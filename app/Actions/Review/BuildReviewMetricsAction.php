<?php

namespace App\Actions\Review;

use App\Models\Review;
use Illuminate\Support\Collection;

class BuildReviewMetricsAction
{
    public function execute(?Review $review, Collection $categories): array
    {
        $metrics = [];
        $overallScore = null;
        $lastReviewDate = null;

        if ($review) {
            foreach ($categories as $cat) {
                $catScores = $review->scores->whereIn('criteria_id', $cat->criteria->pluck('id'));
                if ($catScores->count()) {
                    $metrics[] = [
                        'name' => $cat->name,
                        'value' => round($catScores->avg('score'), 1),
                    ];
                }
            }

            $overallScore = round($review->scores->avg('score'), 1);
            $lastReviewDate = $review->created_at ? $review->created_at->format('n/j/Y') : null;
        }

        return [
            'metrics' => $metrics,
            'overallScore' => $overallScore,
            'lastReviewDate' => $lastReviewDate,
        ];
    }
}
