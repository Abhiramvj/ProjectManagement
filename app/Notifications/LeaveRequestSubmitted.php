<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmitted extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public LeaveApplication $leaveApplication)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Save to the database
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Send managers to the approval/logs page, others to their leave index
        $targetUrl = $notifiable->can('manage leave applications')
            ? route('leave.logs', ['highlight' => $this->leaveApplication->id])
            : route('leave.index');

        return [
            'title' => 'New Leave Request',
            'message' => 'A new leave application from '.$this->leaveApplication->user->name.' requires your approval.',
            'type' => 'leave_request',
            'leave_id' => $this->leaveApplication->id,
            'user_name' => $this->leaveApplication->user->name,
            'url' => $targetUrl,
        ];
    }
}
