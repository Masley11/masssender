FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    curl \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip

# Activer les extensions
RUN docker-php-ext-enable pdo pdo_mysql pdo_pgsql

# Activer le mod_rewrite d'Apache
RUN a2enmod rewrite

# Créer le répertoire public
RUN mkdir -p /var/www/html/public

# Copier les scripts
COPY build.sh /usr/local/bin/build.sh
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/build.sh /usr/local/bin/start.sh

# Copier d'abord les fichiers de l'application
COPY . /tmp/app/

# Exécuter le script build dans le contexte copié
WORKDIR /tmp/app
RUN /usr/local/bin/build.sh

# Déplacer les fichiers construits vers le répertoire web d'Apache
RUN cp -r /tmp/app/public/* /var/www/html/ 2>/dev/null || true

# Vérifier que les fichiers sont bien présents
RUN ls -la /var/www/html/

# Modifier la configuration d'Apache pour que DocumentRoot pointe vers /var/www/html
# (pas besoin de changer puisque c'est déjà la valeur par défaut)
# Mais on ajoute une configuration pour le ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# S'assurer qu'il y a un fichier index.php
RUN if [ ! -f "/var/www/html/index.php" ]; then \
    echo "<?php echo 'MassSender - Redirection en cours...'; header('Location: whatsapp/connexion.php'); ?>" > /var/www/html/index.php; \
    fi

EXPOSE 80
CMD ["/usr/local/bin/start.sh"]
