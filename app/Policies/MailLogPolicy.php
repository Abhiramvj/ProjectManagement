<?php

namespace App\Policies;

use App\Models\MailLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MailLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * This corresponds to the 'index' page.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Example: Only allow users with the 'Admin' role.
        // Replace 'isAdmin' with your actual role-checking logic.
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can view the model.
     *
     * This corresponds to the 'show' (detail) page.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, MailLog $mailLog)
    {
        return $user->role === 'Admin';
    }
}
