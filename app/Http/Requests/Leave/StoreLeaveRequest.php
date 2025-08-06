<?php

namespace App\Http\Requests\Leave;

use App\Models\LeaveApplication;
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
        return [
            'start_date' => ['required', 'date', 'after_or_equal:today', function ($attribute, $value, $fail) {
                $leaveType = $this->input('leave_type');
                $startDate = Carbon::parse($value);
                $today = Carbon::today();

                // Adjust the leave types and advance days as per your business logic
                if ($leaveType === 'annual') {
                    if ($startDate->lt($today->copy()->addDays(7))) {
                        $fail('Annual leave must be applied at least 7 days in advance.');
                    }
                } elseif ($leaveType === 'personal') {
                    if ($startDate->lt($today->copy()->addDays(3))) {
                        $fail('Personal leave must be applied at least 3 days in advance.');
                    }
                }
                // Add other leave type advance checks here if needed
            }],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:10'],
            'leave_type' => ['required', 'string', 'in:annual,sick,personal,emergency,maternity,paternity,wfh,compensatory'],
            // New session fields instead of old day_type and half_session
            'start_half_session' => ['nullable', 'in:morning,afternoon'],
            'end_half_session' => ['nullable', 'in:morning,afternoon'],
            'supporting_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
{
    $validator->after(function ($validator) {
        $userId = $this->user()->id;
        $start = $this->input('start_date');
        $end = $this->input('end_date');

        // Only check overlap if both dates exist and are not empty
        if (!empty($start) && !empty($end)) {
            $hasOverlap = LeaveApplication::where('user_id', $userId)
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
