<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize()
    {
        // Authorize the request; you can implement additional logic if needed
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'status' => 'required|in:pending,completed,canceled',
            'parent_id' => 'nullable|exists:tasks,id',
        ];
    }

    public function messages()
    {
        return [
            'status.in' => 'The status must be one of: pending, completed, canceled.',
            'parent_id.exists' => 'The specified parent task does not exist.',
        ];
    }
}
