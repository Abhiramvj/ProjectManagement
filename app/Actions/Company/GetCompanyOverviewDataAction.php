<?php

namespace App\Actions\Company;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GetCompanyOverviewDataAction
{
    public function execute(): array
    {
        $projects = Project::with([
            'team:id,name,team_lead_id',
            'team.teamLead:id,name',
        ])->get();

        $teams = Team::with([
            'teamLead:id,name',
            'members:id,name',
        ])->get();

        $roleSummary = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name as role', DB::raw('COUNT(model_id) as count'))
            ->where('model_type', User::class)
            ->groupBy('roles.name')
            ->get();

        $totalEmployees = User::count();

        $userRole = Auth::user()->getRoleNames()->first();

        return compact('projects', 'teams', 'roleSummary', 'totalEmployees', 'userRole');
    }
}
