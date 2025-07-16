<?php

namespace App\Actions\TimeLog;

use App\Models\Project;
use App\Models\TimeLog;
use Illuminate\Support\Facades\Auth;

class GetTimeLogs
{
    public function handle(): array
    {
        $user = Auth::user();
        $timeLogs = collect();
        $assignableProjects = collect();

        if ($user->can('view all working hours')) {
            $timeLogs = TimeLog::with(['user:id,name', 'project:id,name'])
                ->latest('work_date')
                ->latest('id')
                ->paginate(15);
        } else {
            $timeLogs = TimeLog::where('user_id', $user->id)
                ->with('project:id,name')
                ->latest('work_date')
                ->latest('id')
                ->paginate(15);

            $teamIds = $user->teams()->pluck('teams.id');

            $assignableProjects = Project::query()
                ->when($teamIds->isNotEmpty(), function ($query) use ($teamIds) {
                    $query->orWhereIn('team_id', $teamIds);
                })
                ->orWhere('project_manager_id', $user->id)
                ->where('status', '!=', 'completed')
                ->get(['id', 'name']);
        }

        return [
            'timeLogs' => $timeLogs,
            'assignableProjects' => $assignableProjects,
            'canViewAll' => $user->can('view all working hours'),
        ];
    }
}
