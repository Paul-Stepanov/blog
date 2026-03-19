# Research: Трансформация доменной модели в Eloquent модель

**Дата:** 2026-03-19
**Этап:** Research (дополнительный)
**Связано с:** Фаза 5 (Infrastructure Layer)

---

## Резюме

### Рекомендация

Использовать **Data Mapper паттерн** с отдельными Mapper классами для трансформации между доменными сущностями и Eloquent моделями.

**Ключевое решение:**
- Простые Value Objects (Uuid, Slug, Email, Enum) → **Custom Casts** в Eloquent моделях
- Сложные Value Objects (Timestamps, ArticleContent) → **ручной маппинг** в Mapper
- Отношения → **Uuid идентификаторы** в домене, eager loading в Repository

**Обоснование:**
1. Соответствие DDD принципам (чистый домен, независимость от инфраструктуры)
2. Тестируемость (Mapper можно тестировать отдельно от Eloquent)
3. Переиспользуемость (Mapper можно использовать в Import/Export, Jobs)
4. Производительность (Custom Casts для простых VO кэшируются Laravel)

---

## Анализ подходов

### Вариант 1: Маппинг внутри Eloquent модели

```php
class ArticleModel extends Model {
    public function toDomain(): Article {
        return Article::reconstitute(
            Uuid::fromString($this->uuid),
            $this->title,
            Slug::fromString($this->slug),
            // ...
        );
    }

    public static function fromDomain(Article $article): self {
        $model = new self();
        $model->uuid = $article->getId()->getValue();
        // ...
        return $model;
    }
}
```

**Плюсы:**
- Простая инкапсуляция
- Маппинг рядом с моделью БД
- Меньше файлов

**Минусы:**
- Нарушение SRP (модель занимается и БД, и маппингом)
- Сложно тестировать маппинг отдельно
- Домен зависит от инфраструктуры (Eloquent)
- Проблемы с массовыми операциями (bulk operations)

**Вердикт:** ❌ Не рекомендуется для DDD

---

### Вариант 2: Маппинг внутри Repository

```php
class EloquentArticleRepository implements ArticleRepositoryInterface {
    public function findById(Uuid $id): ?Article {
        $model = ArticleModel::where('uuid', $id->getValue())->first();
        if (!$model) return null;

        return $this->toDomain($model);
    }

    private function toDomain(ArticleModel $model): Article {
        // маппинг
    }

    private function toEloquent(Article $article): array {
        // маппинг
    }
}
```

**Плюсы:**
- Repository отвечает за всю персистентность
- Маппинг инкапсулирован в одном месте
- Меньше классов

**Минусы:**
- Repository становится слишком "толстым"
- Сложно переиспользовать маппинг
- Смешивание логики репозитория и маппинга

**Вердикт:** ⚠️ Допустимо для простых сущностей (Category, Tag)

---

### Вариант 3: Отдельный Mapper класс (РЕКОМЕНДУЕТСЯ)

```php
class ArticleMapper implements MapperInterface {
    public function toDomain(object $model): Article {
        assert($model instanceof ArticleModel);

        return Article::reconstitute(
            $this->mapUuid($model->uuid),
            $model->title,
            $this->mapSlug($model->slug),
            $this->mapContent($model->content),
            $model->excerpt,
            $this->mapStatus($model->status),
            $this->mapNullableUuid($model->category_id),
            $this->mapNullableUuid($model->author_id),
            $this->mapNullableUuid($model->cover_image_id),
            $this->mapPublishedAt($model->published_at),
            $this->mapTimestamps($model),
        );
    }

    public function toEloquent(object $entity): array {
        assert($entity instanceof Article);

        return [
            'uuid' => $entity->getId()->getValue(),
            'title' => $entity->getTitle(),
            'slug' => $entity->getSlug()->getValue(),
            'content' => $entity->getContent()->getValue(),
            'excerpt' => $entity->getExcerpt(),
            'status' => $entity->getStatus()->value,
            'category_id' => $entity->getCategoryId()?->getValue(),
            'author_id' => $entity->getAuthorId()?->getValue(),
            'cover_image_id' => $entity->getCoverImageId()?->getValue(),
            'published_at' => $entity->getPublishedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    public function toDomainCollection(array $models): array {
        return array_map([$this, 'toDomain'], $models);
    }
}
```

**Плюсы:**
- Четкое разделение ответственности
- Легко тестировать отдельно
- Можно переиспользовать (Import/Export, Jobs)
- Поддержка batch операций через `toDomainCollection()`
- Соответствие DDD принципам

**Минусы:**
- Больше классов в проекте
- Дополнительный уровень абстракции

**Вердикт:** ✅ РЕКОМЕНДУЕТСЯ для всех доменных сущностей

---

## Сравнение подходов

| Подход | Плюсы | Минусы | Рекомендация |
|--------|-------|--------|--------------|
| **В Eloquent модели** | Простая инкапсуляция, меньше файлов | Нарушение SRP, сложное тестирование | ❌ Не для DDD |
| **В Repository** | Меньше классов | "Толстый" Repository, сложное переиспользование | ⚠️ Для простых сущностей |
| **Отдельный Mapper** | Чёткое разделение, тестируемость | Больше файлов | ✅ РЕКОМЕНДУЕТСЯ |

---

## Рекомендуемая реализация

### Архитектура

```
app/Infrastructure/Persistence/
├── Casts/
│   ├── UuidCast.php
│   ├── SlugCast.php
│   ├── ArticleStatusCast.php
│   ├── UserRoleCast.php
│   └── EmailCast.php
├── Eloquent/
│   ├── Models/
│   │   ├── ArticleModel.php        # Чистая Eloquent модель
│   │   ├── CategoryModel.php
│   │   └── TagModel.php
│   ├── Mappers/
│   │   ├── MapperInterface.php
│   │   ├── BaseMapper.php          # Трейт с общими методами
│   │   ├── ArticleMapper.php
│   │   ├── CategoryMapper.php
│   │   └── TagMapper.php
│   └── Repositories/
│       ├── EloquentArticleRepository.php
│       ├── EloquentCategoryRepository.php
│       └── EloquentTagRepository.php
```

---

## Обработка Value Objects

### Простые Value Objects → Custom Casts

**UuidCast:**

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Casts;

use App\Domain\Shared\Uuid;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class UuidCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes): ?Uuid
    {
        if ($value === null) {
            return null;
        }

        return Uuid::fromString($value);
    }

    public function set($model, $key, $value, $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Uuid) {
            return $value->getValue();
        }

        return $value;
    }
}
```

**SlugCast:**

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Casts;

use App\Domain\Article\ValueObjects\Slug;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class SlugCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes): Slug
    {
        return Slug::fromString($value);
    }

    public function set($model, $key, $value, $attributes): string
    {
        if ($value instanceof Slug) {
            return $value->getValue();
        }

        return $value;
    }
}
```

**ArticleStatusCast:**

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Casts;

use App\Domain\Article\ValueObjects\ArticleStatus;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class ArticleStatusCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes): ArticleStatus
    {
        return ArticleStatus::fromString($value);
    }

    public function set($model, $key, $value, $attributes): string
    {
        if ($value instanceof ArticleStatus) {
            return $value->value;
        }

        return $value;
    }
}
```

**Использование в Eloquent модели:**

```php
class ArticleModel extends Model
{
    protected $casts = [
        'uuid' => UuidCast::class,
        'slug' => SlugCast::class,
        'status' => ArticleStatusCast::class,
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Теперь можно использовать:
    // $model->slug instanceof Slug
    // $model->status instanceof ArticleStatus
}
```

---

### Сложные Value Objects → Ручной маппинг в Mapper

**Timestamps:**

```php
class ArticleMapper implements MapperInterface
{
    private function mapTimestamps(ArticleModel $model): Timestamps
    {
        return Timestamps::fromStrings(
            $model->created_at->format('Y-m-d H:i:s'),
            $model->updated_at->format('Y-m-d H:i:s')
        );
    }

    // toEloquent не маппит timestamps - они управляются Eloquent автоматически
}
```

**ArticleContent:**

```php
class ArticleMapper implements MapperInterface
{
    private function mapContent(string $content): ArticleContent
    {
        return ArticleContent::fromString($content);
    }
}
```

---

## Обработка отношений

### В домене: только Uuid идентификаторы

```php
final class Article extends Entity
{
    private ?Uuid $categoryId;
    private ?Uuid $authorId;
    private ?Uuid $coverImageId;

    // Геттеры возвращают только Uuid, не загруженные сущности
    public function getCategoryId(): ?Uuid
    {
        return $this->categoryId;
    }
}
```

### В Repository: eager loading через with()

```php
class EloquentArticleRepository implements ArticleRepositoryInterface
{
    public function findByFilters(
        ArticleFilters $filters,
        int $page = 1,
        int $perPage = 12
    ): PaginatedResult {
        $query = ArticleModel::query()
            ->with(['category', 'author', 'coverImage', 'tags']) // Eager loading
            ->filterByFilters($filters);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $articles = $this->mapper->toDomainCollection(
            $paginator->items()
        );

        return new PaginatedResult(
            items: $articles,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
        );
    }
}
```

### Many-to-Many отношения

```php
interface ArticleRepositoryInterface
{
    public function attachTag(Uuid $articleId, Uuid $tagId): void;
    public function detachTag(Uuid $articleId, Uuid $tagId): void;
    public function syncTags(Uuid $articleId, array $tagIds): void;
}
```

---

## BaseMapper Trait

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Shared\{Timestamps, Uuid};
use Illuminate\Database\Eloquent\Model;

/**
 * Base mapper with common mapping logic.
 */
trait BaseMapper
{
    protected function mapUuid($uuid): Uuid
    {
        if ($uuid instanceof Uuid) {
            return $uuid;
        }

        return Uuid::fromString($uuid);
    }

    protected function mapNullableUuid($uuid): ?Uuid
    {
        if ($uuid === null) {
            return null;
        }

        return $this->mapUuid($uuid);
    }

    protected function getNullableUuidValue(?Uuid $uuid): ?string
    {
        return $uuid?->getValue();
    }

    protected function mapTimestamps(Model $model): Timestamps
    {
        return Timestamps::fromStrings(
            $model->created_at->format('Y-m-d H:i:s'),
            $model->updated_at->format('Y-m-d H:i:s')
        );
    }

    protected function formatDateTime(?\DateTimeImmutable $dateTime): ?string
    {
        return $dateTime?->format('Y-m-d H:i:s');
    }
}
```

---

## Полный пример: ArticleMapper

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Article\Entities\Article;
use App\Domain\Article\ValueObjects\{ArticleContent, ArticleStatus, Slug};
use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Domain\Shared\{Timestamps, Uuid};
use DateTimeImmutable;

/**
 * Mapper for Article entity <-> ArticleModel transformation.
 */
final class ArticleMapper implements MapperInterface
{
    use BaseMapper;

    public function toDomain(object $model): Article
    {
        assert($model instanceof ArticleModel);

        return Article::reconstitute(
            $this->mapUuid($model->uuid),
            $model->title,
            $this->mapSlug($model->slug),
            $this->mapContent($model->content),
            $model->excerpt,
            $this->mapStatus($model->status),
            $this->mapNullableUuid($model->category_id),
            $this->mapNullableUuid($model->author_id),
            $this->mapNullableUuid($model->cover_image_id),
            $this->mapPublishedAt($model->published_at),
            $this->mapTimestamps($model),
        );
    }

    public function toEloquent(object $entity): array
    {
        assert($entity instanceof Article);

        return [
            'uuid' => $entity->getId()->getValue(),
            'title' => $entity->getTitle(),
            'slug' => $entity->getSlug()->getValue(),
            'content' => $entity->getContent()->getValue(),
            'excerpt' => $entity->getExcerpt(),
            'status' => $entity->getStatus()->value,
            'category_id' => $this->getNullableUuidValue($entity->getCategoryId()),
            'author_id' => $this->getNullableUuidValue($entity->getAuthorId()),
            'cover_image_id' => $this->getNullableUuidValue($entity->getCoverImageId()),
            'published_at' => $this->formatDateTime($entity->getPublishedAt()),
        ];
    }

    /**
     * @param array<ArticleModel> $models
     * @return array<Article>
     */
    public function toDomainCollection(array $models): array
    {
        return array_map([$this, 'toDomain'], $models);
    }

    private function mapSlug($slug): Slug
    {
        if ($slug instanceof Slug) {
            return $slug;
        }

        return Slug::fromString($slug);
    }

    private function mapContent(string $content): ArticleContent
    {
        return ArticleContent::fromString($content);
    }

    private function mapStatus($status): ArticleStatus
    {
        if ($status instanceof ArticleStatus) {
            return $status;
        }

        return ArticleStatus::fromString($status);
    }

    private function mapPublishedAt($publishedAt): ?DateTimeImmutable
    {
        if ($publishedAt === null) {
            return null;
        }

        if ($publishedAt instanceof DateTimeImmutable) {
            return $publishedAt;
        }

        return new DateTimeImmutable($publishedAt);
    }
}
```

---

## Риски и митигация

| Риск | Уровень | Митигация |
|------|---------|-----------|
| **Сложность маппинга** | Medium | BaseMapper trait с общими методами |
| **Синхронизация домена и БД** | High | Миграции + Domain Layer вместе |
| **N+1 queries** | High | Eager loading в Repository |
| **Performance на коллекциях** | Low | Batch маппинг, pagination |
| **Дублирование кода** | Medium | BaseMapper trait |

---

## Проверочный список для Фазы 5

### Custom Casts
- [ ] UuidCast
- [ ] SlugCast
- [ ] ArticleStatusCast
- [ ] UserRoleCast
- [ ] EmailCast
- [ ] IPAddressCast
- [ ] MimeTypeCast

### Mappers
- [ ] BaseMapper trait
- [ ] ArticleMapper
- [ ] CategoryMapper
- [ ] TagMapper
- [ ] UserMapper
- [ ] MediaFileMapper
- [ ] ContactMessageMapper
- [ ] SiteSettingMapper

### Eloquent Models
- [ ] ArticleModel - добавить Custom Casts
- [ ] CategoryModel - добавить Custom Casts
- [ ] TagModel - добавить Custom Casts
- [ ] UserModel - добавить Custom Casts
- [ ] MediaFileModel - добавить Custom Casts
- [ ] ContactMessageModel - добавить Custom Casts
- [ ] SiteSettingModel - добавить Custom Casts

### Repositories
- [ ] EloquentArticleRepository
- [ ] EloquentCategoryRepository
- [ ] EloquentTagRepository
- [ ] EloquentUserRepository
- [ ] EloquentMediaRepository
- [ ] EloquentContactRepository
- [ ] EloquentSettingsRepository

### Тесты
- [ ] ArticleMapperTest
- [ ] CategoryMapperTest
- [ ] EloquentArticleRepositoryTest (интеграционный)

---

**Статус:** ✅ ЗАВЕРШЁН