</main>
    
    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <h4>MassSender</h4>
                <p>Solution professionnelle d'envoi de messages WhatsApp</p>
            </div>
            
            <div class="footer-links">
                <div class="footer-column">
                    <h5>Navigation</h5>
                    <ul>
                        <li><a href="<?php echo $baseUrl; ?>index.php">Accueil</a></li>
                        <li><a href="<?php echo $baseUrl; ?>contacts.php">Contacts</a></li>
                        <li><a href="<?php echo $baseUrl; ?>message.php">Messages</a></li>
                        <li><a href="<?php echo $baseUrl; ?>whatsapp/connexion.php">Connexion WhatsApp</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h5>Légal</h5>
                    <ul>
                        <li><a href="<?php echo $baseUrl; ?>mentions.php">Mentions légales</a></li>
                        <li><a href="<?php echo $baseUrl; ?>confidentialite.php">Confidentialité</a></li>
                        <li><a href="<?php echo $baseUrl; ?>cgu.php">CGU</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h5>Support</h5>
                    <ul>
                        <li><a href="<?php echo $baseUrl; ?>aide.php">Aide</a></li>
                        <li><a href="<?php echo $baseUrl; ?>contact.php">Contact</a></li>
                        <li><a href="<?php echo $baseUrl; ?>statut.php">Statut</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 MassSender – Tous droits réservés – Version 1.0.0</p>
        </div>
    </footer>

    <style>
    footer {
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        margin-top: 40px;
        padding: 30px 0 0;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 40px;
        justify-content: space-between;
    }

    .footer-brand h4 {
        color: #25D366;
        margin: 0 0 8px;
        font-size: 18px;
    }

    .footer-brand p {
        color: #666;
        margin: 0;
        font-size: 14px;
        line-height: 1.4;
    }

    .footer-links {
        display: flex;
        flex-wrap: wrap;
        gap: 40px;
    }

    .footer-column h5 {
        color: #333;
        margin: 0 0 12px;
        font-size: 14px;
        font-weight: 600;
    }

    .footer-column ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-column li {
        margin-bottom: 6px;
    }

    .footer-column a {
        color: #666;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.2s;
    }

    .footer-column a:hover {
        color: #25D366;
    }

    .footer-bottom {
        border-top: 1px solid #e9ecef;
        margin-top: 30px;
        padding: 20px;
        text-align: center;
    }

    .footer-bottom p {
        color: #666;
        margin: 0;
        font-size: 13px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            gap: 30px;
        }
        
        .footer-links {
            gap: 30px;
        }
        
        .footer-column {
            min-width: 140px;
        }
    }

    @media (max-width: 480px) {
        .footer-links {
            flex-direction: column;
            gap: 20px;
        }
    }
    </style>
    
    <script src="<?php echo $baseUrl; ?>assets/js/script.js"></script>
</body>
</html>