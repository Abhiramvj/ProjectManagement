<?php

namespace App\Actions\LeaveApplication;

use App\Models\LeaveApplication;
use App\Models\LeaveLog;

class LogLeaveAction
{
    /**
     * Log a leave action.
     *
     * @param  mixed  $authUser
     * @param  string  $action  Action type: approved, rejected, created
     */
    public function log(LeaveApplication $leaveApplication, $authUser, string $action = 'created', ?string $rejectReason = null): void
    {
        $description = '';
        $details = [
            'leave_type' => $leaveApplication->leave_type,
            'start_date' => $leaveApplication->start_date->toDateString(),
            'end_date' => $leaveApplication->end_date->toDateString(),
        ];

        // Sick or emergency leaves: simple approved/rejected
        if (in_array($leaveApplication->leave_type, ['sick', 'emergency'])) {
            if ($action === 'created') {
                $description = 'Submitted a new leave request.';
            } else {
                $description = $action === 'approved'
                    ? "Leave approved by {$authUser->name}."
                    : "Leave rejected by {$authUser->name}.".($rejectReason ? " Reason: {$rejectReason}" : '');
            }
        } else {
            // Other leave types: include leave days and balance
            if ($action === 'created') {
                $description = "Submitted a new leave request for {$leaveApplication->leave_days} day(s).";
            } elseif ($action === 'approved') {
                $description = "Request approved by {$authUser->name}. {$leaveApplication->leave_days} day(s) deducted.";
                $details['change_amount'] = -$leaveApplication->leave_days;
                $details['old_balance'] = $leaveApplication->user->leave_balance;
                $details['new_balance'] = $leaveApplication->user->leave_balance - $leaveApplication->leave_days;
            } elseif ($action === 'rejected') {
                $description = "Request rejected by {$authUser->name}. Balance restored.";
                $details['change_amount'] = $leaveApplication->leave_days;
                $details['old_balance'] = $leaveApplication->user->leave_balance;
                $details['new_balance'] = $leaveApplication->user->leave_balance + $leaveApplication->leave_days;
                if ($rejectReason) {
                    $details['rejection_reason'] = $rejectReason;
                }
            }
        }

        LeaveLog::create([
            'user_id' => $leaveApplication->user_id,
            'actor_id' => $authUser->id,
            'leave_application_id' => $leaveApplication->id,
            'action' => $action,
            'description' => $description,
            'details' => $details,
        ]);
    }
}
