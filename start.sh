#!bin/bash

# Vérification de la connectivité Internet au démarrage
if curl -I https://www.google.com > /dev/null 2>&1; then
  echo "🌐 Internet OK, démarrage de l'application..."
else
  echo "❌ Pas d'accès à Internet. Vérifie ta connexion."
  exit 1
fi

# Démarrer Apache
apache2-foreground

