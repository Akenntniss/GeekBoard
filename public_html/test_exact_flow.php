<?php
// Test du flux exact de la requête template_sms
session_start();

echo "=== TEST FLUX EXACT ===\n";

// Simuler exactement les conditions de la requête
$_GET['page'] = 'template_sms';
$page = $_GET['page'];

echo "Page demandée: $page\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'non défini') . "\n";
echo "Shop ID initial: " . ($_SESSION['shop_id'] ?? 'non défini') . "\n";

// Vérifier si l'utilisateur est connecté (il ne l'est pas)
if (!isset($_SESSION['user_id'])) {
    echo "Utilisateur non connecté\n";
    
    // Cas spéciaux qui ne nécessitent pas d'authentification
    $no_auth_pages = ['imprimer_etiquette', 'diagnostic_session', 'debug_fournisseurs', 'template_sms'];
    
    if (in_array($page, $no_auth_pages)) {
        echo "Page autorisée sans authentification\n";
        
        // Pour les pages sans authentification, s'assurer que la session magasin est initialisée
        if (!isset($_SESSION['shop_id'])) {
            echo "Shop ID non défini, chargement de subdomain_config.php\n";
            // Forcer la détection du magasin pour les pages sans auth
            require_once __DIR__ . '/config/subdomain_config.php';
            echo "Après subdomain_config.php, Shop ID = " . ($_SESSION['shop_id'] ?? 'non défini') . "\n";
        }
        
        echo "Accès autorisé à la page $page\n";
        
        // Maintenant, simuler l'inclusion de la page
        define('BASE_PATH', '/var/www/mdgeek.top');
        
        // Nettoyer la page (comme dans index.php)
        require_once BASE_PATH . '/includes/functions.php';
        $page = cleanInput($page);
        echo "Page après nettoyage: $page\n";
        
        // Liste des pages autorisées
        $allowed_pages = ['accueil', 'clients', 'ajouter_client', 'modifier_client', 'reparations', 'ajouter_reparation', 'modifier_reparation', 'taches', 'ajouter_tache', 'modifier_tache', 'supprimer_tache', 'commentaires_tache', 'employes', 'ajouter_employe', 'modifier_employe', 'conges', 'conges_employe', 'conges_calendrier', 'conges_imposer', 'conges_disponibles', 'categories', 'fournisseurs', 'commandes', 'commandes_pieces', 'nouvelle_commande', 'ajax/recherche_clients', 'ajax/ajouter_client', 'inventaire', 'inventaire_actions', 'historique_client', 'deconnexion', 'rachat_appareils', 'parametre', 'scanner', 'ajouter_scan', 'nouveau_rachat', 'imprimer_etiquette', 'details_reparation', 'statut_rapide', 'comptes_partenaires', 'reparation_logs', 'reparation_log', 'messagerie', 'base_connaissances', 'article_kb', 'ajouter_article_kb', 'modifier_article_kb', 'gestion_kb', 'sms_templates', 'template_sms', 'sms_historique', 'gardiennage', 'campagne_sms', 'campagne_details', 'bug-reports', 'suivi_reparation', 'admin_notifications', 'retours', 'retours_actions', 'switch_shop', 'diagnostic_session', 'debug_fournisseurs'];

        // Vérifier si la page demandée est autorisée
        if (!in_array($page, $allowed_pages)) {
            echo "Page $page NON autorisée - sera changée en 404\n";
            $page = '404';
        } else {
            echo "Page $page autorisée\n";
        }
        
        echo "Page finale: $page\n";
        
        // Tenter d'inclure la page
        if ($page === 'template_sms') {
            echo "Tentative d'inclusion de sms_templates.php\n";
            $template_path = BASE_PATH . '/pages/sms_templates.php';
            if (file_exists($template_path)) {
                echo "Fichier sms_templates.php trouvé\n";
                echo "SUCCÈS - La page devrait s'afficher\n";
            } else {
                echo "ERREUR - Fichier sms_templates.php non trouvé\n";
            }
        }
        
    } else {
        echo "Page NON autorisée sans authentification - redirection vers login\n";
    }
} else {
    echo "Utilisateur connecté - accès autorisé\n";
}

echo "=== FIN TEST ===\n";
?>
