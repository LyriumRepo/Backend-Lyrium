#!/usr/bin/env sh
set -eu

echo "==> Linking public storage"
php artisan storage:link --force

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Caching production config"
php artisan config:cache
php artisan view:cache
