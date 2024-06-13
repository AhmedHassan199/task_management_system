<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddDependenciesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'assigned_tasks' => 'required|array',
            'assigned_tasks.*' => 'exists:tasks,id',
        ];
    }

    public function messages()
    {
        return [
            'assigned_tasks.*.exists' => 'One or more assigned tasks do not exist.',
        ];
    }
}
