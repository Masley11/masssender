FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql

# Activer les extensions
RUN docker-php-ext-enable pdo pdo_mysql pdo_pgsql

# Installer et activer les autres extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install zip

# Copier les fichiers de l'application
COPY public/ /var/www/html/

# Configurer Apache
RUN a2enmod rewrite

# Exposer le port
EXPOSE 80

CMD ["apache2-foreground"]