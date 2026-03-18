<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

/**
 * Exception for domain validation errors.
 *
 * Use when input data violates domain validation rules.
 */
final class ValidationException extends DomainException
{
    /**
     * @param array<string, string[]> $errors Field => [error messages]
     * @param string $message General error message
     */
    public function __construct(
        private readonly array $errors = [],
        string $message = 'Validation failed'
    ) {
        parent::__construct($message);
    }

    /**
     * Create from single field error.
     */
    public static function forField(string $field, string $message): self
    {
        return new self([$field => [$message]]);
    }

    /**
     * Create from multiple errors.
     *
     * @param array<string, string|string[]> $errors
     */
    public static function fromArray(array $errors): self
    {
        $normalized = array_map(static function ($messages) {
            return is_array($messages) ? $messages : [$messages];
        }, $errors);

        return new self($normalized);
    }

    /**
     * Get validation errors.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error for a field.
     */
    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Check if field has errors.
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && count($this->errors[$field]) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        return ['errors' => $this->errors];
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorType(): string
    {
        return 'domain_validation_error';
    }
}