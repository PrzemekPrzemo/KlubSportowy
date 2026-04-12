FROM php:8.2-fpm

# System deps
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev libonig-dev libicu-dev \
    zip unzip git curl cron default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring gd zip intl opcache

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP config
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize=20M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "post_max_size=25M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "memory_limit=256M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "date.timezone=Europe/Warsaw" >> "$PHP_INI_DIR/conf.d/custom.ini"

WORKDIR /var/www/html

# Install dependencies
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || true

# Copy application
COPY . .

# Permissions
RUN chown -R www-data:www-data storage/ public/uploads/ \
    && chmod -R 775 storage/ public/uploads/

EXPOSE 9000
CMD ["php-fpm"]
