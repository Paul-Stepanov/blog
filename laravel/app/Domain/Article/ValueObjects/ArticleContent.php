<?php

declare(strict_types=1);

namespace App\Domain\Article\ValueObjects;

use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\ValueObject;

/**
 * Article Content Value Object.
 *
 * Represents the HTML content of an article with validation.
 */
final class ArticleContent extends ValueObject
{
    private readonly string $value;

    private function __construct(string $value)
    {
        $this->validateProperty($value);
        $this->value = $value;
    }

    /**
     * Create from raw HTML string.
     *
     * @throws ValidationException
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Create an empty content.
     */
    public static function empty(): self
    {
        return new self('');
    }

    /**
     * Validate content.
     *
     * @throws ValidationException
     */
    protected function validate(mixed $value): void
    {
        if (!is_string($value)) {
            throw ValidationException::forField('content', 'Content must be a string');
        }

        if (strlen($value) > 1000000) {
            throw ValidationException::forField('content', 'Content cannot exceed 1MB');
        }
    }

    /**
     * Check if content is empty.
     */
    public function isEmpty(): bool
    {
        return trim($this->value) === '';
    }

    /**
     * Check if content is not empty.
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get content length in characters.
     */
    public function length(): int
    {
        return strlen($this->value);
    }

    /**
     * Get word count (approximate).
     */
    public function wordCount(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }

        return str_word_count(strip_tags($this->value));
    }

    /**
     * Get estimated reading time in minutes.
     */
    public function readingTime(): int
    {
        $words = $this->wordCount();
        $wordsPerMinute = 200;

        return max(1, (int) ceil($words / $wordsPerMinute));
    }

    /**
     * Get excerpt (first N characters without HTML).
     */
    public function getExcerpt(int $length = 200): string
    {
        $text = strip_tags($this->value);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = trim($text);

        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . '...';
    }

    /**
     * Check equality with another ArticleContent.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get the raw HTML content.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return ['content' => $this->value];
    }
}