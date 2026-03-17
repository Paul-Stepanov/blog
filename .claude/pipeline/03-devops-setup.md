# DevOps Setup: Laravel Blog

**Дата:** 2026-03-17
**Этап:** DevOps Setup (3/7)
**Основано на:** 01-research.md, 02-design.md

---

## Обзор

Docker инфраструктура для блога на стеке Laravel (PHP 8.3) + Vue.js 3 + PostgreSQL 17 + Redis 7.

**Ключевые решения:**
- Multi-stage Dockerfile для оптимизации production образов
- Раздельные конфигурации Nginx для development и production
- Health checks для всех критических сервисов
- Makefile для упрощения управления окружением

---

## Созданные файлы

| Файл | Описание |
|------|----------|
| `docker/php/Dockerfile` | Multi-stage build (base, development, builder, production) |
| `docker/php/php.ini` | Базовая конфигурация PHP |
| `docker/php/local.ini` | Настройки для разработки (Xdebug) |
| `docker/php/production.ini` | Оптимизации для production (OPcache, JIT) |
| `docker/nginx/default.conf` | Конфигурация Nginx для production |
| `docker/nginx/dev.conf` | Конфигурация Nginx для разработки (Vite HMR) |
| `docker-compose.yml` | Development окружение |
| `docker-compose.prod.yml` | Production окружение |
| `.env.example` | Пример переменных окружения |
| `Makefile` | Команды для управления |

---

## Структура Docker

```
docker/
├── php/
│   ├── Dockerfile          # Multi-stage PHP image
│   ├── php.ini             # Base PHP configuration
│   ├── local.ini           # Development overrides
│   └── production.ini      # Production optimizations
└── nginx/
    ├── default.conf        # Production config
    └── dev.conf            # Development config (Vite HMR)
```

---

## Сервисы (Development)

| Сервис | Контейнер | Образ              | Порт | Описание |
|--------|-----------|--------------------|------|----------|
| app | blog_app | php:8.3-fpm-alpine | 9000 | PHP-FPM backend |
| web | blog_web | nginx:1.27-alpine  | 80, 443 | Nginx reverse proxy |
| db | blog_db | postgres:17-alpine | 5432 | PostgreSQL database |
| redis | blog_redis | redis:7.4-alpine   | 6379 | Cache & Queue |
| node | blog_node | node:24-alpine     | 5173 | Vue.js dev server |
| adminer | blog_adminer | adminer:4.8.1      | 8080 | DB management UI |
| mailpit | blog_mailpit | mailpit:v1.21      | 8025, 1025 | Email testing |

---

## Сервисы (Production)

| Сервис | Контейнер | Ресурсы (limits) | Описание |
|--------|-----------|------------------|----------|
| app | blog_app_prod | CPU: 1, RAM: 512M | PHP-FPM |
| web | blog_web_prod | CPU: 0.5, RAM: 256M | Nginx |
| db | blog_db_prod | CPU: 1, RAM: 1G | PostgreSQL |
| redis | blog_redis_prod | CPU: 0.5, RAM: 512M | Redis |
| queue | blog_queue_prod | CPU: 0.5, RAM: 256M (x2) | Queue workers |
| scheduler | blog_scheduler_prod | CPU: 0.25, RAM: 128M | Laravel scheduler |

---

## Dockerfile (Multi-stage)

### Stages

1. **base** - Общие зависимости и PHP расширения
2. **development** - Xdebug, development конфигурация
3. **builder** - Установка composer зависимостей
4. **production** - Оптимизированный образ без dev зависимостей

### PHP Extensions

```
pdo_pgsql    - PostgreSQL driver
pgsql        - PostgreSQL native functions
gd           - Image processing
intl         - Internationalization
mbstring     - Multibyte strings
xml          - XML processing
curl         - HTTP client
zip          - ZIP archives
opcache      - Bytecode cache
bcmath       - Arbitrary precision math
redis        - Redis client (PECL)
```

### Production Optimizations

- OPcache: 256MB memory, 10000 files cache
- JIT compiler: 100MB buffer
- Session security: secure cookies, SameSite
- Disabled dangerous functions

---

## Nginx Configuration

### Development (dev.conf)

- Vite HMR WebSocket proxy (`/hmr`)
- Vite dev server proxy (`/@fs`, `/@vite`, `/app`)
- CORS headers enabled
- Debug logging

### Production (default.conf)

- Gzip compression
- Security headers (X-Frame-Options, CSP)
- Static assets caching (1 year for images, 30 days for CSS/JS)
- FastCGI optimizations

---

## Запуск

### Первичная установка

```bash
# 1. Создать .env файл
cp .env.example .env

# 2. Создать директории для Laravel и Vue.js
mkdir -p laravel frontend

# 3. Собрать и запустить контейнеры
make build
make up

# 4. Установить Laravel (в контейнере app)
docker compose exec app composer create-project laravel/laravel . --prefer-dist

# 5. Установить Vue.js (в контейнере node)
docker compose exec node npm create vue@latest .
docker compose exec node npm install

# 6. Запустить миграции
make migrate
```

### Быстрый старт

```bash
make dev
```

Это запустит все контейнеры и выведет URL для доступа:
- Frontend: http://localhost
- API: http://localhost/api
- Adminer: http://localhost:8080
- Mailpit: http://localhost:8025

---

## Makefile команды

### Docker управление

```bash
make build          # Собрать образы
make rebuild        # Пересобрать без кэша
make up             # Запустить контейнеры
make down           # Остановить контейнеры
make dev            # Запустить dev окружение
```

### Laravel

```bash
make composer-install    # Установить PHP зависимости
make composer-update     # Обновить PHP зависимости
make migrate             # Миграции БД
make migrate-fresh       # Fresh миграции с сидерами
make seed                # Запустить сидеры
make artisan CMD="..."   # Произвольная artisan команда
```

### Frontend

```bash
make npm-install    # Установить Node зависимости
make npm-update     # Обновить Node зависимости
make npm-dev        # Запустить Vite dev server
make npm-build      # Сборка для production
```

### Отладка

```bash
make logs           # Все логи
make logs-app       # Логи PHP контейнера
make shell          # Shell в PHP контейнере
make shell-root     # Root shell в PHP контейнере
make shell-node     # Shell в Node контейнере
make shell-db       # PostgreSQL shell
```

### Тестирование

```bash
make test           # PHPUnit тесты
make test-coverage  # Тесты с покрытием
make cs-fix         # Исправить стиль кода
make cs-check       # Проверить стиль кода
make stan           # PHPStan анализ
```

---

## Переменные окружения

### Критичные для настройки

```env
# Database
DB_DATABASE=blog
DB_USERNAME=blog_user
DB_PASSWORD=your_secure_password

# Redis
REDIS_PASSWORD=your_redis_password

# Application
APP_KEY=                         # Генерируется автоматически
APP_URL=https://your-domain.com

# Mail (production)
MAIL_HOST=smtp.mailtrap.io
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

---

## Сеть и Volumes

### Network

```
blog_network (bridge)
├── app
├── web
├── db
├── redis
├── node
├── adminer
└── mailpit
```

### Volumes (Development)

```
blog_postgres_data    - PostgreSQL data
blog_redis_data       - Redis persistence
blog_node_modules     - Node modules cache
```

### Volumes (Production)

```
blog_postgres_data_prod   - PostgreSQL data
blog_redis_data_prod      - Redis persistence
blog_laravel_storage      - Laravel storage (uploads)
blog_laravel_cache        - Laravel bootstrap cache
blog_frontend_dist        - Built frontend assets
blog_certbot_data         - SSL certificates
```

---

## Health Checks

| Сервис | Check | Interval |
|--------|-------|----------|
| app | PHP-FPM healthcheck | 30s |
| web | wget http://localhost/health | 30s |
| db | pg_isready | 10s |
| redis | redis-cli ping | 10s |

---

## Xdebug Configuration

Для отладки в PhpStorm:

1. Settings > PHP > Debug > Xdebug
   - Port: 9003
   - Force break at first line: off

2. Settings > PHP > Servers
   - Name: blog
   - Host: localhost
   - Path mappings: /var/www/html -> ./laravel

3. Run configuration
   - PHP Remote Debug
   - Server: blog
   - Ide key: PHPSTORM

---

## Ресурсы

### Минимальные требования (Development)

- CPU: 2 cores
- RAM: 4 GB
- Disk: 10 GB

### Рекомендуемые требования (Production)

- CPU: 4 cores
- RAM: 8 GB
- Disk: 50 GB SSD

---

## Troubleshooting

### Частые проблемы

1. **Порт уже занят**
   ```bash
   # Проверить, что использует порт
   lsof -i :80
   # Изменить порт в .env
   NGINX_PORT=8080
   ```

2. **Permission denied**
   ```bash
   # Исправить права на Laravel storage
   make shell-root
   chown -R www-data:www-data /var/www/html/storage
   chown -R www-data:www-data /var/www/html/bootstrap/cache
   ```

3. **Node modules не установлены**
   ```bash
   make npm-install
   ```

4. **База данных не готова**
   ```bash
   # Проверить health check
   docker compose ps
   # Подождать или перезапустить
   make down && make up
   ```

---

## Следующий шаг

После настройки Docker инфраструктуры:
1. Создать Laravel проект в директории `laravel/`
2. Создать Vue.js проект в директории `frontend/`
3. Настроить подключение к БД в Laravel `.env`
4. Запустить миграции

**Следующий этап:** Plan (04-plan.md) - план реализации

---

**Статус:** ✅ ЗАВЕРШЁН