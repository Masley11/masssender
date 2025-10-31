const express = require('express');
const cors = require('cors');
const { makeWASocket, useMultiFileAuthState, delay } = require('@whiskeysockets/baileys');
const qrcode = require('qrcode-terminal');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());

// Route de test
app.get('/', (req, res) => {
    res.json({ 
        status: 'online', 
        service: 'WhatsApp Backend',
        timestamp: new Date().toISOString()
    });
});

app.get('/health', (req, res) => {
    res.json({ 
        status: 'healthy',
        connected: isConnected,
        timestamp: new Date().toISOString()
    });
});

// État global
let socket = null;
let qrCode = null;
let isConnected = false;
let authFolder = '/tmp/whatsapp-auth'; // Dossier temporaire sur Render

// S'assurer que le dossier auth existe
if (!fs.existsSync(authFolder)) {
    fs.mkdirSync(authFolder, { recursive: true });
}

app.get('/api/status', (req, res) => {
    res.json({
        connected: isConnected,
        qr: qrCode,
        status: isConnected ? 'connected' : (qrCode ? 'waiting' : 'disconnected'),
        backend: 'online'
    });
});

app.post('/api/start', async (req, res) => {
    try {
        console.log('🚀 Démarrage de WhatsApp...');
        
        if (socket) {
            console.log('⚠️ Déjà connecté, fermeture précédente...');
            await socket.end();
            socket = null;
        }

        const { state, saveCreds } = await useMultiFileAuthState(authFolder);
        console.log('✅ Auth state chargé');

        socket = makeWASocket({
            auth: state,
            printQRInTerminal: true,
            logger: {
                level: 'debug'
            }
        });

        socket.ev.on('connection.update', (update) => {
            const { connection, qr } = update;
            console.log('🔗 Connection update:', connection, qr ? 'QR reçu' : 'pas de QR');
            
            if (qr) {
                qrCode = qr;
                console.log('📱 QR Code généré');
                qrcode.generate(qr, { small: true });
            }

            if (connection === 'open') {
                isConnected = true;
                qrCode = null;
                console.log('✅ WhatsApp connecté!');
            }

            if (connection === 'close') {
                isConnected = false;
                qrCode = null;
                socket = null;
                console.log('❌ WhatsApp déconnecté');
            }
        });

        socket.ev.on('creds.update', saveCreds);

        // Timeout pour éviter les blocages
        setTimeout(() => {
            if (!isConnected && !qrCode) {
                console.log('⏰ Timeout - Regénération du QR...');
                socket.end();
                socket = null;
            }
        }, 30000);

        res.json({ 
            success: true, 
            message: 'Connexion WhatsApp démarrée',
            hasQR: !!qrCode
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
        console.log('🛑 Arrêt de WhatsApp...');
        if (socket) {
            await socket.end();
            socket = null;
        }
        isConnected = false;
        qrCode = null;
        
        // Nettoyer les fichiers auth
        if (fs.existsSync(authFolder)) {
            fs.rmSync(authFolder, { recursive: true, force: true });
        }
        
        console.log('✅ WhatsApp arrêté');
        res.json({ success: true, message: 'WhatsApp arrêté' });
    } catch (error) {
        console.error('❌ Erreur arrêt:', error);
        res.json({ success: false, error: error.message });
    }
});

app.post('/api/send', async (req, res) => {
    try {
        const { phone, message } = req.body;

        if (!isConnected || !socket) {
            return res.json({ 
                success: false, 
                error: 'WhatsApp non connecté' 
            });
        }

        if (!phone || !message) {
            return res.json({ 
                success: false, 
                error: 'Numéro et message requis' 
            });
        }

        // Formater le numéro
        const formattedPhone = phone.replace(/[^0-9]/g, '') + '@s.whatsapp.net';
        console.log(`📤 Envoi message à: ${formattedPhone}`);

        await socket.sendMessage(formattedPhone, { text: message });
        
        res.json({ 
            success: true, 
            message: 'Message envoyé avec succès' 
        });

    } catch (error) {
        console.error('❌ Erreur envoi:', error);
        res.json({ 
            success: false, 
            error: 'Erreur envoi: ' + error.message 
        });
    }
});

// Nettoyer à l'arrêt
process.on('SIGINT', async () => {
    console.log('🧹 Nettoyage avant fermeture...');
    if (socket) {
        await socket.end();
    }
    process.exit(0);
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 Backend WhatsApp démarré sur le port ${PORT}`);
    console.log(`📍 Health: https://whatsapp-backend-e6sw.onrender.com/health`);
    console.log(`📍 Auth folder: ${authFolder}`);
});
