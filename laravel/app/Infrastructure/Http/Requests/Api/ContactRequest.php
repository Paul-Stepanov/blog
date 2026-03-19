<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Contact Form Request.
 *
 * Validates contact form submissions with strict rules.
 */
final class ContactRequest extends FormRequest
{
    /**
     * Authorization - public endpoint.
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
        return [
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'email' => ['required', 'email', 'max:254'],
            'subject' => ['required', 'string', 'min:1', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
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
            'name.required' => 'Пожалуйста, укажите ваше имя.',
            'name.max' => 'Имя не может быть длиннее 100 символов.',
            'email.required' => 'Пожалуйста, укажите email.',
            'email.email' => 'Пожалуйста, укажите корректный email.',
            'email.max' => 'Email не может быть длиннее 254 символов.',
            'subject.required' => 'Пожалуйста, укажите тему сообщения.',
            'subject.max' => 'Тема не может быть длиннее 200 символов.',
            'message.required' => 'Пожалуйста, напишите сообщение.',
            'message.min' => 'Сообщение должно содержать минимум 10 символов.',
            'message.max' => 'Сообщение не может быть длиннее 5000 символов.',
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