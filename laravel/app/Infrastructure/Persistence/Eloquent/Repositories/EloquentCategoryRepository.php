<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Article\Entities\Category;
use App\Domain\Article\Repositories\CategoryRepositoryInterface;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\Uuid;
use App\Infrastructure\Persistence\Eloquent\Mappers\CategoryMapper;
use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;

/**
 * Eloquent implementation of Category Repository.
 */
final readonly class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private CategoryMapper $mapper,
    ) {}

    /**
     * @inheritDoc
     */
    public function findById(Uuid $id): ?Category
    {
        $model = $this->findModelById($id);

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getById(Uuid $id): Category
    {
        $model = $this->findModelById($id);

        if ($model === null) {
            throw EntityNotFoundException::forEntity('Category', $id);
        }

        return $this->mapper->toDomain($model);
    }

    /**
     * @inheritDoc
     */
    public function findBySlug(string $slug): ?Category
    {
        $model = $this->findModelBySlug($slug);

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getBySlug(string $slug): Category
    {
        $model = $this->findModelBySlug($slug);

        if ($model === null) {
            throw EntityNotFoundException::bySlug('Category', $slug);
        }

        return $this->mapper->toDomain($model);
    }

    /**
     * @inheritDoc
     */
    public function findAll(): array
    {
        $models = CategoryModel::query()
            ->orderBy('name', 'asc')
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function findAllWithArticleCount(): array
    {
        $models = CategoryModel::query()
            ->withCount(['articles' => fn($q) => $q->where('status', 'published')])
            ->orderBy('name', 'asc')
            ->get();

        $result = [];
        foreach ($models as $model) {
            $result[] = [
                'category' => $this->mapper->toDomain($model),
                'count' => $model->articles_count,
            ];
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getWithPublishedArticles(): array
    {
        $models = CategoryModel::query()
            ->whereHas('articles', fn($q) => $q->where('status', 'published'))
            ->orderBy('name', 'asc')
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function save(Category $category): void
    {
        $data = $this->mapper->toEloquent($category);

        CategoryModel::query()->updateOrCreate(
            ['uuid' => $data['uuid']],
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(Uuid $id): void
    {
        CategoryModel::query()
            ->where('uuid', $id->getValue())
            ->delete();
    }

    /**
     * @inheritDoc
     */
    public function slugExists(string $slug, ?Uuid $excludeId = null): bool
    {
        $query = CategoryModel::query()->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('uuid', '!=', $excludeId->getValue());
        }

        return $query->exists();
    }

    /**
     * @inheritDoc
     */
    public function hasArticles(Uuid $id): bool
    {
        return ArticleModel::query()
            ->where('category_uuid', $id->getValue())
            ->exists();
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return CategoryModel::query()->count();
    }

    /**
     * Find model by UUID.
     */
    private function findModelById(Uuid $id): ?CategoryModel
    {
        return CategoryModel::query()
            ->where('uuid', $id->getValue())
            ->first();
    }

    /**
     * Find model by slug.
     */
    private function findModelBySlug(string $slug): ?CategoryModel
    {
        return CategoryModel::query()
            ->where('slug', $slug)
            ->first();
    }
}