# Use the official PHP-FPM image
FROM php:8.3-fpm

# Install necessary packages and PHP extensions
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

# Copy Nginx configuration file to the container
# Ensure that the nginx.conf file exists in the same directory as this Dockerfile
COPY ./nginx.conf /etc/nginx/nginx.conf

# Create the XenForo web directory
RUN mkdir -p /var/www/xenforo && chown -R www-data:www-data /var/www/xenforo

# Set the working directory to /var/www/xenforo
WORKDIR /var/www/xenforo

# Expose port 90 for Nginx
EXPOSE 90

# Copy the entrypoint script (optional if you want a custom script)
# COPY ./entrypoint.sh /entrypoint.sh
# RUN chmod +x /entrypoint.sh

# Start both Nginx and PHP-FPM using a shell script
CMD ["sh", "-c", "service nginx start && php-fpm"]
