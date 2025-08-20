<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUser
{
    public function handle(User $user, array $data): User
    {
        // Handle image upload if provided
        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            //  Consider deleting the old image
            $imagePath = $data['image']->store('profile_images', 'public');
            $data['image'] = $imagePath; // Update the data array with the new image path
        }

        // Update the user with the provided data
        // The array_filter is kept for consistency with your existing code
        $data = array_filter($data, function ($value) {
            return $value !== null;
        });

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],

            // ADDED: Include work_mode in the update call.
            // If 'work_mode' is not in the form data, it keeps the user's existing value.
            'work_mode' => $data['work_mode'] ?? $user->work_mode,

            'parent_id' => $data['parent_id'] ?? null,
            'designation' => $data['designation'] ?? null,
            'image' => $data['image'] ?? $user->image,
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        if (! empty($data['role'])) {
            // syncRoles is the correct Spatie method for updating roles.
            $user->syncRoles($data['role']);
        }

        if (! empty($data['team_id'])) {
            // sync() will update the pivot table correctly.
            $user->teams()->sync([$data['team_id']]);
        } else {
            // If no team was selected, detach the user from all teams.
            $user->teams()->detach();
        }

        return $user;
    }
}
