<?php

namespace App\Http\Requests\LeaveApplication;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Adjust authorization if needed, for now allow all
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string'],
            'mail_template_id' => ['required', 'string'],
        ];
    }
}
