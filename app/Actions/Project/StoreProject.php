<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Support\Facades\Auth; // <-- Make sure Auth is imported

class StoreProject
{
    public function handle(array $data): Project
    {
        // --- STEP 1: Create the Project ---
        // We now ignore `project_manager_id` from the form and use Auth::id()
        $project = Project::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'team_id' => $data['team_id'],
            'project_manager_id' => Auth::id(), // <-- THE KEY CHANGE IS HERE
            'status' => 'pending',
            'priority' => $data['priority'] ?? 'medium',
            'end_date' => $data['end_date'] ?? null,
            'total_hours_required' => $data['total_hours_required'] ?? 0,
        ]);

        // --- STEP 2: Find the Team and Sync Members ---
        $team = Team::find($data['team_id']);

        if ($team) {
            $memberIds = $team->members()->pluck('id')->toArray();

            // Ensure the Project Manager is included as a member
            if (! in_array($project->project_manager_id, $memberIds)) {
                $memberIds[] = $project->project_manager_id;
            }

            $project->members()->sync($memberIds);
        }

        return $project;
    }
}
