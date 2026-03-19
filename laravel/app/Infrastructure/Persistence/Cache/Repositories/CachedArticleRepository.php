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
 * Read operations are cached, write operations invalidate cache.
 */
final readonly class CachedArticleRepository implements ArticleRepositoryInterface
{
    private const TTL_READ = 3600; // 1 hour
    private const TTL_LIST = 1800; // 30 minutes
    private const TTL_COUNT = 300; // 5 minutes

    public function __construct(
        private ArticleRepositoryInterface $repository,
        private CacheRepository $cache,
    ) {}

    /**
     * @inheritDoc
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

        return $this->cache->remember($cacheKey, self::TTL_LIST, fn() => $this->repository->findByFilters($filters, $page, $perPage));
    }

    /**
     * @inheritDoc
     */
    public function findById(Uuid $id): ?Article
    {
        $cacheKey = $this->buildEntityCacheKey($id);

        return $this->cache->remember($cacheKey, self::TTL_READ, fn() => $this->repository->findById($id));
    }

    /**
     * @inheritDoc
     */
    public function getById(Uuid $id): Article
    {
        $cacheKey = $this->buildEntityCacheKey($id);

        return $this->cache->remember($cacheKey, self::TTL_READ, fn() => $this->repository->getById($id));
    }

    /**
     * @inheritDoc
     */
    public function findBySlug(string $slug): ?Article
    {
        $cacheKey = $this->buildSlugCacheKey($slug);

        return $this->cache->remember($cacheKey, self::TTL_READ, fn() => $this->repository->findBySlug($slug));
    }

    /**
     * @inheritDoc
     */
    public function getBySlug(string $slug): Article
    {
        $cacheKey = $this->buildSlugCacheKey($slug);

        return $this->cache->remember($cacheKey, self::TTL_READ, fn() => $this->repository->getBySlug($slug));
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.DeprecatedMethod)
     */
    public function findPublished(int $page = 1, int $perPage = 12): PaginatedResult
    {
        $cacheKey = $this->buildListCacheKey('published', [
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->cache->remember($cacheKey, self::TTL_LIST, fn() => $this->repository->findPublished($page, $perPage));
    }

    /**
     * @inheritDoc
     */
    public function findByCategory(string $categorySlug, int $page = 1, int $perPage = 12): PaginatedResult
    {
        $cacheKey = $this->buildListCacheKey('by_category', [
            'category' => $categorySlug,
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->cache->remember($cacheKey, self::TTL_LIST, fn() => $this->repository->findByCategory($categorySlug, $page, $perPage));
    }

    /**
     * @inheritDoc
     */
    public function findByTag(string $tagSlug, int $page = 1, int $perPage = 12): PaginatedResult
    {
        $cacheKey = $this->buildListCacheKey('by_tag', [
            'tag' => $tagSlug,
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->cache->remember($cacheKey, self::TTL_LIST, fn() => $this->repository->findByTag($tagSlug, $page, $perPage));
    }

    /**
     * @inheritDoc
     */
    public function findByAuthor(Uuid $authorId, int $page = 1, int $perPage = 12): PaginatedResult
    {
        $cacheKey = $this->buildListCacheKey('by_author', [
            'author' => $authorId->getValue(),
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->cache->remember($cacheKey, self::TTL_LIST, fn() => $this->repository->findByAuthor($authorId, $page, $perPage));
    }

    /**
     * @inheritDoc
     */
    public function search(string $query, int $page = 1, int $perPage = 12): PaginatedResult
    {
        // Search results are not cached - queries vary too much
        return $this->repository->search($query, $page, $perPage);
    }

    /**
     * @inheritDoc
     */
    public function getLatest(int $limit = 5): array
    {
        $cacheKey = $this->buildListCacheKey('latest', ['limit' => $limit]);

        return $this->cache->remember($cacheKey, self::TTL_LIST, fn() => $this->repository->getLatest($limit));
    }

    /**
     * @inheritDoc
     */
    public function getFeatured(int $limit = 3): array
    {
        $cacheKey = $this->buildListCacheKey('featured', ['limit' => $limit]);

        return $this->cache->remember($cacheKey, self::TTL_LIST, fn() => $this->repository->getFeatured($limit));
    }

    /**
     * @inheritDoc
     */
    public function findAll(int $page = 1, int $perPage = 20): PaginatedResult
    {
        // Admin list - shorter TTL
        $cacheKey = $this->buildListCacheKey('all', [
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->cache->remember($cacheKey, self::TTL_COUNT, fn() => $this->repository->findAll($page, $perPage));
    }

    /**
     * @inheritDoc
     */
    public function findByStatus(string $status, int $page = 1, int $perPage = 20): PaginatedResult
    {
        $cacheKey = $this->buildListCacheKey('by_status', [
            'status' => $status,
            'page' => $page,
            'perPage' => $perPage,
        ]);

        return $this->cache->remember($cacheKey, self::TTL_LIST, fn() => $this->repository->findByStatus($status, $page, $perPage));
    }

    /**
     * @inheritDoc
     */
    public function save(Article $article): void
    {
        $this->repository->save($article);
        $this->invalidateArticleCache($article);
    }

    /**
     * @inheritDoc
     */
    public function delete(Uuid $id): void
    {
        $article = $this->repository->findById($id);
        $this->repository->delete($id);

        if ($article !== null) {
            $this->invalidateArticleCache($article);
        }

        // Also invalidate by ID directly
        $this->cache->forget($this->buildEntityCacheKey($id));
    }

    /**
     * @inheritDoc
     */
    public function slugExists(string $slug, ?Uuid $excludeId = null): bool
    {
        // Validation queries are not cached
        return $this->repository->slugExists($slug, $excludeId);
    }

    /**
     * @inheritDoc
     */
    public function countByStatus(): array
    {
        $cacheKey = 'articles:count:by_status';

        return $this->cache->remember($cacheKey, self::TTL_COUNT, fn() => $this->repository->countByStatus());
    }

    /**
     * @inheritDoc
     */
    public function syncTags(Uuid $articleId, array $tagIds): void
    {
        $this->repository->syncTags($articleId, $tagIds);

        // Invalidate article cache
        $this->cache->forget($this->buildEntityCacheKey($articleId));

        // Invalidate list caches (tags changed)
        $this->invalidateListCaches();
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
     */
    private function buildListCacheKey(string $type, array $params = []): string
    {
        $paramsString = empty($params) ? '' : ':' . md5(serialize($params));

        return "articles:list:{$type}{$paramsString}";
    }

    /**
     * Invalidate all cache for a specific article.
     */
    private function invalidateArticleCache(Article $article): void
    {
        // Invalidate by ID
        $this->cache->forget($this->buildEntityCacheKey($article->getId()));

        // Invalidate by slug
        $this->cache->forget($this->buildSlugCacheKey($article->getSlug()->getValue()));

        // Invalidate list caches
        $this->invalidateListCaches();
    }

    /**
     * Invalidate all list caches.
     */
    private function invalidateListCaches(): void
    {
        // Use cache tags if supported (Redis, Memcached)
        // For file/database cache, we rely on TTL
        // In production with Redis, use: $this->cache->tags(['articles'])->flush();

        // Invalidate known list keys
        $this->cache->forget('articles:count:by_status');
    }
}