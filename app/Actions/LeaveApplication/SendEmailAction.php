<?php

namespace App\Actions\LeaveApplication;

use App\Models\LeaveApplication;
use App\Services\LeaveNotificationService;

class SendEmailAction
{
    public function handle(LeaveApplication $leaveApplication, string $status): void
    {
        $user = $leaveApplication->user;

        if ($status === 'approved') {
            LeaveNotificationService::sendLeaveApprovedNotification($leaveApplication);
        } elseif ($status === 'rejected') {
            LeaveNotificationService::sendLeaveRejectedNotification($leaveApplication);

        }
    }
}
