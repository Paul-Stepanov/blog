#!/bin/sh
set -e

# Fix permissions for Laravel storage and cache directories
echo "Setting permissions for storage and bootstrap/cache..."

# Create directories if they don't exist
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

# Set permissions (run as root before switching to www-data)
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache

    # Run composer install if vendor doesn't exist
    if [ ! -d "vendor" ] && [ -f "composer.json" ]; then
        echo "Installing Composer dependencies..."
        su-exec www-data composer install --no-interaction
    fi

    # Generate app key if not set
    if [ -f ".env" ] && ! grep -q "APP_KEY=." .env; then
        echo "Generating application key..."
        su-exec www-data php artisan key:generate --force
    fi

    # For php-fpm in development, run as root to avoid log permission issues
    if [ "$1" = "php-fpm" ]; then
        exec "$@"
    fi

    # For other commands, switch to www-data
    exec su-exec www-data "$@"
fi

# Execute the main command (if not root)
exec "$@"