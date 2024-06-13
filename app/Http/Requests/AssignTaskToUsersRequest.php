<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTaskToUsersRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'users' => 'required|array',
            'users.*' => 'integer|exists:users,id',
        ];
    }

    public function messages()
    {
        return [
            'users.*.exists' => 'One or more users do not exist.',
        ];
    }
}
