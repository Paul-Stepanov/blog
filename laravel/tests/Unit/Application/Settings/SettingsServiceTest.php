<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Settings;

use App\Application\Settings\Services\SettingsService;
use App\Domain\Settings\Repositories\SettingsRepositoryInterface;
use App\Domain\Settings\ValueObjects\SettingKey;
use App\Domain\Settings\ValueObjects\SettingValue;
use App\Infrastructure\Persistence\Eloquent\Models\SiteSettingModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Settings caching and batch-write behaviour.
 */
final class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_many_uses_a_single_upsert_statement(): void
    {
        $service = app(SettingsService::class);

        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $service->setMany([
            'site.title' => 'New Title',
            'site.tagline' => 'Tagline',
        ]);

        $inserts = array_filter(
            $queries,
            static fn (string $sql): bool => stripos($sql, 'insert') === 0
        );

        $this->assertCount(1, $inserts, 'setMany must batch-write in a single INSERT (upsert)');
    }

    public function test_settings_cache_is_invalidated_after_save(): void
    {
        $repo = app(SettingsRepositoryInterface::class);

        SiteSettingModel::factory()->create([
            'key' => 'site.title',
            'value' => 'Original',
            'type' => 'string',
        ]);

        // Prime the cache with the first read.
        $this->assertSame('Original', $repo->getAllAsKeyValue()['site.title'] ?? null);

        // Mutate through the cached decorator.
        $setting = $repo->findByKey(SettingKey::fromString('site.title'));
        $setting->updateValue(SettingValue::fromMixed('Updated'));
        $repo->save($setting);

        // A subsequent read must observe the new value (cache was flushed on save).
        $this->assertSame('Updated', $repo->getAllAsKeyValue()['site.title'] ?? null);
    }
}
