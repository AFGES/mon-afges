#!/bin/sh
set -e

# Caches are built at container start because they depend on runtime environment variables.
# Migrations are not run here: run them once per release (k8s Job or init container).
php artisan optimize
php artisan filament:optimize

exec php artisan octane:frankenphp \
    --host=0.0.0.0 \
    --port=8000 \
    --workers="${OCTANE_WORKERS:-auto}" \
    --max-requests="${OCTANE_MAX_REQUESTS:-500}"
