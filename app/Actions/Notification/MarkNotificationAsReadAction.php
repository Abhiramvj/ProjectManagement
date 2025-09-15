<?php

namespace App\Actions\Notification;

use Illuminate\Notifications\DatabaseNotification;

class MarkNotificationAsReadAction
{
    /**
     * Mark a specific notification as read.
     */
    public function execute(string $id): void
    {
        $notification = DatabaseNotification::findOrFail($id);
        $notification->update(['read_at' => now()]);
    }
}
