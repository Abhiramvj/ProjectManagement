<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ImportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adjust if you want role-based permissions
    }

    public function rules(): array
    {
        return [
            'file' => 'required|mimes:csv,txt,xlsx',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please upload a file.',
            'file.mimes' => 'The file must be a CSV, TXT, or XLSX.',
        ];
    }
}
