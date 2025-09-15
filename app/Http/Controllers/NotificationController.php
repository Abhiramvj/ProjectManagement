<?php

namespace App\Http\Controllers;

use App\Actions\Notification\GetNotificationsAction;
use App\Actions\Notification\GetRecentNotificationsAction;
use App\Actions\Notification\GetUnreadCountAction;
use App\Actions\Notification\MarkAllNotificationsAsReadAction;
use App\Actions\Notification\MarkNotificationAsReadAction;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    protected GetNotificationsAction $getNotificationsAction;

    protected GetRecentNotificationsAction $getRecentNotificationsAction;

    protected GetUnreadCountAction $getUnreadCountAction;

    protected MarkNotificationAsReadAction $markNotificationAsReadAction;

    protected MarkAllNotificationsAsReadAction $markAllNotificationsAsReadAction;

    public function __construct(GetNotificationsAction $getNotificationsAction, GetRecentNotificationsAction $getRecentNotificationsAction,
        GetUnreadCountAction $getUnreadCountAction, MarkNotificationAsReadAction $markNotificationAsReadAction, MarkAllNotificationsAsReadAction $markAllNotificationsAsReadAction)
    {
        $this->getNotificationsAction = $getNotificationsAction;
        $this->getRecentNotificationsAction = $getRecentNotificationsAction;
        $this->getUnreadCountAction = $getUnreadCountAction;
        $this->markNotificationAsReadAction = $markNotificationAsReadAction;
        $this->markAllNotificationsAsReadAction = $markAllNotificationsAsReadAction;

    }

    public function index()
    {
        $notifications = $this->getNotificationsAction
            ->execute()
            ->paginate(20);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function getRecent()
    {
        $notifications = $this->getRecentNotificationsAction->execute(Auth::user());

        return response()->json(['data' => $notifications]);
    }

    public function getUnreadCount()
    {
        $count = $this->getUnreadCountAction->execute(Auth::user());

        return response()->json(['count' => $count]);
    }

    public function markAsRead($id)
    {
        $this->markNotificationAsReadAction->execute($id);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        $this->markAllNotificationsAsReadAction->execute(Auth::user());

        return back()->with('success', 'All notifications marked as read.');
    }
}
