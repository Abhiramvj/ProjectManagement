<?php

namespace App\Http\Controllers;

use App\Actions\Project\CreateProject;
use App\Actions\Project\ShowProject;
use App\Actions\Project\StoreProject;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\Project;
use App\Models\User; // <-- Import User model
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth; // <-- Import Auth facade
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of projects and provide data for the creation modal.
     */
    public function index(CreateProject $createProject): Response
    {
        $user = Auth::user();

        // Eager load only required relations and use counts to avoid loading full collections
        $query = Project::query()
            ->with(['projectManager:id,name', 'team:id,name'])
            ->withCount('tasks')
            ->withSum('timeLogs', 'hours_worked')
            ->select(['id', 'name', 'team_id', 'project_manager_id', 'status', 'end_date', 'total_hours_required', 'created_at', 'priority'])
            ->latest();

        // Role-based scoping for viewing projects
        if ($user->hasRole('project-manager')) {
            $query->where('project_manager_id', $user->id);
        } elseif (! ($user->hasRole('admin') || $user->hasRole('hr'))) { // Assuming HR can also see all
            // For team leads and employees, scope to their teams
            $teamIds = $user->teams->pluck('id');
            $query->whereIn('team_id', $teamIds);
        }

        $projects = $query->get();

        // This action now returns an array with both 'teams' and 'projectManagers'
        $creationData = $createProject->handle();

        return Inertia::render('Projects/Index', [
            'projects' => $projects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'team_id' => $project->team_id,
                'team' => $project->team,
                'project_manager' => $project->projectManager,
                'tasks_count' => $project->tasks_count,
                'status' => $project->status,
                'priority' => $project->priority,
                'total_hours_required' => $project->total_hours_required,
                'hours_progress' => $project->total_hours_required > 0
                    ? (int) round(min(100, (float) ($project->time_logs_sum_hours_worked ?? 0) / $project->total_hours_required * 100))
                    : 0,
                'end_date' => $project->end_date,
                'created_at' => $project->created_at,
            ]),
            // The controller now correctly receives both keys from the action.
            'teams' => $creationData['teams'],
            'projectManagers' => $creationData['projectManagers'],
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request, StoreProject $storeProject)
    {
        $storeProject->handle($request->validated());

        return Redirect::route('projects.index')->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified project with role-aware assignable users.
     */
    public function show(Project $project, ShowProject $showProject): Response
    {
        $projectData = $showProject->handle($project);

        return Inertia::render('Projects/Show', $projectData);
    }
}
