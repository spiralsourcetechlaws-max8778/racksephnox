#!/bin/bash
set -e

export APP_ENV=${APP_ENV:-production}
echo "🜂 Racksephnox starting..."
echo "   Environment: ${APP_ENV}"

mkdir -p storage/framework/sessions storage/framework/views \
         storage/framework/cache/data storage/logs bootstrap/cache database \
         /var/log/supervisor /var/log/nginx /run
chown -R www-data:www-data storage bootstrap/cache database /run 2>/dev/null || true
chmod -R 775 storage bootstrap/cache database 2>/dev/null || true

if [ "${BROADCAST_CONNECTION}" = "reverb" ]; then
    if [ -z "${REVERB_APP_KEY}" ] || [ -z "${REVERB_APP_SECRET}" ] || [ -z "${REVERB_APP_ID}" ]; then
        echo "⚠️  Reverb keys missing. Real-time updates disabled."
    else
        echo "✅ Reverb keys present."
    fi
fi

echo "📦 Refreshing autoloader..."
composer dump-autoload --optimize --no-scripts --classmap-authoritative

echo "🗃️  Running migrations..."
php artisan migrate --force

if [ -z "$SKIP_SEED" ]; then
    echo "🌱 Seeding reference data..."
    if php artisan db:seed --force; then
        echo "✅ Seeding complete."
    else
        echo "⚠️  Seeding failed — deploy continues."
    fi
else
    echo "⏭  SKIP_SEED set — skipping seeder."
fi

echo "🔥 Warming admin dashboard cache..."
php artisan admin:warm-cache || echo "⚠️  Cache warm failed — deploy continues."

echo "⚙️  Clearing stale caches..."
php artisan optimize:clear

echo "⚙️  Caching fresh configs..."
php artisan route:cache
php artisan config:cache
php artisan view:cache

echo ""
echo "🔎 Post-deploy verification..."
if php artisan deploy:verify; then
    echo "✅ Verification complete."
else
    echo "⚠️  Verification command failed — deploy continues."
fi
echo ""

echo "🛡️  Restoring storage ownership after artisan commands..."
mkdir -p storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage/framework 2>/dev/null || true
chmod -R 775 bootstrap/cache 2>/dev/null || true
chmod 777 storage/framework/cache 2>/dev/null || true
chmod 777 storage/framework/cache/data 2>/dev/null || true
chmod 777 storage/framework/sessions 2>/dev/null || true
chmod 777 storage/framework/views 2>/dev/null || true
chmod 777 storage/logs 2>/dev/null || true
echo "✅ Storage permissions locked to www-data."

echo ""
echo "🚀 Starting processes:"
echo "   • nginx (port 10000)"
echo "   • php-fpm (127.0.0.1:9000)"
echo "   • scheduler (schedule:work)"
echo "   • queue-worker (queue:work)"
echo ""

exec /usr/bin/supervisord -c /etc/supervisord.conf
