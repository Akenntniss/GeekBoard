<?php
// DÉTECTION PRÉALABLE DE LA PAGE DE LANDING
// On vérifie AVANT tout si on doit afficher la landing page
$host = $_SERVER['HTTP_HOST'] ?? '';

// Nettoyer le host (enlever le port s'il y en a un)
$host = preg_replace('/:\d+$/', '', $host);

// Si on est sur le domaine principal (mdgeek.top, servo.tools ou www), router vers le site marketing
if ($host === 'mdgeek.top' || $host === 'www.mdgeek.top' || $host === 'servo.tools' || $host === 'www.servo.tools') {
    // Démarrer une session minimale pour vérifier les variables
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // LOGGING pour debug
    error_log("LANDING PAGE DEBUG: Host=$host, shop_id=" . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'non défini') . ", superadmin_id=" . (isset($_SESSION['superadmin_id']) ? $_SESSION['superadmin_id'] : 'non défini'));
    
    // Si aucun shop_id n'est défini ET qu'on n'est pas un super admin, afficher le site marketing multi-pages
    if (!isset($_SESSION['shop_id']) && !isset($_SESSION['superadmin_id'])) {
        // Router marketing (multi-pages: accueil, fonctionnalités, tarifs, avis, contact, calculateur ROI)
        include __DIR__ . '/marketing/router.php';
        exit;
    }
}

// Si on arrive ici, on charge l'application normale
// Inclure la configuration de session avant de démarrer la session
require_once __DIR__ . '/config/session_config.php';
// La session est déjà démarrée dans session_config.php, pas besoin de session_start() ici

// Inclure la configuration pour la gestion des sous-domaines
require_once __DIR__ . '/config/subdomain_config.php';
// Le sous-domaine est détecté et la session est configurée avec le magasin correspondant

// Vérifier si on est dans le contexte d'un magasin spécifique
if (isset($_SESSION['shop_id'])) {
    // Nous sommes dans un magasin spécifique, noter cela pour l'interface
    $current_shop_id = $_SESSION['shop_id'];
    $current_shop_name = $_SESSION['shop_name'] ?? 'Magasin';
} else {
    // Pas de magasin sélectionné, vérifier si c'est un super administrateur
    if (isset($_SESSION['superadmin_id'])) {
        // Rediriger vers le tableau de bord des magasins
        header('Location: superadmin/index.php');
        exit;
    }
}

// Vérifier les paramètres de test PWA dans l'URL et les stocker dans la session
if (isset($_GET['test_pwa']) && $_GET['test_pwa'] === 'true') {
    $_SESSION['test_pwa'] = true;
}

if (isset($_GET['test_ios']) && $_GET['test_ios'] === 'true') {
    $_SESSION['test_ios'] = true;
}

if (isset($_GET['test_dynamic_island']) && $_GET['test_dynamic_island'] === 'true') {
    $_SESSION['test_dynamic_island'] = true;
}

// Définir la page demandée (nécessaire pour la vérification d'authentification)
$page = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Gestion spéciale pour la page devis (contexte iframe)
if ($page === 'devis') {
    // Forcer l'initialisation de session magasin si elle n'est pas déjà faite
    if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
        error_log("DEVIS IFRAME: Initialisation forcée de session magasin");
        detectShopFromSubdomain();
    }
    
    error_log("DEVIS IFRAME: shop_id=" . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'non défini') . 
              ", user_id=" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'non défini'));
}

// Vérification d'authentification
if (!isset($_SESSION['user_id'])) {
    // Cas spéciaux qui ne nécessitent pas d'authentification
    $no_auth_pages = ['imprimer_etiquette', 'diagnostic_session', 'debug_fournisseurs', 'devis_client', 'reparation_logs'];
    
    // Permettre l'accès à la page devis si on a une session magasin valide (contexte iframe)
    if ($page === 'devis' && isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
        error_log("Accès à la page devis autorisé via session magasin (shop_id: " . $_SESSION['shop_id'] . ") - contexte iframe");
        $no_auth_pages[] = 'devis';
    }
    
    if (in_array($page, $no_auth_pages)) {
        // Permettre l'accès à ces pages sans authentification
        if ($page == 'imprimer_etiquette' && isset($_GET['id'])) {
            error_log("Accès à imprimer_etiquette avec id=" . $_GET['id'] . " sans session utilisateur active.");
        }
        // Continuer sans redirection
    } else {
        // Détecter si on est sur un sous-domaine de magasin pour rediriger vers login_auto.php
        if (isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
            $redirect_url = '/pages/login_auto.php?shop_id=' . $_SESSION['shop_id'];
        } else {
            $redirect_url = '/pages/login.php';
        }
        
        $pwa_params = [];
        
        if (isset($_SESSION['test_pwa']) && $_SESSION['test_pwa'] === true) {
            $pwa_params[] = 'test_pwa=true';
        }
        
        if (isset($_SESSION['test_ios']) && $_SESSION['test_ios'] === true) {
            $pwa_params[] = 'test_ios=true';
        }
        
        if (isset($_SESSION['test_dynamic_island']) && $_SESSION['test_dynamic_island'] === true) {
            $pwa_params[] = 'test_dynamic_island=true';
        }
        
        // Mémoriser la page originale pour y revenir après connexion
        if (isset($_GET['page'])) {
            $pwa_params[] = 'redirect=' . urlencode($_GET['page']);
            
            // Ajouter l'ID si présent
            if (isset($_GET['id'])) {
                $pwa_params[] = 'id=' . $_GET['id'];
            }
            // Propager open_modal si présent (pour ouvrir automatiquement le modal réparation)
            if (isset($_GET['open_modal'])) {
                $pwa_params[] = 'open_modal=' . urlencode($_GET['open_modal']);
            }
        }
        
        // Ajouter les paramètres à l'URL de redirection
        if (!empty($pwa_params)) {
            $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . implode('&', $pwa_params);
        }
        
        header('Location: ' . $redirect_url);
        exit();
    }
}

// Configuration de l'affichage des erreurs (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le chemin de base
define('BASE_PATH', __DIR__);
define('BASE_URL', '/');

// Vérification et redirection de domaine
$current_domain = $_SERVER['HTTP_HOST'];
if (strpos($current_domain, 'mdgeek.fr') !== false) {
    // Récupérer l'URL complète actuelle
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    // Remplacer mdgeek.fr par mdgeek.top
    $new_url = str_replace('mdgeek.fr', 'mdgeek.top', $current_url);
    
    // Effectuer la redirection permanente
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $new_url);
    exit;
}

// Script d'initialisation navbar responsive - respecte les breakpoints CSS
echo '<script>
// Fonction pour gérer l\'affichage responsive de la navbar
function handleResponsiveNavbar() {
    const isDesktop = window.innerWidth >= 992;
    const desktopNavbar = document.getElementById("desktop-navbar");
    const mobileDock = document.getElementById("mobile-dock");
    
    // Les règles CSS media queries gèrent déjà l\'affichage
    // Ce script ne fait que des ajustements si nécessaire
    
    if (isDesktop) {
        // Sur desktop, s\'assurer que le padding-top du body est correct
        document.body.style.paddingTop = "55px";
        
        // Log pour debug
        console.log("Mode desktop activé - navbar en haut visible");
    } else {
        // Sur mobile, retirer le padding-top
        document.body.style.paddingTop = "0px";
        
        // Log pour debug
        console.log("Mode mobile activé - dock en bas visible");
    }
}

// Exécuter au chargement
document.addEventListener("DOMContentLoaded", function() {
    handleResponsiveNavbar();
    
    // Écouter les changements de taille d\'écran
    window.addEventListener("resize", handleResponsiveNavbar);
});
</script>';

// Inclure les fichiers de configuration et de connexion à la base de données
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/actions/inventaire_actions.php';

// Nettoyer la page maintenant que functions.php est inclus
$page = cleanInput($page);

// Liste des pages autorisées
$allowed_pages = ['accueil', 'clients', 'ajouter_client', 'modifier_client', 'reparations', 'devis', 'devis_client', 'ajouter_reparation', 'modifier_reparation', 'taches', 'ajouter_tache', 'modifier_tache', 'supprimer_tache', 'commentaires_tache', 'employes', 'ajouter_employe', 'modifier_employe', 'conges', 'conges_employe', 'conges_calendrier', 'conges_imposer', 'conges_disponibles', 'inventaire', 'categories', 'fournisseurs', 'commandes', 'commandes_pieces', 'nouvelle_commande', 'ajax/recherche_clients', 'ajax/ajouter_client', 'inventaire_actions', 'historique_client', 'deconnexion', 'rachat_appareils', 'parametre', 'scanner', 'ajouter_scan', 'nouveau_rachat', 'imprimer_etiquette', 'details_reparation', 'statut_rapide', 'comptes_partenaires', 'reparation_logs', 'reparation_log', 'messagerie', 'base_connaissances', 'article_kb', 'ajouter_article_kb', 'modifier_article_kb', 'gestion_kb', 'sms_templates', 'template_sms', 'sms_historique', 'gardiennage', 'campagne_sms', 'campagne_details', 'bug-reports', 'suivi_reparation', 'admin_notifications', 'admin_timetracking', 'retours', 'retours_actions', 'switch_shop', 'diagnostic_session', 'debug_fournisseurs', 'presence_gestion', 'presence_ajouter', 'presence_calendrier', 'presence_export', 'presence_export_handler', 'presence_export_print', 'presence_modifier', 'presence_form', 'mes_missions', 'admin_missions', 'garanties', 'kpi_dashboard'];

// Vérifier si la page demandée est autorisée
if (!in_array($page, $allowed_pages)) {
    $page = '404';
}

// Gérer le changement de magasin si demandé
if ($page === 'switch_shop') {
    // Vérifie si l'utilisateur est un super administrateur
    if (isset($_SESSION['superadmin_id'])) {
        $shop_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($shop_id > 0) {
            // Stocker l'ID du magasin en session
            $_SESSION['shop_id'] = $shop_id;
            
            // Récupérer le nom du magasin
            $shop_pdo = getMainDBConnection();
            $stmt = $shop_pdo->prepare("SELECT name FROM shops WHERE id = ?");
            $stmt->execute([$shop_id]);
            $shop = $stmt->fetch();
            
            if ($shop) {
                $_SESSION['shop_name'] = $shop['name'];
            }
        }
    }
    
    // Rediriger vers la page d'accueil
    header('Location: index.php');
    exit;
}

// Contenu principal
try {
    // Vérifier si c'est une requête AJAX ou une page publique sans layout
    $is_ajax = strpos($page, 'ajax/') === 0 || 
               (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') ||
               (isset($_POST['action']) && in_array($_POST['action'], ['approve_entry', 'reject_entry', 'force_clock_out', 'send_notification', 'alert_details']));
    $is_public_page = in_array($page, ['devis_client']);
    
    // Ne pas inclure l'en-tête pour les requêtes AJAX ou les pages publiques
    $is_modal = isset($_GET['modal']) && $_GET['modal'] == '1';
    if (!$is_ajax && !$is_public_page) {
        include BASE_PATH . '/includes/header.php';
    }
    
    switch ($page) {
        case 'accueil':
            include BASE_PATH . '/pages/accueil.php';
            break;
        case 'clients':
            include BASE_PATH . '/pages/clients.php';
            break;
        case 'ajouter_client':
            include BASE_PATH . '/pages/ajouter_client.php';
            break;
        case 'modifier_client':
            include BASE_PATH . '/pages/modifier_client.php';
            break;
        case 'reparations':
            include BASE_PATH . '/pages/reparations.php';
            break;
        case 'devis':
            include BASE_PATH . '/pages/devis.php';
            break;
        case 'devis_client':
            include BASE_PATH . '/pages/devis_client.php';
            break;
        case 'ajouter_reparation':
            include BASE_PATH . '/pages/ajouter_reparation.php';
            break;
        case 'modifier_reparation':
            include BASE_PATH . '/pages/modifier_reparation.php';
            break;
        case 'taches':
            include BASE_PATH . '/pages/taches.php';
            break;
        case 'ajouter_tache':
            include BASE_PATH . '/pages/ajouter_tache.php';
            break;
        case 'modifier_tache':
            include BASE_PATH . '/pages/modifier_tache.php';
            break;
        case 'supprimer_tache':
            include BASE_PATH . '/pages/supprimer_tache.php';
            break;
        case 'commentaires_tache':
            include BASE_PATH . '/pages/commentaires_tache.php';
            break;
        case 'employes':
            include BASE_PATH . '/pages/employes.php';
            break;
        case 'ajouter_employe':
            include BASE_PATH . '/pages/ajouter_employe.php';
            break;
        case 'modifier_employe':
            include BASE_PATH . '/pages/modifier_employe.php';
            break;
        case 'conges':
            include BASE_PATH . '/pages/conges.php';
            break;
        case 'conges_employe':
            include BASE_PATH . '/pages/conges_employe.php';
            break;
        case 'conges_calendrier':
            include BASE_PATH . '/pages/conges_calendrier.php';
            break;
        case 'conges_imposer':
            include BASE_PATH . '/pages/conges_imposer.php';
            break;
        
        // Pages de gestion des absences et retards
        case 'presence_gestion':
            include BASE_PATH . '/pages/presence_gestion.php';
            break;
        case 'presence_ajouter':
            include BASE_PATH . '/pages/presence_ajouter.php';
            break;
        case 'presence_calendrier':
            include BASE_PATH . '/pages/presence_calendrier.php';
            break;
        case 'presence_export':
            include BASE_PATH . '/pages/presence_export.php';
            break;
        case 'presence_export_handler':
            include BASE_PATH . '/pages/presence_export_handler.php';
            break;
        case 'presence_export_print':
            include BASE_PATH . '/pages/presence_export_print.php';
            break;
        case 'presence_modifier':
            include BASE_PATH . '/pages/presence_modifier.php';
            break;
            
            
        case 'conges_disponibles':
            include BASE_PATH . '/pages/conges_disponibles.php';
            break;
        case 'inventaire':
            include BASE_PATH . '/pages/inventaire_elegant.php';
            break;
        case 'categories':
            include BASE_PATH . '/pages/categories.php';
            break;
        case 'fournisseurs':
            include BASE_PATH . '/pages/fournisseurs.php';
            break;
        case 'commandes':
            include BASE_PATH . '/pages/commandes.php';
            break;
        case 'commandes_pieces':
            include BASE_PATH . '/pages/commandes_pieces.php';
            break;
        case 'nouvelle_commande':
            include BASE_PATH . '/pages/nouvelle_commande.php';
            break;
        case 'historique_client':
            include BASE_PATH . '/pages/historique_client.php';
            break;
        case 'ajax/recherche_clients':
            include BASE_PATH . '/ajax/recherche_clients.php';
            break;
        case 'ajax/ajouter_client':
            include BASE_PATH . '/ajax/ajouter_client.php';
            break;
        case 'inventaire_actions':
            require_once BASE_PATH . '/actions/inventaire_actions.php';
            break;
        case 'deconnexion':
            include BASE_PATH . '/pages/deconnexion.php';
            break;
        case 'rachat_appareils':
            include BASE_PATH . '/pages/rachat_appareils.php';
            break;
        case 'nouveau_rachat':
            include BASE_PATH . '/pages/nouveau_rachat.php';
            break;
        case 'scanner':
            include BASE_PATH . '/pages/scanner.php';
            break;
        case 'ajouter_scan':
            include BASE_PATH . '/pages/ajouter_scan.php';
            break;
        case 'parametre':
            include BASE_PATH . '/pages/parametre.php';
            break;
        case 'imprimer_etiquette':
            include BASE_PATH . '/pages/imprimer_etiquette.php';
            break;
        case 'details_reparation':
            include BASE_PATH . '/pages/details_reparation.php';
            break;
        case 'statut_rapide':
            include BASE_PATH . '/pages/statut_rapide.php';
            break;
        case 'comptes_partenaires':
            include BASE_PATH . '/pages/comptes_partenaires.php';
            break;
        case 'reparation_logs':
            include BASE_PATH . '/pages/reparation_logs.php';
            break;
        case 'reparation_log':
            include BASE_PATH . '/pages/reparation_logs.php';
            break;
        case 'messagerie':
            include BASE_PATH . '/pages/messagerie.php';
            break;
        case 'base_connaissances':
            include BASE_PATH . '/pages/base_connaissances.php';
            break;
        case 'article_kb':
            include BASE_PATH . '/pages/article_kb.php';
            break;
        case 'ajouter_article_kb':
            include BASE_PATH . '/pages/ajouter_article_kb.php';
            break;
        case 'modifier_article_kb':
            include BASE_PATH . '/pages/modifier_article_kb.php';
            break;
        case 'gestion_kb':
            include BASE_PATH . '/pages/gestion_kb.php';
            break;
        case 'sms_templates':
            include BASE_PATH . '/pages/sms_templates.php';
            break;
        case 'template_sms':
            include BASE_PATH . '/pages/sms_templates.php';
            break;
        case 'sms_historique':
            include BASE_PATH . '/pages/sms_historique.php';
            break;
        case 'gardiennage':
            include BASE_PATH . '/pages/gardiennage.php';
            break;
        case 'campagne_sms':
            include BASE_PATH . '/pages/campagne_sms.php';
            break;
        case 'campagne_details':
            include BASE_PATH . '/pages/campagne_details.php';
            break;
        case 'bug-reports':
            include BASE_PATH . '/pages/bug-reports.php';
            break;
        case 'suivi_reparation':
            include BASE_PATH . '/pages/suivi_reparation.php';
            break;
        case 'admin_notifications':
            include BASE_PATH . '/pages/admin_notifications.php';
            break;
        case 'admin_timetracking':
            include BASE_PATH . '/pages/admin_timetracking.php';
            break;
        case 'retours':
            include BASE_PATH . '/pages/retours.php';
            break;
        case 'retours_actions':
            include BASE_PATH . '/pages/retours_actions.php';
            break;
        case 'diagnostic_session':
            include BASE_PATH . '/pages/diagnostic_session.php';
            break;
        case 'debug_fournisseurs':
            include BASE_PATH . '/ajax/debug_fournisseurs.php';
            break;
        case 'mes_missions':
            include BASE_PATH . '/pages/mes_missions_harmonieux.php';
            break;
        case 'admin_missions':
            include BASE_PATH . '/pages/admin_missions_harmonieux.php';
            break;
        case 'garanties':
            include BASE_PATH . '/pages/garanties.php';
            break;
        case 'kpi_dashboard':
            include BASE_PATH . '/pages/kpi_dashboard_integrated.php';
            break;
        case 'kpi_debug':
            include BASE_PATH . '/pages/kpi_debug_integrated.php';
            break;
        case 'kpi_simple':
            include BASE_PATH . '/pages/kpi_simple_integrated.php';
            break;
        case 'kpi_test':
            include BASE_PATH . '/pages/kpi_test.php';
            break;
        case '404':
            include BASE_PATH . '/pages/404.php';
            break;
        default:
            include BASE_PATH . '/pages/404.php';
            break;
    }
    
    // Ne pas inclure le footer pour les requêtes AJAX, les pages publiques, ou en mode modal
    if (!$is_ajax && !$is_public_page && !$is_modal) {
        include BASE_PATH . '/includes/footer.php';
    }
} catch (Exception $e) {
    // Log l'erreur
    error_log("Erreur lors du chargement de la page $page : " . $e->getMessage());
    // Afficher une page d'erreur générique
    include BASE_PATH . '/pages/404.php';
}
?>