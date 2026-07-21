ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-fpm

ARG UNAME=www-data
ARG UID
ARG GID

ENV DEBIAN_FRONTEND=noninteractive

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY /conf.d/ "$PHP_INI_DIR/conf.d/"

ENV TZ="Europe/Paris"
RUN sed -i "s/^;date.timezone =.*/date.timezone = Europe\/Paris/" $PHP_INI_DIR/php.ini

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
    tzdata \
    nano \
    bash \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libxml2-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
        pdo \
        pdo_mysql \
        zip \
        soap \
    && docker-php-ext-enable soap

# For PHP_VERSION < 8.5
#RUN docker-php-ext-install opcache \
#    && docker-php-ext-enable opcache

COPY --link --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --from=ghcr.io/php/pie:bin /pie /usr/bin/pie
RUN pie install xdebug/xdebug

RUN usermod -u $UID $UNAME \
    && groupmod -g $GID $UNAME

USER $UNAME:$UNAME

WORKDIR /var/www
