# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Блог на Laravel 13 + Vue 3 с DDD-архитектурой и API-first подходом. SPA-фронтенд (Vue/TypeScript/Vite) общается с REST API через nginx-прокси. Вся разработка ведётся в Docker.

## Technology Stack

- **Backend**: PHP 8.4, Laravel 13, PHPUnit 12, Laravel Pint, PHPStan
- **Frontend**: Vue 3, TypeScript 5.9, Pinia, Vue Router 5, Vite 7, Vitest, Playwright
- **Infrastructure**: Docker (nginx, PHP-FPM, PostgreSQL 17, Redis 7.4, Node 24)
- **Auth**: Laravel Sanctum (SPA stateful auth)
- **Image Processing**: Intervention Image

## Commands

Все команды выполняются через Docker. Запуск окружения: `make dev` (с инструментами: `make dev-full`).

### Backend

```bash
make test                          # Запуск всех тестов PHPUnit
make test-coverage                 # Тесты с покрытием
make cs-fix                        # Исправить стиль кода (Laravel Pint)
make cs-check                      # Проверить стиль кода
make stan                          # PHPStan анализ
make migrate                       # Миграции БД
make migrate-fresh                 # Миграции с нуля + сиды
make artisan CMD="config:clear"    # Произвольная artisan-команда
make shell                         # Bash в PHP-контейнере
```

Для запуска одного теста внутри контейнера:
```bash
docker compose exec app php artisan test --filter=TestName
docker compose exec app php vendor/bin/phpunit tests/Unit/Domain/ArticleTest.php
```

### Frontend

```bash
make npm-install                   # Установка зависимостей
make npm-dev                       # Vite dev-сервер
make npm-build                     # Production-сборка
make shell-node                    # Shell в Node-контейнере
```

Frontend-тесты и линтинг (внутри Node-контейнера):
```bash
docker compose exec node npm run test:unit
docker compose exec node npm run test:e2e
docker compose exec node npm run lint
docker compose exec node npm run format
```

## Architecture

Проект использует **Domain-Driven Design** с тремя слоями внутри `laravel/app/`:

### Domain (`App\Domain\`) — чистый PHP, без зависимостей от фреймворка

- **Entities**: `Article`, `Category`, `Tag`, `ContactMessage`, `MediaFile`, `SiteSetting`, `User` — наследуют `Entity` (UUID-идентичность, `releaseEvents()` для агрегатов)
- **Value Objects**: `Slug`, `ArticleContent`, `ArticleStatus` (backed enum с guard-методами переходов состояний), `Email`, `Uuid`, `Password`, `UserRole` и др. — наследуют `ValueObject`
- **Repository Interfaces**: по одному на bounded context (`ArticleRepositoryInterface`, `CategoryRepositoryInterface`, ...)
- **Domain Events**: `ArticlePublished`, `ArticleArchived`, `ContactMessageReceived`
- **Exceptions**: `DomainException` (abstract) → `EntityNotFoundException`, `ValidationException`
- **Shared**: `PaginatedResult<T>` с `map()` для трансформации типов

### Application (`App\Application\`) — оркестрация use cases

- **CQRS-lite**: `Commands` (CreateArticleCommand, PublishArticleCommand, ...) и `Queries` (GetPublishedArticlesQuery, GetArticleBySlugQuery, ...)
- **DTOs**: `ArticleDTO`, `ArticleListDTO`, `ContactMessageDTO`, ... — реализуют `DTOInterface`, используют `DTOFormattingTrait`
- **Application Services**: `ArticleService`, `ContactService`, `MediaService`, `SettingsService`, `AuthenticationService`

### Infrastructure (`App\Infrastructure\`) — привязка к Laravel

- **Eloquent Models** (`Persistence\Eloquent\Models\`): мост к БД, не используются за пределами Infrastructure
- **Repositories** (`Persistence\Eloquent\Repositories\`): Eloquent-реализации domain-интерфейсов
- **Cache Decorator**: `CachedArticleRepository` оборачивает любой `ArticleRepositoryInterface` (Redis, tag-based invalidation)
- **Mappers** (`Persistence\Eloquent\Mappers\`): преобразование Eloquent Model ↔ Domain Entity через `BaseMapper` trait
- **Custom Casts**: `UuidCast`, `SlugCast`, `EmailCast` и др. — мост Value Objects ↔ колонки БД
- **Controllers**: `Http\Controllers\Api\` (публичные), `Http\Controllers\Admin\` ( Sanctum-auth)
- **Storage**: `LocalStorageAdapter` + `InterventionImageProcessor`

## Key Architecture Rules

1. **Domain не зависит от фреймворка** — в `App\Domain\` нет `use Illuminate\...`. Entity используют Value Objects, а не Eloquent.
2. **Eloquent Models только в Infrastructure** — контроллеры получают domain-сущности через repository interfaces, а не Eloquent-модели.
3. **Создание через фабричные методы** — `Article::createDraft()` для новых сущностей, `Article::reconstitute()` для восстановления из хранилища.
4. **Двойные ключи** — автоинкрементный `id` + UUID для внешних ссылок (slug для URL).
5. **Статья как агрегат** — `ArticleStatus` enum с guard-методами (`canBePublished()`, `canBeArchived()`), доменные события собираются через `releaseEvents()`.

## API Structure

REST API в `laravel/routes/api.php`:
- **Public** (`/api/articles`, `/api/categories`, `/api/tags`, `/api/settings`) — throttle 60/min
- **Contact** (`POST /api/contact`) — throttle 3/hour
- **Admin** (`/api/admin/*`) — Sanctum auth, throttle 120/min
- **Health** (`/api/health`, `/up`) — без rate limiting

Обработка исключений настроена в `bootstrap/app.php`: `EntityNotFoundException` → 404, `DomainException` → 400, `ValidationException` → 422, `AuthenticationException` → 401.

## Testing

- PHPUnit с in-memory SQLite (`phpunit.xml`): suite `Unit` (`tests/Unit`) и `Feature` (`tests/Feature`)
- Frontend: Vitest (unit) + Playwright (E2E, Chromium/Firefox/WebKit)

## Coding Standards

- `declare(strict_types=1);` в начале каждого PHP-файла
- PSR-1 и PSR-12 для стиля кода
- Типизированные аргументы и возвращаемые значения
- PHPDoc для публичных методов и классов
- Принципы DRY, KISS, YAGNI

## Docker Profiles

- **По умолчанию** (`make dev`): app, web, db, redis, node
- **Tools** (`make dev-full`): + Adminer (:8080), Mailpit (:8025)