<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * This ensures only logged-in users can proceed.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     * This will only run if authorize() returns true.
     */
    public function rules(): array
    {
        $user = $this->user(); // We know user is not null here.

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'end_date' => ['required', 'date', 'after_or_equal:today'],
            'total_hours_required' => ['required', 'integer', 'min:1'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
        ];

        if ($user->hasRole('admin')) {
            $rules['team_id'] = ['nullable', 'exists:teams,id'];
            $rules['project_manager_id'] = ['nullable', 'exists:users,id'];
        } else {
            $rules['team_id'] = ['required', 'exists:teams,id'];
            $rules['project_manager_id'] = ['nullable'];
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     * This is where the error was happening.
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            if ($user->hasRole('admin')) {
                if (empty($this->team_id) && empty($this->project_manager_id)) {
                    $validator->errors()->add(
                        'project_manager_id',
                        'You must assign the project to either a Team or a Project Manager.'
                    );
                }
            }
        });
    }
}
