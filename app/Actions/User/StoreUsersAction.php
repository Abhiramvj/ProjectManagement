<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class StoreUsersAction
{
    public function execute(array $data): User
    {
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'work_mode' => $data['work_mode'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'designation' => $data['designation'] ?? null,
            'image' => null,
        ];

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $userData['image'] = $data['image']->store('profile_images', 'public');
        }

        // Create the user first
        $user = User::create($userData);

        // Assign the role
        $user->assignRole($data['role']);

        // --- NEW LOGIC TO HANDLE THE PIVOT TABLE ---
        // If a team_id is provided, attach the user to that team.
        if (! empty($data['team_id'])) {
            $user->teams()->attach($data['team_id']);
        }

        return $user;
    }
}
