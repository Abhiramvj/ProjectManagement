<?php

namespace App\Http\Controllers;

use App\Actions\Task\StoreTask;
use App\Actions\Task\UpdateTaskStatus;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateStatusTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request, Project $project, StoreTask $storeTask): RedirectResponse
    {
        $validated = $request->validated();

        $storeTask->handle($project, $validated);

        return redirect()->back()->with('success', 'Task created successfully.');
    }

    public function updateStatus(UpdateStatusTaskRequest $request, Task $task, UpdateTaskStatus $updateTaskStatus): RedirectResponse
    {
        $validated = $request->validated();
        $updateTaskStatus->handle($task, $validated['status']);

        return redirect()->back()->with('success', 'Task status updated.');
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $validated = $request->validated();

        $task->update($validated);

        return redirect()->back()->with('success', 'Task updated successfully.');
    }
}
