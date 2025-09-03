<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class CompanyOverviewController extends Controller
{
    public function index()
    {
        // All projects with minimal team info
        $projects = Project::with('team:id,name,team_lead_id')->get();

        // Key summary info: number of users per role (using Spatie tables)
        $roleSummary = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name as role', DB::raw('COUNT(model_id) as count'))
            ->where('model_type', User::class)
            ->groupBy('roles.name')
            ->get();

        // Total employees
        $totalEmployees = User::count();

        // Basic company info
        $companyInfo = [
            'name' => 'Your Company Name',
            'description' => 'Brief description of company, mission, vision...',
            'policies' => ['Work Hours', 'Leave Policy', 'Code of Conduct'],
        ];

        return Inertia::render('CompanyOverview/Index', [
            'companyInfo' => $companyInfo,
            'projects' => $projects,
            'roleSummary' => $roleSummary,
            'totalEmployees' => $totalEmployees,
        ]);
    }
}
