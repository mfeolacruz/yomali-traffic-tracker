FROM php:8.4-fpm

# Build arguments for Xdebug
ARG XDEBUG_MODE=off
ARG XDEBUG_CLIENT_HOST=host.docker.internal
ARG XDEBUG_CLIENT_PORT=9003

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    default-mysql-client \
    nano \
    vim

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Xdebug
RUN pecl install xdebug-3.3.0 \
    && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js and npm (for potential frontend tooling)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Set working directory
WORKDIR /var/www

# Configure PHP for development
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Custom PHP configuration
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/error-reporting.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/error-reporting.ini && \
    echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/error-reporting.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/error-reporting.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/performance.ini && \
    echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/performance.ini && \
    echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/performance.ini

# Configure Xdebug
RUN echo "[xdebug]" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.mode=${XDEBUG_MODE}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=${XDEBUG_CLIENT_HOST}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_port=${XDEBUG_CLIENT_PORT}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.log=/tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Copy composer.json first for better caching
COPY composer.json composer.lock* /var/www/

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-dev && \
    composer clear-cache

# Copy the rest of the application
COPY . /var/www

# Generate autoloader
RUN composer dump-autoload --optimize

# Create a non-root user
RUN groupadd -g 1000 www && \
    useradd -u 1000 -ms /bin/bash -g www www

# Give ownership to www user
RUN chown -R www:www /var/www

USER www