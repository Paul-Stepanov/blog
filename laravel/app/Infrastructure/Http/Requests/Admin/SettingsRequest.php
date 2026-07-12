<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Settings Batch Form Request.
 *
 * Validates a batch update of site settings (a key => value map).
 */
final class SettingsRequest extends FormRequest
{
    /**
     * Authorization — requires authentication (route is behind auth:sanctum).
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
            'settings' => ['required', 'array', 'min:1'],
            'settings.*' => ['required'],
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
            'settings.required' => 'Настройки обязательны.',
            'settings.array' => 'Настройки должны быть объектом key-value.',
            'settings.min' => 'Должна быть хотя бы одна настройка.',
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
