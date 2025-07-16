<?php

namespace App\Actions\Performance;

use App\Models\LeaveApplication;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;

class ShowPerformance
{
    public function handle(User $user): array
    {
        $tasks = Task::where('assigned_to_id', $user->id);
        $taskStats = [
            'total' => $tasks->count(),
            'completed' => (clone $tasks)->where('status', 'done')->count(),
        ];

        $totalHours = TimeLog::where('user_id', $user->id)->sum('hours_worked');

        $approvedLeave = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')->get();

        $leaveDays = $approvedLeave->sum(fn ($leave) => Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1
        );

        return [
            'employee' => $user->only('id', 'name', 'email'),
            'taskStats' => $taskStats,
            'totalHours' => $totalHours,
            'leaveDays' => $leaveDays,
        ];
    }
}
