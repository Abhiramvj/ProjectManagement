<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\GetAnnouncementAction;
use App\Actions\Dashboard\GetAttendanceDataAction;
use App\Actions\Dashboard\GetCalendarEventsAction;
use App\Actions\Dashboard\GetGreetingAction;
use App\Actions\Dashboard\GetPerformanceStatsAction;
use App\Actions\Dashboard\GetProjectsAndTasksAction;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the user's dashboard.
     */
    public function index(GetAttendanceDataAction $getAttendanceDataAction, GetGreetingAction $getGreetingAction,
        GetCalendarEventsAction $getCalendarEventsAction, GetProjectsAndTasksAction $getProjectsAndTasksAction,
        GetAnnouncementAction $getAnnouncementAction, GetPerformanceStatsAction $getPerformanceStatsAction)
    {
        $user = Auth::user()->load('parent');

        $attendanceData = $getAttendanceDataAction->execute();
        $greeting = $getGreetingAction->execute();
        $calendarEvents = $getCalendarEventsAction->execute($user->id);
        $projectsAndTasks = $getProjectsAndTasksAction->execute($user);
        $announcements = $getAnnouncementAction->execute();
        $performanceStats = $getPerformanceStatsAction->execute($user->id);

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
            'calendarEvents' => $calendarEvents,
            'greeting' => $greeting,
            'projects' => $projectsAndTasks['projects'],
            'myTasks' => $projectsAndTasks['myTasks'],
            'taskStats' => $performanceStats['taskStats'],
            'timeStats' => $performanceStats['timeStats'],
            'leaveStats' => $performanceStats['leaveStats'],
            'authUser' => Auth::user()->load('roles'),
            'announcements' => $announcements,
        ]);
    }
    /**
     * Get color for different leave types.
     */
}
