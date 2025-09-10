<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CompanyOverviewController extends Controller
{
    public function index()
    {
        // Company Overview Data
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

        // Hierarchy Data
        $allUsers = User::with('teams')->withCount([
            'tasks',
            'tasks as tasks_completed_count' => function ($query) {
                $query->where('status', 'completed');
            },
        ])->get();

        $managerIds = $allUsers->whereNotNull('parent_id')->pluck('parent_id')->unique()->all();
        $reportingNodes = $this->formatForCompactChart($allUsers, $managerIds);
        $designationBasedNodes = $this->generateDesignationBasedNodes($allUsers);

        // Enhanced company information
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

        $userRole = Auth::user()->getRoleNames()->first();

        return Inertia::render('CompanyOverview/Index', [
            // Company Overview Data
            'companyInfo' => $companyInfo,
            'projects' => $projects,
            'teams' => $teams,
            'roleSummary' => $roleSummary,
            'totalEmployees' => $totalEmployees,
            'userRole' => $userRole,
            
            // Hierarchy Data
            'reportingNodes' => $reportingNodes,
            'designationBasedNodes' => $designationBasedNodes,
        ]);
    }

    private function formatForCompactChart(Collection $users, array $managerIds): array
    {
        $loggedInUser = Auth::user();

        return $users->map(function ($user) use ($managerIds, $loggedInUser) {
            $tags = [];
            if (in_array($user->id, $managerIds)) {
                $tags[] = 'manager';
            }
            if ($user->id === $loggedInUser->id) {
                $tags[] = 'is-logged-in-user';
            }

            $teamName = $user->teams->first()->name ?? $user->designation ?? 'Unassigned';
            $color = $this->generateColorForText($teamName);

            // Permission logic for performance data
            $canViewPerformance = $loggedInUser->hasRole('admin') ||    // Is admin?
                                  $loggedInUser->id === $user->parent_id || // Is their manager?
                                  $loggedInUser->id === $user->id;         // Is it their own node?

            $performanceSummary = null;
            if ($canViewPerformance) {
                $performanceSummary = [
                    'tasks_total' => $user->tasks_count,
                    'tasks_completed' => $user->tasks_completed_count,
                ];
            }

            return [
                'id' => $user->id, 
                'pid' => $user->parent_id, 
                'name' => $user->name,
                'title' => $user->designation,
                'image' => $user->avatar_url ?? ($user->image ? Storage::url($user->image) : 'https://ui-avatars.com/api/?background=random&name='.urlencode($user->name)),
                'color' => $color, 
                'tags' => $tags, 
                'employee_id' => $user->employee_id, 
                'email' => $user->email,
                'hire_date' => $user->hire_date, 
                'total_experience' => $user->total_experience,
                'canViewPerformance' => $canViewPerformance, 
                'performance_summary' => $performanceSummary,
            ];
        })->all();
    }

    private function generateDesignationBasedNodes(Collection $users): array
    {
        $nodes = [];
        $createdDesignationGroups = [];
        $allowedUserIds = $users->pluck('id')->all();
        $loggedInUser = Auth::user();

        foreach ($users as $user) {
            // Permission logic for performance data
            $canViewPerformance = $loggedInUser->hasRole('admin') ||    // Is admin?
                                  $loggedInUser->id === $user->parent_id || // Is their manager?
                                  $loggedInUser->id === $user->id;         // Is it their own node?

            $performanceSummary = null;
            if ($canViewPerformance) {
                $performanceSummary = [
                    'tasks_total' => $user->tasks_count,
                    'tasks_completed' => $user->tasks_completed_count,
                ];
            }
            
            $teamName = $user->teams->first()->name ?? $user->designation ?? 'Unassigned';
            $color = $this->generateColorForText($teamName);
            $imageUrl = $user->avatar_url ?? ($user->image ? Storage::url($user->image) : 'https://ui-avatars.com/api/?name='.urlencode($user->name));

            if (is_null($user->parent_id) || ! in_array($user->parent_id, $allowedUserIds)) {
                $nodes[] = [
                    'id' => $user->id, 
                    'pid' => null, 
                    'name' => $user->name, 
                    'title' => $user->designation, 
                    'image' => $imageUrl,
                    'color' => $color,
                    'tags' => ['employee-node', $user->id === $loggedInUser->id ? 'is-logged-in-user' : ''],
                    'canViewPerformance' => $canViewPerformance, 
                    'performance_summary' => $performanceSummary,
                ];

                continue;
            }

            $directParentId = $user->parent_id;
            $designation = $user->designation ?? 'Unassigned';

            if (! isset($createdDesignationGroups[$directParentId][$designation])) {
                $groupNodeId = 'group_'.$directParentId.'_'.str_replace(' ', '_', $designation);
                $nodes[] = [
                    'id' => $groupNodeId, 
                    'pid' => $directParentId, 
                    'name' => $designation,
                    'title' => 'Designation Group', 
                    'tags' => ['role-category'],
                    'color' => $this->generateColorForText($designation),
                ];
                $createdDesignationGroups[$directParentId][$designation] = true;
            }

            $groupNodeId = 'group_'.$directParentId.'_'.str_replace(' ', '_', $designation);

            $nodes[] = [
                'id' => $user->id, 
                'pid' => $groupNodeId, 
                'name' => $user->name, 
                'title' => $user->designation, 
                'image' => $imageUrl,
                'color' => $color,
                'tags' => ['employee-node', $user->id === $loggedInUser->id ? 'is-logged-in-user' : ''],
                'canViewPerformance' => $canViewPerformance, 
                'performance_summary' => $performanceSummary,
            ];
        }

        return $nodes;
    }

    private function generateColorForText(string $text): string
    {
        $hash = crc32($text);
        $hue = $hash % 360;
        $saturation = ($hash % 20) + 65;
        $lightness = ($hash % 10) + 40;

        return "hsl({$hue}, {$saturation}%, {$lightness}%)";
    }
}