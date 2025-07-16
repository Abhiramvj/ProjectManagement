<?php

namespace App\Actions\Profile;

use App\Http\Requests\ProfileUpdateRequest;

class UpdateProfile
{
    public function handle(ProfileUpdateRequest $request): void
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
