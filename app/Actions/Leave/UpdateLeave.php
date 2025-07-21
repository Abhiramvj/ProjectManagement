<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;

class UpdateLeave
{
    public function handle(LeaveApplication $leaveApplication, string $status): void
    {
        $leaveApplication->update(['status' => $status]);
    }
}
