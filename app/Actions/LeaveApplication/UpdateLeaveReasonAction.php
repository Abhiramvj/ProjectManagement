<?php

namespace App\Actions\LeaveApplication;

use App\Models\LeaveApplication;

class UpdateLeaveReasonAction
{
    public function handle(LeaveApplication $leaveApplication, string $reason): void
    {
        $leaveApplication->update([
            'reason' => $reason,
        ]);
    }
}
