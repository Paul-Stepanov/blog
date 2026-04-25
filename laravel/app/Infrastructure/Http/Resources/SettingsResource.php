<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\Settings\DTOs\SettingsDTO;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Settings Resource.
 *
 * Represents a single site setting.
 */
final class SettingsResource extends JsonResource
{
    /**
     * @param SettingsDTO $resource
     */
    public function __construct(SettingsDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var SettingsDTO $setting */
        $setting = $this->resource;

        return [
            'id' => $setting->id,
            'key' => $setting->key,
            'group' => $setting->group,
            'value' => $setting->value,
            'type' => $setting->valueType,
            'created_at' => $setting->createdAt,
            'updated_at' => $setting->updatedAt,
        ];
    }
}