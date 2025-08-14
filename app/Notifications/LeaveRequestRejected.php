<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class LeaveRequestRejected extends Notification
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
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $targetUrl = $notifiable->can('manage leave applications')
            ? route('leave.logs', ['highlight' => $this->leaveApplication->id])
            : route('leave.index');

        return [
            'title' => 'Leave Request Rejected',
            'message' => 'Your leave request from '.$this->leaveApplication->start_date.' to '.$this->leaveApplication->end_date.' has been rejected.',
            'type' => 'leave_rejected',
            'leave_id' => $this->leaveApplication->id,
            'rejected_by' => Auth::user()->name,
            'url' => $targetUrl,
        ];
    }
}
