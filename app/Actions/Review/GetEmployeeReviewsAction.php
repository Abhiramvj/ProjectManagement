<?php

namespace App\Actions\Review;

use App\Models\Review;

class GetEmployeeReviewsAction
{
    public function execute(int $employeeId, int $month, int $year): array
    {
        $selfReview = Review::where('user_id', $employeeId)
            ->where('reviewer_id', $employeeId)
            ->where('review_month', $month)
            ->where('review_year', $year)
            ->with('scores')
            ->first();

        $managerReview = Review::where('user_id', $employeeId)
            ->where('reviewer_id', '!=', $employeeId)
            ->where('review_month', $month)
            ->where('review_year', $year)
            ->with('scores')
            ->first();

        return compact('selfReview', 'managerReview');
    }
}
