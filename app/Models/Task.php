<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $guarded = [];

    // A Task belongs to one Project
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // A Task is assigned to one User
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }
}
