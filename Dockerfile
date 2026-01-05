# Use official PHP image with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions in one layer
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set Apache document root to Laravel public folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess files
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install dependencies (skip scripts that need database)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts

# Create storage directories if they don't exist
RUN mkdir -p storage/framework/cache/data \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Create startup script
RUN echo '#!/bin/bash\n\
    # Generate APP_KEY if not set\n\
    if [ -z "$APP_KEY" ]; then\n\
    php artisan key:generate --force 2>/dev/null || true\n\
    fi\n\
    # Create storage link\n\
    php artisan storage:link 2>/dev/null || true\n\
    # Clear and cache config\n\
    php artisan config:clear 2>/dev/null || true\n\
    php artisan cache:clear 2>/dev/null || true\n\
    # Start Apache\n\
    apache2-foreground\n\
    ' > /start.sh && chmod +x /start.sh

# Expose port 80
EXPOSE 80

# Start with our script
CMD ["/start.sh"]
