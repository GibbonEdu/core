# PHP_VERSION can be overridden at build time via --build-arg or docker-compose.yaml
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
    && rm -rf /var/lib/apt/lists/*

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

WORKDIR /var/www/html

# Steps 4–7 are intentionally commented out for the dev environment.

# # 4. Copy application source from local context
# COPY . .

# # 5. Install PHP Dependencies
# RUN composer install --no-dev --optimize-autoloader --no-interaction

# # 6. Configure PHP for Gibbon
# # Path is relative to build context (project root), so ops/php/gibbon.ini
# COPY ops/php/gibbon.ini /usr/local/etc/php/conf.d/gibbon.ini

# # 7. Permissions
# RUN chown -R www-data:www-data /var/www/html \
#     && find /var/www/html -not -path "*/vendor/*" -type d -exec chmod 755 {} + \
#     && find /var/www/html -not -path "*/vendor/*" -type f -exec chmod 644 {} +

EXPOSE 80

CMD ["apache2-foreground"]
