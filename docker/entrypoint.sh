#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
    echo "[entrypoint] WARNING: APP_KEY is empty, using a temporary key. Run 'make key' to set a permanent one." >&2
    APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
    export APP_KEY
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
