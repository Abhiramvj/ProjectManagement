<?php

namespace App\Actions\Project;

use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class ShowProjectAction
{
    /**
     * @throws AuthorizationException
     */
    public function execute(Project $project): array
    {
        if (! Auth::user()->can('view', $project)) {
            throw new AuthorizationException('Not authorized to view this project.');
        }

        $project->load('team.members', 'team.teamLead');

        $tasks = $project->tasks()->with('assignedTo:id,name')->get();

        $teamMembers = collect();
        if (Auth::user()->can('assign tasks') && $project->team) {
            $teamMembers = $project->team->members
                ->concat([$project->team->teamLead])
                ->filter()
                ->unique('id');

        }

        return [
            'project' => $project,
            'tasks' => $tasks,
            'teamMembers' => $teamMembers,
        ];
    }
}
