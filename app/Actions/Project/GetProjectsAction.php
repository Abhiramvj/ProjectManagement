<?php

namespace App\Actions\Project;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class GetProjectsAction
{
    public function execute()
    {
        $user = Auth::user();

        // Base project query with eager loading and aggregations
        $query = Project::query()
            ->with(['projectManager:id,name', 'team:id,name'])
            ->withCount('tasks')
            ->withSum('timeLogs', 'hours_worked')
            ->latest();

        // Role-based scoping
        if ($user->hasRole('project-manager')) {
            $query->where('project_manager_id', $user->id);
        } elseif (! ($user->hasRole('admin') || $user->hasRole('hr'))) {
            $teamIds = $user->teams->pluck('id')->toArray();

            if (count($teamIds) > 0) {
                $query->whereIn('team_id', $teamIds);
            } else {
                return collect(); // Return empty collection
            }
        }

        // Fetch projects
        $projects = $query->get();

        // Map with hours_progress calculation
        return $projects->map(function ($project) {
            $hoursLogged = $project->time_logs_sum_hours_worked ?? 0;
            $totalHoursRequired = $project->total_hours_required ?? 0;

            $hoursProgress = 0;
            if ($totalHoursRequired > 0) {
                $hoursProgress = (int) round(min(100, ($hoursLogged / $totalHoursRequired) * 100));
            }

            return [
                'id' => $project->id,
                'name' => $project->name,
                'team_id' => $project->team_id,
                'team' => $project->team,
                'project_manager' => $project->projectManager,
                'tasks_count' => $project->tasks_count,
                'status' => $project->status,
                'priority' => $project->priority,
                'total_hours_required' => $totalHoursRequired,
                'hours_progress' => $hoursProgress,
                'end_date' => $project->end_date,
                'created_at' => $project->created_at,
            ];
        });
    }
}
