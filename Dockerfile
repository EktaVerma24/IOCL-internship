# Use an official PHP runtime as a parent image
FROM php:8.1-apache

# Install mysqli and other necessary PHP extensions
RUN docker-php-ext-install mysqli

# Set the working directory
WORKDIR /var/www/html

# Copy the current directory contents into the container at /var/www/html
COPY . /var/www/html/

# Expose port 80
EXPOSE 80

# Run Apache in the foreground
CMD ["apache2-foreground"]
