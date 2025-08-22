<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * This is the recommended "whitelist" approach for security.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'work_date',
        'hours_worked',
        'description',
    ];

    /**
     * Get the user that owns the time log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project associated with the time log.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the total hours worked
     */
    public function getTotalHoursAttribute()
    {
        return $this->hours_worked ?? 0;
    }

    /**
     * Get the week number
     */
    public function getWeekAttribute()
    {
        return $this->work_date ? $this->work_date->weekOfYear : null;
    }

    /**
     * Get the year
     */
    public function getYearAttribute()
    {
        return $this->work_date ? $this->work_date->year : null;
    }
}
