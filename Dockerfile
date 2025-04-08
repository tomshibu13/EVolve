# Use official PHP image with Apache
FROM php:8.2-apache

# Enable PHP extensions (like mysqli)
RUN docker-php-ext-install mysqli

# Copy project files into container
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
