# Use official PHP 8.2 image with Apache
FROM php:8.2-apache

# Install required extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Set working dir
WORKDIR /var/www/html

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

# Expose the port Koyeb expects (use env variable)
EXPOSE 8080

# Configure Apache to use PORT from env var
CMD ["sh", "-c", "sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
