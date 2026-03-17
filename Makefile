# Makefile for Laravel Blog Docker Environment
# Usage: make [command]

.PHONY: help up down build rebuild migrate seed composer-install composer-update npm-install npm-update dev logs shell shell-root test test-coverage cs-fix cs-check clean reset-db adminer mailpit

# ============================================
# Default target
# ============================================
help:
	@echo "Laravel Blog - Docker Commands"
	@echo ""
	@echo "Setup & Build:"
	@echo "  make build          - Build Docker images"
	@echo "  make rebuild        - Rebuild Docker images (no cache)"
	@echo "  make up             - Start all containers"
	@echo "  make down           - Stop all containers"
	@echo "  make dev            - Start development environment"
	@echo ""
	@echo "Laravel:"
	@echo "  make composer-install  - Install PHP dependencies"
	@echo "  make composer-update   - Update PHP dependencies"
	@echo "  make migrate           - Run database migrations"
	@echo "  make migrate-fresh     - Fresh migration with seed"
	@echo "  make seed              - Run database seeders"
	@echo "  make artisan           - Run artisan command (make artisan CMD="config:clear")"
	@echo ""
	@echo "Frontend:"
	@echo "  make npm-install    - Install Node dependencies"
	@echo "  make npm-update     - Update Node dependencies"
	@echo "  make npm-dev        - Start Vite dev server"
	@echo "  make npm-build      - Build frontend for production"
	@echo ""
	@echo "Development:"
	@echo "  make logs           - Show container logs"
	@echo "  make shell          - Shell into PHP container"
	@echo "  make shell-root     - Root shell into PHP container"
	@echo "  make shell-node     - Shell into Node container"
	@echo "  make shell-db       - Shell into PostgreSQL"
	@echo ""
	@echo "Testing & Quality:"
	@echo "  make test           - Run PHPUnit tests"
	@echo "  make test-coverage  - Run tests with coverage"
	@echo "  make cs-fix         - Fix code style (Laravel Pint)"
	@echo "  make cs-check       - Check code style"
	@echo "  make stan           - Run PHPStan analysis"
	@echo ""
	@echo "Database:"
	@echo "  make reset-db       - Reset database (fresh migrate + seed)"
	@echo "  make backup-db      - Backup database"
	@echo ""
	@echo "Cleanup:"
	@echo "  make clean          - Remove temporary files"
	@echo "  make prune          - Remove unused Docker resources"
	@echo ""
	@echo "Tools:"
	@echo "  make adminer        - Open Adminer in browser"
	@echo "  make mailpit        - Open Mailpit in browser"

# ============================================
# Docker Commands
# ============================================
build:
	@echo "Building Docker images..."
	docker compose build

rebuild:
	@echo "Rebuilding Docker images (no cache)..."
	docker compose build --no-cache

up:
	@echo "Starting containers..."
	docker compose up -d
	@echo "Containers started. Application available at http://localhost"

down:
	@echo "Stopping containers..."
	docker compose down

dev: up
	@echo "Development environment started"
	@echo "Frontend: http://localhost"
	@echo "API: http://localhost/api"
	@echo "Adminer: http://localhost:8080"
	@echo "Mailpit: http://localhost:8025"

# ============================================
# Laravel Commands
# ============================================
composer-install:
	@echo "Installing PHP dependencies..."
	docker compose exec app composer install

composer-update:
	@echo "Updating PHP dependencies..."
	docker compose exec app composer update

migrate:
	@echo "Running migrations..."
	docker compose exec app php artisan migrate

migrate-fresh:
	@echo "Running fresh migrations with seed..."
	docker compose exec app php artisan migrate:fresh --seed

seed:
	@echo "Running seeders..."
	docker compose exec app php artisan db:seed

artisan:
	docker compose exec app php artisan $(CMD)

# ============================================
# Frontend Commands
# ============================================
npm-install:
	@echo "Installing Node dependencies..."
	docker compose exec node npm install

npm-update:
	@echo "Updating Node dependencies..."
	docker compose exec node npm update

npm-dev:
	@echo "Starting Vite dev server..."
	docker compose exec node npm run dev

npm-build:
	@echo "Building frontend for production..."
	docker compose exec node npm run build

# ============================================
# Shell Access
# ============================================
shell:
	docker compose exec app bash

shell-root:
	docker compose exec -u root app bash

shell-node:
	docker compose exec node sh

shell-db:
	docker compose exec db psql -U blog_user -d blog

# ============================================
# Logs
# ============================================
logs:
	docker compose logs -f

logs-app:
	docker compose logs -f app

logs-web:
	docker compose logs -f web

logs-db:
	docker compose logs -f db

# ============================================
# Testing & Quality
# ============================================
test:
	@echo "Running PHPUnit tests..."
	docker compose exec app php artisan test

test-coverage:
	@echo "Running tests with coverage..."
	docker compose exec app php artisan test --coverage

cs-fix:
	@echo "Fixing code style..."
	docker compose exec app ./vendor/bin/pint

cs-check:
	@echo "Checking code style..."
	docker compose exec app ./vendor/bin/pint --test

stan:
	@echo "Running PHPStan..."
	docker compose exec app ./vendor/bin/phpstan analyse

# ============================================
# Database
# ============================================
reset-db:
	@echo "Resetting database..."
	docker compose exec app php artisan migrate:fresh --seed

backup-db:
	@echo "Backing up database..."
	docker compose exec db pg_dump -U blog_user blog > backup_$$(date +%Y%m%d_%H%M%S).sql

# ============================================
# Cleanup
# ============================================
clean:
	@echo "Cleaning temporary files..."
	docker compose exec app rm -rf storage/logs/*
	docker compose exec app rm -rf bootstrap/cache/*
	docker compose exec app php artisan clear-compiled
	docker compose exec app php artisan view:clear

prune:
	@echo "Pruning unused Docker resources..."
	docker system prune -f
	docker volume prune -f

# ============================================
# Quick Access
# ============================================
adminer:
	@echo "Opening Adminer at http://localhost:8080"
	@xdg-open http://localhost:8080 2>/dev/null || open http://localhost:8080 2>/dev/null || echo "Open http://localhost:8080 in your browser"

mailpit:
	@echo "Opening Mailpit at http://localhost:8025"
	@xdg-open http://localhost:8025 2>/dev/null || open http://localhost:8025 2>/dev/null || echo "Open http://localhost:8025 in your browser"

# ============================================
# Production
# ============================================
deploy-prod:
	@echo "Deploying to production..."
	docker compose -f docker-compose.prod.yml up -d --build
	docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
	docker compose -f docker-compose.prod.yml exec app php artisan config:cache
	docker compose -f docker-compose.prod.yml exec app php artisan route:cache
	docker compose -f docker-compose.prod.yml exec app php artisan view:cache