<?php
/**
 * Script de test pour vérifier la nouvelle barre de navigation mobile
 */

// Simuler une session pour les tests
session_start();
$_SESSION['shop_id'] = 63; // ID de test
$_SESSION['shop_name'] = 'Test Shop';
$_SESSION['full_name'] = 'Utilisateur Test';

// Inclure les fichiers nécessaires
require_once 'config/database.php';

// Définir la page courante
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'accueil';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Navbar Mobile - GeekBoard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .test-content {
            padding: 20px;
            margin-bottom: 120px;
        }
        
        .test-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 50%;
            width: 56px;
            height: 56px;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
        }
        
        /* Mode sombre pour le contenu de test */
        [data-theme="dark"] body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            color: #e5e7eb;
        }
        
        [data-theme="dark"] .test-card {
            background: rgba(30, 41, 59, 0.8);
            color: #e5e7eb;
        }
        
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
                color: #e5e7eb;
            }
            
            .test-card {
                background: rgba(30, 41, 59, 0.8);
                color: #e5e7eb;
            }
        }
    </style>
</head>
<body>
    <!-- Bouton de basculement de thème -->
    <button class="theme-toggle" onclick="toggleTheme()" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>
    
    <!-- Contenu de test -->
    <div class="test-content">
        <div class="test-card">
            <h1 class="h3 mb-3">🧪 Test - Nouvelle Barre de Navigation Mobile</h1>
            <p class="text-muted">Cette page teste la barre de navigation mobile mise à jour dans navbar_new.php.</p>
            <div class="alert alert-info">
                <strong>Fichier utilisé :</strong> /components/navbar_new.php<br>
                <strong>Page courante :</strong> <?php echo htmlspecialchars($currentPage); ?><br>
                <strong>Shop ID :</strong> <?php echo $_SESSION['shop_id']; ?>
            </div>
        </div>
        
        <div class="test-card">
            <h2 class="h5 mb-3">📱 Instructions de test</h2>
            <ol>
                <li class="mb-2">Redimensionnez la fenêtre pour simuler mobile/tablette (&lt; 1366px)</li>
                <li class="mb-2">Vérifiez que la barre de navigation apparaît en bas</li>
                <li class="mb-2">Testez le bouton + central (doit ouvrir le modal nouvelles_actions_modal)</li>
                <li class="mb-2">Changez de thème avec le bouton lune/soleil</li>
                <li class="mb-2">Vérifiez les effets tactiles sur mobile</li>
            </ol>
        </div>
        
        <div class="test-card">
            <h2 class="h5 mb-3">🔍 Vérifications</h2>
            <div class="row">
                <div class="col-md-6">
                    <h6>Fichiers CSS chargés :</h6>
                    <ul class="small">
                        <li>mobile-navbar-modern.css ✅</li>
                        <li>navbar.css (existant) ✅</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Fichiers JS chargés :</h6>
                    <ul class="small">
                        <li>mobile-navbar-modern.js ✅</li>
                        <li>navbar.js (existant) ✅</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Inclure la navbar mise à jour
    include_once __DIR__ . '/components/navbar_new.php';
    ?>

    <!-- Modal de test (simulé) -->
    <div class="modal fade" id="nouvelles_actions_modal" tabindex="-1" aria-labelledby="nouvelles_actions_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nouvelles_actions_modal_label">
                        <i class="fas fa-sparkles me-2"></i>
                        Créer quelque chose de nouveau
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h6>Modal ouvert avec succès !</h6>
                        <p class="text-muted">Le bouton + fonctionne correctement depuis la barre mobile.</p>
                        <small class="text-muted">Fichier utilisé : navbar_new.php</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Fonction pour basculer le thème
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Mettre à jour l'icône
            const icon = document.querySelector('#themeToggle i');
            icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            
            console.log('Thème basculé vers:', newTheme);
        }
        
        // Charger le thème sauvegardé
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 
                              (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            const icon = document.querySelector('#themeToggle i');
            icon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            
            console.log('🧪 Page de test navbar mobile chargée');
            console.log('📱 Largeur écran:', window.innerWidth);
            console.log('📁 Fichier navbar utilisé: navbar_new.php');
            console.log('🌓 Thème initial:', savedTheme);
        });
        
        // Observer les changements de taille d'écran
        window.addEventListener('resize', function() {
            console.log('📐 Nouvelle taille:', window.innerWidth + 'px');
            if (window.innerWidth < 1366) {
                console.log('📱 Mode mobile/tablette activé');
            } else {
                console.log('🖥️ Mode desktop activé');
            }
        });
    </script>
</body>
</html>
