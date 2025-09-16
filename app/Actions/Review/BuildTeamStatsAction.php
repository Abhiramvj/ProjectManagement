<?php

namespace App\Actions\Review;

use Illuminate\Support\Collection;

class BuildTeamStatsAction
{
    public function execute(Collection $reviewSummaries): array
    {
        return [
            'teamSize' => $reviewSummaries->count(),
            'avgPerformance' => $reviewSummaries->avg('performance'),
            'reviewsPending' => $reviewSummaries->where('status', 'Pending')->count(),
            'topPerformers' => $reviewSummaries->where('performance', '>', 8.5)->count(),
        ];
    }
}
