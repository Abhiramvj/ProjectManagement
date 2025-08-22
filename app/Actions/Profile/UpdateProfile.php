<?php

namespace App\Actions\Profile;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Support\Facades\Storage;

class UpdateProfile
{
    public function handle(ProfileUpdateRequest $request): void
    {
        // Get the validated data from the request.
        $validatedData = $request->validated();

        // Get the currently authenticated user.
        $user = $request->user();

        // Check if a new image has been uploaded.
        if ($request->hasFile('image')) {
            // 1. Delete the old image if it exists to save space.
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }

            // 2. Store the new image in 'storage/app/public/profile-photos'
            // and get the path to save in the database.
            $path = $request->file('image')->store('profile-photos', 'public');

            // 3. Add the new image path to the data that will be saved.
            $validatedData['image'] = $path;
        }

        // Update the user model with the validated data (including name, email, and new image path if present).
        $user->fill($validatedData);

        // If the user is changing their email, we need to reset the verification status.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Save all the changes to the database.
        $user->save();
    }
}
