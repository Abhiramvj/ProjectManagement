<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewCriteria extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'max_points',
    ];

    /**
     * Each criterion belongs to one category.
     */
    public function category()
    {
        return $this->belongsTo(ReviewCategory::class, 'category_id');
    }

    /**
     * Each criterion can have many reviews (once you add reviews table).
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'criteria_id');
    }
}
