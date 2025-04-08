# Use official PHP image with Apache
FROM php:8.2-apache

# Install PHP extensions (like mysqli for MySQL connection)
RUN docker-php-ext-install mysqli

# Copy the project files to Apache's root directory
COPY . /var/www/html/

# Set permissions (optional but good practice)
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (default for Apache)
EXPOSE 80
