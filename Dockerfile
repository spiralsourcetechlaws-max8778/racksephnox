# ═══════════════════════════════════════════════════════════════
#  R A C K S E P H N O X   ·   D O C K E R F I L E
#  Laravel 13 · PHP 8.4 · Nginx + PHP-FPM · 888 Hz · Φ = 1.618
#  Optimized for Render.com deployment
# ═══════════════════════════════════════════════════════════════

# ─────────────────────────────────────────────────────────────
# STAGE 1 — Composer dependency builder
# ─────────────────────────────────────────────────────────────
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

RUN npm run build

# ─────────────────────────────────────────────────────────────
# STAGE 3 — Production runtime (Nginx + PHP-FPM)
# ─────────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS runtime

# ── System dependencies + PHP extensions ──
RUN apk add --no-cache \
        bash \
        curl \
        git \
        unzip \
        zip \
        nginx \
        supervisor \
        sqlite \
        sqlite-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        libxml2-dev \
        icu-dev \
        postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_sqlite \
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
    && apk del --no-cache \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        libxml2-dev \
        icu-dev \
        sqlite-dev \
        postgresql-dev

# ── PHP production config ──
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# ── Nginx config ──
COPY docker/nginx.conf /etc/nginx/nginx.conf

# ── Supervisor config ──
COPY docker/supervisord.conf /etc/supervisord.conf

# ── OPcache config ──
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# ── Copy application code ──
COPY . .

# ── Copy built vendor from stage 1 ──
COPY --from=vendor /app/vendor ./vendor

# ── Copy built frontend assets from stage 2 ──
COPY --from=frontend /app/public/build ./public/build

# ── Generate optimized autoloader ──
RUN composer dump-autoload --optimize --classmap-authoritative

# ── Set permissions ──
RUN mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache/data \
        storage/logs \
        bootstrap/cache \
        /var/log/nginx \
        /var/log/supervisor \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# ── Entrypoint ──
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ── Expose Render's default port ──
EXPOSE 10000

# ── Health check ──
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:10000/health || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
