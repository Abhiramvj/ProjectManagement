<?php

namespace App\Actions\LeaveApplication;

use App\Models\LeaveLog;
use App\Models\User;

class ApproveCompOffAction
{
    public function execute(User $user, float $daysToCredit, string $reason, ?User $actor = null): User
    {
        $actor = $actor ?? auth()->user();
        $oldBalance = $user->comp_off_balance;

        // Increment the user's comp-off balance
        $user->increment('comp_off_balance', $daysToCredit);

        // Log the action
        LeaveLog::create([
            'user_id' => $user->id,
            'actor_id' => $actor->id,
            'action' => 'comp_off_credited',
            'description' => $actor->name.' credited '.$daysToCredit.' comp-off day(s). Reason: '.$reason,
            'details' => [
                'balance_type' => 'compensatory',
                'change_amount' => $daysToCredit,
                'old_balance' => $oldBalance,
                'new_balance' => $user->fresh()->comp_off_balance,
                'reason' => $reason,
            ],
        ]);

        return $user->fresh();
    }
}
