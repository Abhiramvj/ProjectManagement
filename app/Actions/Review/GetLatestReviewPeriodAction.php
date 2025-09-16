<?php

namespace App\Actions\Review;

use App\Models\Review;

class GetLatestReviewPeriodAction
{
    public function execute(int $employeeId): ?array
    {
        $latestReview = Review::where('user_id', $employeeId)
            ->orderByDesc('review_year')
            ->orderByDesc('review_month')
            ->first();

        if (! $latestReview) {
            return null;
        }

        return [
            'month' => $latestReview->review_month,
            'year' => $latestReview->review_year,
        ];
    }
}
