<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Or your specific authorization logic
        return true; // Or your specific authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Get the user ID from the route to ignore their own email address during unique check
        // Get the user ID from the route to ignore their own email address during unique check
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'team_id' => ['nullable', 'integer', Rule::exists('teams', 'id')],
            'parent_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],


            'work_mode' => [
                'nullable', // Makes the field optional.
                'string',   // It must be a string.
                Rule::in(['On-site', 'WFH']),
            ],
            // You can also add 'designation' if it's part of your form
            'designation' => ['nullable', 'string', 'max:255'],
        ];
    }
}
