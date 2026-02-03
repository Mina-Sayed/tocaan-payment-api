#!/usr/bin/env bash
set -euo pipefail

if [ ! -f .env ]; then
  cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --ansi
fi

if [ ! -f database/database.sqlite ]; then
  mkdir -p database
  touch database/database.sqlite
fi

if ! grep -q '^JWT_SECRET=.' .env; then
  php artisan jwt:secret --force
fi

composer install --no-interaction --prefer-dist
php artisan migrate --force

exec "$@"
