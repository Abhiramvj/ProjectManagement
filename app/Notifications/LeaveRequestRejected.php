<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
        return [
            'title'       => 'Leave Request Rejected',
            'message'     => 'Your leave request from ' . $this->leaveApplication->start_date . ' to ' . $this->leaveApplication->end_date . ' has been rejected.',
            'type'        => 'leave_rejected', // For the frontend to pick an icon
            'leave_id'    => $this->leaveApplication->id,
            'rejected_by' => Auth::user()->name,
            'url'         => route('leave.index'),
        ];
    }
}