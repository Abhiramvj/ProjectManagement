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
     * (This method is unchanged)
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
     * Fetch leave requests and other data for the frontend.
     */
    public function handle(): array
    {
        /** @var User $user */
        $user = Auth::user();

        // 1. Fetch employees list for admin/hr dropdown WITH their leave stats
        $employees = [];
    if ($user->hasAnyRole(['admin', 'hr'])) {
        $employees = User::select(
                'id', 'name', 'leave_balance', 'comp_off_balance',
                'total_annual_leave', 'total_sick_leave', 'total_personal_leave' // Select new columns
            )
            ->withSum('approvedAnnualLeaves', 'leave_days')
            ->withSum('approvedSickLeaves', 'leave_days')
            ->withSum('approvedPersonalLeaves', 'leave_days')
            ->orderBy('name')
            ->get()
            ->map(fn ($emp) => [
                'id' => $emp->id,
                'name' => $emp->name,
                'leave_balance' => $emp->leave_balance,
                'comp_off_balance' => $emp->comp_off_balance,
                'stats' => [
                    'annual' => ['taken' => $emp->approved_annual_leaves_sum_leave_days ?? 0, 'total' => $emp->total_annual_leave],
                    'sick' => ['taken' => $emp->approved_sick_leaves_sum_leave_days ?? 0, 'total' => $emp->total_sick_leave],
                    'personal' => ['taken' => $emp->approved_personal_leaves_sum_leave_days ?? 0, 'total' => $emp->total_personal_leave],
                ]
            ]);
    }

        // NEW: 2. Get leave stats FOR THE LOGGED-IN USER
        $currentUserStats = [
        'annual' => ['taken' => $user->approvedAnnualLeaves()->sum('leave_days'), 'total' => $user->total_annual_leave],
        'sick' => ['taken' => $user->approvedSickLeaves()->sum('leave_days'), 'total' => $user->total_sick_leave],
        'personal' => ['taken' => $user->approvedPersonalLeaves()->sum('leave_days'), 'total' => $user->total_personal_leave],
    ];

        // 3. Build the query for calendar events (Unchanged)
        $leaveEventsQuery = LeaveApplication::with('user:id,name')
            ->whereIn('status', ['pending', 'approved']);

        if (! $user->hasAnyRole(['admin', 'hr'])) {
            $leaveEventsQuery->where('user_id', $user->id);
        }

        $leaveEvents = $leaveEventsQuery->get()
            ->map(fn ($request) => [
                'user_id' => $request->user_id,
                'start' => $request->start_date->toDateString(),
                'end' => $request->end_date?->toDateString() ?? $request->start_date->toDateString(),
                'title' => ucfirst($request->leave_type).' Leave',
                'class' => $request->status,
                'color_category' => $this->getLeaveColorCategory($request),
            ]);

        // 4. Get holiday events (Unchanged)
        $holidayEvents = Holiday::all()->map(fn ($holiday) => [
            'start' => $holiday->date->toDateString(),
            'end' => null,
            'title' => $holiday->name,
            'class' => 'holiday',
            'color_category' => 'holiday',
            'user_id' => null,
        ]);

        $highlighted = $leaveEvents->merge($holidayEvents)->values()->all();

        // 5. Get paginated requests for "Your Requests" modal (Unchanged)
        $requests = LeaveApplication::with(['user:id,name'])
            ->where('user_id', $user->id)
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END")
            ->latest()
            ->paginate(15);

        // 6. Return all data to the Vue component
        return [
        'leaveRequests' => $requests,
        'highlightedDates' => $highlighted,
        'remainingLeaveBalance' => $user->leave_balance,
        'compOffBalance' => $user->comp_off_balance,
        'employees' => $employees,
        'leaveStats' => $currentUserStats,
        'canManage' => false,
    ];
    }
}
