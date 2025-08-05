#!/bin/sh

set -e

if [ -f "/app/composer.json" ]; then
  composer install --working-dir=/app
fi

mkdir /app/data && chmod -R 777 /app/data

exec php-fpm
