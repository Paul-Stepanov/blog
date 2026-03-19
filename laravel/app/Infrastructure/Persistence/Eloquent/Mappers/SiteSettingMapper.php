<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Settings\Entities\SiteSetting;
use App\Domain\Settings\ValueObjects\SettingValue;
use App\Infrastructure\Persistence\Eloquent\Models\SiteSettingModel;

/**
 * Mapper for SiteSetting Entity <-> SiteSettingModel.
 */
final class SiteSettingMapper
{
    use BaseMapper;

    /**
     * Convert Eloquent Model to Domain Entity.
     */
    public function toDomain(SiteSettingModel $model): SiteSetting
    {
        return SiteSetting::reconstitute(
            id: $this->mapUuid($model->uuid),
            key: $this->mapSettingKey($model->key),
            value: $this->mapSettingValue($model->value, $model->type),
            timestamps: $this->mapTimestamps($model),
        );
    }

    /**
     * Convert Domain Entity to Eloquent data array.
     *
     * @return array<string, mixed>
     */
    public function toEloquent(SiteSetting $entity): array
    {
        return [
            'uuid' => $entity->getId()->getValue(),
            'key' => $this->getSettingKeyValue($entity->getKey()),
            'value' => $entity->getValue()->toString(),
            'type' => $entity->getValue()->getType(),
            'created_at' => $entity->getTimestamps()->getCreatedAt(),
            'updated_at' => $entity->getTimestamps()->getUpdatedAt(),
        ];
    }

    /**
     * Convert collection of models to domain entities.
     *
     * @param SiteSettingModel[] $models
     * @return SiteSetting[]
     */
    public function toDomainCollection(array $models): array
    {
        return array_map(
            fn(SiteSettingModel $model): SiteSetting => $this->toDomain($model),
            $models
        );
    }

    /**
     * Map value and type to SettingValue VO.
     */
    private function mapSettingValue(string $value, string $type): SettingValue
    {
        return SettingValue::fromString($value, $type);
    }
}