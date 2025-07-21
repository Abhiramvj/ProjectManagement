<?php

namespace App\Actions\User;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StoreUsers
{
    public function handle(array $data) {

        $hrApprover = User::role('hr')->first();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'parent_id' => $data['parent_id'] ?? null,
             'leave_approver_id' => $hrApprover ? $hrApprover->id : null,
        ]);

        $user->assignRole($data['role']);

        if ($data['role'] === 'employee' && isset($data['team_id'])) {
            $team = Team::find($data['team_id']);
            if ($team) {
                $team->members()->attach($user->id);
            }
        }
    }
}
