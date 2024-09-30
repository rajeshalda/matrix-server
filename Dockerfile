# Use an official PHP-FPM image with required PHP extensions
FROM php:8.3-fpm

# Install necessary extensions for XenForo
RUN apt-get update && apt-get install -y \
    nginx \
    mariadb-client \
    php-mysql \
    php-gd \
    php-mbstring \
    php-xml \
    php-json \
    php-curl \
    php-cli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure Nginx
RUN mkdir -p /etc/nginx
COPY ./nginx.conf /etc/nginx/nginx.conf

# Create directory for XenForo
RUN mkdir -p /var/www/xenforo && chown -R www-data:www-data /var/www/xenforo

# Set the working directory
WORKDIR /var/www/xenforo

# Expose port 80 for Nginx
EXPOSE 80

# Start both PHP-FPM and Nginx
CMD service nginx start && php-fpm
