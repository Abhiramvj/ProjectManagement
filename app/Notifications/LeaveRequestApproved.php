<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class LeaveRequestApproved extends Notification
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
        return ['database']; // This tells Laravel to save the notification in the 'notifications' table.
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Leave Request Approved',
            'message' => ' leave request from '.$this->leaveApplication->start_date
                        .' to '.$this->leaveApplication->end_date.' has been approved.',
            'type' => 'leave_approved',
            'leave_id' => $this->leaveApplication->id,
            'approved_by' => Auth::user()->name,
            'url' => route('leave.fullRequests'), // employee page
        ];
    }
}
