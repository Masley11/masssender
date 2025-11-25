<?php include('includes/header.php'); ?>

<main class="app-container">
    <div class="page-header">
        <h1>Paramètres</h1>
        <p>Configurez votre compte et vos préférences</p>
    </div>

    <div class="settings-container">
        <!-- Informations Compte -->
        <section class="settings-section">
            <div class="card">
                <h3>Informations du Compte</h3>
                <form class="settings-form" id="accountForm">
                    <div class="form-group">
                        <label>Nom de l'entreprise</label>
                        <input type="text" name="company_name" placeholder="Votre entreprise">
                    </div>
                    <div class="form-group">
                        <label>Email de contact</label>
                        <input type="email" name="email" placeholder="contact@entreprise.com">
                    </div>
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="tel" name="phone" placeholder="+33 1 23 45 67 89">
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </form>
            </div>
        </section>

        <!-- Mentions Légales -->
        <section class="settings-section">
            <div class="card">
                <h3>Mentions Légales</h3>
                <form class="settings-form" id="legalForm">
                    <div class="form-group">
                        <label>Mention de désinscription</label>
                        <textarea name="legal_mention" rows="2" placeholder="Ex: Pour vous désinscrire, répondez STOP"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Informations légales</label>
                        <textarea name="company_info" rows="2" placeholder="Société - SIRET - Adresse"></textarea>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="auto_legal" checked>
                        <label>Ajouter automatiquement les mentions légales</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Sauvegarder</button>
                </form>
            </div>
        </section>

        <!-- Paramètres d'Envoi -->
        <section class="settings-section">
            <div class="card">
                <h3>Paramètres d'Envoi</h3>
                <form class="settings-form" id="sendingForm">
                    <div class="form-group">
                        <label>Délai entre messages (secondes)</label>
                        <input type="number" name="delay" min="5" max="60" value="10">
                        <small>Recommandé: 10-30 secondes</small>
                    </div>
                    <div class="form-group">
                        <label>Messages maximum par heure</label>
                        <input type="number" name="max_messages" min="10" max="500" value="100">
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="stop_on_error" checked>
                        <label>Arrêter en cas d'erreur</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Appliquer</button>
                </form>
            </div>
        </section>

        <!-- Sécurité -->
        <section class="settings-section">
            <div class="card">
                <h3>Sécurité & Accès</h3>
                <div class="security-settings">
                    <div class="action-item">
                        <p>Connexion WhatsApp</p>
                        <a href="../whatsapp/connexion.php" class="btn btn-outline">Gérer</a>
                    </div>
                    <div class="action-item">
                        <p>Journal d'activité</p>
                        <a href="journal.php" class="btn btn-outline">Voir</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Notifications -->
        <section class="settings-section">
            <div class="card">
                <h3>Notifications</h3>
                <form class="settings-form" id="notificationsForm">
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="notif_campaign_end" checked>
                        <label>Fin de campagne</label>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="notif_errors" checked>
                        <label>Erreurs d'envoi</label>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="notif_weekly_report">
                        <label>Rapport hebdomadaire</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </form>
            </div>
        </section>

        <!-- Actions Administratives -->
        <section class="settings-section">
            <div class="card">
                <h3>Actions Administratives</h3>
                <div class="admin-actions">
                    <div class="action-item">
                        <p>Export des données</p>
                        <button class="btn btn-outline" onclick="exportData()">Exporter</button>
                    </div>
                    <div class="action-item">
                        <p>Réinitialisation</p>
                        <button class="btn btn-danger" onclick="confirmReset()">Tout supprimer</button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<style>
.app-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    color: #333;
    margin-bottom: 8px;
}

.page-header p {
    color: #666;
    margin: 0;
}

.settings-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.card h3 {
    margin: 0 0 20px;
    color: #333;
    font-size: 18px;
}

.settings-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.form-group input,
.form-group textarea {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
    min-height: 60px;
}

.form-group small {
    color: #666;
    font-size: 12px;
}

.checkbox-group {
    flex-direction: row;
    align-items: center;
    gap: 10px;
}

.checkbox-group input[type="checkbox"] {
    margin: 0;
}

.checkbox-group label {
    margin: 0;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
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

.btn-danger {
    background: #ff4444;
    color: white;
}

.btn-danger:hover {
    background: #cc0000;
}

.security-settings,
.admin-actions {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.action-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.action-item:last-child {
    border-bottom: none;
}

.action-item p {
    margin: 0;
    color: #666;
}

/* Responsive */
@media (max-width: 768px) {
    .app-container {
        padding: 16px;
    }
    
    .card {
        padding: 20px;
    }
    
    .action-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .action-item .btn {
        align-self: stretch;
    }
}
</style>

<script>
// Gestion des formulaires
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            // Simulation sauvegarde
            console.log('Sauvegarde:', data);
            
            // Message de confirmation
            showMessage('Paramètres sauvegardés avec succès', 'success');
        });
    });
});

function exportData() {
    showMessage('Export en cours...', 'info');
    // Simulation export
    setTimeout(() => {
        showMessage('Données exportées avec succès', 'success');
    }, 2000);
}

function confirmReset() {
    if (confirm('Êtes-vous sûr de vouloir tout supprimer ? Cette action est irréversible.')) {
        showMessage('Réinitialisation en cours...', 'info');
        // Simulation réinitialisation
        setTimeout(() => {
            showMessage('Données supprimées', 'success');
        }, 2000);
    }
}

function showMessage(message, type = 'info') {
    // Créer un élément de message
    const messageEl = document.createElement('div');
    messageEl.textContent = message;
    messageEl.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 6px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    if (type === 'success') {
        messageEl.style.background = '#25D366';
    } else if (type === 'error') {
        messageEl.style.background = '#ff4444';
    } else {
        messageEl.style.background = '#007bff';
    }
    
    document.body.appendChild(messageEl);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        messageEl.remove();
    }, 3000);
}

// Animation CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
</script>

<?php include('includes/footer.php'); ?>