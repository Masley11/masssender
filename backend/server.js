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

// Dossier pour sauvegarder les sessions
const SESSION_DIR = './whatsapp-sessions';

// Créer le dossier s'il n'existe pas
if (!fs.existsSync(SESSION_DIR)) {
    fs.mkdirSync(SESSION_DIR, { recursive: true });
}

// Fonction pour sauvegarder l'état
function saveSessionState() {
    const state = {
        isConnected,
        connectionStatus,
        timestamp: Date.now()
    };
    fs.writeFileSync(path.join(SESSION_DIR, 'session-state.json'), JSON.stringify(state));
}

// Fonction pour charger l'état
function loadSessionState() {
    try {
        const statePath = path.join(SESSION_DIR, 'session-state.json');
        if (fs.existsSync(statePath)) {
            const state = JSON.parse(fs.readFileSync(statePath, 'utf8'));
            // Vérifier si la session n'est pas trop vieille (max 24h)
            if (Date.now() - state.timestamp < 24 * 60 * 60 * 1000) {
                return state;
            }
        }
    } catch (error) {
        console.log('Aucun état de session trouvé ou erreur de lecture');
    }
    return null;
}

// Configuration du client WhatsApp
function initializeWhatsApp() {
    console.log('🔄 Initialisation de WhatsApp...');
    
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
                '--disable-gpu',
                '--single-process'
            ]
        },
        // Ajouter ces options pour la persistance
        restartOnAuthFail: true,
        takeoverOnConflict: true,
        takeoverTimeoutMs: 15000
    });

    // Génération du QR Code
    client.on('qr', (qr) => {
        qrCode = qr;
        connectionStatus = 'waiting_qr';
        console.log('📱 QR Code reçu - Scannez pour vous connecter');
        qrcode.generate(qr, { small: true });
        saveSessionState();
    });

    // Connexion réussie
    client.on('ready', () => {
        isConnected = true;
        qrCode = null;
        connectionStatus = 'connected';
        console.log('✅ WhatsApp connecté avec succès!');
        saveSessionState();
    });

    // Authentification réussie
    client.on('authenticated', () => {
        console.log('🔐 Authentification réussie');
        saveSessionState();
    });

    // Déconnexion
    client.on('disconnected', (reason) => {
        isConnected = false;
        connectionStatus = 'disconnected';
        console.log('❌ WhatsApp déconnecté:', reason);
        saveSessionState();
        
        // Nettoyer la session
        try {
            const statePath = path.join(SESSION_DIR, 'session-state.json');
            if (fs.existsSync(statePath)) {
                fs.unlinkSync(statePath);
            }
        } catch (error) {
            console.log('Erreur lors du nettoyage de la session');
        }

        // Reconnexion automatique après 10 secondes
        setTimeout(() => {
            console.log('🔄 Tentative de reconnexion...');
            initializeWhatsApp();
            client.initialize().catch(error => {
                console.log('❌ Erreur lors de la réinitialisation:', error);
            });
        }, 10000);
    });

    // Erreurs
    client.on('auth_failure', (error) => {
        console.log('❌ Échec de l\'authentification:', error);
        connectionStatus = 'error';
        saveSessionState();
    });

    // Initialiser le client
    client.initialize().catch(error => {
        console.log('❌ Erreur lors de l\'initialisation:', error);
    });
}

// Routes API
app.get('/api/status', (req, res) => {
    res.json({
        connected: isConnected,
        qr: qrCode,
        status: connectionStatus,
        message: getStatusMessage(connectionStatus),
        persistent: true
    });
});

app.post('/api/start', async (req, res) => {
    try {
        if (client && connectionStatus === 'waiting_qr') {
            return res.json({ 
                success: false, 
                error: 'En attente du scan du QR Code' 
            });
        }

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
        console.error('Erreur démarrage:', error);
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
        
        // Nettoyer la session
        try {
            const statePath = path.join(SESSION_DIR, 'session-state.json');
            if (fs.existsSync(statePath)) {
                fs.unlinkSync(statePath);
            }
        } catch (error) {
            console.log('Erreur lors du nettoyage de la session');
        }
        
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

// Ajouter une route pour forcer la restauration
app.post('/api/restore', async (req, res) => {
    try {
        if (!client) {
            initializeWhatsApp();
        }
        
        // Vérifier l'état de la session
        const sessionState = loadSessionState();
        if (sessionState && sessionState.isConnected) {
            console.log('🔄 Tentative de restauration de la session...');
        }
        
        res.json({ 
            success: true, 
            message: 'Restauration de la session démarrée',
            hasSession: !!sessionState
        });

    } catch (error) {
        console.error('Erreur restauration:', error);
        res.json({ 
            success: false, 
            error: 'Erreur lors de la restauration: ' + error.message 
        });
    }
});

// Route de santé
app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        timestamp: new Date().toISOString(),
        whatsapp_status: connectionStatus,
        persistent: true
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

// Au démarrage, essayer de restaurer la session
console.log('🚀 Initialisation du backend WhatsApp...');
const savedState = loadSessionState();
if (savedState && savedState.isConnected) {
    console.log('🔍 Session précédente détectée, tentative de restauration...');
    connectionStatus = 'connecting';
}
initializeWhatsApp();

app.listen(PORT, '0.0.0.0', () => {
    console.log(`🎯 Backend WhatsApp démarré sur le port ${PORT}`);
    console.log(`💾 Sessions sauvegardées dans: ${SESSION_DIR}`);
});