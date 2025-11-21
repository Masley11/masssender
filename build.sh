#!/bin/bash
echo "🚀 Déploiement MassSender avec Supabase..."

# Vérification de la connectivité Internet
echo "Vérification de la connectivité Internet..."
if command -v curl &>/dev/null; then
  if curl -I https://www.google.com > /dev/null 2>&1; then
    echo "✅ Internet accessible"
  else
    echo "❌ Impossible d'accéder à Internet. Vérifie ta connexion réseau."
    exit 1
  fi
else
  echo "curl n'est pas installé. Impossible de vérifier la connectivité."
fi

# Création de la structure
mkdir -p public

# Copier tous les fichiers PHP dans public/
cp *.php public/ 2>/dev/null || true

# Copie des autres dossiers
cp -r includes public/ 2>/dev/null || true
cp -r frontend public/ 2>/dev/null || true
cp -r whatsapp public/ 2>/dev/null || true  # Ajout important!

# S'assurer qu'il y a un index.php
if [ ! -f "public/index.php" ]; then
  echo "<?php header('Location: whatsapp/connexion.php'); ?>" > public/index.php
  echo "✅ Fichier index.php de redirection créé"
fi

# Installer composer si besoin
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
fi

# Copier vers le répertoire web d'Apache
echo "Copie des fichiers vers /var/www/html/"
cp -r public/* /var/www/html/ 2>/dev/null || true

echo "✅ Build terminé! Structure vérifiée:"
ls -la /var/www/html/