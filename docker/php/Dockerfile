# syntax=docker/dockerfile:1
# Dev image: PHP 8.5 FPM + CLI, all extensions this app needs.
FROM php:8.5-fpm-trixie AS dev

ARG UID=1000
ARG GID=1000

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# pdo_mysql : DATABASE_URL (MariaDB)
# intl      : symfony/intl
# gd        : liip/imagine-bundle (built with jpeg/png/webp/freetype by the installer)
# exif      : image orientation on uploads
# zip       : composer package extraction
# opcache   : dev-tuned in conf.d/app.ini (timestamp revalidation on)
# xdebug    : installed but inert unless XDEBUG_MODE is set
RUN install-php-extensions \
        pdo_mysql \
        intl \
        gd \
        exif \
        zip \
        opcache \
        xdebug

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        mariadb-client \
    && rm -rf /var/lib/apt/lists/*

# Line up www-data with the host user so bind-mounted files stay writable on Linux.
RUN groupmod -o -g "${GID}" www-data \
    && usermod  -o -u "${UID}" -g "${GID}" www-data

COPY docker/php/conf.d/ /usr/local/etc/php/conf.d/
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENV COMPOSER_HOME=/tmp/composer \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
