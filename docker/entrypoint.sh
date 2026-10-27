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

# ─── Reverb key validation (never crash the deploy over this) ──
if [ "${BROADCAST_CONNECTION}" = "reverb" ]; then
    if [ -z "${REVERB_APP_KEY}" ] || [ -z "${REVERB_APP_SECRET}" ] || [ -z "${REVERB_APP_ID}" ]; then
        echo ""
        echo "⚠️  ══════════════════════════════════════════════════════"
        echo "⚠️   REVERB_APP_KEY / REVERB_APP_SECRET / REVERB_APP_ID"
        echo "⚠️   are NOT set. Real-time WebSockets will be disabled."
        echo "⚠️   The chart will fall back to HTTP polling (every 5s)."
        echo "⚠️"
        echo "⚠️   To enable Reverb, add these to Render Environment:"
        echo "⚠️     REVERB_APP_ID=<id>"
        echo "⚠️     REVERB_APP_KEY=<key>"
        echo "⚠️     REVERB_APP_SECRET=<secret>"
        echo "⚠️     REVERB_HOST=<your-domain>"
        echo "⚠️     REVERB_PORT=443"
        echo "⚠️     REVERB_SCHEME=https"
        echo "⚠️     VITE_REVERB_APP_KEY=<same as REVERB_APP_KEY>"
        echo "⚠️     VITE_REVERB_HOST=<your-domain>"
        echo "⚠️     VITE_REVERB_PORT=443"
        echo "⚠️     VITE_REVERB_SCHEME=https"
        echo "⚠️   Then redeploy."
        echo "⚠️  ══════════════════════════════════════════════════════"
        echo ""
    else
        echo "✅ Reverb keys present (key prefix: ${REVERB_APP_KEY:0:8}...)"
    fi
fi

# ─── Autoloader ─────────────────────────────────────────────────
echo "📦 Refreshing autoloader..."
composer dump-autoload --optimize --no-scripts --classmap-authoritative

# ─── Migrations (fatal — broken schema = broken app) ────────────
echo "🗃️  Running migrations..."
php artisan migrate --force

# ─── Seeders (non-fatal — reference data missing ≠ outage) ──────
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

# ─── Caches ─────────────────────────────────────────────────────
echo "⚙️  Caching configs..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ─── Startup summary ────────────────────────────────────────────
echo ""
echo "🚀 Starting processes:"
echo "   • nginx (port 10000)"
echo "   • php-fpm (127.0.0.1:9000)"
echo "   • scheduler (schedule:work)"
echo "   • queue-worker (queue:work)"
if [ -n "${REVERB_APP_KEY}" ]; then
    echo "   • reverb (0.0.0.0:8080) — WebSocket server"
else
    echo "   • reverb — DISABLED (no key)"
fi
echo ""

exec /usr/bin/supervisord -c /etc/supervisord.conf
