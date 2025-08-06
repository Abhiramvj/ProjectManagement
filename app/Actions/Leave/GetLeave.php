<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use Illuminate\Support\Facades\Auth;

class GetLeave
{
    /**
     * Determine the color category used for frontend display,
     * based on leave type and remaining leave balance.
     */
    private function getLeaveColorCategory(LeaveApplication $request): string
{
    if ($request->status === 'pending') {
        return 'pending';
    }

    $leaveType = $request->leave_type;
    $user = $request->user;
    $remainingBalance = $user ? $user->getRemainingLeaveBalance() : 0;

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
     * Fetch leave requests, annotate with color category,
     * and return data for frontend consumption.
     */
    public function handle(): array
    {
        $user = Auth::user();
        $remainingLeaveBalance = $user->getRemainingLeaveBalance();
        $compOffBalance = $user->comp_off_balance ?? 0;

        // Always fetch only logged-in user's leave requests, regardless of role
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

        $highlighted = $requests->getCollection()->filter(fn ($request) => in_array($request->status, ['pending', 'approved']))
            ->map(fn ($request) => [
                'start' => $request->start_date->toDateString(),
                'end' => $request->end_date ? $request->end_date->toDateString() : null,
                'title' => ucfirst($request->leave_type).' Leave',
                'class' => $request->status,
                'color_category' => $this->getLeaveColorCategory($request),
                'user_id' => $request->user_id,
            ])->values()->all();

        return [
            'leaveRequests' => $requests,
            'canManage' => false, // since you do NOT want manage all requests here
            'highlightedDates' => $highlighted,
            'remainingLeaveBalance' => $remainingLeaveBalance,
            'compOffBalance' => $compOffBalance,
        ];
    }
}
