<?php
// Débogage de session
error_log("============= DÉBUT IMPRIMER_ETIQUETTE =============");
error_log("Session ID: " . session_id());
error_log("Variables de session: " . print_r($_SESSION, true));
error_log("shop_id en session: " . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'non défini'));
error_log("user_id en session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'non défini'));

// Si la session utilisateur n'est pas active, essayer une autre méthode d'authentification
if (!isset($_SESSION['user_id'])) {
    error_log("Tentative d'accès à imprimer_etiquette sans session utilisateur");
    
    // Validation de l'ID de réparation comme critère minimal de sécurité
    $repair_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($repair_id <= 0) {
        error_log("ID de réparation invalide pour imprimer_etiquette: " . $_GET['id']);
        redirect("reparations");
        exit;
    }
    
    // Si l'ID du magasin n'est pas défini, utiliser une valeur par défaut ou essayer de la récupérer
    if (!isset($_SESSION['shop_id'])) {
        // Essayer de récupérer depuis un cookie
        if (isset($_COOKIE['current_shop'])) {
            $_SESSION['shop_id'] = $_COOKIE['current_shop'];
            error_log("Shop ID récupéré depuis cookie pour impression: " . $_SESSION['shop_id']);
        }
        // Ou définir une valeur par défaut (généralement shop_id=1 pour le magasin principal)
        else {
            $_SESSION['shop_id'] = 1;
            error_log("Utilisation du shop_id par défaut (1) pour impression");
        }
    }
    
    // Définir un user_id temporaire pour l'opération d'impression
    $_SESSION['temp_auth_for_print'] = true;
    error_log("Session temporaire créée pour impression d'étiquette");
}

// Vérifier si l'ID de la réparation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_message("ID réparation non spécifié.", "danger");
    redirect("reparations");
}

$reparation_id = (int)$_GET['id'];

// Récupérer les informations de la réparation
try {
    // Utiliser explicitement la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        error_log("Impossible d'obtenir une connexion à la base de données du magasin");
        throw new Exception("Impossible de se connecter à la base de données.");
    }
    
    $stmt = $shop_pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        set_message("Réparation non trouvée.", "danger");
        redirect("reparations");
    }
} catch (PDOException $e) {
    error_log("Erreur PDO dans imprimer_etiquette.php: " . $e->getMessage());
    set_message("Erreur lors de la récupération des informations de la réparation: " . $e->getMessage(), "danger");
    redirect("reparations");
} catch (Exception $e) {
    error_log("Exception dans imprimer_etiquette.php: " . $e->getMessage());
    set_message("Erreur: " . $e->getMessage(), "danger");
    redirect("reparations");
}

// Charger le gestionnaire de layouts
require_once __DIR__ . '/../includes/label_manager.php';

// Récupérer le layout par défaut (défini dans les paramètres)
$selectedLayout = LabelManager::getSelectedLayout($shop_pdo);

// Récupérer les informations de l'entreprise depuis les paramètres
$company_name = 'Maison du Geek';
$company_phone = '';
$company_address = '';

try {
    $stmt_company = $shop_pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone', 'company_address')");
    $stmt_company->execute();
    $company_params = [];
    while ($row = $stmt_company->fetch(PDO::FETCH_ASSOC)) {
        $company_params[$row['cle']] = $row['valeur'];
    }
    
    if (!empty($company_params['company_name'])) {
        $company_name = $company_params['company_name'];
    }
    if (!empty($company_params['company_phone'])) {
        $company_phone = $company_params['company_phone'];
    }
    if (!empty($company_params['company_address'])) {
        $company_address = $company_params['company_address'];
    }
} catch (Exception $e) {
    error_log("Erreur récupération paramètres entreprise: " . $e->getMessage());
}

// Ajouter les infos entreprise au tableau reparation pour les rendre accessibles aux templates
$reparation['company_name'] = $company_name;
$reparation['company_phone'] = $company_phone;
$reparation['company_address'] = $company_address;

// Formatage de la date
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étiquette #<?php echo $reparation_id; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Système de mode nuit (inclus avant les styles personnalisés pour permettre la surcharge) -->
    <?php include_once 'includes/night-mode-system.php'; ?>
    
    <style>
        /* Masquer complètement la navbar et tous les éléments de navigation */
        @media print {
            /* APPROCHE INVERSÉE: Masquer TOUT par défaut */
            * {
                visibility: hidden !important;
            }
            
            /* Puis afficher UNIQUEMENT l'étiquette et son contenu */
            .label-preview,
            .label-preview * {
                visibility: visible !important;
            }
            
            /* Masquer quand même certains éléments spécifiques même dans .label-preview */
            .no-print,
            .btn-return-home {
                display: none !important;
                visibility: hidden !important;
            }
            
            /* Configuration de la page pour l'impression */
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                color: black !important;
                font-family: Arial, sans-serif !important;
                overflow: hidden !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
                width: 100% !important;
                height: 100% !important;
            }
            
            /* Positionner l'étiquette en haut à gauche de la page */
            .print-wrapper {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background: white !important;
            }
            
            /* Afficher l'étiquette correctement */
            .label-preview {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: auto !important;
                z-index: 1 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            
            /* S'assurer que le contenu de l'étiquette est visible */
            .label-content, .label-moderne, .label-business, .label-startup, 
            .label-professional, .etiquette-content, .print-content {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: static !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: 100% !important;
                z-index: 1 !important;
            }
        }
        
        /* Styles pour l'écran (avant impression) */
        @media screen {
            /* Masquer TOUS les éléments de navigation de façon radicale */
            nav, .navbar, .navbar-brand, .navbar-nav, .navbar-collapse, .navbar-toggler,
            #desktop-navbar, #mobile-dock, #mobile_dock_bar, #dock-recall-zone, .dock-bar-container,
            .nav, .nav-link, .nav-item, .dropdown, .dropdown-menu,
            header, .header, .top-bar, .menu, .navigation,
            .sidebar, .side-nav, .breadcrumb, .breadcrumbs,
            .servo-logo-container, .theme-switch, .particles, #particles,
            .pwa-controls, .modern-controls, .nouvelles-actions-overlay, .circular-menu-overlay {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                position: absolute !important;
                left: -9999px !important;
                top: -9999px !important;
                width: 0 !important;
                height: 0 !important;
                pointer-events: none !important;
            }
            
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                overflow: hidden !important;
            }
            
            /* Centrer l'étiquette à l'écran */
            .print-wrapper {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                background: #f5f5f5;
                padding: 20px;
            }
            
            .label-preview {
                background: white;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                border-radius: 8px;
                        overflow: hidden;
                    }
                    
            /* Bouton Retour à l'accueil */
            .btn-return-home {
                margin-top: 20px;
                padding: 12px 30px;
                background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            }
            
            .btn-return-home:hover {
                background: linear-gradient(135deg, #2980b9 0%, #21618c 100%);
                box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
                transform: translateY(-2px);
                color: white;
                text-decoration: none;
            }
            
            .btn-return-home i {
                margin-right: 8px;
            }
            
            /* Styles ULTRA PRIORITAIRES pour les boutons - Force l'opacité et la couleur */
            html body .print-wrapper .btn-primary.btn-print-trigger {
                background-color: #0d6efd !important;
                border: 1px solid #0d6efd !important;
                color: #ffffff !important;
                opacity: 1 !important;
                background-image: none !important; /* Pour contrer les gradients du mode nuit */
                box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important;
            }
            
            html body .print-wrapper .btn-secondary.btn-return-home {
                background-color: #6c757d !important;
                border: 1px solid #6c757d !important;
                color: #ffffff !important;
                opacity: 1 !important;
                background-image: none !important;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important;
            }
            
            /* Survol */
            html body .print-wrapper .btn-primary.btn-print-trigger:hover {
                background-color: #0b5ed7 !important;
                border-color: #0a58ca !important;
                opacity: 1 !important;
                transform: translateY(-1px);
            }
            
            html body .print-wrapper .btn-secondary.btn-return-home:hover {
                background-color: #5c636a !important;
                border-color: #565e64 !important;
                opacity: 1 !important;
                transform: translateY(-1px);
            }

            .btn i {
                margin-right: 8px;
            }
        }
                    </style>
</head>
                <body>
    <div class="print-wrapper">
        <div class="label-preview">
            <?php
            // Charger et afficher directement le layout par défaut
            try {
                echo LabelManager::loadLayout($selectedLayout, $reparation);
            } catch (Exception $e) {
                error_log("Erreur lors du chargement du layout: " . $e->getMessage());
                // Fallback sur le layout par défaut en cas d'erreur
                echo LabelManager::loadLayout('4x6_moderne', $reparation);
            }
            ?>
                                </div>
        <div style="text-align: center;">
            <div class="d-flex justify-content-center gap-3 mt-4 no-print">
        <a href="index.php" class="btn btn-secondary btn-return-home">
            <i class="fas fa-home me-2"></i>Retour à l'accueil
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-print-trigger">
            <i class="fas fa-print me-2"></i>Imprimer
        </button>
    </div>
        </div>
                            </div>
                            
    <script>
        // Lancer automatiquement l'impression après le chargement
        window.addEventListener('load', function() {
            // NETTOYAGE RADICAL DU DOM
            // On supprime physiquement tout ce qui pourrait traîner
            const forbiddenSelectors = [
                'nav', '.navbar', '#desktop-navbar', 
                '#mobile-dock', '#mobile_dock_bar', '.dock-bar-container', 
                'header', '.header', '.footer', '#dock-recall-zone',
                '.nouvelles-actions-overlay', '.circular-menu-overlay', '#nouvelles-actions-overlay'
            ];
            
            forbiddenSelectors.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                if (elements.length > 0) {
                    console.log('Suppression de ' + selector + ' (' + elements.length + ' éléments)');
                    elements.forEach(el => el.remove());
                }
            });
            
            // Attendre un peu pour que les QR codes se génèrent
                setTimeout(function() {
                window.print();
                    }, 1000);
        });
        
        // Fermer la fenêtre après impression (si c'est un popup)
        window.addEventListener('afterprint', function() {
            if (window.opener) {
                window.close();
            }
});
</script>
</body>
</html>
<?php
// Fin du script
exit;
?>