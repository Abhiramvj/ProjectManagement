<?php

namespace App\Actions\User;

use App\Models\Team;
use App\Models\User;
use Spatie\Permission\Models\Role;

class CreateUsersAction
{
    public function execute()
    {
        $theAdmin = User::role('admin')->first();

        // Simplified queries: An empty collection is returned automatically if no users are found.
        $potential_managers = [
            'project_managers' => User::role('project-manager')->get(['id', 'name']),
            'team_leads' => User::role('team-lead')->get(['id', 'name']),
        ];

        // --- THIS IS THE NEW LOGIC ---
        // Fetch all unique, non-null, and non-empty work modes from the database.
        $workModes = User::select('work_mode')
            ->whereNotNull('work_mode')
            ->where('work_mode', '!=', '')
            ->distinct()
            ->pluck('work_mode');
        // --- END OF NEW LOGIC ---

        return [
            'roles' => Role::all()->pluck('name'),
            'teams' => Team::select('id', 'name')->get(),
            'potential_managers' => $potential_managers,
            'theAdmin' => $theAdmin ? ['id' => $theAdmin->id, 'name' => $theAdmin->name] : null,
            'workModes' => $workModes, // <-- Add the dynamic work modes to the returned array
        ];
    }
}
