<?php

namespace App\Services;

use App\Models\TimeLog;
use Illuminate\Support\Facades\DB;

class TimeStatsService
{
    /**
     * Get general time logging stats for a user.

     */
    public function getStatsForUser(int $userId): array
    {
        // [+] MODIFIED: Simply sum the 'hours_worked' column. No calculation needed.
        $totalHours = TimeLog::where('user_id', $userId)->sum('hours_worked');

        // [+] MODIFIED: Sum 'hours_worked' for the current month.
        $hoursThisMonth = TimeLog::where('user_id', $userId)
            ->whereYear('work_date', now()->year)
            ->whereMonth('work_date', now()->month)
            ->sum('hours_worked');

        // [+] MODIFIED: Use 'work_date' to find distinct days.
        $distinctDaysLogged = TimeLog::where('user_id', $userId)
            ->select('work_date')
            ->distinct()
            ->count();

        $dailyAverage = ($distinctDaysLogged > 0) ? round($totalHours / $distinctDaysLogged, 2) : 0;

        return [
            'total_hours' => round($totalHours, 2),
            'current_month' => round($hoursThisMonth, 2),
            'daily_average' => $dailyAverage,
        ];
    }

    /**
     * Get hours logged per week for the last X weeks.

     */
    public function getWeeklyHoursForUser(int $userId, int $weekCount = 8): array
    {
        $endDate = now()->endOfWeek();
        $startDate = now()->subWeeks($weekCount - 1)->startOfWeek();

        // [+] MODIFIED: Group by 'work_date' and sum 'hours_worked'.
        $logs = TimeLog::where('user_id', $userId)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->select(
                DB::raw('YEAR(work_date) as year, WEEK(work_date, 1) as week'),
                DB::raw('SUM(hours_worked) as total_hours')
            )
            ->groupBy('year', 'week')
            ->orderBy('year', 'asc')
            ->orderBy('week', 'asc')
            ->get()->keyBy(fn($item) => $item->year . '-' . $item->week);


        $weeklyHours = [];
        for ($i = 0; $i < $weekCount; $i++) {
            $date = now()->subWeeks($i);
            $year = $date->year;
            $week = $date->weekOfYear;

            $key = $year . '-' . $week;


            $hours = $logs->has($key) ? $logs[$key]->total_hours : 0;

            $weeklyHours[] = [

                'week' => 'Week ' . $week,
                'hours' => round((float)$hours, 2),

            ];
        }

        return array_reverse($weeklyHours);
    }
}
