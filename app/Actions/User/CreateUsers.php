<?php
namespace App\Actions\User;

use App\Models\Team;
use App\Models\User;
use Spatie\Permission\Models\Role;

class CreateUsers
{
    public function handle()
    {
        $theAdmin = User::role('admin')->first();
        $potential_managers = [
            'project_managers' => User::role('project-manager')->get(['id', 'name'])->isNotEmpty()
                ? User::role('project-manager')->get(['id', 'name'])
                : collect(),
            'team_leads' => User::role('team-lead')->get(['id', 'name'])->isNotEmpty()
                ? User::role('team-lead')->get(['id', 'name'])
                : collect(),
        ];

        return [
            'roles' => Role::all()->pluck('name'),
            'teams' => Team::select('id', 'name')->get(),
            'potential_managers' => $potential_managers,
            'theAdmin' => $theAdmin ? ['id' => $theAdmin->id, 'name' => $theAdmin->name] : null,
        ];
    }
}