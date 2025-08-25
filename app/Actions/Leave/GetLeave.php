<?php

namespace App\Actions\Leave;

use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GetLeave
{
    /**
     * Determine the color category used for frontend display.
     */
    private function getLeaveColorCategory(LeaveApplication $request): string
    {
        if ($request->status === 'pending') {
            return 'pending';
        }

        return match ($request->leave_type) {
            'annual' => 'annual',
            'sick' => 'sick',
            'personal' => 'personal',
            'emergency' => 'emergency',
            'maternity', 'paternity' => $request->leave_type,
            'wfh' => 'wfh',
            'compensatory' => 'compensatory',
            default => 'unknown',
        };
    }

    /**
     * Fetch all necessary data for the Leave application page.
     * This version is optimized to NOT load all employees on initial page load.
     */
    public function handle(): array
    {
        $user = Auth::user();

        // --- PERFORMANCE FIX ---
        // The 'employees' prop is now always an empty array on initial load.
        // The searchable dropdown will fetch users asynchronously as needed.
        $employees = [];

        // Get leave stats ONLY for the currently logged-in user.
        $currentUserStats = [
            'annual' => ['taken' => $user->approvedAnnualLeaves()->sum('leave_days'), 'total' => $user->total_annual_leave],
            'sick' => ['taken' => $user->approvedSickLeaves()->sum('leave_days'), 'total' => $user->total_sick_leave],
            'personal' => ['taken' => $user->approvedPersonalLeaves()->sum('leave_days'), 'total' => $user->total_personal_leave],
        ];

        // Build the query for calendar events. This logic is correct.
        $leaveEventsQuery = LeaveApplication::with('user:id,name')
            ->whereIn('status', ['pending', 'approved']);

        // The calendar initially shows events for the logged-in user.
        // If an admin selects another user via the search box, the page will reload
        // with that user's ID, and this query will correctly show their events.
        $displayUserId = request('user_id', $user->id);

        if ($user->hasAnyRole(['admin', 'hr'])) {
             // Admin can see the selected user's calendar, or their own by default
             $leaveEventsQuery->where('user_id', $displayUserId);
        } else {
            // Non-admins can only ever see their own calendar
            $leaveEventsQuery->where('user_id', $user->id);
        }

        $leaveEvents = $leaveEventsQuery->get()
            ->map(fn ($request) => [
                'user_id' => $request->user_id,
                'start' => $request->start_date->toDateString(),
                'end' => $request->end_date ? $request->end_date->toDateString() : null,
                'title' => ucfirst($request->leave_type).' Leave',
                'class' => $request->status,
                'color_category' => $this->getLeaveColorCategory($request),
            ]);

        // Get holiday events. This is correct.
        $holidayEvents = Holiday::all()->map(fn ($holiday) => [
            'start' => $holiday->date->toDateString(),
            'end' => null,
            'title' => $holiday->name,
            'class' => 'holiday',
            'color_category' => 'holiday',
            'user_id' => null,
        ]);

        $highlighted = $leaveEvents->merge($holidayEvents)->values()->all();

        // Get paginated requests for "Your Requests" modal. This is correct.
        $requests = LeaveApplication::with(['user:id,name'])
            ->where('user_id', $user->id)
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END")
            ->latest()
            ->paginate(15);

        // Return all data to the Vue component.
        return [
            'leaveRequests' => $requests,
            'highlightedDates' => $highlighted,
            'remainingLeaveBalance' => $user->leave_balance,
            'compOffBalance' => $user->comp_off_balance,
            'employees' => $employees, // This is now an empty array.
            'leaveStats' => $currentUserStats,
            'canManage' => false,
        ];
    }
}