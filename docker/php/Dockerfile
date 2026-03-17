# Multi-stage Dockerfile for Laravel Blog
# PHP 8.4-FPM with PostgreSQL and Redis support

# ============================================
# Base stage with common dependencies
# ============================================
FROM php:8.4-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    curl-dev \
    postgresql-dev \
    linux-headers \
    bash \
    git \
    zip \
    unzip

# Configure GD extension
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    gd \
    intl \
    mbstring \
    xml \
    curl \
    zip \
    opcache \
    bcmath

# Install Redis extension
RUN pecl install redis && \
    docker-php-ext-enable redis

# ============================================
# Development stage
# ============================================
FROM base AS development

# Install Xdebug for development
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

# Install su-exec for user switching (Alpine)
RUN apk add --no-cache su-exec

# Install Composer
COPY --from=composer:2.8.4 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy PHP configuration for development
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copy entrypoint script
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Use entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]

# ============================================
# Builder stage for production
# ============================================
FROM base AS builder

# Install Composer
COPY --from=composer:2.8.4 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for better caching
COPY laravel/composer.json laravel/composer.lock ./

# Install dependencies (no dev in production)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY laravel/ .

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# ============================================
# Production stage
# ============================================
FROM base AS production

WORKDIR /var/www/html

# Copy application from builder
COPY --from=builder --chown=www-data:www-data /var/www/html .

# Copy PHP configuration for production
COPY docker/php/production.ini /usr/local/etc/php/conf.d/production.ini
COPY docker/php/php.ini /usr/local/etc/php/php.ini

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/storage && \
    chmod -R 755 /var/www/html/bootstrap/cache

# Switch to non-root user
USER www-data

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

CMD ["php-fpm"]