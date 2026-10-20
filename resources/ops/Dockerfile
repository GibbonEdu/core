# PHP_VERSION can be overridden at build time via --build-arg or compose.yaml
ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-apache

# 1. Install System Dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    gettext \
    locales \
    && rm -rf /var/lib/apt/lists/* \
    && sed -i \
              -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' \
              -e 's/# en_GB.UTF-8 UTF-8/en_GB.UTF-8 UTF-8/' \
              -e 's/# es_ES.UTF-8 UTF-8/es_ES.UTF-8 UTF-8/' \
              -e 's/# fr_FR.UTF-8 UTF-8/fr_FR.UTF-8 UTF-8/' \
              -e 's/# zh_TW.UTF-8 UTF-8/zh_TW.UTF-8 UTF-8/' \
              /etc/locale.gen \
    && locale-gen \
    && update-locale LANG=en_GB.UTF-8 LC_ALL=en_GB.UTF-8

# 2. Install PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
    mysqli \
    pdo_mysql \
    gd \
    zip \
    intl \
    xml \
    gettext \
    bcmath \
    curl \
    mbstring

# Enable Apache mod_rewrite
RUN a2enmod rewrite \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# 3. Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Unpacking large packages into the bind-mounted vendor folder can exceed Composer's
# default 300 second process timeout on Docker Desktop (Windows and macOS)
ENV COMPOSER_PROCESS_TIMEOUT=0

WORKDIR /var/www/html

# Application source is bind-mounted at /var/www/html by compose (developer Docker).
# Do not COPY the repo into the image.

EXPOSE 80

CMD ["apache2-foreground"]
