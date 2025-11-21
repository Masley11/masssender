#!/bin/bash
echo "🚀 Démarrage du backend WhatsApp..."

# Vérifier Node.js
node --version
npm --version

# Installer les dépendances
npm install

# Démarrer l'application
echo "📦 Démarrage de l'application Node.js..."
exec node server.js