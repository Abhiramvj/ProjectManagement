<?php

namespace App\Actions\Performance;

use App\Models\LeaveApplication;
use App\Models\User;
use Carbon\Carbon;

class GetLeaveStatsAction
{
    public function handle(User $user): array
    {
        $approvedLeave = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->get();

        $totalAllowance = $user->leave_allowance ?? 20;

        $currentYearLeave = $approvedLeave->filter(fn ($leave) => Carbon::parse($leave->start_date)->year === Carbon::now()->year
        )->sum(fn ($leave) => Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1);

        $leaveStats = [
            'current_year' => $currentYearLeave,
            'balance' => $totalAllowance,
            'remaining' => max(0, $totalAllowance - $currentYearLeave),
            'total_days' => $approvedLeave->sum(fn ($leave) => Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1),
        ];

        $recentLeave = LeaveApplication::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['start_date', 'end_date', 'reason', 'status', 'created_at'])
            ->map(fn ($leave) => [
                'start_date' => Carbon::parse($leave->start_date)->format('M d, Y'),
                'end_date' => Carbon::parse($leave->end_date)->format('M d, Y'),
                'reason' => $leave->reason ?? 'No reason provided',
                'status' => $leave->status,
                'days' => Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1,
            ]);

        return [
            'leaveStats' => $leaveStats,
            'recentLeave' => $recentLeave->toArray(),
        ];
    }
}
