<?php

declare(strict_types=1);

namespace App\Domain\Article\ValueObjects;

use App\Domain\Shared\ValueObject;
use InvalidArgumentException;

/**
 * Slug Value Object for URL-friendly identifiers.
 *
 * Slugs are lowercase, alphanumeric strings with hyphens instead of spaces.
 * Used for article, category, and tag URLs.
 */
final class Slug extends ValueObject
{
    private readonly string $value;

    private function __construct(string $value)
    {
        $this->validateProperty($value);
        $this->value = $value;
    }

    /**
     * Create a Slug from a string.
     *
     * @throws InvalidArgumentException If the slug format is invalid
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Generate a slug from a title or name.
     */
    public static function fromTitle(string $title): self
    {
        // Transliterate non-ASCII characters
        $slug = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $title);

        // Replace non-alphanumeric characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug ?? $title);

        // Remove leading/trailing hyphens and multiple hyphens
        $slug = preg_replace('/-+/', '-', trim($slug ?? '', '-'));

        return new self($slug ?? '');
    }

    /**
     * Validate slug format.
     *
     * @throws InvalidArgumentException
     */
    protected function validate(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Slug must be a string');
        }

        if (empty($value)) {
            throw new InvalidArgumentException('Slug cannot be empty');
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            throw new InvalidArgumentException(
                'Slug must contain only lowercase letters, numbers, and hyphens. ' .
                'Cannot start or end with a hyphen or contain consecutive hyphens.'
            );
        }

        if (strlen($value) > 255) {
            throw new InvalidArgumentException('Slug cannot exceed 255 characters');
        }
    }

    /**
     * Check equality with another Slug.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get the slug string value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get string representation.
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return ['slug' => $this->value];
    }
}