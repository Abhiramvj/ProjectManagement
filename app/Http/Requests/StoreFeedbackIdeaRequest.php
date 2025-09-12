<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['admin', 'hr']);
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:5000|min:10',
            'created_by' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Description is required.',
            'description.min' => 'Description must be at least 10 characters.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'created_by.required' => 'Created by field is required.',
            'created_by.max' => 'Created by field cannot exceed 255 characters.',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'type' => $this->route('type'), // Get type from route parameter
        ]);
    }
}
