<?php

namespace App\Http\Controllers;

use App\Actions\Project\AssignTeamAction;
use App\Actions\Project\CreateProjectAction;
use App\Actions\Project\GetProjectsAction;
use App\Actions\Project\ShowProjectAction;
use App\Actions\Project\StoreProjectAction;
use App\Http\Requests\AssignTeamRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function index(CreateProjectAction $createProject, GetProjectsAction $getProjectsAction): Response
    {
        return Inertia::render('Projects/Index', [
            'projects' => $getProjectsAction->execute(),
            'teams' => $createProject->execute()['teams'],
            'projectManagers' => $createProject->execute()['projectManagers'],
        ]);
    }

    public function store(StoreProjectRequest $request, StoreProjectAction $storeProject)
    {

        $storeProject->execute($request->validated());

        return Redirect::route('projects.index')->with('success', 'Project created successfully.');
    }

    public function show(Project $project, ShowProjectAction $showProject): Response
    {
        $projectData = $showProject->execute($project);
        $user = Auth::user();

        if ($project->project_manager_id === $user->id && ! $project->team_id) {
            $projectData['userTeams'] = Team::query()->select('id', 'name')->get();
        }

        return Inertia::render('Projects/Show', $projectData);
    }

    public function assignTeam(AssignTeamRequest $request, Project $project, AssignTeamAction $assignTeamAction)
    {
        $this->authorize('update', $project);

        return $assignTeamAction->execute($project, $request->validated('team_id'));
    }
}
