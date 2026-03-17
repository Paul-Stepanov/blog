---
name: devops
description: |
  Инструкции для DevOps. Docker, инфраструктура, CI/CD.
  Создаёт 03-devops-setup.md или 08-deploy.md
---

# Skill: DevOps

Инструкции для DevOps инженера.

---

## ⚠️ Критические правила

| ❌ Никогда | ✅ Всегда |
|-----------|----------|
| Тег `latest` | Конкретная версия |
| Большие образы | Alpine версии |
| Незакреплённые версии | Pin версии |

---

## Версии по умолчанию

| Технология | Версия | Образ |
|------------|--------|-------|
| PHP | 8.3 | `php:8.3-fpm-alpine` |
| Node.js | 24 | `node:24-alpine` |
| MySQL | 8.0 | `mysql:8.0` |
| PostgreSQL | 17 | `postgres:17-alpine` |
| Redis | 7.4 | `redis:7.4-alpine` |
| Nginx | 1.27 | `nginx:1.27-alpine` |
| RabbitMQ | 4.0 | `rabbitmq:4.0-management-alpine` |

---

## Setup (03-devops-setup.md)

### Dockerfile (multi-stage)

```dockerfile
# Build stage
FROM php:8.3-fpm-alpine AS builder

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    icu-dev \
    && docker-php-ext-install -j$(nproc) \
    intl \
    opcache

# Production stage
FROM php:8.3-fpm-alpine AS production

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

WORKDIR /app
COPY . .

USER www-data
```

### docker-compose.yml

```yaml
services:
  app:
    build:
      context: .
      target: production
    volumes:
      - ./:/app
    depends_on:
      - mysql

  nginx:
    image: nginx:1.27-alpine
    ports:
      - "80:80"
    volumes:
      - ./:/app
      - ./docker/nginx.conf:/etc/nginx/nginx.conf

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
```

---

## Deploy (08-deploy.md)

### GitHub Actions

```yaml
# .github/workflows/ci.yml
name: CI/CD

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Run tests
        run: ./vendor/bin/phpunit

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - name: Deploy
        run: echo "Deploy..."
```

### Rollback план

```markdown
## Rollback Procedure

1. Identify issue
2. Revert commit: git revert HEAD
3. Redeploy previous version
4. Verify functionality
```

---

## Формат отчёта Setup

**Файл:** `.claude/pipeline/03-devops-setup.md`

```markdown
# DevOps Setup: {Проект}

**Дата:** {дата}
**Этап:** DevOps Setup (3/7)

## Созданные файлы

| Файл | Описание |
|------|----------|
| `Dockerfile` | Multi-stage build |
| `docker-compose.yml` | Сервисы |
| `docker/nginx.conf` | Nginx config |
| `.env.example` | Переменные |

## Запуск

```bash
cp .env.example .env
docker-compose up -d
```

## Сервисы

| Сервис | URL | Образ |
|--------|-----|-------|
| App | http://localhost | php:8.3-fpm-alpine |
| MySQL | localhost:3306 | mysql:8.0 |

## Ресурсы

| Сервис | CPU | Memory |
|--------|-----|--------|
| app | 1 | 512M |

---
**Статус:** ⏳ ОЖИДАЕТ
```

---

## Формат отчёта Deploy

**Файл:** `.claude/pipeline/08-deploy.md`

```markdown
# DevOps Deploy: {Проект}

**Дата:** {дата}
**Этап:** Deploy (8/7)

## CI/CD Pipeline

**Файл:** `.github/workflows/ci.yml`

### Этапы
1. Test → PHPUnit
2. Build → Docker image
3. Deploy → Production

## Secrets Required

| Secret | Описание |
|--------|----------|
| DB_PASSWORD | Пароль БД |

## Rollback Plan

1. ...
2. ...

## Проверки

- [ ] Тесты пройдены
- [ ] Миграции готовы
- [ ] Secrets настроены

---
**Статус:** ⏳ ОЖИДАЕТ
```