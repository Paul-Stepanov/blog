# Research: HTTP Layer (API)

> **Автор:** Researcher
> **Дата:** 2025-03-19
> **Статус:** Завершён

## Executive Summary

Исследование HTTP Layer для блога на PHP/Laravel. Проект использует DDD архитектуру с четким разделением на Domain, Application и Infrastructure слои. Анализ охватывает Application Services, DTOs, Commands/Queries, Repository Interfaces и Value Objects для определения требований к REST API.

---

## 1. Application Services Analysis

### 1.1 ArticleService (`laravel/app/Application/Article/Services/ArticleService.php`)

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|------------|----------|
| `createArticle()` | `CreateArticleCommand` | `ArticleDTO` | Создаёт черновик статьи |
| `publishArticle()` | `PublishArticleCommand` | `ArticleDTO` | Публикует черновик |
| `archiveArticle()` | `ArchiveArticleCommand` | `ArticleDTO` | Архивирует статью |
| `getArticleBySlug()` | `GetArticleBySlugQuery` | `ArticleDTO` | Получает статью по slug |
| `getPublishedArticles()` | `GetPublishedArticlesQuery` | `PaginatedResult<ArticleListDTO>` | Список опубликованных с фильтрацией |
| `getArticlesForAdmin()` | `search?, status?, categoryId?, page, perPage` | `PaginatedResult<ArticleListDTO>` | Все статьи для админки |

**Примечание:** Методы используют CQRS паттерн с Commands и Queries.

### 1.2 ContactService (`laravel/app/Application/Contact/Services/ContactService.php`)

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|------------|----------|
| `sendMessage()` | `SendMessageCommand` | `ContactMessageDTO` | Отправляет контактное сообщение |
| `markAsRead()` | `messageId: string` | `void` | Помечает как прочитанное |
| `markAsUnread()` | `messageId: string` | `void` | Помечает как непрочитанное |

### 1.3 AuthenticationService (`laravel/app/Application/User/Services/AuthenticationService.php`)

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|------------|----------|
| `login()` | `AuthRequest` | `UserDTO|null` | Аутентификация по credentials |
| `getUserById()` | `userId: string` | `UserDTO|null` | Получить пользователя по ID |
| `canAuthenticate()` | `userId: string` | `bool` | Проверка возможности входа |

### 1.4 MediaService (`laravel/app/Application/Media/Services/MediaService.php`)

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|------------|----------|
| `uploadFile()` | `filename, content, mimeType, size, width?, height?, altText` | `MediaFileDTO` | Загрузка файла |
| `updateAltText()` | `fileId, altText` | `MediaFileDTO` | Обновить alt текст |
| `renameFile()` | `fileId, newFilename` | `MediaFileDTO` | Переименовать файл |
| `deleteFile()` | `fileId` | `void` | Удалить файл |
| `getFile()` | `fileId` | `MediaFileDTO` | Получить файл по ID |

### 1.5 SettingsService (`laravel/app/Application/Settings/Services/SettingsService.php`)

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|------------|----------|
| `getSetting()` | `keyString` | `SettingsDTO` | Получить настройку по ключу |
| `getValue()` | `keyString, default?` | `mixed` | Получить значение настройки |
| `setSetting()` | `keyString, value` | `SettingsDTO` | Создать/обновить настройку |
| `setMany()` | `settings: array` | `array<SettingsDTO>` | Массовое обновление |
| `getAllSettings()` | - | `array<SettingsDTO>` | Все настройки |
| `getSettingsByGroup()` | `group` | `array<SettingsDTO>` | Настройки по группе |
| `getAllAsKeyValue()` | - | `array<string, mixed>` | Все как key-value |
| `getGroupAsKeyValue()` | `group` | `array<string, mixed>` | Группа как key-value |
| `deleteSetting()` | `keyString` | `void` | Удалить настройку |
| `deleteGroup()` | `group` | `void` | Удалить группу |
| `exists()` | `keyString` | `bool` | Проверка существования |

---

## 2. DTOs & Commands/Queries

### 2.1 DTOs

#### ArticleDTO (`laravel/app/Application/Article/DTOs/ArticleDTO.php`)

```php
final readonly class ArticleDTO {
    public string $id;              // UUID
    public string $title;
    public string $slug;
    public string $content;         // HTML
    public string $excerpt;
    public string $status;          // draft|published|archived
    public ?string $categoryId;     // UUID
    public ?string $authorId;       // UUID
    public ?string $coverImageId;   // UUID
    public ?string $publishedAt;    // ISO 8601
    public string $createdAt;       // ISO 8601
    public string $updatedAt;       // ISO 8601
    public int $wordCount;
    public int $readingTime;
}
```

**Хелперы:** `isPublished()`, `isDraft()`, `isArchived()`

#### ArticleListDTO (`laravel/app/Application/Article/DTOs/ArticleListDTO.php`)

```php
final readonly class ArticleListDTO {
    public string $id;
    public string $title;
    public string $slug;
    public string $excerpt;
    public string $status;
    public ?string $categoryId;
    public ?string $publishedAt;
    public int $readingTime;
}
```

#### ContactMessageDTO (`laravel/app/Application/Contact/DTOs/ContactMessageDTO.php`)

```php
final readonly class ContactMessageDTO {
    public string $id;
    public string $name;
    public string $email;
    public string $subject;
    public string $message;
    public string $ipAddress;
    public string $userAgent;
    public bool $isRead;
    public string $createdAt;
    public string $updatedAt;
}
```

#### UserDTO (`laravel/app/Application/User/DTOs/UserDTO.php`)

```php
final readonly class UserDTO {
    public string $id;
    public string $name;
    public string $email;
    public string $role;            // admin|editor|author
    public string $createdAt;
    public string $updatedAt;
}
```

**Хелперы:** `isAdmin()`, `canPublish()`, `getObfuscatedEmail()`

#### MediaFileDTO (`laravel/app/Application/Media/DTOs/MediaFileDTO.php`)

```php
final readonly class MediaFileDTO {
    public string $id;
    public string $filename;
    public string $path;
    public string $publicUrl;
    public string $mimeType;
    public int $sizeBytes;
    public string $sizeHuman;
    public ?int $width;
    public ?int $height;
    public string $altText;
    public bool $isImage;
    public string $createdAt;
    public string $updatedAt;
}
```

**Хелперы:** `isVideo()`, `isDocument()`, `getAspectRatio()`

#### SettingsDTO (`laravel/app/Application/Settings/DTOs/SettingsDTO.php`)

```php
final readonly class SettingsDTO {
    public string $id;
    public string $key;             // например 'site.title'
    public string $group;           // например 'site'
    public mixed $value;
    public string $valueType;       // string|integer|float|boolean|json
    public string $valueString;
    public string $createdAt;
    public string $updatedAt;
}
```

**Хелперы:** `isBoolean()`, `isJson()`, `asBoolean()`, `asArray()`

### 2.2 Commands

#### CreateArticleCommand

```php
final readonly class CreateArticleCommand {
    public string $title;
    public string $content;
    public ?Slug $slug;
    public ?Uuid $categoryId;
    public ?Uuid $authorId;
}
```

#### PublishArticleCommand

```php
final readonly class PublishArticleCommand {
    public Uuid $articleId;
}
```

#### ArchiveArticleCommand

```php
final readonly class ArchiveArticleCommand {
    public Uuid $articleId;
}
```

#### SendMessageCommand

```php
final readonly class SendMessageCommand {
    public string $name;
    public Email $email;
    public string $subject;
    public string $message;
    public IPAddress $ipAddress;
    public string $userAgent;
}
```

### 2.3 Queries

#### GetArticleBySlugQuery

```php
final readonly class GetArticleBySlugQuery {
    public Slug $slug;
}
```

#### GetPublishedArticlesQuery

```php
final readonly class GetPublishedArticlesQuery {
    public int $page = 1;
    public int $perPage = 15;
    public ?string $categoryId = null;
    public ?string $searchTerm = null;
}
```

### 2.4 AuthRequest

```php
final readonly class AuthRequest {
    public Email $email;
    public string $password;
}
```

---

## 3. Repository Interfaces Summary

### 3.1 ArticleRepositoryInterface

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|------------|----------|
| `findByFilters()` | `ArticleFilters, page, perPage` | `PaginatedResult<Article>` | Универсальный фильтр |
| `findById()` | `Uuid` | `?Article` | Найти по ID (optional) |
| `getById()` | `Uuid` | `Article` | Получить по ID (throws) |
| `findBySlug()` | `string` | `?Article` | Найти по slug (optional) |
| `getBySlug()` | `string` | `Article` | Получить по slug (throws) |
| `findPublished()` | `page, perPage` | `PaginatedResult<Article>` | **deprecated** |
| `findByCategory()` | `categorySlug, page, perPage` | `PaginatedResult<Article>` | Статьи категории |
| `findByTag()` | `tagSlug, page, perPage` | `PaginatedResult<Article>` | Статьи тега |
| `findByAuthor()` | `authorId, page, perPage` | `PaginatedResult<Article>` | Статьи автора |
| `search()` | `query, page, perPage` | `PaginatedResult<Article>` | Поиск |
| `getLatest()` | `limit` | `array<Article>` | Последние |
| `getFeatured()` | `limit` | `array<Article>` | Избранные |
| `findAll()` | `page, perPage` | `PaginatedResult<Article>` | Все (для админки) |
| `findByStatus()` | `status, page, perPage` | `PaginatedResult<Article>` | По статусу |
| `save()` | `Article` | `void` | Сохранить |
| `delete()` | `Uuid` | `void` | Удалить |
| `slugExists()` | `slug, excludeId?` | `bool` | Проверка уникальности |
| `countByStatus()` | - | `array` | Статистика |
| `syncTags()` | `articleId, tagIds` | `void` | Синхронизировать теги |

### 3.2 CategoryRepositoryInterface

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|------------|----------|
| `findById()` | `Uuid` | `?Category` | Найти по ID |
| `getById()` | `Uuid` | `Category` | Получить по ID |
| `findBySlug()` | `string` | `?Category` | Найти по slug |
| `getBySlug()` | `string` | `Category` | Получить по slug |
| `findAll()` | - | `array<Category>` | Все категории |
| `findAllWithArticleCount()` | - | `array{category, count}` | С счётчиком статей |
| `getWithPublishedArticles()` | - | `array<Category>` | С опубликованными статьями |
| `save()` | `Category` | `void` | Сохранить |
| `delete()` | `Uuid` | `void` | Удалить |
| `slugExists()` | `slug, excludeId?` | `bool` | Проверка уникальности |
| `hasArticles()` | `id` | `bool` | Есть ли статьи |
| `count()` | - | `int` | Общее количество |

### 3.3 TagRepositoryInterface

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|------------|----------|
| `findById()` | `Uuid` | `?Tag` | Найти по ID |
| `getById()` | `Uuid` | `Tag` | Получить по ID |
| `findBySlug()` | `string` | `?Tag` | Найти по slug |
| `findByIds()` | `Uuid[]` | `array<Tag>` | Найти несколько по ID |
| `findBySlugs()` | `string[]` | `array<Tag>` | Найти несколько по slug |
| `findAll()` | - | `array<Tag>` | Все теги |
| `findAllOrderedByName()` | - | `array<Tag>` | Все по алфавиту |
| `getWithArticleCount()` | - | `array{tag, count}` | С счётчиком |
| `getPopular()` | `limit` | `array<Tag>` | Популярные |
| `getForArticle()` | `articleId` | `array<Tag>` | Теги статьи |
| `save()` | `Tag` | `void` | Сохранить |
| `delete()` | `Uuid` | `void` | Удалить |
| `slugExists()` | `slug, excludeId?` | `bool` | Проверка |
| `count()` | - | `int` | Количество |

### 3.4 UserRepositoryInterface

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|------------|----------|
| `findById()` | `Uuid` | `?User` | Найти по ID |
| `getById()` | `Uuid` | `User` | Получить по ID |
| `findByEmail()` | `string` | `?User` | Найти по email |
| `getByEmailOrFail()` | `string` | `User` | Получить по email |
| `findAll()` | `page, perPage` | `PaginatedResult<User>` | Все |
| `findByRole()` | `UserRole, page, perPage` | `PaginatedResult<User>` | По роли |
| `search()` | `query, page, perPage` | `PaginatedResult<User>` | Поиск |
| `getAdmins()` | - | `array<User>` | Администраторы |
| `getEditors()` | - | `array<User>` | Редакторы |
| `save()` | `User` | `void` | Сохранить |
| `delete()` | `Uuid` | `void` | Удалить |
| `emailExists()` | `email, excludeId?` | `bool` | Проверка |
| `count()` | - | `int` | Количество |
| `countByRole()` | - | `array<string, int>` | Статистика |

### 3.5 MediaRepositoryInterface

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|------------|----------|
| `findById()` | `Uuid` | `?MediaFile` | Найти по ID |
| `findByPath()` | `string` | `?MediaFile` | Найти по пути |
| `findAll()` | `page, perPage` | `PaginatedResult<MediaFile>` | Все |
| `findImages()` | `page, perPage` | `PaginatedResult<MediaFile>` | Только изображения |
| `findDocuments()` | `page, perPage` | `PaginatedResult<MediaFile>` | Только документы |
| `findVideos()` | `page, perPage` | `PaginatedResult<MediaFile>` | Только видео |
| `findByMimeType()` | `MimeType, page, perPage` | `PaginatedResult<MediaFile>` | По MIME типу |
| `search()` | `query, page, perPage` | `PaginatedResult<MediaFile>` | Поиск |
| `getRecent()` | `limit` | `array<MediaFile>` | Последние |
| `getUnused()` | `page, perPage` | `PaginatedResult<MediaFile>` | Неиспользуемые |
| `save()` | `MediaFile` | `void` | Сохранить |
| `delete()` | `Uuid` | `void` | Удалить |
| `count()` | - | `int` | Количество |
| `countByType()` | - | `array<string, int>` | По типам |
| `getTotalSize()` | - | `int` | Общий размер |

### 3.6 ContactRepositoryInterface

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|------------|----------|
| `findById()` | `Uuid` | `?ContactMessage` | Найти по ID |
| `findAll()` | `page, perPage` | `PaginatedResult<ContactMessage>` | Все |
| `findUnread()` | `page, perPage` | `PaginatedResult<ContactMessage>` | Непрочитанные |
| `findRead()` | `page, perPage` | `PaginatedResult<ContactMessage>` | Прочитанные |
| `search()` | `query, page, perPage` | `PaginatedResult<ContactMessage>` | Поиск |
| `getRecent()` | `limit` | `array<ContactMessage>` | Последние |
| `findByEmail()` | `email` | `array<ContactMessage>` | По email |
| `findByIpAddress()` | `ipAddress` | `array<ContactMessage>` | По IP |
| `save()` | `ContactMessage` | `void` | Сохранить |
| `delete()` | `Uuid` | `void` | Удалить |
| `count()` | - | `int` | Количество |
| `countUnread()` | - | `int` | Непрочитанные |
| `countByDate()` | `from, to` | `array<string, int>` | По датам |

### 3.7 SettingsRepositoryInterface

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|------------|----------|
| `findById()` | `Uuid` | `?SiteSetting` | Найти по ID |
| `findByKey()` | `SettingKey` | `?SiteSetting` | Найти по ключу |
| `getValue()` | `SettingKey, default?` | `mixed` | Получить значение |
| `findAll()` | - | `array<SiteSetting>` | Все |
| `findByGroup()` | `group` | `array<SiteSetting>` | По группе |
| `getAllAsKeyValue()` | - | `array<string, mixed>` | Все как key-value |
| `getGroupAsKeyValue()` | `group` | `array<string, mixed>` | Группа как key-value |
| `exists()` | `SettingKey` | `bool` | Существует |
| `save()` | `SiteSetting` | `void` | Сохранить |
| `saveMany()` | `SiteSetting[]` | `void` | Массовое сохранение |
| `delete()` | `Uuid` | `void` | Удалить по ID |
| `deleteByKey()` | `SettingKey` | `void` | Удалить по ключу |
| `deleteByGroup()` | `group` | `void` | Удалить группу |
| `count()` | - | `int` | Количество |
| `countByGroup()` | `group` | `int` | По группе |

---

## 4. Value Objects Validation Rules

### 4.1 Slug (`laravel/app/Domain/Article/ValueObjects/Slug.php`)

| Правило | Значение |
|---------|----------|
| **Pattern** | `/^[a-z0-9]+(?:-[a-z0-9]+)*$/` |
| **Min length** | 1 символ |
| **Max length** | 255 символов |
| **Allowed** | Строчные буквы, цифры, дефисы |
| **Forbidden** | Начало/конец с дефиса, последовательные дефисы |
| **Generation** | `Slug::fromTitle($title)` - транслитерация + очистка |

### 4.2 Email (`laravel/app/Domain/Contact/ValueObjects/Email.php`)

| Правило | Значение |
|---------|----------|
| **Validation** | `FILTER_VALIDATE_EMAIL` |
| **Max length** | 254 символа |
| **Case** | Всегда lowercase |
| **Empty** | Не допускается |

### 4.3 ArticleStatus (Enum)

| Значение | Описание |
|----------|----------|
| `draft` | Черновик |
| `published` | Опубликована |
| `archived` | В архиве |

**Методы:** `isPublic()`, `isEditable()`, `canBePublished()`, `canBeArchived()`

### 4.4 UserRole (Enum)

| Значение | Описание | Права |
|----------|----------|-------|
| `admin` | Администратор | Полный доступ |
| `editor` | Редактор | Управление контентом, публикация |
| `author` | Автор | Создание/редактирование своих статей |

**Методы:** `isAdmin()`, `canManageContent()`, `canPublish()`, `canManageUsers()`

### 4.5 Password (`laravel/app/Domain/User/ValueObjects/Password.php`)

| Правило | Значение |
|---------|----------|
| **Min length** | 8 символов |
| **Max length** | 72 символа (bcrypt limit) |
| **Lowercase** | Минимум 1 |
| **Uppercase** | Минимум 1 |
| **Digit** | Минимум 1 |
| **Hashing** | `PASSWORD_BCRYPT` with cost 12 |

### 4.6 MimeType (`laravel/app/Domain/Media/ValueObjects/MimeType.php`)

**Allowed types:**
- Images: `image/jpeg`, `image/png`, `image/gif`, `image/webp`, `image/svg+xml`, `image/avif`
- Documents: `application/pdf`, `text/plain`, `text/markdown`
- Video: `video/mp4`, `video/webm`

**Methods:** `isAllowed()`, `isImage()`, `isVideo()`, `isDocument()`

### 4.7 FilePath (`laravel/app/Domain/Media/ValueObjects/FilePath.php`)

| Правило | Значение |
|---------|----------|
| **Max length** | 500 символов |
| **Directory traversal** | Запрещено (`..`) |
| **Absolute paths** | Запрещено (должен быть относительным) |
| **Generation** | `FilePath::generateForUpload($dir, $filename)` - формат: `{dir}/{YYYY/MM/DD}/{sanitized_name}_{hash}.{ext}` |

### 4.8 SettingKey (`laravel/app/Domain/Settings/ValueObjects/SettingKey.php`)

| Правило | Значение |
|---------|----------|
| **Pattern** | `/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)*$/` |
| **Format** | `group.name` или `group.subgroup.name` |
| **Max length** | 100 символов |
| **Known keys** | site.*, seo.*, social.*, analytics.*, features.* |

---

## 5. Endpoints Definition

### 5.1 Public API (без аутентификации)

#### Articles

```
GET  /api/articles
     Query: page, per_page, category, tag, search
     Response: PaginatedResult<ArticleListResource>

GET  /api/articles/{slug}
     Response: ArticleResource
```

#### Categories

```
GET  /api/categories
     Response: CategoryCollectionResource

GET  /api/categories/{slug}
     Response: CategoryResource (со статьями)
```

#### Tags

```
GET  /api/tags
     Response: TagCollectionResource

GET  /api/tags/{slug}
     Response: TagResource (со статьями)
```

#### Contact

```
POST /api/contact
     Body: { name, email, subject, message }
     Response: ContactMessageResource (201)
```

#### Settings

```
GET  /api/settings
     Query: group?
     Response: SettingsCollectionResource
```

#### Health

```
GET  /api/health
     Response: { status, timestamp, checks }
```

### 5.2 Admin API (требует аутентификацию)

#### Authentication

```
POST /api/admin/auth/login
     Body: { email, password }
     Response: { user, token/cookie }

POST /api/admin/auth/logout
     Response: 204

GET  /api/admin/user
     Response: UserResource (текущий пользователь)
```

#### Articles (Admin)

```
GET    /api/admin/articles
       Query: page, per_page, status, category, search
       Response: PaginatedResult<ArticleListResource>

POST   /api/admin/articles
       Body: { title, content, slug?, categoryId?, authorId?, tags?[] }
       Response: ArticleResource (201)

GET    /api/admin/articles/{id}
       Response: ArticleResource

PUT    /api/admin/articles/{id}
       Body: { title, content, slug?, categoryId?, coverImageId? }
       Response: ArticleResource

DELETE /api/admin/articles/{id}
       Response: 204

POST   /api/admin/articles/{id}/publish
       Response: ArticleResource

POST   /api/admin/articles/{id}/archive
       Response: ArticleResource

PUT    /api/admin/articles/{id}/tags
       Body: { tagIds: string[] }
       Response: ArticleResource
```

#### Categories (Admin)

```
GET    /api/admin/categories
       Response: CategoryCollectionResource

POST   /api/admin/categories
       Body: { name, slug?, description? }
       Response: CategoryResource (201)

GET    /api/admin/categories/{id}
       Response: CategoryResource

PUT    /api/admin/categories/{id}
       Body: { name, slug?, description? }
       Response: CategoryResource

DELETE /api/admin/categories/{id}
       Response: 204
```

#### Tags (Admin)

```
GET    /api/admin/tags
       Query: sort?
       Response: TagCollectionResource

POST   /api/admin/tags
       Body: { name, slug? }
       Response: TagResource (201)

GET    /api/admin/tags/{id}
       Response: TagResource

PUT    /api/admin/tags/{id}
       Body: { name, slug? }
       Response: TagResource

DELETE /api/admin/tags/{id}
       Response: 204
```

#### Media (Admin)

```
GET    /api/admin/media
       Query: page, per_page, type, search
       Response: PaginatedResult<MediaFileResource>

POST   /api/admin/media
       Body: multipart/form-data (file, alt_text?)
       Response: MediaFileResource (201)

GET    /api/admin/media/{id}
       Response: MediaFileResource

PUT    /api/admin/media/{id}
       Body: { alt_text?, filename? }
       Response: MediaFileResource

DELETE /api/admin/media/{id}
       Response: 204

GET    /api/admin/media/recent
       Query: limit?
       Response: MediaFileCollectionResource

GET    /api/admin/media/unused
       Query: page, per_page
       Response: PaginatedResult<MediaFileResource>
```

#### Contact Messages (Admin)

```
GET    /api/admin/messages
       Query: page, per_page, status, search
       Response: PaginatedResult<ContactMessageResource>

GET    /api/admin/messages/{id}
       Response: ContactMessageResource

PUT    /api/admin/messages/{id}/read
       Response: ContactMessageResource

PUT    /api/admin/messages/{id}/unread
       Response: ContactMessageResource

DELETE /api/admin/messages/{id}
       Response: 204
```

#### Settings (Admin)

```
GET    /api/admin/settings
       Query: group?
       Response: SettingsCollectionResource

GET    /api/admin/settings/group/{group}
       Response: SettingsCollectionResource

PUT    /api/admin/settings
       Body: { key1: value1, key2: value2, ... }
       Response: SettingsCollectionResource

DELETE /api/admin/settings/{key}
       Response: 204

DELETE /api/admin/settings/group/{group}
       Response: 204
```

#### Users (Admin)

```
GET    /api/admin/users
       Query: page, per_page, role, search
       Response: PaginatedResult<UserResource>

GET    /api/admin/users/{id}
       Response: UserResource

PUT    /api/admin/users/{id}
       Body: { name, email?, role? }
       Response: UserResource

DELETE /api/admin/users/{id}
       Response: 204
```

---

## 6. Authentication (Laravel Sanctum)

### 6.1 Конфигурация

**Файл:** `laravel/config/sanctum.php`

| Параметр | Значение | Описание |
|----------|----------|----------|
| `stateful` | localhost, localhost:3000, ... | Домены для cookie-based auth |
| `guard` | `['web']` | Используемый guard |
| `expiration` | `null` | Сессия не истекает |
| `middleware` | authenticate_session, encrypt_cookies, validate_csrf_token | Middleware для SPA |

### 6.2 Стратегия для SPA

**Cookie-based authentication:**
- Sanctum использует Laravel session cookies
- CSRF защита включена
- Отсутствие API токенов в заголовках
- Авторизация через `auth:sanctum` middleware

**Workflow:**
1. Vue.js SPA отправляет POST `/api/admin/auth/login` с credentials
2. Server валидирует, создаёт session, возвращает cookie
3. Последующие запросы включают cookie автоматически
4. Server проверяет `auth:sanctum` middleware

### 6.3 Middleware для Admin API

```php
Route::middleware(['auth:sanctum'])->group(function () {
    // All admin routes
});
```

---

## 7. Request/Response Formats

### 7.1 Pagination Response

```json
{
  "items": [...],
  "meta": {
    "total": 100,
    "page": 1,
    "perPage": 15,
    "lastPage": 7,
    "hasMore": true
  }
}
```

### 7.2 Error Response

```json
{
  "message": "Validation error",
  "errors": {
    "email": ["Invalid email format"],
    "password": ["Password must be at least 8 characters"]
  }
}
```

### 7.3 Standard Resource Response

Все ресурсы должны использовать Laravel API Resources для согласованности.

---

## 8. Risks & Mitigations

| Риск | Митигация | Приоритет |
|------|-----------|----------|
| **SQL Injection в фильтрах** | Repository использует prepared statements; ArticleFilters экранирует LIKE спецсимволы | High |
| **XSS в контенте статей** | Экранирование при выводе; Content Security Policy | High |
| **CSRF на SPA** | Sanctum middleware включён; Vue.js должен отправлять XSRF-TOKEN cookie | High |
| **Загрузка вредоносных файлов** | MimeType VO white-list; ImageProcessor для валидации изображений | Medium |
| **Brute force на login** | Laravel throttle middleware; rate limiting | Medium |
| **Path traversal при загрузке** | FilePath VO блокирует `..`; абсолютные пути запрещены | High |
| **DoS через большие файлы** | Валидация размера; Content-Length проверка | Medium |
| **Утечка данных через ошибки** | Production: `display_errors=off`; логирование вместо вывода | High |
| **Unauthenticated admin access** | sanctum auth middleware на всех `/api/admin/*` маршрутах | High |
| **Slug collision** | Repository проверка `slugExists()`; генерация с hash | Low |

---

## 9. Open Questions

| Вопрос | Описание | Priority |
|--------|----------|----------|
| **User registration** | Нужно ли создавать endpoint для self-registration? | Low |
| **Password reset** | Нужно ли реализовать forgot password flow? | Medium |
| **Image thumbnails** | Создавать ли thumbs на загрузке или on-the-fly? | Medium |
| **Article versioning** | Нужен ли версионинг статей (draft history)? | Low |
| **Soft delete** | Использовать soft delete вместо archive? | Medium |
| **Rate limiting** | Какие лимиты для public API endpoints? | High |
| **API versioning** | Нужен ли `/api/v1/` префикс? | Low |
| **CORS policy** | Какие домены разрешены для API access? | High |
| **File size limits** | Максимальный размер upload для media? | Medium |
| **Bulk operations** | Нужны ли bulk delete/publish endpoints? | Low |

---

## 10. Dependencies Required

```json
{
  "require": {
    "laravel/sanctum": "^4.0"
  }
}
```

**Уже установлено:**
- Laravel framework
- Eloquent ORM
- Validation
- CSRF protection

---

## 11. Next Steps

1. **Design HTTP API Architecture** - создаёт структуру Controllers, Resources, Requests
2. **Create Implementation Plan** - детальный план разработки с задачами

---

## Приложение A: Существующая инфраструктура

### A.1 HealthController

`laravel/app/Infrastructure/Http/Controllers/Api/HealthController.php`

```php
GET /api/health
Response: {
  "status": "ok",
  "timestamp": "2025-03-19T12:00:00Z",
  "checks": {
    "database": true,
    "redis": true
  }
}
```

### A.2 Existing Routes

`laravel/routes/api.php`

```php
// Health check
Route::get('/health', HealthController::class);

// Placeholder routes (controllers need implementation)
Route::apiResource('articles', ArticleController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('tags', TagController::class);
Route::get('settings', [SettingsController::class, 'index']);
Route::post('contact', [ContactController::class, 'store']);
```

### A.3 Directory Structure

```
laravel/app/Infrastructure/Http/
├── Controllers/
│   └── Api/
│       ├── HealthController.php          (exists)
│       ├── ArticleController.php         (stub)
│       ├── CategoryController.php        (stub)
│       ├── TagController.php             (stub)
│       ├── ContactController.php         (stub)
│       └── SettingsController.php        (stub)
├── Requests/
│   └── Api/
│       ├── ArticleRequest.php            (needed)
│       ├── ContactRequest.php            (needed)
│       └── ...
└── Resources/
    ├── ArticleResource.php               (needed)
    ├── ArticleListResource.php           (needed)
    └── ...
```

---

**Research завершён**

**Файл:** `.claude/pipeline/research-http-api.md`

**Ключевые находки:**
- Полностью реализованный Application слой с CQRS паттерном
- 5 Services с чёткими операциями для всех доменов
- Value Objects с встроенной валидацией
- Repository Interfaces с find/get семантикой
- PaginatedResult для pagination
- Laravel Sanctum настроен для SPA с cookie-based auth

**Риски:** High: 5, Medium: 5, Low: 3

**Следующий шаг:** dev-architect для Design