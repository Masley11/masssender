<?php
// frontend/whatsapp/connexion.php

// DÉBUT DU CODE - Vérifier si c'est une requête AJAX
if ($_POST['action'] ?? false) {
    require_once 'WhatsAppService.php';
    $whatsapp = new WhatsAppService();
    
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'start':
            $result = $whatsapp->startConnection();
            echo json_encode($result);
            exit;
            
        case 'stop':
            $result = $whatsapp->stopConnection();
            echo json_encode($result);
            exit;
            
        case 'send_message':
            $phone = $_POST['phone'] ?? '';
            $message = $_POST['message'] ?? '';
            
            if (empty($phone) || empty($message)) {
                echo json_encode([
                    'success' => false, 
                    'error' => 'Le numéro et le message sont obligatoires'
                ]);
                exit;
            }
            
            $result = $whatsapp->sendMessage($phone, $message);
            echo json_encode($result);
            exit;
            
        case 'get_status':
            $status = $whatsapp->getStatus();
            echo json_encode($status);
            exit;
            
        case 'check_backend':
            echo json_encode([
                'alive' => $whatsapp->isBackendAlive(),
                'backend_url' => 'http://localhost:3001'
            ]);
            exit;
            
        default:
            echo json_encode([
                'success' => false, 
                'error' => 'Action non reconnue'
            ]);
            exit;
    }
}

// SI CE N'EST PAS UNE REQUÊTE AJAX, AFFICHER L'INTERFACE NORMALE
include __DIR__ . '/../includes/header.php';

require_once 'WhatsAppService.php';

$whatsapp = new WhatsAppService();
$status = $whatsapp->getStatus();
$isConnected = $status['connected'] ?? false;
$qrCode = $status['qr'] ?? null;
$isBackendAlive = $whatsapp->isBackendAlive();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion WhatsApp</title>
    <style>
        .whatsapp-page {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        h1 {
            text-align: center;
            color: #25D366;
            margin-bottom: 30px;
        }
        
        h3 {
            color: #075E54;
            margin-top: 0;
        }
        
        .status-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        
        .status-connected {
            border-left: 4px solid #25D366;
        }
        
        .status-disconnected {
            border-left: 4px solid #ff4444;
        }
        
        .status-waiting {
            border-left: 4px solid #ffbb33;
        }
        
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin: 4px;
            transition: all 0.2s;
        }
        
        .btn-start {
            background: #25D366;
            color: white;
        }
        
        .btn-stop {
            background: #ff4444;
            color: white;
        }
        
        .btn-send {
            background: #128C7E;
            color: white;
            width: 100%;
            padding: 12px;
        }
        
        .btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        
        .btn:hover:not(:disabled) {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        
        .qr-code img {
            max-width: 250px;
            border-radius: 8px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }
        
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        small {
            color: #666;
            font-size: 12px;
        }
        
        .alert {
            padding: 10px 12px;
            border-radius: 6px;
            margin: 10px 0;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #25D366;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .hidden {
            display: none;
        }
        
        .actions {
            text-align: center;
            margin: 20px 0;
        }
        
        #logs {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            height: 150px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            border: 1px solid #e0e0e0;
        }
        
        .log-entry {
            margin-bottom: 4px;
        }
        
        .log-time {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="whatsapp-page">
        <h1>📱 Connexion WhatsApp</h1>
        
        <!-- Statut du backend -->
        <?php if (!$isBackendAlive): ?>
            <div class="alert alert-error">
                <strong>⚠️ Service indisponible</strong><br>
                Le service WhatsApp n'est pas accessible.
            </div>
        <?php endif; ?>
        
        <!-- Carte de statut -->
        <div class="status-card <?php echo $isConnected ? 'status-connected' : ($qrCode ? 'status-waiting' : 'status-disconnected'); ?>">
            <h3>Statut de la connexion</h3>
            
            <?php if ($isConnected): ?>
                <p><strong>✅ WhatsApp est connecté</strong></p>
            <?php elseif ($qrCode): ?>
                <p><strong>📱 Code QR disponible</strong></p>
            <?php else: ?>
                <p><strong>❌ WhatsApp n'est pas connecté</strong></p>
            <?php endif; ?>
            
            <div id="statusMessage"></div>
        </div>
        
        <!-- Actions -->
        <div class="actions">
            <button id="btnStart" class="btn btn-start" <?php echo $isConnected ? 'disabled' : ''; ?>>
                Démarrer
            </button>
            <button id="btnStop" class="btn btn-stop" <?php echo !$isConnected ? 'disabled' : ''; ?>>
                Arrêter
            </button>
            <button id="btnRefresh" class="btn">
                Actualiser
            </button>
        </div>
        
        <!-- QR Code -->
        <?php if ($qrCode): ?>
            <div class="qr-code">
                <h3>Code QR de connexion</h3>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode($qrCode); ?>" 
                     alt="QR Code WhatsApp">
                <p>Scannez ce code avec WhatsApp > Paramètres > Appareils connectés</p>
            </div>
        <?php endif; ?>
        
        <!-- Formulaire d'envoi de message -->
        <div id="messageForm" <?php echo !$isConnected ? 'class="hidden"' : ''; ?>>
            <h3>📤 Envoyer un message</h3>
            
            <form id="sendMessageForm">
                <div class="form-group">
                    <label for="phone">Numéro de téléphone:</label>
                    <input type="text" id="phone" name="phone" 
                           placeholder="Ex: 612345678" required>
                    <small>Format: 612345678 (sans indicatif)</small>
                </div>
                
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="4" 
                              placeholder="Tapez votre message ici..." required></textarea>
                </div>
                
                <button type="submit" class="btn btn-send">
                    Envoyer le message
                </button>
                
                <div id="messageResult" style="margin-top: 15px;"></div>
            </form>
        </div>
        
        <!-- Logs en temps réel -->
        <div style="margin-top: 30px;">
            <h3>📊 Logs</h3>
            <div id="logs">
                <!-- Les logs seront affichés ici -->
            </div>
        </div>
    </div>

    <script>
    // Le code JavaScript reste exactement le même
    // Éléments DOM
    const btnStart = document.getElementById('btnStart');
    const btnStop = document.getElementById('btnStop');
    const btnRefresh = document.getElementById('btnRefresh');
    const sendMessageForm = document.getElementById('sendMessageForm');
    const statusMessage = document.getElementById('statusMessage');
    const messageResult = document.getElementById('messageResult');
    const messageForm = document.getElementById('messageForm');
    const logsDiv = document.getElementById('logs');
    
    // Ajouter un log
    function addLog(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = document.createElement('div');
        logEntry.className = 'log-entry';
        logEntry.innerHTML = `<span class="log-time">[${timestamp}]</span> ${message}`;
        
        if (type === 'error') {
            logEntry.style.color = '#dc3545';
        } else if (type === 'success') {
            logEntry.style.color = '#28a745';
        }
        
        logsDiv.appendChild(logEntry);
        logsDiv.scrollTop = logsDiv.scrollHeight;
    }
    
    // Mettre à jour le statut
    async function updateStatus() {
        addLog('Actualisation du statut...');
        statusMessage.innerHTML = '<div class="loading"></div> Actualisation...';
        
        try {
            const response = await fetch('connexion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_status'
            });
            
            const data = await response.json();
            
            // Mettre à jour l'interface
            if (data.connected) {
                btnStart.disabled = true;
                btnStop.disabled = false;
                messageForm.classList.remove('hidden');
                statusMessage.innerHTML = '<div class="alert alert-success">✅ Connecté</div>';
                
                // Cacher le QR code si connecté
                const qrContainer = document.querySelector('.qr-code');
                if (qrContainer) {
                    qrContainer.style.display = 'none';
                }
                
            } else if (data.qr) {
                btnStart.disabled = true;
                btnStop.disabled = false;
                messageForm.classList.add('hidden');
                statusMessage.innerHTML = '<div class="alert alert-warning">📱 QR Code disponible - Scannez pour vous connecter</div>';
                
                // Afficher le QR code SANS recharger la page
                let qrContainer = document.querySelector('.qr-code');
                if (!qrContainer) {
                    qrContainer = document.createElement('div');
                    qrContainer.className = 'qr-code';
                    document.querySelector('.whatsapp-page').appendChild(qrContainer);
                }
                
                qrContainer.innerHTML = `
                    <h3>Code QR de connexion</h3>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(data.qr)}" 
                         alt="QR Code WhatsApp">
                    <p>Scannez ce code avec WhatsApp > Paramètres > Appareils connectés</p>
                `;
                
            } else {
                btnStart.disabled = false;
                btnStop.disabled = true;
                messageForm.classList.add('hidden');
                statusMessage.innerHTML = '<div class="alert alert-error">❌ Déconnecté</div>';
                
                // Cacher le QR code si déconnecté
                const qrContainer = document.querySelector('.qr-code');
                if (qrContainer) {
                    qrContainer.style.display = 'none';
                }
            }
            
            addLog('Statut actualisé: ' + (data.connected ? 'Connecté' : 'Déconnecté'));
            
        } catch (error) {
            addLog('Erreur lors de la vérification du statut: ' + error.message, 'error');
            statusMessage.innerHTML = '<div class="alert alert-error">❌ Erreur de connexion au serveur</div>';
        }
    }
    
    // Démarrer WhatsApp
    async function startWhatsApp() {
        addLog('Démarrage de WhatsApp...');
        statusMessage.innerHTML = '<div class="loading"></div> Démarrage en cours...';
        btnStart.disabled = true;
        
        try {
            const response = await fetch('connexion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=start'
            });
            
            const data = await response.json();
            
            if (data.success) {
                addLog('WhatsApp démarré avec succès', 'success');
                statusMessage.innerHTML = '<div class="alert alert-success">✅ ' + data.message + '</div>';
                // Actualiser le statut après un délai
                setTimeout(updateStatus, 3000);
            } else {
                addLog('Erreur démarrage: ' + data.error, 'error');
                statusMessage.innerHTML = '<div class="alert alert-error">❌ ' + data.error + '</div>';
                btnStart.disabled = false;
            }
            
        } catch (error) {
            addLog('Erreur: ' + error.message, 'error');
            statusMessage.innerHTML = '<div class="alert alert-error">❌ Erreur de connexion</div>';
            btnStart.disabled = false;
        }
    }
    
    // Arrêter WhatsApp
    async function stopWhatsApp() {
        addLog('Arrêt de WhatsApp...');
        statusMessage.innerHTML = '<div class="loading"></div> Arrêt en cours...';
        btnStop.disabled = true;
        
        try {
            const response = await fetch('connexion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=stop'
            });
            
            const data = await response.json();
            
            if (data.success) {
                addLog('WhatsApp arrêté avec succès', 'success');
                statusMessage.innerHTML = '<div class="alert alert-success">✅ WhatsApp arrêté</div>';
                // Actualiser le statut après un délai
                setTimeout(updateStatus, 2000);
            } else {
                addLog('Erreur arrêt: ' + data.error, 'error');
                statusMessage.innerHTML = '<div class="alert alert-error">❌ ' + data.error + '</div>';
                btnStop.disabled = false;
            }
            
        } catch (error) {
            addLog('Erreur: ' + error.message, 'error');
            statusMessage.innerHTML = '<div class="alert alert-error">❌ Erreur de connexion</div>';
            btnStop.disabled = false;
        }
    }
    
    // Envoyer un message
    async function sendMessage(phone, message) {
        addLog(`Envoi message à ${phone}...`);
        messageResult.innerHTML = '<div class="loading"></div> Envoi en cours...';
        
        try {
            const response = await fetch('connexion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=send_message&phone=${encodeURIComponent(phone)}&message=${encodeURIComponent(message)}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                addLog(`✅ Message envoyé à ${phone}`, 'success');
                messageResult.innerHTML = '<div class="alert alert-success">✅ Message envoyé avec succès</div>';
                // Effacer le formulaire
                sendMessageForm.reset();
            } else {
                addLog(`❌ Erreur envoi: ${data.error}`, 'error');
                messageResult.innerHTML = '<div class="alert alert-error">❌ ' + data.error + '</div>';
            }
            
        } catch (error) {
            addLog('Erreur envoi: ' + error.message, 'error');
            messageResult.innerHTML = '<div class="alert alert-error">❌ Erreur de connexion</div>';
        }
    }
    
    // Événements
    btnStart.addEventListener('click', startWhatsApp);
    btnStop.addEventListener('click', stopWhatsApp);
    btnRefresh.addEventListener('click', updateStatus);
    
    sendMessageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const phone = document.getElementById('phone').value.trim();
        const message = document.getElementById('message').value.trim();
        
        if (!phone || !message) {
            messageResult.innerHTML = '<div class="alert alert-error">❌ Veuillez remplir tous les champs</div>';
            return;
        }
        
        sendMessage(phone, message);
    });
    
    // Actualisation automatique du statut toutes les 10 secondes
    setInterval(updateStatus, 10000);
    
    // Initialisation
    addLog('Interface WhatsApp initialisée');
    updateStatus();
</script>
</body>
</html>
