<?php

namespace App\Models;

use App\Traits\Scopes\QueryByUserRole;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    use QueryByUserRole;


    protected $table = 'notifications';

      protected $fillable = [
        'id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }
}
