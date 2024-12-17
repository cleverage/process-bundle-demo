#!/usr/bin/env bash
set -eux

if [[ "${XDEBUG_MODE}" != "off" ]]; then
    docker-php-ext-enable xdebug
fi

exec docker-php-entrypoint "$@"
