#!/bin/bash

echo "🚀 Démarrage de MassSender..."

# Installer les dépendances du backend
echo "📦 Installation des dépendances Node.js..."
cd backend
npm install --production
cd ..

# Démarrer le backend Node.js (WhatsApp)
echo "🔧 Démarrage du backend WhatsApp..."
cd backend
node server.js &
BACKEND_PID=$!
cd ..

# Attendre que le backend soit prêt
echo "⏳ Attente du démarrage du backend..."
sleep 5

# Démarrer le serveur PHP (Frontend)
echo "🌐 Démarrage du serveur PHP..."
php -S 0.0.0.0:$PORT -t frontend

# Si PHP s'arrête, arrêter aussi le backend
kill $BACKEND_PID

echo "✅ Arrêt de MassSender"