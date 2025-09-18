<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSession extends Model
{
    /** @use HasFactory<\Database\Factories\SessionFactory> */
    use HasFactory;

    protected $fillable = [
        'topic', 'description', 'date', 'requester_id', 'status', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
