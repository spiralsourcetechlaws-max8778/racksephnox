#!/bin/sh
set -e

echo "🜂 Racksephnox starting..."
echo "   Environment: ${APP_ENV:-production}"

# ─── 1. Storage dirs + permissions ───
mkdir -p /var/www/html/storage/framework/{sessions,views,cache/data} \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache \
         /run

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /run
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ─── 2. Ensure SQLite database file exists ───
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    if [ ! -f "$DB_FILE" ]; then
        echo "📂 Creating SQLite database at $DB_FILE"
        mkdir -p "$(dirname "$DB_FILE")"
        touch "$DB_FILE"
        chown www-data:www-data "$DB_FILE"
        chmod 666 "$DB_FILE"
    fi
fi

# ─── 3. Start web server first (so Render port scan passes) ───
echo "🚀 Starting Nginx and PHP-FPM..."
/usr/bin/supervisord -c /etc/supervisord.conf &
SUPERVISOR_PID=$!

# ─── 4. Run migrations in background (don't block port 10000) ───
echo "🗃️  Running migrations in the background..."
(
    sleep 3
    php /var/www/html/artisan migrate --force --no-interaction || true

    if [ "${RUN_SEEDERS:-false}" = "true" ]; then
        echo "🌱 Running seeders..."
        php /var/www/html/artisan db:seed --force --no-interaction || true
    fi

    if [ -n "$APP_KEY" ]; then
        echo "🔥 Warming caches..."
        php /var/www/html/artisan config:cache || true
        php /var/www/html/artisan route:cache  || true
        php /var/www/html/artisan view:cache   || true
    fi
) &

# ─── 5. Keep container alive ───
wait $SUPERVISOR_PID
