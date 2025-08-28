<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth; // Import this exception

class StoreProject
{
    /**
     * @throws AuthenticationException
     */
    public function handle(array $data): Project
    {
        $user = Auth::user();

        // ===================================================================
        // THE DEFINITIVE FIX IS HERE
        // This is the final gatekeeper. If an unauthenticated user somehow
        // reaches this action, we stop them immediately before any other
        // code runs. This will solve the "Value of type null is not callable" error.
        // ===================================================================
        if (! $user) {
            throw new AuthenticationException('You must be logged in to create a project.');
        }

        $projectData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
            'priority' => $data['priority'],
            'end_date' => $data['end_date'] ?? null,
            'total_hours_required' => $data['total_hours_required'] ?? 0,
        ];

        if ($user->hasRole('admin')) {
            if (! empty($data['team_id'])) {
                $team = Team::findOrFail($data['team_id']);
                $projectData['team_id'] = $team->id;
                $projectData['project_manager_id'] = $team->team_lead_id;
            } else {
                $projectData['project_manager_id'] = $data['project_manager_id'];
                $projectData['team_id'] = null;
            }
        } else {
            $projectData['team_id'] = $data['team_id'];
            $projectData['project_manager_id'] = $user->id;
        }
\Log::info('Saving project with priority:', ['priority' => $data['priority']]);

        $project = Project::create($projectData);

        if ($project->team_id) {
            $team = Team::with('members')->find($project->team_id);
            if ($team) {
                $memberIds = $team->members()->pluck('users.id')->toArray();
                if (! in_array($project->project_manager_id, $memberIds)) {
                    $memberIds[] = $project->project_manager_id;
                }
                $project->members()->sync($memberIds);
            }
        }
        return $project;
    }
}
