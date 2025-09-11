<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceCategory extends Model
{
    /** @use HasFactory<\Database\Factories\PerformanceCategoryFactory> */
    use HasFactory;

     protected $fillable = ['name', 'weight'];

    public function criteria(): HasMany
    {
        return $this->hasMany(PerformanceCriterion::class, 'category_id');
    }
}
