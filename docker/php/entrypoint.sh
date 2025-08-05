#!/bin/sh

set -e

if [ -f "/app/composer.json" ]; then
  composer install --working-dir=/app
fi

mkdir -p /app/data && chmod -R 777 /app/data

exec php-fpm &

while true; do
    bin/console messenger:consume import --limit=1 --time-limit=3600 -vv
done;
