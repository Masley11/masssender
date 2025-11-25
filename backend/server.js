const express = require('express');
const cors = require('cors');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());

// État global
let client = null;
let qrCode = null;
let isConnected = false;
let connectionStatus = 'disconnected';
let qrCodeGenerated = false;

// Dossier pour sauvegarder les sessions
const SESSION_DIR = './whatsapp-sessions';

// Créer le dossier s'il n'existe pas
if (!fs.existsSync(SESSION_DIR)) {
    fs.mkdirSync(SESSION_DIR, { recursive: true });
}

// Fonction pour nettoyer complètement
function cleanupSession() {
    try {
        const sessionPath = path.join(SESSION_DIR, 'masssender-client');
        const statePath = path.join(SESSION_DIR, 'session-state.json');
        
        if (fs.existsSync(sessionPath)) {
            fs.rmSync(sessionPath, { recursive: true, force: true });
            console.log('🗑️ Dossier de session supprimé');
        }
        
        if (fs.existsSync(statePath)) {
            fs.unlinkSync(statePath);
            console.log('🗑️ Fichier d\'état supprimé');
        }
    } catch (error) {
        console.log('❌ Erreur nettoyage:', error);
    }
}

// Configuration du client WhatsApp - VERSION SIMPLIFIÉE
function initializeWhatsApp() {
    console.log('🔄 Initialisation de WhatsApp...');
    
    // Nettoyer l'ancien client
    if (client) {
        try {
            client.destroy();
        } catch (e) {
            console.log('⚠️ Erreur lors de la destruction du client précédent:', e.message);
        }
        client = null;
    }

    client = new Client({
        authStrategy: new LocalAuth({
            clientId: "masssender-client",
            dataPath: SESSION_DIR
        }),
        puppeteer: {
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu'
            ]
        },
        // Options critiques pour éviter les conflits
        restartOnAuthFail: false,
        takeoverOnConflict: false
    });

    // Événement QR Code - SIMPLIFIÉ
    client.on('qr', (qr) => {
        console.log('📱 NOUVEAU QR Code reçu');
        qrCode = qr;
        qrCodeGenerated = true;
        connectionStatus = 'waiting_qr';
        isConnected = false;
        
        // Afficher dans la console
        qrcode.generate(qr, { small: true });
        console.log('✅ QR Code affiché - En attente du scan...');
    });

    // Événement READY - CRITIQUE
    client.on('ready', () => {
        console.log('🎉 ✅ ÉVÉNEMENT READY DÉCLENCHÉ - WhatsApp connecté avec succès!');
        isConnected = true;
        qrCode = null;
        qrCodeGenerated = false;
        connectionStatus = 'connected';
        
        console.log('📱 Session WhatsApp active et fonctionnelle');
    });

    // Événement AUTHENTICATED
    client.on('authenticated', () => {
        console.log('🔐 Authentification réussie - Session sauvegardée');
        // Ne pas mettre à jour isConnected ici, attendre 'ready'
    });

    // Événement DISCONNECTED
    client.on('disconnected', (reason) => {
        console.log('❌ WhatsApp déconnecté:', reason);
        isConnected = false;
        qrCode = null;
        connectionStatus = 'disconnected';
        
        // Nettoyer et redémarrer après délai
        setTimeout(() => {
            console.log('🔄 Reconnexion automatique...');
            cleanupSession();
            initializeWhatsApp();
            client.initialize().catch(console.error);
        }, 5000);
    });

    // Événement AUTH FAILURE
    client.on('auth_failure', (error) => {
        console.log('❌ Échec authentification:', error);
        connectionStatus = 'error';
        isConnected = false;
        cleanupSession();
    });

    // Événement CHANGE STATE
    client.on('change_state', (state) => {
        console.log('🔄 Changement d\'état:', state);
    });

    // Événement LOADING SCREEN
    client.on('loading_screen', (percent, message) => {
        console.log(`📱 Écran de chargement: ${percent}% - ${message}`);
    });

    // Initialiser le client
    try {
        client.initialize();
        console.log('🎯 Client WhatsApp initialisé');
    } catch (error) {
        console.log('❌ Erreur initialisation client:', error);
        connectionStatus = 'error';
    }
}

// Routes API
app.get('/api/status', (req, res) => {
    res.json({
        connected: isConnected,
        qr: qrCode,
        status: connectionStatus,
        message: getStatusMessage(connectionStatus),
        persistent: true,
        timestamp: new Date().toISOString(),
        qr_generated: qrCodeGenerated
    });
});

app.post('/api/start', async (req, res) => {
    try {
        if (isConnected) {
            return res.json({ 
                success: false, 
                error: 'Déjà connecté' 
            });
        }

        if (!client) {
            initializeWhatsApp();
        }
        
        res.json({ 
            success: true, 
            message: 'Connexion WhatsApp démarrée',
            status: connectionStatus
        });

    } catch (error) {
        console.error('❌ Erreur démarrage:', error);
        res.json({ 
            success: false, 
            error: 'Erreur lors du démarrage: ' + error.message 
        });
    }
});

app.post('/api/stop', async (req, res) => {
    try {
        if (client) {
            await client.destroy();
            client = null;
        }
        isConnected = false;
        qrCode = null;
        connectionStatus = 'disconnected';
        qrCodeGenerated = false;
        
        cleanupSession();
        
        console.log('🛑 Connexion WhatsApp arrêtée manuellement');
        res.json({ 
            success: true, 
            message: 'WhatsApp arrêté avec succès' 
        });
    } catch (error) {
        res.json({ 
            success: false, 
            error: error.message 
        });
    }
});

app.post('/api/send', async (req, res) => {
    try {
        const { phone, message } = req.body;

        if (!isConnected || !client) {
            return res.json({ 
                success: false, 
                error: 'WhatsApp non connecté. Veuillez d\'abord vous connecter.' 
            });
        }

        if (!phone || !message) {
            return res.json({ 
                success: false, 
                error: 'Numéro de téléphone et message sont requis' 
            });
        }

        // Formater le numéro
        const formattedPhone = phone.replace(/[^0-9]/g, '') + '@c.us';

        console.log(`📤 Envoi message à: ${formattedPhone}`);
        
        const result = await client.sendMessage(formattedPhone, message);
        
        console.log('✅ Message envoyé avec succès');
        res.json({ 
            success: true, 
            message: 'Message envoyé avec succès',
            messageId: result.id._serialized
        });

    } catch (error) {
        console.error('❌ Erreur envoi:', error);
        res.json({ 
            success: false, 
            error: 'Erreur lors de l\'envoi: ' + error.message 
        });
    }
});

// NOUVELLE ROUTE : Forcer un nouveau QR Code
app.post('/api/refresh-qr', async (req, res) => {
    try {
        console.log('🔄 Régénération du QR Code demandée...');
        
        if (client) {
            await client.destroy();
            client = null;
        }
        
        // Nettoyer complètement
        cleanupSession();
        
        // Réinitialiser l'état
        isConnected = false;
        qrCode = null;
        connectionStatus = 'disconnected';
        qrCodeGenerated = false;
        
        // Redémarrer
        setTimeout(() => {
            initializeWhatsApp();
        }, 1000);
        
        res.json({ 
            success: true, 
            message: 'QR Code régénéré avec succès' 
        });
        
    } catch (error) {
        console.error('❌ Erreur refresh QR:', error);
        res.json({ 
            success: false, 
            error: error.message 
        });
    }
});

// Route pour réinitialiser complètement
app.post('/api/reset', async (req, res) => {
    try {
        console.log('🔄 Réinitialisation complète demandée...');
        
        if (client) {
            await client.destroy();
            client = null;
        }
        
        // Nettoyer COMPLÈTEMENT
        cleanupSession();
        
        // Réinitialiser l'état
        isConnected = false;
        qrCode = null;
        connectionStatus = 'disconnected';
        qrCodeGenerated = false;
        
        // Redémarrer après un délai
        setTimeout(() => {
            initializeWhatsApp();
        }, 2000);
        
        res.json({ 
            success: true, 
            message: 'Session complètement réinitialisée' 
        });
        
    } catch (error) {
        console.error('❌ Erreur réinitialisation:', error);
        res.json({ 
            success: false, 
            error: error.message 
        });
    }
});

// Route de diagnostic
app.get('/api/debug-sessions', (req, res) => {
    try {
        const sessionPath = path.join(SESSION_DIR, 'masssender-client');
        const sessionExists = fs.existsSync(sessionPath);
        
        let sessionInfo = {};
        if (sessionExists) {
            const files = fs.readdirSync(sessionPath);
            sessionInfo = {
                exists: true,
                fileCount: files.length,
                files: files
            };
        }
        
        res.json({
            session: sessionInfo,
            currentStatus: {
                isConnected,
                qrCode: !!qrCode,
                connectionStatus,
                qrCodeGenerated,
                clientInitialized: !!client
            }
        });
        
    } catch (error) {
        res.json({ error: error.message });
    }
});

// Route de santé
app.get('/api/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        timestamp: new Date().toISOString(),
        whatsapp_status: connectionStatus,
        connected: isConnected,
        has_qr: !!qrCode,
        qr_generated: qrCodeGenerated
    });
});

// Fonction utilitaire
function getStatusMessage(status) {
    const messages = {
        'disconnected': 'Déconnecté',
        'connecting': 'Connexion en cours...',
        'waiting_qr': 'En attente du scan du QR Code',
        'connected': 'Connecté avec succès',
        'error': 'Erreur de connexion'
    };
    return messages[status] || 'Statut inconnu';
}

// Démarrage initial
console.log('🚀 Démarrage du backend WhatsApp...');
console.log('📁 Dossier sessions:', SESSION_DIR);

// Nettoyer au démarrage pour éviter les conflits
cleanupSession();

// Démarrer WhatsApp après un court délai
setTimeout(() => {
    initializeWhatsApp();
}, 3000);

app.listen(PORT, '0.0.0.0', () => {
    console.log(`🎯 Backend WhatsApp démarré sur le port ${PORT}`);
    console.log(`🔍 Diagnostic: http://localhost:${PORT}/api/debug-sessions`);
    console.log(`❤️  Santé: http://localhost:${PORT}/api/health`);
});