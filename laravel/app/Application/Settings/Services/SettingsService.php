<?php

declare(strict_types=1);

namespace App\Application\Settings\Services;

use App\Application\Settings\DTOs\SettingsDTO;
use App\Application\Settings\Exceptions\SettingNotFoundException;
use App\Domain\Settings\Entities\SiteSetting;
use App\Domain\Settings\Repositories\SettingsRepositoryInterface;
use App\Domain\Settings\ValueObjects\{SettingKey, SettingValue};
use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\Uuid;

/**
 * Settings Application Service.
 *
 * Handles site settings management.
 */
final readonly class SettingsService
{
    public function __construct(
        private SettingsRepositoryInterface $settingsRepository,
    ) {}

    /**
     * Get setting by key.
     *
     * @param string $keyString Setting key (e.g., 'site.title')
     * @throws SettingNotFoundException If setting not found
     * @throws ValidationException If key format is invalid
     */
    public function getSetting(string $keyString): SettingsDTO
    {
        $key = SettingKey::fromString($keyString);
        $setting = $this->settingsRepository->findByKey($key);

        if ($setting === null) {
            throw SettingNotFoundException::byKey($keyString);
        }

        return SettingsDTO::fromEntity($setting);
    }

    /**
     * Get setting value by key with default.
     *
     * @param string $keyString Setting key
     * @param mixed $default Default value if not found
     * @return mixed Setting value or default
     * @throws ValidationException If key format is invalid
     */
    public function getValue(string $keyString, mixed $default = null): mixed
    {
        $key = SettingKey::fromString($keyString);
        return $this->settingsRepository->getValue($key, $default);
    }

    /**
     * Set (create or update) a setting.
     *
     * @param string $keyString Setting key
     * @param mixed $value Setting value
     * @throws ValidationException If key or value is invalid
     */
    public function setSetting(string $keyString, mixed $value): SettingsDTO
    {
        $key = SettingKey::fromString($keyString);
        $settingValue = SettingValue::fromMixed($value);

        $existing = $this->settingsRepository->findByKey($key);

        if ($existing !== null) {
            $existing->updateValue($settingValue);
            $this->settingsRepository->save($existing);

            return SettingsDTO::fromEntity($existing);
        }

        $setting = SiteSetting::create(
            id: Uuid::generate(),
            key: $key,
            value: $settingValue,
        );

        $this->settingsRepository->save($setting);

        return SettingsDTO::fromEntity($setting);
    }

    /**
     * Set multiple settings at once.
     *
     * @param array<string, mixed> $settings Key-value pairs
     * @return array<SettingsDTO> Updated settings
     * @throws ValidationException If any key or value is invalid
     */
    public function setMany(array $settings): array
    {
        $result = [];

        $entities = [];
        foreach ($settings as $keyString => $value) {
            $key = SettingKey::fromString($keyString);
            $settingValue = SettingValue::fromMixed($value);

            $existing = $this->settingsRepository->findByKey($key);

            if ($existing !== null) {
                $existing->updateValue($settingValue);
                $entities[] = $existing;
                $result[] = SettingsDTO::fromEntity($existing);
            } else {
                $setting = SiteSetting::create(
                    id: Uuid::generate(),
                    key: $key,
                    value: $settingValue,
                );
                $entities[] = $setting;
                $result[] = SettingsDTO::fromEntity($setting);
            }
        }

        $this->settingsRepository->saveMany($entities);

        return $result;
    }

    /**
     * Get all settings.
     *
     * @return array<SettingsDTO>
     */
    public function getAllSettings(): array
    {
        $settings = $this->settingsRepository->findAll();

        return array_map(
            static fn(SiteSetting $setting) => SettingsDTO::fromEntity($setting),
            $settings
        );
    }

    /**
     * Get settings by group.
     *
     * @param string $group Group name (e.g., 'site', 'seo', 'social')
     * @return array<SettingsDTO>
     */
    public function getSettingsByGroup(string $group): array
    {
        $settings = $this->settingsRepository->findByGroup($group);

        return array_map(
            static fn(SiteSetting $setting) => SettingsDTO::fromEntity($setting),
            $settings
        );
    }

    /**
     * Get settings as key-value pairs.
     *
     * @return array<string, mixed>
     */
    public function getAllAsKeyValue(): array
    {
        return $this->settingsRepository->getAllAsKeyValue();
    }

    /**
     * Get settings by group as key-value pairs.
     *
     * @param string $group Group name
     * @return array<string, mixed>
     */
    public function getGroupAsKeyValue(string $group): array
    {
        return $this->settingsRepository->getGroupAsKeyValue($group);
    }

    /**
     * Delete a setting by key.
     *
     * @param string $keyString Setting key
     * @throws ValidationException If key format is invalid
     */
    public function deleteSetting(string $keyString): void
    {
        $key = SettingKey::fromString($keyString);
        $this->settingsRepository->deleteByKey($key);
    }

    /**
     * Delete all settings in a group.
     *
     * @param string $group Group name
     */
    public function deleteGroup(string $group): void
    {
        $this->settingsRepository->deleteByGroup($group);
    }

    /**
     * Check if a setting exists.
     *
     * @param string $keyString Setting key
     * @throws ValidationException If key format is invalid
     */
    public function exists(string $keyString): bool
    {
        $key = SettingKey::fromString($keyString);
        return $this->settingsRepository->exists($key);
    }
}