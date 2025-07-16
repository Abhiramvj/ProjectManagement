<?php

namespace App\Policies;

use App\Models\LeaveApplication;
use App\Models\User;

class LeaveApplicationPolicy
{
    /**
     * Grant all actions to HR or Admin users.
     * This runs before any other authorization methods.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Check for the ROLE directly to avoid an infinite loop.
        if ($user->hasRole(['hr', 'admin'])) {
            return true;
        }

        return null; // Let other methods decide for other roles
    }

    /**
     * Determine whether the user can view the model.
     * The user can view their own application. HR/Admin is handled by the `before` method.
     */
    public function view(User $user, LeaveApplication $leaveApplication): bool
    {
        return $user->id === $leaveApplication->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any user should be able to create one, as long as they have the base permission
        return $user->can('apply for leave');
    }

    /**
     * Determine whether the user can update the model.
     * Only HR/Admin can update, which is handled by the `before` method.
     */
    public function update(User $user, LeaveApplication $leaveApplication): bool
    {
        // We return false here because only the `before` method should grant update permission.
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveApplication $leaveApplication): bool
    {
        return false; // No one can delete for now
    }
}
