<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use App\Services\LeaveService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class UpdateLeave
{
    public function __construct(
        private LeaveService $leaveService
    ) {}

    public function handle(LeaveApplication $leaveApplication, string $status, ?string $rejectReason = null): void
    {
        $updateData = ['status' => $status];

        if (Schema::hasColumn('leave_applications', 'approved_by')) {
            $updateData['approved_by'] = Auth::id();
        }

        if (Schema::hasColumn('leave_applications', 'approved_at')) {
            $updateData['approved_at'] = now();
        }

        // Save reject_reason if rejecting
        if ($status === 'rejected') {
            $updateData['rejection_reason'] = $rejectReason;
        } else {
            // Optionally clear reject_reason if status changes to something else
            $updateData['rejection_reason'] = null;
        }

        $leaveApplication->update($updateData);

        $this->leaveService->clearUserLeaveCache($leaveApplication->user);

    }
}
