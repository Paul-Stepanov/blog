<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Article\Entities\Category;
use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;

/**
 * Mapper for Category Entity <-> CategoryModel.
 */
final class CategoryMapper
{
    use BaseMapper;

    /**
     * Convert Eloquent Model to Domain Entity.
     */
    public function toDomain(CategoryModel $model): Category
    {
        return Category::reconstitute(
            id: $this->mapUuid($model->uuid),
            name: $model->name,
            slug: $this->mapSlug($model->slug),
            description: $model->description ?? '',
            timestamps: $this->mapTimestamps($model),
        );
    }

    /**
     * Convert Domain Entity to Eloquent data array.
     *
     * @return array<string, mixed>
     */
    public function toEloquent(Category $entity): array
    {
        return [
            'uuid' => $entity->getId()->getValue(),
            'name' => $entity->getName(),
            'slug' => $this->getSlugValue($entity->getSlug()),
            'description' => $entity->getDescription(),
            'created_at' => $entity->getTimestamps()->getCreatedAt(),
            'updated_at' => $entity->getTimestamps()->getUpdatedAt(),
        ];
    }

    /**
     * Convert collection of models to domain entities.
     *
     * @param CategoryModel[] $models
     * @return Category[]
     */
    public function toDomainCollection(array $models): array
    {
        return array_map(
            fn(CategoryModel $model): Category => $this->toDomain($model),
            $models
        );
    }
}