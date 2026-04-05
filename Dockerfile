FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    unzip git curl libzip-dev zip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev \
    nodejs npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        gd zip pdo pdo_mysql mbstring exif pcntl bcmath

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN chmod -R 777 storage bootstrap/cache

# PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# ✅ Vite build (MOST IMPORTANT)
RUN npm install
RUN npm run build

# ❌ REMOVE ALL ARTISAN COMMANDS HERE

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=10000
