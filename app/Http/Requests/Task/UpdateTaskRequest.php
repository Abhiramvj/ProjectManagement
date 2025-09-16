<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Add authorization logic if needed, e.g.:
        // return auth()->user()->can('update', $this->task);
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'assigned_to_id' => 'sometimes|required|exists:users,id',
            'status' => 'sometimes|required|in:todo,in_progress,completed',
            'due_date' => 'sometimes|required|date',
        ];
    }
}
