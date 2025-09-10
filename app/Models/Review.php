<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'criteria_id',
        'reviewer_id',
        'month',
        'year',
        'score',
        'rating',
    ];

    /**
     * Each review belongs to a user.
     */
 public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}


    /**
     * Each review belongs to a specific criteria.
     */
    public function criteria()
    {
        return $this->belongsTo(ReviewCriteria::class, 'criteria_id');
    }

    public function reviewer()
{
    return $this->belongsTo(User::class, 'reviewer_id');
}

}
