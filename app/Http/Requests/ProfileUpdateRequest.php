<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
  public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        'total_experience' => ['nullable', 'numeric', 'min:0', 'max:100'],
    ];
}

}
