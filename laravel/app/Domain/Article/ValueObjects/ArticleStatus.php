<?php

declare(strict_types=1);

namespace App\Domain\Article\ValueObjects;

use App\Domain\Shared\Exceptions\ValidationException;

/**
 * Article Status Enum.
 *
 * Represents the lifecycle states of an article.
 */
enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * Check if the article is publicly visible.
     */
    public function isPublic(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Check if the article can be edited.
     */
    public function isEditable(): bool
    {
        return $this !== self::ARCHIVED;
    }

    /**
     * Check if the article can be published.
     */
    public function canBePublished(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if the article can be archived.
     */
    public function canBeArchived(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Get all available status values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Черновик',
            self::PUBLISHED => 'Опубликована',
            self::ARCHIVED => 'В архиве',
        };
    }

    /**
     * Get CSS class for UI styling.
     */
    public function getCssClass(): string
    {
        return match ($this) {
            self::DRAFT => 'status-draft',
            self::PUBLISHED => 'status-published',
            self::ARCHIVED => 'status-archived',
        };
    }

    /**
     * Create from string value or throw ValidationException.
     *
     * @throws ValidationException
     */
    public static function fromString(string $value): self
    {
        $status = self::tryFrom(strtolower($value));

        if ($status === null) {
            throw ValidationException::forField(
                'status',
                sprintf('Invalid status "%s". Valid values: %s', $value, implode(', ', self::values()))
            );
        }

        return $status;
    }
}