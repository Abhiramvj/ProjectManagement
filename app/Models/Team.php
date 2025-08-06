<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'team_lead_id',
    ];

    // A Team is led by one User (the Team Lead)
    public function teamLead()
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    // A Team has many members
    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user', 'team_id', 'user_id');
    }

    // A Team can have many Projects
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
