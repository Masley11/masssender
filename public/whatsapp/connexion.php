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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        
        .status-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .status-connected {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .status-disconnected {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .status-waiting {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        
        .btn-start {
            background: #28a745;
            color: white;
        }
        
        .btn-stop {
            background: #dc3545;
            color: white;
        }
        
        .btn-send {
            background: #007bff;
            color: white;
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        
        .qr-code img {
            max-width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .hidden {
            display: none;
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
                Le service WhatsApp n'est pas accessible. Vérifiez que le backend est démarré sur le port 3001.
            </div>
        <?php endif; ?>
        
        <!-- Carte de statut -->
        <div class="status-card <?php echo $isConnected ? 'status-connected' : ($qrCode ? 'status-waiting' : 'status-disconnected'); ?>">
            <h3>Statut de la connexion</h3>
            
            <?php if ($isConnected): ?>
                <p><strong>✅ WhatsApp est connecté</strong></p>
                <p>Vous pouvez maintenant envoyer des messages.</p>
            <?php elseif ($qrCode): ?>
                <p><strong>📱 Code QR disponible</strong></p>
                <p>Scannez le code QR avec votre téléphone pour vous connecter.</p>
            <?php else: ?>
                <p><strong>❌ WhatsApp n'est pas connecté</strong></p>
                <p>Démarrez la connexion pour générer un code QR.</p>
            <?php endif; ?>
            
            <div id="statusMessage"></div>
        </div>
        
        <!-- Actions -->
        <div style="text-align: center; margin: 20px 0;">
            <button id="btnStart" class="btn btn-start" <?php echo $isConnected ? 'disabled' : ''; ?>>
                🚀 Démarrer WhatsApp
            </button>
            <button id="btnStop" class="btn btn-stop" <?php echo !$isConnected ? 'disabled' : ''; ?>>
                🛑 Arrêter WhatsApp
            </button>
            <button id="btnRefresh" class="btn">
                🔄 Actualiser le statut
            </button>
        </div>
        
        <!-- QR Code -->
        <?php if ($qrCode): ?>
            <div class="qr-code">
                <h3>Code QR de connexion</h3>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo urlencode($qrCode); ?>" 
                     alt="QR Code WhatsApp">
                <p>Scannez ce code avec l'application WhatsApp > Paramètres > Appareils connectés</p>
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
                    📨 Envoyer le message
                </button>
                
                <div id="messageResult" style="margin-top: 15px;"></div>
            </form>
        </div>
        
        <!-- Logs en temps réel -->
        <div style="margin-top: 30px;">
            <h3>📊 Logs en temps réel</h3>
            <div id="logs" style="background: #f8f9fa; padding: 15px; border-radius: 5px; height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                <!-- Les logs seront affichés ici -->
            </div>
        </div>
    </div>

    <script>
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
        logEntry.innerHTML = `<span style="color: #666;">[${timestamp}]</span> ${message}`;
        
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
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(data.qr)}" 
                         alt="QR Code WhatsApp">
                    <p>Scannez ce code avec l'application WhatsApp > Paramètres > Appareils connectés</p>
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