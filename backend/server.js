const express = require('express');
const cors = require('cors');
const { makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const qrcode = require('qrcode-terminal');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());

// Configuration du logger compatible avec Baileys
const logger = {
    level: 'info',
    info: (msg, ...args) => console.log(`[INFO] ${msg}`, ...args),
    warn: (msg, ...args) => console.warn(`[WARN] ${msg}`, ...args),
    error: (msg, ...args) => console.error(`[ERROR] ${msg}`, ...args),
    debug: (msg, ...args) => console.debug(`[DEBUG] ${msg}`, ...args),
    trace: (msg, ...args) => console.trace(`[TRACE] ${msg}`, ...args)
};

// État global
let socket = null;
let qrCode = null;
let isConnected = false;
let connectionStatus = 'disconnected';

// Dossier d'authentification
const authFolder = path.join(__dirname, 'auth_info');

// S'assurer que le dossier auth existe
if (!fs.existsSync(authFolder)) {
    fs.mkdirSync(authFolder, { recursive: true });
}

async function connectToWhatsApp() {
    try {
        console.log('🔄 Tentative de connexion à WhatsApp...');
        
        const { state, saveCreds } = await useMultiFileAuthState(authFolder);

        // Configuration améliorée pour Render
        socket = makeWASocket({
            auth: state,
            printQRInTerminal: true,
            logger: logger,
            browser: ['Ubuntu', 'Chrome', '120.0.0.0'],
            connectTimeoutMs: 60000,
            keepAliveIntervalMs: 30000,
            markOnlineOnConnect: false, // Important pour les serveurs
            generateHighQualityLinkPreview: true,
            getMessage: async (key) => {
                return {
                    conversation: "hello"
                }
            },
            version: [2, 2413, 1] // Version spécifique
        });

        socket.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;
            
            console.log('📡 Statut connexion:', connection);

            if (qr) {
                qrCode = qr;
                connectionStatus = 'waiting_qr';
                console.log('📱 QR Code reçu - Scannez pour vous connecter');
                qrcode.generate(qr, { small: true });
            }

            if (connection === 'open') {
                isConnected = true;
                qrCode = null;
                connectionStatus = 'connected';
                console.log('✅ WhatsApp connecté avec succès!');
            }

            if (connection === 'close') {
                isConnected = false;
                connectionStatus = 'disconnected';
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                
                console.log('❌ Connexion fermée, code:', statusCode);

                if (statusCode === DisconnectReason.loggedOut) {
                    console.log('🔓 Déconnecté - suppression des données auth...');
                    // Nettoyer le dossier auth si déconnecté
                    try {
                        const files = fs.readdirSync(authFolder);
                        for (const file of files) {
                            fs.unlinkSync(path.join(authFolder, file));
                        }
                    } catch (cleanError) {
                        console.log('Erreur nettoyage auth:', cleanError);
                    }
                } else {
                    // Reconnexion automatique
                    console.log('🔄 Tentative de reconnexion dans 5 secondes...');
                    setTimeout(connectToWhatsApp, 5000);
                }
            }

            if (connection === 'connecting') {
                connectionStatus = 'connecting';
                console.log('🔄 Connexion en cours...');
            }
        });

        socket.ev.on('creds.update', saveCreds);
        socket.ev.on('messages.upsert', () => {
            // Gérer les nouveaux messages si nécessaire
        });

    } catch (error) {
        console.error('💥 Erreur connexion WhatsApp:', error);
        connectionStatus = 'error';
        
        // Tentative de reconnexion après erreur
        setTimeout(connectToWhatsApp, 10000);
    }
}

// Routes API
app.get('/api/status', (req, res) => {
    res.json({
        connected: isConnected,
        qr: qrCode,
        status: connectionStatus,
        message: getStatusMessage(connectionStatus)
    });
});

app.post('/api/start', async (req, res) => {
    try {
        if (socket && connectionStatus === 'connecting') {
            return res.json({ 
                success: false, 
                error: 'Connexion déjà en cours' 
            });
        }

        if (isConnected) {
            return res.json({ 
                success: false, 
                error: 'Déjà connecté' 
            });
        }

        // Démarrer la connexion
        await connectToWhatsApp();
        
        res.json({ 
            success: true, 
            message: 'Connexion WhatsApp démarrée',
            status: connectionStatus,
            qr: qrCode
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
        if (socket) {
            await socket.end();
            socket = null;
        }
        isConnected = false;
        qrCode = null;
        connectionStatus = 'disconnected';
        
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

        if (!isConnected || !socket) {
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
        const formattedPhone = phone.replace(/[^0-9]/g, '') + '@s.whatsapp.net';

        console.log(`📤 Envoi message à: ${formattedPhone}`);
        
        await socket.sendMessage(formattedPhone, { text: message });
        
        console.log('✅ Message envoyé avec succès');
        res.json({ 
            success: true, 
            message: 'Message envoyé avec succès' 
        });

    } catch (error) {
        console.error('❌ Erreur envoi:', error);
        res.json({ 
            success: false, 
            error: 'Erreur lors de l\'envoi: ' + error.message 
        });
    }
});

// Route de santé
app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        timestamp: new Date().toISOString(),
        whatsapp_status: connectionStatus
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

// Démarrer la connexion WhatsApp au lancement
console.log('🚀 Initialisation du backend WhatsApp...');
connectToWhatsApp();

app.listen(PORT, '0.0.0.0', () => {
    console.log(`🎯 Backend WhatsApp démarré sur le port ${PORT}`);
    console.log(`🌍 Accessible sur: 0.0.0.0:${PORT}`);
});
