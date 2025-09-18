<?php

namespace App\Actions\Review;

use App\Models\PerformanceCategory;
use Illuminate\Database\Eloquent\Collection;

class GetCategoriesWithCriteriaAction
{
    public function execute(): Collection
    {
        return PerformanceCategory::with('criteria')->get();
    }
}
