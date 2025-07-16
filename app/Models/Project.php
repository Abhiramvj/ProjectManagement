<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Automatically include these calculated values when sending the model to the frontend
    protected $appends = ['task_progress', 'hours_progress'];

    // Relationships
    public function projectManager()
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }

    // Accessor for Task-based progress
    protected function taskProgress(): Attribute
    {
        return new Attribute(get: function () {
            $totalTasks = $this->tasks()->count();
            if ($totalTasks === 0) {
                return 0;
            }
            $completedTasks = $this->tasks()->where('status', 'done')->count();

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
            $loggedHours = $this->timeLogs()->sum('hours_worked');

            return round(($loggedHours / $this->total_hours_required) * 100);
        });
    }
}
