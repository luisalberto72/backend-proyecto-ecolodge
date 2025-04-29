# Imagen base oficial de PHP con Apache
FROM php:8.2-apache

# Instala dependencias del sistema necesarias para Laravel
RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Instala Composer globalmente
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia el código de la aplicación al contenedor
COPY . /var/www

# Establece el directorio de trabajo
WORKDIR /var/www

# Instala las dependencias de Composer como root (con permisos suficientes)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Crea un usuario no-root para ejecución más segura
RUN useradd -m laravel && chown -R laravel:laravel /var/www

# Cambia al usuario no-root
USER laravel

# Habilita el módulo rewrite de Apache
USER root
RUN a2enmod rewrite

# Establece el DocumentRoot a la carpeta public/
ENV APACHE_DOCUMENT_ROOT /var/www/public

# Actualiza la configuración de Apache para usar el nuevo DocumentRoot
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Expone el puerto 80
EXPOSE 80

# Comando por defecto para iniciar Apache
CMD ["apache2-foreground"]
