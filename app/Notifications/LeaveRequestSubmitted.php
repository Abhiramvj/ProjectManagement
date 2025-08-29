<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmitted extends Notification
{
    use Queueable;

    protected LeaveApplication $leaveApplication;

    public function __construct(LeaveApplication $leaveApplication)
    {
        $this->leaveApplication = $leaveApplication;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Leave Request',
            'message' => 'A new leave application from '.$this->leaveApplication->user->name.' requires your approval.',
            'type' => 'leave_request',
            'leave_id' => $this->leaveApplication->id,
            'user_name' => $this->leaveApplication->user->name,
            'url' => $notifiable->hasPermissionTo('manage leave applications')
                ? route('leave.manageRequests')
                : route('leave.index'),
        ];
    }
}
