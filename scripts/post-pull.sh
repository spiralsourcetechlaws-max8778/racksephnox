#!/bin/bash
set -e
cd "$(dirname "$0")/.."

echo "📦 Installing Composer deps..."
composer install --no-interaction --prefer-dist

echo "📦 Installing npm deps..."
npm install --legacy-peer-deps

echo "🏗 Building frontend..."
npm run build

echo "🧹 Clearing Laravel caches..."
php artisan view:clear
php artisan config:clear
php artisan cache:clear

echo "✅ Post-pull complete."
