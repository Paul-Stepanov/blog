<?php

declare(strict_types=1);

namespace App\Domain\Settings\Repositories;

use App\Domain\Settings\Entities\SiteSetting;
use App\Domain\Settings\ValueObjects\SettingKey;
use App\Domain\Shared\Uuid;

/**
 * Settings Repository Interface.
 *
 * Contract for site settings persistence operations.
 */
interface SettingsRepositoryInterface
{
    /**
     * Find setting by ID.
     */
    public function findById(Uuid $id): ?SiteSetting;

    /**
     * Find setting by key.
     */
    public function findByKey(SettingKey $key): ?SiteSetting;

    /**
     * Get setting value by key.
     */
    public function getValue(SettingKey $key, mixed $default = null): mixed;

    /**
     * Get all settings.
     *
     * @return array<SiteSetting>
     */
    public function findAll(): array;

    /**
     * Get settings by group.
     *
     * @return array<SiteSetting>
     */
    public function findByGroup(string $group): array;

    /**
     * Get settings as key-value pairs.
     *
     * @return array<string, mixed>
     */
    public function getAllAsKeyValue(): array;

    /**
     * Get settings by group as key-value pairs.
     *
     * @return array<string, mixed>
     */
    public function getGroupAsKeyValue(string $group): array;

    /**
     * Check if setting exists.
     */
    public function exists(SettingKey $key): bool;

    /**
     * Save setting (create or update).
     */
    public function save(SiteSetting $setting): void;

    /**
     * Save multiple settings at once.
     *
     * @param array<SiteSetting> $settings
     */
    public function saveMany(array $settings): void;

    /**
     * Delete setting by ID.
     */
    public function delete(Uuid $id): void;

    /**
     * Delete setting by key.
     */
    public function deleteByKey(SettingKey $key): void;

    /**
     * Delete all settings in a group.
     */
    public function deleteByGroup(string $group): void;

    /**
     * Count total settings.
     */
    public function count(): int;

    /**
     * Count settings by group.
     */
    public function countByGroup(string $group): int;
}