<?php

namespace App\Http\Requests\Auth;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'employee_id' => ['required', 'string', 'max:100', 'unique:users,employee_id'],
            'division' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function employeeAttributes(): array
    {
        return [
            ...$this->safe()->except('password_confirmation'),
            'role' => Role::Employee,
        ];
    }
}
