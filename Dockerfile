# Usa una imagen PHP adecuada
FROM php:8.2-fpm

# Instala dependencias del sistema necesarias
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    zip

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copia el contenido del proyecto
COPY . /var/www

WORKDIR /var/www

# Instala dependencias de PHP (Laravel)
RUN composer install --no-dev --optimize-autoloader

# Cambia permisos para Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Expone el puerto
EXPOSE 8000

# Comando de inicio
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
