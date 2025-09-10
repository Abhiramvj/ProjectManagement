<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// --- ADD THIS IMPORT ---

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'project_manager_id',
        'team_id',
        'status',
        'end_date',
        'priority',
        'total_hours_required',

    ];

    // Automatically include these calculated values when sending the model to the frontend
    protected $appends = ['task_progress', 'hours_progress'];

    // ==================
    // Relationships
    // ==================

    public function projectManager()
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function teamLead()
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * --- ADD THIS RELATIONSHIP METHOD ---
     *
     * The users that belong to the project as members.
     * This defines the many-to-many relationship required by the `whereHas('members', ...)` query.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id');
    }

    // ==================
    // Accessors
    // ==================

    // Accessor for Task-based progress
    protected function taskProgress(): Attribute
    {
        return new Attribute(get: function () {
            $totalTasks = $this->tasks()->count();
            if ($totalTasks === 0) {
                return 0;
            }
            // Align with allowed task statuses used across the app
            $completedTasks = $this->tasks()->where('status', 'completed')->count();

            return round(($completedTasks / $totalTasks) * 100);
        });
    }

    // Accessor for Hours-based progress
    protected function hoursProgress(): Attribute
    {
        return new Attribute(get: function () {
            if ($this->total_hours_required == 0) {
                return 0;
            }

            // Use eager loaded sum if available, else fallback to query
            $loggedHours = $this->time_logs_sum_hours_worked ?? $this->timeLogs()->sum('hours_worked');

            return round(($loggedHours / $this->total_hours_required) * 100);
        });
    }
}
