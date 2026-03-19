<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Casts;

use App\Domain\Media\ValueObjects\FilePath;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom Cast for FilePath Value Object.
 *
 * Transforms between database string and Domain FilePath VO.
 */
final readonly class FilePathCast implements CastsAttributes
{
    /**
     * Cast the given value from database to Domain FilePath.
     *
     * @param Model $model
     * @param string $key
     * @param string|null $value
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?FilePath
    {
        if ($value === null) {
            return null;
        }

        return FilePath::fromString($value);
    }

    /**
     * Cast the given Domain FilePath to database string.
     *
     * @param Model $model
     * @param string $key
     * @param FilePath|string|null $value
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof FilePath) {
            return $value->getValue();
        }

        // If string, create FilePath VO for validation
        if (is_string($value)) {
            return FilePath::fromString($value)->getValue();
        }

        return null;
    }
}