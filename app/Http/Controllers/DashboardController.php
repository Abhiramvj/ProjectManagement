<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\CalendarNote;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\Project;
use App\Models\Review;
use App\Models\ReviewCategory;
use App\Models\ReviewCriteria;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\LeaveStatsService;
use App\Services\TaskStatsService;
use App\Services\TimeStatsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $taskStatsService;

    protected $timeStatsService;

    protected $leaveStatsService;

    public function __construct(
        TaskStatsService $taskStatsService,
        TimeStatsService $timeStatsService,
        LeaveStatsService $leaveStatsService
    ) {
        $this->taskStatsService = $taskStatsService;
        $this->timeStatsService = $timeStatsService;
        $this->leaveStatsService = $leaveStatsService;
    }

    /**
     * Display the user's dashboard.
     */
 public function index()
{
    $user = Auth::user()->load('parent');

    // --- ATTENDANCE & GREETING DATA ---
    $totalEmployees = User::count();
    $absentTodayUsers = User::whereHas('leaveApplications', function ($query) {
        $today = now()->toDateString();
        $query->where('status', 'approved')
              ->where('start_date', '<=', $today)
              ->where('end_date', '>=', $today);
    })->get();

    $attendanceData = [
        'total' => $totalEmployees,
        'present' => $totalEmployees - $absentTodayUsers->count(),
        'absent' => $absentTodayUsers->count(),
        'absent_list' => $absentTodayUsers->map->only('id', 'name', 'designation', 'avatar_url'),
    ];

    $hour = now()->hour;
    $greetingMessage = 'Morning';
    $greetingIcon = '🌤️';
    if ($hour >= 12 && $hour < 17) {
        $greetingMessage = 'Afternoon';
        $greetingIcon = '☀️';
    } elseif ($hour >= 17) {
        $greetingMessage = 'Evening';
        $greetingIcon = '🌙';
    }

    // --- CALENDAR DATA ---
    $leaveEvents = LeaveApplication::where('user_id', $user->id)
        ->where('status', 'approved')
        ->get()
        ->map(function ($leave) {
            return [
                'id' => 'leave_' . $leave->id,
                'title' => ucfirst($leave->leave_type) . ' Leave',
                'start' => $leave->start_date,
                'end' => Carbon::parse($leave->end_date)->addDay()->toDateString(),
                'allDay' => true,
                'backgroundColor' => $this->getLeaveColor($leave->leave_type),
                'borderColor' => $this->getLeaveColor($leave->leave_type),
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'type' => 'leave',
                    'leave_type' => $leave->leave_type,
                    'status' => $leave->status,
                    'day_type' => $leave->day_type ?? 'full_day',
                ],
            ];
        });

    $noteEvents = CalendarNote::where('user_id', $user->id)
        ->get()
        ->map(function ($note) {
            return [
                'id' => 'note_' . $note->id,
                'title' => $note->note,
                'start' => $note->date,
                'allDay' => true,
                'backgroundColor' => '#FBBF24',
                'borderColor' => '#F59E0B',
                'textColor' => '#000000',
                'extendedProps' => [
                    'type' => 'note',
                    'note_id' => $note->id,
                ],
            ];
        });

    $holidayEvents = Holiday::all()->map(function ($holiday) {
        return [
            'id' => 'holiday_' . $holiday->id,
            'title' => $holiday->name,
            'start' => $holiday->date->toDateString(),
            'allDay' => true,
            'backgroundColor' => '#10B981',
            'borderColor' => '#059669',
            'textColor' => '#ffffff',
            'extendedProps' => [
                'type' => 'holiday',
            ],
        ];
    });

    $allCalendarEvents = (new Collection($leaveEvents))
        ->merge($noteEvents)
        ->merge($holidayEvents);

    $teamIdsLedByUser = Team::where('team_lead_id', $user->id)->pluck('id');

    // --- PROJECTS AND TASKS ---
    $projects = collect();

    if ($user->hasRole('admin')) {
        $projects = Project::where('status', '!=', 'completed')->latest()->get();
    } elseif ($user->hasRole('project-manager')) {
        $projects = Project::where('status', '!=', 'completed')
            ->where(function ($query) use ($user) {
                $query->where('project_manager_id', $user->id)
                      ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id));
            })
            ->latest()
            ->get();
    } elseif ($user->hasRole('team-lead')) {
        $projects = Project::where('status', '!=', 'completed')
            ->where(function ($query) use ($user, $teamIdsLedByUser) {
                $query->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
                      ->orWhereIn('team_id', $teamIdsLedByUser);
            })
            ->latest()
            ->get();
    }

    $myTasks = Task::where('assigned_to_id', $user->id)
        ->with('project:id,name')
        ->where('status', '!=', 'completed')
        ->orderBy('due_date', 'asc')
        ->get();

    // --- ANNOUNCEMENTS ---
    $announcements = Announcement::with('user:id,name,avatar_url')
        ->latest()
        ->take(5)
        ->get()
        ->map(function ($announcement) {
            return [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'author' => $announcement->user,
                'created_at_formatted' => $announcement->created_at->format('M d, Y'),
            ];
        });

    // --- PERFORMANCE STATS ---
    $taskStats = $this->taskStatsService->getStatsForUser($user->id);
    $timeStats = $this->timeStatsService->getStatsForUser($user->id);
    $leaveStats = $this->leaveStatsService->getStatsForUser($user->id);

 $user = Auth::user()->load('parent'); // or auth()->user()
$isTeamLead = $user->hasRole('team-lead');
$teamLeadId = $user->parent_id;

if ($isTeamLead) {
    $employeeIds = User::where('parent_id', $user->id)->pluck('id')->toArray();

    // Reviews GIVEN by Team Lead
    $reviewsGivenByMe = Review::with(['criteria', 'user'])
        ->where('reviewer_id', $user->id)
        ->whereIn('user_id', $employeeIds)
        ->orderByDesc('year')
        ->orderByDesc('month')
        ->get();

    // Employee Self Reviews ONLY (user_id == reviewer_id)
    $employeeReviews = Review::with(['criteria', 'user'])
        ->whereIn('user_id', $employeeIds)
        ->whereColumn('user_id', 'reviewer_id')
        ->orderByDesc('year')
        ->orderByDesc('month')
        ->get();

    $myReviews = collect();

    $users = User::where('parent_id', $user->id)
        ->orderBy('name')
        ->get(['id', 'name']);
} else {
    // Employee self reviews
    $myReviews = Review::with(['criteria', 'user'])
        ->where('user_id', $user->id)
        ->where('reviewer_id', $user->id)
        ->orderByDesc('year')
        ->orderByDesc('month')
        ->get();

    $reviewsGivenByMe = collect();

    if (!empty($teamLeadId)) {
        $teamLeadReviews = Review::with(['criteria', 'user'])
            ->where('user_id', $user->id)
            ->where('reviewer_id', $teamLeadId)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        // Combine self and team lead reviews
        $employeeReviews = $myReviews->merge($teamLeadReviews);
    } else {
        $employeeReviews = $myReviews;
    }

    $users = collect([['id' => $user->id, 'name' => $user->name]]);
}

$criterias = \App\Models\ReviewCriteria::orderBy('name')->get(['id','category_id', 'name','max_points']);
$categories = ReviewCategory::orderBy('name')->get(['id', 'name','weight']);
// --- RENDER VIEW ---

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
    'attendance' => $attendanceData,
    'calendarEvents' => $allCalendarEvents,
    'greeting' => [
        'message' => $greetingMessage,
        'icon' => $greetingIcon,
        'date' => now()->format('jS F Y'),
    ],
    'projects' => $projects,
    'myTasks' => $myTasks,
    'announcements' => $announcements,
    'taskStats' => $taskStats,
    'timeStats' => $timeStats,
    'leaveStats' => $leaveStats,
    'authUser' => Auth::user()->load('roles'),
    'myReviews' => $myReviews,
    'employeeReviews' => $employeeReviews,
    'reviewsGivenByMe' => $reviewsGivenByMe,
    'isTeamLead' => $isTeamLead,
    'users' => $users,
    'criterias' => $criterias,
    'categories' => $categories,
]);


}



    /**
     * Get color for different leave types.
     */
    private function getLeaveColor($leaveType)
    {
        $colors = [
            'annual' => '#3B82F6',    // Blue
            'sick' => '#EF4444',      // Red
            'personal' => '#F59E0B',  // Amber
            'emergency' => '#DC2626', // Dark Red
            'maternity' => '#EC4899', // Pink
            'paternity' => '#8B5CF6', // Purple
        ];

        return $colors[$leaveType] ?? '#6B7280'; // Default gray
    }

    public function reviews() {
         $user = Auth::user();

        // Fetch reviews with criteria + category
        $reviews = Review::with(['criteria.category'])
            ->where('user_id', $user->id)
            ->orderBy('year', 'desc')
            ->orderByRaw("FIELD(month, 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec')")
            ->get();

        // Group reviews by category
        $grouped = $reviews->groupBy(fn($review) => $review->criteria->category->name);

        // Calculate weighted score
        $weightedScore = 0;
        foreach ($grouped as $categoryName => $items) {
            $categoryWeight = $items->first()->criteria->category->weight; // e.g. 70
            $sum = $items->sum('score'); // sum of all scores under this category
            $max = $items->sum(fn($r) => $r->criteria->max_points); // sum of max points under this category

            $categoryPercent = $max > 0 ? ($sum / $max) * 100 : 0;

            $weightedScore += ($categoryPercent * ($categoryWeight / 100));
        }

        return Inertia::render('Dashboard/Index', [
            'user' => $user,
            'reviews' => $grouped,
            'weightedScore' => round($weightedScore, 2),
        ]);
    }




}
