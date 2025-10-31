<?php 
// contacts.php
include('includes/header.php'); 

// Connexion DB - Version compatible Render
if (getenv('RENDER')) {
    // SQLite pour Render
    $dbPath = __DIR__ . '/data/contacts.db';
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
?>

<h1>📇 Gestion des Contacts</h1>

<!-- Cartes de statistiques -->
<div class="dashboard">
    <div class="card">
        <h3>👥 Contacts total</h3>
        <p style="font-size: 24px; margin: 0;"><?php echo getContactCount($pdo); ?></p>
    </div>
    <div class="card">
        <h3>✅ Consentements</h3>
        <p style="font-size: 24px; margin: 0;"><?php echo getConsentCount($pdo); ?></p>
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
    
    <?php if (getContactCount($pdo) > 0): ?>
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
            <?php displayContacts($pdo); ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="text-align: center; color: #7f8c8d; padding: 20px;">Aucun contact pour le moment. Ajoutez-en manuellement ou importez un CSV.</p>
    <?php endif; ?>
</div>

<?php
// Traitement formulaire manuel
if (isset($_POST['add_contact'])) {
    addContact($pdo, $_POST['name'], $_POST['phone']);
}

// Traitement import CSV
if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    importCSV($pdo, $_FILES['csv_file']);
}

// Suppression contact
if (isset($_GET['delete'])) {
    deleteContact($pdo, $_GET['delete']);
}

// Fonctions PDO compatibles
function getContactCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['count'] ?? 0;
}

function getConsentCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM contacts WHERE consent = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['count'] ?? 0;
}

function displayContacts($pdo) {
    $stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC");
    
    while ($contact = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            echo "<script>alert('Numéro déjà existant')</script>";
        } else {
            echo "<script>alert('Erreur lors de l\\'ajout')</script>";
        }
    }
}

function importCSV($pdo, $csvFile) {
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
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO contacts (name, phone) VALUES (:name, :phone)");
    
    while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
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
        
        try {
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':phone', $phone);
            
            if ($stmt->execute()) {
                $imported++;
            } else {
                $errors++;
            }
        } catch (PDOException $e) {
            $errors++;
        }
    }
    
    fclose($file);
    
    // Message de résultat
    $message = "Import terminé : {$imported} contacts importés";
    if ($errors > 0) {
        $message .= ", {$errors} erreurs";
    }
    echo "<script>alert('{$message}')</script>";
}

function deleteContact($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        
        echo "<script>alert('Contact supprimé !'); window.location.href = 'contacts.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Erreur suppression contact')</script>";
    }
}

include('includes/footer.php'); 
?>