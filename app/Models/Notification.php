<?php

namespace App\Models;

use App\Traits\Scopes\QueryByUserRole;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    use QueryByUserRole;

    public function user()
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }
}
