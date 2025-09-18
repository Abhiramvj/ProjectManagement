<?php

namespace App\Actions\Review;

use Illuminate\Support\Collection;

class BuildReviewSummariesAction
{
    public function execute(Collection $teamMembers, Collection $reviews): Collection
    {
        return $teamMembers->map(function ($member) use ($reviews) {
            $memberReviews = $reviews->where('user_id', $member->id);
            $latestReview = $memberReviews->first();

            $avgScore = 0;
            $totalWeight = 0;

            if ($latestReview && $latestReview->scores->count()) {
                $scoresByCategory = $latestReview->scores->groupBy(fn ($score) => optional($score->criterion->category)->id
                );

                foreach ($scoresByCategory as $scores) {
                    $categoryWeight = optional($scores->first()->criterion->category)->weight ?? 0;
                    $categoryAvg = $scores->avg('score');
                    if ($categoryWeight > 0 && $categoryAvg > 0) {
                        $avgScore += ($categoryAvg * $categoryWeight);
                        $totalWeight += $categoryWeight;
                    }
                }
            }

            $avgScore = $totalWeight ? ($avgScore / $totalWeight) : 0;
            $progress = min(100, round($avgScore * 10));

            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->designation ?? 'Employee',
                'performance' => round($avgScore, 1),
                'progress' => $progress,
                'lastReview' => $latestReview ? $latestReview->review_month.'/'.$latestReview->review_year : 'N/A',
                'status' => $latestReview ? 'Completed' : 'Pending',
                'latestReviewMonth' => $latestReview?->review_month,
                'latestReviewYear' => $latestReview?->review_year,
            ];
        });
    }
}
