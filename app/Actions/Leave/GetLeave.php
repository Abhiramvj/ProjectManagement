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
     * Fetch leave requests and other data for the frontend.
     */
    public function handle(): array
    {
        $user = Auth::user();

        // 1. Fetch employees list for admin/hr dropdown
        $employees = [];
        if ($user->hasAnyRole(['admin', 'hr'])) {
            $employees = User::select('id', 'name', 'leave_balance', 'comp_off_balance')
                ->orderBy('name')
                ->get()
                ->map(fn ($emp) => [
                    'id' => $emp->id,
                    'name' => $emp->name,
                    'leave_balance' => $emp->leave_balance,
                    'comp_off_balance' => $emp->comp_off_balance,
                ]);
        }

        // 2. Build the query for calendar events
        $leaveEventsQuery = LeaveApplication::with('user:id,name')
            ->whereIn('status', ['pending', 'approved']);

        // If user is NOT admin/hr, restrict the query to their own leaves
        if (! $user->hasAnyRole(['admin', 'hr'])) {
            $leaveEventsQuery->where('user_id', $user->id);
        }

        // Execute query and map results for the calendar
        $leaveEvents = $leaveEventsQuery->get()
            ->map(fn ($request) => [
                'user_id' => $request->user_id,
                'start' => $request->start_date->toDateString(),
                'end' => $request->end_date ? $request->end_date->toDateString() : null,
                'title' => ucfirst($request->leave_type).' Leave',
                'class' => $request->status,
                'color_category' => $this->getLeaveColorCategory($request),
            ]);

        // 3. Get holiday events
        $holidayEvents = Holiday::all()->map(fn ($holiday) => [
            'start' => $holiday->date->toDateString(),
            'end' => null,
            'title' => $holiday->name,
            'class' => 'holiday',
            'color_category' => 'holiday',
            'user_id' => null,
        ]);

        // Merge all events for the calendar display
        $highlighted = $leaveEvents->merge($holidayEvents)->values()->all();

        // 4. Get paginated requests FOR THE LOGGED-IN USER (for the "Your Requests" modal)
        $requests = LeaveApplication::with(['user:id,name'])
            ->where('user_id', $user->id)
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END")
            ->latest()
            ->paginate(15);

        // 5. Return all data to the Vue component
        return [
            'leaveRequests' => $requests,
            'highlightedDates' => $highlighted,
            'remainingLeaveBalance' => $user->leave_balance,
            'compOffBalance' => $user->comp_off_balance,
            'employees' => $employees,
            'canManage' => false,
        ];
    }
}
