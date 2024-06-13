<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize()
    {
        // Authorize the request; you can implement additional logic if needed
        return true;
    }

    public function rules()
    {
        return [
            'status' => 'required|in:pending,completed,canceled',
        ];
    }

    public function messages()
    {
        return [
            'status.in' => 'The status must be one of: pending, completed, canceled.',
        ];
    }
}
