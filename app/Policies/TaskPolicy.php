<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can update the model.
     * A user can update a task if they are the one it's assigned to.
     * We can add more logic here later (e.g., allow the Team Lead too).
     */
    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->assigned_to_id;
    }
}
