<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewScore extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewScoreFactory> */
    use HasFactory;

     protected $fillable = ['review_id', 'criteria_id', 'score'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'review_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(PerformanceCriterion::class, 'criteria_id');
    }
}
