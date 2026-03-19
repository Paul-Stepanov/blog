<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Settings\Entities\SiteSetting;
use App\Domain\Settings\Repositories\SettingsRepositoryInterface;
use App\Domain\Settings\ValueObjects\SettingKey;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\Uuid;
use App\Infrastructure\Persistence\Eloquent\Mappers\SiteSettingMapper;
use App\Infrastructure\Persistence\Eloquent\Models\SiteSettingModel;

/**
 * Eloquent implementation of Settings Repository.
 */
final readonly class EloquentSettingsRepository implements SettingsRepositoryInterface
{
    public function __construct(
        private SiteSettingMapper $mapper,
    ) {}

    /**
     * @inheritDoc
     */
    public function findById(Uuid $id): ?SiteSetting
    {
        $model = $this->findModelById($id);

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getById(Uuid $id): SiteSetting
    {
        $model = $this->findModelById($id);

        if ($model === null) {
            throw EntityNotFoundException::forEntity('SiteSetting', $id);
        }

        return $this->mapper->toDomain($model);
    }

    /**
     * @inheritDoc
     */
    public function findByKey(SettingKey $key): ?SiteSetting
    {
        $model = SiteSettingModel::query()
            ->where('key', $key->getValue())
            ->first();

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getByKey(SettingKey $key): SiteSetting
    {
        $model = SiteSettingModel::query()
            ->where('key', $key->getValue())
            ->first();

        if ($model === null) {
            throw EntityNotFoundException::byIdentifier('SiteSetting', $key->getValue(), 'key');
        }

        return $this->mapper->toDomain($model);
    }

    /**
     * @inheritDoc
     */
    public function getValue(SettingKey $key, mixed $default = null): mixed
    {
        $setting = $this->findByKey($key);

        return $setting !== null
            ? $setting->getValue()->getValue()
            : $default;
    }

    /**
     * @inheritDoc
     */
    public function findAll(): array
    {
        $models = SiteSettingModel::query()
            ->orderBy('key', 'asc')
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function findByGroup(string $group): array
    {
        $models = SiteSettingModel::query()
            ->where('group', $group)
            ->orderBy('key', 'asc')
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function getAllAsKeyValue(): array
    {
        $models = SiteSettingModel::query()
            ->orderBy('key', 'asc')
            ->get();

        $result = [];
        foreach ($models as $model) {
            $setting = $this->mapper->toDomain($model);
            $result[$model->key] = $setting->getValue()->getValue();
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getGroupAsKeyValue(string $group): array
    {
        $models = SiteSettingModel::query()
            ->where('group', $group)
            ->orderBy('key', 'asc')
            ->get();

        $result = [];
        foreach ($models as $model) {
            $setting = $this->mapper->toDomain($model);
            $result[$model->key] = $setting->getValue()->getValue();
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function exists(SettingKey $key): bool
    {
        return SiteSettingModel::query()
            ->where('key', $key->getValue())
            ->exists();
    }

    /**
     * @inheritDoc
     */
    public function save(SiteSetting $setting): void
    {
        $data = $this->mapper->toEloquent($setting);

        SiteSettingModel::query()->updateOrCreate(
            ['uuid' => $data['uuid']],
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function saveMany(array $settings): void
    {
        foreach ($settings as $setting) {
            $this->save($setting);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(Uuid $id): void
    {
        SiteSettingModel::query()
            ->where('uuid', $id->getValue())
            ->delete();
    }

    /**
     * @inheritDoc
     */
    public function deleteByKey(SettingKey $key): void
    {
        SiteSettingModel::query()
            ->where('key', $key->getValue())
            ->delete();
    }

    /**
     * @inheritDoc
     */
    public function deleteByGroup(string $group): void
    {
        SiteSettingModel::query()
            ->where('group', $group)
            ->delete();
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return SiteSettingModel::query()->count();
    }

    /**
     * @inheritDoc
     */
    public function countByGroup(string $group): int
    {
        return SiteSettingModel::query()
            ->where('group', $group)
            ->count();
    }

    /**
     * Find model by UUID.
     */
    private function findModelById(Uuid $id): ?SiteSettingModel
    {
        return SiteSettingModel::query()
            ->where('uuid', $id->getValue())
            ->first();
    }
}