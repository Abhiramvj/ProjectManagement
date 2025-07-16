<?php

namespace App\Http\Requests\Leave;

use App\Models\LeaveApplication;
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
        'start_date' => ['required', 'date', 'after_or_equal:today', function($attribute, $value, $fail) {
            $leaveType = $this->input('leave_type');
            $startDate = \Carbon\Carbon::parse($value);
            $today = \Carbon\Carbon::today();

            if ($leaveType === 'casual') {
                if ($startDate->lt($today->addDays(7))) {
                    $fail('Casual leave must be applied at least 7 days in advance.');
                }
            } elseif ($leaveType === 'paid') {
                if ($startDate->lt($today->addDays(3))) {
                    $fail('Paid leave must be applied at least 3 days in advance.');
                }
            }
            // Sick leave has no restriction here
        }],
        'end_date' => 'required|date|after_or_equal:start_date',
        'reason' => 'required|string|min:10',
        'leave_type' => ['required', 'in:casual,sick,paid'],
    ];
}


    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $userId = $this->user()->id;
            $start = $this->input('start_date');
            $end = $this->input('end_date');

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
        });
    }
}
