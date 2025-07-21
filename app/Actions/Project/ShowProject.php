<?php

namespace App\Actions\Project;

use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class ShowProject
{
    /**
     * @throws AuthorizationException
     */
    public function handle(Project $project): array
    {
        Auth::user()->can('view', $project) || abort(403);

        $tasks = $project->tasks()->with('assignedTo:id,name')->get();

        $teamMembers = Auth::user()->can('assign tasks')
            ? $project->team->members
            : collect();

        return [
            'project' => $project,
            'tasks' => $tasks,
            'teamMembers' => $teamMembers,
        ];
    }
}
