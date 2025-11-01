const express = require('express');
const cors = require('cors');
const { makeWASocket, useMultiFileAuthState, delay, Browsers } = require('@whiskeysockets/baileys');
const QRCode = require('qrcode');
const path = require('path');
const fs = require('fs');
const os = require('os');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());

// État global
let socket = null;
let qrCode = null;
let isConnected = false;
let connectionStatus = 'disconnected';

// Dossier auth dans le dossier temporaire
const authFolder = path.join(os.tmpdir(), 'whatsapp_auth');
if (!fs.existsSync(authFolder)) {
    fs.mkdirSync(authFolder, { recursive: true });
}
console.log('Dossier auth:', authFolder);

// ---------------- Configuration Baileys améliorée ---------------- //

async function createWhatsAppSocket() {
    const { state, saveCreds } = await useMultiFileAuthState(authFolder);

    return makeWASocket({
        auth: state,
        printQRInTerminal: false,
        browser: Browsers.ubuntu('Chrome'), // User-agent amélioré
        
        // Configuration de connexion robuste
        markOnlineOnConnect: false,
        generateHighQualityLinkPreview: false,
        emitOwnEvents: false,
        defaultQueryTimeoutMs: 60000,
        
        // Options de reconnect
        connectTimeoutMs: 60000,
        keepAliveIntervalMs: 10000,
        
        // Logger personnalisé
        logger: {
            level: 'silent', // Réduit le logging pour éviter le spam
            // level: 'debug' // Décommentez pour le débogage
        }
    });
}

// ---------------- API ---------------- //

// Statut de connexion
app.get('/api/status', (req, res) => {
    res.json({
        connected: isConnected,
        qr: qrCode,
        status: connectionStatus,
        timestamp: new Date().toISOString()
    });
});

// Démarrer WhatsApp
app.post('/api/start', async (req, res) => {
    try {
        if (socket) {
            return res.json({ success: false, error: 'Déjà connecté' });
        }

        console.log('🚀 Démarrage de la connexion WhatsApp...');
        connectionStatus = 'starting';
        
        socket = await createWhatsAppSocket();

        socket.ev.on('connection.update', async (update) => {
            const { connection, qr, lastDisconnect } = update;
            console.log('📡 Statut connexion:', connection, qr ? 'QR reçu' : '');

            if (qr) {
                connectionStatus = 'waiting_qr';
                qrCode = await QRCode.toDataURL(qr);
                console.log('📱 QR Code généré');
            }

            if (connection === 'open') {
                isConnected = true;
                connectionStatus = 'connected';
                qrCode = null;
                console.log('✅ WhatsApp connecté avec succès!');
            }

            if (connection === 'close') {
                isConnected = false;
                connectionStatus = 'disconnected';
                qrCode = null;
                
                const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== 401;
                
                if (shouldReconnect) {
                    console.log('🔄 Tentative de reconnexion...');
                    setTimeout(() => {
                        if (!isConnected) {
                            initializeConnection();
                        }
                    }, 5000);
                } else {
                    console.log('❌ Déconnexion permanente, QR requis');
                    socket = null;
                }
            }

            if (connection === 'connecting') {
                connectionStatus = 'connecting';
                console.log('🔄 Connexion en cours...');
            }
        });

        socket.ev.on('creds.update', saveCreds);

        // Timeout pour la génération du QR
        setTimeout(() => {
            if (!isConnected && !qrCode) {
                connectionStatus = 'timeout';
                console.log('⏰ Timeout de connexion');
            }
        }, 30000);

        res.json({ 
            success: true, 
            message: 'Connexion WhatsApp démarrée',
            qr: qrCode 
        });

    } catch (error) {
        console.error('❌ Erreur démarrage:', error);
        connectionStatus = 'error';
        res.json({ 
            success: false, 
            error: 'Erreur lors du démarrage: ' + error.message 
        });
    }
});

// Réinitialiser la connexion
async function initializeConnection() {
    try {
        if (socket) {
            await socket.end();
            socket = null;
        }
        
        await delay(2000);
        socket = await createWhatsAppSocket();
        setupEventHandlers();
        
    } catch (error) {
        console.error('❌ Erreur réinitialisation:', error);
    }
}

function setupEventHandlers() {
    socket.ev.on('connection.update', async (update) => {
        const { connection, qr, lastDisconnect } = update;

        if (qr) {
            qrCode = await QRCode.toDataURL(qr);
            connectionStatus = 'waiting_qr';
        }

        if (connection === 'open') {
            isConnected = true;
            connectionStatus = 'connected';
            qrCode = null;
        }

        if (connection === 'close') {
            handleConnectionClose(lastDisconnect);
        }
    });

    socket.ev.on('creds.update', saveCreds);
}

function handleConnectionClose(lastDisconnect) {
    isConnected = false;
    connectionStatus = 'disconnected';
    
    const statusCode = lastDisconnect?.error?.output?.statusCode;
    console.log('🔌 Déconnexion, code:', statusCode);

    if (statusCode === 401) {
        // Session expirée, suppression des creds
        console.log('🔑 Session expirée, nettoyage...');
        cleanupAuthFiles();
        socket = null;
    } else {
        // Reconnexion automatique
        console.log('🔄 Reconnexion dans 5s...');
        setTimeout(initializeConnection, 5000);
    }
}

function cleanupAuthFiles() {
    try {
        const files = fs.readdirSync(authFolder);
        for (const file of files) {
            fs.unlinkSync(path.join(authFolder, file));
        }
        console.log('🧹 Fichiers auth nettoyés');
    } catch (error) {
        console.log('⚠️ Erreur nettoyage:', error.message);
    }
}

// Arrêter WhatsApp
app.post('/api/stop', async (req, res) => {
    try {
        if (socket) {
            await socket.end();
            socket = null;
        }
        isConnected = false;
        connectionStatus = 'disconnected';
        qrCode = null;
        
        cleanupAuthFiles();
        
        res.json({ success: true, message: 'WhatsApp arrêté' });
    } catch (error) {
        res.json({ success: false, error: error.message });
    }
});

// Envoyer un message (version améliorée)
app.post('/api/send', async (req, res) => {
    try {
        const { phone, message } = req.body;

        if (!isConnected || !socket) {
            return res.json({ 
                success: false, 
                error: 'WhatsApp non connecté',
                status: connectionStatus 
            });
        }

        if (!phone || !message) {
            return res.json({ 
                success: false, 
                error: 'Numéro et message requis' 
            });
        }

        // Validation du numéro
        const cleanPhone = phone.replace(/[^0-9]/g, '');
        if (cleanPhone.length < 9) {
            return res.json({ 
                success: false, 
                error: 'Numéro de téléphone invalide' 
            });
        }

        const formattedPhone = cleanPhone + '@s.whatsapp.net';
        console.log(`📤 Envoi message à: ${cleanPhone}`);

        await socket.sendMessage(formattedPhone, { text: message });
        
        res.json({ 
            success: true, 
            message: 'Message envoyé avec succès' 
        });

    } catch (error) {
        console.error('❌ Erreur envoi:', error);
        
        // Gestion spécifique des erreurs
        let errorMessage = 'Erreur envoi: ' + error.message;
        if (error.message.includes('not-authorized')) {
            errorMessage = 'Session WhatsApp expirée, veuillez reconnecter';
            isConnected = false;
            connectionStatus = 'disconnected';
        }
        
        res.json({ 
            success: false, 
            error: errorMessage 
        });
    }
});

// Route de santé améliorée
app.get('/health', (req, res) => {
    res.json({ 
        status: 'OK', 
        timestamp: new Date().toISOString(),
        whatsapp_status: connectionStatus,
        connected: isConnected,
        has_qr: !!qrCode
    });
});

// Nettoyer les fichiers auth au démarrage (optionnel)
app.post('/api/cleanup', (req, res) => {
    cleanupAuthFiles();
    res.json({ success: true, message: 'Nettoyage effectué' });
});

// Lancer le serveur
app.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 Backend WhatsApp démarré sur le port ${PORT}`);
    console.log(`📁 Dossier auth: ${authFolder}`);
    console.log(`🌐 Health check: http://0.0.0.0:${PORT}/health`);
});
