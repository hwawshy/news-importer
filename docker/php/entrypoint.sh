#!/bin/sh

set -e

if [ -f "/app/composer.json" ]; then
  composer install --working-dir=/app
fi

exec php-fpm
