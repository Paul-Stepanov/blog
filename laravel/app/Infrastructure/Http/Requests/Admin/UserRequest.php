<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Admin;

use App\Domain\User\ValueObjects\UserRole;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * User Form Request.
 *
 * Validates user creation and update data.
 */
final class UserRequest extends FormRequest
{
    /**
     * Authorization - requires authentication.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $userId = $this->route('id');
        $isCreate = $this->isMethod('POST');

        return [
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email,'.$userId],
            'password' => $isCreate ? ['required', 'string', 'min:8', 'max:72'] : ['nullable', 'string', 'min:8', 'max:72'],
            'role' => ['required', 'string', 'in:'.implode(',', UserRole::values())],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Имя пользователя обязательно.',
            'name.max' => 'Имя не может быть длиннее 255 символов.',
            'email.required' => 'Email обязателен.',
            'email.email' => 'Пожалуйста, укажите корректный email.',
            'email.max' => 'Email не может быть длиннее 254 символов.',
            'email.unique' => 'Пользователь с таким email уже существует.',
            'password.required' => 'Пароль обязателен.',
            'password.min' => 'Пароль должен содержать минимум 8 символов.',
            'password.max' => 'Пароль не может быть длиннее 72 символов.',
            'role.required' => 'Роль обязательна.',
            'role.in' => 'Роль должна быть одной из: '.implode(', ', UserRole::values()).'.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'имя',
            'email' => 'email',
            'password' => 'пароль',
            'role' => 'роль',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => 'validation_error',
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}