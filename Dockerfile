FROM php:8.2-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    unzip git curl libzip-dev zip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        gd zip pdo pdo_mysql mbstring exif pcntl bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

# Create fake .env (VERY IMPORTANT)
RUN cp .env.example .env || true

# Permissions
RUN chmod -R 777 storage bootstrap/cache

# Install बिना scripts run किए
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# Ab manually Laravel commands run karo
RUN php artisan key:generate
RUN php artisan config:cache

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=10000