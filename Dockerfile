# Use the official PHP image with FPM as the base
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    unzip \
    xfonts-75dpi \
    xfonts-100dpi \
    fontconfig \
    nano \
    wkhtmltopdf \
    && docker-php-ext-install pdo_mysql exif pcntl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www/html

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Install PHP dependencies using Composer
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Create a custom PHP configuration file
RUN echo "memory_limit = 10G\nupload_max_filesize = 5G\npost_max_size = 5G\nmax_execution_time = 600\nmax_input_time = 600" > /usr/local/etc/php/conf.d/custom.ini

# Expose port 5000
EXPOSE 5000

# Start the Laravel application
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=5000"]
