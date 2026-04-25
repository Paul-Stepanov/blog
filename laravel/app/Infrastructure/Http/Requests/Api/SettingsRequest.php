<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Api;

use App\Domain\Settings\ValueObjects\SettingKey;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Settings Form Request.
 *
 * Validates site settings data.
 */
final class SettingsRequest extends FormRequest
{
    /**
     * Authorization - requires authentication for admin.
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
            'key' => ['required', 'string', 'regex:/^[a-z]+\.[a-z_]+$/', 'max:50'],
            'value' => ['required'],
            'type' => ['nullable', 'string', 'in:string,integer,boolean,json'],
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
            'key.required' => 'Ключ настройки обязателен.',
            'key.regex' => 'Ключ должен быть в формате "group.name" (например, "site.title").',
            'key.max' => 'Ключ не может быть длиннее 50 символов.',
            'value.required' => 'Значение обязательно.',
            'type.in' => 'Тип должен быть одним из: string, integer, boolean, json.',
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
            'key' => 'ключ настройки',
            'value' => 'значение',
            'type' => 'тип',
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

    /**
     * Validate setting key against known keys.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $key = $this->input('key');

            if ($key) {
                try {
                    $settingKey = SettingKey::fromString($key);
                    if (!$settingKey->isKnown()) {
                        $allKeys = SettingKey::getAllGrouped();
                        $flatKeys = array_merge(...array_values($allKeys));
                        $validator->errors()->add('key', 'Неизвестный ключ настройки. Допустимые ключи: ' . implode(', ', array_keys($flatKeys)));
                    }
                } catch (\Exception) {
                    $validator->errors()->add('key', 'Некорректный формат ключа настройки.');
                }
            }
        });
    }
}