<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TaskController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController; 
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\PerformanceReportController; // Import
use App\Http\Controllers\NotificationController;

use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use App\Models\Team;

use App\Http\Controllers\UserController; // Import at the top
use App\Models\Task; // <-- IMPORT TASK MODEL
use App\Models\LeaveApplication; // <-- IMPORT LEAVE APPLICATION MODEL
use App\Models\User;
use App\Models\TimeLog; // <-- IMPORT TIME LOG MODEL
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

    Route::get('/dashboard', function () {
    $user =Auth::user();
    $projects = collect();
    $myTasks = collect();
    $pendingLeaveRequests = collect();
    $stats = [];

    // HR & Admin Logic
    if ($user->hasRole('admin') || $user->hasRole('hr')) {
        $pendingLeaveRequests = LeaveApplication::where('status', 'pending')->with('user:id,name')->latest()->get();
        $stats['employee_count'] = User::count();
    }

    // PM & Admin Logic
    if ($user->hasRole('admin') || $user->hasRole('project-manager')) {
        $projectQuery = $user->hasRole('admin') ? Project::query() : Project::where('project_manager_id', $user->id);
        $projects = $projectQuery->get();
        $stats['project_count'] = $projects->count();
    } 
    // Team Lead Logic
    elseif ($user->hasRole('team-lead')) {
        $teamIds = Team::where('team_lead_id', $user->id)->pluck('id');
        $projects = Project::whereIn('team_id', $teamIds)->get();
    }
    
    // Task Logic for Employee, Team Lead, Admin
    if ($user->hasRole('employee') || $user->hasRole('team-lead') || $user->hasRole('admin')) {
        $myTasks = Task::where('assigned_to_id', $user->id)->with('project:id,name')->latest()->get();
    }

    return Inertia::render('Dashboard', [
        'projects' => $projects,
        'myTasks' => $myTasks,
        'pendingLeaveRequests' => $pendingLeaveRequests,
        'stats' => $stats,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // All other application routes
    Route::resource('users', UserController::class)->only(['index', 'create', 'store'])->middleware(['can:manage employees']);
    Route::get('/performance/{user}', [\App\Http\Controllers\PerformanceReportController::class, 'show'])->name('performance.show')->middleware(['can:manage employees']);
    Route::resource('roles', RoleController::class)->only(['index', 'store', 'edit', 'update'])->middleware(['can:manage roles']);
    Route::resource('projects', ProjectController::class)->only(['create', 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::resource('leave', LeaveApplicationController::class)->only(['index', 'store'])->middleware(['can:apply for leave']);
    Route::patch('/leave/{leave_application}', [LeaveApplicationController::class, 'update'])->name('leave.update')->middleware(['can:manage leave applications']);
    Route::resource('hours', TimeLogController::class)->only(['index', 'store']);
    Route::resource('teams', TeamController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('users', UserController::class)->except(['show']);

    Route::resource('teams', TeamController::class)
    ->only(['index', 'store'])
    ->middleware(['can:manage employees']);

    Route::get('/leave-calendar', [LeaveApplicationController::class, 'calendar'])
    ->middleware(['auth', 'verified'])
    ->name('leaves.calendar');


    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent', [NotificationController::class, 'getRecent'])->name('notifications.recent');});


require __DIR__.'/auth.php';