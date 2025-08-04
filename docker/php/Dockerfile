FROM php:8.4-fpm AS build

RUN apt-get update && apt-get upgrade -y
RUN apt-get install -y libzip-dev unzip zip git

RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install zip
#RUN docker-php-ext-install intl
#RUN docker-php-ext-install sockets

RUN pecl install xdebug-3.4.4 && docker-php-ext-enable xdebug

RUN echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)\n\
xdebug.mode=debug\n\
xdebug.log_level=0\n\
xdebug.start_with_request=1\n\
xdebug.client_host=\"host.docker.internal\"\n\
xdebug.client_port=9003\n\
xdebug.discover_client_host=0\
" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN sed -ri -e 's!;date.timezone =!date.timezone = "Europe/Berlin"!g' /usr/local/etc/php/php.ini

FROM composer:latest as composer

FROM build as prepare

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --chmod=755 ./docker/php/entrypoint.sh /usr/bin/entrypoint.sh
CMD ["entrypoint.sh"]