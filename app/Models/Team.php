<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $guarded = []; // Allows mass assignment for all fields

    // A Team is led by one User (the Team Lead)
    public function teamLead()
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }


    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user');
    }

    // A Team can have many Projects
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
