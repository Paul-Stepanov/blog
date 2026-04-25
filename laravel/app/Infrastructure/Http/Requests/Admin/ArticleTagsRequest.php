<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Article Tags Sync Form Request.
 *
 * Validates tag synchronization for articles.
 */
final class ArticleTagsRequest extends FormRequest
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
        return [
            'tags' => ['required', 'array', 'max:10'],
            'tags.*' => ['required', 'uuid', 'exists:tags,id'],
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
            'tags.required' => 'Теги обязательны.',
            'tags.array' => 'Теги должны быть массивом.',
            'tags.max' => 'Максимум 10 тегов.',
            'tags.*.required' => 'ID тега обязателен.',
            'tags.*.uuid' => 'ID тега должен быть валидным UUID.',
            'tags.*.exists' => 'Тег не найден.',
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