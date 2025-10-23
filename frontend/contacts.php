<?php 
// contacts.php
include('includes/header.php'); 

// Connexion DB
$db = new SQLite3('contacts.db');
?>

<h1>📇 Gestion des Contacts</h1>

<!-- Cartes de statistiques -->
<div class="dashboard">
    <div class="card">
        <h3>👥 Contacts total</h3>
        <p style="font-size: 24px; margin: 0;"><?php echo getContactCount($db); ?></p>
    </div>
    <div class="card">
        <h3>✅ Consentements</h3>
        <p style="font-size: 24px; margin: 0;"><?php echo getConsentCount($db); ?></p>
    </div>
</div>

<!-- Formulaire d'ajout manuel -->
<div class="card">
    <h3>➕ Ajouter un contact manuellement</h3>
    <form method="POST" action="">
        <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
            <input type="text" name="name" placeholder="Nom" required style="padding: 10px; flex: 1; min-width: 200px;">
            <input type="text" name="phone" placeholder="+33612345678" required style="padding: 10px; flex: 1; min-width: 200px;">
            <button type="submit" name="add_contact" style="padding: 10px 20px; background: #2c3e50; color: white; border: none; border-radius: 5px;">
                Ajouter
            </button>
        </div>
    </form>
</div>

<!-- Import CSV -->
<div class="card">
    <h3>📁 Importer des contacts depuis CSV</h3>
    <form method="POST" action="" enctype="multipart/form-data">
        <div style="margin-bottom: 15px;">
            <input type="file" name="csv_file" accept=".csv" required style="padding: 10px; width: 100%;">
        </div>
        <div style="margin-bottom: 15px;">
            <small>Format CSV attendu : <code>Nom,Téléphone</code> (ex: "John Doe,+33612345678")</small>
        </div>
        <button type="submit" name="import_csv" style="padding: 10px 20px; background: #27ae60; color: white; border: none; border-radius: 5px;">
            📤 Importer CSV
        </button>
    </form>
</div>

<!-- Liste des contacts -->
<div class="card">
    <h3>📋 Liste des contacts</h3>
    
    <?php if (getContactCount($db) > 0): ?>
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="background: #f4f4f4;">
                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Nom</th>
                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Téléphone</th>
                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Consentement</th>
                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Date</th>
                <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php displayContacts($db); ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="text-align: center; color: #7f8c8d; padding: 20px;">Aucun contact pour le moment. Ajoutez-en manuellement ou importez un CSV.</p>
    <?php endif; ?>
</div>

<?php
// Traitement formulaire manuel
if (isset($_POST['add_contact'])) {
    addContact($db, $_POST['name'], $_POST['phone']);
}

// Traitement import CSV
if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    importCSV($db, $_FILES['csv_file']);
}

// Suppression contact
if (isset($_GET['delete'])) {
    deleteContact($db, $_GET['delete']);
}

// Fonctions
function getContactCount($db) {
    $result = $db->query("SELECT COUNT(*) as count FROM contacts");
    $row = $result->fetchArray();
    return $row['count'];
}

function getConsentCount($db) {
    $result = $db->query("SELECT COUNT(*) as count FROM contacts WHERE consent = 1");
    $row = $result->fetchArray();
    return $row['count'];
}

function displayContacts($db) {
    $result = $db->query("SELECT * FROM contacts ORDER BY created_at DESC");
    
    while ($contact = $result->fetchArray(SQLITE3_ASSOC)) {
        $date = date('d/m/Y', strtotime($contact['created_at']));
        echo "<tr>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$contact['name']}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$contact['phone']}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>" . 
             ($contact['consent'] ? '✅ Oui' : '❌ Non') . "</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>{$date}</td>";
        echo "<td style='padding: 12px; border-bottom: 1px solid #ddd;'>
                <a href='?delete={$contact['id']}' 
                   onclick='return confirm(\"Supprimer ce contact ?\")'
                   style='color: #e74c3c; text-decoration: none;'>🗑️ Supprimer</a>
              </td>";
        echo "</tr>";
    }
}

function addContact($db, $name, $phone) {
    // Validation
    if (!preg_match('/^\+[1-9]\d{1,14}$/', $phone)) {
        echo "<script>alert('Format téléphone invalide. Ex: +33612345678')</script>";
        return;
    }
    
    $stmt = $db->prepare("INSERT INTO contacts (name, phone) VALUES (:name, :phone)");
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        echo "<script>alert('Contact ajouté !')</script>";
    } else {
        echo "<script>alert('Erreur ou numéro déjà existant')</script>";
    }
}
function importCSV($db, $csvFile) {
    if ($csvFile['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Erreur lors du téléchargement du fichier')</script>";
        return;
    }
    
    // Vérifier l'extension
    $fileType = pathinfo($csvFile['name'], PATHINFO_EXTENSION);
    if (strtolower($fileType) !== 'csv') {
        echo "<script>alert('Seuls les fichiers CSV sont autorisés')</script>";
        return;
    }
    
    $file = fopen($csvFile['tmp_name'], 'r');
    $imported = 0;
    $errors = 0;
    $line = 0;
    
    // Préparer la requête d'insertion
    $stmt = $db->prepare("INSERT OR IGNORE INTO contacts (name, phone) VALUES (:name, :phone)");
    
    // CORRECTION : Ajouter le paramètre $escape à fgetcsv()
    while (($data = fgetcsv($file, 1000, ",", '"', '\\')) !== FALSE) {
        $line++;
        
        // Ignorer la première ligne si c'est un en-tête
        if ($line === 1 && (strtolower($data[0]) === 'nom' || strtolower($data[0]) === 'name')) {
            continue;
        }
        
        // Vérifier qu'on a au moins 2 colonnes
        if (count($data) < 2) {
            $errors++;
            continue;
        }
        
        $name = trim($data[0]);
        $phone = trim($data[1]);
        
        // Validation du téléphone
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $phone)) {
            $errors++;
            continue;
        }
        
        // Insertion
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $imported++;
        } else {
            $errors++;
        }
        
        // Réinitialiser la requête pour la prochaine ligne
        $stmt->reset();
    }
    
    fclose($file);
    
    // Message de résultat
    $message = "Import terminé : {$imported} contacts importés";
    if ($errors > 0) {
        $message .= ", {$errors} erreurs";
    }
    echo "<script>alert('{$message}')</script>";
}

function deleteContact($db, $id) {
    $stmt = $db->prepare("DELETE FROM contacts WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    
    header("Location: contacts.php");
    exit;
}

include('includes/footer.php'); 
?>