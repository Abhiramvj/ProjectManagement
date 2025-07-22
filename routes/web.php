<?php

use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\PerformanceReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserHierarchyController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\UserController;
use App\Models\LeaveApplication;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\TimeLog; // Added TimeLog model import
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// Make login page the landing page for guests
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'canRegister' => Route::has('register'),
        'status' => session('status'),
    ]);
})->middleware('guest')->name('login');

// Keep the original welcome page accessible at /welcome (optional)
Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $user = Auth::user();
    $projects = collect();
    $myTasks = collect();
    $pendingLeaveRequests = collect();
    $stats = [];

    // HR & Admin Logic
    if ($user->hasRole('admin') || $user->hasRole('hr')) {
        $pendingLeaveRequests = LeaveApplication::with('user:id,name')
            ->where('status', 'pending')
            ->latest()
            ->get(['id', 'user_id', 'start_date', 'end_date', 'reason', 'leave_type']);
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
    Route::get('/performance/{user}', [PerformanceReportController::class, 'show'])->name('performance.show')->middleware(['can:manage employees']);
    Route::resource('roles', RoleController::class)->only(['index', 'store', 'edit', 'update'])->middleware(['can:manage roles']);
    Route::resource('projects', ProjectController::class)->only(['create', 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::resource('leave', LeaveApplicationController::class)->only(['index', 'store'])->middleware(['can:apply for leave']);
    Route::patch('/leave/{leave_application}', [LeaveApplicationController::class, 'update'])->name('leave.update')->middleware(['can:manage leave applications']);
    Route::delete('/leave/{leave_application}/cancel', [LeaveApplicationController::class, 'cancel'])->name('leave.cancel')->middleware(['auth', 'can:apply for leave']);
    Route::resource('hours', TimeLogController::class)->only(['index', 'store']);
    Route::resource('teams', TeamController::class)->only(['index', 'store'])->middleware(['can:manage employees']);

    Route::get('/company-hierarchy', [UserHierarchyController::class, 'index'])
        ->name('company.hierarchy');

         Route::get('/leave-calendar', [LeaveApplicationController::class, 'calendar'])
    ->middleware(['auth', 'verified'])
    ->name('leaves.calendar');
});
Route::get('/dev-login/{role}', function ($role) {
    abort_unless(app()->isLocal(), 403); // Restrict to local/dev ONLY

    $user = User::role($role)->first();
    if (! $user) {
        abort(404);
    }
    Auth::login($user); // Log in as user of given role

    return redirect('/dashboard'); // Redirect to dashboard or wherever you want
})->name('dev.login');

require __DIR__.'/auth.php';
