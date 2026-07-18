<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\SiteSettingModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Дефолтные публичные настройки сайта (site.*, social.*).
 *
 * Idempotent: firstOrCreate по key — повторный запуск не перезаписывает
 * существующие значения (админка Фазы 9 правит, seeder не затирает).
 * Значения — разумные плейсхолдеры для демо лендинга; заменяются через админку.
 */
class SettingsSeeder extends Seeder
{
    /**
     * @return array<string, string> key => value
     */
    private function defaults(): array
    {
        return [
            'site.title' => 'Pavel Stepanov — блог',
            'site.description' => 'Заметки о backend-инженерии, PHP, Laravel и архитектуре распределённых систем.',
            'site.author' => 'Pavel Stepanov',
            'site.author_bio' => 'Backend-инженер с фокусом на PHP, Laravel и Domain-Driven Design. Пишу о чистой архитектуре, тестировании и производительности. Открыт к обсуждению проектов и сотрудничества.',
            'site.author_photo_url' => 'https://i.pravatar.cc/400?img=12',
            'site.email' => 'hello@example.com',
            'site.url' => 'http://localhost',
            'social.github' => 'https://github.com/',
            'social.twitter' => 'https://twitter.com/',
            'social.linkedin' => 'https://linkedin.com/',
        ];
    }

    public function run(): void
    {
        foreach ($this->defaults() as $key => $value) {
            SiteSettingModel::firstOrCreate(
                ['key' => $key],
                [
                    'uuid' => Str::uuid()->toString(),
                    'value' => $value,
                    'type' => 'string',
                ],
            );
        }
    }
}
