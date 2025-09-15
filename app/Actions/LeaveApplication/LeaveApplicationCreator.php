<?php

namespace App\Actions\LeaveApplication;

use App\Models\LeaveApplication;
use App\Models\User;

class LeaveApplicationCreator
{
    public function create(User $targetUser, $authUser, array $data, float $leaveDays, ?string $documentPath): LeaveApplication
    {
        $leaveApplication = LeaveApplication::create([
            'user_id' => $targetUser->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_half_session' => $data['start_half_session'] ?? null,
            'end_half_session' => $data['end_half_session'] ?? null,
            'reason' => $data['reason'],
            'leave_type' => $data['leave_type'],
            'leave_days' => $leaveDays,
            'day_type' => $data['day_type'],
            'salary_deduction_days' => 0,
            'status' => 'pending',
            'supporting_document_path' => $documentPath,
        ]);

        // Deduct balance
        if (in_array($data['leave_type'], ['annual', 'personal'])) {
            $targetUser->leave_balance = max(0, $targetUser->leave_balance - $leaveDays);
        } elseif ($data['leave_type'] === 'compensatory') {
            $targetUser->comp_off_balance = max(0, $targetUser->comp_off_balance - $leaveDays);
        }
        $targetUser->save();

        return $leaveApplication;
    }
}
