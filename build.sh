#!bin/bash
echo "🚀 Déploiement MassSender avec Supabase..."

# Vérification de la connectivité Internet lors du build
echo "Vérification de la connectivité Internet..."
if command -v curl &>/dev/null; then
  if curl -I https://www.google.com > /dev/null 2>&1; then
    echo "✅ Internet accessible"
  else
    echo "❌ Impossible d’accéder à Internet. Vérifie ta connexion réseau."
    exit 1
  fi
else
  echo "curl n’est pas installé. Impossible de vérifier la connectivité."
fi

# Création de la structure
mkdir -p public

# Copier tous les fichiers PHP, y compris index.php, dans public/
cp *.php public/ 2>/dev/null || true

# Copie des autres dossiers si présents
cp -r includes public/ 2>/dev/null || true
cp -r frontend public/ 2>/dev/null || true

# Copier le contenu de 'public/' dans le répertoire web d'Apache
# Si tu veux copier tout dans /var/www/html, tu peux faire ça dans le Dockerfile après
# ou dans ce script, mais ici on ne copie pas dans /var/www/html/
# On laisse le Dockerfile faire cette étape

# Installer composer si besoin
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
fi

echo "✅ Build terminé! La structure est prête."
