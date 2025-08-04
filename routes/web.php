<?php

use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\PerformanceReportController;
use App\Http\Controllers\CalendarNoteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveCalendarController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserHierarchyController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\AnnouncementController; // <-- Make sure this is imported


// Make login page the landing page for guests
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'canRegister' => Route::has('register'),
        'status' => session('status'),
    ]);
})->middleware('guest')->name('login');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User management routes
    Route::resource('users', UserController::class)->except(['show'])->middleware(['can:manage employees']);
    Route::get('/performance/{user}', [PerformanceReportController::class, 'show'])
        ->name('performance.show')
        ->middleware(['can:manage employees']);

    // Role management routes
    Route::resource('roles', RoleController::class)
        ->only(['index', 'store', 'edit', 'update'])
        ->middleware(['can:manage roles']);

    // Project routes
    Route::resource('projects', ProjectController::class)->only(['index', 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

    // Task routes
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');

    // --- THIS IS THE FIX ---
    // Add the specific route for updating only the status
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');

    // Leave application routes
    Route::resource('leave', LeaveApplicationController::class)->only(['index', 'store', 'destroy'])->middleware(['can:apply for leave']);
    Route::patch('/leave/{leave_application}', [LeaveApplicationController::class, 'update'])->name('leave.update')->middleware(['can:manage leave applications']);
    Route::delete('/leave/{leave_application}/cancel', [LeaveApplicationController::class, 'cancel'])->name('leave.cancel')->middleware(['can:apply for leave']);

    // Leave calendar route
    Route::get('/leave-calendar', [LeaveCalendarController::class, 'index'])->name('leaves.calendar');

    // Time logging routes
    Route::resource('hours', TimeLogController::class)->only(['index', 'store']);

    // Team management routes
    Route::resource('teams', TeamController::class)->only(['index', 'store', 'update', 'destroy'])->middleware(['can:manage employees']);

    // Company hierarchy route
    Route::get('/company-hierarchy', [UserHierarchyController::class, 'index'])->name('company.hierarchy');

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent', [NotificationController::class, 'getRecent'])->name('notifications.recent');

    // Calendar note routes
    Route::post('/calendar-notes', [CalendarNoteController::class, 'store'])->name('calendar-notes.store');
    Route::put('/calendar-notes/{calendarNote}', [CalendarNoteController::class, 'update'])->name('calendar-notes.update');
    Route::delete('/calendar-notes/{calendarNote}', [CalendarNoteController::class, 'destroy'])->name('calendar-notes.destroy');


    Route::resource('announcements', AnnouncementController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage announcements');
    // -----------------------------------------
});
    
// Developer login route
Route::get('/dev-login/{role}', function ($role) {
    abort_unless(app()->isLocal(), 403);

    $user = User::role($role)->firstOrFail();
    Auth::login($user);
    return redirect('/dashboard');
})->name('dev.login');

require __DIR__.'/auth.php';