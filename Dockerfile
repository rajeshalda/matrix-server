# Stage 1: PHP-FPM with necessary PHP extensions
FROM php:8.3-fpm AS php-fpm

# Install necessary PHP extensions for XenForo
RUN docker-php-ext-install mysqli gd mbstring xml json curl zip

# Stage 2: Nginx
FROM nginx:alpine AS nginx

# Copy Nginx configuration for XenForo
COPY ./nginx.conf /etc/nginx/conf.d/default.conf

# Copy PHP-FPM configuration from the first stage
COPY --from=php-fpm /usr/local/etc/php-fpm.d/ /usr/local/etc/php-fpm.d/

# Create the directory for XenForo
RUN mkdir -p /var/www/xenforo && \
    chown -R nginx:nginx /var/www/xenforo && \
    chmod -R 755 /var/www/xenforo

# Expose port 80
EXPOSE 80

# Start Nginx in the foreground
CMD ["nginx", "-g", "daemon off;"]
