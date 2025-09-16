<?php 
namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // you can add policy checks here if needed
    }

    public function rules(): array
    {
        return [
            'scores' => 'required|array',
            'scores.*' => 'integer|min:1|max:10',
            'comments' => 'nullable|array',
            'comments.*' => 'nullable|string',
        ];
    }
}
