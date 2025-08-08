<?php

namespace App\Models;

// Use the class from the package you have installed
use MongoDB\Laravel\Eloquent\Model;

class MailLog extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mongodb';

    /**
     * The collection associated with the model.
     *
     * @var string
     */
    protected $collection = 'mail_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'leave_application_id',
        'recipient_email',
        'subject',
        'status',
        'event_type',
        'error_message',
        'sent_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
