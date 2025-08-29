<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class MailTemplate extends Model
{
    // Specify you want to use the MongoDB connection (configure name in config/database.php)
    protected $connection = 'mongodb';

    // Specify the collection name in MongoDB
    protected $collection = 'mail_templates';

    // If you want, specify fillable or guarded fields
    protected $fillable = [
        'event_type',
        'name',
        'subject',
        'body',
    ];

    // Disable timestamps if you don’t store created_at/updated_at
    public $timestamps = false;
}
