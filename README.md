# Blog

A personal blog built with **Laravel 13** and **Vue 3**, following **Domain-Driven Design** principles with an API-first architecture. Fully containerized with Docker.

## Tech Stack

| Layer | Technologies |
|-------|-------------|
| **Backend** | PHP 8.4, Laravel 13, PHPUnit 12, PHPStan, Laravel Pint |
| **Frontend** | Vue 3, TypeScript 5.9, Pinia, Vue Router 5, Vite 7, Vitest, Playwright |
| **Infrastructure** | Docker (nginx, PHP-FPM, PostgreSQL 17, Redis 7.4, Node 24) |
| **Auth** | Laravel Sanctum (SPA stateful authentication) |
| **Images** | Intervention Image |

## Architecture

The project uses **Domain-Driven Design** with three layers inside `laravel/app/`:

```
app/
├── Domain/           # Pure PHP, no framework dependencies
│   ├── Article/      # Article entity, ArticleStatus VO, slug, content
│   ├── Contact/      # Contact messages
│   ├── Media/        # Media file handling
│   ├── Settings/     # Site settings
│   ├── User/         # User entity, Email, Password, UserRole VOs
│   └── Shared/       # Base Entity, ValueObject, PaginatedResult, events
├── Application/      # Use case orchestration (CQRS-lite)
│   ├── Commands/     # Write operations (CreateArticle, PublishArticle, ...)
│   ├── Queries/      # Read operations (GetPublishedArticles, ...)
│   └── DTOs/         # Data transfer objects
└── Infrastructure/   # Laravel bindings
    ├── Persistence/  # Eloquent models, repositories, mappers
    ├── Http/         # Controllers (API + Admin)
    └── Storage/      # File storage adapters
```

**Key rules:**
- Domain layer has zero framework dependencies (`use Illuminate\...` is forbidden)
- Eloquent models live only in Infrastructure
- Entities use factory methods (`createDraft()`, `reconstitute()`)
- Dual keys: auto-increment `id` + UUID/slug for external references

## Quick Start

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) + Docker Compose
- [Make](https://www.gnu.org/software/make/) (usually pre-installed)

### Setup

```bash
# 1. Clone the repository
git clone https://github.com/Paul-Stepanov/blog.git
cd blog

# 2. Copy environment file
cp .env.example .env

# 3. Start the development environment
make dev
```

The app will be available at **http://localhost**.

### First Run

```bash
# Install PHP dependencies
make composer-install

# Generate application key
make artisan CMD="key:generate"

# Run database migrations
make migrate

# (Optional) Seed with sample data
make seed
```

## Available Commands

All commands are run through `make`. Type `make help` for the full list.

### Development

| Command | Description |
|---------|-------------|
| `make dev` | Start development environment |
| `make dev-full` | Start with Adminer (:8080) and Mailpit (:8025) |
| `make down` | Stop all containers |
| `make logs` | Follow container logs |

### Backend

| Command | Description |
|---------|-------------|
| `make composer-install` | Install PHP dependencies |
| `make migrate` | Run database migrations |
| `make migrate-fresh` | Fresh migration + seed |
| `make artisan CMD="..."` | Run any artisan command |
| `make shell` | Bash shell in PHP container |

### Frontend

| Command | Description |
|---------|-------------|
| `make npm-install` | Install Node dependencies |
| `make npm-dev` | Start Vite dev server (hot reload) |
| `make npm-build` | Production build |

### Testing & Quality

| Command | Description |
|---------|-------------|
| `make test` | Run PHPUnit tests |
| `make test-coverage` | Tests with coverage report |
| `make cs-fix` | Fix code style (Laravel Pint) |
| `make cs-check` | Check code style |
| `make stan` | PHPStan static analysis |

Frontend tests (inside Node container):
```bash
docker compose exec node npm run test:unit     # Vitest
docker compose exec node npm run test:e2e       # Playwright
docker compose exec node npm run lint            # ESLint + Oxlint
docker compose exec node npm run format          # Prettier
```

## API

REST API is available at `/api/`. All routes are defined in `laravel/routes/api.php`.

### Public Endpoints

| Method | Endpoint | Description | Rate Limit |
|--------|----------|-------------|------------|
| GET | `/api/health` | Health check | None |
| GET | `/api/articles` | List published articles | 60/min |
| GET | `/api/articles/{slug}` | Get article by slug | 60/min |
| GET | `/api/categories` | List categories with articles | 60/min |
| GET | `/api/categories/{slug}` | Get category by slug | 60/min |
| GET | `/api/tags` | List all tags | 60/min |
| GET | `/api/tags/popular` | Popular tags | 60/min |
| GET | `/api/tags/{slug}` | Get tag by slug | 60/min |
| GET | `/api/settings` | Public site settings | 60/min |
| POST | `/api/contact` | Submit contact form | 3/hour |

### Admin Endpoints

Admin routes at `/api/admin/*` require Sanctum authentication. Rate limit: 120/min.

## Project Structure

```
blog/
├── laravel/              # Backend (Laravel application)
│   ├── app/
│   │   ├── Domain/       # Domain layer (pure PHP)
│   │   ├── Application/  # Application layer (use cases)
│   │   └── Infrastructure/ # Infrastructure layer (Laravel)
│   ├── database/         # Migrations, seeders, factories
│   ├── routes/           # API routes
│   └── tests/            # PHPUnit tests (Unit + Feature)
├── frontend/             # Frontend (Vue 3 SPA)
│   ├── src/              # Vue components, stores, router
│   └── tests/            # Vitest + Playwright tests
├── docker/               # Docker configurations
│   ├── nginx/            # Nginx configs
│   └── php/              # PHP-FPM + Xdebug configs
├── docker-compose.yml    # Development environment
├── docker-compose.prod.yml # Production configuration
├── Makefile              # Development commands
└── .env.example          # Environment template
```

## License

This project is licensed under the [MIT License](LICENSE).