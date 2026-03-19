<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent Model for SiteSetting persistence.
 *
 * Represents site configuration settings as key-value pairs.
 */
final class SiteSettingModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'site_settings';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'key',
        'value',
        'type',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for settings by key.
     */
    public function scopeByKey(\Illuminate\Database\Eloquent\Builder $query, string $key): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Scope for settings by group (prefix before first dot).
     */
    public function scopeByGroup(\Illuminate\Database\Eloquent\Builder $query, string $group): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('key', 'LIKE', $group . '.%');
    }

    /**
     * Scope for settings by type.
     */
    public function scopeByType(\Illuminate\Database\Eloquent\Builder $query, string $type): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for ordering by key.
     */
    public function scopeOrderedByKey(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('key', 'asc');
    }

    /**
     * Find by UUID.
     */
    public function scopeByUuid(\Illuminate\Database\Eloquent\Builder $query, string $uuid): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * Get the group (first part before dot).
     */
    public function getGroup(): string
    {
        $parts = explode('.', $this->key);
        return $parts[0];
    }

    /**
     * Get the name (last part after last dot).
     */
    public function getName(): string
    {
        $parts = explode('.', $this->key);
        return end($parts);
    }

    /**
     * Check if this setting belongs to a specific group.
     */
    public function belongsToGroup(string $group): bool
    {
        return str_starts_with($this->key, $group . '.');
    }

    /**
     * Get typed value based on type field.
     */
    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            'boolean' => $this->castToBoolean($this->value),
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'json' => $this->castToJson($this->value),
            default => $this->value,
        };
    }

    /**
     * Cast value to boolean.
     */
    private function castToBoolean(string $value): bool
    {
        return match (strtolower($value)) {
            'true', '1', 'yes', 'on' => true,
            'false', '0', 'no', 'off', '' => false,
            default => (bool) $value,
        };
    }

    /**
     * Cast value to JSON array.
     */
    private function castToJson(string $value): array
    {
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $decoded ?? [];
    }

    /**
     * Set value with automatic type detection.
     */
    public function setTypedValue(mixed $value): void
    {
        $this->type = $this->detectType($value);
        $this->value = $this->serializeValue($value);
    }

    /**
     * Detect type from value.
     */
    private function detectType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_array($value)) {
            return 'json';
        }

        return 'string';
    }

    /**
     * Serialize value to string for storage.
     */
    private function serializeValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        return (string) $value;
    }

    /**
     * Get route key name for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}