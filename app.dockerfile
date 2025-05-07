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

# Set working directory
WORKDIR /var/www

ADD . /var/www

RUN chown -R www-data:www-data /var/www
