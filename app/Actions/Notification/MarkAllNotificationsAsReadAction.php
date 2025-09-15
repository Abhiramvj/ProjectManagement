<?php

namespace App\Actions\Notification;

use App\Models\User;

class MarkAllNotificationsAsReadAction
{
    /**
     * Mark all unread notifications for a user as read.
     */
    public function execute(User $user): void
    {
        foreach ($user->unreadNotifications as $notification) {
            $notification->markAsRead();
        }
    }
}
