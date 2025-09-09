<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;

class ApproveLeave
{
    public function handle(LeaveApplication $leaveApplication, UpdateLeave $updateLeaveStatus): void
    {
        $user = $leaveApplication->user;
        $actor = auth()->user();
        $balanceColumn = match ($leaveApplication->leave_type) {
            'annual', 'personal' => 'leave_balance',
            'compensatory' => 'comp_off_balance',
            default => null,
        };
        $oldBalance = $user->$balanceColumn;

        $updateLeaveStatus->handle($leaveApplication, 'approved');

        $leaveLogger = app(\App\Services\LeaveLogger::class);

$leaveLogger->handle(
    $leaveApplication,
    'approved',
    'Request approved by '.$actor->name.'. '.$leaveApplication->leave_days.' day(s) deducted.',
    [
        'balance_type' => str_replace('_balance', '', $balanceColumn),
        'change_amount' => -$leaveApplication->leave_days,
        'old_balance' => $oldBalance,
        'new_balance' => $user->fresh()->$balanceColumn,
    ]
);

    }
}
