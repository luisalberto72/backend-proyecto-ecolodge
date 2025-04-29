# Imagen base oficial de PHP con Apache y extensiones necesarias
FROM php:8.2-apache

# Instala dependencias del sistema
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

# Crea un usuario no-root
RUN useradd -m laravel

# Copia el código al contenedor (con permisos para el usuario laravel)
COPY --chown=laravel:laravel . /var/www

# Establece el directorio de trabajo
WORKDIR /var/www

# Cambia al usuario no-root
USER laravel

# Instala dependencias de Composer
RUN composer install --no-dev --optimize-autoloader

# Vuelve a usar root para configurar Apache
USER root

# Habilita módulo rewrite para Laravel (URLs amigables)
RUN a2enmod rewrite

# Establece el DocumentRoot de Apache a public/
ENV APACHE_DOCUMENT_ROOT /var/www/public

# Actualiza Apache config para usar el nuevo DocumentRoot
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Expone el puerto 80
EXPOSE 80

# Comando por defecto
CMD ["apache2-foreground"]
