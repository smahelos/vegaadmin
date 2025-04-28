FROM php:8.3-fpm

RUN apt-get update && apt-get install -y  \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libicu-dev \
    libwebp-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    # balíčky pro Imagick
    libmagickwand-dev \
    imagemagick \
    && docker-php-ext-install pdo_mysql -j$(nproc) mbstring exif pcntl bcmath intl zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    # Instalace rozšíření Imagick
    && pecl install imagick \
    && docker-php-ext-enable imagick

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
