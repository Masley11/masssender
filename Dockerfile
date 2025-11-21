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

# Copier les scripts dans l'image
COPY build.sh /usr/local/bin/build.sh
RUN chmod +x /usr/local/bin/build.sh

COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Modifier la configuration Apache pour que DocumentRoot pointe vers /var/www/html/public
RUN sed -i 's#/var/www/html#/var/www/html/public#' /etc/apache2/sites-available/000-default.conf

# Exécuter le script build
RUN /usr/local/bin/build.sh

# Copier le contenu du dossier 'public/' dans le dossier web d'Apache
# Assure-toi que ton build place tout dans 'public/'
COPY public/ /var/www/html/

# Commande pour démarrer avec vérification en runtime
CMD ["/usr/local/bin/start.sh"]


