<?php

namespace App\Actions\Dashboard;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

class GetProjectsAndTasksAction
{
    public function execute(User $user)
    {
        $projects = collect();
        $teamIdsLedByUser = Team::where('team_lead_id', $user->id)->pluck('id');

        if ($user->hasRole('admin')) {
            $projects = Project::where('status', '!=', 'completed')->latest()->get();
        } elseif ($user->hasRole('project-manager')) {
            $projects = Project::where('status', '!=', 'completed')
                ->where(function ($query) use ($user) {
                    $query->where('project_manager_id', $user->id)
                        ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id));
                })
                ->latest()
                ->get();
        } elseif ($user->hasRole('team-lead')) {
            $projects = Project::where('status', '!=', 'completed')
                ->where(function ($query) use ($user, $teamIdsLedByUser) {
                    $query->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
                        ->orWhereIn('team_id', $teamIdsLedByUser);
                })
                ->latest()
                ->get();
        }

        $myTasks = Task::where('assigned_to_id', $user->id)
            ->with('project:id,name')
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->get();

        return [
            'projects' => $projects,
            'myTasks' => $myTasks,
        ];
    }
}
