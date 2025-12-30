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

// Formatage de la date
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étiquette #<?php echo $reparation_id; ?></title>
    <style>
        /* Masquer complètement la navbar et tous les éléments de navigation */
        @media print {
            /* Masquer tous les éléments de navigation */
            nav, .navbar, #desktop-navbar, #mobile-dock, #dock-recall-zone,
            .navbar-brand, .navbar-nav, .navbar-collapse, .navbar-toggler,
            .nav, .nav-link, .nav-item, .dropdown, .dropdown-menu,
            header, .header, .top-bar, .menu, .navigation,
            .sidebar, .side-nav, .breadcrumb, .breadcrumbs,
            .btn, button, .controls, .actions, .toolbar,
            .alert, .message, .notification, .toast,
            .footer, .bottom-bar, .status-bar,
            .container-fluid:not(.label-container),
            .row:not(.label-row), .col:not(.label-col),
            .card:not(.label-card), .card-body:not(.label-card-body),
            .form, .form-group, .form-control, input, select, textarea,
            .modal, .modal-dialog, .modal-content, .overlay,
            .particles, #particles, .background, .bg-pattern,
            .servo-logo-container, .theme-switch, .settings-nav,
            .pwa-controls, .modern-controls {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                height: 0 !important;
                width: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                position: absolute !important;
                left: -9999px !important;
                top: -9999px !important;
                z-index: -9999 !important;
            }
            
            /* Forcer l'affichage uniquement du contenu de l'étiquette */
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
            }
            
            /* Afficher uniquement le contenu de l'étiquette */
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
            /* Masquer la navbar même à l'écran */
            nav, .navbar, #desktop-navbar, #mobile-dock, #dock-recall-zone,
            .navbar-brand, .navbar-nav, .navbar-collapse, .navbar-toggler,
            .servo-logo-container, .theme-switch, .particles, #particles {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
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
                            </div>
                            
    <script>
        // Lancer automatiquement l'impression après le chargement
        window.addEventListener('load', function() {
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