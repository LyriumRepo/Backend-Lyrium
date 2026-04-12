#!/usr/bin/env sh
set -eu

echo "==> Starting Laravel queue worker"
php artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600 --verbose
