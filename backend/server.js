const express = require('express');
const cors = require('cors');
const { makeWASocket, useMultiFileAuthState, delay } = require('@whiskeysockets/baileys');
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

// Dossier auth dans le dossier temporaire (avec permissions)
const authFolder = path.join(os.tmpdir(), 'whatsapp_auth');
if (!fs.existsSync(authFolder)) {
    fs.mkdirSync(authFolder, { recursive: true });
}
console.log('Dossier auth:', authFolder);

// ---------------- API ---------------- //

// Statut de connexion
app.get('/api/status', (req, res) => {
    res.json({
        connected: isConnected,
        qr: qrCode,
        status: isConnected ? 'connected' : (qrCode ? 'waiting' : 'disconnected')
    });
});

// Démarrer WhatsApp
app.post('/api/start', async (req, res) => {
    try {
        if (socket) {
            return res.json({ success: false, error: 'Déjà connecté' });
        }

        const { state, saveCreds } = await useMultiFileAuthState(authFolder);

        socket = makeWASocket({
            auth: state,
            printQRInTerminal: false,
        });

        socket.ev.on('connection.update', async (update) => {
            const { connection, qr } = update;

            if (qr) {
                qrCode = await QRCode.toDataURL(qr);
                console.log('QR Code généré');
            }

            if (connection === 'open') {
                isConnected = true;
                qrCode = null;
                console.log('✅ WhatsApp connecté!');
            }

            if (connection === 'close') {
                isConnected = false;
                socket = null;
                console.log('❌ WhatsApp déconnecté');
            }
        });

        socket.ev.on('creds.update', saveCreds);

        res.json({ success: true, message: 'Connexion WhatsApp démarrée', qr: qrCode });

    } catch (error) {
        console.error('Erreur démarrage:', error);
        res.json({ success: false, error: 'Erreur lors du démarrage: ' + error.message });
    }
});

// Arrêter WhatsApp
app.post('/api/stop', async (req, res) => {
    try {
        if (socket) {
            await socket.end();
            socket = null;
        }
        isConnected = false;
        qrCode = null;
        
        // Nettoyer les fichiers d'auth
        try {
            const files = fs.readdirSync(authFolder);
            for (const file of files) {
                fs.unlinkSync(path.join(authFolder, file));
            }
        } catch (cleanError) {
            console.log('Nettoyage des fichiers auth:', cleanError.message);
        }
        
        res.json({ success: true, message: 'WhatsApp arrêté' });
    } catch (error) {
        res.json({ success: false, error: error.message });
    }
});

// Envoyer un message
app.post('/api/send', async (req, res) => {
    try {
        const { phone, message } = req.body;

        if (!isConnected || !socket) {
            return res.json({ success: false, error: 'WhatsApp non connecté' });
        }

        if (!phone || !message) {
            return res.json({ success: false, error: 'Numéro et message requis' });
        }

        // Formater le numéro correctement
        const formattedPhone = phone.replace(/[^0-9]/g, '') + '@s.whatsapp.net';

        await socket.sendMessage(formattedPhone, { text: message });
        
        res.json({ success: true, message: 'Message envoyé avec succès' });

    } catch (error) {
        console.error('Erreur envoi:', error);
        res.json({ success: false, error: 'Erreur envoi: ' + error.message });
    }
});

// Route de santé
app.get('/health', (req, res) => {
    res.json({ 
        status: 'OK', 
        timestamp: new Date().toISOString(),
        authFolder: authFolder
    });
});

// Lancer le serveur
app.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 Backend WhatsApp démarré sur le port ${PORT}`);
    console.log(`📁 Dossier auth: ${authFolder}`);
});
