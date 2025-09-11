<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceCriterion extends Model
{
    /** @use HasFactory<\Database\Factories\PerformanceCriterionFactory> */
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'description', 'max_score'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PerformanceCategory::class, 'category_id');
    }
}
