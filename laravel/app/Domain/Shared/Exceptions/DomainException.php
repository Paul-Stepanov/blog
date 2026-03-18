<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use RuntimeException;

/**
 * Base exception for domain-level errors.
 *
 * Use this exception when domain rules or invariants are violated.
 * Examples: invalid state transition, business rule violation.
 */
abstract class DomainException extends RuntimeException
{
    /**
     * @param string $message Error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the domain context where the exception occurred.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return [];
    }

    /**
     * Get error type for API response classification.
     */
    public function getErrorType(): string
    {
        return 'domain_error';
    }
}