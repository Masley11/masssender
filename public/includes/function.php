<?php
// includes/functions.php

// Fonctions pour Supabase (PostgreSQL)
function getContactCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts");
    return $stmt->fetch()['count'];
}

function getConsentCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE consent = true");
    return $stmt->fetch()['count'];
}

function getActiveContactsCount($pdo) {
    return getConsentCount($pdo);
}

function displayContacts($pdo) {
    $stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC");
    
    while ($contact = $stmt->fetch()) {
        $date = date('d/m/Y', strtotime($contact['created_at']));
        $consentText = $contact['consent'] ? '✅ Oui' : '❌ Non';
        
        echo "<tr>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$contact['name']}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$contact['phone']}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$consentText}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$date}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>
                <a href='?delete={$contact['id']}' 
                   onclick='return confirm(\"Supprimer ce contact ?\")'
                   style='color: #e74c3c; text-decoration: none;'>🗑️ Supprimer</a>
              </td>";
        echo "</tr>";
    }
}

function addContact($pdo, $name, $phone) {
    // Validation
    if (!preg_match('/^\+[1-9]\d{1,14}$/', $phone)) {
        echo "<script>alert('Format téléphone invalide. Ex: +33612345678')</script>";
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO contacts (name, phone) VALUES (:name, :phone)");
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':phone', $phone);
        
        if ($stmt->execute()) {
            echo "<script>alert('Contact ajouté !')</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('Erreur ou numéro déjà existant')</script>";
    }
}

function saveCampaign($pdo, $message, $totalContacts) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO campaigns (message, total_contacts, sent_count, status, created_at) 
            VALUES (:message, :total_contacts, 0, 'sending', NOW())
        ");
        $stmt->bindValue(':message', $message);
        $stmt->bindValue(':total_contacts', $totalContacts);
        $stmt->execute();
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Erreur sauvegarde campagne: " . $e->getMessage());
        return false;
    }
}
?>