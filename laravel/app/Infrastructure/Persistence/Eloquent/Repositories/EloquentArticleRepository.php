<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Article\Entities\Article;
use App\Domain\Article\Repositories\ArticleRepositoryInterface;
use App\Domain\Article\ValueObjects\ArticleFilters;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;
use App\Infrastructure\Persistence\Eloquent\Mappers\ArticleMapper;
use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Infrastructure\Persistence\Eloquent\Models\TagModel;

/**
 * Eloquent implementation of Article Repository.
 */
final readonly class EloquentArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private ArticleMapper $mapper,
    ) {}

    /**
     * @inheritDoc
     */
    public function findByFilters(
        ArticleFilters $filters,
        int $page = 1,
        int $perPage = 12
    ): PaginatedResult {
        $query = ArticleModel::query()->with(['category', 'tags']);

        $this->applyFilters($query, $filters);
        $this->applyOrdering($query, $filters);

        $total = $query->count();
        $models = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->all();

        return PaginatedResult::create(
            items: $this->mapper->toDomainCollection($models),
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    /**
     * @inheritDoc
     */
    public function findById(Uuid $id): ?Article
    {
        $model = $this->findModelById($id);

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getById(Uuid $id): Article
    {
        $model = $this->findModelById($id);

        if ($model === null) {
            throw EntityNotFoundException::forEntity('Article', $id);
        }

        return $this->mapper->toDomain($model);
    }

    /**
     * @inheritDoc
     */
    public function findBySlug(string $slug): ?Article
    {
        $model = $this->findModelBySlug($slug);

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getBySlug(string $slug): Article
    {
        $model = $this->findModelBySlug($slug);

        if ($model === null) {
            throw EntityNotFoundException::bySlug('Article', $slug);
        }

        return $this->mapper->toDomain($model);
    }

    /**
     * @inheritDoc
     */
    public function findPublished(int $page = 1, int $perPage = 12): PaginatedResult
    {
        return $this->findByFilters(
            ArticleFilters::published(),
            $page,
            $perPage
        );
    }

    /**
     * @inheritDoc
     */
    public function findByCategory(string $categorySlug, int $page = 1, int $perPage = 12): PaginatedResult
    {
        $query = ArticleModel::query()
            ->with(['category', 'tags'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereHas('category', fn($q) => $q->where('slug', $categorySlug))
            ->orderBy('published_at', 'desc');

        $total = $query->count();
        $models = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->all();

        return PaginatedResult::create(
            items: $this->mapper->toDomainCollection($models),
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    /**
     * @inheritDoc
     */
    public function findByTag(string $tagSlug, int $page = 1, int $perPage = 12): PaginatedResult
    {
        $query = ArticleModel::query()
            ->with(['category', 'tags'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereHas('tags', fn($q) => $q->where('slug', $tagSlug))
            ->orderBy('published_at', 'desc');

        $total = $query->count();
        $models = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->all();

        return PaginatedResult::create(
            items: $this->mapper->toDomainCollection($models),
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    /**
     * @inheritDoc
     */
    public function findByAuthor(Uuid $authorId, int $page = 1, int $perPage = 12): PaginatedResult
    {
        $query = ArticleModel::query()
            ->with(['category', 'tags'])
            ->where('author_uuid', $authorId->getValue())
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $models = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->all();

        return PaginatedResult::create(
            items: $this->mapper->toDomainCollection($models),
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    /**
     * @inheritDoc
     */
    public function search(string $query, int $page = 1, int $perPage = 12): PaginatedResult
    {
        return $this->findByFilters(
            ArticleFilters::create(['search' => $query]),
            $page,
            $perPage
        );
    }

    /**
     * @inheritDoc
     */
    public function getLatest(int $limit = 5): array
    {
        $models = ArticleModel::query()
            ->with(['category', 'tags'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function getFeatured(int $limit = 3): array
    {
        // Featured = latest published articles
        return $this->getLatest($limit);
    }

    /**
     * @inheritDoc
     */
    public function findAll(int $page = 1, int $perPage = 20): PaginatedResult
    {
        return $this->findByFilters(
            ArticleFilters::empty(),
            $page,
            $perPage
        );
    }

    /**
     * @inheritDoc
     */
    public function findByStatus(string $status, int $page = 1, int $perPage = 20): PaginatedResult
    {
        return $this->findByFilters(
            ArticleFilters::create(['status' => $status]),
            $page,
            $perPage
        );
    }

    /**
     * @inheritDoc
     */
    public function save(Article $article): void
    {
        $data = $this->mapper->toEloquent($article);

        ArticleModel::query()->updateOrCreate(
            ['uuid' => $data['uuid']],
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(Uuid $id): void
    {
        ArticleModel::query()
            ->where('uuid', $id->getValue())
            ->delete();
    }

    /**
     * @inheritDoc
     */
    public function slugExists(string $slug, ?Uuid $excludeId = null): bool
    {
        $query = ArticleModel::query()->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('uuid', '!=', $excludeId->getValue());
        }

        return $query->exists();
    }

    /**
     * @inheritDoc
     */
    public function countByStatus(): array
    {
        $results = ArticleModel::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $counts = [
            'draft' => 0,
            'published' => 0,
            'archived' => 0,
        ];

        foreach ($results as $row) {
            $counts[$row->status] = $row->count;
        }

        return $counts;
    }

    /**
     * @inheritDoc
     */
    public function syncTags(Uuid $articleId, array $tagIds): void
    {
        // 1. Find article model
        $articleModel = ArticleModel::query()
            ->where('uuid', $articleId->getValue())
            ->first();

        if ($articleModel === null) {
            throw EntityNotFoundException::forEntity('Article', $articleId);
        }

        // 2. Convert tag UUIDs to internal IDs
        $tagUuidValues = array_map(
            static fn(Uuid $id): string => $id->getValue(),
            $tagIds
        );

        $tagModelIds = TagModel::query()
            ->whereIn('uuid', $tagUuidValues)
            ->pluck('id')
            ->all();

        // 3. Sync via Eloquent relationship
        $articleModel->tags()->sync($tagModelIds);
    }

    /**
     * Apply filters to query.
     */
    private function applyFilters(
        \Illuminate\Database\Eloquent\Builder $query,
        ArticleFilters $filters
    ): void {
        if ($filters->hasStatus()) {
            $query->where('status', $filters->status->value);
        }

        if ($filters->hasSearch()) {
            $searchTerm = $filters->getSearchTermSafe();
            $query->where(function ($q) use ($searchTerm): void {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('content', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('excerpt', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($filters->hasCategory()) {
            $query->where('category_uuid', $filters->categoryId->getValue());
        }

        if ($filters->hasAuthor()) {
            $query->where('author_uuid', $filters->authorId->getValue());
        }

        if ($filters->hasTag()) {
            $query->whereHas('tags', fn($q) => $q->where('tags.uuid', $filters->tagId->getValue()));
        }
    }

    /**
     * Apply ordering to query.
     */
    private function applyOrdering(
        \Illuminate\Database\Eloquent\Builder $query,
        ArticleFilters $filters
    ): void {
        if ($filters->hasStatus() && $filters->status->isPublic()) {
            $query->orderBy('published_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Find model by UUID.
     */
    private function findModelById(Uuid $id): ?ArticleModel
    {
        return ArticleModel::query()
            ->with(['category', 'tags'])
            ->where('uuid', $id->getValue())
            ->first();
    }

    /**
     * Find model by slug.
     */
    private function findModelBySlug(string $slug): ?ArticleModel
    {
        return ArticleModel::query()
            ->with(['category', 'tags'])
            ->where('slug', $slug)
            ->first();
    }
}