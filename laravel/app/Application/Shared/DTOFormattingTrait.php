<?php

declare(strict_types=1);

namespace App\Application\Shared;

use App\Domain\Shared\Uuid;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Trait for common DTO formatting operations.
 *
 * Provides reusable static formatting methods for dates, UUIDs, and other values.
 * All methods are static to be usable in readonly DTO classes.
 */
trait DTOFormattingTrait
{
    /**
     * Default JSON serialization - delegates to toArray().
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Format DateTimeImmutable to ISO 8601 string.
     */
    protected static function formatDate(?DateTimeImmutable $date): ?string
    {
        return $date?->format(DateTimeInterface::ATOM);
    }

    /**
     * Format UUID Value Object to string.
     */
    protected static function formatUuid(?Uuid $uuid): ?string
    {
        return $uuid?->getValue();
    }

    /**
     * Get human-readable reading time text.
     *
     * @return non-empty-string
     */
    protected static function getReadingTimeText(int $minutes): string
    {
        if ($minutes < 1) {
            return 'Less than 1 min read';
        }

        if ($minutes === 1) {
            return '1 min read';
        }

        return "{$minutes} min read";
    }

    /**
     * Format file size in human-readable format.
     */
    protected static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Truncate text to specified length with ellipsis.
     */
    protected static function truncateText(string $text, int $length = 100): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . '...';
    }

    /**
     * Format boolean as string for API responses.
     */
    protected static function formatBool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}