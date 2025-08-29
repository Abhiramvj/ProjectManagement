<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    // You don’t need to redefine fillables/casts since
    // DatabaseNotification already has them.
    // But if you want custom relationships or scopes, add here.

    public function user()
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }
}
