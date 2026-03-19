<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Casts;

use App\Domain\Settings\ValueObjects\SettingKey;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom Cast for SettingKey Value Object.
 *
 * Transforms between database string and Domain SettingKey VO.
 */
final readonly class SettingKeyCast implements CastsAttributes
{
    /**
     * Cast the given value from database to Domain SettingKey.
     *
     * @param Model $model
     * @param string $key
     * @param string|null $value
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?SettingKey
    {
        if ($value === null) {
            return null;
        }

        return SettingKey::fromString($value);
    }

    /**
     * Cast the given Domain SettingKey to database string.
     *
     * @param Model $model
     * @param string $key
     * @param SettingKey|string|null $value
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof SettingKey) {
            return $value->getValue();
        }

        // If string, create SettingKey VO for validation
        if (is_string($value)) {
            return SettingKey::fromString($value)->getValue();
        }

        return null;
    }
}