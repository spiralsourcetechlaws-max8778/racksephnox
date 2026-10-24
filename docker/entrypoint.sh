#!/bin/bash
set -e

export APP_ENV=${APP_ENV:-production}
echo "🜂 Racksephnox starting..."
echo "   Environment: ${APP_ENV}"

# ─── Filesystem prep ──────────────────────────────────────────
mkdir -p storage/framework/sessions storage/framework/views \
         storage/framework/cache/data storage/logs bootstrap/cache database
chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database

# ─── Autoloader (catches migrations/seeders added since build) ─
echo "📦 Refreshing autoloader..."
composer dump-autoload --optimize --no-scripts --classmap-authoritative

# ─── Migrations ───────────────────────────────────────────────
echo "🗃️  Running migrations..."
php artisan migrate --force

# ─── Seed reference data (idempotent, non-fatal) ──────────────
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

# ─── Cache ────────────────────────────────────────────────────
echo "⚙️  Caching configs..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ─── Serve ────────────────────────────────────────────────────
echo "🚀 Starting Nginx and PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
