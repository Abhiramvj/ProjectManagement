<?php

namespace App\Services;

use App\Models\LeaveApplication;
use App\Models\LeaveLog;

class LeaveLogger
{
    public function handle(LeaveApplication $leaveApplication, string $action, string $description, array $details = []): void
    {
        LeaveLog::create([
            'user_id' => $leaveApplication->user_id,
            'actor_id' => auth()->id(),
            'leave_application_id' => $leaveApplication->id,
            'action' => $action,
            'description' => $description,
            'details' => $details,
        ]);
    }
}
