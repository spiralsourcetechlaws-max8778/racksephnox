#!/bin/bash
set -e

echo "🜂 Racksephnox starting..."
echo "   Environment: ${APP_ENV}"

# 1. Ensure SQLite database exists and permissions are correct
mkdir -p database
touch database/database.sqlite
chown -R www-data:www-data database
chmod 666 database/database.sqlite

# 2. Ensure storage directories are writable
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 3. Run migrations BEFORE starting the web server
echo "🗃️  Running migrations..."
php artisan migrate --force

# 4. Cache configs for production speed
echo "⚙️  Caching configs..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Start Nginx and PHP-FPM (via supervisord)
echo "🚀 Starting Nginx and PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
