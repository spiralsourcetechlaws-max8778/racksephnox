#!/bin/bash
set -e

export APP_ENV=${APP_ENV:-production}
echo "🜂 Racksephnox starting..."
echo "   Environment: ${APP_ENV}"

# ─── Filesystem prep ─────────────────────────────────────────────
mkdir -p storage/framework/sessions storage/framework/views \
         storage/framework/cache/data storage/logs bootstrap/cache database \
         /var/log/supervisor /var/log/nginx /run
chown -R www-data:www-data storage bootstrap/cache database /run 2>/dev/null || true
chmod -R 775 storage bootstrap/cache database 2>/dev/null || true

# ─── Reverb key validation ───────────────────────────────────────
if [ "${BROADCAST_CONNECTION}" = "reverb" ]; then
    if [ -z "${REVERB_APP_KEY}" ] || [ -z "${REVERB_APP_SECRET}" ] || [ -z "${REVERB_APP_ID}" ]; then
        echo "⚠️  Reverb keys missing. Real-time updates disabled."
    else
        echo "✅ Reverb keys present."
    fi
fi

# ─── Autoloader ──────────────────────────────────────────────────
echo "📦 Refreshing autoloader..."
composer dump-autoload --optimize --no-scripts --classmap-authoritative

# ─── Migrations ──────────────────────────────────────────────────
echo "🗃️  Running migrations..."
php artisan migrate --force

# ─── Seeders ─────────────────────────────────────────────────────
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

# ─── Warm Dashboard Cache ────────────────────────────────────────
echo "🔥 Warming admin dashboard cache..."
php artisan admin:warm-cache || echo "⚠️  Cache warm failed — deploy continues."

# ─── CRITICAL: Clear old caches, then rebuild ────────────────────
echo "⚙️  Clearing stale caches..."
php artisan optimize:clear

echo "⚙️  Caching fresh configs..."
php artisan route:cache
php artisan config:cache
php artisan view:cache

# ─── Post-deploy verification ────────────────────────────────────
echo ""
echo "🔎 Post-deploy verification..."
if php artisan deploy:verify; then
    echo "✅ Verification complete."
else
    echo "⚠️  Verification command failed — deploy continues."
fi
echo ""

# ─── Startup summary ─────────────────────────────────────────────
echo ""
echo "🚀 Starting processes:"
echo "   • nginx (port 10000)"
echo "   • php-fpm (127.0.0.1:9000)"
echo "   • scheduler (schedule:work)"
echo "   • queue-worker (queue:work)"
echo ""

exec /usr/bin/supervisord -c /etc/supervisord.conf
