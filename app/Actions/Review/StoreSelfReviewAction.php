<?php

namespace App\Actions\Review;

use App\Models\Review;
use App\Models\ReviewScore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreSelfReviewAction
{
    public function execute(array $scores, int $month, int $year): void
    {
        $user = Auth::user();

        DB::transaction(function () use ($user, $scores, $month, $year) {
            $review = Review::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'reviewer_id' => $user->id,
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
                    ]
                );
            }
        });
    }
}
