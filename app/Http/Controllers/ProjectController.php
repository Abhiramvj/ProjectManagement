<?php

namespace App\Http\Controllers;

use App\Actions\Project\CreateProject;
use App\Actions\Project\ShowProject;
use App\Actions\Project\StoreProject;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
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
