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
        if (! Auth::user()->can('view', $project)) {
            throw new AuthorizationException('Not authorized to view this project.');
        }

        $project->load('team.members', 'team.teamLead');

        $tasks = $project->tasks()->with('assignedTo:id,name')->get();

        $teamMembers = collect();
        if (Auth::user()->can('assign tasks') && $project->team) {
            $teamMembers = $project->team->members
                ->push($project->team->teamLead)
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
