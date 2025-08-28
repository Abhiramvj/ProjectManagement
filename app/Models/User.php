<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * == Properties for Database Columns ==
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property ?Carbon $email_verified_at
 * @property string $password
 * @property ?string $remember_token
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property ?string $image
 * @property ?string $designation
 * @property ?Carbon $hire_date
 * @property ?Carbon $birth_date // ADDED
 * @property ?string $work_mode // ADDED
 * @property ?int $parent_id
 * @property ?int $leave_approver_id
 * @property ?int $team_id // ADDED
 * @property float $leave_balance
 *
 * == Properties for Eloquent Relationships ==
 * @property-read ?User $parent
 * @property-read Collection<int, User> $children
 * @property-read ?User $leaveApprover
 * @property-read Collection<int, Team> $teams
 * @property-read Collection<int, LeaveApplication> $leaveApplications
 * @property-read Collection<int, Role> $roles   // CORRECTED for Spatie/Permission
 *
 * == Properties from Laravel Traits ==
 * @property-read DatabaseNotificationCollection $notifications
 * @property-read DatabaseNotificationCollection $unreadNotifications
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'designation',
        'hire_date',
        'birth_date',
        'work_mode',
        'parent_id',
        'leave_approver_id',
        'team_id',
        'image',
        'leave_balance',
        'total_experience',
        'company_experience',
        'comp_off_balance',
        'total_annual_leave',
        'total_sick_leave',
        'total_personal_leave',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // === LEAVE APPLICATIONS ===

    /**
     * Get the leave applications for the user.
     */
    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class);
    }

    /**
     * Get remaining leave balance for current year
     * Optimized to use scopes and caching
     */
    public function getRemainingLeaveBalance(): float
    {
        $currentYear = now()->year;

        $usedLeaveDays = $this->leaveApplications()
            ->approved()
            ->currentYear()
            ->sum('leave_days');

        $totalLeaveBalance = $this->leave_balance ?? 20;
        $remaining = max(0, $totalLeaveBalance - $usedLeaveDays);

        return $remaining;
    }

    public function getAvailableLeaveBalance(): float
    {
        $currentYear = now()->year;

        // Sum leave days of approved leave applications for current year
        $approvedLeaves = $this->leaveApplications()
            ->approved()
            ->currentYear()
            ->sum('leave_days');

        // Sum leave days of pending leave applications for current year
        $pendingLeaves = $this->leaveApplications()
            ->pending()
            ->currentYear()
            ->sum('leave_days');

        // Total entitled leave balance from DB or default
        $totalLeave = $this->leave_balance ?? 20;

        // Calculate available leave considering approved and pending leaves
        $availableLeave = max(0, $totalLeave - $approvedLeaves - $pendingLeaves);

        return $availableLeave;
    }

    /**
     * Get used leave days for current year
     * Optimized version using scopes
     */
    public function getUsedLeaveDays(): float
    {
        static $cachedUsedDays = null;
        static $cachedUserId = null;
        static $cachedYear = null;

        $currentYear = now()->year;

        // Use cached result if for same user and year
        if ($cachedUsedDays !== null && $cachedUserId === $this->id && $cachedYear === $currentYear) {
            return $cachedUsedDays;
        }

        $usedDays = $this->leaveApplications()
            ->approved()
            ->currentYear()
            ->sum('leave_days');

        // Cache the result
        $cachedUsedDays = $usedDays;
        $cachedUserId = $this->id;
        $cachedYear = $currentYear;

        return $usedDays;
    }

    /**
     * Get pending leave applications
     * Optimized version using scopes
     */
    public function getPendingLeaveApplications()
    {
        return $this->leaveApplications()
            ->pending()
            ->orderByStatusPriority()
            ->latest()
            ->get();
    }

    /**
     * Get leave statistics for current year
     * Single query to get all leave statistics at once
     */
    public function getLeaveStatistics(): array
    {
        $stats = $this->leaveApplications()
            ->currentYear()
            ->selectRaw('
                status,
                COUNT(*) as count,
                SUM(leave_days) as total_days,
                AVG(leave_days) as avg_days
            ')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $totalLeaveBalance = $this->leave_balance ?? 20;
        $usedDays = $stats->get('approved')->total_days ?? 0;
        $pendingDays = $stats->get('pending')->total_days ?? 0;

        return [
            'total_balance' => $totalLeaveBalance,
            'used_days' => $usedDays,
            'pending_days' => $pendingDays,
            'remaining_balance' => max(0, $totalLeaveBalance - $usedDays),
            'available_balance' => max(0, $totalLeaveBalance - $usedDays - $pendingDays),
            'approved_applications' => $stats->get('approved')->count ?? 0,
            'pending_applications' => $stats->get('pending')->count ?? 0,
            'rejected_applications' => $stats->get('rejected')->count ?? 0,
        ];
    }

    // === TIME LOGS ===

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * Get total hours logged for current month
     */
    public function getCurrentMonthHours(): float
    {
        return $this->timeLogs()
            ->whereMonth('work_date', Carbon::now()->month)
            ->whereYear('work_date', Carbon::now()->year)
            ->sum('hours_worked') ?? 0;
    }

    /**
     * Get total hours logged
     */
    public function getTotalHours(): float
    {
        return $this->timeLogs()->sum('hours_worked') ?? 0;
    }

    // === TEAMS ===

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user', 'user_id', 'team_id');
    }

    /**
     * The teams that this user LEADS.
     */
    public function ledTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'team_lead_id');
    }
    // === NOTIFICATIONS ===

    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    public function markNotificationAsRead($notificationId)
    {
        return $this->notifications()->where('id', $notificationId)->update(['read_at' => now()]);
    }

    public function markAllNotificationsAsRead()
    {
        return $this->unreadNotifications()->update(['read_at' => now()]);
    }

    // === ORGANIZATIONAL HIERARCHY ===

    /**
     * Get the parent user (manager/supervisor)
     */
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Get direct subordinates
     */
    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Get all subordinates recursively
     */
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all subordinates (flattened)
     */
    public function getAllSubordinates()
    {
        $subordinates = collect();

        foreach ($this->children as $child) {
            $subordinates->push($child);
            $subordinates = $subordinates->merge($child->getAllSubordinates());
        }

        return $subordinates;
    }

    /**
     * Check if user is manager of another user
     */
    public function isManagerOf(User $user): bool
    {
        return $this->getAllSubordinates()->contains('id', $user->id);
    }

    public function tasks(): HasMany
    {
        // This defines that a User can have many Tasks.
        // It correctly points to the 'assigned_to_id' foreign key on the 'tasks' table.
        return $this->hasMany(Task::class, 'assigned_to_id');
    }

    /**
     * Get the hierarchy path (from top to current user)
     */
    public function getHierarchyPath()
    {
        $path = collect([$this]);
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            $path->prepend($current);
        }

        return $path;
    }

    // === LEAVE APPROVAL ===

    /**
     * Get the designated leave approver
     */
    public function leaveApprover()
    {
        return $this->belongsTo(User::class, 'leave_approver_id');
    }

    /**
     * Get users who can approve this user's leave
     */
    public function getLeaveApprovers()
{
    $approvers = collect();

    // Primary approver
    if ($this->leaveApprover) {
        $approvers->push($this->leaveApprover);
    }

    // Fallback to parent if no specific approver
    if ($approvers->isEmpty() && $this->parent) {
        $approvers->push($this->parent);
    }

    // Add users with leave management permissions (admin, hr, team-lead)
    $leaveManagers = User::whereHas('roles.permissions', function ($query) {
    $query->where('name', 'manage leave applications');
})->get();


    $approvers = $approvers->merge($leaveManagers)->unique('id');

    return $approvers;
}


    /**
     * Check if user can approve leave for another user
     */
    public function canApproveLeaveFor(User $user): bool
    {
        // Check if this user is the designated approver
        if ($user->leave_approver_id === $this->id) {
            return true;
        }

        // Check if this user is the parent
        if ($user->parent_id === $this->id) {
            return true;
        }

        // Check if user has leave management permissions
        if ($this->hasAnyRole(['admin', 'hr', 'project-manager'])) {
            return true;
        }

        return false;
    }

    // === TASKS ===

    /**
     * Get tasks assigned to this user
     */
    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to_id');
    }

    /**
     * Get task completion rate
     */
    public function getTaskCompletionRate(): float
    {
        // Use a single, raw query to get both counts efficiently from the database.
        $stats = $this->assignedTasks()
            ->selectRaw("
                count(*) as total_tasks,
                sum(case when status = 'completed' then 1 else 0 end) as completed_tasks
            ")
            ->first();

        // If there are no tasks, the completion rate is 0.

        if (! $stats || $stats->total_tasks == 0) {

            return 0;
        }

        // Perform the calculation in PHP.
        $completionRate = ($stats->completed_tasks / $stats->total_tasks) * 100;

        return round($completionRate, 1);
    }

    // === PROJECTS ===

    /**
     * Get projects where user is project manager
     */
    public function managedProjects()
    {
        return $this->hasMany(Project::class, 'project_manager_id');
    }

    // === PERFORMANCE METHODS ===

    /**
     * Get user performance score
     */
    public function getPerformanceScore(): int
    {
        $taskScore = $this->getTaskCompletionRate();
        $timeScore = min(100, ($this->getCurrentMonthHours() / 160) * 100);

        $totalLeave = $this->leave_balance ?? 20;
        $leaveScore = 100;
        if ($totalLeave > 0) {
            $leaveScore = max(0, 100 - ($this->getUsedLeaveDays() / $totalLeave) * 100);
        }

        return round(($taskScore + $timeScore + $leaveScore) / 3);
    }

    /**
     * Check if user is active (has logged time recently)
     */
    public function isActive(): bool
    {
        return $this->timeLogs()
            ->where('work_date', '>=', Carbon::now()->subDays(7))
            ->exists();
    }

    public function manager()
    {
        // We use your existing 'parent_id' column for this relationship
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function calendarNotes()
    {
        return $this->hasMany(CalendarNote::class);
    }

    // public function parent()
    // {
    //     // A user BELONGS TO another user (their parent/manager).
    //     return $this->belongsTo(User::class, 'parent_id');
    // }

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                // If the user has an 'image' path stored in the database...
                if ($this->image) {
                    // ...return the full public URL to that image from the storage disk.
                    return Storage::url($this->image);
                }

                // Otherwise, return a URL to a fallback avatar service (ui-avatars.com)
                return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=random';
            }
        );
    }

    public function announcements(): HasMany // <-- THIS IS THE NEW METHOD
    {
        return $this->hasMany(Announcement::class);
    }

    public function approvedAnnualLeaves()
    {
        return $this->hasMany(LeaveApplication::class)
            ->where('leave_type', 'annual')
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year);
    }

    public function approvedSickLeaves()
    {
        return $this->hasMany(LeaveApplication::class)
            ->where('leave_type', 'sick')
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year);
    }

    public function approvedPersonalLeaves()
    {
        return $this->hasMany(LeaveApplication::class)
            ->where('leave_type', 'personal')
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year);
    }

public function hasPermission($permission): bool
{
    if (is_array($permission)) {
        foreach ($permission as $perm) {
            if ($this->hasPermissionTo($perm)) {
                return true;
            }
        }
        return false;
    }

    return $this->hasPermissionTo($permission);
}




}
