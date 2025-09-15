<?php

namespace App\Http\Requests\LeaveApplication;

use Illuminate\Foundation\Http\FormRequest;

class UploadLeaveDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only the owner of the leave can upload
        return $this->leave_application->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'supporting_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'supporting_document.required' => 'Please upload a document.',
            'supporting_document.mimes' => 'Only PDF, JPG, JPEG, or PNG files are allowed.',
            'supporting_document.max' => 'The document may not be larger than 5MB.',
        ];
    }
}
