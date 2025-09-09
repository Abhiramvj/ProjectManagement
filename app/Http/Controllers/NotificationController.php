<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * The main notifications page, scoped by user role.
     */
    public function index()
    {
        $user = Auth::user();

        // Start building the query using our new Notification model
        $query = Notification::query();

        $this->scopeQueryByUserRole($query);

        // Exclude self-generated notifications
        if ($user->hasRole('team-lead')) {
            $query->where('notifiable_id', '!=', $user->id);
        }

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

    public function markAsRead($id)
    {
        $notification = DatabaseNotification::findOrFail($id);
        $notification->update(['read_at' => now()]);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        foreach (auth()->user()->unreadNotifications as $notification) {
            $notification->markAsRead();
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}
