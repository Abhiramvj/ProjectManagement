<?php

namespace App\Http\Controllers;

use App\Actions\Leave\GetLeave;
use App\Actions\Leave\StoreLeave;
use App\Actions\Leave\UpdateLeave;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Jobs\SendLeaveEmail; // Import the job for queuing emails
use App\Mail\LeaveApplicationApproved;
use App\Mail\LeaveApplicationRejected;
use App\Mail\LeaveApplicationSubmitted;
use App\Models\LeaveApplication;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;


/**
 * @property-read User $user // <-- ADD THIS LINE
 */
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
                // Dispatch the email job to the queue instead of sending it synchronously
                SendLeaveEmail::dispatch(
                    $leave_application,
                    new LeaveApplicationSubmitted($leave_application),
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

        return redirect()->back()->with('success', "Compensatory off of {$daysToCredit} days credited to user.");
    }

    public function update(UpdateLeaveRequest $request, LeaveApplication $leave_application, UpdateLeave $updateLeaveStatus)
    {
        $validatedData = $request->validated();
        $status = $validatedData['status'];

        if ($status === 'approved') {
            // --- APPROVAL LOGIC ---
            $updateLeaveStatus->handle($leave_application, 'approved');

            $employee = $leave_application->user;
            if ($employee) {
                $mailable = new LeaveApplicationApproved($leave_application);
                // Dispatch the approval email to the queue
                SendLeaveEmail::dispatch($leave_application, $mailable, $employee->email);
            }

        } elseif ($status === 'rejected') {
            // --- REJECTION LOGIC ---
            $rejectReason = $validatedData['rejection_reason'] ?? 'No reason provided.';
            $updateLeaveStatus->handle($leave_application, 'rejected', $rejectReason);

            // Restore the user's leave balance
            $user = $leave_application->user;
            if ($user) {
                if (in_array($leave_application->leave_type, ['annual', 'casual'])) {
                    $user->leave_balance += $leave_application->leave_days;
                } elseif ($leave_application->leave_type === 'compensatory') {
                    $user->comp_off_balance += $leave_application->leave_days;
                }
                $user->save();
            }

            // Prepare and send the rejection email
            if ($user) {
                $mailable = new LeaveApplicationRejected($leave_application, $rejectReason);
                // Dispatch the rejection email to the queue
                SendLeaveEmail::dispatch($leave_application, $mailable, $user->email);
            }
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
        Log::info('Calendar request received', ['query_params' => $request->all(), 'user_id' => Auth::id()]);

        $validated = $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'team_id' => 'nullable|integer|exists:teams,id',
            'search' => 'nullable|string|max:100',
            'absent_only' => 'nullable|in:1,true',
        ]);

        $startDate = !empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = !empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : (!empty($validated['start_date'])
                ? Carbon::parse($validated['start_date'])->endOfDay()
                : Carbon::now()->endOfMonth());

        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        $usersQuery = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['employee', 'team-lead', 'project-manager']));

        if (!empty($validated['team_id'])) {
            $usersQuery->whereHas('teams', function ($q) use ($validated) {
                $q->where('teams.id', $validated['team_id']);
            });
        }

        if (!empty($validated['search'])) {
            $usersQuery->where('name', 'like', '%' . $validated['search'] . '%');
        }

        if (!empty($validated['absent_only'])) {
            $usersQuery->whereHas('leaveApplications', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'approved')
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->where('start_date', '<=', $endDate->toDateString())
                              ->where('end_date', '>=', $startDate->toDateString());
                    });
            });
        }

        $usersWithLeaves = $usersQuery
            ->select('id', 'name')
            ->with([
                'teams:id,name',
                'leaveApplications' => function ($query) use ($startDate, $endDate) {
                    $query->select('id', 'user_id', 'start_date', 'end_date', 'reason', 'status')
                        ->where('status', 'approved')
                        ->where(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $endDate->toDateString())
                              ->where('end_date', '>=', $startDate->toDateString());
                        });
                },
            ])
            ->orderBy('name')
            ->get();

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

        $path = $request->file('supporting_document')->store('leave_documents/' . auth()->id(), 'public');

        // Delete old file if it exists
        if ($leave_application->supporting_document_path) {
            Storage::disk('public')->delete($leave_application->supporting_document_path);
        }

        $leave_application->update([
            'supporting_document_path' => $path,
        ]);

        return redirect()->back()->with('success', 'Supporting document uploaded successfully.');
    }


}
