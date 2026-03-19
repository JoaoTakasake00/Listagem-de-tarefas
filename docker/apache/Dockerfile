FROM php:8.2-apache

# Instala extensões
RUN docker-php-ext-install pdo pdo_mysql

# Ativa mod_rewrite
RUN a2enmod rewrite

# PERMITE .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html