<?php

namespace App\Http\Requests\LeaveApplication;

use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
                'nullable', 'integer', 'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value && ! Auth::user()->hasAnyRole(['admin', 'hr'])) {
                        $fail('You are not authorized to apply leave for other employees.');
                    }
                },
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $data = $this->validated();
            $user = $this->getTargetUser($data);

            if (! $user) {
                $validator->errors()->add('user_id', 'The selected employee could not be found.');

                return;
            }

            $this->validateAdvanceNotice($validator, $data);
            $this->validateNoOverlap($validator, $data, $user);
            $this->validateSufficientBalance($validator, $data, $user);
        });
    }

    private function getTargetUser(array $data): ?User
    {
        if (Auth::user()->hasAnyRole(['admin', 'hr']) && ! empty($data['user_id'])) {
            return User::find($data['user_id']);
        }

        return Auth::user();
    }

    private function validateAdvanceNotice(Validator $validator, array $data): void
    {
        if (Auth::user()->hasAnyRole(['admin', 'hr']) && ! empty($data['user_id'])) {
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

    private function validateNoOverlap(Validator $validator, array $data, User $user): void
    {
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $dayType = $data['day_type'] ?? 'full';
        $startHalf = $data['start_half_session'] ?? null;
        $endHalf = $data['end_half_session'] ?? null;

        $overlapExists = $user->leaveApplications()
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($startDate, $endDate, $dayType, $startHalf) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    // Check date overlap
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                });

                if ($dayType === 'half') {
                    $query->where(function ($q) use ($startHalf) {
                        // Only block if same half session on the same date
                        $q->where('day_type', 'half')
                            ->where(function ($q2) use ($startHalf) {
                                $q2->where('start_half_session', $startHalf)
                                    ->orWhere('end_half_session', $startHalf);
                            });
                    });
                }
            })
            ->exists();

        if ($overlapExists) {
            $validator->errors()->add('start_date', 'The selected dates or sessions overlap with another request.');
        }
    }

    private function validateSufficientBalance(Validator $validator, array $data, \App\Models\User $user): void
    {
        $balanceLeaveTypes = ['annual', 'personal', 'compensatory'];
        if (! in_array($data['leave_type'], $balanceLeaveTypes)) {
            return;
        }

        $leaveDays = $this->calculateLeaveDays($data);
        $balance = ($data['leave_type'] === 'compensatory') ? $user->comp_off_balance : $user->leave_balance;

        // Debug log statement for troubleshooting
        Log::info("Validation check: user={$user->id}, leave_type={$data['leave_type']}, leave_balance={$user->leave_balance}, calculated_leaveDays={$leaveDays}");

        if ($leaveDays <= 0) {
            return;
        }

        if ($balance < $leaveDays) {
            $validator->errors()->add(
                'leave_balance',
                "Insufficient balance. Request requires {$leaveDays} days, only {$balance} available."
            );
        }

    }

    public function calculateLeaveDays(array $data): float
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])->pluck('date')->map(fn ($date) => $date->toDateString());

        $days = 0;
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->isWeekday() && ! $holidays->contains($date->toDateString())) {
                $days++;
            }
        }

        if ($data['day_type'] === 'half') {
            return $days * 0.5;
        }

        return (float) $days;
    }
}
