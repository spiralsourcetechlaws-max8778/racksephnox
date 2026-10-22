#!/bin/bash
set -e
export APP_ENV=${APP_ENV:-production}
echo "🜂 Racksephnox starting..."
echo "   Environment: ${APP_ENV}"

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "🗃️  Running migrations..."
php artisan migrate --force

echo "⚙️  Caching configs..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🚀 Starting Nginx and PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
