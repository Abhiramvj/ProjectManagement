<?php

namespace App\Http\Controllers;

use App\Actions\Leave\GetLeave;
use App\Actions\Leave\StoreLeave;
use App\Actions\Leave\UpdateLeave;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Mail\LeaveApplicationSubmitted;
use Illuminate\Mail\Mailable;
use App\Models\LeaveApplication;
use App\Models\MailLog;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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


        if ($recipients->isNotEmpty()) {
            foreach ($recipients as $recipient) {
                $this->sendAndLogEmail(
                    $leave_application,
                    new LeaveApplicationSubmitted($leave_application),
                    'leave_application_submitted',
                    $recipient->email
                );
            }
        }


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
        $data = $request->validated();
        $status = $data['status'];
        $rejectReason = $data['rejection_reason'] ?? null;

        $updateLeaveStatus->handle($leave_application, $status, $rejectReason);

        // Deduct comp_off_balance on approval if leave type is compensatory
        if ($status === 'approved' && $leave_application->leave_type === 'compensatory') {
            $user = $leave_application->user;
            $user->decrement('comp_off_balance', $leave_application->leave_days);
            $user->save();
        }

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

        // --- ADD THIS HELPER FUNCTION AT THE END OF THE CLASS ---
    /**
     * Send an email and create a log entry in MongoDB.
     *
     * @param LeaveApplication $leaveApplication The leave application instance.
     * @param Mailable $mailable The Mailable class instance to be sent.
     * @param string $eventType A string representing the event (e.g., 'leave_application_approved').
     * @param string $recipientEmail The email address of the recipient.
     */
    private function sendAndLogEmail(LeaveApplication $leaveApplication, Mailable $mailable, string $eventType, string $recipientEmail)
    {
        // Assumes your Mailable class has a public 'subject' property.
        $subject = $mailable->subject ?? 'Leave Application Notification';

        try {
            Mail::to($recipientEmail)->send($mailable);

            MailLog::create([
                'leave_application_id' => $leaveApplication->id,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'status' => 'sent',
                'event_type' => $eventType,
                'sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            // Log the error to your default log file for immediate debugging
            Log::error('Mail sending failed for leave application ' . $leaveApplication->id . ': ' . $e->getMessage());

            // Create a record of the failure in MongoDB
            MailLog::create([
                'leave_application_id' => $leaveApplication->id,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'status' => 'failed',
                'event_type' => $eventType,
                'error_message' => $e->getMessage(),
                'sent_at' => now(),
            ]);
        }
    }
}
