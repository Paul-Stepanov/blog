# Research: Repository Pattern Issues

**Дата:** 2026-03-19
**Pipeline этап:** Research (1/7)

---

## Обзор

Исследование трёх критических проблем в реализации Repository Pattern для DDD архитектуры блога.

---

## Проблема 1: Null vs Exception при поиске сущности

### Контекст

```php
public function findById(Uuid $id): ?Tag
{
    $model = TagModel::query()->where('uuid', $id->getValue())->first();
    if ($model === null) {
        return null;  // Правильно ли это?
    }
    return $this->mapper->toDomain($model);
}
```

### Анализ авторитетных источников

#### Martin Fowler (Patterns of Enterprise Application Architecture)

> "A Repository mediates between the domain and data mapping layers, acting like an in-memory domain object collection."

Fowler различает два типа методов:
- **find()** - возвращает null если не найден (для опционального поиска)
- **get()** - выбрасывает исключение если не найден (для обязательного поиска)

#### Vaughn Vernon (Implementing Domain-Driven Design)

> "Use `repository().entityOfId(identity)` when you expect the entity to exist. If it doesn't, throw an exception. Use `repository().entityOfId(identity)` that returns `Option[Entity]` or null when the entity may or may not exist."

#### Eric Evans (Domain-Driven Design)

> "A Repository should provide the illusion of an in-memory collection of domain objects."

Evans подчёркивает, что семантика метода должна соответствовать намерению вызывающего кода.

### Практики в популярных фреймворках

| Фреймворк | find() | get() | Exception |
|-----------|--------|-------|-----------|
| **Doctrine ORM** | `find()` → `?Entity` | `findOrFail()` → `Entity` | `EntityNotFoundException` |
| **Laravel Eloquent** | `find()` → `?Model` | `findOrFail()` → `Model` | `ModelNotFoundException` |
| **Spring Data** | `findById()` → `Optional` | `getById()` → `Entity` | `NoSuchElementException` |
| **Symfony Doctrine** | `find()` → `?Entity` | `findOneBy()` → `?Entity` | Custom exception |

### Рекомендации

1. **Разделять методы по семантике:**
   - `findById()` - возвращает `?Entity` (опциональный поиск)
   - `getById()` - возвращает `Entity` или выбрасывает `EntityNotFoundException` (обязательный поиск)

2. **Использовать exception только когда:**
   - Сущность **обязана** существовать по бизнес-логике
   - Вызывающий код не готов обработать null
   - Ошибка является действительно исключительной ситуацией

3. **Не использовать exception когда:**
   - Отсутствие сущности - нормальная ситуация
   - Вызывающий код должен обработать оба случая
   - Для валидации (slugExists, emailExists)

### Предлагаемое решение

```php
interface TagRepositoryInterface
{
    // Опциональный поиск - возвращает null если не найден
    public function findById(Uuid $id): ?Tag;

    // Обязательный поиск - выбрасывает EntityNotFoundException
    public function getById(Uuid $id): Tag;

    // Валидация - всегда возвращает bool
    public function slugExists(string $slug, ?Uuid $excludeId = null): bool;
}
```

---

## Проблема 2: Вызов Eloquent Scopes

### Контекст

```php
// ОШИБКА: Method 'orderedByName' not found in Builder
$models = TagModel::query()
    ->orderedByName()
    ->get()
    ->all();
```

### Анализ

#### Почему не работает

1. `TagModel::query()` возвращает `Builder`, а не экземпляр `TagModel`
2. Local scopes в Eloquent вызываются **на экземпляре модели** или **через статический вызов**
3. Scope-методы - это инстанс-методы, а не методы Builder'а

#### Правильные способы вызова scopes

**Способ 1: Через статический метод модели (если scope глобальный)**
```php
// Scope определён в модели
public function scopeOrderedByName(Builder $query): Builder
{
    return $query->orderBy('name', 'asc');
}

// Вызов - через модель, а не через query()
$models = TagModel::orderedByName()->get()->all();
```

**Способ 2: Вызов scope через экземпляр модели**
```php
$model = new TagModel();
$models = $model->newQuery()
    ->scopes(['orderedByName'])
    ->get()
    ->all();
```

**Способ 3: Прямой вызов в репозитории (рекомендуется)**
```php
// В репозитории - не использовать scopes, а писать явно
$models = TagModel::query()
    ->orderBy('name', 'asc')
    ->get()
    ->all();
```

### Рекомендации

1. **Не использовать scopes в репозиториях** - репозиторий сам контролирует запросы
2. **Перенести query logic из Model в Repository** - Model должна быть пассивной
3. **Если scope нужен в нескольких местах** - вынести в отдельный QueryBuilder или trait

### Предлагаемое решение

```php
final readonly class EloquentTagRepository implements TagRepositoryInterface
{
    public function findAllOrderedByName(): array
    {
        // Прямой запрос в репозитории - без scopes
        $models = TagModel::query()
            ->orderBy('name', 'asc')
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }
}
```

**Удалить scopes из Models** - они дублируют логику репозитория.

---

## Проблема 3: Синхронизация many-to-many отношений

### Контекст

```php
public function syncForArticle(Uuid $articleId, array $tagIds): void
{
    // Note: This requires ArticleModel to be available
    // The actual sync is done via ArticleModel's tags relationship
    // This method is typically called from ArticleService which handles the sync
}
```

### Анализ DDD подхода

#### Vaughn Vernon (Implementing DDD)

> "Model every Aggregate as its own consistency boundary. Allow references to other Aggregates only by identity, not by full reference."

#### Ключевые принципы

1. **Aggregate Boundary** - Article и Tag - это разные Aggregates
2. **Identity Reference** - Aggregate может ссылаться на другой только по ID
3. **Invariant Protection** - Каждое Aggregate защищает свои инварианты

#### Кто владеет отношением article_tag?

**Анализ:**
- `Article` - Aggregate Root, который "имеет" теги
- `Tag` - отдельный Aggregate, не зависит от Articles
- `article_tag` - это **значение (Value)**, не Entity

**Вывод:** Отношение принадлежит **Article Aggregate**. Tag Aggregate не должен знать об Article.

### Где должна быть логика синхронизации?

| Подход | Проблема |
|--------|----------|
| В TagRepository | Нарушает Aggregate Boundary - Tag не должен знать об Article |
| В ArticleRepository | TagRepository циклически зависит от ArticleRepository |
| В ArticleService (Domain Service) | ✅ Правильный подход - Domain Service координирует несколько Aggregates |

### Рекомендации

1. **Удалить `syncForArticle()` из TagRepositoryInterface**
   - Это нарушает DDD принципы
   - Tag Aggregate не должен знать об Article

2. **Добавить `syncTags()` в ArticleRepositoryInterface**
   - Article владеет отношением
   - Репозиторий Article управляет его отношениями

3. **Или создать отдельный ArticleTagService (Domain Service)**
   - Если логика сложная
   - Координирует Article и Tag repositories

### Предлагаемое решение

**Вариант A: Через ArticleRepository (рекомендуется для простого случая)**

```php
interface ArticleRepositoryInterface
{
    // ... существующие методы

    /**
     * Sync tags for an article.
     *
     * @param Uuid[] $tagIds
     */
    public function syncTags(Uuid $articleId, array $tagIds): void;
}
```

**Вариант B: Через Domain Service (для сложной логики)**

```php
final readonly class ArticleTagService
{
    public function __construct(
        private ArticleRepositoryInterface $articles,
        private TagRepositoryInterface $tags,
    ) {}

    public function syncTagsForArticle(Uuid $articleId, array $tagIds): void
    {
        // 1. Validate article exists
        $article = $this->articles->getById($articleId);

        // 2. Validate tags exist
        $tags = $this->tags->findByIds($tagIds);
        if (count($tags) !== count($tagIds)) {
            throw new InvalidArgumentException('Some tags not found');
        }

        // 3. Sync through ArticleRepository
        $this->articles->syncTags($articleId, $tagIds);
    }
}
```

---

## Итоговые рекомендации

1. **Разделять find() и get()** - разная семантика, разное поведение
2. **Не использовать scopes в репозиториях** - репозиторий контролирует запросы
3. **Владелец отношения синхронизирует** - ArticleRepository, не TagRepository
4. **Использовать Domain Service для координации** - если нужно работать с несколькими Aggregates