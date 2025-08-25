<?php

namespace App\Actions\Leave;

use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Notifications\LeaveRequestSubmitted;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StoreLeave
{
    public function handle(array $data): LeaveApplication
    {
        $authUser = Auth::user();

        // Determine target user (Admin/HR can specify, else use auth user)
        $targetUserId = $data['user_id'] ?? Auth::id();
        $targetUser = User::findOrFail($targetUserId);

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

        // Calculate leave days (existing logic unchanged)
        $leaveDays = 0;
        $isSingleDay = $start->isSameDay($end);

        if ($isSingleDay) {
            $isWeekend = in_array($start->dayOfWeekIso, [6, 7]);
            $isHoliday = Holiday::whereDate('date', $start->toDateString())->exists();

            if ($isWeekend || $isHoliday) {
                $leaveDays = 0;
            } else {
                $isFullDay = ($startSession === 'morning' && $endSession === 'afternoon');

                $halfDayCases = [
                    ['morning', null],
                    [null, 'afternoon'],
                    ['morning', 'morning'],
                    ['afternoon', 'afternoon'],
                    ['afternoon', null],
                ];

                $isHalfDay = false;
                foreach ($halfDayCases as $case) {
                    if ($startSession === $case[0] && $endSession === $case[1]) {
                        $isHalfDay = true;
                        break;
                    }
                }

                if ($isFullDay) {
                    $leaveDays = 1.0;
                } elseif ($isHalfDay) {
                    $leaveDays = 0.5;
                } else {
                    $leaveDays = 1.0;
                }
            }
        } else {
            $firstDayValue = 0.0;
            if (! in_array($start->dayOfWeekIso, [6, 7])
                && ! Holiday::whereDate('date', $start->toDateString())->exists()) {
                $firstDayValue = ($startSession === 'afternoon') ? 0.5 : 1.0;
            }

            $lastDayValue = 0.0;
            if (! in_array($end->dayOfWeekIso, [6, 7])
                && ! Holiday::whereDate('date', $end->toDateString())->exists()) {
                $lastDayValue = ($endSession === 'morning') ? 0.5 : 1.0;
            }

            $workingDaysInBetween = 0;
            $currentDay = $start->copy()->addDay();

            while ($currentDay->lt($end)) {
                $isWeekend = in_array($currentDay->dayOfWeekIso, [6, 7]);
                $isHoliday = Holiday::whereDate('date', $currentDay->toDateString())->exists();

                if (! $isWeekend && ! $isHoliday) {
                    $workingDaysInBetween++;
                }

                $currentDay->addDay();
            }

            $leaveDays = $firstDayValue + $lastDayValue + $workingDaysInBetween;
            $leaveDays = max(0, $leaveDays);
        }

        // Validate leave balance for target user except exempt leave types
        if (! in_array($data['leave_type'], ['emergency', 'sick', 'wfh', 'compensatory'])
            && $leaveDays > $targetUser->getRemainingLeaveBalance()) {
            $remaining = $targetUser->getRemainingLeaveBalance();
            throw ValidationException::withMessages([
                'leave_days' => ["User does not have enough leave balance. Remaining: {$remaining} days."],
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

        $this->sendNotifications($leaveApplication);

        return $leaveApplication;
    }

    private function sendNotifications(LeaveApplication $leaveApplication): void
    {
        try {
            $approvers = $leaveApplication->user->getLeaveApprovers();
            if ($approvers->count() > 0) {
                foreach ($approvers as $approver) {
                    $approver->notify(new LeaveRequestSubmitted($leaveApplication));
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send leave request notifications', [
                'error' => $e->getMessage(),
                'leave_id' => $leaveApplication->id,
            ]);
        }
    }
}
