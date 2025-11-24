<?php
// message.php
include('includes/header.php');

// Connexion DB
$db = new SQLite3('contacts.db');

// Créer les tables si elles n'existent pas AVANT toute utilisation
initializeDatabase($db);

function initializeDatabase($db) {
    // Table contacts
    $db->exec("
        CREATE TABLE IF NOT EXISTS contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT UNIQUE NOT NULL,
            consent INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Table campaigns
    $db->exec("
        CREATE TABLE IF NOT EXISTS campaigns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            message TEXT NOT NULL,
            total_contacts INTEGER,
            sent_count INTEGER,
            status TEXT,
            created_at DATETIME,
            completed_at DATETIME
        )
    ");
    
    // Table message_logs
    $db->exec("
        CREATE TABLE IF NOT EXISTS message_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            campaign_id INTEGER,
            contact_id INTEGER,
            phone TEXT,
            status TEXT,
            sent_at DATETIME
        )
    ");
    
    // Insérer des données d'exemple si la table contacts est vide
    $result = $db->query("SELECT COUNT(*) as count FROM contacts");
    if ($result) {
        $row = $result->fetchArray();
        if ($row['count'] == 0) {
            $sampleContacts = [
                ['Jean Dupont', '+33123456789', 1],
                ['Marie Martin', '+33987654321', 1],
                ['Pierre Durand', '+33555123456', 0],
                ['Sophie Lambert', '+33222333444', 1]
            ];
            
            $stmt = $db->prepare("INSERT INTO contacts (name, phone, consent) VALUES (?, ?, ?)");
            foreach ($sampleContacts as $contact) {
                $stmt->bindValue(1, $contact[0], SQLITE3_TEXT);
                $stmt->bindValue(2, $contact[1], SQLITE3_TEXT);
                $stmt->bindValue(3, $contact[2], SQLITE3_INTEGER);
                $stmt->execute();
            }
        }
    }
}

// Fonctions
function getActiveContactsCount($db) {
    $result = $db->query("SELECT COUNT(*) as count FROM contacts WHERE consent = 1");
    if ($result) {
        $row = $result->fetchArray();
        return $row['count'];
    }
    return 0;
}

function sendBulkMessages($db, $message) {
    // Récupérer les contacts avec consentement
    $result = $db->query("SELECT * FROM contacts WHERE consent = 1 ORDER BY name");
    $contacts = [];
    
    while ($contact = $result->fetchArray(SQLITE3_ASSOC)) {
        $contacts[] = $contact;
    }
    
    $totalContacts = count($contacts);
    
    if ($totalContacts === 0) {
        echo "<script>alert('Aucun contact avec consentement actif !')</script>";
        return;
    }
    
    // Enregistrer la campagne
    $campaignId = saveCampaign($db, $message, $totalContacts);
    
    echo "<script>
        document.getElementById('sendingProgress').style.display = 'block';
        simulateSending({$totalContacts}, {$campaignId});
    </script>";
    
    // Forcer l'affichage immédiat
    ob_flush();
    flush();
    
    // SIMULATION - Dans la vraie version, on enverrait vraiment via WhatsApp
    simulateWhatsAppSending($db, $contacts, $message, $campaignId);
}

function saveCampaign($db, $message, $totalContacts) {
    $stmt = $db->prepare("
        INSERT INTO campaigns (message, total_contacts, sent_count, status, created_at) 
        VALUES (:message, :total, 0, 'sending', datetime('now'))
    ");
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    $stmt->bindValue(':total', $totalContacts, SQLITE3_INTEGER);
    $stmt->execute();
    
    return $db->lastInsertRowID();
}

function simulateWhatsAppSending($db, $contacts, $message, $campaignId) {
    $sentCount = 0;
    $totalContacts = count($contacts);
    
    foreach ($contacts as $contact) {
        $sentCount++;
        
        // SIMULATION d'envoi WhatsApp
        $status = 'sent'; // Dans la vraie version, ça dépendrait de la réponse WhatsApp
        
        // Enregistrer l'envoi
        $stmt = $db->prepare("
            INSERT INTO message_logs (campaign_id, contact_id, phone, status, sent_at) 
            VALUES (:campaign_id, :contact_id, :phone, :status, datetime('now'))
        ");
        $stmt->bindValue(':campaign_id', $campaignId, SQLITE3_INTEGER);
        $stmt->bindValue(':contact_id', $contact['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':phone', $contact['phone'], SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->execute();
        
        // Mettre à jour le compteur de la campagne
        $db->exec("UPDATE campaigns SET sent_count = {$sentCount} WHERE id = {$campaignId}");
        
        // Délai de 10 secondes entre les messages
        sleep(2); // Réduit à 2s pour les tests
        
        // Mettre à jour la progression en temps réel
        $progress = round(($sentCount / $totalContacts) * 100);
        echo "<script>
            document.getElementById('progressBar').style.width = '{$progress}%';
            document.getElementById('progressText').innerHTML = 'Envoi {$sentCount}/{$totalContacts} ({$progress}%) - Dernier: {$contact['name']}';
        </script>";
        
        ob_flush();
        flush();
    }
    
    // Finaliser la campagne
    $db->exec("UPDATE campaigns SET status = 'completed', completed_at = datetime('now') WHERE id = {$campaignId}");
    
    echo "<script>
        document.getElementById('progressText').innerHTML = '✅ Envoi terminé ! {$sentCount} messages envoyés.';
        setTimeout(() => { location.reload(); }, 2000);
    </script>";
}

function displayMessageHistory($db) {
    $result = $db->query("
        SELECT * FROM campaigns 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    
    $hasHistory = false;
    
    while ($campaign = $result->fetchArray(SQLITE3_ASSOC)) {
        if (!$hasHistory) {
            echo '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">';
            echo '<thead><tr style="background: #f4f4f4;">';
            echo '<th style="padding: 12px; text-align: left;">Date</th>';
            echo '<th style="padding: 12px; text-align: left;">Message</th>';
            echo '<th style="padding: 12px; text-align: left;">Statut</th>';
            echo '<th style="padding: 12px; text-align: left;">Résultats</th>';
            echo '</tr></thead><tbody>';
            $hasHistory = true;
        }
        
        $date = date('d/m/Y H:i', strtotime($campaign['created_at']));
        $messagePreview = strlen($campaign['message']) > 50 
            ? substr($campaign['message'], 0, 50) . '...' 
            : $campaign['message'];
        
        $statusColor = $campaign['status'] === 'completed' ? '#27ae60' : '#f39c12';
        
        echo "<tr>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$date}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;' title='{$campaign['message']}'>{$messagePreview}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd; color: {$statusColor};'>{$campaign['status']}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$campaign['sent_count']}/{$campaign['total_contacts']}</td>";
        echo "</tr>";
    }
    
    if ($hasHistory) {
        echo '</tbody></table>';
    } else {
        echo '<p style="text-align: center; color: #7f8c8d; padding: 20px;">Aucun historique d\'envoi pour le moment.</p>';
    }
}
?>

<h1>💬 Envoyer des Messages</h1>

<div class="dashboard">
    <div class="card">
        <h3>📊 Statistiques</h3>
        <p>Contacts actifs: <strong><?php echo getActiveContactsCount($db); ?></strong></p>
        <p>Délai entre messages: <strong>10 secondes</strong></p>
    </div>
</div>

<!-- Formulaire de message -->
<div class="card">
    <h3>✍️ Composer le message</h3>
    <form method="POST" action="" id="messageForm">
        <div style="margin-bottom: 15px;">
            <label for="message"><strong>Message :</strong></label>
            <textarea name="message" id="message" rows="6" 
                      placeholder="Tapez votre message promotionnel ici..." 
                      required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 8px;"></textarea>
        </div>
        
        <div style="margin-bottom: 15px;">
            <small>💡 Conseil : N'oubliez pas d'inclure la mention "Répondez STOP pour vous désabonner"</small>
        </div>
        
        <button type="submit" name="send_messages" 
                style="padding: 12px 30px; background: #27ae60; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
            🚀 Envoyer aux contacts
        </button>
    </form>
</div>

<!-- Résultats et historique -->
<div class="card">
    <h3>📋 Historique des envois</h3>
    <div id="sendingProgress" style="display: none;">
        <h4>⏳ Envoi en cours...</h4>
        <div style="background: #f0f0f0; border-radius: 10px; padding: 10px; margin: 10px 0;">
            <div id="progressBar" style="height: 20px; background: #3498db; border-radius: 10px; width: 0%; transition: width 0.5s;"></div>
        </div>
        <p id="progressText">Préparation de l'envoi...</p>
    </div>
    
    <?php displayMessageHistory($db); ?>
</div>

<?php
// Traitement de l'envoi
if (isset($_POST['send_messages'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        sendBulkMessages($db, $message);
    }
}

include('includes/footer.php');
?>

<!-- JavaScript pour la simulation en temps réel -->
<script>
function simulateSending(totalContacts, campaignId) {
    let sent = 0;
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    // Cette fonction serait remplacée par de vrais appels API WhatsApp
    function sendNext() {
        if (sent < totalContacts) {
            sent++;
            const progress = Math.round((sent / totalContacts) * 100);
            
            progressBar.style.width = progress + '%';
            progressText.innerHTML = `Envoi ${sent}/${totalContacts} (${progress}%) - Simulation en cours...`;
            
            // Simuler un délai de 2 secondes pour les tests
            setTimeout(sendNext, 2000);
        } else {
            progressText.innerHTML = '✅ Envoi simulé terminé !';
            setTimeout(() => { location.reload(); }, 2000);
        }
    }
    
    sendNext();
}
</script>