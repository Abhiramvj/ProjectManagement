<?php
namespace App\Actions\Review;

use App\Models\Review;
use App\Models\ReviewScore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreEmployeeReviewAction
{
    
    public function execute(int $employeeId, int $month, int $year, array $scores, ?array $comments = []): Review
    {
        return DB::transaction(function () use ($employeeId, $month, $year, $scores, $comments) {
            $review = Review::updateOrCreate(
                [
                    'user_id' => $employeeId,
                    'reviewer_id' => Auth::id(),
                    'review_month' => $month,
                    'review_year' => $year,
                ],
                []
            );

            foreach ($scores as $criteriaId => $score) {
                ReviewScore::updateOrCreate(
                    [
                        'review_id' => $review->id,
                        'criteria_id' => $criteriaId,
                    ],
                    [
                        'score' => $score,
                        'comment' => $comments[$criteriaId] ?? null,
                    ]
                );
            }

            return $review;
        });
    }
}
