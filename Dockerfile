FROM php:8.2-apache

# Instalar extensiones requeridas de PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite de Apache para URLs limpias si es necesario
RUN a2enmod rewrite

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Exponer el puerto estándar del contenedor Apache
EXPOSE 80
