<?php

namespace App\Actions\Project;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class StoreProject
{
    public function handle(array $data)
    {
        return Project::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'team_id' => $data['team_id'],
            'project_manager_id' => Auth::id(),
            'status' => 'pending',
            'end_date' => $data['end_date'],
            'total_hours_required' => $data['total_hours_required'],
        ]);
    }
}
