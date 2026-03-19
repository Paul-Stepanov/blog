# Research: Альтернативы AWS S3 для хранения файлов

**Дата:** 2026-03-19
**Задача:** Исследовать альтернативы AWS S3 для хранения файлов в Laravel проекте
**Статус:** ЗАВЕРШЕНО

---

## Резюме

**Рекомендация:** Использовать **Laravel Local Storage** с возможностью миграции на **MinIO** в будущем.

**Обоснование:**
- Проект на одном сервере — Local Storage достаточно для блога
- `FileStorageInterface` уже абстрагирует хранение — swap будет лёгким
- Нет оверхеда на дополнительный контейнер
- Nginx уже настроен для раздачи `/storage`
- Простая настройка и отладка

**Migration path:** LocalStorageAdapter → MinIOStorageAdapter (без изменения Domain/Application слоёв)

---

## Сравнение решений

| Характеристика | Local Storage | MinIO | S3 |
|----------------|---------------|-------|-----|
| **Настройка** | ✅ Встроен в Laravel | ⚠️ Docker контейнер | ❌ Недоступен |
| **Производительность** | ✅ Высокая | ⚠️ Хорошая (+ оверхед) | ✅ Высокая |
| **Масштабируемость** | ❌ Один сервер | ✅ Кластер | ✅ Cloud |
| **Signed URLs** | ⚠️ Кастомная реализация | ✅ Из коробки | ✅ Из коробки |
| **Ресурсы** | ✅ Минимум | ⚠️ RAM/CPU | ✅ Cloud |
| **CDN** | ❌ Нет | ⚠️ Возможен | ✅ Cloudflare |
| **Backup** | ⚠️ Ручной | ⚠️ Ручной | ✅ Встроен |
| **Стоимость** | ✅ Бесплатно | ✅ Бесплатно | ❌ Платно |

### Local Storage — Преимущества
- ✅ Встроен в Laravel (не требует dependencies)
- ✅ Простая настройка (`storage:link` symbolic link)
- ✅ Высокая производительность (нет network latency)
- ✅ Легкая отладка (файлы на диске)
- ✅ Нет дополнительных Docker контейнеров

### Local Storage — Недостатки
- ❌ Нет встроенных signed URLs (требуется кастомная реализация)
- ❌ Ограниченная масштабируемость (один сервер)
- ❌ Нет CDN из коробки (можно добавить Cloudflare)

### MinIO — Преимущества
- ✅ S3-совместимый API (можно использовать Laravel S3 driver)
- ✅ Signed URLs из коробки
- ✅ Масштабируемость (кластер MinIO)
- ✅ Возможность CDN в будущем (Cloudflare + MinIO)

### MinIO — Недостатки
- ❌ Дополнительный контейнер (больше ресурсов)
- ❌ Сложнее настройка (credentials, buckets, policies)
- ❌ Overkill для одного блога

---

## Рекомендуемая реализация

### Архитектура

```
app/
├── Domain/
│   └── Media/
│       ├── Services/
│       │   └── FileStorageInterface.php        # ✅ Уже существует
│       └── ValueObjects/
│           └── FilePath.php                    # ✅ Уже существует
├── Application/
│   └── Media/
│       ├── Services/
│       │   ├── MediaService.php                # ✅ Уже существует
│       │   └── ImageProcessingService.php      # ⚠️ Нужен
│       └── DTOs/
│           └── MediaFileDTO.php                # ✅ Уже существует
└── Infrastructure/
    └── Storage/
        ├── LocalStorageAdapter.php             # ⚠️ Нужен
        ├── ImageProcessor.php                  # ⚠️ Нужен
        └── MinIOStorageAdapter.php             # 📋 Future (резерв)
```

### Composer зависимости

```json
{
  "require": {
    "intervention/image": "^3.0"
  }
}
```

---

## Docker конфигурация

### docker-compose.yml изменения

```yaml
services:
  app:
    volumes:
      - storage_data:/var/www/html/storage/app  # Persistence для файлов

volumes:
  storage_data:
    driver: local
```

---

## Laravel Filesystem Config

### config/filesystems.php

```php
<?php

declare(strict_types=1);

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'private' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'visibility' => 'private',
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
```

---

## Nginx конфигурация

### Раздача публичных файлов

```nginx
# Public storage files
location /storage {
    alias /var/www/html/storage/app/public;
    expires 30d;
    add_header Cache-Control "public, immutable";
    access_log off;

    # Разрешённые типы файлов
    location ~* \.(jpg|jpeg|png|gif|webp|svg|ico|pdf)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Запретить PHP в storage
    location ~* \.php$ {
        deny all;
    }
}
```

### Приватные файлы через X-Accel-Redirect

```nginx
# Private files - только через Laravel
location /private-files {
    internal;
    alias /var/www/html/storage/app/private/;
    expires 1h;
}
```

---

## LocalStorageAdapter Implementation

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Domain\Media\Services\FileStorageInterface;
use App\Domain\Media\ValueObjects\FilePath;
use App\Domain\Media\ValueObjects\MimeType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

final class LocalStorageAdapter implements FileStorageInterface
{
    public function __construct(
        private readonly string $publicDisk = 'public',
        private readonly string $privateDisk = 'private'
    ) {}

    public function store(UploadedFile $file, FilePath $path, bool $public = true): bool
    {
        $disk = $public ? $this->publicDisk : $this->privateDisk;

        return Storage::disk($disk)->putFileAs(
            dirname($path->toString()),
            $file,
            basename($path->toString())
        ) !== false;
    }

    public function delete(FilePath $path, bool $public = true): bool
    {
        $disk = $public ? $this->publicDisk : $this->privateDisk;

        return Storage::disk($disk)->delete($path->toString());
    }

    public function exists(FilePath $path, bool $public = true): bool
    {
        $disk = $public ? $this->publicDisk : $this->privateDisk;

        return Storage::disk($disk)->exists($path->toString());
    }

    public function getPublicUrl(FilePath $path): string
    {
        return Storage::url($path->toString());
    }

    public function getPrivateUrl(FilePath $path, int $expiresMinutes = 60): string
    {
        // Laravel Signed Route для приватных файлов
        return URL::temporarySignedRoute(
            'files.download',
            now()->addMinutes($expiresMinutes),
            ['path' => base64_encode($path->toString())]
        );
    }

    public function getMimeType(FilePath $path, bool $public = true): MimeType
    {
        $disk = $public ? $this->publicDisk : $this->privateDisk;
        $mimeType = Storage::disk($disk)->mimeType($path->toString());

        return MimeType::fromString($mimeType);
    }

    public function getSize(FilePath $path, bool $public = true): int
    {
        $disk = $public ? $this->publicDisk : $this->privateDisk;

        return Storage::disk($disk)->size($path->toString());
    }
}
```

---

## Image Processing Service

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Domain\Media\ValueObjects\FilePath;
use App\Domain\Media\ValueObjects\ImageDimensions;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

final class ImageProcessor
{
    private readonly ImageManager $manager;

    public function __construct()
    {
        $this->manager = ImageManager::gd();
    }

    public function resize(
        FilePath $sourcePath,
        FilePath $targetPath,
        int $maxWidth,
        int $maxHeight
    ): bool {
        $image = $this->manager->read($sourcePath->toString());
        $image->scaleDown(width: $maxWidth, height: $maxHeight);

        return $image->save($targetPath->toString()) !== false;
    }

    public function convertToWebP(
        FilePath $sourcePath,
        FilePath $targetPath,
        int $quality = 85
    ): bool {
        $image = $this->manager->read($sourcePath->toString());

        return $image->toWebp(quality: $quality)->save($targetPath->toString()) !== false;
    }

    public function getDimensions(FilePath $path): ImageDimensions
    {
        $image = $this->manager->read($path->toString());

        return ImageDimensions::fromWidthAndHeight(
            $image->width(),
            $image->height()
        );
    }

    public function optimize(FilePath $path, int $quality = 85): bool
    {
        $image = $this->manager->read($path->toString());

        return $image->save($path->toString(), quality: $quality) !== false;
    }
}
```

---

## Controller для приватных файлов

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Media\Services\FileStorageInterface;
use App\Domain\Media\ValueObjects\FilePath;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FileDownloadController
{
    public function __construct(
        private readonly FileStorageInterface $storage
    ) {}

    public function download(Request $request, string $encodedPath): Response|StreamedResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired link');
        }

        $path = FilePath::fromString(base64_decode($encodedPath));

        if (!$this->storage->exists($path, public: false)) {
            abort(404, 'File not found');
        }

        // X-Accel-Redirect для Nginx (эффективная отдача)
        return response()->streamDownload(
            fn() => $this->storage->stream($path, public: false),
            basename($path->toString()),
            [
                'Content-Type' => $this->storage->getMimeType($path, public: false)->toString(),
                'X-Accel-Redirect' => '/private-files/' . $path->toString(),
            ]
        );
    }
}
```

---

## Риски и митигация

| Риск | Уровень | Митигация |
|------|---------|-----------|
| Потеря файлов при redeploy | High | Docker volume для storage/app |
| Несанкционированный доступ | High | Signed routes + validation |
| Переполнение диска | Medium | Monitoring + cleanup job |
| Медленная отдача файлов | Medium | Nginx caching + X-Accel-Redirect |
| Нет CDN | Low | Cloudflare перед Nginx |

---

## Backup стратегия

### Скрипт backup

```bash
#!/bin/bash
# backup-storage.sh

BACKUP_DIR="/backups/storage"
DATE=$(date +%Y%m%d_%H%M%S)
SOURCE="/var/www/html/storage/app"

# Incremental backup с rsync
rsync -av --delete \
    --link-dest="$BACKUP_DIR/latest" \
    "$SOURCE/" \
    "$BACKUP_DIR/$DATE/"

# Update latest symlink
ln -sfn "$BACKUP_DIR/$DATE" "$BACKUP_DIR/latest"

# Remove backups older than 30 days
find "$BACKUP_DIR" -maxdepth 1 -type d -mtime +30 -exec rm -rf {} \;
```

### Cron job

```
0 2 * * * /usr/local/bin/backup-storage.sh >> /var/log/backup.log 2>&1
```

---

## Migration Path на MinIO

Если в будущем понадобится MinIO:

1. Добавить контейнер в docker-compose.yml
2. Установить `league/flysystem-aws-s3-v3`
3. Создать MinIOStorageAdapter (реализует тот же FileStorageInterface)
4. Изменить binding в ServiceProvider
5. Мигрировать файлы через rsync/scp

**Domain/Application слои не требуют изменений!**

---

## Следующие шаги

1. Добавить `intervention/image` в composer.json
2. Добавить Docker volume для storage в docker-compose.yml
3. Создать LocalStorageAdapter
4. Создать ImageProcessor
5. Настроить Nginx для X-Accel-Redirect
6. Создать маршрут для приватных файлов
7. Написать тесты для LocalStorageAdapter