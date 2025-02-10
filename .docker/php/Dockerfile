ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-fpm-alpine AS php

ARG XDEBUG_VERSION=3.1.4

ARG UID
ARG GID

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY /conf.d/ "$PHP_INI_DIR/conf.d/"

RUN apk update && apk add \
    tzdata \
    shadow \
    nano \
    bash \
    icu-dev icu-data-full \
    libxml2-dev \
    libzip-dev \
    postgresql-dev \
    unzip \
    wget \
    git \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl soap opcache zip pdo pdo_pgsql pdo_mysql \
    && docker-php-ext-enable soap opcache

RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} \
  && apk add --update linux-headers \
  && pecl install xdebug-${XDEBUG_VERSION} \
  && apk del pcre-dev ${PHPIZE_DEPS}

RUN ln -s /usr/share/zoneinfo/Europe/Paris /etc/localtime \
    && sed -i "s/^;date.timezone =.*/date.timezone = Europe\/Paris/" $PHP_INI_DIR/php.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN usermod -u $UID www-data \
    && groupmod -g $GID www-data

USER www-data:www-data

WORKDIR /var/www

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
