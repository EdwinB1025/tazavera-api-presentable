<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => "The user's email address.",
                'example'     => 'laura@tazavera.test',
            ],
            'password' => [
                'description' => "The user's password.",
                'example'     => 'secret123',
            ],
        ];
    }
}
