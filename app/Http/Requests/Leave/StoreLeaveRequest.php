<?php

namespace App\Http\Requests\Leave;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'user_id' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($user) {
                    // Only admin or hr can apply leave for other employees
                    if ($value && ! $user->hasAnyRole(['admin', 'hr'])) {
                        $fail('You are not authorized to apply leave for other employees.');
                    }
                },
            ],
            'start_date' => [
                'required',
                'date',
                $user && ! $user->hasAnyRole(['admin', 'hr'])
                    ? 'after_or_equal:today'
                    : 'nullable',
                function ($attribute, $value, $fail) {
                    $leaveType = $this->input('leave_type');
                    try {
                        $startDate = Carbon::parse($value);
                    } catch (\Exception) {
                        return; // Let Laravel's date validation handle invalid dates
                    }
                    $today = Carbon::today();

                    if ($leaveType === 'annual' && $startDate->lt($today->copy()->addDays(7))) {
                        $fail('Annual leave must be applied at least 7 days in advance.');
                    } elseif ($leaveType === 'personal' && $startDate->lt($today->copy()->addDays(3))) {
                        $fail('Personal leave must be applied at least 3 days in advance.');
                    }
                },
            ],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:10'],
            'leave_type' => ['required', 'string', 'in:annual,sick,personal,emergency,maternity,paternity,wfh,compensatory'],
            'start_half_session' => ['nullable', 'in:morning,afternoon'],
            'end_half_session' => ['nullable', 'in:morning,afternoon'],
            'supporting_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $userId = $this->input('user_id') ?? $this->user()->id;
            $start = $this->input('start_date');
            $end = $this->input('end_date');

            if (! empty($start) && ! empty($end)) {
                $hasOverlap = \App\Models\LeaveApplication::where('user_id', $userId)
                    ->where(function ($query) use ($start, $end) {
                        $query->whereBetween('start_date', [$start, $end])
                            ->orWhereBetween('end_date', [$start, $end])
                            ->orWhere(function ($query) use ($start, $end) {
                                $query->where('start_date', '<=', $start)
                                    ->where('end_date', '>=', $end);
                            });
                    })
                    ->whereIn('status', ['pending', 'approved'])
                    ->exists();

                if ($hasOverlap) {
                    $validator->errors()->add('start_date', 'These dates overlap with an existing leave request.');
                }
            }
        });
    }
}
