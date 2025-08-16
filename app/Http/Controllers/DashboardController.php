<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
// All necessary imports from both files, deduplicated
use App\Models\CalendarNote;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\Project;
use App\Models\Task;
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
    // Using the Service-based architecture from the second controller for better organization
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

        // --- ATTENDANCE & GREETING DATA (Common to both) ---
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

        // --- CALENDAR DATA (Common to both) ---
        $leaveEvents = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => 'leave_'.$leave->id,
                    'title' => ucfirst($leave->leave_type).' Leave',
                    'start' => $leave->start_date,
                    'end' => $leave->start_date === $leave->end_date
                        ? null // Single day event
                        : Carbon::parse($leave->end_date)->addDay()->toDateString(),
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
                    'id' => 'note_'.$note->id,
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
                'id' => 'holiday_'.$holiday->id,
                'title' => $holiday->name,
                'start' => $holiday->date->toDateString(),
                'allDay' => true,
                'backgroundColor' => '#10B981', // Tailwind Emerald for example
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

        // --- PROJECTS AND TASKS (Using the more concise logic) ---
        $projects = collect();
        if ($user->hasRole(['admin', 'project-manager', 'team-lead'])) {
            $projects = Project::where('status', '!=', 'completed')
                ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
                ->latest()->get();
        }

        $myTasks = Task::where('assigned_to_id', $user->id)
            ->with('project:id,name')
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')->get();

        // --- ANNOUNCEMENTS (Merged from the first controller) ---
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

        // --- PERFORMANCE STATS (Using the Service classes) ---
        $taskStats = $this->taskStatsService->getStatsForUser($user->id);
        $timeStats = $this->timeStatsService->getStatsForUser($user->id);
        $leaveStats = $this->leaveStatsService->getStatsForUser($user->id);

        // --- RENDER VIEW (With all props combined) ---
        return Inertia::render('Dashboard', [
            'user' => $user->append('avatar_url'),
            'attendance' => $attendanceData,
            'calendarEvents' => $allCalendarEvents,
            'greeting' => [
                'message' => $greetingMessage,
                'icon' => $greetingIcon,
                'date' => now()->format('jS F Y'),
            ],
            'projects' => $projects,
            'myTasks' => $myTasks,
            'announcements' => $announcements, // <-- Merged
            'taskStats' => $taskStats,         // <-- Merged
            'timeStats' => $timeStats,         // <-- Merged
            'leaveStats' => $leaveStats,         // <-- Merged
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
}
