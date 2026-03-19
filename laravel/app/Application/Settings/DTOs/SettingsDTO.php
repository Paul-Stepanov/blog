<?php

declare(strict_types=1);

namespace App\Application\Settings\DTOs;

use App\Application\Shared\{DTOFormattingTrait, DTOInterface};
use App\Application\Shared\Exceptions\InvalidEntityTypeException;
use App\Domain\Settings\Entities\SiteSetting;
use App\Domain\Shared\Entity;

/**
 * Settings Data Transfer Object.
 *
 * Represents a site setting for API responses.
 */
final readonly class SettingsDTO implements DTOInterface
{
    use DTOFormattingTrait;

    /**
     * @param string $id UUID string
     * @param string $key Setting key (e.g., 'site.title')
     * @param string $group Setting group (e.g., 'site')
     * @param mixed $value Typed value
     * @param string $valueType Value type (string, integer, float, boolean, json)
     * @param string $valueString String representation for storage
     * @param string $createdAt ISO 8601 datetime
     * @param string $updatedAt ISO 8601 datetime
     */
    public function __construct(
        public string $id,
        public string $key,
        public string $group,
        public mixed $value,
        public string $valueType,
        public string $valueString,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    /**
     * Create from Domain Entity.
     *
     * @param Entity $entity Domain setting entity
     * @throws \App\Domain\Shared\Exceptions\ValidationException If JSON encoding fails
     */
    public static function fromEntity(Entity $entity): static
    {
        if (!$entity instanceof SiteSetting) {
            throw new InvalidEntityTypeException(
                expectedType: SiteSetting::class,
                actualType: $entity::class
            );
        }

        $timestamps = $entity->getTimestamps();
        $settingValue = $entity->getValue();

        return new self(
            id: $entity->getId()->getValue(),
            key: $entity->getKey()->getValue(),
            group: $entity->getKey()->getGroup(),
            value: $settingValue->getValue(),
            valueType: $settingValue->getType(),
            valueString: $settingValue->toString(),
            createdAt: self::formatDate($timestamps->getCreatedAt()),
            updatedAt: self::formatDate($timestamps->getUpdatedAt()),
        );
    }

    /**
     * Convert DTO to associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'group' => $this->group,
            'value' => $this->value,
            'value_type' => $this->valueType,
            'value_string' => $this->valueString,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Check if setting is a boolean.
     */
    public function isBoolean(): bool
    {
        return $this->valueType === 'boolean';
    }

    /**
     * Check if setting is JSON (array).
     */
    public function isJson(): bool
    {
        return $this->valueType === 'json';
    }

    /**
     * Get value as boolean (for boolean settings).
     */
    public function asBoolean(): bool
    {
        return (bool) $this->value;
    }

    /**
     * Get value as array (for JSON settings).
     *
     * @return array<mixed>
     */
    public function asArray(): array
    {
        return is_array($this->value) ? $this->value : [];
    }
}