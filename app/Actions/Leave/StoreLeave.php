<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use App\Notifications\LeaveRequestSubmitted;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile; // Import UploadedFile class
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Import Log facade
use Illuminate\Support\Facades\Storage; // Import Storage facade
use Illuminate\Validation\ValidationException;

class StoreLeave
{
    public function __construct(
        private LeaveService $leaveService
    ) {}

    /**
     * Handles the creation of a new leave application.
     *
     * @param array $data The validated data from the request.
     * @param UploadedFile|null $document The uploaded document file.
     * @return LeaveApplication
     * @throws ValidationException
     */
    public function handle(array $data, ?UploadedFile $document = null): LeaveApplication
    {
        $user = Auth::user();
        info($data);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        // Determine day type based on session fields if not explicitly provided
        $dayType = $data['day_type'] ?? 'full';
        if (!isset($data['day_type']) && (isset($data['start_half_session']) || isset($data['end_half_session']))) {
            $dayType = 'half';
        }

        // --- Basic Date Validations ---
        if ($start->gt($end)) {
            throw ValidationException::withMessages([
                'start_date' => ['Start date must be before or equal to the end date.'],
            ]);
        }

        if ($start->isPast() && !$start->isToday()) {
            throw ValidationException::withMessages([
                'start_date' => ['Cannot apply for leave in the past.'],
            ]);
        }

        // --- Leave Days Calculation ---
        if ($dayType === 'half') {
            // Validate that the required session fields are present
            if (empty($data['start_half_session'])) {
                throw ValidationException::withMessages(['start_half_session' => 'The start session is required for a half-day leave.']);
            }
            if ($start->ne($end) && empty($data['end_half_session'])) {
                throw ValidationException::withMessages(['end_half_session' => 'The end session is required for a multi-day half-day leave.']);
            }
        }

        $requestedDays = $this->leaveService->calculateLeaveDays($start, $end, $dayType, $data);

        // Debug information
        Log::info('Leave calculation debug', [
            'day_type' => $dayType,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'start_half_session' => $data['start_half_session'] ?? null,
            'end_half_session' => $data['end_half_session'] ?? null,
            'calculated_days' => $requestedDays
        ]);

        // Ensure the leave period is valid
        if ($requestedDays <= 0) {
             throw ValidationException::withMessages(['end_half_session' => 'The selected leave period results in zero or fewer leave days. Please check your start and end sessions.']);
        }


        // --- Leave Balance and Deduction Logic ---
        $leaveType = $data['leave_type'] ?? 'annual';

        // Get leave statistics to avoid multiple queries
        $leaveStats = $user->getLeaveStatistics();
        $remainingBalance = $leaveStats['remaining_balance'];
        $leaveToDeduct = 0;

        switch ($leaveType) {
            case 'annual':
                if ($remainingBalance < $requestedDays) {
                    throw ValidationException::withMessages([
                        'start_date' => ["Insufficient annual leave balance. You have {$remainingBalance} days remaining, but requested {$requestedDays} days."],
                    ]);
                }
                $leaveToDeduct = $requestedDays;
                break;

            case 'sick':
                // Assuming sick leave doesn't deduct from annual balance
                $leaveToDeduct = 0;
                break;

            case 'personal':
                if ($remainingBalance >= $requestedDays) {
                    $leaveToDeduct = $requestedDays;
                } else {
                    $leaveToDeduct = $remainingBalance;
                    // Note: Remaining days would be unpaid (not tracked in database)
                }
                break;

            case 'maternity':
                $maxMaternityDays = 6 * 30; // approx 6 months
                if ($requestedDays > $maxMaternityDays) {
                    throw ValidationException::withMessages(['start_date' => ['Maternity leave cannot exceed 6 months.']]);
                }
                $leaveToDeduct = 0;
                break;

            case 'paternity':
                $maxPaternityDays = 2 * 30; // approx 2 months
                if ($requestedDays > $maxPaternityDays) {
                    throw ValidationException::withMessages(['start_date' => ['Paternity leave cannot exceed 2 months.']]);
                }
                $leaveToDeduct = 0;
                break;

            case 'emergency':
                if ($remainingBalance >= $requestedDays) {
                    $leaveToDeduct = $requestedDays;
                } else {
                    $leaveToDeduct = $remainingBalance;
                }
                break;

            default:
                throw ValidationException::withMessages(['leave_type' => ['Invalid leave type selected.']]);
        }

        // --- Overlapping Leave Check ---
        $overlapping = $this->leaveService->hasOverlappingLeave($user, $start, $end);

        if ($overlapping) {
            throw ValidationException::withMessages([
                'start_date' => ['You already have an approved or pending leave application that overlaps with these dates.'],
            ]);
        }
        
        // --- NEW: Handle Document Upload ---
        $documentPath = null;
        if ($document && $leaveType === 'sick') {
            // Store the file in 'storage/app/public/leave_documents'
            $documentPath = $document->store('leave_documents', 'public');
        }

        // --- Create Leave Application in Database ---
        $leaveApplication = LeaveApplication::create([
            'user_id' => $user->id,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => $data['reason'],
            'leave_type' => $leaveType,
            'day_type' => $dayType,
            'start_half_session' => $dayType === 'half' ? $data['start_half_session'] : null,
            'end_half_session' => ($dayType === 'half' && $start->ne($end)) ? $data['end_half_session'] : ($dayType === 'half' ? $data['start_half_session'] : null),
            'leave_days' => $requestedDays,
            'status' => 'pending',
            'document_path' => $documentPath, // Save the path
        ]);

        // Clear user's leave cache since data has changed
        $this->leaveService->clearUserLeaveCache($user);

        $this->sendNotifications($leaveApplication);

        return $leaveApplication;
    }

    /**
     * Sends notifications to the designated leave approvers.
     *
     * @param LeaveApplication $leaveApplication
     */
    private function sendNotifications(LeaveApplication $leaveApplication): void
    {
        try {
            $approvers = $leaveApplication->user->getLeaveApprovers();

            if ($approvers->count() > 0) {
                foreach ($approvers as $approver) {
                    $approver->notify(new LeaveRequestSubmitted($leaveApplication));
                }

                Log::info('Leave request notifications sent', [
                    'leave_id' => $leaveApplication->id,
                    'approvers_count' => $approvers->count(),
                ]);
            } else {
                Log::warning('No approvers found for leave request', [
                    'leave_id' => $leaveApplication->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send leave request notifications', [
                'error' => $e->getMessage(),
                'leave_id' => $leaveApplication->id,
            ]);
        }
    }
}