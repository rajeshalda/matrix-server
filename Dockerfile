# Base image: Ubuntu 24.04
FROM ubuntu:24.04

# Set environment variables to avoid interactive prompts
ENV DEBIAN_FRONTEND=noninteractive

# Update and install necessary packages: Nginx, MariaDB, PHP
RUN apt-get update && apt-get upgrade -y && \
    apt-get install -y nginx mariadb-server php8.3-fpm \
    php-mysql php-gd php-mbstring php-xml php-json php-cli php-curl \
    wget curl zip unzip nano sudo

# Set MariaDB configuration to allow database setup during build
RUN service mysql stop && \
    mysqld --initialize-insecure && \
    mysqld_safe & \
    sleep 10 && \
    mysql -e "CREATE DATABASE xenforo_db;" && \
    mysql -e "CREATE USER 'xenforo_user'@'localhost' IDENTIFIED BY '9631';" && \
    mysql -e "GRANT ALL PRIVILEGES ON xenforo_db.* TO 'xenforo_user'@'localhost';" && \
    mysql -e "FLUSH PRIVILEGES;" && \
    mysqladmin shutdown

# Configure Nginx for XenForo
RUN rm /etc/nginx/sites-enabled/default && \
    echo 'server { \
        listen 80; \
        server_name yourdomain.com; \
        root /var/www/xenforo; \
        index index.php index.html index.htm; \
        location / { \
            try_files $uri $uri/ /index.php?$uri&$args; \
        } \
        location ~ \.php$ { \
            include snippets/fastcgi-php.conf; \
            fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; \
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
            include fastcgi_params; \
        } \
    }' > /etc/nginx/sites-available/xenforo && \
    ln -s /etc/nginx/sites-available/xenforo /etc/nginx/sites-enabled/

# Start services (PHP-FPM and Nginx)
RUN service php8.3-fpm start && \
    service nginx start

# Create XenForo directory and set proper permissions
RUN mkdir -p /var/www/xenforo && \
    chown -R www-data:www-data /var/www/xenforo && \
    chmod -R 755 /var/www/xenforo

# Expose port 80 for Nginx
EXPOSE 80

# Command to start MariaDB, PHP-FPM, and Nginx services when the container starts
CMD mysqld_safe & \
    service php8.3-fpm start && \
    service nginx start && \
    tail -f /dev/null
