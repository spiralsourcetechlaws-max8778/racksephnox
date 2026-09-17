# ═══════════════════════════════════════════════════════════════
#  R A C K S E P H N O X   ·   D O C K E R F I L E
#  Laravel 13 · PHP 8.4 · SQLite · 888 Hz · Φ = 1.618
#  Multi-stage · amd64 + arm64 · Production-ready
# ═══════════════════════════════════════════════════════════════

# ─────────────────────────────────────────────────────────────
# STAGE 1 — Composer dependency builder
# ─────────────────────────────────────────────────────────────
FROM composer:2 AS vendor

WORKDIR /app

# Copy only composer files first (better layer caching)
COPY composer.json composer.lock ./

# Install production dependencies without scripts
# (scripts need the full app which isn't copied yet)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# ─────────────────────────────────────────────────────────────
# STAGE 2 — Frontend asset builder (Node 20)
# ─────────────────────────────────────────────────────────────
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund

COPY vite.config.js ./
COPY postcss.config.js ./
COPY tailwind.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build || true

# ─────────────────────────────────────────────────────────────
# STAGE 3 — Final runtime (PHP 8.4 FPM Alpine)
# ─────────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS runtime

# ── Install system dependencies + PHP extensions in one layer ──
RUN apk add --no-cache \
        bash \
        curl \
        git \
        unzip \
        zip \
        sqlite \
        sqlite-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        libxml2-dev \
        icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
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
    && apk del --no-cache \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        libxml2-dev \
        icu-dev \
        sqlite-dev

# ── PHP production config ──
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini 2>/dev/null || true

# ── Set working directory ──
WORKDIR /var/www

# ── Copy application code ──
COPY . .

# ── Copy built vendor from stage 1 ──
COPY --from=vendor /app/vendor ./vendor

# ── Copy built frontend assets from stage 2 ──
COPY --from=frontend /app/public/build ./public/build 2>/dev/null || true

# ── Set permissions ──
RUN mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache/data \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ── Enable OPcache for production ──
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# ── Expose FastCGI port ──
EXPOSE 9000

# ── Health check ──
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php-fpm -t || exit 1

# ── Entrypoint ──
USER www-data
CMD ["php-fpm"]
