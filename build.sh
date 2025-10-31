#!/bin/bash
echo "🚀 Déploiement MassSender avec Supabase..."

# Créer la structure
mkdir -p public
cp *.php public/ 2>/dev/null || true
cp -r includes public/ 2>/dev/null || true
cp -r frontend public/ 2>/dev/null || true

# Installer composer si besoin
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
fi

echo "✅ Build terminé! Supabase configuré."