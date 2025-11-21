#!/bin/bash

# Vérification de la connectivité Internet au démarrage
echo "🔍 Vérification de la connectivité Internet..."
if curl -I https://www.google.com > /dev/null 2>&1; then
  echo "🌐 Internet OK, démarrage de l'application..."
else
  echo "❌ Pas d'accès à Internet. Vérifie ta connexion."
  # On ne quitte pas forcément, peut-être que l'app peut fonctionner sans Internet
  echo "⚠️  Poursuite du démarrage sans connectivité Internet..."
fi

# Vérifier que les fichiers sont présents
echo "📁 Contenu de /var/www/html/:"
ls -la /var/www/html/

# Démarrer Apache
echo "🚀 Démarrage d'Apache..."
exec apache2-foreground