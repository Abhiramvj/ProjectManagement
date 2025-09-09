<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CompanyOverviewController extends Controller
{
    public function index()
    {
        // All projects with team info and team lead details
        $projects = Project::with([
            'team:id,name,team_lead_id',
            'team.teamLead:id,name',
        ])->get();

        // All teams with their members and leads
        $teams = Team::with([
            'teamLead:id,name',
            'members:id,name',
        ])->get();

        // Role summary for company statistics
        $roleSummary = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name as role', DB::raw('COUNT(model_id) as count'))
            ->where('model_type', User::class)
            ->groupBy('roles.name')
            ->get();

        // Total employees
        $totalEmployees = User::count();

        // Enhanced company information based on IOCOD website
        $companyInfo = [
            'name' => 'IOCOD',
            'tagline' => 'Innovative Digital Solutions & Events',
            'description' => 'At IOCOD, we specialize in creating cutting-edge digital experiences and managing world-class events. Our expertise spans across digital media, event management, technology solutions, and creative services.',
            'mission' => 'To transform ideas into impactful digital experiences that drive business growth and create lasting connections.',
            'vision' => 'To be the leading provider of innovative digital solutions and premium event experiences globally.',

            'work_hours' => [
                'Morning Shift: 6:00 AM – 2:30 PM',
                'Regular Shift: 9:00 AM – 5:00 PM',
                'Evening Shift: 12:00 PM – 8:00 PM',
                'Night Shift: 6:30 PM – 3:30 AM',
            ],

            'code_of_conduct' => 'We maintain the highest standards of professionalism, integrity, and respect in all our interactions. Every team member is expected to be punctual, collaborative, innovative, and committed to delivering excellence in everything we do.',

            'core_values' => [
                'Innovation' => 'We constantly push boundaries and embrace new technologies to deliver cutting-edge solutions.',
                'Excellence' => 'We strive for perfection in every project and maintain the highest quality standards.',
                'Collaboration' => 'We believe in the power of teamwork and foster an inclusive, supportive work environment.',
                'Integrity' => 'We conduct business with honesty, transparency, and ethical practices.',
                'Client-Centric' => 'We put our clients at the center of everything we do, ensuring their success is our success.',
            ],

            'services' => [
                'Digital Media' => 'Comprehensive digital marketing, content creation, and multimedia production services.',
                'Event Management' => 'End-to-end event planning, coordination, and execution for corporate and private events.',
                'Technology Solutions' => 'Custom software development, web applications, and digital transformation services.',
                'Creative Services' => 'Brand development, graphic design, and creative campaign management.',
            ],

            'policies' => [
                'Leave Policy' => 'Comprehensive leave system supporting annual, sick, emergency, maternity, paternity, and compensatory leave types.',
                'Remote Work Policy' => 'Flexible work-from-home options available based on role requirements and manager approval.',
                'Professional Development' => 'Continuous learning opportunities, training programs, and skill development initiatives.',
                'Health & Safety' => 'Comprehensive health insurance, safety protocols, and wellness programs for all employees.',
                'Performance Management' => 'Regular performance reviews, goal setting, and career progression planning.',
                'Diversity & Inclusion' => 'Commitment to maintaining a diverse, inclusive, and equal opportunity workplace.',
            ],

            'benefits' => [
                'Health Insurance' => 'Comprehensive medical, dental, and vision coverage for employees and their families.',
                'Paid Time Off' => 'Generous vacation time, sick leave, and personal days to maintain work-life balance.',
                'Professional Growth' => 'Training budgets, conference attendance, and certification support.',
                'Flexible Schedule' => 'Multiple shift options and remote work opportunities where applicable.',
                'Team Events' => 'Regular team building activities, company outings, and celebration events.',
                'Performance Bonuses' => 'Merit-based bonuses and recognition programs for outstanding performance.',
            ],

            'contact_info' => [
                'address' => 'IOCOD Headquarters, Innovation District',
                'phone' => '+1 (555) 123-IOCOD',
                'email' => 'info@iocod.com',
                'website' => 'https://www.iocod.com',
            ],

            'office_locations' => [
                'Headquarters' => 'Main office with all departments and leadership team',
                'Creative Studio' => 'Dedicated space for design, multimedia production, and creative work',
                'Event Center' => 'State-of-the-art facility for hosting and managing events',
                'Tech Hub' => 'Development center focused on technology solutions and innovation',
            ],
        ];

        // Get current logged-in user's role
        $userRole = Auth::user()->getRoleNames()->first();

        return Inertia::render('CompanyOverview/Index', [
            'companyInfo' => $companyInfo,
            'projects' => $projects,
            'teams' => $teams,
            'roleSummary' => $roleSummary,
            'totalEmployees' => $totalEmployees,
            'userRole' => $userRole,
        ]);
    }
}
