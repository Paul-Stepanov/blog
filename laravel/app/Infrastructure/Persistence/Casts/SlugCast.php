<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Casts;

use App\Domain\Article\ValueObjects\Slug;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom Cast for Slug Value Object.
 *
 * Transforms between database string and Domain Slug VO.
 */
final readonly class SlugCast implements CastsAttributes
{
    /**
     * Cast the given value from database to Domain Slug.
     *
     * @param Model $model
     * @param string $key
     * @param string|null $value
     * @param array<string, mixed> $attributes
     * @return Slug|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Slug
    {
        if ($value === null) {
            return null;
        }

        return Slug::fromString($value);
    }

    /**
     * Cast the given Domain Slug to database string.
     *
     * @param Model $model
     * @param string $key
     * @param Slug|string|null $value
     * @param array<string, mixed> $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Slug) {
            return $value->getValue();
        }

        // If string, create Slug VO for validation
        if (is_string($value)) {
            return Slug::fromString($value)->getValue();
        }

        return null;
    }
}