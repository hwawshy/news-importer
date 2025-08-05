#!/bin/sh

set -e

if [ -f "/app/composer.json" ]; then
  composer install --working-dir=/app
fi

chmod -R 777 /app/data

exec php-fpm
