<?php

namespace App\Actions\Review;

use App\Models\PerformanceCategory;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class GetMyReviewsAction
{
    public function execute(): array
    {
        $user = Auth::user();

        $reviews = Review::where('user_id', $user->id)
            ->where('reviewer_id', $user->id)
            ->with('scores.criterion.category')
            ->orderBy('review_year', 'desc')
            ->orderBy('review_month', 'desc')
            ->paginate(10);

        $categories = PerformanceCategory::with('criteria')->get();

        return [
            'reviews' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'from' => $reviews->firstItem(),
                'to' => $reviews->lastItem(),
            ],
            'links' => $reviews->linkCollection()->toArray(),
            'categories' => $categories,
        ];
    }
}
