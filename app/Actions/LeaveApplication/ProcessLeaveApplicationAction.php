<?php

namespace App\Actions\LeaveApplication;

use App\Models\LeaveApplication;

class ProcessLeaveApplicationAction
{
    public function __construct(
        private UpdateLeaveApplicationAction $updateLeaveApplication,
        private UpdateLeaveBalanceAction $updateLeaveBalance,
        private LogLeaveAction $logLeave,
        private SendEmailAction $sendEmailAction,
    ) {}

    public function handle(LeaveApplication $leaveApplication, string $status, ?string $rejectReason = null): void
    {

        $this->updateLeaveApplication->handle($leaveApplication, $status, $rejectReason);

        $this->updateLeaveBalance->handle($leaveApplication, $status);

        $this->logLeave->log($leaveApplication, auth()->user(), $status, $rejectReason);

        $this->sendEmailAction->handle($leaveApplication, $status);

    }
}
