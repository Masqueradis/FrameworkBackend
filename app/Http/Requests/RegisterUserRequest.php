<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\RegisterUserDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function toDTO(): RegisterUserDTO
    {
        return new RegisterUserDTO(
            name: $this->validated('name'),
            email: $this->validated('email'),
            password: $this->validated('password')
        );
    }
}
