<?php

namespace App\Http\Controllers;

use App\Models\Notification; // <-- Use our new Notification model
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * The main notifications page, scoped by user role.
     */
    public function index()
    {
        // Start building the query using our new Notification model
        $query = Notification::query();

        $this->scopeQueryByUserRole($query);

        $notifications = $query->with('user:id,name') // Eager load the user's name
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get recent notifications for the dropdown, scoped by user role.
     */
    public function getRecent()
    {
        $query = Notification::query();

        $this->scopeQueryByUserRole($query);

        $notifications = $query->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json(['data' => $notifications]);
    }

    /**
     * Get the count of unread notifications, scoped by user role.
     */
    public function getUnreadCount()
    {
        $query = Notification::query()->whereNull('read_at');

        $this->scopeQueryByUserRole($query);

        return response()->json(['count' => $query->count()]);
    }

    /**
     * This is a reusable method to apply the correct security scope to our queries.
     */
    private function scopeQueryByUserRole(Builder $query): void
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['admin', 'hr'])) {
            // Admin/HR can see notifications for all relevant users.
            $query->whereHas('user.roles', function ($q) {
                $q->whereIn('name', ['employee', 'team-lead', 'project-manager']);
            });
        } elseif ($user->hasRole('team-lead')) {
            // Team lead can only see notifications for their team members.
            $ledTeams = $user->ledTeams()->with('members:id')->get();
            $memberIds = $ledTeams->flatMap(fn ($team) => $team->members->pluck('id'))->unique();

            $query->whereIn('notifiable_id', $memberIds);
        } else {
            // A regular employee can ONLY see their own notifications.
            $query->where('notifiable_id', $user->id)
                ->where('notifiable_type', User::class);
        }
    }

    // --- The following methods for marking notifications as read remain largely unchanged ---
    // They are actions performed by the logged-in user on notifications they have access to.

    public function markAsRead($id)
    {
        // Find the notification, but ensure it's one the user is allowed to see.
        $query = Notification::query();
        $this->scopeQueryByUserRole($query);
        $notification = $query->findOrFail($id);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        // For individual employees, also update their native unread notifications
        if ($notification->notifiable_id === auth()->id()) {
            auth()->user()->notifications()->find($id)?->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        // Get all unread notifications the user is allowed to see
        $query = Notification::query()->whereNull('read_at');
        $this->scopeQueryByUserRole($query);
        $notificationsToUpdate = $query->get();

        if ($notificationsToUpdate->isNotEmpty()) {
            // Mark them as read in the database
            Notification::whereIn('id', $notificationsToUpdate->pluck('id'))->update(['read_at' => now()]);

            // For individual employees, also update their native unread notifications
            auth()->user()->unreadNotifications->markAsRead();
        }

        return response()->json(['success' => true]);
    }
}
