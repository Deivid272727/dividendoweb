
FROM php:8.2-apache

# Instalar extensiones para trabajar con MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar permisos y directorio de la aplicación
WORKDIR /var/www/html

COPY . /var/www/html/

# Permitir archivos .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80
