<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Casts;

use App\Domain\Media\ValueObjects\MimeType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom Cast for MimeType Value Object.
 *
 * Transforms between database string and Domain MimeType VO.
 */
final readonly class MimeTypeCast implements CastsAttributes
{
    /**
     * Cast the given value from database to Domain MimeType.
     *
     * @param Model $model
     * @param string $key
     * @param string|null $value
     * @param array<string, mixed> $attributes
     * @return MimeType|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?MimeType
    {
        if ($value === null) {
            return null;
        }

        return MimeType::fromString($value);
    }

    /**
     * Cast the given Domain MimeType to database string.
     *
     * @param Model $model
     * @param string $key
     * @param MimeType|string|null $value
     * @param array<string, mixed> $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof MimeType) {
            return $value->getValue();
        }

        // If string, create MimeType VO for validation
        if (is_string($value)) {
            return MimeType::fromString($value)->getValue();
        }

        return null;
    }
}