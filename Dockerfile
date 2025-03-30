# Imagen base con PHP 8.2 y extensiones necesarias
FROM php:8.2-fpm

# Instala dependencias del sistema
RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring xml gd

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Copia los archivos del proyecto a la imagen
COPY . .

# Instala dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# Configura permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Configura Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan migrate --force

# Instala y configura Nginx
RUN apt-get install -y nginx
COPY ./docker/nginx/default.conf /etc/nginx/sites-available/default

# Exponer el puerto 80 para Nginx
EXPOSE 80

# Inicia Nginx y PHP-FPM
CMD service nginx start && php-fpm
