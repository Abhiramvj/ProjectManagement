<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Services\DashboardDataFetcher;
use App\Services\LeaveStatsService;
use App\Services\TaskStatsService;
use App\Services\TimeStatsService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $taskStatsService;

    protected $timeStatsService;

    protected $leaveStatsService;

    protected $dashboardDataFetcher;

    public function __construct(
        TaskStatsService $taskStatsService,
        TimeStatsService $timeStatsService,
        LeaveStatsService $leaveStatsService,
        DashboardDataFetcher $dashboardDataFetcher
    ) {
        $this->taskStatsService = $taskStatsService;
        $this->timeStatsService = $timeStatsService;
        $this->leaveStatsService = $leaveStatsService;
        $this->dashboardDataFetcher = $dashboardDataFetcher;
    }

    /**
     * Display the user's dashboard.
     */
    public function index()
    {
        $user = Auth::user()->load('parent');

        $attendance = $this->dashboardDataFetcher->getAttendanceData($user);
        $calendarEvents = $this->dashboardDataFetcher->getCalendarEvents($user);
        $announcements = $this->dashboardDataFetcher->getAnnouncements();
        $greeting = $this->dashboardDataFetcher->getGreetingData();
        $taskStats = $this->taskStatsService->getStatsForUser($user->id);
        $timeStats = $this->timeStatsService->getStatsForUser($user->id);
        $leaveStats = $this->leaveStatsService->getStatsForUser($user->id);

        $projects = $user->hasRole(['admin', 'project-manager', 'team-lead'])
            ? Project::where('status', '!=', 'completed')
                ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
                ->latest()
                ->get()
            : collect();

        $myTasks = Task::where('assigned_to_id', $user->id)
            ->with('project:id,name')
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->get();

        return Inertia::render('Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'designation' => $user->designation,
                'total_experience' => $user->total_experience,
                'hire_date' => $user->hire_date,
                'parent' => $user->parent ? [
                    'id' => $user->parent->id,
                    'name' => $user->parent->name,
                ] : null,
            ],
            'attendance' => $attendance,
            'calendarEvents' => $calendarEvents,
            'greeting' => array_merge($greeting, [
                'date' => now()->format('jS F Y'),
            ]),
            'projects' => $projects,
            'myTasks' => $myTasks,
            'announcements' => $announcements,
            'taskStats' => $taskStats,
            'timeStats' => $timeStats,
            'leaveStats' => $leaveStats,
        ]);
    }
}
