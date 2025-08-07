<?php

namespace App\Services;

use App\Models\LeaveApplication;
use App\Models\User;

class LeaveStatsService
{
    // You can make this dynamic later, e.g., from a user's contract or a settings table
    const ANNUAL_LEAVE_ALLOWANCE = 20;

    /**
     * Get leave statistics for a specific user.
     */
    public function getStatsForUser(int $userId): array
    {
        $balance = self::ANNUAL_LEAVE_ALLOWANCE;

        // [+] MODIFIED: Changed 'duration_in_days' to the correct column name 'leave_days'
        $currentYearTaken = LeaveApplication::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->sum('leave_days'); // <-- THIS IS THE FIX

        $remaining = $balance - $currentYearTaken;

        return [
            'balance' => $balance,
            'current_year' => (float) $currentYearTaken, // Cast to float for consistency
            'remaining' => $remaining,
        ];
    }
}
