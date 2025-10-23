<?php
// frontend/includes/header.php

// Fonction pour obtenir l'URL de base adaptée à la structure
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    
    // Obtenir le chemin du script actuel
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    
    // Déterminer le chemin de base selon l'emplacement
    if (strpos($scriptPath, 'whatsapp/') !== false) {
        // Si on est dans le dossier whatsapp, remonter d'un niveau
        $basePath = dirname(dirname($scriptPath)) . '/';
    } else {
        // Sinon, utiliser le répertoire racine
        $basePath = dirname($scriptPath) . '/';
    }
    
    // Nettoyer les doubles slashes
    $basePath = str_replace('//', '/', $basePath);
    
    return $protocol . "://" . $host . $basePath;
}

$baseUrl = getBaseUrl();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MassSender by Braileys - Envoi de messages WhatsApp en masse</title>
    <meta name="description" content="Solution professionnelle pour l'envoi de messages WhatsApp en masse. Simple, rapide et efficace.">
    
    <!-- Préchargement des polices -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $baseUrl; ?>assets/images/favicon.ico">
    
    <style>
        /* Styles de base pour la navigation */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }
        
        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }
        
        .logo-icon {
            font-size: 1.8rem;
            margin-right: 0.5rem;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 1.5rem;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }
        
        /* Style pour le contenu principal */
        main {
            min-height: calc(100vh - 80px);
            background: #f8f9fa;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.5rem;
            }
            
            .nav-link {
                font-size: 0.9rem;
                padding: 0.4rem 0.8rem;
            }
        }
        
        /* Indicateur de page active */
        .nav-link[href*="<?php echo $currentPage; ?>"] {
            background: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }
    </style>
</head>
<body class="app-container">
    <header>
        <nav class="navbar">
            <div class="navbar-content">
                <a href="<?php echo $baseUrl; ?>index.php" class="logo">
                    <span class="logo-icon">📩</span>
                    MassSender
                    <small style="font-size: 0.7rem; margin-left: 0.5rem; opacity: 0.9;">by Braileys</small>
                </a>
                
                <ul class="nav-links">
                    <li>
                        <a href="<?php echo $baseUrl; ?>index.php" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                            🏠 Accueil
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $baseUrl; ?>contacts.php" class="nav-link <?php echo $currentPage === 'contacts.php' ? 'active' : ''; ?>">
                            📇 Contacts
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $baseUrl; ?>message.php" class="nav-link <?php echo $currentPage === 'message.php' ? 'active' : ''; ?>">
                            💬 Messages
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $baseUrl; ?>whatsapp/connexion.php" class="nav-link <?php echo strpos($currentPage, 'connexion.php') !== false ? 'active' : ''; ?>">
                            🔗 WhatsApp
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $baseUrl; ?>parametres.php" class="nav-link <?php echo $currentPage === 'parametres.php' ? 'active' : ''; ?>">
                            ⚙️ Paramètres
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    
    <main>
        <!-- Le contenu spécifique à chaque page sera inséré ici -->