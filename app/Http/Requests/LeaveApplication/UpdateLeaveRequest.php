<?php

namespace App\Http\Requests\LeaveApplication;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string|max:500',
        ];
    }
}
