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
    try {
        fs.writeFileSync(path.join(SESSION_DIR, 'session-state.json'), JSON.stringify(state));
        console.log('💾 État de session sauvegardé');
    } catch (error) {
        console.log('❌ Erreur sauvegarde état:', error);
    }
}

// Fonction pour charger l'état
function loadSessionState() {
    try {
        const statePath = path.join(SESSION_DIR, 'session-state.json');
        if (fs.existsSync(statePath)) {
            const state = JSON.parse(fs.readFileSync(statePath, 'utf8'));
            // Vérifier si la session n'est pas trop vieille (max 24h)
            if (Date.now() - state.timestamp < 24 * 60 * 60 * 1000) {
                console.log('📁 État de session chargé:', state);
                return state;
            } else {
                console.log('🗑️ Session expirée');
            }
        }
    } catch (error) {
        console.log('❌ Aucun état de session valide trouvé');
    }
    return null;
}

// Configuration du client WhatsApp
function initializeWhatsApp() {
    console.log('🔄 Initialisation de WhatsApp...');
    
    // Vérifier l'état de la session avant d'initialiser
    const sessionPath = path.join(SESSION_DIR, 'masssender-client');
    const sessionExists = fs.existsSync(sessionPath);
    
    console.log('📁 Session existante:', sessionExists);
    
    // Nettoyer si le client existe déjà
    if (client) {
        console.log('🛑 Nettoyage du client précédent...');
        client.destroy().catch(() => {});
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
                '--disable-gpu',
                '--single-process'
            ]
        },
        restartOnAuthFail: true,
        takeoverOnConflict: false, // IMPORTANT: Éviter les conflits
        takeoverTimeoutMs: 5000
    });

    // Génération du QR Code
    client.on('qr', (qr) => {
        qrCode = qr;
        connectionStatus = 'waiting_qr';
        isConnected = false;
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
        console.log('🎯 Session active sauvegardée dans:', sessionPath);
        saveSessionState();
    });

    // Authentification réussie
    client.on('authenticated', () => {
        console.log('🔐 Authentification réussie - Session sauvegardée');
        saveSessionState();
    });

    // Déconnexion
    client.on('disconnected', (reason) => {
        isConnected = false;
        qrCode = null;
        connectionStatus = 'disconnected';
        console.log('❌ WhatsApp déconnecté:', reason);
        
        // Nettoyer la session
        cleanupSession();
        
        // Reconnexion automatique après 5 secondes
        setTimeout(() => {
            console.log('🔄 Tentative de reconnexion automatique...');
            initializeWhatsApp();
            client.initialize().catch(error => {
                console.log('❌ Erreur lors de la réinitialisation:', error);
            });
        }, 5000);
    });

    // Erreurs
    client.on('auth_failure', (error) => {
        console.log('❌ Échec de l\'authentification:', error);
        connectionStatus = 'error';
        isConnected = false;
        cleanupSession();
        saveSessionState();
    });

    // Erreur générale
    client.on('error', (error) => {
        console.log('❌ Erreur WhatsApp:', error);
        connectionStatus = 'error';
        saveSessionState();
    });

    // Initialiser le client
    client.initialize().catch(error => {
        console.log('❌ Erreur lors de l\'initialisation:', error);
        connectionStatus = 'error';
        saveSessionState();
    });
}

// Nettoyage de session
function cleanupSession() {
    try {
        const statePath = path.join(SESSION_DIR, 'session-state.json');
        if (fs.existsSync(statePath)) {
            fs.unlinkSync(statePath);
            console.log('🗑️ Fichier d\'état supprimé');
        }
    } catch (error) {
        console.log('❌ Erreur lors du nettoyage:', error);
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
        timestamp: new Date().toISOString()
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

// Route pour forcer la restauration
app.post('/api/restore', async (req, res) => {
    try {
        console.log('🔄 Tentative de restauration de session...');
        
        if (!client) {
            initializeWhatsApp();
        }
        
        // Vérifier l'état de la session
        const sessionState = loadSessionState();
        
        res.json({ 
            success: true, 
            message: 'Restauration de la session démarrée',
            hasSession: !!sessionState,
            previousState: sessionState
        });

    } catch (error) {
        console.error('❌ Erreur restauration:', error);
        res.json({ 
            success: false, 
            error: 'Erreur lors de la restauration: ' + error.message 
        });
    }
});

// Route pour réinitialiser complètement la session
app.post('/api/reset', async (req, res) => {
    try {
        console.log('🔄 Réinitialisation complète demandée...');
        
        if (client) {
            await client.destroy();
            client = null;
        }
        
        isConnected = false;
        qrCode = null;
        connectionStatus = 'disconnected';
        
        // Supprimer tous les fichiers de session
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
        
        // Réinitialiser
        setTimeout(() => {
            initializeWhatsApp();
        }, 1000);
        
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

// Route de diagnostic des sessions
app.get('/api/debug-sessions', (req, res) => {
    try {
        const sessionPath = path.join(SESSION_DIR, 'masssender-client');
        const statePath = path.join(SESSION_DIR, 'session-state.json');
        
        const sessionExists = fs.existsSync(sessionPath);
        const stateExists = fs.existsSync(statePath);
        
        let sessionInfo = {};
        let stateInfo = {};
        
        if (sessionExists) {
            const files = fs.readdirSync(sessionPath);
            sessionInfo = {
                exists: true,
                fileCount: files.length,
                files: files
            };
        }
        
        if (stateExists) {
            const stateContent = fs.readFileSync(statePath, 'utf8');
            stateInfo = {
                exists: true,
                content: JSON.parse(stateContent)
            };
        }
        
        res.json({
            session: sessionInfo,
            state: stateInfo,
            currentStatus: {
                isConnected,
                qrCode: !!qrCode,
                connectionStatus
            },
            clientInitialized: !!client
        });
        
    } catch (error) {
        res.json({ error: error.message });
    }
});

// Route de santé améliorée
app.get('/api/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        timestamp: new Date().toISOString(),
        whatsapp_status: connectionStatus,
        connected: isConnected,
        has_qr: !!qrCode,
        persistent: true,
        client_initialized: !!client
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
    isConnected = false; // Reset until confirmed
}
setTimeout(() => {
    initializeWhatsApp();
}, 2000);

app.listen(PORT, '0.0.0.0', () => {
    console.log(`🎯 Backend WhatsApp démarré sur le port ${PORT}`);
    console.log(`💾 Sessions sauvegardées dans: ${SESSION_DIR}`);
    console.log(`🔍 Diagnostic disponible sur: http://localhost:${PORT}/api/debug-sessions`);
});