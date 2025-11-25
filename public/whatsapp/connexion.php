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
            
        case 'refresh_qr':
            $result = $whatsapp->refreshQR();
            echo json_encode($result);
            exit;
            
        case 'reset':
            $result = $whatsapp->resetConnection();
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
                'backend_url' => $whatsapp->getBackendUrl()
            ]);
            exit;
            
        case 'debug_info':
            $result = $whatsapp->getDebugInfo();
            echo json_encode($result);
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
$connectionStatus = $status['status'] ?? 'disconnected';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion WhatsApp - MassSender</title>
    <style>
        .whatsapp-page {
            max-width: 800px;
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        h3 {
            color: #075E54;
            margin-top: 0;
            border-bottom: 2px solid #25D366;
            padding-bottom: 8px;
        }
        
        .status-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .status-card:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .status-connected {
            border-left: 6px solid #25D366;
            background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
        }
        
        .status-disconnected {
            border-left: 6px solid #ff4444;
            background: linear-gradient(135deg, #fff8f8 0%, #f5e8e8 100%);
        }
        
        .status-waiting {
            border-left: 6px solid #ffbb33;
            background: linear-gradient(135deg, #fffbf0 0%, #f5f0e8 100%);
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin: 6px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-start {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
        }
        
        .btn-stop {
            background: linear-gradient(135deg, #ff4444 0%, #cc0000 100%);
            color: white;
        }
        
        .btn-refresh-qr {
            background: linear-gradient(135deg, #ff8800 0%, #ff6600 100%);
            color: white;
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #666666 0%, #444444 100%);
            color: white;
        }
        
        .btn-refresh {
            background: linear-gradient(135deg, #3399ff 0%, #0066cc 100%);
            color: white;
        }
        
        .btn-send {
            background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
            color: white;
            width: 100%;
            padding: 14px;
            font-size: 16px;
            justify-content: center;
        }
        
        .btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        
        .btn:hover:not(:disabled) {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn:active:not(:disabled) {
            transform: translateY(0);
        }
        
        .qr-code {
            text-align: center;
            margin: 25px 0;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 2px dashed #25D366;
        }
        
        .qr-code img {
            max-width: 280px;
            border-radius: 12px;
            border: 2px solid #25D366;
            padding: 10px;
            background: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #075E54;
        }
        
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-group input:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: #25D366;
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        small {
            color: #666;
            font-size: 12px;
            display: block;
            margin-top: 4px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin: 12px 0;
            font-size: 14px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left-color: #ffc107;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left-color: #17a2b8;
        }
        
        .loading {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #25D366;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            vertical-align: middle;
            margin-right: 10px;
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
            margin: 25px 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }
        
        #logs {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            height: 200px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            border: 1px solid #e0e0e0;
            background: #1e1e1e;
            color: #00ff00;
        }
        
        .log-entry {
            margin-bottom: 6px;
            padding: 4px 0;
            border-bottom: 1px solid #333;
        }
        
        .log-time {
            color: #888;
        }
        
        .log-info {
            color: #00ff00;
        }
        
        .log-success {
            color: #00ff00;
            font-weight: bold;
        }
        
        .log-error {
            color: #ff4444;
            font-weight: bold;
        }
        
        .log-warning {
            color: #ffbb33;
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin: 10px 0;
        }
        
        .status-indicator.connected {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }
        
        .status-indicator.disconnected {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }
        
        .status-indicator.waiting {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
        }
        
        .debug-panel {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid #e0e0e0;
        }
        
        .debug-toggle {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 12px;
            text-decoration: underline;
        }
        
        .debug-content {
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            background: white;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            max-height: 200px;
            overflow-y: auto;
        }
        
        @media (max-width: 768px) {
            .whatsapp-page {
                padding: 15px;
            }
            
            .actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
            
            .qr-code img {
                max-width: 220px;
            }
        }
    </style>
</head>
<body>
    <div class="whatsapp-page">
        <h1>
            <span>📱</span>
            Connexion WhatsApp - MassSender
        </h1>
        
        <!-- Statut du backend -->
        <?php if (!$isBackendAlive): ?>
            <div class="alert alert-error">
                <strong>⚠️ Service indisponible</strong><br>
                Le service WhatsApp n'est pas accessible. Vérifiez que le backend est démarré.
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <strong>✅ Backend connecté</strong><br>
                Le service WhatsApp est accessible et fonctionnel.
            </div>
        <?php endif; ?>
        
        <!-- Carte de statut -->
        <div class="status-card <?php echo $isConnected ? 'status-connected' : ($qrCode ? 'status-waiting' : 'status-disconnected'); ?>">
            <h3>📊 Statut de la connexion WhatsApp</h3>
            
            <div class="status-indicator <?php echo $isConnected ? 'connected' : ($qrCode ? 'waiting' : 'disconnected'); ?>">
                <?php if ($isConnected): ?>
                    <span>✅</span>
                    <span>WhatsApp est connecté</span>
                <?php elseif ($qrCode): ?>
                    <span>📱</span>
                    <span>En attente du scan du QR Code</span>
                <?php else: ?>
                    <span>❌</span>
                    <span>WhatsApp n'est pas connecté</span>
                <?php endif; ?>
            </div>
            
            <div id="statusMessage">
                <?php if ($qrCode): ?>
                    <div class="alert alert-warning">
                        <strong>Scannez le QR Code ci-dessous avec WhatsApp</strong><br>
                        Allez dans WhatsApp → Paramètres → Appareils connectés → Lier un appareil
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Actions principales -->
        <div class="actions">
            <button id="btnStart" class="btn btn-start" <?php echo $isConnected || $qrCode ? 'disabled' : ''; ?>>
                <span>🚀</span>
                Démarrer
            </button>
            <button id="btnStop" class="btn btn-stop" <?php echo !$isConnected && !$qrCode ? 'disabled' : ''; ?>>
                <span>🛑</span>
                Arrêter
            </button>
            <button id="btnRefreshQR" class="btn btn-refresh-qr" <?php echo !$qrCode ? 'disabled' : ''; ?>>
                <span>🔄</span>
                Nouveau QR Code
            </button>
            <button id="btnReset" class="btn btn-reset">
                <span>🗑️</span>
                Réinitialiser
            </button>
            <button id="btnRefresh" class="btn btn-refresh">
                <span>📡</span>
                Actualiser
            </button>
        </div>
        
        <!-- QR Code -->
        <?php if ($qrCode): ?>
            <div class="qr-code">
                <h3>🔐 Code QR de connexion</h3>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=<?php echo urlencode($qrCode); ?>&format=png&margin=10&color=25D366&bgcolor=ffffff" 
                     alt="QR Code WhatsApp">
                <p><strong>Instructions :</strong> Scannez ce code avec WhatsApp → Paramètres → Appareils connectés → Lier un appareil</p>
                <small>Le QR Code expire après un certain temps. Utilisez "Nouveau QR Code" si nécessaire.</small>
            </div>
        <?php endif; ?>
        
        <!-- Formulaire d'envoi de message -->
        <div id="messageForm" <?php echo !$isConnected ? 'class="hidden"' : ''; ?>>
            <div class="status-card status-connected">
                <h3>📤 Envoyer un message WhatsApp</h3>
                
                <form id="sendMessageForm">
                    <div class="form-group">
                        <label for="phone">📞 Numéro de téléphone:</label>
                        <input type="text" id="phone" name="phone" 
                               placeholder="Ex: 612345678" required
                               pattern="[0-9]{9,12}"
                               title="Format: 612345678 (9 à 12 chiffres sans indicatif)">
                        <small>Format: 612345678 (9 à 12 chiffres, sans indicatif international)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">💬 Message:</label>
                        <textarea id="message" name="message" rows="4" 
                                  placeholder="Tapez votre message WhatsApp ici..." 
                                  required maxlength="1000"></textarea>
                        <small id="charCount">0/1000 caractères</small>
                    </div>
                    
                    <button type="submit" class="btn btn-send">
                        <span>📤</span>
                        Envoyer le message WhatsApp
                    </button>
                    
                    <div id="messageResult" style="margin-top: 20px;"></div>
                </form>
            </div>
        </div>
        
        <!-- Logs en temps réel -->
        <div class="status-card">
            <h3>📊 Journal d'activité</h3>
            <div id="logs">
                <div class="log-entry">
                    <span class="log-time">[<?php echo date('H:i:s'); ?>]</span>
                    <span class="log-info">Interface WhatsApp initialisée</span>
                </div>
                <?php if (!$isBackendAlive): ?>
                <div class="log-entry">
                    <span class="log-time">[<?php echo date('H:i:s'); ?>]</span>
                    <span class="log-error">ERREUR: Backend WhatsApp non accessible</span>
                </div>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 10px;">
                <button id="btnClearLogs" class="btn" style="background: #666; color: white; padding: 8px 16px; font-size: 12px;">
                    Effacer les logs
                </button>
            </div>
        </div>
        
        <!-- Panel de débogage -->
        <div class="debug-panel">
            <h3>🔧 Outils de débogage</h3>
            <button id="btnDebug" class="debug-toggle">Afficher les informations de débogage</button>
            <div id="debugContent" class="debug-content hidden">
                <!-- Les informations de débogage seront affichées ici -->
            </div>
        </div>
    </div>

    <script>
    // Éléments DOM
    const btnStart = document.getElementById('btnStart');
    const btnStop = document.getElementById('btnStop');
    const btnRefreshQR = document.getElementById('btnRefreshQR');
    const btnReset = document.getElementById('btnReset');
    const btnRefresh = document.getElementById('btnRefresh');
    const btnClearLogs = document.getElementById('btnClearLogs');
    const btnDebug = document.getElementById('btnDebug');
    const sendMessageForm = document.getElementById('sendMessageForm');
    const statusMessage = document.getElementById('statusMessage');
    const messageResult = document.getElementById('messageResult');
    const messageForm = document.getElementById('messageForm');
    const logsDiv = document.getElementById('logs');
    const debugContent = document.getElementById('debugContent');
    const charCount = document.getElementById('charCount');
    const messageTextarea = document.getElementById('message');
    
    // Variables d'état
    let isAutoRefresh = true;
    
 // Compteur de caractères pour le message
    messageTextarea.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = `${length}/1000 caractères`;
        if (length > 900) {
            charCount.style.color = '#ff4444';
        } else if (length > 800) {
            charCount.style.color = '#ff8800';
        } else {
            charCount.style.color = '#666';
        }
    });
    
    // Ajouter un log
    function addLog(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = document.createElement('div');
        logEntry.className = 'log-entry';
        
        let typeClass = 'log-info';
        if (type === 'error') typeClass = 'log-error';
        if (type === 'success') typeClass = 'log-success';
        if (type === 'warning') typeClass = 'log-warning';
        
        logEntry.innerHTML = `<span class="log-time">[${timestamp}]</span> <span class="${typeClass}">${message}</span>`;
        
        logsDiv.appendChild(logEntry);
        logsDiv.scrollTop = logsDiv.scrollHeight;
    }
    
    // Effacer les logs
    btnClearLogs.addEventListener('click', function() {
        logsDiv.innerHTML = '';
        addLog('Journal effacé', 'info');
    });
    
    // Basculer le débogage
    btnDebug.addEventListener('click', async function() {
        if (debugContent.classList.contains('hidden')) {
            addLog('Récupération des informations de débogage...', 'info');
            debugContent.innerHTML = '<div class="loading"></div> Chargement...';
            debugContent.classList.remove('hidden');
            btnDebug.textContent = 'Masquer les informations de débogage';
            
            try {
                const response = await fetch('connexion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=debug_info'
                });
                
                const data = await response.json();
                debugContent.textContent = JSON.stringify(data, null, 2);
                addLog('Informations de débogage chargées', 'success');
            } catch (error) {
                debugContent.textContent = 'Erreur: ' + error.message;
                addLog('Erreur lors du chargement du débogage', 'error');
            }
        } else {
            debugContent.classList.add('hidden');
            btnDebug.textContent = 'Afficher les informations de débogage';
        }
    });
    
    // Mettre à jour le statut
    async function updateStatus() {
        if (!isAutoRefresh) return;
        
        addLog('Actualisation du statut...', 'info');
        
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
            updateInterface(data);
            
        } catch (error) {
            addLog('Erreur lors de la vérification du statut: ' + error.message, 'error');
            statusMessage.innerHTML = '<div class="alert alert-error">❌ Erreur de connexion au serveur</div>';
        }
    }
    
    // Mettre à jour l'interface
    function updateInterface(data) {
        if (data.connected) {
            // Connecté
            btnStart.disabled = true;
            btnStop.disabled = false;
            btnRefreshQR.disabled = true;
            messageForm.classList.remove('hidden');
            statusMessage.innerHTML = '<div class="alert alert-success">✅ WhatsApp est connecté et prêt à envoyer des messages</div>';
            
            // Cacher le QR code
            const qrContainer = document.querySelector('.qr-code');
            if (qrContainer) {
                qrContainer.style.display = 'none';
            }
            
            addLog('Statut: Connecté avec succès', 'success');
            
        } else if (data.qr) {
            // En attente de QR
            btnStart.disabled = true;
            btnStop.disabled = false;
            btnRefreshQR.disabled = false;
            messageForm.classList.add('hidden');
            statusMessage.innerHTML = '<div class="alert alert-warning">📱 QR Code disponible - Scannez avec WhatsApp pour vous connecter</div>';
            
            // Afficher/mettre à jour le QR code
            let qrContainer = document.querySelector('.qr-code');
            if (!qrContainer) {
                qrContainer = document.createElement('div');
                qrContainer.className = 'qr-code';
                document.querySelector('.whatsapp-page').insertBefore(qrContainer, messageForm);
            }
            
            qrContainer.style.display = 'block';
            qrContainer.innerHTML = `
                <h3>🔐 Code QR de connexion</h3>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=${encodeURIComponent(data.qr)}&format=png&margin=10&color=25D366&bgcolor=ffffff" 
                     alt="QR Code WhatsApp">
                <p><strong>Instructions :</strong> Scannez ce code avec WhatsApp → Paramètres → Appareils connectés → Lier un appareil</p>
                <small>Le QR Code expire après un certain temps. Utilisez "Nouveau QR Code" si nécessaire.</small>
            `;
            
            addLog('Statut: QR Code disponible - En attente du scan', 'warning');
            
        } else {
            // Déconnecté
            btnStart.disabled = false;
            btnStop.disabled = true;
            btnRefreshQR.disabled = true;
            messageForm.classList.add('hidden');
            statusMessage.innerHTML = '<div class="alert alert-error">❌ WhatsApp déconnecté - Cliquez sur "Démarrer" pour vous connecter</div>';
            
            // Cacher le QR code
            const qrContainer = document.querySelector('.qr-code');
            if (qrContainer) {
                qrContainer.style.display = 'none';
            }
            
            addLog('Statut: Déconnecté', 'info');
        }
    }
    
    // Démarrer WhatsApp
    async function startWhatsApp() {
        addLog('Démarrage de WhatsApp...', 'info');
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
                addLog('WhatsApp démarré avec succès - QR Code en attente', 'success');
                statusMessage.innerHTML = '<div class="alert alert-success">✅ ' + data.message + '</div>';
                // Actualiser le statut après un délai
                setTimeout(updateStatus, 2000);
            } else {
                addLog('Erreur démarrage: ' + data.error, 'error');
                statusMessage.innerHTML = '<div class="alert alert-error">❌ ' + data.error + '</div>';
                btnStart.disabled = false;
            }
            
        } catch (error) {
            addLog('Erreur démarrage: ' + error.message, 'error');
            statusMessage.innerHTML = '<div class="alert alert-error">❌ Erreur de connexion au serveur</div>';
            btnStart.disabled = false;
        }
    }
    
    // Arrêter WhatsApp
    async function stopWhatsApp() {
        if (!confirm('Êtes-vous sûr de vouloir arrêter la connexion WhatsApp ?')) {
            return;
        }
        
        addLog('Arrêt de WhatsApp...', 'info');
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
                statusMessage.innerHTML = '<div class="alert alert-success">✅ ' + data.message + '</div>';
                // Actualiser le statut après un délai
                setTimeout(updateStatus, 2000);
            } else {
                addLog('Erreur arrêt: ' + data.error, 'error');
                statusMessage.innerHTML = '<div class="alert alert-error">❌ ' + data.error + '</div>';
                btnStop.disabled = false;
            }
            
        } catch (error) {
            addLog('Erreur arrêt: ' + error.message, 'error');
            statusMessage.innerHTML = '<div class="alert alert-error">❌ Erreur de connexion</div>';
            btnStop.disabled = false;
        }
    }
    
    // Rafraîchir le QR Code
    async function refreshQRCode() {
        addLog('Génération d\'un nouveau QR Code...', 'info');
        statusMessage.innerHTML = '<div class="loading"></div> Génération d\'un nouveau QR Code...';
        btnRefreshQR.disabled = true;
        
        try {
            const response = await fetch('connexion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=refresh_qr'
            });
            
            const data = await response.json();
            
            if (data.success) {
                addLog('Nouveau QR Code généré avec succès', 'success');
                statusMessage.innerHTML = '<div class="alert alert-success">✅ ' + data.message + '</div>';
                // Actualiser après délai
                setTimeout(updateStatus, 3000);
            } else {
                addLog('Erreur génération QR: ' + data.error, 'error');
                statusMessage.innerHTML = '<div class="alert alert-error">❌ ' + data.error + '</div>';
                btnRefreshQR.disabled = false;
            }
            
        } catch (error) {
            addLog('Erreur génération QR: ' + error.message, 'error');
            statusMessage.innerHTML = '<div class="alert alert-error">❌ Erreur de connexion</div>';
            btnRefreshQR.disabled = false;
        }
    }
    
    // Réinitialiser complètement
    async function resetConnection() {
        if (!confirm('Êtes-vous sûr de vouloir réinitialiser complètement la session WhatsApp ? Cette action supprimera toutes les données de session.')) {
            return;
        }
        
        addLog('Réinitialisation complète de la session...', 'warning');
        statusMessage.innerHTML = '<div class="loading"></div> Réinitialisation en cours...';
        
        try {
            const response = await fetch('connexion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=reset'
            });
            
            const data = await response.json();
            
            if (data.success) {
                addLog('Session complètement réinitialisée', 'success');
                statusMessage.innerHTML = '<div class="alert alert-success">✅ ' + data.message + '</div>';
                // Actualiser après délai
                setTimeout(updateStatus, 3000);
            } else {
                addLog('Erreur réinitialisation: ' + data.error, 'error');
                statusMessage.innerHTML = '<div class="alert alert-error">❌ ' + data.error + '</div>';
            }
            
        } catch (error) {
            addLog('Erreur réinitialisation: ' + error.message, 'error');
            statusMessage.innerHTML = '<div class="alert alert-error">❌ Erreur de connexion</div>';
        }
    }
    
    // Envoyer un message
    async function sendMessage(phone, message) {
        addLog(`Envoi de message à ${phone}...`, 'info');
        messageResult.innerHTML = '<div class="loading"></div> Envoi du message en cours...';
        
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
                addLog(`✅ Message envoyé avec succès à ${phone}`, 'success');
                messageResult.innerHTML = '<div class="alert alert-success">✅ Message envoyé avec succès</div>';
                // Effacer le formulaire
                sendMessageForm.reset();
                charCount.textContent = '0/1000 caractères';
                charCount.style.color = '#666';
            } else {
                addLog(`❌ Erreur envoi à ${phone}: ${data.error}`, 'error');
                messageResult.innerHTML = '<div class="alert alert-error">❌ ' + data.error + '</div>';
            }
            
        } catch (error) {
            addLog('Erreur envoi: ' + error.message, 'error');
            messageResult.innerHTML = '<div class="alert alert-error">❌ Erreur de connexion lors de l\'envoi</div>';
        }
    }
    
    // Événements
    btnStart.addEventListener('click', startWhatsApp);
    btnStop.addEventListener('click', stopWhatsApp);
    btnRefreshQR.addEventListener('click', refreshQRCode);
    btnReset.addEventListener('click', resetConnection);
    btnRefresh.addEventListener('click', updateStatus);
    
    sendMessageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const phone = document.getElementById('phone').value.trim();
        const message = document.getElementById('message').value.trim();
        
        if (!phone || !message) {
            messageResult.innerHTML = '<div class="alert alert-error">❌ Veuillez remplir tous les champs</div>';
            return;
        }
        
        // Validation basique du numéro
        const phoneRegex = /^[0-9]{9,12}$/;
        if (!phoneRegex.test(phone)) {
            messageResult.innerHTML = '<div class="alert alert-error">❌ Format de numéro invalide. Utilisez 9 à 12 chiffres (ex: 612345678)</div>';
            return;
        }
        
        sendMessage(phone, message);
    });
    
    // Actualisation automatique du statut toutes les 5 secondes
    setInterval(updateStatus, 5000);
    
    // Initialisation
    addLog('Interface WhatsApp MassSender initialisée', 'success');
    updateStatus();
    </script>
</body>
</html>