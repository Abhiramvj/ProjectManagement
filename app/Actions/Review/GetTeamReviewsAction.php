<?php

namespace App\Actions\Review;

use App\Models\Review;
use Illuminate\Support\Collection;

class GetTeamReviewsAction
{
    public function execute(Collection $teamMemberIds): Collection
    {
        return Review::whereIn('user_id', $teamMemberIds)
            ->with(['scores.criterion.category', 'reviewer'])
            ->orderBy('review_year', 'desc')
            ->orderBy('review_month', 'desc')
            ->get();
    }
}
