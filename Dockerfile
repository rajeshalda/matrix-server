# Use the official PHP image with Apache and PHP 8.0
FROM php:8.0-apache

# Set working directory
WORKDIR /var/www/html

# Install necessary dependencies for XenForo
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    mariadb-client \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mbstring pdo pdo_mysql zip exif mysqli

# Enable Apache mod_rewrite for pretty URLs
RUN a2enmod rewrite

# Set Apache ServerName to avoid issues
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install Composer for dependency management (if needed in future)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy current directory contents into the working directory in the container
COPY . /var/www/html

# Set permissions for Apache to work with XenForo directories
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Ensure that the data and internal_data directories are writable by XenForo
RUN chown -R www-data:www-data /var/www/html/data /var/www/html/internal_data \
    && chmod -R 0777 /var/www/html/data /var/www/html/internal_data

# Expose port 80 to access Apache
EXPOSE 80

# Start Apache in the foreground (to keep the container running)
CMD ["apache2-foreground"]
