<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Cache\Repositories;

use App\Domain\Article\Entities\Tag;
use App\Domain\Article\Repositories\TagRepositoryInterface;
use App\Domain\Shared\Uuid;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Cached decorator for Tag Repository.
 *
 * Only static lookups (find/get by id/slug, findAll, findAllOrderedByName, count)
 * are cached under the "tags" tag. Dynamic methods that depend on articles
 * (getWithArticleCount, getPopular, getForArticle) bypass the cache so
 * article-derived data never goes stale without a cross-tag flush.
 */
final readonly class CachedTagRepository implements TagRepositoryInterface
{
    private const string TAG = 'tags';

    private const int TTL_READ = 3600; // 1 hour

    private const int TTL_LIST = 1800; // 30 minutes

    private const int TTL_COUNT = 300; // 5 minutes

    public function __construct(
        private TagRepositoryInterface $repository,
        private CacheRepository $cache,
    ) {}

    public function findById(Uuid $id): ?Tag
    {
        return $this->remember("tags:id:{$id->getValue()}", self::TTL_READ, fn () => $this->repository->findById($id));
    }

    public function getById(Uuid $id): Tag
    {
        return $this->remember("tags:id:{$id->getValue()}", self::TTL_READ, fn () => $this->repository->getById($id));
    }

    public function findBySlug(string $slug): ?Tag
    {
        return $this->remember("tags:slug:{$slug}", self::TTL_READ, fn () => $this->repository->findBySlug($slug));
    }

    public function findByIds(array $ids): array
    {
        return $this->remember($this->buildIdsKey('ids', $ids), self::TTL_LIST, fn () => $this->repository->findByIds($ids));
    }

    public function findBySlugs(array $slugs): array
    {
        return $this->remember($this->buildSlugsKey($slugs), self::TTL_LIST, fn () => $this->repository->findBySlugs($slugs));
    }

    public function findAll(): array
    {
        return $this->remember('tags:all', self::TTL_LIST, fn () => $this->repository->findAll());
    }

    public function findAllOrderedByName(?int $limit = 100): array
    {
        return $this->remember("tags:ordered:{$limit}", self::TTL_LIST, fn () => $this->repository->findAllOrderedByName($limit));
    }

    public function getWithArticleCount(): array
    {
        // Depends on articles - not cached
        return $this->repository->getWithArticleCount();
    }

    public function getPopular(int $limit = 10): array
    {
        // Depends on articles - not cached
        return $this->repository->getPopular($limit);
    }

    public function getForArticle(Uuid $articleId): array
    {
        // Depends on articles - not cached
        return $this->repository->getForArticle($articleId);
    }

    public function save(Tag $tag): void
    {
        $this->repository->save($tag);
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

    public function count(): int
    {
        return $this->remember('tags:count', self::TTL_COUNT, fn () => $this->repository->count());
    }

    /**
     * Build cache key for an id list lookup.
     *
     * @param  Uuid[]  $ids
     */
    private function buildIdsKey(string $type, array $ids): string
    {
        $values = array_map(static fn (Uuid $id): string => $id->getValue(), $ids);
        sort($values);

        return "tags:{$type}:".md5(implode('|', $values));
    }

    /**
     * Build cache key for a slug list lookup.
     *
     * @param  string[]  $slugs
     */
    private function buildSlugsKey(array $slugs): string
    {
        $values = $slugs;
        sort($values);

        return 'tags:slugs:'.md5(implode('|', $values));
    }

    /**
     * Remember a value under the tags tag.
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
     * Invalidate the entire tags cache.
     */
    private function flush(): void
    {
        $this->cache->tags([self::TAG])->flush();
    }
}
