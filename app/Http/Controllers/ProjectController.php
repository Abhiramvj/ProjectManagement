<?php

namespace App\Http\Controllers;

use App\Actions\Project\CreateProject;
use App\Actions\Project\ShowProject;
use App\Actions\Project\StoreProject;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function create(CreateProject $createProject)
    {
        return Inertia::render('Projects/Create', $createProject->handle());
    }

    public function store(StoreProjectRequest $request, StoreProject $storeProject)
    {
        $storeProject->handle($request->validated());

        return Redirect::route('dashboard')->with('success', 'Project created.');
    }

    public function show(Project $project, ShowProject $showProject)
    {
        $data = $showProject->handle($project);

        return Inertia::render('Projects/Show', $data);
    }
}
