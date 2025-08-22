<?php

namespace App\Actions\Leave;

use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\User; // <-- Import User model for type hinting
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

        $leaveType = $request->leave_type;
        $user = $request->user;

        // FIX FOR LINE 24: Removed unnecessary ternary check
        // We know $user is never null here because of the relationship definition.
        $remainingBalance = $user->getRemainingLeaveBalance();

        if ($leaveType === 'personal') {
            return $remainingBalance >= $request->leave_days ? 'personal' : 'paid';
        }

        return match ($leaveType) {
            'annual' => 'annual',
            'sick' => 'sick',
            'emergency' => 'emergency',
            'maternity', 'paternity' => $leaveType,
            'wfh' => 'wfh',
            'compensatory' => 'compensatory',
            default => 'unknown',
        };
    }

    /**
     * Fetch leave requests and prepare data for the frontend.
     */
    public function handle(): array
    {
        /** @var User $user */
        $user = Auth::user();

        // FIX FOR LINE 67: Removed unnecessary null coalescing operator
        // We know comp_off_balance is a float on the User model.
        $compOffBalance = $user->comp_off_balance;

        $requests = LeaveApplication::with(['user:id,name'])
            ->where('user_id', $user->id)
            ->orderByRaw("CASE status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
            ELSE 4
        END")
            ->latest()
            ->paginate(15);

        $leaveEvents = $requests->getCollection()
            ->filter(fn ($request) => in_array($request->status, ['pending', 'approved']))
            ->map(fn (LeaveApplication $request) => [ // Added type hint for clarity
                'start' => $request->start_date->toDateString(),
                'end' => $request->end_date?->toDateString() ?? $request->start_date->toDateString(),
                'title' => ucfirst($request->leave_type).' Leave',
                'class' => $request->status,
                'color_category' => $this->getLeaveColorCategory($request),
                'user_id' => $request->user_id,
            ]);

        $holidayEvents = Holiday::all()->map(function ($holiday) {
            return [
                'start' => $holiday->date->toDateString(),
                'end' => $holiday->date->toDateString(),
                'title' => $holiday->name,
                'class' => 'holiday',
                'color_category' => 'holiday',
                // FIX FOR LINE 86: Changed null to 0 to match the integer type
                'user_id' => 0,
            ];
        });

        $highlighted = $leaveEvents->merge($holidayEvents)->values()->all();

        return [
            'leaveRequests' => $requests,
            'canManage' => false,
            'highlightedDates' => $highlighted,
            'remainingLeaveBalance' => $user->leave_balance,
            'compOffBalance' => $compOffBalance,
        ];
    }
}
