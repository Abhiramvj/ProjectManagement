<?php

namespace App\Actions\LeaveApplication;

use App\Models\LeaveApplication;
use App\Notifications\LeaveRequestSubmitted;
use App\Services\LeaveNotificationService;
use Illuminate\Support\Facades\Log;

class LeaveNotifier
{
    public function notify(LeaveApplication $leaveApplication): void
    {

        LeaveNotificationService::sendLeaveSubmittedNotification($leaveApplication);

        // approvers
        try {
            $applicantId = $leaveApplication->user_id;
            $approvers = $leaveApplication->user->getLeaveApprovers()->unique('id');

            foreach ($approvers as $approver) {
                if ($approver->id === $applicantId) {
                    continue;
                }
                $approver->notify(new LeaveRequestSubmitted($leaveApplication));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send leave request notifications', [
                'error' => $e->getMessage(),
                'leave_id' => $leaveApplication->id,
            ]);
        }
    }
}
