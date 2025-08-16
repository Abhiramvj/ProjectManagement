<?php

namespace App\Http\Controllers;

use App\Actions\Task\StoreTask;
use App\Actions\Task\UpdateTaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(Request $request, Project $project, StoreTask $storeTask): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'assigned_to_id' => 'required|exists:users,id',
        ]);

        $storeTask->handle($project, $validated);

        return redirect()->back()->with('success', 'Task created successfully.');
    }

    /**
     * --- THIS IS THE NEW, CORRECT METHOD ---
     *
     * This method is called directly by the route `tasks.updateStatus`.
     * It's specifically for handling the "Start" and "Done" buttons.
     */
    public function updateStatus(Request $request, Task $task, UpdateTaskStatus $updateTaskStatus): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:todo,in_progress,completed',
        ]);

        $updateTaskStatus->handle($task, $validated['status']);

        return redirect()->back()->with('success', 'Task status updated.');
    }

    /**
     * This method is now only for handling full task updates (e.g., from an "Edit Task" form).
     * The status update logic has been moved to its own dedicated method.
     */
    public function update(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'assigned_to_id' => 'sometimes|required|exists:users,id',
            'status' => 'sometimes|required|in:todo,in_progress,completed',
            'due_date' => 'sometimes|required|date',
        ]);

        $task->update($validated);

        return redirect()->back()->with('success', 'Task updated successfully.');
    }
}
