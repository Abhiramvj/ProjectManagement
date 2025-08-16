<?php

// app/Actions/Task/UpdateTaskStatus.php

namespace App\Actions\Task;

use App\Models\Task;

class UpdateTaskStatus
{
    // Unified task statuses across backend and frontend
    private const VALID_STATUSES = ['todo', 'in_progress', 'completed'];

    public function handle(Task $task, string $status)
    {
        if (! in_array($status, self::VALID_STATUSES)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $task->update([
            'status' => $status,
        ]);

        return $task->fresh();
    }

    public static function getValidStatuses(): array
    {
        return self::VALID_STATUSES;
    }
}
