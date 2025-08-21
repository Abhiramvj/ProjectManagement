<?php

namespace App\Http\Requests\Leave;

use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreLeaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // We assume authorization is handled by middleware or controller policies.
    }

    /**
     * Get the validation rules that apply to the request.
     * These are the basic, single-field rules.
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'leave_type' => ['required', 'string', 'in:annual,sick,personal,emergency,maternity,paternity,wfh,compensatory'],
            'day_type' => ['required', 'string', 'in:full,half'],
            'start_half_session' => ['nullable', 'required_if:day_type,half', 'string', 'in:morning,afternoon'],
            'end_half_session' => ['nullable', 'required_if:day_type,half', 'string', 'in:morning,afternoon'],
            'supporting_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // This closure ensures only admins/HR can submit requests for other users.
                    if ($value && !Auth::user()->hasAnyRole(['admin', 'hr'])) {
                        $fail('You are not authorized to apply leave for other employees.');
                    }
                },
            ],
        ];
    }

    /**
     * Configure the validator instance.
     * This is where we add all our complex, custom validation logic.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // If initial validation fails (e.g., bad date format), stop here.
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            
            $data = $this->validated();
            $user = $this->getTargetUser($data);

            if (!$user) {
                $validator->errors()->add('user_id', 'The selected employee could not be found.');
                return;
            }

            // --- VALIDATION 1: Advance Notice ---
            $this->validateAdvanceNotice($validator, $data);

            // --- VALIDATION 2: Overlapping Dates ---
            $this->validateNoOverlap($validator, $data, $user);

            // --- VALIDATION 3: Sufficient Balance ---
            $this->validateSufficientBalance($validator, $data, $user);
        });
    }

    /**
     * Helper to get the user who the leave is for (either the logged-in user or one selected by an admin).
     */
    private function getTargetUser(array $data): ?User
    {
        // If an admin/hr is applying for someone else
        if (Auth::user()->hasAnyRole(['admin', 'hr']) && !empty($data['user_id'])) {
            return User::find($data['user_id']);
        }
        // Otherwise, it's for the logged-in user
        return Auth::user();
    }

    /**
     * VALIDATION 1: Check if the leave request adheres to the advance notice policy.
     * Admins/HR can bypass this when applying for others.
     */
    private function validateAdvanceNotice(Validator $validator, array $data): void
    {
        // If the logged-in user is an admin/hr applying for someone else, bypass this rule.
        if (Auth::user()->hasAnyRole(['admin', 'hr']) && !empty($data['user_id'])) {
            return;
        }

        $startDate = Carbon::parse($data['start_date']);
        $noticeDays = Carbon::today()->diffInDays($startDate, false);

        if ($data['leave_type'] === 'annual' && $noticeDays < 7) {
            $validator->errors()->add('start_date', 'Annual leave must be requested at least 7 days in advance.');
        }

        if ($data['leave_type'] === 'personal' && $noticeDays < 3) {
            $validator->errors()->add('start_date', 'Personal leave must be requested at least 3 days in advance.');
        }
    }

    /**
     * VALIDATION 2: Check if the requested dates overlap with any existing leaves.
     */
    private function validateNoOverlap(Validator $validator, array $data, User $user): void
    {
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];

        $overlapExists = $user->leaveApplications()
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($startDate, $endDate) {
                // This is the standard, robust way to check for any date range overlap
                $query->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
            })
            ->exists();

        if ($overlapExists) {
            $validator->errors()->add('start_date', 'The selected dates overlap with another pending or approved leave request.');
        }
    }
    
    /**
     * VALIDATION 3: Check if the user has enough balance for the requested leave.
     */
    private function validateSufficientBalance(Validator $validator, array $data, User $user): void
    {
        // Only check balance for these leave types
        $balanceLeaveTypes = ['annual', 'sick', 'personal', 'compensatory'];
        if (!in_array($data['leave_type'], $balanceLeaveTypes)) {
            return;
        }

        $leaveDays = $this->calculateLeaveDays($data);

        // No need to check balance if the request is for 0 days (e.g., a weekend)
        if ($leaveDays <= 0) return;

        $balance = ($data['leave_type'] === 'compensatory') ? $user->comp_off_balance : $user->leave_balance;

        if ($balance < $leaveDays) {
            $validator->errors()->add(
                'end_date', // Attach error to the end_date field for better UI placement
                "Insufficient balance. This request requires {$leaveDays} days, but only {$balance} are available."
            );
        }
    }
    
    /**
     * Helper function to accurately calculate the number of working days in a request.
     * It accounts for weekends, public holidays, and half-day requests.
     */
    private function calculateLeaveDays(array $data): float
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        
        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])->pluck('date')->map(fn ($date) => $date->toDateString());

        $days = $startDate->diffInDaysFiltered(function (Carbon $date) use ($holidays) {
            // Count the day only if it's a weekday AND not a public holiday
            return $date->isWeekday() && !$holidays->contains($date->toDateString());
        }, $endDate);

        // If the request is for a half-day, multiply the result by 0.5
        if ($data['day_type'] === 'half') {
            return $days * 0.5;
        }

        return $days;
    }
}