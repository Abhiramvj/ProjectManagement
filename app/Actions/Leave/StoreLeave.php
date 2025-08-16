<?php

namespace App\Actions\Leave;

use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Notifications\LeaveRequestSubmitted;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StoreLeave
{
    public function handle(array $data): LeaveApplication
    {
        \Log::info('Received leave request data', $data);

        $user = Auth::user();

        // Validate leave_type presence
        $leaveType = $data['leave_type'] ?? null;
        if (! $leaveType) {
            throw ValidationException::withMessages([
                'leave_type' => ['Leave type is required.'],
            ]);
        }

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        $startSession = $data['start_half_session'] ?? null;
        $endSession = $data['end_half_session'] ?? null;

        // Normalize empty string sessions to null
        $startSession = $startSession === '' ? null : $startSession;
        $endSession = $endSession === '' ? null : $endSession;

        if ($start->gt($end)) {
            throw ValidationException::withMessages([
                'start_date' => ['Start date must be before or equal to the end date.'],
            ]);
        }

        // --- Leave days calculation ---
        $leaveDays = 0;
        $isSingleDay = $start->isSameDay($end);

        if ($isSingleDay) {
            // Check if this single day is weekend or holiday — if yes → leaveDays = 0
            $isWeekend = in_array($start->dayOfWeekIso, [6, 7]);
            $isHoliday = Holiday::whereDate('date', $start->toDateString())->exists();

            if ($isWeekend || $isHoliday) {
                $leaveDays = 0;
            } else {
                // Half-day / full-day check
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
                    $leaveDays = 1.0; // Fallback
                }
            }
        } else {
            // Multi-day leave — exclude weekends & holidays

            // First day value
            $firstDayValue = 0.0;
            if (! in_array($start->dayOfWeekIso, [6, 7]) &&
                ! Holiday::whereDate('date', $start->toDateString())->exists()) {
                $firstDayValue = ($startSession === 'afternoon') ? 0.5 : 1.0;
            }

            // Last day value
            $lastDayValue = 0.0;
            if (! in_array($end->dayOfWeekIso, [6, 7]) &&
                ! Holiday::whereDate('date', $end->toDateString())->exists()) {
                $lastDayValue = ($endSession === 'morning') ? 0.5 : 1.0;
            }

            // Days in between
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

        \Log::info('Calculated leave days', [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'start_half_session' => $startSession,
            'end_half_session' => $endSession,
            'leave_days' => $leaveDays,
        ]);

        // Validate leave balance except for exempt leave types
        if (! in_array($leaveType, ['emergency', 'sick', 'wfh', 'compensatory'])
            && $leaveDays > $user->getRemainingLeaveBalance()) {
            $remaining = $user->getRemainingLeaveBalance();
            throw ValidationException::withMessages([
                'leave_days' => ["You do not have enough leave balance. Remaining: {$remaining} days."],
            ]);
        }

        // Validate compensatory leave balance
        $compOffBalance = $user->comp_off_balance ?? 0;
        if ($leaveType === 'compensatory' && $leaveDays > $compOffBalance) {
            throw ValidationException::withMessages([
                'leave_days' => ['You do not have enough compensatory leave balance.'],
            ]);
        }

        // Handle supporting document upload
        $supportingDocumentPath = null;
        if (isset($data['supporting_document']) && $data['supporting_document'] instanceof UploadedFile) {
            $supportingDocumentPath = $data['supporting_document']->store(
                'leave_documents/'.$user->id,
                'public'
            );
        }

        \Log::info('Starting leave creation');

        $leaveApplication = LeaveApplication::create([
            'user_id' => $user->id,
            'start_date' => $start,
            'end_date' => $end,
            'start_half_session' => $startSession,
            'end_half_session' => $endSession,
            'reason' => $data['reason'],
            'leave_type' => $leaveType,
            'leave_days' => $leaveDays,
            'salary_deduction_days' => 0,
            'status' => 'pending',
            'supporting_document_path' => $supportingDocumentPath,
        ]);

        // Deduct from DB immediately
        if (in_array($leaveType, ['annual', 'casual'])) {
            $user->leave_balance = max(0, $user->leave_balance - $leaveDays);
        } elseif ($leaveType === 'compensatory') {
            $user->comp_off_balance = max(0, $user->comp_off_balance - $leaveDays);
        }
        $user->save();

        $this->sendNotifications($leaveApplication);

        \Log::info('Leave created', ['id' => $leaveApplication->id]);

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
