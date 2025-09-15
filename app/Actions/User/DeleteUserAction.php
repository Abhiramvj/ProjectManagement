<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DeleteUserAction
{
    public function execute(User $user): void
    {
        // Optional: Add safety checks
        if ($user->id === Auth::id()) {
            throw ValidationException::withMessages([
                'delete' => 'You cannot delete your own account.',
            ]);
        }

        // Add other checks if needed (e.g., cannot delete a team lead)

        $user->delete();
    }
}
