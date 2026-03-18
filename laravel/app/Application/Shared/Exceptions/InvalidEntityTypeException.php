<?php

declare(strict_types=1);

namespace App\Application\Shared\Exceptions;

/**
 * Exception thrown when an entity has an unexpected type.
 *
 * Used in DTOs when creating from domain entities with wrong type.
 */
final class InvalidEntityTypeException extends ApplicationException
{
    /**
     * @param string $expectedType Expected entity class name
     * @param string $actualType Actual entity class name
     */
    public function __construct(
        private readonly string $expectedType,
        private readonly string $actualType
    ) {
        $message = sprintf(
            'Expected entity of type "%s", got "%s"',
            $expectedType,
            $actualType
        );

        parent::__construct($message);
    }

    /**
     * Get the expected entity type.
     */
    public function getExpectedType(): string
    {
        return $this->expectedType;
    }

    /**
     * Get the actual entity type received.
     */
    public function getActualType(): string
    {
        return $this->actualType;
    }

    /**
     * @return non-empty-string
     */
    public function getErrorType(): string
    {
        return 'invalid_entity_type';
    }

    /**
     * @return array<string, string>
     */
    public function getContext(): array
    {
        return [
            'expected_type' => $this->expectedType,
            'actual_type' => $this->actualType,
        ];
    }
}