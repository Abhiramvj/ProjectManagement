<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        // Allow if the user is the Project Manager of this project
        if ($user->id === $project->project_manager_id) {
            return true;
        }

        // Allow if the user is the Team Lead of the team assigned to this project
        if ($user->id === $project->team->team_lead_id) {
            return true;
        }

        return false;
    }
}
