<?php

namespace App\Actions\Notification;

use App\Models\Notification;
use App\Models\User;

class GetUnreadCountAction
{
    /**
     * Execute the action to get unread notifications count based on user role.
     */
    public function execute(User $user): int
    {
        $query = Notification::query()->whereNull('read_at');

        // Use the trait method for scoping
        $query->queryByUserRole($user);

        return $query->count();
    }
}
