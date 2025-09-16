# Use official PHP image with Apache
FROM php:8.2-apache

# Install needed extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy project files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Give permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
