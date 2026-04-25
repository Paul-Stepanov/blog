<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\Settings\DTOs\SettingsDTO;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Settings Collection Resource.
 *
 * Transforms array of SettingsDTO for API response.
 */
final class SettingsCollectionResource extends JsonResource
{
    /**
     * @param array<SettingsDTO> $settings
     */
    public function __construct(private readonly array $settings)
    {
        parent::__construct($settings);
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<int, array<string, mixed>>
     */
    public function toArray($request): array
    {
        return array_map(
            static fn(SettingsDTO $setting) => [
                'id' => $setting->id,
                'key' => $setting->key,
                'group' => $setting->group,
                'value' => $setting->value,
                'type' => $setting->valueType,
                'created_at' => $setting->createdAt,
                'updated_at' => $setting->updatedAt,
            ],
            $this->settings
        );
    }
}