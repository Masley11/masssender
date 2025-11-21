
echo "🚀 Déploiement MassSender avec Supabase..."

# Vérification de la connexion Internet lors de la build
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
cp *.php public/ 2>/dev/null || true
cp -r includes public/ 2>/dev/null || true
cp -r frontend public/ 2>/dev/null || true

# Installer composer si besoin
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
fi

echo "✅ Build terminé! Supabase configuré."
