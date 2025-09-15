<?php

namespace App\Actions\LeaveApplication;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StoreLeaveApplicationAction
{
    public function execute(array $data)
    {
        $authUser = Auth::user();
        $targetUser = User::findOrFail($data['user_id'] ?? Auth::id())->refresh();

        // Step 1: Calculate leave days
        $leaveDays = app(LeaveDayCalculator::class)
            ->calculate($data['start_date'], $data['end_date'], $data['start_half_session'] ?? null, $data['end_half_session'] ?? null);

        // Step 2: Validate balance
        app(LeaveBalanceValidator::class)->validate($targetUser, $data['leave_type'], $leaveDays);

        // Step 3: Upload document
        $documentPath = app(LeaveDocumentUploader::class)->upload($targetUser, $data['supporting_document'] ?? null);

        // Step 4: Create leave application
        $leaveApplication = app(LeaveApplicationCreator::class)->create($targetUser, $authUser, $data, $leaveDays, $documentPath);

        // Step 5: Log
        app(LogLeaveAction::class)->log($leaveApplication, $authUser);

        // Step 6: Notify
        app(LeaveNotifier::class)->notify($leaveApplication);

        return $leaveApplication;
    }
}
