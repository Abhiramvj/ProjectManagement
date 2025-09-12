<?php

namespace App\Actions\Dashboard;

use App\Services\LeaveStatsService;
use App\Services\TaskStatsService;
use App\Services\TimeStatsService;

class GetPerformanceStatsAction
{
    protected $taskStatsService;

    protected $timeStatsService;

    protected $leaveStatsService;

    public function __construct(TaskStatsService $taskStatsService, TimeStatsService $timeStatsService, LeaveStatsService $leaveStatsService)
    {
        $this->taskStatsService = $taskStatsService;
        $this->timeStatsService = $timeStatsService;
        $this->leaveStatsService = $leaveStatsService;

    }

    public function execute(int $userId)
    {

        return [
            'taskStats' => $this->taskStatsService->getStatsForUser($userId),
            'timeStats' => $this->timeStatsService->getStatsForUser($userId),
            'leaveStats' => $this->leaveStatsService->getStatsForUser($userId),
        ];

    }
}
