<?php

namespace App\Actions\Project;

use App\Models\Team;
use App\Models\User;

class CreateProject
{
    /**
     * Gathers all the necessary data required for the "Create Project" form/modal.
     *
     * @return array
     */
    public function handle(): array
    {
        // 1. Fetch all teams, including their team leads for display.
        $teams = Team::with('teamLead:id,name')->get(['id', 'name', 'team_lead_id']);

        // 2. Fetch all users who have the 'project-manager' role.
        // This is the logic that was missing.
        $projectManagers = User::role('project-manager')->orderBy('name')->get(['id', 'name']);

        // 3. Return both sets of data in a single array with the correct keys.
        return [
            'teams' => $teams,
            'projectManagers' => $projectManagers,
        ];
    }
}