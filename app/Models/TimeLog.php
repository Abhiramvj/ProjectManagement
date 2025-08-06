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
     * @var array<int, string>
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
}
