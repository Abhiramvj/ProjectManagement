<?php

namespace App\Http\Requests\LeaveApplication;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCompOffRequest extends FormRequest
{
    public function authorize(): bool
    {

        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'comp_off_days' => ['required', 'numeric', 'min:0.5'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }
}
