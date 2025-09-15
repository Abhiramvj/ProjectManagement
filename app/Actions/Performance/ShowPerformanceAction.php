<?php

namespace App\Actions\Performance;

use App\Models\User;

class ShowPerformanceAction
{
    public function handle(User $user): array
    {
        $taskData = (new GetTaskPerformanceAction)->handle($user);
        $timeData = app(GetTimeStatsAction::class)->handle($user);
        $leaveData = (new GetLeaveStatsAction)->handle($user);

        return array_merge(
            ['employee' => $user->only('id', 'name', 'email')],
            $taskData,
            $timeData,
            $leaveData
        );
    }
}
