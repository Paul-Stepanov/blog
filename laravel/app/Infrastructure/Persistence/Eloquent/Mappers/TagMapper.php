<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Article\Entities\Tag;
use App\Infrastructure\Persistence\Eloquent\Models\TagModel;

/**
 * Mapper for Tag Entity <-> TagModel.
 */
final class TagMapper
{
    use BaseMapper;

    /**
     * Convert Eloquent Model to Domain Entity.
     */
    public function toDomain(TagModel $model): Tag
    {
        return Tag::reconstitute(
            id: $this->mapUuid($model->uuid),
            name: $model->name,
            slug: $this->mapSlug($model->slug),
            timestamps: $this->mapTimestamps($model),
        );
    }

    /**
     * Convert Domain Entity to Eloquent data array.
     *
     * @return array<string, mixed>
     */
    public function toEloquent(Tag $entity): array
    {
        return [
            'uuid' => $entity->getId()->getValue(),
            'name' => $entity->getName(),
            'slug' => $this->getSlugValue($entity->getSlug()),
            'created_at' => $entity->getTimestamps()->getCreatedAt(),
            'updated_at' => $entity->getTimestamps()->getUpdatedAt(),
        ];
    }

    /**
     * Convert collection of models to domain entities.
     *
     * @param TagModel[] $models
     * @return Tag[]
     */
    public function toDomainCollection(array $models): array
    {
        return array_map(
            fn(TagModel $model): Tag => $this->toDomain($model),
            $models
        );
    }
}