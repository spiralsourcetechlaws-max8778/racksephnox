#!/bin/bash
set -e

export APP_ENV=${APP_ENV:-production}
echo "🜂 Racksephnox starting..."
echo "   Environment: ${APP_ENV}"

# Ensure storage directories are writable
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Run migrations BEFORE starting the web server
echo "🗃️  Running migrations..."
# --force is required for production
php artisan migrate --force

# Cache configs for production speed
echo "⚙️  Caching configs..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Nginx and PHP-FPM
echo "🚀 Starting Nginx and PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
