<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Cache\Repositories;

use App\Domain\Article\Entities\Category;
use App\Domain\Article\Repositories\CategoryRepositoryInterface;
use App\Domain\Shared\Uuid;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Cached decorator for Category Repository.
 *
 * Only static lookups (find/get by id/slug, findAll, count) are cached under
 * the "categories" tag. Dynamic methods that depend on articles
 * (findAllWithArticleCount, getWithPublishedArticles, hasArticles) bypass the
 * cache so article-derived data never goes stale without a cross-tag flush.
 */
final readonly class CachedCategoryRepository implements CategoryRepositoryInterface
{
    private const string TAG = 'categories';

    private const int TTL_READ = 3600; // 1 hour

    private const int TTL_LIST = 1800; // 30 minutes

    private const int TTL_COUNT = 300; // 5 minutes

    public function __construct(
        private CategoryRepositoryInterface $repository,
        private CacheRepository $cache,
    ) {}

    public function findById(Uuid $id): ?Category
    {
        return $this->remember("categories:id:{$id->getValue()}", self::TTL_READ, fn () => $this->repository->findById($id));
    }

    public function getById(Uuid $id): Category
    {
        return $this->remember("categories:id:{$id->getValue()}", self::TTL_READ, fn () => $this->repository->getById($id));
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->remember("categories:slug:{$slug}", self::TTL_READ, fn () => $this->repository->findBySlug($slug));
    }

    public function getBySlug(string $slug): Category
    {
        return $this->remember("categories:slug:{$slug}", self::TTL_READ, fn () => $this->repository->getBySlug($slug));
    }

    public function findAll(): array
    {
        return $this->remember('categories:all', self::TTL_LIST, fn () => $this->repository->findAll());
    }

    public function findAllWithArticleCount(): array
    {
        // Depends on articles - not cached to avoid cross-tag staleness
        return $this->repository->findAllWithArticleCount();
    }

    public function getWithPublishedArticles(?int $limit = 100): array
    {
        // Depends on articles - not cached
        return $this->repository->getWithPublishedArticles($limit);
    }

    public function save(Category $category): void
    {
        $this->repository->save($category);
        $this->flush();
    }

    public function delete(Uuid $id): void
    {
        $this->repository->delete($id);
        $this->flush();
    }

    public function slugExists(string $slug, ?Uuid $excludeId = null): bool
    {
        // Validation queries are not cached
        return $this->repository->slugExists($slug, $excludeId);
    }

    public function hasArticles(Uuid $id): bool
    {
        // Depends on articles - not cached
        return $this->repository->hasArticles($id);
    }

    public function count(): int
    {
        return $this->remember('categories:count', self::TTL_COUNT, fn () => $this->repository->count());
    }

    /**
     * Remember a value under the categories tag.
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
     * Invalidate the entire categories cache.
     */
    private function flush(): void
    {
        $this->cache->tags([self::TAG])->flush();
    }
}
