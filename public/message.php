<?php
// message.php
include('includes/header.php');

// Connexion DB - Même connexion que contacts.php
if (getenv('RENDER')) {
    // SQLite pour Render
    $dbPath = __DIR__ . '/data/contacts.db';
    // Assurer que le dossier data existe
    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0755, true);
    }
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} else {
    // MySQL pour l'environnement local
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'masssender';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}

// Créer les tables si elles n'existent pas
initializeDatabase($pdo);

function initializeDatabase($pdo) {
    // Table contacts (identique à contacts.php)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT UNIQUE NOT NULL,
            consent INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Table campaigns
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS campaigns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            message TEXT NOT NULL,
            total_contacts INTEGER,
            sent_count INTEGER DEFAULT 0,
            status TEXT DEFAULT 'draft',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME
        )
    ");
    
    // Table message_logs
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS message_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            campaign_id INTEGER,
            contact_id INTEGER,
            phone TEXT,
            status TEXT,
            error_message TEXT,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
            FOREIGN KEY (contact_id) REFERENCES contacts(id)
        )
    ");
    
    // Vérifier s'il y a des contacts, sinon insérer des exemples
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (($row['count'] ?? 0) == 0) {
        $sampleContacts = [
            ['Jean Dupont', '+33123456789', 1],
            ['Marie Martin', '+33987654321', 1],
            ['Pierre Durand', '+33555123456', 0],
            ['Sophie Lambert', '+33222333444', 1]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO contacts (name, phone, consent) VALUES (?, ?, ?)");
        foreach ($sampleContacts as $contact) {
            $stmt->execute([$contact[0], $contact[1], $contact[2]]);
        }
    }
}

// Fonctions adaptées pour PDO
function getActiveContactsCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE consent = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['count'] ?? 0;
}

function getTotalContactsCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['count'] ?? 0;
}

function sendBulkMessages($pdo, $message) {
    // Récupérer les contacts avec consentement
    $stmt = $pdo->query("SELECT * FROM contacts WHERE consent = 1 ORDER BY name");
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalContacts = count($contacts);
    
    if ($totalContacts === 0) {
        echo "<div class='alert alert-error'>❌ Aucun contact avec consentement actif !</div>";
        return;
    }
    
    // Enregistrer la campagne
    $campaignId = saveCampaign($pdo, $message, $totalContacts);
    
    if (!$campaignId) {
        echo "<div class='alert alert-error'>❌ Erreur lors de la création de la campagne</div>";
        return;
    }
    
    // Afficher la progression
    echo "
    <div id='sendingProgress' class='card' style='display: block;'>
        <h3>⏳ Envoi en cours...</h3>
        <div style='background: #f0f0f0; border-radius: 10px; padding: 10px; margin: 10px 0;'>
            <div id='progressBar' style='height: 20px; background: #3498db; border-radius: 10px; width: 0%; transition: width 0.5s;'></div>
        </div>
        <p id='progressText'>Préparation de l'envoi...</p>
        <div id='progressDetails' style='margin-top: 10px; font-size: 14px; color: #666;'></div>
    </div>
    ";
    
    // Forcer l'affichage immédiat
    ob_flush();
    flush();
    
    // Lancer l'envoi
    processWhatsAppSending($pdo, $contacts, $message, $campaignId);
}

function saveCampaign($pdo, $message, $totalContacts) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO campaigns (message, total_contacts, sent_count, status, created_at) 
            VALUES (:message, :total, 0, 'sending', datetime('now'))
        ");
        $stmt->bindValue(':message', $message, PDO::PARAM_STR);
        $stmt->bindValue(':total', $totalContacts, PDO::PARAM_INT);
        $stmt->execute();
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Erreur sauvegarde campagne: " . $e->getMessage());
        return false;
    }
}

function processWhatsAppSending($pdo, $contacts, $message, $campaignId) {
    $sentCount = 0;
    $totalContacts = count($contacts);
    $successCount = 0;
    $errorCount = 0;

    foreach ($contacts as $contact) {
        $sentCount++;
        
        // SIMULATION d'envoi WhatsApp (à remplacer par l'appel réel à l'API)
        $sendResult = simulateWhatsAppSend($contact['phone'], $message);
        
        // Enregistrer le résultat
        $logStmt = $pdo->prepare("
            INSERT INTO message_logs (campaign_id, contact_id, phone, status, error_message, sent_at) 
            VALUES (:campaign_id, :contact_id, :phone, :status, :error_message, datetime('now'))
        ");
        
        if ($sendResult['success']) {
            $logStmt->bindValue(':status', 'sent', PDO::PARAM_STR);
            $logStmt->bindValue(':error_message', null, PDO::PARAM_NULL);
            $successCount++;
        } else {
            $logStmt->bindValue(':status', 'failed', PDO::PARAM_STR);
            $logStmt->bindValue(':error_message', $sendResult['error'], PDO::PARAM_STR);
            $errorCount++;
        }
        
        $logStmt->bindValue(':campaign_id', $campaignId, PDO::PARAM_INT);
        $logStmt->bindValue(':contact_id', $contact['id'], PDO::PARAM_INT);
        $logStmt->bindValue(':phone', $contact['phone'], PDO::PARAM_STR);
        $logStmt->execute();
        
        // Mettre à jour le compteur de la campagne
        $updateStmt = $pdo->prepare("UPDATE campaigns SET sent_count = :sent WHERE id = :id");
        $updateStmt->bindValue(':sent', $sentCount, PDO::PARAM_INT);
        $updateStmt->bindValue(':id', $campaignId, PDO::PARAM_INT);
        $updateStmt->execute();
        
        // Mettre à jour l'affichage en temps réel
        $progress = round(($sentCount / $totalContacts) * 100);
        $contactName = htmlspecialchars($contact['name']);
        $phone = $contact['phone'];
        
        echo "<script>
            document.getElementById('progressBar').style.width = '{$progress}%';
            document.getElementById('progressText').innerHTML = 'Envoi {$sentCount}/{$totalContacts} ({$progress}%)';
            document.getElementById('progressDetails').innerHTML = 'Dernier: {$contactName} ({$phone})<br>Succès: {$successCount} | Erreurs: {$errorCount}';
        </script>";
        
        ob_flush();
        flush();
        
        // Délai de 2 secondes entre les messages pour éviter le spam
        sleep(2);
    }
    
    // Finaliser la campagne
    $finalStatus = $errorCount > 0 ? ($successCount > 0 ? 'partial' : 'failed') : 'completed';
    $finalizeStmt = $pdo->prepare("UPDATE campaigns SET status = :status, completed_at = datetime('now') WHERE id = :id");
    $finalizeStmt->bindValue(':status', $finalStatus, PDO::PARAM_STR);
    $finalizeStmt->bindValue(':id', $campaignId, PDO::PARAM_INT);
    $finalizeStmt->execute();
    
    // Message final
    $finalMessage = "";
    if ($finalStatus === 'completed') {
        $finalMessage = "✅ Envoi terminé avec succès ! {$successCount} messages envoyés.";
    } elseif ($finalStatus === 'partial') {
        $finalMessage = "⚠️ Envoi partiellement réussi : {$successCount} messages envoyés, {$errorCount} erreurs.";
    } else {
        $finalMessage = "❌ Échec de l'envoi : {$errorCount} erreurs.";
    }
    
    echo "<script>
        document.getElementById('progressText').innerHTML = '{$finalMessage}';
        document.getElementById('progressDetails').innerHTML += '<br><br><strong>Envoi terminé !</strong>';
        setTimeout(() => { location.reload(); }, 3000);
    </script>";
}

function simulateWhatsAppSend($phone, $message) {
    // SIMULATION - À REMPLACER PAR L'APPEL RÉEL À VOTRE API WHATSAPP
    
    // Simulation d'aléatoire pour les tests
    $random = rand(1, 10);
    
    if ($random <= 8) { // 80% de succès
        return [
            'success' => true,
            'message_id' => 'simulated_' . uniqid()
        ];
    } else { // 20% d'échec
        $errors = [
            'Numéro invalide',
            'Service WhatsApp indisponible',
            'Timeout de connexion',
            'Erreur d authentification'
        ];
        return [
            'success' => false,
            'error' => $errors[array_rand($errors)]
        ];
    }
}

function displayMessageHistory($pdo) {
    $stmt = $pdo->query("
        SELECT c.*, 
               (SELECT COUNT(*) FROM message_logs WHERE campaign_id = c.id AND status = 'sent') as success_count,
               (SELECT COUNT(*) FROM message_logs WHERE campaign_id = c.id AND status = 'failed') as failed_count
        FROM campaigns c 
        ORDER BY c.created_at DESC 
        LIMIT 10
    ");
    
    $hasHistory = false;
    
    while ($campaign = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!$hasHistory) {
            echo '<div class="table-responsive">';
            echo '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">';
            echo '<thead><tr style="background: #f4f4f4;">';
            echo '<th style="padding: 12px; text-align: left;">Date</th>';
            echo '<th style="padding: 12px; text-align: left;">Message</th>';
            echo '<th style="padding: 12px; text-align: left;">Statut</th>';
            echo '<th style="padding: 12px; text-align: left;">Résultats</th>';
            echo '<th style="padding: 12px; text-align: left;">Détails</th>';
            echo '</tr></thead><tbody>';
            $hasHistory = true;
        }
        
        $date = date('d/m/Y H:i', strtotime($campaign['created_at']));
        $messagePreview = strlen($campaign['message']) > 50 
            ? substr($campaign['message'], 0, 50) . '...' 
            : $campaign['message'];
        
        // Couleur selon le statut
        $statusConfig = [
            'completed' => ['color' => '#27ae60', 'icon' => '✅'],
            'partial' => ['color' => '#f39c12', 'icon' => '⚠️'],
            'failed' => ['color' => '#e74c3c', 'icon' => '❌'],
            'sending' => ['color' => '#3498db', 'icon' => '⏳'],
            'draft' => ['color' => '#95a5a6', 'icon' => '📝']
        ];
        
        $statusInfo = $statusConfig[$campaign['status']] ?? $statusConfig['draft'];
        
        echo "<tr>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$date}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;' title='" . htmlspecialchars($campaign['message']) . "'>{$messagePreview}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd; color: {$statusInfo['color']};'>{$statusInfo['icon']} " . ucfirst($campaign['status']) . "</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$campaign['sent_count']}/{$campaign['total_contacts']}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>";
        if ($campaign['success_count'] > 0) echo "✅ {$campaign['success_count']} ";
        if ($campaign['failed_count'] > 0) echo "❌ {$campaign['failed_count']}";
        echo "</td>";
        echo "</tr>";
    }
    
    if ($hasHistory) {
        echo '</tbody></table></div>';
    } else {
        echo '<div class="alert alert-info">📝 Aucun historique d\'envoi pour le moment.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envoyer des Messages - MassSender</title>
    <style>
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }
        
        .card h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #17a2b8;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .btn {
            padding: 12px 24px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💬 Envoyer des Messages</h1>
        
        <!-- Statistiques -->
        <div class="card">
            <h3>📊 Vue d'ensemble</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo getTotalContactsCount($pdo); ?></div>
                    <div class="stat-label">Contacts total</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo getActiveContactsCount($pdo); ?></div>
                    <div class="stat-label">Contacts actifs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php 
                        $stmt = $pdo->query("SELECT COUNT(DISTINCT campaign_id) as count FROM campaigns WHERE status = 'completed'");
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo $row['count'] ?? 0;
                    ?></div>
                    <div class="stat-label">Campagnes terminées</div>
                </div>
            </div>
        </div>

        <!-- Formulaire de message -->
        <div class="card">
            <h3>✍️ Composer le message</h3>
            <form method="POST" action="" id="messageForm">
                <div class="form-group">
                    <label for="message"><strong>Message :</strong></label>
                    <textarea name="message" id="message" rows="6" 
                              placeholder="Tapez votre message promotionnel ici...
Exemple : Bonjour [Nom], découvrez notre nouvelle offre exclusive ! 
Répondez STOP pour vous désabonner." 
                              required></textarea>
                </div>
                
                <div class="form-group">
                    <small>💡 <strong>Conseils :</strong></small><br>
                    <small>• Personalisez avec [Nom] pour utiliser le nom du contact</small><br>
                    <small>• Incluez toujours la mention &quot;Répondez STOP pour vous désabonner&quot;</small><br>
                    <small>• Délai entre les messages : 2 secondes</small>
                </div>
                
                <button type="submit" name="send_messages" class="btn">
                    🚀 Lancer l'envoi massif
                </button>
                
                <?php if (getActiveContactsCount($pdo) === 0): ?>
                <div class="alert alert-warning">
                    ⚠️ Aucun contact actif. <a href="contacts.php" style="color: #856404; font-weight: bold;">Ajoutez des contacts avec consentement</a> avant de pouvoir envoyer des messages.
                </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Historique des envois -->
        <div class="card">
            <h3>📋 Historique des campagnes</h3>
            <?php displayMessageHistory($pdo); ?>
        </div>
    </div>
<script>
    // Empêcher l'envoi multiple du formulaire
    document.getElementById('messageForm')?.addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '⏳ Préparation...';
        
        // Afficher un message d'attente
        const waitingMsg = document.createElement('div');
        waitingMsg.className = 'alert alert-info';
        waitingMsg.innerHTML = '⏳ Initialisation de l\'envoi massif...';
        this.appendChild(waitingMsg);
    });
    
    // Compteur de caractères
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        const charCount = document.createElement('div');
        charCount.style.fontSize = '12px';
        charCount.style.color = '#666';
        charCount.style.textAlign = 'right';
        charCount.style.marginTop = '5px';
        messageTextarea.parentNode.appendChild(charCount);
        
        messageTextarea.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length + ' caractères';
            
            if (length > 1000) {
                charCount.style.color = '#e74c3c';
            } else if (length > 500) {
                charCount.style.color = '#f39c12';
            } else {
                charCount.style.color = '#666';
            }
        });
        
        // Déclencher une première fois pour l'état initial
        messageTextarea.dispatchEvent(new Event('input'));
    }
    </script>
</body>
</html>

<?php
// Traitement de l'envoi
if (isset($_POST['send_messages'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        if (getActiveContactsCount($pdo) > 0) {
            sendBulkMessages($pdo, $message);
        } else {
            echo "<div class='alert alert-error'>❌ Aucun contact avec consentement actif !</div>";
        }
    } else {
        echo "<div class='alert alert-error'>❌ Le message ne peut pas être vide</div>";
    }
}

include('includes/footer.php');
?>
    