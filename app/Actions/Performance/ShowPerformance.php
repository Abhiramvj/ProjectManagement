<?php

// app/Actions/Performance/ShowPerformance.php

namespace App\Actions\Performance;

use App\Models\LeaveApplication;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;

class ShowPerformance
{
    public function handle(User $user): array
    {
        $tasks = Task::where('assigned_to_id', $user->id);

        // Enhanced task statistics
        $taskStats = [
            'total' => $tasks->count(),
            'completed' => (clone $tasks)->where('status', 'completed')->count(),
            'in_progress' => (clone $tasks)->where('status', 'in_progress')->count(),
            'pending' => (clone $tasks)->where('status', 'pending')->count(),
        ];

        $taskStats['completion_rate'] = $taskStats['total'] > 0 ?
            round(($taskStats['completed'] / $taskStats['total']) * 100, 1) : 0;

        // Get tasks with project info for Gantt chart
        $ganttTasks = $tasks->with(['project:id,name'])
            ->get(['id', 'name', 'status', 'created_at', 'project_id'])
            ->map(function ($task, $index) {
                return [
                    'id' => $task->id,
                    'name' => $task->name ?? 'Unnamed Task',
                    'project' => $task->project->name ?? 'No Project',
                    'status' => $task->status ?? 'pending',
                    'priority' => 'medium',
                    'start_date' => $task->created_at->format('Y-m-d'),
                    'end_date' => Carbon::parse($task->created_at)->addDays(7 + ($index % 14))->format('Y-m-d'),
                    'progress' => $this->calculateTaskProgress($task->status ?? 'pending'),
                ];
            });

        // Time logging statistics
        $timeStats = [
            'total_hours' => TimeLog::where('user_id', $user->id)->sum('hours_worked') ?? 0,
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

        // Calculate daily average
        if ($timeStats['total_hours'] > 0) {
            $firstLog = TimeLog::where('user_id', $user->id)->orderBy('work_date')->first();
            if ($firstLog) {
                $daysSinceFirst = Carbon::parse($firstLog->work_date)->diffInDays(Carbon::now()) + 1;
                $timeStats['daily_average'] = round($timeStats['total_hours'] / $daysSinceFirst, 1);
            }
        }

        // Enhanced weekly time logs for chart (last 8 weeks) with proper dates
        $weeklyHours = collect();
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek(Carbon::SUNDAY);

            $hours = TimeLog::where('user_id', $user->id)
                ->whereBetween('work_date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                ->sum('hours_worked') ?? 0;

            $weeklyHours->push([
                'week' => 'Week '.$weekStart->weekOfYear,
                'hours' => (float) $hours,
                'start_date' => $weekStart->format('Y-m-d'),
                'end_date' => $weekEnd->format('Y-m-d'),
                'date_range' => $weekStart->format('M j').' - '.$weekEnd->format('M j'),
                'is_current_week' => $weekStart->isSameWeek(Carbon::now()),
            ]);
        }

        // Daily hours for the last 30 days
        $dailyHours = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $hours = TimeLog::where('user_id', $user->id)
                ->whereDate('work_date', $date->format('Y-m-d'))
                ->sum('hours_worked') ?? 0;

            $dailyHours->push([
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('M j'),
                'hours' => (float) $hours,
                'is_weekend' => $date->isWeekend(),
                'is_today' => $date->isToday(),
            ]);
        }

        // Project-wise hours breakdown
        $projectHours = TimeLog::where('user_id', $user->id)
            ->with(['project:id,name'])
            ->selectRaw('project_id, SUM(hours_worked) as total_hours')
            ->groupBy('project_id')
            ->get()
            ->map(function ($item) {
                return [
                    'project' => $item->project->name ?? 'No Project',
                    'hours' => (float) $item->total_hours,
                ];
            });

        // Monthly hours for the last 6 months
        $monthlyHours = collect();
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $hours = TimeLog::where('user_id', $user->id)
                ->whereMonth('work_date', $month->month)
                ->whereYear('work_date', $month->year)
                ->sum('hours_worked') ?? 0;

            $monthlyHours->push([
                'month' => $month->format('M Y'),
                'hours' => (float) $hours,
                'target' => 160, // 40 hours * 4 weeks
            ]);
        }

        // Recent time logs
        $recentTimeLogs = TimeLog::where('user_id', $user->id)
            ->with(['project:id,name'])
            ->orderBy('work_date', 'desc')
            ->limit(10)
            ->get(['project_id', 'work_date', 'hours_worked', 'description'])
            ->map(function ($log) {
                return [
                    'date' => Carbon::parse($log->work_date)->format('M d, Y'),
                    'project' => $log->project->name ?? 'No Project',
                    'hours' => $log->hours_worked,
                    'description' => $log->description ?? 'No description',
                ];
            });

        // --- [START] CORRECTED LEAVE STATISTICS ---

        $approvedLeave = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->get();

        // Best practice: Get allowance from user model, otherwise default to 20
        // You might need to add a 'leave_allowance' column to your 'users' table
        $totalAllowance = $user->leave_allowance ?? 20;

        $currentYearLeave = $approvedLeave->filter(
            fn ($leave) => Carbon::parse($leave->start_date)->year === Carbon::now()->year
        )->sum(
            fn ($leave) => Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1
        );

        $leaveStats = [
            'current_year' => $currentYearLeave,
            'balance'      => $totalAllowance, // <-- THE FIX #1: Add the balance key
            'remaining'    => max(0, $totalAllowance - $currentYearLeave), // <-- THE FIX #2: Use the variable
            'total_days'   => $approvedLeave->sum(fn ($leave) => Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1),
        ];

        // --- [END] CORRECTED LEAVE STATISTICS ---


        // Recent leave applications
        $recentLeave = LeaveApplication::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['start_date', 'end_date', 'reason', 'status', 'created_at'])
            ->map(function ($leave) {
                return [
                    'start_date' => Carbon::parse($leave->start_date)->format('M d, Y'),
                    'end_date' => Carbon::parse($leave->end_date)->format('M d, Y'),
                    'reason' => $leave->reason ?? 'No reason provided',
                    'status' => $leave->status,
                    'days' => Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1,
                ];
            });

        return [
            'employee' => $user->only('id', 'name', 'email'),
            'taskStats' => $taskStats,
            'ganttTasks' => $ganttTasks,
            'timeStats' => $timeStats,
            'weeklyHours' => $weeklyHours->toArray(),
            'dailyHours' => $dailyHours->toArray(),
            'projectHours' => $projectHours->toArray(),
            'monthlyHours' => $monthlyHours->toArray(),
            'recentTimeLogs' => $recentTimeLogs->toArray(),
            'leaveStats' => $leaveStats, // This will now contain the 'balance'
            'recentLeave' => $recentLeave->toArray(),
        ];
    }

    private function calculateTaskProgress($status): int
    {
        switch (strtolower($status)) {
            case 'completed':
            case 'done':
                return 100;
            case 'in_progress':
            case 'in-progress':
                return 50;
            case 'pending':
            case 'todo':
            default:
                return 0;
        }
    }
}
