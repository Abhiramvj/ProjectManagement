<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method bool hasRole(string|array $roles)
 * @method bool hasAnyRole(string|array $roles)
 * @method bool hasAllRoles(string|array $roles)
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


    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class);
    }

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

        $approvedLeaves = $this->leaveApplications()
            ->approved()
            ->currentYear()
            ->sum('leave_days');

        $pendingLeaves = $this->leaveApplications()
            ->pending()
            ->currentYear()
            ->sum('leave_days');

        $totalLeave = $this->leave_balance ?? 20;

        $availableLeave = max(0, $totalLeave - $approvedLeaves - $pendingLeaves);

        return $availableLeave;
    }


    public function getUsedLeaveDays(): float
    {
        static $cachedUsedDays = null;
        static $cachedUserId = null;
        static $cachedYear = null;

        $currentYear = now()->year;

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

    public function getPendingLeaveApplications()
    {
        return $this->leaveApplications()
            ->pending()
            ->orderByStatusPriority()
            ->latest()
            ->get();
    }


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


    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }

    public function getCurrentMonthHours(): float
    {
        return $this->timeLogs()
            ->whereMonth('work_date', Carbon::now()->month)
            ->whereYear('work_date', Carbon::now()->year)
            ->sum('hours_worked') ?? 0;
    }


    public function getTotalHours(): float
    {
        return $this->timeLogs()->sum('hours_worked') ?? 0;
    }


    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user', 'user_id', 'team_id');
    }


    public function ledTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'team_lead_id');
    }

    public function teamLeads()
    {
        return $this->teams()->with('teamLead')->get()->pluck('teamLead')->filter()->unique('id');
    }


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


    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }


    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    public function getAllSubordinates()
    {
        $subordinates = collect();

        foreach ($this->children as $child) {
            $subordinates->push($child);
            $subordinates = $subordinates->merge($child->getAllSubordinates());
        }

        return $subordinates;
    }

    public function isManagerOf(User $user): bool
    {
        return $this->getAllSubordinates()->contains('id', $user->id);
    }

    public function tasks(): HasMany
    {

        return $this->hasMany(Task::class, 'assigned_to_id');
    }

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

    public function leaveApprover()
    {
        return $this->belongsTo(User::class, 'leave_approver_id');
    }


    public function getLeaveApprovers()
    {
        $approvers = collect();

        if ($this->leaveApprover) {
            $approvers->push($this->leaveApprover);
        }

        if ($approvers->isEmpty() && $this->parent) {
            $approvers->push($this->parent);
        }

        $leaveManagers = User::whereHas('roles.permissions', function ($query) {
            $query->where('name', 'manage leave applications');
        })->get();

        $approvers = $approvers->merge($leaveManagers)->unique('id');

        return $approvers;
    }

    public function canApproveLeaveFor(User $user): bool
    {
        if ($user->leave_approver_id === $this->id) {
            return true;
        }

        if ($user->parent_id === $this->id) {
            return true;
        }

        if ($this->hasAnyRole(['admin', 'hr', 'project-manager'])) {
            return true;
        }

        return false;
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to_id');
    }

    public function getTaskCompletionRate(): float
    {
        $stats = $this->assignedTasks()
            ->selectRaw("
                count(*) as total_tasks,
                sum(case when status = 'completed' then 1 else 0 end) as completed_tasks
            ")
            ->first();

        if (! $stats || $stats->total_tasks == 0) {

            return 0;
        }
        $completionRate = ($stats->completed_tasks / $stats->total_tasks) * 100;

        return round($completionRate, 1);
    }

    public function managedProjects()
    {
        return $this->hasMany(Project::class, 'project_manager_id');
    }

    public function teamMembers()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

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

    public function isActive(): bool
    {
        return $this->timeLogs()
            ->where('work_date', '>=', Carbon::now()->subDays(7))
            ->exists();
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function calendarNotes()
    {
        return $this->hasMany(CalendarNote::class);
    }


    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->image) {
                    return Storage::url($this->image);
                }

                return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=random';
            }
        );
    }

    public function announcements(): HasMany
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

     public function projectSessions()
    {
        return $this->hasMany(ProjectSession::class, 'requester_id');
    }

    // Many-to-many: User and Badge, including unlock timestamp
    public function badges()
    {
        return $this->belongsToMany(Badge::class)->withTimestamps()->withPivot('unlocked_at');
    }

    public function completedSessionsCount()
{
    return $this->projectSessions()->where('status', 'completed')->count();
}

    // Many-to-many: User and Milestone, with progress and unlock timestamp
   public function milestones()
{
    return $this->belongsToMany(Milestone::class, 'milestone_user')
        ->withPivot('unlocked_at', 'session_id')
        ->withTimestamps();
}




}
