FROM php:8.3-apache
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev libsqlite3-dev libheif-dev libwebp-dev libavif-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-avif \
    && docker-php-ext-install gd pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite
COPY docker/php-upload.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/waves-entrypoint
RUN chmod +x /usr/local/bin/waves-entrypoint
ENTRYPOINT ["waves-entrypoint"]
CMD ["apache2-foreground"]
