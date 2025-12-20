FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    mysqli \
    pdo \
    pdo_mysql \
    zip

# Configure PHP-FPM
RUN echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "pm.start_servers = 10" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "pm.max_spare_servers = 20" >> /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html
COPY src/ /var/www/html/



EXPOSE 9000

CMD ["php-fpm"]