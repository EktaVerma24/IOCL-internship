# Use the official PHP image from the Docker Hub
FROM php:8.0-apache

# Install required PHP extensions and other dependencies
RUN docker-php-ext-install mysqli pdo pdo_mysql && \
    apt-get update && \
    apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd

# Copy application files to /var/www/html
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Expose port 80 to the outside world
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]