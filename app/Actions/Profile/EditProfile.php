<?php

namespace App\Actions\Profile;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;

class EditProfile
{
    public function handle(Request $request): array
    {
        return [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ];
    }
}
