<?php 
namespace App\Actions\Review;

use App\Models\Review;

class FormatReviewAction
{
    public function execute(?Review $review): array
    {
        if (! $review) {
            return ['scores' => [], 'comments' => []];
        }

        $scores = [];
        $comments = [];
        foreach ($review->scores as $score) {
            $scores[$score->criteria_id] = $score->score;
            $comments[$score->criteria_id] = $score->comment ?? '';
        }

        return compact('scores', 'comments');
    }
}
