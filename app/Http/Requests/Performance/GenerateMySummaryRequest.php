<?php

namespace App\Http\Requests\Performance;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMySummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow authenticated users
    }

    public function rules(): array
    {
        return [
            'taskStats' => 'required|array',
            'taskStats.completion_rate' => 'required|numeric|min:0',

            'timeStats' => 'required|array',
            'timeStats.current_month' => 'required|numeric|min:0',

            'leaveStats' => 'required|array',
            'leaveStats.current_year' => 'required|numeric|min:0',
            'leaveStats.balance' => 'required|numeric|min:0',

            'performanceScore' => 'required|numeric|min:0|max:100',
        ];
    }
}
