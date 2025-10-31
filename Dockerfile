FROM php:8.2-apache

# Mettre à jour et installer les dépendances pour PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && docker-php-ext-install zip

# Copier les fichiers de l'application
COPY public/ /var/www/html/

# Configurer Apache
RUN a2enmod rewrite

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html

# Exposer le port
EXPOSE 80

CMD ["apache2-foreground"]
