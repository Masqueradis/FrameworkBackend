FROM php:8.5-fpm AS base

RUN apt-get update && apt-get install -y \
	libfreetype-dev \
	libjpeg62-turbo-dev \
	libpng-dev \
	libicu-dev \
	libmagickwand-dev \
	libpq-dev \
	&& rm -rf /var/lib/apt/lists/*

FROM base AS dependencies

RUN apt-get update && apt-get install -y \
    libfreetype-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxpm-dev \
    libicu-dev \
    libmagickwand-dev \
    libpq-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install imagick \
	&& docker-php-ext-enable imagick

RUN pecl install xdebug \
	&& docker-php-ext-enable xdebug

RUN docker-php-ext-install pdo_pgsql && php -m | grep pdo_pgsql

FROM dependencies AS configure

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY --from=composer/composer:latest /usr/bin/composer /usr/bin/composer

ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} app && useradd -u ${UID} -g app app

USER app

WORKDIR /var/www/html
