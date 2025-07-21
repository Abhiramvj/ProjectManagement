<?php

namespace App\Http\Controllers;

use App\Actions\Task\StoreTask;
use App\Actions\Task\UpdateTaskStatus;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Redirect;

class TaskController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreTaskRequest $request, Project $project, StoreTask $storeTask)
    {
        $this->authorize('view', $project);
        $storeTask->handle($project, $request->validated());

        return Redirect::back()->with('success', 'Task created.');
    }

    public function update(UpdateTaskRequest $request, UpdateTaskStatus $updateTaskStatus, Task $task)
    {
        $this->authorize('update', $task);
        $updateTaskStatus->handle($task, $request->validated()['status']);

        return Redirect::back()->with('success', 'Task status updated.');
    }
}
