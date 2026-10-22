# ═══════════════════════════════════════════════════════════════
#  RACKSEPHNOX · DOCKERFILE (SQLite Only · Auto-deps)
#  Laravel 13 · PHP 8.4 · Nginx + PHP-FPM · Debian Bookworm
# ═══════════════════════════════════════════════════════════════

# ─── Stage 1: Composer vendor ───
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs \
    --no-autoloader

# ─── Stage 2: Frontend build ───
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ─── Stage 3: Runtime ───
FROM php:8.4-fpm-bookworm AS runtime

# Use the reliable extension installer
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

# Install all necessary extensions (dependencies are handled automatically)
RUN install-php-extensions \
        pdo_sqlite \
        bcmath \
        ctype \
        fileinfo \
        mbstring \
        tokenizer \
        xml \
        gd \
        intl \
        opcache \
        zip

# Install system packages needed for the runtime (Nginx, Supervisor, etc.)
RUN apt-get update && apt-get install -y --no-install-recommends \
        bash \
        curl \
        git \
        unzip \
        zip \
        nginx \
        supervisor \
        sqlite3 \
        ca-certificates \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Use the production PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# Copy application code
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# Optimize the autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Set permissions and create SQLite database file
RUN mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache/data \
        storage/logs \
        bootstrap/cache \
        database \
        /var/log/nginx \
        /var/log/supervisor \
        /run \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database /run \
    && chmod -R 775 storage bootstrap/cache database \
    && chmod 666 database/database.sqlite \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# Copy the entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:10000/health || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
