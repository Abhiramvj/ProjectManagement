<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeedbackIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['admin', 'hr']);
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:5000|min:10',
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Description is required.',
            'description.min' => 'Description must be at least 10 characters.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'status.in' => 'Status must be either active or archived.',
        ];
    }
}
