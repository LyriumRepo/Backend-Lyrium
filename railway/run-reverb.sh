#!/usr/bin/env sh
set -eu

PORT_TO_USE="${PORT:-8080}"

echo "==> Starting Laravel Reverb on 0.0.0.0:${PORT_TO_USE}"
php artisan reverb:start --host=0.0.0.0 --port="${PORT_TO_USE}"
