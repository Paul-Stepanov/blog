<?php

declare(strict_types=1);

namespace App\Domain\Settings\Entities;

use App\Domain\Settings\ValueObjects\{SettingKey, SettingValue};
use App\Domain\Shared\Entity;
use App\Domain\Shared\Timestamps;
use App\Domain\Shared\Uuid;

/**
 * SiteSetting Entity.
 *
 * Represents a site configuration setting.
 */
final class SiteSetting extends Entity
{
    // Mutable properties
    private SettingValue $value;
    private Timestamps $timestamps;

    // Immutable properties (readonly)
    private readonly SettingKey $key;

    public function __construct(
        Uuid $id,
        SettingKey $key,
        SettingValue $value,
        Timestamps $timestamps,
    ) {
        parent::__construct($id);

        $this->key = $key;
        $this->value = $value;
        $this->timestamps = $timestamps;
    }

    /**
     * Create a new setting.
     */
    public static function create(
        Uuid $id,
        SettingKey $key,
        SettingValue $value,
    ): self {
        return new self(
            id: $id,
            key: $key,
            value: $value,
            timestamps: Timestamps::now(),
        );
    }

    /**
     * Reconstruct from persistence.
     */
    public static function reconstitute(
        Uuid $id,
        SettingKey $key,
        SettingValue $value,
        Timestamps $timestamps,
    ): self {
        return new self(
            id: $id,
            key: $key,
            value: $value,
            timestamps: $timestamps,
        );
    }

    /**
     * Update the setting value.
     */
    public function updateValue(SettingValue $newValue): void
    {
        if ($this->value->equals($newValue)) {
            return;
        }

        $this->value = $newValue;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Check if setting belongs to a specific group.
     */
    public function belongsToGroup(string $group): bool
    {
        return $this->key->belongsToGroup($group);
    }

    /**
     * Get the key string value.
     */
    public function getKeyString(): string
    {
        return $this->key->getValue();
    }

    // Getters

    public function getKey(): SettingKey
    {
        return $this->key;
    }

    public function getValue(): SettingValue
    {
        return $this->value;
    }

    public function getTimestamps(): Timestamps
    {
        return $this->timestamps;
    }
}