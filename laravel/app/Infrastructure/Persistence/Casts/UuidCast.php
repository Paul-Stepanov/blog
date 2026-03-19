<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Casts;

use App\Domain\Shared\Uuid;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom Cast for Uuid Value Object.
 *
 * Transforms between database string and Domain Uuid VO.
 */
final readonly class UuidCast implements CastsAttributes
{
    /**
     * Cast the given value from database to Domain Uuid.
     *
     * @param Model $model
     * @param string $key
     * @param string|null $value
     * @param array<string, mixed> $attributes
     * @return Uuid|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Uuid
    {
        if ($value === null) {
            return null;
        }

        return Uuid::fromString($value);
    }

    /**
     * Cast the given Domain Uuid to database string.
     *
     * @param Model $model
     * @param string $key
     * @param Uuid|string|null $value
     * @param array<string, mixed> $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Uuid) {
            return $value->getValue();
        }

        // If string, validate it's a valid UUID format
        if (is_string($value)) {
            return Uuid::fromString($value)->getValue();
        }

        return null;
    }
}