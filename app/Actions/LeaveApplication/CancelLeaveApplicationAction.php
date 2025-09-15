<?php

namespace App\Actions\LeaveApplication;

use App\Models\LeaveApplication;
use App\Models\LeaveLog;
use Illuminate\Support\Facades\Auth;

class CancelLeaveApplicationAction
{
    public function handle(LeaveApplication $leaveApplication): void
    {
        if ($leaveApplication->user_id !== Auth::id() || $leaveApplication->status !== 'pending') {
            abort(403, 'Unauthorized action.');
        }

        $user = $leaveApplication->user;

        if (in_array($leaveApplication->leave_type, ['annual', 'personal'])) {
            $user->increment('leave_balance', $leaveApplication->leave_days);
        } elseif ($leaveApplication->leave_type === 'compensatory') {
            $user->increment('comp_off_balance', $leaveApplication->leave_days);
        }

        LeaveLog::create([
            'user_id' => $leaveApplication->user_id,
            'actor_id' => Auth::id(),
            'leave_application_id' => $leaveApplication->id,
            'action' => 'cancelled',
            'description' => 'User cancelled their pending leave request.',
        ]);

        $leaveApplication->delete();
    }
}
