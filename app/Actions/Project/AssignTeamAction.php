<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Support\Facades\Redirect;

class AssignTeamAction
{
    public function execute(Project $project, int $teamId)
    {
        // 1. Assign team
        $project->update(['team_id' => $teamId]);

        // 2. Sync members
        $team = Team::with('members')->find($teamId);

        if ($team) {
            $memberIds = $team->members()->pluck('users.id')->toArray();

            if (! in_array($project->project_manager_id, $memberIds)) {
                $memberIds[] = $project->project_manager_id;
            }

            $project->members()->sync($memberIds);
        }

        return Redirect::route('projects.show', $project)
            ->with('success', 'Project assigned to team successfully.');
    }
}
