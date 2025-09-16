<?php

namespace App\Actions\Review;

use App\Models\PerformanceCategory;
use Illuminate\Support\Facades\Auth;

class CreateSelfReviewsAction
{
    public function execute(int $month, int $year): array
    {
        $user = Auth::user();

        $categories = PerformanceCategory::with('criteria')->get();

        return [
            'employee' => [
                'id' => $user->id,
                'name' => $user->name,
                'designation' => $user->designation ?? 'Employee',
            ],
            'reviewPeriod' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'categories' => $categories,
            'month' => $month,
            'year' => $year,
        ];
    }
}
