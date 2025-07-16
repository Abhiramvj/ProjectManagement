<?php

namespace App\Actions\Profile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeleteAccount
{
    public function handle(Request $request): void
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
