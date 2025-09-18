<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    protected $fillable = ['name', 'target', 'description', 'icon'];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps()->withPivot(['progress', 'unlocked_at']);
    }
}

