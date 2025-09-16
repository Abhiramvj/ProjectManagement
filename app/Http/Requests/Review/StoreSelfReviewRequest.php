<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreSelfReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Add authorization logic if needed (e.g. check if user can review)
        return true;
    }

    public function rules(): array
    {
        return [
            'scores' => 'required|array',
            'scores.*' => 'integer|min:1|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'scores.required' => 'You must provide review scores.',
            'scores.*.integer' => 'Each score must be a number.',
            'scores.*.min' => 'Each score must be at least 1.',
            'scores.*.max' => 'Each score may not be greater than 10.',
        ];
    }
}
