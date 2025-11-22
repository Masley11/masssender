const express = require('express');
const cors = require('cors');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');

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

// Configuration du client WhatsApp
function initializeWhatsApp() {
    console.log('🔄 Initialisation de WhatsApp...');
    
    client = new Client({
        authStrategy: new LocalAuth({
            clientId: "masssender-client"
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
        }
    });

    // Génération du QR Code
    client.on('qr', (qr) => {
        qrCode = qr;
        connectionStatus = 'waiting_qr';
        console.log('📱 QR Code reçu - Scannez pour vous connecter');
        qrcode.generate(qr, { small: true });
    });

    // Connexion réussie
    client.on('ready', () => {
        isConnected = true;
        qrCode = null;
        connectionStatus = 'connected';
        console.log('✅ WhatsApp connecté avec succès!');
    });

    // Déconnexion
    client.on('disconnected', (reason) => {
        isConnected = false;
        connectionStatus = 'disconnected';
        console.log('❌ WhatsApp déconnecté:', reason);
        
        // Reconnexion automatique
        setTimeout(() => {
            console.log('🔄 Tentative de reconnexion...');
            initializeWhatsApp();
            client.initialize();
        }, 5000);
    });

    // Initialiser le client
    client.initialize();
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

// Démarrer au lancement
console.log('🚀 Initialisation du backend WhatsApp...');
initializeWhatsApp();

app.listen(PORT, '0.0.0.0', () => {
    console.log(`🎯 Backend WhatsApp démarré sur le port ${PORT}`);
});