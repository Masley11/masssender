<?php 
// public/index.php

// Configuration pour Render
if (getenv('RENDER')) {
    // Utiliser SQLite en mode fichier
    $dbPath = __DIR__ . '/data/contacts.db';
    
    // Créer le dossier data si nécessaire
    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0755, true);
    }
    
    // Initialiser la base de données
    initDatabase($dbPath);
}

function initDatabase($dbPath) {
    $db = new SQLite3($dbPath);
    
    // Créer les tables si elles n'existent pas
    $db->exec("
        CREATE TABLE IF NOT EXISTS contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT UNIQUE NOT NULL,
            consent BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
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
    
    $db->close();
}

include('includes/header.php'); 
?>

<main class="app-container">
    <div class="hero-section fade-in">
        <h1>Bienvenue sur MassSender by Braileys 🚀</h1>
        <p class="hero-subtitle">Envoyez vos messages en masse facilement, rapidement et légalement.</p>
    </div>

    <!-- Section Connexion WhatsApp -->
    <section class="whatsapp-section fade-in">
        <div class="whatsapp-content">
            <h2>🔗 Connexion WhatsApp Requise</h2>
            <p>Avant de pouvoir envoyer des messages, connectez votre compte WhatsApp pour commencer à diffuser vos campagnes.</p>
            <div class="connection-actions">
                <a href="/frontend/whatsapp/connexion.php" class="btn btn-success">
                    <span class="btn-icon">📱</span>
                    Connecter WhatsApp
                </a>
                <span class="status-badge status-disconnected">
                    <span class="spinner"></span>
                    Non connecté
                </span>
            </div>
        </div>
    </section>

    <!-- Tableau de bord des fonctionnalités -->
    <section class="dashboard-section">
        <h2 class="text-center">Fonctionnalités Principales</h2>
        <p class="text-center mb-3">Découvrez toutes les fonctionnalités puissantes de MassSender</p>
        
        <div class="dashboard">
            <div class="card feature-card">
                <div class="card-icon">📇</div>
                <h3>Gestion des Contacts</h3>
                <p>Importez, organisez et segmentez votre base de contacts pour des campagnes ciblées.</p>
                <a href="contacts.php" class="btn btn-primary mt-2">
                    Gérer les contacts
                </a>
            </div>
            
            <div class="card feature-card">
                <div class="card-icon">💬</div>
                <h3>Création de Messages</h3>
                <p>Créez et personnalisez vos campagnes de messages avec notre éditeur intuitif.</p>
                <a href="message.php" class="btn btn-primary mt-2">
                    Créer un message
                </a>
            </div>
            
            <div class="card feature-card">
                <div class="card-icon">⚙️</div>
                <h3>Paramètres</h3>
                <p>Configurez vos informations, mentions légales et préférences d'envoi.</p>
                <a href="parametres.php" class="btn btn-primary mt-2">
                    Configurer
                </a>
            </div>
            
            <div class="card feature-card">
                <div class="card-icon">📊</div>
                <h3>Analytiques</h3>
                <p>Suivez les performances de vos campagnes avec des statistiques détaillées.</p>
                <a href="analytiques.php" class="btn btn-primary mt-2">
                    Voir les stats
                </a>
            </div>
        </div>
    </section>

    <!-- Section Statistiques rapides -->
    <section class="stats-section fade-in">
        <div class="card">
            <h3 class="text-center">📈 Votre Activité</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Contacts</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Campagnes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Messages envoyés</div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include('includes/footer.php'); ?>

<script src="assets/js/script.js"></script>
</body>
</html>