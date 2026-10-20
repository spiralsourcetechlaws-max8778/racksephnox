# ═══════════════════════════════════════════════════════════════
#  RACKSEPHNOX · DOCKERFILE
#  Laravel 13 · PHP 8.4 · Alpine · Nginx + PHP-FPM
# ═══════════════════════════════════════════════════════════════

# ─── Stage 1: Vendor ───
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev --no-interaction --no-scripts --prefer-dist \
    --optimize-autoloader --ignore-platform-reqs --no-autoloader

# ─── Stage 2: Frontend ───
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ─── Stage 3: Runtime ───
FROM php:8.4-fpm-alpine AS runtime

# Build dependencies (installed first, removed after extensions compiled)
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        libxml2-dev \
        icu-dev \
        postgresql-dev \
        sqlite-dev \
        linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_sqlite \
        pdo_mysql \
        pdo_pgsql \
        bcmath \
        ctype \
        fileinfo \
        mbstring \
        tokenizer \
        xml \
        gd \
        intl \
        opcache \
    && apk del .build-deps

# Runtime libraries only
RUN apk add --no-cache \
        bash \
        curl \
        git \
        unzip \
        zip \
        nginx \
        supervisor \
        sqlite \
        libpng \
        libjpeg-turbo \
        freetype \
        oniguruma \
        libxml2 \
        icu-libs \
        postgresql-libs \
        sqlite-libs

# Config files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# PHP production config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# Application code
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# Optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Permissions
RUN mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache/data \
        storage/logs \
        bootstrap/cache \
        /var/log/nginx \
        /var/log/supervisor \
        /run \
    && chown -R www-data:www-data storage bootstrap/cache /run \
    && chmod -R 775 storage bootstrap/cache \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:10000/health || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
