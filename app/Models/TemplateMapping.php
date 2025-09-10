<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class TemplateMapping extends Model
{

      protected $connection = 'mongodb';

    // Specify the collection name
    protected $collection = 'template_mappings';
    protected $fillable = ['event_type', 'mail_template_id'];
}
