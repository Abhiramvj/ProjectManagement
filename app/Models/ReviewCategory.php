<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'weight',
    ];

    /**
     * A category has many criteria.
     */
    public function criteria()
    {
        return $this->hasMany(ReviewCriteria::class, 'category_id');
    }
}
