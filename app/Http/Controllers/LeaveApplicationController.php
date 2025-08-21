<?php

namespace App\Http\Controllers;

use App\Actions\Leave\GetLeave;
use App\Actions\Leave\StoreLeave;
use App\Actions\Leave\UpdateLeave;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Mail\LeaveApplicationApproved;
use App\Mail\LeaveApplicationRejected;
use App\Mail\LeaveApplicationSubmitted;
use App\Models\LeaveApplication;
use App\Models\MailLog;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LeaveApplicationController extends Controller
{
    public function index(GetLeave $getLeaveRequests)
    {
        return Inertia::render('Leave/Index', $getLeaveRequests->handle());
    }

    public function store(StoreLeaveRequest $request, StoreLeave $storeLeave)
    {
        $leave_application = $storeLeave->handle($request->validated());

        $recipients = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'hr']);
        })->get();

        // if ($recipients->isNotEmpty()) {
        //     foreach ($recipients as $recipient) {
        //         $this->sendEmail(
        //             $leave_application,
        //             new LeaveApplicationSubmitted($leave_application),
        //             $recipient->email
        //         );
        //     }
        // }

        return redirect()->route('leave.index')->with('success', 'Leave application submitted.');
    }

    public function approveCompOff(Request $request, User $user)
    {
        $request->validate([
            'comp_off_days' => ['required', 'numeric', 'min:0.5'],
        ]);

        $daysToCredit = $request->input('comp_off_days');

        // Increment user's comp_off_balance
        $user->increment('comp_off_balance', $daysToCredit);

        // Optionally notify user of new comp off balance (implement notification if desired)
        // $user->notify(new CompOffApprovedNotification($daysToCredit));

        return redirect()->back()->with('success', "Compensatory off of {$daysToCredit} days credited to user.");
    }

    public function update(UpdateLeaveRequest $request, LeaveApplication $leave_application, UpdateLeave $updateLeaveStatus)
    {
        // 1. Get validated data. This is correct.
        $validatedData = $request->validated();
        $status = $validatedData['status'];

        // --- THIS IS THE KEY CHANGE ---
        // We now handle everything based on the status.
        if ($status === 'approved') {
            // --- APPROVAL LOGIC ---

            // A. Update the status in the database.
            $updateLeaveStatus->handle($leave_application, 'approved');

            // B. Prepare and send the approval email.
            // $employee = $leave_application->user;
            // if ($employee) {
            //     $mailable = new LeaveApplicationApproved($leave_application);
            //     $this->sendEmail($leave_application, $mailable, $employee->email);
            // }

        } elseif ($status === 'rejected') {
            // --- REJECTION LOGIC ---

            // A. Get the rejection reason from the validated data.
            // We only access it here, where we know it's safe.
            $rejectReason = $validatedData['rejection_reason'] ?? 'No reason provided.';

            // B. Update the status in the database.
            $updateLeaveStatus->handle($leave_application, 'rejected', $rejectReason);

            // C. Restore the user's leave balance.
            $user = $leave_application->user;
            if ($user) {
                if (in_array($leave_application->leave_type, ['annual', 'personal'])) {
                    $user->leave_balance += $leave_application->leave_days;
                } elseif ($leave_application->leave_type === 'compensatory') {
                    $user->comp_off_balance += $leave_application->leave_days;
                }
                $user->save();
            }

            // D. Prepare and send the rejection email.
            // if ($user) {
            //     $mailable = new LeaveApplicationRejected($leave_application, $rejectReason);
            //     $this->sendEmail($leave_application, $mailable, $user->email);
            // }
        }

        // --- REDIRECT BACK ---
        return Redirect::back()->with('success', 'Application status updated.');
    }

    public function updateReason(Request $request, LeaveApplication $leave_application)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $leave_application->update(['reason' => $validated['reason']]);

        return back()->with('success', 'Reason updated.');
    }

    public function calendar(Request $request)
    {
        // Log incoming request for debugging
        Log::info('Calendar request received', [
            'query_params' => $request->all(),
            'user_id' => Auth::id(),
        ]);

        $validated = $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'team_id' => 'nullable|integer|exists:teams,id',
            'search' => 'nullable|string|max:100',
            'absent_only' => 'nullable|in:1,true',
        ]);

        // Better date handling with logging
        $startDate = isset($validated['start_date']) && ! empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = isset($validated['end_date']) && ! empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : (isset($validated['start_date']) && ! empty($validated['start_date'])
                ? Carbon::parse($validated['start_date'])->endOfDay()  // Same day if only start_date provided
                : Carbon::now()->endOfMonth());

        // Ensure end date is not before start date
        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        Log::info('Date range calculated', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'validated' => $validated,
        ]);

        // Build users query
        $usersQuery = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['employee', 'team-lead', 'project-manager']));

        // Apply team filter - using many-to-many relationship
        if (! empty($validated['team_id'])) {
            $usersQuery->whereHas('teams', function ($q) use ($validated) {
                $q->where('teams.id', $validated['team_id']);
            });
            Log::info('Applied team filter', ['team_id' => $validated['team_id']]);
        }

        // Apply search filter
        if (! empty($validated['search'])) {
            $usersQuery->where('name', 'like', '%'.$validated['search'].'%');
            Log::info('Applied search filter', ['search' => $validated['search']]);
        }

        // Apply absent only filter
        if (! empty($validated['absent_only'])) {
            $usersQuery->whereHas('leaveApplications', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'approved')
                    ->where(function ($query) use ($startDate, $endDate) {
                        // Leave overlaps with date range
                        $query->where(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $endDate->toDateString())
                                ->where('end_date', '>=', $startDate->toDateString());
                        });
                    });
            });
            Log::info('Applied absent only filter');
        }

        // Get users with their leave applications
        $usersWithLeaves = $usersQuery
            ->select('id', 'name')
            ->with([
                'teams:id,name', // Load team relationship
                'leaveApplications' => function ($query) use ($startDate, $endDate) {
                    $query->select('id', 'user_id', 'start_date', 'end_date', 'reason', 'status')
                        ->where('status', 'approved')
                        ->where(function ($q) use ($startDate, $endDate) {
                            // Leave overlaps with date range
                            $q->where('start_date', '<=', $endDate->toDateString())
                                ->where('end_date', '>=', $startDate->toDateString());
                        });
                },
            ])
            ->orderBy('name')
            ->get();

        Log::info('Query results', [
            'users_count' => $usersWithLeaves->count(),
            'users_with_leaves' => $usersWithLeaves->filter(function ($user) {
                return $user->leaveApplications->count() > 0;
            })->count(),
        ]);

        // Get all teams for filter dropdown
        $teams = Team::select('id', 'name')->orderBy('name')->get();

        $response = [
            'usersWithLeaves' => $usersWithLeaves,
            'teams' => $teams,
            'filters' => $validated,
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];

        Log::info('Sending response', [
            'date_range' => $response['dateRange'],
            'filters' => $response['filters'],
            'users_count' => $usersWithLeaves->count(),
        ]);

        return Inertia::render('Leave/Calendar', $response);
    }

    public function cancel(LeaveApplication $leave_application)
    {
        if ($leave_application->user_id !== auth()->id() || $leave_application->status !== 'pending') {
            abort(403, 'Unauthorized action.');
        }

        $leave_application->delete();

        return Redirect::route('leave.index')->with('success', 'Leave request canceled.');
    }

    public function uploadDocument(Request $request, LeaveApplication $leave_application)
    {
        if ($leave_application->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'supporting_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->file('supporting_document')->store('leave_documents/'.auth()->id(), 'public');

        // Delete old file if exists
        if ($leave_application->supporting_document_path) {
            Storage::disk('public')->delete($leave_application->supporting_document_path);
        }

        $leave_application->update([
            'supporting_document_path' => $path,
        ]);

        return redirect()->back()->with('success', 'Supporting document uploaded successfully.');
    }

    // private function sendEmail(LeaveApplication $leaveApplication, Mailable $mailable, string $recipientEmail): void
    // {
    //     // --- STEP 1: RENDER THE MAIL TO HTML ---
    //     // This is the most important new line. It takes your Markdown template
    //     // and converts it into a complete HTML string, exactly as it will be sent.
    //     $emailHtmlContent = $mailable->render();

    //     // Get the event type directly from the mailable's public property.
    //     $eventType = $mailable->eventType ?? 'unknown_event';

    //     // --- STEP 2: PREPARE DATA FOR MONGODB ---
    //     // Now we prepare the data array that will be saved to your database.
    //     $logData = [
    //         'leave_application_id' => $leaveApplication->id,
    //         'recipient_email' => $recipientEmail,
    //         'subject' => $mailable->subject ?? 'Leave Application Notification',
    //         'event_type' => $eventType,
    //         'sent_at' => now(),
    //         'reason' => $leaveApplication->reason,
    //         'leave_period' => $leaveApplication->start_date->format('M d, Y').' to '.$leaveApplication->end_date->format('M d, Y'),

    //         // --- Add the rendered HTML to the log data ---
    //         'body_html' => $emailHtmlContent,
    //     ];

    //     try {
    //         // Send the actual email.
    //         Mail::to($recipientEmail)->send($mailable);

    //         // LOG SUCCESS: The $logData array now includes the 'body_html'
    //         MailLog::create(array_merge($logData, [
    //             'status' => 'sent',
    //             'error_message' => null,
    //         ]));

    //     } catch (\Exception $e) {
    //         // LOG FAILURE
    //         Log::error(
    //             'Mail sending failed for LeaveApplication ID '.$leaveApplication->id.
    //             ' to '.$recipientEmail.': '.$e->getMessage()
    //         );
    //         // The log data still includes the HTML, so you can see what was supposed to be sent.
    //         MailLog::create(array_merge($logData, [
    //             'status' => 'failed',
    //             'error_message' => $e->getMessage(),
    //         ]));
    //     }
    // }

    // private function getEventTypeFromMailable(Mailable $mailable): string
    // {
    //     // This handles modern Laravel Mailables (Laravel 9+)
    //     if (method_exists($mailable, 'headers')) {
    //         // NEW, CORRECT WAY: get the header and check if the result is not null.
    //         $header = $mailable->headers()->get('X-Event-Type');

    //         if ($header) {
    //             // The getBodyAsString() method safely returns the header's value.
    //             return $header->getBodyAsString();
    //         }
    //     }

    //     // Fallback if the header isn't set or for older mailables.
    //     // It uses the Mailable's class name as the event type.
    //     $path = explode('\\', get_class($mailable));

    //     return array_pop($path);
    // }
}
