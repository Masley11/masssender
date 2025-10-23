<?php include('includes/header.php'); ?>

<main class="app-container">
    <div class="page-header fade-in">
        <h1>⚙️ Paramètres</h1>
        <p class="page-subtitle">Configurez votre compte et vos préférences d'envoi</p>
    </div>

    <div class="settings-container">
        <!-- Section Informations Compte -->
        <section class="settings-section">
            <div class="card">
                <h3>👤 Informations du Compte</h3>
                <div class="settings-form">
                    <div class="form-group">
                        <label for="company_name">Nom de l'entreprise/organisation</label>
                        <input type="text" id="company_name" name="company_name" placeholder="Votre nom ou entreprise">
                    </div>
                    <div class="form-group">
                        <label for="email">Email de contact</label>
                        <input type="email" id="email" name="email" placeholder="contact@votre-entreprise.com">
                    </div>
                    <div class="form-group">
                        <label for="phone">Téléphone de contact</label>
                        <input type="tel" id="phone" name="phone" placeholder="+33 1 23 45 67 89">
                    </div>
                    <button class="btn btn-primary">💾 Enregistrer les informations</button>
                </div>
            </div>
        </section>

        <!-- Section Mentions Légales Obligatoires -->
        <section class="settings-section">
            <div class="card">
                <h3>📝 Mentions Légales & Consentement</h3>
                <div class="legal-notice">
                    <p class="text-muted">Ces mentions seront ajoutées automatiquement à tous vos messages pour respecter la réglementation.</p>
                    
                    <div class="form-group">
                        <label for="legal_mention">Mention de désinscription obligatoire</label>
                        <textarea id="legal_mention" name="legal_mention" rows="3" placeholder="Ex: Pour vous désinscrire, répondez STOP"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_info">Informations légales de l'expéditeur</label>
                        <textarea id="company_info" name="company_info" rows="3" placeholder="Ex: Société XYZ - SIRET 123 456 789 - 123 Rue Example, 75000 Paris"></textarea>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="auto_legal" name="auto_legal" checked>
                        <label for="auto_legal">Ajouter automatiquement les mentions légales à tous les messages</label>
                    </div>
                    
                    <button class="btn btn-primary">💾 Sauvegarder les mentions</button>
                </div>
            </div>
        </section>

        <!-- Section Paramètres d'Envoi -->
        <section class="settings-section">
            <div class="card">
                <h3>⏰ Paramètres d'Envoi</h3>
                <div class="sending-settings">
                    <div class="form-group">
                        <label for="delay_between_messages">Délai entre chaque message (secondes)</label>
                        <input type="number" id="delay_between_messages" name="delay_between_messages" min="5" max="60" value="10">
                        <small class="text-muted">Recommandé: 10-30 secondes pour éviter les limitations</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_messages_per_hour">Nombre maximum de messages par heure</label>
                        <input type="number" id="max_messages_per_hour" name="max_messages_per_hour" min="10" max="500" value="100">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="stop_on_error" name="stop_on_error" checked>
                        <label for="stop_on_error">Arrêter l'envoi en cas d'erreur répétée</label>
                    </div>
                    
                    <button class="btn btn-primary">💾 Appliquer les paramètres</button>
                </div>
            </div>
        </section>

        <!-- Section Sécurité -->
        <section class="settings-section">
            <div class="card">
                <h3>🔒 Sécurité & Accès</h3>
                <div class="security-settings">
                    <div class="security-item">
                        <h4>Dernière connexion WhatsApp</h4>
                        <p class="text-muted">Aucune connexion active</p>
                        <a href="../whatsapp/connexion.php" class="btn btn-outline">🔗 Gérer la connexion WhatsApp</a>
                    </div>
                    
                    <div class="security-item">
                        <h4>Journal d'activité</h4>
                        <p class="text-muted">Consultez les actions récentes sur votre compte</p>
                        <a href="journal.php" class="btn btn-outline">📋 Voir le journal</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Notifications -->
        <section class="settings-section">
            <div class="card">
                <h3>🔔 Notifications</h3>
                <div class="notification-settings">
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="notif_campaign_end" name="notif_campaign_end" checked>
                        <label for="notif_campaign_end">Me notifier à la fin d'une campagne</label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="notif_errors" name="notif_errors" checked>
                        <label for="notif_errors">Me notifier en cas d'erreurs d'envoi</label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="notif_weekly_report" name="notif_weekly_report">
                        <label for="notif_weekly_report">Rapport hebdomadaire d'activité</label>
                    </div>
                    
                    <button class="btn btn-primary">💾 Enregistrer les préférences</button>
                </div>
            </div>
        </section>

        <!-- Section Actions Administratives -->
        <section class="settings-section">
            <div class="card">
                <h3>🛠️ Actions Administratives</h3>
                <div class="admin-actions">
                    <div class="action-item">
                        <h4>Export des données</h4>
                        <p class="text-muted">Téléchargez l'ensemble de vos données et contacts</p>
                        <button class="btn btn-outline">📤 Exporter mes données</button>
                    </div>
                    
                    <div class="action-item">
                        <h4>Réinitialisation</h4>
                        <p class="text-muted">Supprimez tous vos contacts et historiques</p>
                        <button class="btn btn-danger" onclick="confirmReset()">🗑️ Tout réinitialiser</button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
function confirmReset() {
    if (confirm('⚠️ Êtes-vous sûr de vouloir tout réinitialiser ? Cette action est irréversible et supprimera tous vos contacts et historiques.')) {
        // Action de réinitialisation
        alert('Réinitialisation effectuée');
    }
}
</script>

<?php include('includes/footer.php'); ?>