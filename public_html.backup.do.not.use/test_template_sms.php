<?php
// Script de test pour diagnostiquer le problème avec template_sms
session_start();

echo "=== DIAGNOSTIC TEMPLATE_SMS ===\n";
echo "Host: " . ($_SERVER['HTTP_HOST'] ?? 'non défini') . "\n";
echo "Page demandée: " . ($_GET['page'] ?? 'non défini') . "\n";
echo "Session shop_id: " . ($_SESSION['shop_id'] ?? 'non défini') . "\n";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'non défini') . "\n";

// Tester la détection de magasin
require_once __DIR__ . '/public_html/config/subdomain_config.php';
echo "Après inclusion subdomain_config:\n";
echo "Session shop_id: " . ($_SESSION['shop_id'] ?? 'non défini') . "\n";
echo "Session shop_name: " . ($_SESSION['shop_name'] ?? 'non défini') . "\n";

// Tester la page dans la liste autorisée
$page = $_GET['page'] ?? 'accueil';
$no_auth_pages = ['imprimer_etiquette', 'diagnostic_session', 'debug_fournisseurs', 'template_sms'];
$allowed_pages = ['accueil', 'clients', 'ajouter_client', 'modifier_client', 'reparations', 'ajouter_reparation', 'modifier_reparation', 'taches', 'ajouter_tache', 'modifier_tache', 'supprimer_tache', 'commentaires_tache', 'employes', 'ajouter_employe', 'modifier_employe', 'conges', 'conges_employe', 'conges_calendrier', 'conges_imposer', 'conges_disponibles', 'categories', 'fournisseurs', 'commandes', 'commandes_pieces', 'nouvelle_commande', 'ajax/recherche_clients', 'ajax/ajouter_client', 'inventaire', 'inventaire_actions', 'historique_client', 'deconnexion', 'rachat_appareils', 'parametre', 'scanner', 'ajouter_scan', 'nouveau_rachat', 'imprimer_etiquette', 'details_reparation', 'statut_rapide', 'comptes_partenaires', 'reparation_logs', 'reparation_log', 'messagerie', 'base_connaissances', 'article_kb', 'ajouter_article_kb', 'modifier_article_kb', 'gestion_kb', 'sms_templates', 'template_sms', 'sms_historique', 'gardiennage', 'campagne_sms', 'campagne_details', 'bug-reports', 'suivi_reparation', 'admin_notifications', 'retours', 'retours_actions', 'switch_shop', 'diagnostic_session', 'debug_fournisseurs'];

echo "Page '$page' dans no_auth_pages: " . (in_array($page, $no_auth_pages) ? 'OUI' : 'NON') . "\n";
echo "Page '$page' dans allowed_pages: " . (in_array($page, $allowed_pages) ? 'OUI' : 'NON') . "\n";

// Tester si la page sms_templates.php existe
$sms_templates_path = __DIR__ . '/public_html/pages/sms_templates.php';
echo "Fichier sms_templates.php existe: " . (file_exists($sms_templates_path) ? 'OUI' : 'NON') . "\n";
echo "Chemin: $sms_templates_path\n";

echo "=== FIN DIAGNOSTIC ===\n";
?>
