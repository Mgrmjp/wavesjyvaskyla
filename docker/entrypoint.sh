#!/bin/sh
set -e

mkdir -p /var/www/html/uploads /var/www/html/data

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data /var/www/html/uploads /var/www/html/data 2>/dev/null || true
    chmod -R a+rwX /var/www/html/uploads /var/www/html/data 2>/dev/null || true
fi

exec docker-php-entrypoint "$@"
