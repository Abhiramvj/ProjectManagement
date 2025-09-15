<?php

namespace App\Actions\Profile;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Support\Facades\Storage;

class UpdateProfileAction
{
    public function handle(ProfileUpdateRequest $request): void
    {
        $validatedData = $request->validated();

        $user = $request->user();

        if ($request->hasFile('image')) {
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }

            $path = $request->file('image')->store('profile-photos', 'public');

            $validatedData['image'] = $path;
        }

        $user->fill($validatedData);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();
    }
}
