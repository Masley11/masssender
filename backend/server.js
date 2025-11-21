
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

// Définir un logger minimal compatible avec baileys
const defaultLogger = {
  info: console.log,
  warn: console.warn,
  error: console.error,
  trace: console.debug, // Ajout pour éviter l'erreur
  child: () => defaultLogger,
};

// État global
let socket = null;
let qrCode = null;
let isConnected = false;

// Dossier d'authentification
const authFolder = path.join(__dirname, 'auth_info');

app.get('/api/status', (req, res) => {
    res.json({
        connected: isConnected,
        qr: qrCode,
        status: isConnected ? 'connected' : (qrCode ? 'waiting' : 'disconnected')
    });
});

app.post('/api/start', async (req, res) => {
    try {
        if (socket) {
            return res.json({ success: false, error: 'Déjà connecté' });
        }

        const { state, saveCreds } = await useMultiFileAuthState(authFolder);

        // Créer le socket avec le logger minimal
        socket = makeWASocket({
            auth: state,
            printQRInTerminal: true,
            logger: defaultLogger,
        });

        socket.ev.on('connection.update', (update) => {
            const { connection, qr } = update;
            
            if (qr) {
                qrCode = qr;
                console.log('QR Code reçu');
                qrcode.generate(qr, { small: true });
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

        res.json({ 
            success: true, 
            message: 'Connexion WhatsApp démarrée',
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
        
        res.json({ success: true, message: 'WhatsApp arrêté' });
    } catch (error) {
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

        await socket.sendMessage(formattedPhone, { text: message });
        
        res.json({ 
            success: true, 
            message: 'Message envoyé avec succès' 
        });

    } catch (error) {
        console.error('Erreur envoi:', error);
        res.json({ 
            success: false, 
            error: 'Erreur envoi: ' + error.message 
        });
    }
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 Backend WhatsApp démarré sur le port ${PORT}`);
});

