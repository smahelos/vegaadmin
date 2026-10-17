FROM php:8.3-fpm-alpine

# Instalace základních balíčků pro Alpine
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    # kompletní ICU data (Alpine má jen angličtinu; nutné pro formátování čísel cs/sk/de)
    icu-data-full \
    libwebp-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    # balíčky pro Imagick
    imagemagick-dev \
    imagemagick \
    autoconf \
    g++ \
    make \
    && docker-php-ext-install pdo_mysql -j$(nproc) mbstring exif pcntl bcmath intl zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Instalace rozšíření Imagick
RUN pecl install imagick \
    && docker-php-ext-enable imagick

# Instalace Redis extenze
RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Set working directory
WORKDIR /var/www

# Dependencies first so this layer is cached until composer.json/lock change.
# Dev dependencies are included: the test suite runs inside this container.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

ADD . /var/www

# Autoloader and package discovery need the application code
RUN composer dump-autoload --optimize --no-interaction

RUN chown -R www-data:www-data /var/www
