<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Tag Form Request.
 *
 * Validates tag creation and update data.
 */
final class TagRequest extends FormRequest
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
        $tagId = $this->route('id');

        return [
            'name' => ['required', 'string', 'min:1', 'max:50'],
            'slug' => ['nullable', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:50', 'unique:tags,slug,'.$tagId],
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
            'name.required' => 'Название тега обязательно.',
            'name.max' => 'Название не может быть длиннее 50 символов.',
            'slug.regex' => 'Slug может содержать только строчные буквы, цифры и дефисы.',
            'slug.max' => 'Slug не может быть длиннее 50 символов.',
            'slug.unique' => 'Тег с таким slug уже существует.',
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