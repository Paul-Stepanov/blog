<?php

declare(strict_types=1);

namespace App\Domain\Settings\ValueObjects;

use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\ValueObject;

/**
 * Setting Key Value Object.
 *
 * Represents a validated setting key (e.g., 'site.title', 'seo.meta_description').
 */
final class SettingKey extends ValueObject
{
    private readonly string $value;

    /**
     * Known setting keys with their types.
     */
    private const KNOWN_KEYS = [
        'site.title' => 'string',
        'site.description' => 'string',
        'site.author' => 'string',
        'site.email' => 'string',
        'site.url' => 'string',
        'site.logo_url' => 'string',
        'site.favicon_url' => 'string',
        'seo.meta_title' => 'string',
        'seo.meta_description' => 'string',
        'seo.meta_keywords' => 'string',
        'seo.og_image_url' => 'string',
        'social.twitter' => 'string',
        'social.github' => 'string',
        'social.linkedin' => 'string',
        'social.telegram' => 'string',
        'analytics.google_id' => 'string',
        'analytics.yandex_id' => 'string',
        'features.comments_enabled' => 'boolean',
        'features.newsletter_enabled' => 'boolean',
        'features.dark_mode_default' => 'boolean',
    ];

    private function __construct(string $value)
    {
        $this->validateProperty($value);
        $this->value = $value;
    }

    /**
     * Create from string.
     *
     * @throws ValidationException
     */
    public static function fromString(string $value): self
    {
        return new self(trim($value));
    }

    /**
     * Validate setting key format.
     *
     * @throws ValidationException
     */
    protected function validate(mixed $value): void
    {
        if (!is_string($value)) {
            throw ValidationException::forField('key', 'Setting key must be a string');
        }

        if (empty($value)) {
            throw ValidationException::forField('key', 'Setting key cannot be empty');
        }

        if (strlen($value) > 100) {
            throw ValidationException::forField('key', 'Setting key cannot exceed 100 characters');
        }

        // Format: group.subgroup.name (lowercase, dots, underscores)
        if (!preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)*$/', $value)) {
            throw ValidationException::forField(
                'key',
                'Setting key must be in format: group.name or group.subgroup.name (lowercase letters, numbers, underscores, dots)'
            );
        }
    }

    /**
     * Get the group (first part before dot).
     */
    public function getGroup(): string
    {
        $parts = explode('.', $this->value);
        return $parts[0];
    }

    /**
     * Get the name (last part after last dot).
     */
    public function getName(): string
    {
        $parts = explode('.', $this->value);
        return end($parts);
    }

    /**
     * Check if this is a known setting key.
     */
    public function isKnown(): bool
    {
        return isset(self::KNOWN_KEYS[$this->value]);
    }

    /**
     * Get expected type for this key.
     */
    public function getExpectedType(): ?string
    {
        return self::KNOWN_KEYS[$this->value] ?? null;
    }

    /**
     * Check if key belongs to a specific group.
     */
    public function belongsToGroup(string $group): bool
    {
        return str_starts_with($this->value, $group . '.');
    }

    /**
     * Get all known keys grouped by category.
     *
     * @return array<string, array<string, string>>
     */
    public static function getAllGrouped(): array
    {
        $grouped = [];

        foreach (self::KNOWN_KEYS as $key => $type) {
            $group = explode('.', $key)[0];
            $grouped[$group][$key] = $type;
        }

        return $grouped;
    }

    /**
     * Check equality with another SettingKey.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get the key string.
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
        return ['key' => $this->value];
    }
}