<?php

declare(strict_types=1);

namespace App\Application\Shared\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base exception for Application layer.
 *
 * All Application layer exceptions should extend this class.
 * This provides consistent exception handling across the Application layer.
 */
abstract class ApplicationException extends RuntimeException
{
    /**
     * @param string $message Exception message
     * @param int $code Error code
     * @param Throwable|null $previous Previous exception for chaining
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the error type for logging and API responses.
     *
     * @return non-empty-string
     */
    abstract public function getErrorType(): string;

    /**
     * Get additional context for logging.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return [];
    }
}