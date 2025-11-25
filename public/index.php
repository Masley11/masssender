<?php 
// public/index.php

// Configuration pour Render
if (getenv('RENDER')) {
    $dbPath = __DIR__ . '/data/contacts.db';
    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0755, true);
    }
    initDatabase($dbPath);
}

function initDatabase($dbPath) {
    $db = new SQLite3($dbPath);
    
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
    <!-- Hero Section -->
    <section class="hero-section">
        <h1>MassSender</h1>
        <p class="hero-subtitle">Solution professionnelle d'envoi de messages WhatsApp</p>
    </section>

    <!-- Connexion WhatsApp -->
    <section class="connection-section">
        <div class="card">
            <h3>Connexion WhatsApp</h3>
            <p>Connectez votre compte WhatsApp pour commencer à envoyer des messages</p>
            <div class="connection-actions">
                <a href="/frontend/whatsapp/connexion.php" class="btn btn-primary">
                    Connecter WhatsApp
                </a>
                <div class="status-indicator disconnected">
                    <div class="status-dot"></div>
                    Non connecté
                </div>
            </div>
        </div>
    </section>

    <!-- Fonctionnalités -->
    <section class="features-section">
        <h2>Fonctionnalités</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h4>Contacts</h4>
                <p>Gérez votre base de contacts</p>
                <a href="contacts.php" class="btn btn-outline">Accéder</a>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h4>Messages</h4>
                <p>Créez vos campagnes</p>
                <a href="message.php" class="btn btn-outline">Accéder</a>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">⚙️</div>
                <h4>Paramètres</h4>
                <p>Configurez l'application</p>
                <a href="parametres.php" class="btn btn-outline">Accéder</a>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h4>Statistiques</h4>
                <p>Suivez vos performances</p>
                <a href="analytiques.php" class="btn btn-outline">Accéder</a>
            </div>
        </div>
    </section>

    <!-- Statistiques -->
    <section class="stats-section">
        <div class="card">
            <h3>Votre activité</h3>
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

<style>
.app-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Hero Section */
.hero-section {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 0;
}

.hero-section h1 {
    font-size: 2.5rem;
    color: #333;
    margin-bottom: 10px;
}

.hero-subtitle {
    font-size: 1.2rem;
    color: #666;
    margin: 0;
}

/* Connection Section */
.connection-section {
    margin-bottom: 40px;
}

.connection-section .card {
    text-align: center;
    padding: 30px;
}

.connection-actions {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-indicator.disconnected {
    background: #f8f9fa;
    color: #666;
}

.status-indicator.disconnected .status-dot {
    background: #dc3545;
}

/* Features Section */
.features-section {
    margin-bottom: 40px;
}

.features-section h2 {
    text-align: center;
    margin-bottom: 30px;
    color: #333;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.feature-card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.feature-icon {
    font-size: 2rem;
    margin-bottom: 16px;
}

.feature-card h4 {
    margin: 0 0 8px;
    color: #333;
}

.feature-card p {
    color: #666;
    margin: 0 0 16px;
    font-size: 14px;
}

/* Stats Section */
.stats-section {
    margin-bottom: 40px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-item {
    text-align: center;
    padding: 20px;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #25D366;
    margin-bottom: 8px;
}

.stat-label {
    color: #666;
    font-size: 14px;
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: #25D366;
    color: white;
}

.btn-primary:hover {
    background: #128C7E;
}

.btn-outline {
    background: white;
    color: #333;
    border: 1px solid #ddd;
}

.btn-outline:hover {
    background: #f5f5f5;
}

/* Card */
.card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.card h3 {
    margin: 0 0 16px;
    color: #333;
}

/* Responsive */
@media (max-width: 768px) {
    .app-container {
        padding: 16px;
    }
    
    .hero-section {
        padding: 20px 0;
    }
    
    .hero-section h1 {
        font-size: 2rem;
    }
    
    .connection-actions {
        flex-direction: column;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<script>
// Vérification du statut WhatsApp
async function checkWhatsAppStatus() {
    try {
        const response = await fetch('/frontend/whatsapp/connexion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_status'
        });
        
        const data = await response.json();
        updateConnectionStatus(data.connected);
    } catch (error) {
        console.log('Erreur de connexion au service WhatsApp');
    }
}

function updateConnectionStatus(connected) {
    const statusElement = document.querySelector('.status-indicator');
    const statusDot = document.querySelector('.status-dot');
    
    if (connected) {
        statusElement.className = 'status-indicator connected';
        statusElement.innerHTML = `
            <div class="status-dot" style="background: #25D366"></div>
            Connecté
        `;
    } else {
        statusElement.className = 'status-indicator disconnected';
        statusElement.innerHTML = `
            <div class="status-dot" style="background: #dc3545"></div>
            Non connecté
        `;
    }
}

// Charger les statistiques
async function loadStats() {
    // Simulation de chargement des stats
    // À remplacer par un appel API réel
    setTimeout(() => {
        document.querySelectorAll('.stat-number').forEach(el => {
            el.textContent = '0';
        });
    }, 500);
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    checkWhatsAppStatus();
    loadStats();
    
    // Vérifier le statut toutes les 30 secondes
    setInterval(checkWhatsAppStatus, 30000);
});
</script>

<?php include('includes/footer.php'); ?>