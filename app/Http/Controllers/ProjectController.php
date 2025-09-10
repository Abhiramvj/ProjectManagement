<?php

namespace App\Http\Controllers;

use App\Actions\Project\CreateProject;
use App\Actions\Project\ShowProject;
use App\Actions\Project\StoreProject;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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




    // Base project query with eager loading and aggregations
  $query = Project::query()
    ->with(['projectManager:id,name', 'team:id,name'])
    ->withCount('tasks')
    ->withSum('timeLogs', 'hours_worked')
    ->latest();


    // Role-based scoping for viewing projects
    if ($user->hasRole('project-manager')) {
        // Project Managers see only projects they manage
        $query->where('project_manager_id', $user->id);
    } elseif (! ($user->hasRole('admin') || $user->hasRole('hr'))) {
        // Team leads and employees see projects assigned to their teams
        $teamIds = $user->teams->pluck('id')->toArray();

        // If user has no teams, avoid invalid query
        if (count($teamIds) > 0) {
            $query->whereIn('team_id', $teamIds);
        } else {
            // If no teams, return empty collection early
            return Inertia::render('Projects/Index', [
                'projects' => collect(),
                'teams' => [],
                'projectManagers' => [],
            ]);
        }
    }


    // Fetch projects from database
    $projects = $query->get();

    // Calculate hours_progress for each project
    $mappedProjects = $projects->map(function ($project) {
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



    // Additional data for project creation (teams and project managers)
    $creationData = $createProject->handle();

    // Return the data to the Vue component via Inertia
    return Inertia::render('Projects/Index', [
        'projects' => $mappedProjects,
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
        $user = Auth::user();

        if ($project->project_manager_id === $user->id && ! $project->team_id) {

            // This gives the PM the authority to assign any team in the system.
            $projectData['userTeams'] = Team::query()->select('id', 'name')->get();
        }

        return Inertia::render('Projects/Show', $projectData);
    }

    public function assignTeam(Request $request, Project $project)
    {

        $this->authorize('update', $project);

        $validated = $request->validate([
            'team_id' => [
                'required',
                'exists:teams,id', // Ensures the team ID exists in the 'teams' table
            ],
        ]);

        // 3. Update Logic: Assign the validated team_id to the project.
        $project->update([
            'team_id' => $validated['team_id'],
        ]);

        //    Once a team is assigned, automatically add all members of that team
        //    to this project's list of members.
        $team = Team::with('members')->find($validated['team_id']);
        if ($team) {
            $memberIds = $team->members()->pluck('users.id')->toArray();
            // Also ensure the Project Manager is included as a member
            if (! in_array($project->project_manager_id, $memberIds)) {
                $memberIds[] = $project->project_manager_id;
            }
            $project->members()->sync($memberIds);
        }

        return Redirect::route('projects.show', $project)->with('success', 'Project assigned to team successfully.');
    }
}
