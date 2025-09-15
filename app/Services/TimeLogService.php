<?php

namespace App\Services;

use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimeLogService
{
    public function getWeeklyHours(User $user, int $weeks = 8): Collection
    {
        $weeklyHours = collect();

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek(Carbon::SUNDAY);

            $hours = TimeLog::where('user_id', $user->id)
                ->whereBetween('work_date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                ->sum('hours_worked');

            $weeklyHours->push([
                'week' => 'Week '.$weekStart->weekOfYear,
                'hours' => (float) $hours,
                'start_date' => $weekStart->format('Y-m-d'),
                'end_date' => $weekEnd->format('Y-m-d'),
                'date_range' => $weekStart->format('M j').' - '.$weekEnd->format('M j'),
                'is_current_week' => $weekStart->isSameWeek(Carbon::now()),
            ]);
        }

        return $weeklyHours;
    }

    public function getDailyHours(User $user, int $days = 30): Collection
    {
        $dailyHours = collect();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $hours = TimeLog::where('user_id', $user->id)
                ->whereDate('work_date', $date->format('Y-m-d'))
                ->sum('hours_worked');

            $dailyHours->push([
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('M j'),
                'hours' => (float) $hours,
                'is_weekend' => $date->isWeekend(),
                'is_today' => $date->isToday(),
            ]);
        }

        return $dailyHours;
    }

    public function getProjectHours(User $user): Collection
    {
        return TimeLog::where('user_id', $user->id)
            ->with('project:id,name')
            ->selectRaw('project_id, SUM(hours_worked) as total_hours')
            ->groupBy('project_id')
            ->get()
            ->map(fn ($item) => [
                'project' => $item->project->name ?? 'No Project',
                'hours' => (float) $item->total_hours,
            ]);
    }

    public function getMonthlyHours(User $user, int $months = 6): Collection
    {
        $monthly = collect();

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $hours = TimeLog::where('user_id', $user->id)
                ->whereMonth('work_date', $month->month)
                ->whereYear('work_date', $month->year)
                ->sum('hours_worked');

            $monthly->push([
                'month' => $month->format('M Y'),
                'hours' => (float) $hours,
                'target' => 160,
            ]);
        }

        return $monthly;
    }

    public function getRecentTimeLogs(User $user, int $limit = 10): Collection
    {
        return TimeLog::where('user_id', $user->id)
            ->with('project:id,name')
            ->orderBy('work_date', 'desc')
            ->limit($limit)
            ->get(['project_id', 'work_date', 'hours_worked', 'description'])
            ->map(fn ($log) => [
                'date' => Carbon::parse($log->work_date)->format('M d, Y'),
                'project' => $log->project->name ?? 'No Project',
                'hours' => $log->hours_worked,
                'description' => $log->description ?? 'No description',
            ]);
    }
}
