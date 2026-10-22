#!/bin/sh
set -e

echo "🜂 Racksephnox starting..."

# 1. Create storage directories and ensure permissions
mkdir -p /var/www/html/storage/framework/{sessions,views,cache/data} /var/www/html/storage/logs /var/www/html/bootstrap/cache /run
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /run
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 2. Ensure SQLite database file exists
if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    if [ ! -f "$DB_FILE" ]; then
        echo "📂 Creating SQLite database at $DB_FILE"
        touch "$DB_FILE"
        chown www-data:www-data "$DB_FILE"
        chmod 666 "$DB_FILE"
    fi
fi

# 3. Start the web server (Supervisor manages Nginx + PHP-FPM) in the background
echo "🚀 Starting Nginx and PHP-FPM..."
/usr/bin/supervisord -c /etc/supervisord.conf &
SUPERVISOR_PID=$!

# 4. Run migrations and cache warming in the background (so they don't block the port scan)
echo "🗃️  Running migrations in the background..."
(
    sleep 2 # Give Nginx a moment to bind to port 10000
    php /var/www/html/artisan migrate --force --no-interaction || true
    if [ -n "$APP_KEY" ]; then
        php /var/www/html/artisan config:cache || true
        php /var/www/html/artisan route:cache || true
        php /var/www/html/artisan view:cache || true
    fi
) &

# 5. Wait for the supervisor process (which keeps the container alive)
wait $SUPERVISOR_PID
