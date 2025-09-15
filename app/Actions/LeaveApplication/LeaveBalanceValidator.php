<?php

namespace App\Actions\LeaveApplication;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class LeaveBalanceValidator
{
    public function validate(User $user, string $leaveType, float $leaveDays): void
    {
        if (! in_array($leaveType, ['emergency', 'sick', 'wfh', 'compensatory']) &&
            $leaveDays > $user->leave_balance) {
            throw ValidationException::withMessages([
                'leave_days' => ["User does not have enough leave balance. Remaining: {$user->leave_balance} days."],
            ]);
        }

        if ($leaveType === 'compensatory' && $leaveDays > ($user->comp_off_balance ?? 0)) {
            throw ValidationException::withMessages([
                'leave_days' => ['User does not have enough compensatory leave balance.'],
            ]);
        }
    }
}
