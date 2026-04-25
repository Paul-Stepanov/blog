<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Media File Form Request.
 *
 * Validates media file upload and update data.
 */
final class MediaRequest extends FormRequest
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
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'file' => $isUpdate ? ['nullable'] : ['required', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'file_name' => ['nullable', 'string', 'max:255'],
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
            'file.required' => 'Файл обязателен.',
            'file.file' => 'Загруженный файл должен быть валидным файлом.',
            'file.max' => 'Размер файла не может превышать 10MB.',
            'file.mimes' => 'Формат файла не поддерживается. Разрешены: jpeg, jpg, png, gif, webp, svg, pdf, doc, docx, xls, xlsx.',
            'alt_text.max' => 'Alt текст не может быть длиннее 255 символов.',
            'file_name.max' => 'Имя файла не может быть длиннее 255 символов.',
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