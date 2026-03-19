<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Casts;

use App\Domain\Contact\ValueObjects\IPAddress;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom Cast for IPAddress Value Object.
 *
 * Transforms between database string and Domain IPAddress VO.
 */
final readonly class IPAddressCast implements CastsAttributes
{
    /**
     * Cast the given value from database to Domain IPAddress.
     *
     * @param Model $model
     * @param string $key
     * @param string|null $value
     * @param array<string, mixed> $attributes
     * @return IPAddress|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?IPAddress
    {
        if ($value === null) {
            return null;
        }

        return IPAddress::fromString($value);
    }

    /**
     * Cast the given Domain IPAddress to database string.
     *
     * @param Model $model
     * @param string $key
     * @param IPAddress|string|null $value
     * @param array<string, mixed> $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof IPAddress) {
            return $value->getValue();
        }

        // If string, create IPAddress VO for validation
        if (is_string($value)) {
            return IPAddress::fromString($value)->getValue();
        }

        return null;
    }
}