# use php 8.5
FROM php:8.5-fpm

# Install system packages
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libicu-dev \
    libzip-dev \
    zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# Install and enable php extensions
RUN docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        intl \
        zip

# Give www-data the same user ID as the host user (dev only)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Download and install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Work directory in the container
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Expose default FPM PORT
EXPOSE 9000

CMD ["php-fpm"]