const express = require('express');
const { makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = 3001;

// Middleware
app.use(cors());
app.use(express.json());

// Variables globales
let sock = null;
let isConnected = false;
let qrCode = null;

// Routes API
app.get('/api/status', (req, res) => {
    res.json({ 
        connected: isConnected,
        qr: qrCode
    });
});

app.post('/api/start', async (req, res) => {
    try {
        if (sock) {
            return res.json({ success: false, error: 'Déjà démarré' });
        }
        await initializeWhatsApp();
        res.json({ success: true, message: 'WhatsApp initialisé' });
    } catch (error) {
        res.json({ success: false, error: error.message });
    }
});

app.post('/api/stop', (req, res) => {
    try {
        if (sock) {
            sock.end();
            sock = null;
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
        
        if (!sock || !isConnected) {
            return res.json({ success: false, error: 'WhatsApp non connecté' });
        }
        
        const formattedPhone = formatPhone(phone);
        const result = await sock.sendMessage(formattedPhone, { text: message });
        
        res.json({ 
            success: true, 
            message_id: result.key.id,
            phone: formattedPhone
        });
    } catch (error) {
        res.json({ success: false, error: error.message });
    }
});

// Initialisation WhatsApp
async function initializeWhatsApp() {
    const sessionsDir = path.join(__dirname, 'sessions');
    
    const { state, saveCreds } = await useMultiFileAuthState(sessionsDir);
    
    sock = makeWASocket({
        auth: state,
        printQRInTerminal: true,
        browser: ["Ubuntu", "Chrome", "22.04.4"]
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', (update) => {
        const { connection, qr, lastDisconnect } = update;
        
        console.log('🔗 Statut connexion:', connection);
        
        if (qr) {
            qrCode = qr;
            console.log('📱 QR Code disponible');
        }

        if (connection === 'open') {
            isConnected = true;
            qrCode = null;
            console.log('✅ WhatsApp connecté !');
        }

        if (connection === 'close') {
            isConnected = false;
            const reason = lastDisconnect?.error?.output?.statusCode;
            
            if (reason === DisconnectReason.loggedOut) {
                console.log('🚫 Session expirée');
                // Nettoyer les sessions
                if (fs.existsSync(sessionsDir)) {
                    fs.rmSync(sessionsDir, { recursive: true, force: true });
                }
            }
            console.log('❌ WhatsApp déconnecté');
            
            // Réinitialiser
            sock = null;
        }
    });
}

function formatPhone(phone) {
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    let formatted = cleanPhone;
    
    if (cleanPhone.length === 9 && !cleanPhone.startsWith('33')) {
        formatted = '33' + cleanPhone;
    }
    
    return formatted + '@s.whatsapp.net';
}

app.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 Backend WhatsApp démarré sur le port ${PORT}`);
});