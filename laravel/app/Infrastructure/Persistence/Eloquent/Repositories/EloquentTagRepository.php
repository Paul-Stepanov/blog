<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Article\Entities\Tag;
use App\Domain\Article\Repositories\TagRepositoryInterface;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\Uuid;
use App\Infrastructure\Persistence\Eloquent\Mappers\TagMapper;
use App\Infrastructure\Persistence\Eloquent\Models\TagModel;

/**
 * Eloquent implementation of Tag Repository.
 *
 * Note: Tag does not manage article relationships.
 * Use ArticleRepository::syncTags() for tag synchronization.
 */
final readonly class EloquentTagRepository implements TagRepositoryInterface
{
    public function __construct(
        private TagMapper $mapper,
    ) {}

    /**
     * @inheritDoc
     */
    public function findById(Uuid $id): ?Tag
    {
        $model = $this->findModelById($id);

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getById(Uuid $id): Tag
    {
        $model = $this->findModelById($id);

        if ($model === null) {
            throw EntityNotFoundException::forEntity('Tag', $id);
        }

        return $this->mapper->toDomain($model);
    }

    /**
     * @inheritDoc
     */
    public function findBySlug(string $slug): ?Tag
    {
        $model = TagModel::query()
            ->where('slug', $slug)
            ->first();

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function findByIds(array $ids): array
    {
        $uuids = array_map(
            static fn(Uuid $id): string => $id->getValue(),
            $ids
        );

        $models = TagModel::query()
            ->whereIn('uuid', $uuids)
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function findBySlugs(array $slugs): array
    {
        $models = TagModel::query()
            ->whereIn('slug', $slugs)
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function findAll(): array
    {
        $models = TagModel::query()
            ->orderBy('name', 'asc')
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function findAllOrderedByName(): array
    {
        $models = TagModel::query()
            ->orderBy('name', 'asc')
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function getWithArticleCount(): array
    {
        $models = TagModel::query()
            ->withCount('articles')
            ->orderBy('name', 'asc')
            ->get();

        $result = [];
        foreach ($models as $model) {
            $result[] = [
                'tag' => $this->mapper->toDomain($model),
                'count' => $model->articles_count,
            ];
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getPopular(int $limit = 10): array
    {
        $models = TagModel::query()
            ->withCount('articles')
            ->having('articles_count', '>', 0)
            ->orderBy('articles_count', 'desc')
            ->limit($limit)
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function getForArticle(Uuid $articleId): array
    {
        $models = TagModel::query()
            ->whereHas('articles', static function ($query) use ($articleId): void {
                $query->where('articles.uuid', $articleId->getValue());
            })
            ->orderBy('name', 'asc')
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     *
     * Note: This method is intentionally NOT implemented here.
     * Tag does not own the article_tag relationship.
     * Use ArticleRepository::syncTags() instead.
     * This method will throw if called directly.
     */
    public function syncForArticle(Uuid $articleId, array $tagIds): void
    {
        throw new \LogicException(
            'TagRepository::syncForArticle() is deprecated. ' .
            'Article owns the relationship. Use ArticleRepository::syncTags() instead.'
        );
    }

    /**
     * @inheritDoc
     */
    public function save(Tag $tag): void
    {
        $data = $this->mapper->toEloquent($tag);

        TagModel::query()->updateOrCreate(
            ['uuid' => $data['uuid']],
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(Uuid $id): void
    {
        TagModel::query()
            ->where('uuid', $id->getValue())
            ->delete();
    }

    /**
     * @inheritDoc
     */
    public function slugExists(string $slug, ?Uuid $excludeId = null): bool
    {
        $query = TagModel::query()->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('uuid', '!=', $excludeId->getValue());
        }

        return $query->exists();
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return TagModel::query()->count();
    }

    /**
     * Find model by UUID.
     */
    private function findModelById(Uuid $id): ?TagModel
    {
        return TagModel::query()
            ->where('uuid', $id->getValue())
            ->first();
    }
}