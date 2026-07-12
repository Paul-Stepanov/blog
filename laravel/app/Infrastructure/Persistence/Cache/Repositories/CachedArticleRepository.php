<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Cache\Repositories;

use App\Domain\Article\Entities\Article;
use App\Domain\Article\Repositories\ArticleRepositoryInterface;
use App\Domain\Article\ValueObjects\ArticleFilters;
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Cached decorator for Article Repository.
 *
 * Decorates any ArticleRepositoryInterface implementation with caching.
 * Read operations are cached under the "articles" cache tag; every write
 * operation flushes the whole tag, guaranteeing readers never observe stale
 * data (no TTL lag between a mutation and the next list read). Tag-based
 * flush works on the array store (tests) and Redis/Memcached (production).
 */
final readonly class CachedArticleRepository implements ArticleRepositoryInterface
{
    private const string TAG = 'articles';

    private const int TTL_READ = 3600; // 1 hour

    private const int TTL_LIST = 1800; // 30 minutes

    private const int TTL_COUNT = 300; // 5 minutes

    public function __construct(
        private ArticleRepositoryInterface $repository,
        private CacheRepository $cache,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function findByFilters(
        ArticleFilters $filters,
        int $page = 1,
        int $perPage = 12
    ): PaginatedResult {
        $cacheKey = $this->buildListCacheKey('by_filters', [
            'filters' => $filters->toArray(),
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->remember($cacheKey, self::TTL_LIST, fn () => $this->repository->findByFilters($filters, $page, $perPage));
    }

    /**
     * {@inheritDoc}
     */
    public function findById(Uuid $id): ?Article
    {
        return $this->remember($this->buildEntityCacheKey($id), self::TTL_READ, fn () => $this->repository->findById($id));
    }

    /**
     * {@inheritDoc}
     */
    public function getById(Uuid $id): Article
    {
        return $this->remember($this->buildEntityCacheKey($id), self::TTL_READ, fn () => $this->repository->getById($id));
    }

    /**
     * {@inheritDoc}
     */
    public function findBySlug(string $slug): ?Article
    {
        return $this->remember($this->buildSlugCacheKey($slug), self::TTL_READ, fn () => $this->repository->findBySlug($slug));
    }

    /**
     * {@inheritDoc}
     */
    public function getBySlug(string $slug): Article
    {
        return $this->remember($this->buildSlugCacheKey($slug), self::TTL_READ, fn () => $this->repository->getBySlug($slug));
    }

    /**
     * {@inheritDoc}
     */
    public function findPublished(int $page = 1, int $perPage = 12): PaginatedResult
    {
        $cacheKey = $this->buildListCacheKey('published', [
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->remember($cacheKey, self::TTL_LIST, fn () => $this->repository->findPublished($page, $perPage));
    }

    /**
     * {@inheritDoc}
     */
    public function findByCategory(string $categorySlug, int $page = 1, int $perPage = 12): PaginatedResult
    {
        $cacheKey = $this->buildListCacheKey('by_category', [
            'category' => $categorySlug,
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->remember($cacheKey, self::TTL_LIST, fn () => $this->repository->findByCategory($categorySlug, $page, $perPage));
    }

    /**
     * {@inheritDoc}
     */
    public function findByTag(string $tagSlug, int $page = 1, int $perPage = 12): PaginatedResult
    {
        $cacheKey = $this->buildListCacheKey('by_tag', [
            'tag' => $tagSlug,
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->remember($cacheKey, self::TTL_LIST, fn () => $this->repository->findByTag($tagSlug, $page, $perPage));
    }

    /**
     * {@inheritDoc}
     */
    public function findByAuthor(Uuid $authorId, int $page = 1, int $perPage = 12): PaginatedResult
    {
        $cacheKey = $this->buildListCacheKey('by_author', [
            'author' => $authorId->getValue(),
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->remember($cacheKey, self::TTL_LIST, fn () => $this->repository->findByAuthor($authorId, $page, $perPage));
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $query, int $page = 1, int $perPage = 12): PaginatedResult
    {
        // Search results are not cached - queries vary too much
        return $this->repository->search($query, $page, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getLatest(int $limit = 5): array
    {
        return $this->remember($this->buildListCacheKey('latest', ['limit' => $limit]), self::TTL_LIST, fn () => $this->repository->getLatest($limit));
    }

    /**
     * {@inheritDoc}
     */
    public function getFeatured(int $limit = 3): array
    {
        return $this->remember($this->buildListCacheKey('featured', ['limit' => $limit]), self::TTL_LIST, fn () => $this->repository->getFeatured($limit));
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(int $page = 1, int $perPage = 20): PaginatedResult
    {
        // Admin list - shorter TTL
        $cacheKey = $this->buildListCacheKey('all', [
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->remember($cacheKey, self::TTL_COUNT, fn () => $this->repository->findAll($page, $perPage));
    }

    /**
     * {@inheritDoc}
     */
    public function findByStatus(string $status, int $page = 1, int $perPage = 20): PaginatedResult
    {
        $cacheKey = $this->buildListCacheKey('by_status', [
            'status' => $status,
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->remember($cacheKey, self::TTL_LIST, fn () => $this->repository->findByStatus($status, $page, $perPage));
    }

    /**
     * {@inheritDoc}
     */
    public function save(Article $article): void
    {
        $this->repository->save($article);
        $this->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Uuid $id): void
    {
        $this->repository->delete($id);
        $this->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function slugExists(string $slug, ?Uuid $excludeId = null): bool
    {
        // Validation queries are not cached
        return $this->repository->slugExists($slug, $excludeId);
    }

    /**
     * {@inheritDoc}
     */
    public function countByStatus(): array
    {
        return $this->remember('articles:count:by_status', self::TTL_COUNT, fn () => $this->repository->countByStatus());
    }

    /**
     * {@inheritDoc}
     */
    public function syncTags(Uuid $articleId, array $tagIds): void
    {
        $this->repository->syncTags($articleId, $tagIds);
        $this->flush();
    }

    /**
     * Build cache key for single entity.
     */
    private function buildEntityCacheKey(Uuid $id): string
    {
        return "articles:entity:{$id->getValue()}";
    }

    /**
     * Build cache key for slug lookup.
     */
    private function buildSlugCacheKey(string $slug): string
    {
        return "articles:slug:{$slug}";
    }

    /**
     * Build cache key for list queries.
     *
     * @param  array<string, mixed>  $params
     */
    private function buildListCacheKey(string $type, array $params = []): string
    {
        $paramsString = empty($params) ? '' : ':'.md5(serialize($params));

        return "articles:list:{$type}{$paramsString}";
    }

    /**
     * Remember a value under the articles tag.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function remember(string $key, int $ttl, callable $callback): mixed
    {
        return $this->cache->tags([self::TAG])->remember($key, $ttl, $callback);
    }

    /**
     * Invalidate the entire articles cache (entity, slug, list, count).
     */
    private function flush(): void
    {
        $this->cache->tags([self::TAG])->flush();
    }
}
