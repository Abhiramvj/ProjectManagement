<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use App\Models\User;
use App\Notifications\LeaveRequestSubmitted;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StoreLeave
{
    public function handle(array $data): LeaveApplication
    {
        $authUser = Auth::user();

        $targetUserId = $data['user_id'] ?? Auth::id();
        $targetUser = User::findOrFail($targetUserId);
        $targetUser->refresh();

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $startSession = $data['start_half_session'] ?? null;
        $endSession = $data['end_half_session'] ?? null;

        // Normalize empty strings to null
        $startSession = $startSession === '' ? null : $startSession;
        $endSession = $endSession === '' ? null : $endSession;

        if ($start->gt($end)) {
            throw ValidationException::withMessages([
                'start_date' => ['Start date must be before or equal to the end date.'],
            ]);
        }

        // leavedays calculation
        $leaveService = app(\App\Services\LeaveService::class);

        $leaveDays = $leaveService->calculateLeaveDays($data, $targetUser);

        // Validate leave balance for target user except exempt leave types
        // Replace in StoreLeave class, handle() method before throwing ValidationException
        if (! in_array($data['leave_type'], ['emergency', 'sick', 'wfh', 'compensatory']) &&
            $leaveDays > $targetUser->leave_balance) {
            throw ValidationException::withMessages([
                'leave_days' => ["User does not have enough leave balance. Remaining: {$targetUser->leave_balance} days."],
            ]);
        }

        if ($data['leave_type'] === 'compensatory' && $leaveDays > ($targetUser->comp_off_balance ?? 0)) {
            throw ValidationException::withMessages([
                'leave_days' => ['User does not have enough compensatory leave balance.'],
            ]);
        }

        $supportingDocumentPath = null;
        if (isset($data['supporting_document']) && $data['supporting_document'] instanceof UploadedFile) {
            $supportingDocumentPath = $data['supporting_document']->store(
                'leave_documents/'.$targetUser->id,
                'public'
            );
        }

        // Create the leave application for target user
        $leaveApplication = LeaveApplication::create([
            'user_id' => $targetUser->id,
            'start_date' => $start,
            'end_date' => $end,
            'start_half_session' => $startSession,
            'end_half_session' => $endSession,
            'reason' => $data['reason'],
            'leave_type' => $data['leave_type'],
            'leave_days' => $leaveDays,
            'salary_deduction_days' => 0,
            'status' => 'pending',
            'supporting_document_path' => $supportingDocumentPath,
        ]);

        // Deduct leave balance from target user
        if (in_array($data['leave_type'], ['annual', 'personal'])) {
            $targetUser->leave_balance = max(0, $targetUser->leave_balance - $leaveDays);
        } elseif ($data['leave_type'] === 'compensatory') {
            $targetUser->comp_off_balance = max(0, $targetUser->comp_off_balance - $leaveDays);
        }
        $targetUser->save();

        Log::info("After deduction: user={$targetUser->id}, leave_balance={$targetUser->leave_balance}");

        $this->sendNotifications($leaveApplication);

        return $leaveApplication;
    }

    private function sendNotifications(LeaveApplication $leaveApplication): void
    {
        try {
            $applicantId = $leaveApplication->user_id;

            $approvers = $leaveApplication->user->getLeaveApprovers()->unique('id');

            foreach ($approvers as $approver) {
                if ($approver->id === $applicantId) {
                    continue; // skip notifying self
                }
                $approver->notify(new LeaveRequestSubmitted($leaveApplication));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send leave request notifications', [
                'error' => $e->getMessage(),
                'leave_id' => $leaveApplication->id,
            ]);
        }
    }
}
