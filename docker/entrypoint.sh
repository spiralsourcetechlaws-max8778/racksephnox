#!/bin/sh
# ═══════════════════════════════════════════════════════════════
#  RACKSEPHNOX · Entrypoint
#  Runs once before the container starts serving traffic
# ═══════════════════════════════════════════════════════════════

set -e

echo "🜂 Racksephnox starting..."
echo "   Environment: ${APP_ENV:-production}"
echo "   Port:        ${PORT:-10000}"

# ─── Ensure storage dirs exist ───
mkdir -p \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ─── Ensure PHP-FPM socket dir ───
mkdir -p /run
chown www-data:www-data /run

# ─── Ensure SQLite database exists (if used) ───
if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    if [ ! -f "$DB_FILE" ]; then
        echo "📂 Creating SQLite database at $DB_FILE"
        touch "$DB_FILE"
        chown www-data:www-data "$DB_FILE"
        chmod 666 "$DB_FILE"
    fi
fi

# ─── Run migrations on every boot (safe - idempotent) ───
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "🗃️  Running migrations..."
    php /var/www/html/artisan migrate --force --no-interaction || true
fi

# ─── Seed only if database is empty ───
if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    echo "🌱 Running seeders..."
    php /var/www/html/artisan db:seed --force --no-interaction || true
fi

# ─── Warm caches (only if APP_KEY is present) ───
if [ -n "$APP_KEY" ]; then
    echo "🔥 Warming caches..."
    php /var/www/html/artisan config:cache  || true
    php /var/www/html/artisan route:cache   || true
    php /var/www/html/artisan view:cache    || true
    php /var/www/html/artisan event:cache   || true
fi

echo "✅ Racksephnox ready — handing off to supervisord."
exec "$@"
