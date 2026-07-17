<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Cache\Repositories;

use App\Domain\Settings\Entities\SiteSetting;
use App\Domain\Settings\Repositories\SettingsRepositoryInterface;
use App\Domain\Settings\ValueObjects\SettingKey;
use App\Domain\Shared\Uuid;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Cached decorator for Settings Repository.
 *
 * Mirrors CachedArticleRepository: all reads are cached under the "settings"
 * cache tag, every write flushes the tag, and validation reads (exists) bypass
 * the cache so duplicate-key checks always see the latest data.
 */
final readonly class CachedSettingsRepository implements SettingsRepositoryInterface
{
    private const string TAG = 'settings';

    private const int TTL_READ = 3600; // 1 hour

    private const int TTL_LIST = 1800; // 30 minutes

    private const int TTL_COUNT = 300; // 5 minutes

    public function __construct(
        private SettingsRepositoryInterface $repository,
        private CacheRepository $cache,
    ) {}

    public function findById(Uuid $id): ?SiteSetting
    {
        return $this->remember("settings:id:{$id->getValue()}", self::TTL_READ, fn () => $this->repository->findById($id));
    }

    public function findByKey(SettingKey $key): ?SiteSetting
    {
        return $this->remember("settings:key:{$key->getValue()}", self::TTL_READ, fn () => $this->repository->findByKey($key));
    }

    public function getValue(SettingKey $key, mixed $default = null): mixed
    {
        return $this->remember("settings:value:{$key->getValue()}", self::TTL_READ, fn () => $this->repository->getValue($key, $default));
    }

    public function findAll(): array
    {
        return $this->remember('settings:all', self::TTL_LIST, fn () => $this->repository->findAll());
    }

    public function findByGroup(string $group): array
    {
        return $this->remember("settings:group:{$group}", self::TTL_LIST, fn () => $this->repository->findByGroup($group));
    }

    public function getAllAsKeyValue(): array
    {
        return $this->remember('settings:kv:all', self::TTL_LIST, fn () => $this->repository->getAllAsKeyValue());
    }

    public function getGroupAsKeyValue(string $group): array
    {
        return $this->remember("settings:kv:group:{$group}", self::TTL_LIST, fn () => $this->repository->getGroupAsKeyValue($group));
    }

    public function exists(SettingKey $key): bool
    {
        // Validation queries are not cached - duplicate-key checks must see latest data
        return $this->repository->exists($key);
    }

    public function save(SiteSetting $setting): void
    {
        $this->repository->save($setting);
        $this->flush();
    }

    public function saveMany(array $settings): void
    {
        $this->repository->saveMany($settings);
        $this->flush(); // single flush for the whole batch
    }

    public function delete(Uuid $id): void
    {
        $this->repository->delete($id);
        $this->flush();
    }

    public function deleteByKey(SettingKey $key): void
    {
        $this->repository->deleteByKey($key);
        $this->flush();
    }

    public function deleteByGroup(string $group): void
    {
        $this->repository->deleteByGroup($group);
        $this->flush();
    }

    public function count(): int
    {
        return $this->remember('settings:count', self::TTL_COUNT, fn () => $this->repository->count());
    }

    public function countByGroup(string $group): int
    {
        return $this->remember("settings:count:group:{$group}", self::TTL_COUNT, fn () => $this->repository->countByGroup($group));
    }

    /**
     * Remember a value under the settings tag.
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
     * Invalidate the entire settings cache.
     */
    private function flush(): void
    {
        $this->cache->tags([self::TAG])->flush();
    }
}
