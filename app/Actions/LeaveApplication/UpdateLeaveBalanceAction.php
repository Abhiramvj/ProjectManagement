<?php

namespace App\Actions\LeaveApplication;

use App\Models\LeaveApplication;

class UpdateLeaveBalanceAction
{
    public function handle(LeaveApplication $leaveApplication, string $status): void
    {
        $user = $leaveApplication->user;

        // Sick leave does not reduce any balance
        if ($leaveApplication->leave_type === 'sick') {
            return;
        }

        $balanceType = null;

        if ($status === 'approved') {
            $balanceType = in_array($leaveApplication->leave_type, ['compensatory'])
                ? 'comp_off_balance'
                : 'leave_balance';

            // Ensure balance does not go negative
            $user->$balanceType = max(0, $user->$balanceType - $leaveApplication->leave_days);

        } elseif ($status === 'rejected') {
            if (in_array($leaveApplication->leave_type, ['annual', 'personal'])) {
                $balanceType = 'leave_balance';
                $user->increment('leave_balance', $leaveApplication->leave_days);
            } elseif ($leaveApplication->leave_type === 'compensatory') {
                $balanceType = 'comp_off_balance';
                $user->increment('comp_off_balance', $leaveApplication->leave_days);
            }
        }

        $user->save();
    }
}
