<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';

    public $incrementing = false;  
    protected $keyType = 'string';   

      protected $fillable = [
        'id', 'notifiable_id', 'notifiable_type', 'type', 'data', 'read_at',
    ];
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user that the notification belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }
}
