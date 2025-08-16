<?php

namespace App\Services;

use App\Models\Task;

class TaskStatsService
{
    /**
     * Get task statistics for a specific user.

     */
    public function getStatsForUser(int $userId): array
    {
        $tasks = Task::where('assigned_to_id', $userId)->get();

        $total = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();
        $in_progress = $tasks->where('status', 'in_progress')->count();
        $pending = $tasks->whereNotIn('status', ['completed', 'in_progress'])->count();

        $completion_rate = ($total > 0) ? round(($completed / $total) * 100) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $in_progress,
            'pending' => $pending,
            'completion_rate' => $completion_rate,
        ];
    }
}
