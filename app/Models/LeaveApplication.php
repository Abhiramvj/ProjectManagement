<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Defines the relationship: An application belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
