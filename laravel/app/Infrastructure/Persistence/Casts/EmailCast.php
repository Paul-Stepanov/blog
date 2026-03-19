<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Casts;

use App\Domain\Contact\ValueObjects\Email;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom Cast for Email Value Object.
 *
 * Transforms between database string and Domain Email VO.
 */
final readonly class EmailCast implements CastsAttributes
{
    /**
     * Cast the given value from database to Domain Email.
     *
     * @param Model $model
     * @param string $key
     * @param string|null $value
     * @param array<string, mixed> $attributes
     * @return Email|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Email
    {
        if ($value === null) {
            return null;
        }

        return Email::fromString($value);
    }

    /**
     * Cast the given Domain Email to database string.
     *
     * @param Model $model
     * @param string $key
     * @param Email|string|null $value
     * @param array<string, mixed> $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Email) {
            return $value->getValue();
        }

        // If string, create Email VO for validation
        if (is_string($value)) {
            return Email::fromString($value)->getValue();
        }

        return null;
    }
}