<?php

namespace App\Actions\Performance;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;

class GetTaskPerformanceAction
{
    public function handle(User $user): array
    {
        $tasks = Task::where('assigned_to_id', $user->id);

        $taskStats = [
            'total' => $tasks->count(),
            'completed' => (clone $tasks)->where('status', 'completed')->count(),
            'in_progress' => (clone $tasks)->where('status', 'in_progress')->count(),
            'pending' => (clone $tasks)->where('status', 'pending')->count(),
        ];

        $taskStats['completion_rate'] = $taskStats['total'] > 0
            ? round(($taskStats['completed'] / $taskStats['total']) * 100, 1)
            : 0;

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

        return [
            'taskStats' => $taskStats,
            'ganttTasks' => $ganttTasks->toArray(),
        ];
    }

    private function calculateTaskProgress(string $status): int
    {
        return match (strtolower($status)) {
            'completed', 'done' => 100,
            'in_progress', 'in-progress' => 50,
            default => 0,
        };
    }
}
