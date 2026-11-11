FROM php:8.3.16-fpm-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libsqlite3-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        mbstring \
        pdo_mysql \
        pdo_sqlite \
        soap \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.8.6 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/conf.d/escall.ini /usr/local/etc/php/conf.d/escall.ini
COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/escall-entrypoint
COPY --chmod=755 docker/php/test.sh /usr/local/bin/escall-test

WORKDIR /var/www/html

ENTRYPOINT ["escall-entrypoint"]
CMD ["php-fpm"]
