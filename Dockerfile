
FROM php:8.2-apache

# Instalar herramientas necesarias para Composer y Firebase
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Habilitar extensiones necesarias
RUN a2enmod rewrite

# Copiar Composer desde su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar Apache para Render
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:10000>/' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copiar archivos de Composer primero
COPY composer.json composer.lock ./

# Instalar dependencias de Firebase
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Copiar el resto de la aplicación
COPY . /var/www/html/

# Permitir archivos .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 10000