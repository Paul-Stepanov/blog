# DevOps Reference — Справочник конфигураций

Этот файл содержит примеры конфигураций для dev-devops агента. Не загружается в контекст автоматически.

---

## 1. Dockerfile для PHP 8.3

```dockerfile
# Stage 1: Base PHP image
FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    libxml2-dev oniguruma-dev icu-dev postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd zip mbstring xml intl pdo pdo_mysql pdo_pgsql bcmath opcache

# Stage 2: Development
FROM base AS development
RUN pecl install xdebug-3.4.0 && docker-php-ext-enable xdebug
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# Stage 3: Production
FROM base AS production
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache-recommended.ini
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader && rm -rf /root/.composer
COPY . .
RUN composer dump-autoload --optimize
WORKDIR /var/www/html
USER www-data
EXPOSE 9000
CMD ["php-fpm"]
```

---

## 2. Dockerfile для Node.js 24

```dockerfile
FROM node:24-alpine AS deps
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --only=production

FROM node:24-alpine AS builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM node:24-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production
RUN addgroup --system --gid 1001 nodejs && adduser --system --uid 1001 nodeapp
COPY --from=builder /app/dist ./dist
COPY --from=deps /app/node_modules ./node_modules
COPY package.json ./
USER nodeapp
EXPOSE 3000
CMD ["node", "dist/index.js"]
```

---

## 3. Docker Compose

### docker-compose.yml

```yaml
services:
  app:
    build: { context: ., dockerfile: Dockerfile, target: development }
    container_name: ${PROJECT_NAME:-project}_app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes: [.:/var/www/html, ./docker/php/php.ini:/usr/local/etc/php/php.ini:ro]
    environment: [DB_HOST=${DB_HOST:-db}, DB_PORT=${DB_PORT:-3306}, DB_DATABASE=${DB_DATABASE:-app}, DB_USERNAME=${DB_USERNAME:-app}, DB_PASSWORD=${DB_PASSWORD:-secret}]
    depends_on: { db: { condition: service_healthy }, redis: { condition: service_started } }
    networks: [app-network]

  nginx:
    image: nginx:1.27-alpine
    container_name: ${PROJECT_NAME:-project}_nginx
    ports: ["${HTTP_PORT:-80}:80", "${HTTPS_PORT:-443}:443"]
    volumes: [.:/var/www/html:ro, ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro]
    depends_on: [app]
    networks: [app-network]

  db:
    image: mysql:8.0
    container_name: ${PROJECT_NAME:-project}_db
    ports: ["${DB_EXTERNAL_PORT:-3306}:3306"]
    environment: [MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD:-root_secret}, MYSQL_DATABASE=${DB_DATABASE:-app}, MYSQL_USER=${DB_USERNAME:-app}, MYSQL_PASSWORD=${DB_PASSWORD:-secret}]
    volumes: [db_data:/var/lib/mysql]
    healthcheck: { test: ["CMD", "mysqladmin", "ping", "-h", "localhost"], interval: 10s, timeout: 5s, retries: 5 }
    networks: [app-network]

  redis:
    image: redis:7.4-alpine
    container_name: ${PROJECT_NAME:-project}_redis
    ports: ["${REDIS_EXTERNAL_PORT:-6379}:6379"]
    volumes: [redis_data:/data]
    command: redis-server --appendonly yes
    healthcheck: { test: ["CMD", "redis-cli", "ping"], interval: 10s, timeout: 5s, retries: 3 }
    networks: [app-network]

networks: { app-network: { driver: bridge } }
volumes: { db_data:, redis_data: }
```

### docker-compose.override.yml

```yaml
services:
  app:
    environment: [XDEBUG_MODE=debug, XDEBUG_CONFIG=client_host=host.docker.internal client_port=9003]
    extra_hosts: ["host.docker.internal:host-gateway"]
    volumes: [.:/var/www/html, vendor_data:/var/www/html/vendor]

  mailhog:
    image: mailhog/mailhog:v1.0.1
    ports: ["${MAILHOG_SMTP_PORT:-1025}:1025", "${MAILHOG_WEB_PORT:-8025}:8025"]
    networks: [app-network]

  adminer:
    image: adminer:4.8.1-standalone
    ports: ["${ADMINER_PORT:-8080}:8080"]
    environment: [ADMINER_DEFAULT_SERVER=db]
    depends_on: [db]
    networks: [app-network]

volumes: { vendor_data: }
```

---

## 4. Альтернативные БД

### PostgreSQL
```yaml
  db:
    image: postgres:17-alpine
    environment: [POSTGRES_DB=${DB_DATABASE:-app}, POSTGRES_USER=${DB_USERNAME:-app}, POSTGRES_PASSWORD=${DB_PASSWORD:-secret}]
    volumes: [db_data:/var/lib/postgresql/data]
    healthcheck: { test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME:-app}"], interval: 10s, timeout: 5s, retries: 5 }
```

### MongoDB
```yaml
  mongo:
    image: mongo:8.0
    environment: [MONGO_INITDB_ROOT_USERNAME=${MONGO_USERNAME:-admin}, MONGO_INITDB_ROOT_PASSWORD=${MONGO_PASSWORD:-secret}, MONGO_INITDB_DATABASE=${MONGO_DATABASE:-app}]
    volumes: [mongo_data:/data/db]
```

### RabbitMQ
```yaml
  rabbitmq:
    image: rabbitmq:4.0-management-alpine
    ports: ["${RABBITMQ_AMQP_PORT:-5672}:5672", "${RABBITMQ_MGMT_PORT:-15672}:15672"]
    environment: [RABBITMQ_DEFAULT_USER=${RABBITMQ_USERNAME:-guest}, RABBITMQ_DEFAULT_PASS=${RABBITMQ_PASSWORD:-guest}]
    volumes: [rabbitmq_data:/var/lib/rabbitmq]
    healthcheck: { test: ["CMD", "rabbitmq-diagnostics", "-q", "ping"], interval: 30s, timeout: 10s, retries: 5 }
```

---

## 5. Nginx

```nginx
# docker/nginx/default.conf
upstream php { server app:9000; }

server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php index.html;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        client_max_body_size 50M;
    }
    location ~ /\.(?!well-known).* { deny all; }
    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    gzip on;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json;
}
```

---

## 6. GitHub Actions CI/CD

```yaml
# .github/workflows/ci.yml
name: CI/CD Pipeline
on: { push: { branches: [main, develop] }, pull_request: { branches: [main] } }
env: { PHP_VERSION: '8.3', NODE_VERSION: '24' }

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql: { image: mysql:8.0, env: { MYSQL_ROOT_PASSWORD: root, MYSQL_DATABASE: test_db }, ports: [3306:3306] }
      redis: { image: redis:7.4-alpine, ports: [6379:6379] }
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: ${{ env.PHP_VERSION }}, extensions: mbstring, xml, mysql, redis, coverage: xdebug }
      - run: composer install --no-progress --prefer-dist --optimize-autoloader
      - run: vendor/bin/phpunit --coverage-clover=coverage.xml
        env: { DB_HOST: 127.0.0.1, DB_DATABASE: test_db, DB_USERNAME: root, DB_PASSWORD: root }
      - uses: codecov/codecov-action@v5

  build:
    needs: test
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'
    steps:
      - uses: actions/checkout@v4
      - uses: docker/setup-buildx-action@v3
      - uses: docker/login-action@v3
        with: { username: ${{ secrets.DOCKER_USERNAME }}, password: ${{ secrets.DOCKER_PASSWORD }}, }
      - uses: docker/build-push-action@v6
        with: { context: ., push: true, tags: "${{ secrets.DOCKER_USERNAME }}/app:8.3-${{ github.sha }}, ${{ secrets.DOCKER_USERNAME }}/app:8.3", cache-from: type=gha, cache-to: type=gha,mode=max }

  deploy:
    needs: build
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'
    environment: production
    steps:
      - uses: appleboy/ssh-action@v1.2.0
        with: { host: ${{ secrets.DEPLOY_HOST }}, username: ${{ secrets.DEPLOY_USER }}, key: ${{ secrets.DEPLOY_KEY }}, script: "cd /var/www/app && docker-compose pull && docker-compose up -d --remove-orphans && docker image prune -f" }
```

---

## 7. GitLab CI

```yaml
# .gitlab-ci.yml
stages: [test, build, deploy]
variables: { PHP_VERSION: "8.3", DOCKER_IMAGE: $CI_REGISTRY_IMAGE:8.3-$CI_COMMIT_SHA }

test:
  stage: test
  image: php:8.3-cli-alpine
  before_script:
    - apk add --no-cache libzip-dev unzip && docker-php-ext-install zip
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install --no-progress
  script: vendor/bin/phpunit --coverage-text
  coverage: '/^\s*Lines:\s*\d+.\d+\%/'

build:
  stage: build
  image: docker:27-cli
  services: [docker:27-dind]
  before_script: docker login -u $CI_REGISTRY_USER -p $CI_REGISTRY_PASSWORD $CI_REGISTRY
  script: docker build --pull -t $DOCKER_IMAGE . && docker push $DOCKER_IMAGE
  only: [main, develop]

deploy_production:
  stage: deploy
  image: alpine:3.21
  before_script: apk add --no-cache openssh-client && eval $(ssh-agent -s) && echo "$SSH_PRIVATE_KEY" | tr -d '\r' | ssh-add -
  script: ssh $SSH_USER@$PRODUCTION_HOST "cd /var/www/production && docker-compose pull && docker-compose up -d"
  environment: { name: production, url: https://example.com }
  when: manual
  only: [main]
```

---

## 8. Environment Variables (.env.example)

```bash
PROJECT_NAME=myapp
HTTP_PORT=80
HTTPS_PORT=443

# Database
DB_HOST=db
DB_PORT=3306
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=secret
DB_ROOT_PASSWORD=root_secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# PHP
PHP_MEMORY_LIMIT=256M
PHP_UPLOAD_MAX_FILESIZE=50M

# Debug
XDEBUG_MODE=debug
XDEBUG_PORT=9003

# Tools
MAILHOG_SMTP_PORT=1025
MAILHOG_WEB_PORT=8025
ADMINER_PORT=8080
```

---

## 9. .dockerignore

```dockerignore
.git
.idea
.vscode
vendor/
node_modules/
dist/
tests/
.env
.env.*
*.log
```

---

## 10. Команды Docker

```bash
docker-compose up -d           # Запуск
docker-compose ps              # Статус
docker-compose logs -f app     # Логи
docker-compose restart app     # Перезапуск
docker-compose down            # Остановка
docker-compose down -v         # С volumes
docker-compose build --no-cache app && docker-compose up -d app  # Пересборка
docker-compose exec app sh     # Вход в контейнер
```
