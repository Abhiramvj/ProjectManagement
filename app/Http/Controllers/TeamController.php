<?php

namespace App\Http\Controllers;

use App\Actions\Team\GetTeams;
use App\Actions\Team\StoreTeams;
use App\Http\Requests\Team\StoreTeamRequest;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(GetTeams $getTeams)
    {
        return Inertia::render('Admin/Teams/Index', $getTeams->handle());
    }

    public function store(StoreTeamRequest $request, StoreTeams $storeTeams)
    {
        $storeTeams->handle($request->validated());

        return Redirect::route('teams.index')->with('success', 'Team created successfully.');
    }
}
