FROM php:8.2-apache


# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    curl \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql

# Activer les extensions
RUN docker-php-ext-enable pdo pdo_mysql pdo_pgsql

# Copier le script build.sh dans l'image
COPY build.sh /usr/local/bin/build.sh
RUN chmod +x /usr/local/bin/build.sh

# Exécuter le script build
RUN /usr/local/bin/build.sh

# Copier le script de démarrage
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Modifier la commande pour utiliser start.sh
CMD ["/usr/local/bin/start.sh"]


