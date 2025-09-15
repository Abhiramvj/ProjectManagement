<?php

namespace App\Http\Requests\LeaveApplication;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        // You can add policy/role checks if needed
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
