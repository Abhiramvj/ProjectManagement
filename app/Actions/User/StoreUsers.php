<?php

namespace App\Actions\User;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StoreUsers
{
    public function handle(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
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
