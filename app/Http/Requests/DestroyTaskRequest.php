<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyTaskRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'task_id' => 'required|exists:tasks,id',
        ];
    }

    public function messages()
    {
        return [
            'task_id.exists' => 'The specified task does not exist.',
        ];
    }
}
