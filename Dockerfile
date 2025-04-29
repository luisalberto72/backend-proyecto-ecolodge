# Imagen base con PHP y FPM
FROM php:8.2-fpm

# Instala dependencias del sistema necesarias para Laravel y MySQL
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establece directorio de trabajo
WORKDIR /var/www

# Copia los archivos del proyecto Laravel (asumiendo que el Dockerfile está en /backend)
COPY . .

# Da permisos adecuados a Laravel (especialmente storage y bootstrap/cache)
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Expone el puerto en el que correrá Laravel (Render lo sustituye por $PORT)
EXPOSE 8000

# Comando por defecto: ejecuta migraciones y lanza el servidor embebido de Laravel
CMD php artisan config:cache && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
