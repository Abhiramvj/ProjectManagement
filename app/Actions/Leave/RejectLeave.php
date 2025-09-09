<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;

class RejectLeave
{
    protected $leaveLogger;

    public function __construct()
    {
        $this->leaveLogger = app(\App\Services\LeaveLogger::class);
    }

    /**
     * Handle leave rejection.
     */
    public function handle(LeaveApplication $leaveApplication, UpdateLeave $updateLeaveStatus, string $rejectReason): void
    {
        $user = $leaveApplication->user;
        $actor = auth()->user();

        // Restore balance if applicable
        $balanceColumn = match ($leaveApplication->leave_type) {
            'annual', 'personal' => 'leave_balance',
            'compensatory' => 'comp_off_balance',
            default => null,
        };

        if ($balanceColumn) {
            $user->increment($balanceColumn, $leaveApplication->leave_days);
        }

        $oldBalance = $balanceColumn ? $user->$balanceColumn : 0;
        $user->save();

        // Update leave status
        $updateLeaveStatus->handle($leaveApplication, 'rejected', $rejectReason);

        // Log rejection
        $this->leaveLogger->handle(
            $leaveApplication,
            'rejected',
            'Request rejected by '.$actor->name.'. Balance restored.',
            [
                'rejection_reason' => $rejectReason,
                'balance_type' => $balanceColumn ? str_replace('_balance', '', $balanceColumn) : null,
                'change_amount' => $leaveApplication->leave_days,
                'old_balance' => $oldBalance,
                'new_balance' => $balanceColumn ? $user->fresh()->$balanceColumn : null,
            ]
        );
    }
}
