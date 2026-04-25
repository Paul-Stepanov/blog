<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Create Article Form Request.
 *
 * Validates article creation data.
 */
final class CreateArticleRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:1', 'max:255'],
            'content' => ['required', 'string', 'min:1'],
            'slug' => ['nullable', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'author_id' => ['nullable', 'uuid', 'exists:users,id'],
            'cover_image_id' => ['nullable', 'uuid', 'exists:media_files,id'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['uuid', 'exists:tags,id'],
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
            'title.required' => 'Заголовок обязателен.',
            'title.max' => 'Заголовок не может быть длиннее 255 символов.',
            'content.required' => 'Содержание обязательно.',
            'slug.regex' => 'Slug может содержать только строчные буквы, цифры и дефисы.',
            'slug.max' => 'Slug не может быть длиннее 255 символов.',
            'excerpt.max' => 'Отрывок не может быть длиннее 500 символов.',
            'category_id.uuid' => 'ID категории должен быть валидным UUID.',
            'category_id.exists' => 'Категория не найдена.',
            'author_id.uuid' => 'ID автора должен быть валидным UUID.',
            'author_id.exists' => 'Автор не найден.',
            'cover_image_id.uuid' => 'ID изображения должен быть валидным UUID.',
            'cover_image_id.exists' => 'Изображение не найдено.',
            'tags.array' => 'Теги должны быть массивом.',
            'tags.max' => 'Максимум 10 тегов.',
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