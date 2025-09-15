<?php

namespace App\Actions\Performance;

use App\Models\TimeLog;
use App\Models\User;
use App\Services\TimeLogService;
use Carbon\Carbon;

class GetTimeStatsAction
{
    protected TimeLogService $timeLogService;

    public function __construct(TimeLogService $timeLogService)
    {
        $this->timeLogService = $timeLogService;
    }

    public function handle(User $user): array
    {
        $timeStats = [
            'total_hours' => TimeLog::where('user_id', $user->id)->sum('hours_worked'),
            'current_month' => TimeLog::where('user_id', $user->id)
                ->whereMonth('work_date', Carbon::now()->month)
                ->whereYear('work_date', Carbon::now()->year)
                ->sum('hours_worked') ?? 0,
            'last_month' => TimeLog::where('user_id', $user->id)
                ->whereMonth('work_date', Carbon::now()->subMonth()->month)
                ->whereYear('work_date', Carbon::now()->subMonth()->year)
                ->sum('hours_worked') ?? 0,
            'daily_average' => 0,
        ];

        if ($timeStats['total_hours'] > 0) {
            $firstLog = TimeLog::where('user_id', $user->id)->orderBy('work_date')->first();
            if ($firstLog) {
                $days = Carbon::parse($firstLog->work_date)->diffInDays(Carbon::now()) + 1;
                $timeStats['daily_average'] = round($timeStats['total_hours'] / $days, 1);
            }
        }

        $weeklyHours = $this->timeLogService->getWeeklyHours($user);
        $dailyHours = $this->timeLogService->getDailyHours($user);
        $projectHours = $this->timeLogService->getProjectHours($user);
        $monthlyHours = $this->timeLogService->getMonthlyHours($user);
        $recentTimeLogs = $this->timeLogService->getRecentTimeLogs($user);

        return [
            'timeStats' => $timeStats,
            'weeklyHours' => $weeklyHours->toArray(),
            'dailyHours' => $dailyHours->toArray(),
            'projectHours' => $projectHours->toArray(),
            'monthlyHours' => $monthlyHours->toArray(),
            'recentTimeLogs' => $recentTimeLogs->toArray(),
        ];
    }
}
