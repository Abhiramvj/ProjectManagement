<?php

namespace App\Services;

use App\Models\LeaveApplication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; // Added for logging

class LeaveService
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get cached leave balance for a user
     */
    public function getUserLeaveBalance(User $user, ?int $year = null): float
    {
        $year = $year ?? Carbon::now()->year;
        $cacheKey = "leave_balance_{$user->id}_{$year}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $year) {
            $usedDays = LeaveApplication::forUser($user->id)
                ->approved()
                ->whereYear('start_date', $year)
                ->sum('leave_days');

            $totalBalance = $user->leave_balance ?? 20;

            return max(0, $totalBalance - $usedDays);
        });
    }

    /**
     * Get cached leave statistics for a user
     */
    public function getUserLeaveStatistics(User $user, ?int $year = null): array
    {
        $year = $year ?? Carbon::now()->year;
        $cacheKey = "leave_stats_{$user->id}_{$year}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $year) {
            $stats = LeaveApplication::forUser($user->id)
                ->whereYear('start_date', $year)
                ->selectRaw('
                    status,
                    COUNT(*) as count,
                    SUM(leave_days) as total_days
                ')
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            $totalLeaveBalance = $user->leave_balance ?? 20;
            $usedDays = $stats->get('approved')->total_days ?? 0;
            $pendingDays = $stats->get('pending')->total_days ?? 0;

            return [
                'total_balance' => $totalLeaveBalance,
                'used_days' => $usedDays,
                'pending_days' => $pendingDays,
                'remaining_balance' => max(0, $totalLeaveBalance - $usedDays),
                'available_balance' => max(0, $totalLeaveBalance - $usedDays - $pendingDays),
                'approved_applications' => $stats->get('approved')->count ?? 0,
                'pending_applications' => $stats->get('pending')->count ?? 0,
                'rejected_applications' => $stats->get('rejected')->count ?? 0,
            ];
        });
    }

    /**
     * Check if dates overlap with existing leave applications
     */
    public function hasOverlappingLeave(User $user, Carbon $startDate, Carbon $endDate, ?int $excludeId = null): bool
    {
        $query = LeaveApplication::forUser($user->id)
            ->where('status', '!=', 'rejected')
            ->overlapsWith($startDate, $endDate); // <-- This now works correctly

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get leave applications for calendar view with optimized queries
     */
    public function getLeaveApplicationsForCalendar(Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $cacheKey = "calendar_leaves_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}_".md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_TTL / 4, function () use ($startDate, $endDate, $filters) {
            $usersQuery = User::query()
                ->with([
                    'leaveApplications' => function ($query) use ($startDate, $endDate) {
                        $query->approved()
                            ->overlapsWith($startDate, $endDate) // <-- This now works correctly
                            ->select('id', 'user_id', 'start_date', 'end_date', 'leave_type', 'day_type', 'leave_days');
                    },
                    'teams:id,name',
                ])
                ->select('id', 'name')
                ->orderBy('name');

            if (! empty($filters['employee_name'])) {
                $usersQuery->where('name', 'like', "%{$filters['employee_name']}%");
            }

            if (! empty($filters['team_id'])) {
                $usersQuery->whereHas('teams', function ($teamQuery) use ($filters) {
                    $teamQuery->where('teams.id', $filters['team_id']);
                });
            }

            return $usersQuery->get()->toArray();
        });
    }

    /**
     * Clear cache for a user's leave data
     */
    public function clearUserLeaveCache(User $user, ?int $year = null): void
    {
        $year = $year ?? Carbon::now()->year;
        Cache::forget("leave_balance_{$user->id}_{$year}");
        Cache::forget("leave_stats_{$user->id}_{$year}");
        // A more targeted cache clearing might be needed for the calendar if it becomes complex
        Cache::flush();
    }

    /**
     * Get leave type colors for display
     */
    public function getLeaveTypeColors(): array
    {
        return [
            'sick' => '#ff9800', 'annual' => '#EF5350', 'personal' => '#9C27B0',
            'emergency' => '#F44336', 'maternity' => '#E91E63', 'paternity' => '#3F51B5',
        ];
    }

    /**
     * Calculate leave days for a date range considering weekends and half days
     */
    public function calculateLeaveDays(Carbon $startDate, Carbon $endDate, string $dayType = 'full', array $data = []): float
    {
        Log::info('LeaveService calculateLeaveDays called', [
            'startDate' => $startDate->toDateString(), 'endDate' => $endDate->toDateString(), 'dayType' => $dayType,
            'start_half_session' => $data['start_half_session'] ?? 'not set',
            'end_half_session' => $data['end_half_session'] ?? 'not set',
        ]);

        if ($dayType === 'half') {
            if ($startDate->isSameDay($endDate)) {
                return 0.5;
            } else {
                $totalDays = $startDate->diffInDaysFiltered(fn ($date) => ! $date->isWeekend(), $endDate) + 1;
                $deduction = 0;
                if (($data['start_half_session'] ?? null) === 'afternoon') {
                    $deduction += 0.5;
                }
                if (($data['end_half_session'] ?? null) === 'morning') {
                    $deduction += 0.5;
                }

                return max(0.5, $totalDays - $deduction);
            }
        }

        return $startDate->diffInDaysFiltered(fn ($date) => ! $date->isWeekend(), $endDate) + 1;
    }
}
