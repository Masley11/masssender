const express = require('express');
const cors = require('cors');
const { makeWASocket, useMultiFileAuthState, delay } = require('@whiskeysockets/baileys');
const QRCode = require('qrcode');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());

// État global
let socket = null;
let qrCode = null;
let isConnected = false;

// Dossier persistant pour auth (Render : /mnt/data)
const authFolder = path.join('/mnt/data', 'auth_info');
if (!fs.existsSync(authFolder)) fs.mkdirSync(authFolder, { recursive: true });

// ---------------- API ---------------- //

// Statut de connexion
app.get('/api/status', (req, res) => {
    res.json({
        connected: isConnected,
        qr: qrCode, // base64
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
            printQRInTerminal: false, // On ne veut pas le terminal sur Render
        });

        socket.ev.on('connection.update', async (update) => {
            const { connection, qr } = update;

            if (qr) {
                qrCode = await QRCode.toDataURL(qr); // Génère le QR en base64
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

        // Formater le numéro correctement (avec indicatif pays)
        const formattedPhone = phone.replace(/[^0-9]/g, '') + '@s.whatsapp.net';

        await socket.sendMessage(formattedPhone, { text: message });
        
        res.json({ success: true, message: 'Message envoyé avec succès' });

    } catch (error) {
        console.error('Erreur envoi:', error);
        res.json({ success: false, error: 'Erreur envoi: ' + error.message });
    }
});

// Lancer le serveur
app.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 Backend WhatsApp démarré sur le port ${PORT}`);
});
