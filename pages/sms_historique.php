<?php

// Vérifier les droits d'accès de base
if (!isset($_SESSION['user_id'])) {
    set_message("Vous devez être connecté pour accéder à cette page.", "danger");
    redirect("");
    exit;
}

// Variable pour déterminer le niveau d'accès
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Obtenir la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Paramètres de pagination
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// Paramètres de filtrage
$recherche_globale = isset($_GET['recherche']) ? clean_input($_GET['recherche']) : null;
$statut_filter = isset($_GET['statut_id']) ? clean_input($_GET['statut_id']) : null;

// Construction de la requête UNION pour combiner les deux tables
$where_conditions_logs = [];
$where_conditions_rep = [];
$params_count = [];

// Filtre par statut
if ($statut_filter) {
    if ($statut_filter === 'sent') {
        $where_conditions_logs[] = "sl.status = 1";
        $where_conditions_rep[] = "rs.statut_id = 1";
    } elseif ($statut_filter === 'failed') {
        $where_conditions_logs[] = "sl.status = 0";
        $where_conditions_rep[] = "rs.statut_id = 0";
    }
}

// Recherche globale unifiée (ID, téléphone, contenu, nom/prénom, appareil/modèle)
if ($recherche_globale) {
    $search_param = '%' . $recherche_globale . '%';
    
    // Pour sms_logs: recherche dans recipient, message, reparation_id, et via jointures dans client et réparation
    $where_conditions_logs[] = "(
        sl.recipient LIKE ? 
        OR sl.message LIKE ? 
        OR CAST(sl.reparation_id AS CHAR) LIKE ?
        OR c.nom LIKE ? 
        OR c.prenom LIKE ? 
        OR CONCAT(c.prenom, ' ', c.nom) LIKE ?
        OR r.type_appareil LIKE ?
        OR r.modele LIKE ?
        OR r.marque LIKE ?
    )";
    
    // Pour reparation_sms: même logique
    $where_conditions_rep[] = "(
        rs.telephone LIKE ? 
        OR rs.message LIKE ? 
        OR CAST(rs.reparation_id AS CHAR) LIKE ?
        OR c2.nom LIKE ? 
        OR c2.prenom LIKE ? 
        OR CONCAT(c2.prenom, ' ', c2.nom) LIKE ?
        OR r2.type_appareil LIKE ?
        OR r2.modele LIKE ?
        OR r2.marque LIKE ?
    )";
    
    // 9 paramètres pour sms_logs + 9 pour reparation_sms
    for ($i = 0; $i < 18; $i++) {
        $params_count[] = $search_param;
    }
}

$where_clause_logs = !empty($where_conditions_logs) ? "WHERE " . implode(" AND ", $where_conditions_logs) : "";
$where_clause_rep = !empty($where_conditions_rep) ? "WHERE " . implode(" AND ", $where_conditions_rep) : "";

// Compter le nombre total avec UNION
try {
    $sql_count = "
        SELECT COUNT(*) as total FROM (
            SELECT sl.id FROM sms_logs sl
            LEFT JOIN reparations r ON sl.reparation_id = r.id
            LEFT JOIN clients c ON r.client_id = c.id
            " . $where_clause_logs . "
            UNION
            SELECT rs.id FROM reparation_sms rs
            LEFT JOIN reparations r2 ON rs.reparation_id = r2.id
            LEFT JOIN clients c2 ON r2.client_id = c2.id
            " . $where_clause_rep . "
        ) as combined_sms
    ";
    $stmt = $shop_pdo->prepare($sql_count);
    $stmt->execute($params_count);
    $total_items = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Erreur SQL count combiné: " . $e->getMessage());
    $total_items = 0;
}

// Calculer le nombre de pages
$total_pages = ceil($total_items / $items_per_page);

// Récupérer les SMS des deux tables avec UNION
$historique = [];
if ($total_items > 0) {
    try {
        $sql = "
            SELECT 
                sl.id,
                sl.recipient as telephone,
                sl.message,
                sl.date_envoi,
                CASE 
                    WHEN sl.status = 1 THEN 1
                    ELSE 0
                END as statut_success,
                NULL as reference_type,
                NULL as reference_id,
                sl.reparation_id,
                NULL as template_id,
                'sms_logs' as source_table
            FROM sms_logs sl
            LEFT JOIN reparations r ON sl.reparation_id = r.id
            LEFT JOIN clients c ON r.client_id = c.id
            " . $where_clause_logs . "
            
            UNION ALL
            
            SELECT 
                rs.id,
                rs.telephone,
                rs.message,
                rs.date_envoi,
                rs.statut_id as statut_success,
                'reparation_sms' as reference_type,
                rs.reparation_id as reference_id,
                rs.reparation_id,
                rs.template_id,
                'reparation_sms' as source_table
            FROM reparation_sms rs
            LEFT JOIN reparations r2 ON rs.reparation_id = r2.id
            LEFT JOIN clients c2 ON r2.client_id = c2.id
            " . $where_clause_rep . "
            
            ORDER BY date_envoi DESC 
            LIMIT ? OFFSET ?
        ";
        
        $params = array_merge($params_count, [$items_per_page, $offset]);
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute($params);
        $sms_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enrichir chaque SMS avec les informations des autres tables
        foreach ($sms_records as $sms) {
            $enriched_sms = $sms;
            
            // Déterminer le reparation_id
            $actual_reparation_id = $sms['reparation_id'];
            
            // Récupérer les informations de la réparation et du client
            if ($actual_reparation_id) {
                try {
                    $stmt_repair = $shop_pdo->prepare("SELECT r.*, c.nom, c.prenom, c.telephone FROM reparations r LEFT JOIN clients c ON r.client_id = c.id WHERE r.id = ?");
                    $stmt_repair->execute([$actual_reparation_id]);
                    $repair = $stmt_repair->fetch(PDO::FETCH_ASSOC);
                    
                    if ($repair) {
                        $enriched_sms['repair_id'] = $repair['id'];
                        $enriched_sms['reparation_id'] = $repair['id'];
                        $enriched_sms['type_appareil'] = $repair['type_appareil'] ?? 'Type inconnu';
                        $enriched_sms['modele'] = $repair['modele'] ?? 'Modèle inconnu';
                        $enriched_sms['marque'] = $repair['marque'] ?? 'Marque inconnue';
                        $enriched_sms['client_nom'] = $repair['nom'] ?? 'Client inconnu';
                        $enriched_sms['client_prenom'] = $repair['prenom'] ?? '';
                        $enriched_sms['client_telephone'] = $repair['telephone'] ?? $sms['telephone'];
                    } else {
                        $enriched_sms['repair_id'] = $actual_reparation_id;
                        $enriched_sms['reparation_id'] = $actual_reparation_id;
                        $enriched_sms['type_appareil'] = 'Réparation supprimée';
                        $enriched_sms['modele'] = '';
                        $enriched_sms['marque'] = '';
                        $enriched_sms['client_nom'] = 'Client inconnu';
                        $enriched_sms['client_prenom'] = '';
                        $enriched_sms['client_telephone'] = $sms['telephone'];
                    }
                } catch (PDOException $e) {
                    $enriched_sms['repair_id'] = $actual_reparation_id;
                    $enriched_sms['reparation_id'] = $actual_reparation_id;
                    $enriched_sms['type_appareil'] = 'Erreur de récupération';
                    $enriched_sms['modele'] = '';
                    $enriched_sms['marque'] = '';
                    $enriched_sms['client_nom'] = 'Client inconnu';
                    $enriched_sms['client_prenom'] = '';
                    $enriched_sms['client_telephone'] = $sms['telephone'];
                }
            } else {
                // SMS sans réparation associée
                $enriched_sms['repair_id'] = 'N/A';
                $enriched_sms['reparation_id'] = null;
                $enriched_sms['type_appareil'] = 'SMS direct';
                $enriched_sms['modele'] = '';
                $enriched_sms['marque'] = '';
                $enriched_sms['client_nom'] = 'Contact direct';
                $enriched_sms['client_prenom'] = '';
                $enriched_sms['client_telephone'] = $sms['telephone'];
            }
            
            // Template basé sur source
            if ($sms['source_table'] === 'sms_logs') {
                $enriched_sms['template_nom'] = 'SMS système (Nouveau)';
            } else {
                // Pour reparation_sms, essayer de récupérer le template réel
                try {
                    $stmt_template = $shop_pdo->prepare("SELECT nom FROM sms_templates WHERE id = ?");
                    $stmt_template->execute([$sms['template_id'] ?? 1]);
                    $template = $stmt_template->fetch(PDO::FETCH_ASSOC);
                    $enriched_sms['template_nom'] = $template ? $template['nom'] . ' (Ancien)' : 'Template inconnu (Ancien)';
                } catch (PDOException $e) {
                    $enriched_sms['template_nom'] = 'Template inconnu (Ancien)';
                }
            }
            
            // Statut unifié
            $enriched_sms['statut_nom'] = $sms['statut_success'] ? 'Envoyé' : 'Échec';
            $enriched_sms['statut_id'] = $sms['statut_success'];
            
            $historique[] = $enriched_sms;
        }
    } catch (PDOException $e) {
        error_log("Erreur SQL select combiné: " . $e->getMessage());
        $historique = [];
    }
}

// Statuts pour le filtre
$statuts = [
    ['id' => 'sent', 'nom' => 'Envoyé', 'categorie_nom' => 'État'],
    ['id' => 'failed', 'nom' => 'Échec', 'categorie_nom' => 'État']
];
// Affichage: table sur desktop (>=992px), cartes sur mobile
$view_mode = 'hybrid';

// Statistiques globales (total, envoyés, échecs) pour bandeau de stats
try {
    $sql_stats = "
        SELECT 
            SUM(statut_success) AS sent,
            SUM(CASE WHEN statut_success = 0 THEN 1 ELSE 0 END) AS failed,
            COUNT(*) AS total
        FROM (
            SELECT CASE WHEN sl.status = 1 THEN 1 ELSE 0 END AS statut_success 
            FROM sms_logs sl
            LEFT JOIN reparations r ON sl.reparation_id = r.id
            LEFT JOIN clients c ON r.client_id = c.id
            " . $where_clause_logs . "
            UNION ALL
            SELECT CASE WHEN rs.statut_id = 1 THEN 1 ELSE 0 END AS statut_success 
            FROM reparation_sms rs
            LEFT JOIN reparations r2 ON rs.reparation_id = r2.id
            LEFT JOIN clients c2 ON r2.client_id = c2.id
            " . $where_clause_rep . "
        ) AS stats
    ";
    $stmt_stats = $shop_pdo->prepare($sql_stats);
    $stmt_stats->execute($params_count);
    $stats_row = $stmt_stats->fetch(PDO::FETCH_ASSOC) ?: ['total' => $total_items, 'sent' => 0, 'failed' => 0];
    $stats_total = (int)$stats_row['total'];
    $stats_sent = (int)$stats_row['sent'];
    $stats_failed = (int)$stats_row['failed'];
} catch (PDOException $e) {
    $stats_total = $total_items;
    $stats_sent = 0;
    $stats_failed = 0;
}
?>

<!-- Styles CSS personnalisés avec mode sombre ultra-perfectionné -->
<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    --card-hover-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    --border-radius: 15px;
    
    /* Variables pour le mode clair */
    --bg-primary: #ffffff;
    --bg-secondary: #f8f9fa;
    --bg-tertiary: #e9ecef;
    --text-primary: #2c3e50;
    --text-secondary: #6c757d;
    --text-muted: #adb5bd;
    --border-color: #e3f2fd;
    --card-bg: #ffffff;
    --input-bg: #ffffff;
}

/* Mode sombre ultra-perfectionné */
[data-bs-theme="dark"] {
    --primary-gradient: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
    --success-gradient: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
    --warning-gradient: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    --info-gradient: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    --card-hover-shadow: 0 15px 40px rgba(0, 0, 0, 0.7);
    
    /* Variables pour le mode sombre (contrastes renforcés) */
    --bg-primary: #0f1216;          /* fond global légèrement plus froid */
    --bg-secondary: #141a22;        /* cartes/filtres */
    --bg-tertiary: #1b2330;         /* encarts */
    --text-primary: #f5f7fa;        /* texte principal */
    --text-secondary: #c9d2dd;      /* sous-titres/labels */
    --text-muted: #9fb0c3;          /* placeholders */
    --border-color: #2c3642;        /* traits */
    --card-bg: #121822;             /* cartes de contenu */
    --input-bg: #17212d;            /* champs */
}

/* Force le mode sombre sur le body */
[data-bs-theme="dark"] body,
body[data-bs-theme="dark"] {
    background: var(--bg-primary) !important;
    color: var(--text-primary) !important;
}

[data-bs-theme="dark"] .container,
[data-bs-theme="dark"] .container-fluid {
    background: transparent !important;
}

.sms-historique-container {
    max-width: 1400px; /* Largeur desktop élargie */
    margin: 0 auto;
    padding: 24px 24px 40px;
    background: transparent;
}

.page-header {
    background: var(--primary-gradient);
    color: white;
    padding: 40px 30px;
    border-radius: var(--border-radius);
    margin-bottom: 30px;
    box-shadow: var(--card-shadow);
    text-align: center;
}

.page-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
}

/* Mode jour - Header noir */
html body .page-header h1,
html body .page-header h1 i,
.sms-historique-container .page-header h1,
.sms-historique-container .page-header h1 i {
    color: #1a1a2e !important;
    text-shadow: none !important;
}

html body .page-header .subtitle,
.sms-historique-container .page-header .subtitle,
html body .page-header p {
    color: #2c3e50 !important;
}

/* Mode sombre - Header blanc */
[data-bs-theme="dark"] .page-header h1,
[data-bs-theme="dark"] .page-header h1 i,
html[data-bs-theme="dark"] body .page-header h1,
html[data-bs-theme="dark"] body .page-header h1 i,
body.night-mode .page-header h1,
body.night-mode .page-header h1 i,
body.dark-mode .page-header h1,
body.dark-mode .page-header h1 i,
.sms-historique-container .page-header h1[data-bs-theme="dark"] {
    color: #ffffff !important;
    text-shadow: 0 2px 6px rgba(0, 0, 0, 0.4) !important;
}
[data-bs-theme="dark"] .page-header .subtitle,
[data-bs-theme="dark"] .page-header p,
html[data-bs-theme="dark"] body .page-header .subtitle,
html[data-bs-theme="dark"] body .page-header p,
body.night-mode .page-header .subtitle,
body.night-mode .page-header p,
body.dark-mode .page-header .subtitle,
body.dark-mode .page-header p {
    color: rgba(255, 255, 255, 0.95) !important;
}

.filters-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: var(--card-shadow);
    border: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

[data-bs-theme="dark"] .filters-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
}
[data-bs-theme="dark"] .filters-card .form-label { color: var(--text-secondary); }
[data-bs-theme="dark"] .filters-card h5 { color: var(--text-primary); }

.filters-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-hover-shadow);
}

.filters-card h5 {
    color: var(--text-primary);
    font-weight: 600;
    margin-bottom: 25px;
}

.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid var(--border-color);
    padding: 12px 16px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: var(--input-bg);
    color: var(--text-primary);
}

[data-bs-theme="dark"] .form-control,
[data-bs-theme="dark"] .form-select {
    background: var(--input-bg);
    border-color: var(--border-color);
    color: var(--text-primary);
}

[data-bs-theme="dark"] .form-control::placeholder {
    color: var(--text-muted);
    opacity: 1;
}

[data-bs-theme="dark"] .form-control:focus,
[data-bs-theme="dark"] .form-select:focus {
    background: var(--input-bg);
    border-color: #5a9bf0;
    color: var(--text-primary);
    box-shadow: 0 0 0 0.2rem rgba(90, 155, 240, 0.25);
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-label {
    color: var(--text-secondary);
    font-weight: 600;
}

[data-bs-theme="dark"] .filters-card h5,
[data-bs-theme="dark"] .results-title { color: var(--text-primary); }
[data-bs-theme="dark"] .page-header .subtitle { color: rgba(255,255,255,0.9); }
[data-bs-theme="dark"] .form-label { color: var(--text-secondary); }
[data-bs-theme="dark"] .sms-card { border: 1px solid var(--border-color); }
[data-bs-theme="dark"] .pagination-modern .page-link { color: #bcd3ff; }
[data-bs-theme="dark"] .sms-content-display { color: var(--text-primary); }

.btn-filter {
    background: var(--primary-gradient);
    border: none;
    padding: 12px 30px;
    border-radius: 10px;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-reset {
    background: var(--text-muted);
    border: none;
    padding: 12px 30px;
    border-radius: 10px;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
}

.btn-reset:hover {
    background: var(--text-secondary);
    transform: translateY(-2px);
    color: white;
}

.results-header {
    display: grid;
    grid-template-columns: 1fr auto auto; /* Titre | quick filters | compteur */
    align-items: center;
    gap: 16px;
    background: var(--card-bg);
    padding: 18px 22px;
    border-radius: var(--border-radius);
    margin-bottom: 18px;
    box-shadow: var(--card-shadow);
}

[data-bs-theme="dark"] .results-header {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
}

.results-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.results-count {
    background: var(--success-gradient);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Quick filters (desktop) */
.quick-filters {
    display: inline-flex;
    gap: 8px;
}
.quick-filters .btn {
    border-radius: 999px;
    padding: 6px 14px;
}

/* Mode jour - Quick filters avec couleur visible */
.quick-filters .btn-outline-secondary {
    color: #495057 !important;
    border-color: #6c757d !important;
    background-color: transparent !important;
}
.quick-filters .btn-outline-secondary:hover {
    color: #ffffff !important;
    background-color: #6c757d !important;
}
.quick-filters .btn-outline-secondary.active {
    color: #ffffff !important;
    background-color: #667eea !important;
    border-color: #667eea !important;
}

/* Mode jour - Titres et textes visibles */
.results-title {
    color: #2c3e50 !important;
}
.page-header h1,
.page-header .subtitle {
    color: white !important;
}
.filters-card h5 {
    color: #2c3e50 !important;
}
.stat-label {
    color: #6c757d !important;
}
.stat-number {
    color: #2c3e50 !important;
}
.sms-date {
    color: #6c757d !important;
}
.sms-client {
    color: #2c3e50 !important;
}
.sms-device {
    color: #6c757d !important;
}

/* Mode sombre - Override pour les titres et textes */
[data-bs-theme="dark"] .results-title {
    color: var(--text-primary) !important;
}
[data-bs-theme="dark"] .filters-card h5 {
    color: var(--text-primary) !important;
}
[data-bs-theme="dark"] .stat-label {
    color: var(--text-secondary) !important;
}
[data-bs-theme="dark"] .stat-number {
    color: var(--text-primary) !important;
}
[data-bs-theme="dark"] .sms-date {
    color: var(--text-secondary) !important;
}
[data-bs-theme="dark"] .sms-client {
    color: var(--text-primary) !important;
}
[data-bs-theme="dark"] .sms-device {
    color: var(--text-secondary) !important;
}
[data-bs-theme="dark"] .quick-filters .btn-outline-secondary {
    color: #bcd3ff;
    border-color: #bcd3ff;
}
[data-bs-theme="dark"] .quick-filters .btn-outline-secondary.active {
    color: white;
    background-color: #5a9bf0;
    border-color: #5a9bf0;
}

/* Tableau desktop moderne */
.table-container {
    background: var(--card-bg);
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
    overflow: auto;
    -webkit-overflow-scrolling: touch;
}

.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.95rem;
}
.modern-table thead th {
    position: sticky;
    top: 0;
    background: var(--bg-secondary);
    color: var(--text-secondary);
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: .04em;
    border-bottom: 1px solid var(--border-color);
    padding: 12px 14px;
    z-index: 1;
}
.modern-table tbody td {
    padding: 12px 14px;
    color: var(--text-primary);
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
}
.modern-table tbody tr:hover { background: rgba(102,126,234,.06); }

/* Colonnes: largeur/nowrap utiles */
.col-date { white-space: nowrap; width: 140px; }
.col-client { min-width: 180px; }
.col-phone { white-space: nowrap; font-family: 'Courier New', monospace; width: 140px; }
.col-rep { white-space: nowrap; width: 110px; }
.col-device { max-width: 260px; }
.col-status { white-space: nowrap; width: 120px; }
.col-template { max-width: 180px; }
.col-actions { white-space: nowrap; width: 90px; text-align: right; }

.truncate { display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

[data-bs-theme="dark"] .table-container { background: var(--bg-secondary); }

.view-toggle.btn-group .btn {
    border-radius: 999px;
    font-weight: 600;
}

/* Bandeau de statistiques & contrôles (styles proches des autres pages) */
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 16px;
}
.stat-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    padding: 16px 20px;
    border-left: 4px solid transparent;
}

/* Couleurs subtiles pour les cartes stats - Mode jour */
.stat-card.stat-total {
    background: rgba(102, 126, 234, 0.1) !important;
    border-left-color: #667eea;
}
.stat-card.stat-total .stat-number {
    color: #667eea !important;
}

.stat-card.stat-sent {
    background: rgba(46, 204, 113, 0.1) !important;
    border-left-color: #2ecc71;
}
.stat-card.stat-sent .stat-number {
    color: #27ae60 !important;
}

.stat-card.stat-failed {
    background: rgba(231, 76, 60, 0.1) !important;
    border-left-color: #e74c3c;
}
.stat-card.stat-failed .stat-number {
    color: #c0392b !important;
}

/* Mode sombre - Couleurs des cartes stats */
[data-bs-theme="dark"] .stat-card.stat-total {
    background: rgba(102, 126, 234, 0.15) !important;
}
[data-bs-theme="dark"] .stat-card.stat-total .stat-number {
    color: #8fa4f0 !important;
}

[data-bs-theme="dark"] .stat-card.stat-sent {
    background: rgba(46, 204, 113, 0.15) !important;
}
[data-bs-theme="dark"] .stat-card.stat-sent .stat-number {
    color: #58d68d !important;
}

[data-bs-theme="dark"] .stat-card.stat-failed {
    background: rgba(231, 76, 60, 0.15) !important;
}
[data-bs-theme="dark"] .stat-card.stat-failed .stat-number {
    color: #ec7063 !important;
}

[data-bs-theme="dark"] .stat-card { border: 1px solid var(--border-color); }
.stat-number { font-size: 1.6rem; font-weight: 800; }
.stat-label { color: var(--text-secondary); font-weight: 600; }
[data-bs-theme="dark"] .stat-number { color: var(--text-primary); }

.controls-section { margin-bottom: 16px; }
.controls-grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
.search-container { position: relative; }
.search-input { width: 100%; padding: 12px 16px; border-radius: 10px; border: 2px solid var(--border-color); background: var(--input-bg); }

/* Grille desktop des cartes */
.sms-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

@media (max-width: 1400px) { .sms-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 992px)  { .sms-grid { grid-template-columns: 1fr; } }

.sms-card {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 18px;
    box-shadow: var(--card-shadow);
    border: 1px solid transparent;
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    position: relative;
    overflow: hidden;
}

[data-bs-theme="dark"] .sms-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
}

.sms-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.sms-card:hover::before {
    transform: scaleX(1);
}

.sms-card:hover { transform: translateY(-3px); box-shadow: var(--card-hover-shadow); border-color: var(--border-color); }

.sms-header {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 12px;
    margin-bottom: 12px;
}

.sms-info {
    flex: 1;
}

.sms-date {
    font-size: 0.9rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.sms-client {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 5px 0;
}

.sms-phone {
    font-size: 0.9rem;
    color: #007bff;
    font-family: 'Courier New', monospace;
    font-weight: 500;
}

[data-bs-theme="dark"] .sms-phone {
    color: #64b5f6;
}

.sms-badges {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.badge-custom {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.badge-repair {
    background: var(--info-gradient);
    color: #2c3e50;
}

[data-bs-theme="dark"] .badge-repair {
    color: white;
}

.badge-status {
    background: var(--success-gradient);
    color: white;
}

.badge-template {
    background: var(--warning-gradient);
    color: white;
}

.sms-body {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 20px;
    align-items: center;
}

.sms-device {
    font-size: 0.95rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.view-sms-btn {
    background: var(--primary-gradient);
    border: none;
    padding: 8px 16px;
    border-radius: 999px;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.view-sms-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
}

[data-bs-theme="dark"] .empty-state {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
}

.empty-state i {
    font-size: 4rem;
    color: var(--text-muted);
    margin-bottom: 20px;
}

.empty-state h3 {
    color: var(--text-secondary);
    margin-bottom: 10px;
}

.empty-state p {
    color: var(--text-muted);
    font-size: 1.1rem;
}

.pagination-modern {
    display: flex;
    justify-content: center;
    margin-top: 30px;
}

.pagination-modern .pagination {
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    overflow: hidden;
}

.pagination-modern .page-link {
    border: none;
    padding: 12px 16px;
    font-weight: 600;
    color: #667eea;
    background: var(--card-bg);
    transition: all 0.3s ease;
}

[data-bs-theme="dark"] .pagination-modern .page-link {
    background: var(--bg-secondary);
    color: #bcd3ff;
    border: 1px solid var(--border-color);
}

.pagination-modern .page-link:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
}

[data-bs-theme="dark"] .pagination-modern .page-link:hover {
    background: #5a9bf0;
    color: white;
}

.pagination-modern .page-item.active .page-link {
    background: var(--primary-gradient);
    color: white;
}

.modal-modern .modal-content {
    border-radius: var(--border-radius);
    border: none;
    box-shadow: var(--card-hover-shadow);
    background: var(--card-bg);
}

[data-bs-theme="dark"] .modal-modern .modal-content {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
}

.modal-modern .modal-header {
    background: var(--primary-gradient);
    color: white;
    border-bottom: none;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.modal-modern .modal-body {
    padding: 30px;
    background: var(--card-bg);
    color: var(--text-primary);
}

[data-bs-theme="dark"] .modal-modern .modal-body {
    background: var(--bg-secondary);
}

.sms-content-display {
    background: var(--bg-tertiary);
    border-radius: 10px;
    padding: 20px;
    border-left: 4px solid #667eea;
    font-size: 1.1rem;
    line-height: 1.6;
    color: var(--text-primary);
}

[data-bs-theme="dark"] .sms-content-display {
    background: var(--bg-primary);
    border-left-color: #4a90e2;
}

/* Améliorations pour les boutons dans le header */
.page-header .btn {
    border: 2px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.page-header .btn:hover {
    border-color: rgba(255, 255, 255, 0.6);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

/* Styles pour les options dans les select en mode sombre */
[data-bs-theme="dark"] .form-select option {
    background: var(--input-bg);
    color: var(--text-primary);
}

[data-bs-theme="dark"] .form-select optgroup {
    background: var(--input-bg);
    color: var(--text-secondary);
    font-weight: bold;
}

/* Scrollbar personnalisée pour le mode sombre */
[data-bs-theme="dark"] ::-webkit-scrollbar {
    width: 8px;
}

[data-bs-theme="dark"] ::-webkit-scrollbar-track {
    background: var(--bg-primary);
}

[data-bs-theme="dark"] ::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 4px;
}

[data-bs-theme="dark"] ::-webkit-scrollbar-thumb:hover {
    background: var(--text-muted);
}

/* Styles pour les alertes et messages en mode sombre */
[data-bs-theme="dark"] .alert {
    background: var(--bg-secondary);
    border-color: var(--border-color);
    color: var(--text-primary);
}

[data-bs-theme="dark"] .alert-success {
    background: rgba(46, 204, 113, 0.1);
    border-color: #27ae60;
    color: #2ecc71;
}

[data-bs-theme="dark"] .alert-danger {
    background: rgba(231, 76, 60, 0.1);
    border-color: #c0392b;
    color: #e74c3c;
}

[data-bs-theme="dark"] .alert-warning {
    background: rgba(243, 156, 18, 0.1);
    border-color: #e67e22;
    color: #f39c12;
}

[data-bs-theme="dark"] .alert-info {
    background: rgba(52, 152, 219, 0.1);
    border-color: #2980b9;
    color: #3498db;
}

@media (max-width: 768px) {
    .sms-historique-container {
        padding: 10px;
    }
    
    .page-header {
        padding: 30px 20px;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .filters-card {
        padding: 20px;
    }
    
    .sms-card {
        padding: 20px;
    }
    
    .sms-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .sms-body {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .sms-badges {
        justify-content: flex-start;
    }
    
    .results-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}

/* Animations fluides */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.sms-card {
    animation: fadeIn 0.5s ease-out;
}

.sms-card:nth-child(even) {
    animation: slideInLeft 0.5s ease-out;
    animation-delay: 0.1s;
}

.sms-card:nth-child(odd) {
    animation-delay: 0.05s;
}

/* Animation pour l'apparition des éléments */
.filters-card,
.results-header,
.page-header {
    animation: fadeIn 0.6s ease-out;
}

.filters-card {
    animation-delay: 0.2s;
}

.results-header {
    animation-delay: 0.4s;
}

/* Effet de brillance pour les cartes */
.sms-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.7s ease;
    pointer-events: none;
}

.sms-card:hover::after {
    left: 100%;
}

[data-bs-theme="dark"] .sms-card::after {
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
}

/* ========================================
   FIX NAVBAR & ANIMATION SERVO
   ======================================== */
@media (min-width: 992px) {
    /* Masquer le dock mobile sur desktop */
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    
    /* S'assurer que la navbar desktop est visible */
    #desktop-navbar, nav#desktop-navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 1030 !important;
        width: 100% !important;
        height: 60px !important;
    }
    
    /* Container fluid de la navbar */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.5rem 1rem !important;
        min-height: 60px !important;
    }
    
    /* Logo SERVO - CENTRÉ horizontalement ET verticalement */
    .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 0 !important;
        transform: translateX(-50%) !important;
        z-index: 99999 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: auto !important;
        height: 100% !important;
        min-width: 200px !important;
        padding-bottom: 5px !important; /* Ajustement fin pour le centrage visuel */
        pointer-events: auto !important;
    }
    
    /* S'assurer que le loader SERVO est visible */
    .servo-logo-container .loader {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Animations SVG pour toutes les lettres SERVO */
    .servo-logo-container .dash {
        animation: dashArray 2s ease-in-out infinite, dashOffset 2s linear infinite !important;
    }
    
    .servo-logo-container .spin {
        animation: spinDashArray 2s ease-in-out infinite, spin 8s ease-in-out infinite, dashOffset 2s linear infinite !important;
        transform-origin: center;
    }
    
    /* Keyframes pour l'animation .dash (S, E, R, V) */
    @keyframes dashArray {
        0% { stroke-dasharray: 0 1 359 0; }
        50% { stroke-dasharray: 0 359 1 0; }
        100% { stroke-dasharray: 359 1 0 0; }
    }
    
    /* Keyframes pour l'animation .spin (O) */
    @keyframes spinDashArray {
        0% { stroke-dasharray: 270 90; }
        50% { stroke-dasharray: 0 360; }
        100% { stroke-dasharray: 250 90; }
    }
    
    /* Animation du trait qui se dessine */
    @keyframes dashOffset {
        0% { stroke-dashoffset: 385; }
        100% { stroke-dashoffset: 5; }
    }
    
    /* Animation de rotation pour le O */
    @keyframes spin {
        0% { rotate: 0deg; }
        12.5%, 25% { rotate: 270deg; }
        37.5%, 50% { rotate: 540deg; }
        62.5%, 75% { rotate: 810deg; }
        87.5%, 100% { rotate: 1080deg; }
    }
    
    /* S'assurer que tous les SVG sont visibles */
    .servo-logo-container svg,
    .servo-logo-container path {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Padding pour le body */
    body {
        padding-top: 80px !important;
    }
}

/* ====================================================================
   ANIMATED BACKGROUND SYSTEM (harmonisé avec taches_moderne.php)
==================================================================== */
/* Mode Jour - Fond animé bleu/violet */
html body {
    background: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff) !important;
    background-size: 300% 300% !important;
    animation: gradientFlowDay 20s ease infinite !important;
}

@keyframes gradientFlowDay {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Mode Nuit - Transparent pour voir #animated-bg */
html body.night-mode,
html body.dark-mode,
html[data-bs-theme="dark"] body,
body[data-bs-theme="dark"] {
    background: transparent !important;
    animation: none !important;
}

/* Conteneurs transparents */
.sms-historique-container,
.container,
.container-fluid {
    background: transparent !important;
}

/* Cartes avec fond blanc semi-opaque en mode jour */
html body .filters-card,
html body .sms-card,
html body .stat-card,
html body .table-container,
html body .page-header,
html body .results-header,
html body .empty-state {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(10px) !important;
}

/* Mode Nuit - Cartes avec fond sombre */
html body.night-mode .filters-card,
html body.night-mode .sms-card,
html body.night-mode .stat-card,
html body.night-mode .table-container,
html body.night-mode .results-header,
html body.night-mode .empty-state,
html[data-bs-theme="dark"] .filters-card,
html[data-bs-theme="dark"] .sms-card,
html[data-bs-theme="dark"] .stat-card,
html[data-bs-theme="dark"] .table-container,
html[data-bs-theme="dark"] .results-header,
html[data-bs-theme="dark"] .empty-state {
    background: rgba(30, 41, 59, 0.95) !important;
}

/* #animated-bg pour le mode nuit */
#animated-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: -1;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.5s ease;
    background-color: #0f172a;
}

body.night-mode #animated-bg,
body.dark-mode #animated-bg,
[data-bs-theme="dark"] #animated-bg {
    opacity: 1;
}

#animated-bg::before,
#animated-bg::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

#animated-bg::before {
    background: radial-gradient(circle at 20% 30%, rgba(76, 29, 149, 0.4), transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(59, 130, 246, 0.3), transparent 50%);
    animation: moveBackground1 25s ease-in-out infinite alternate;
}

#animated-bg::after {
    background: radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.3), transparent 45%),
                radial-gradient(circle at 10% 80%, rgba(236, 72, 153, 0.25), transparent 45%);
    animation: moveBackground2 30s ease-in-out infinite alternate-reverse;
}

@keyframes moveBackground1 {
    0% { transform: scale(1) translate(0, 0); }
    50% { transform: scale(1.1) translate(30px, -20px); }
    100% { transform: scale(1) translate(-20px, 20px); }
}

@keyframes moveBackground2 {
    0% { transform: scale(1) translate(0, 0); }
    50% { transform: scale(1.15) translate(-30px, 25px); }
    100% { transform: scale(1) translate(20px, -20px); }
}

/* ====================================================================
   MODAL SMSCONTENTMODAL - MODE NUIT & POSITION
==================================================================== */
#smsContentModal {
    z-index: 1050 !important;
}

#smsContentModal .modal-dialog {
    margin-top: 80px !important; /* Espace pour la navbar */
}

/* Mode Nuit - Modal smsContentModal */
body.night-mode #smsContentModal .modal-content,
body.dark-mode #smsContentModal .modal-content,
[data-bs-theme="dark"] #smsContentModal .modal-content {
    background: #0f172a !important;
    border: 1px solid #334155 !important;
    color: #f1f5f9 !important;
}

body.night-mode #smsContentModal .modal-header,
body.dark-mode #smsContentModal .modal-header,
[data-bs-theme="dark"] #smsContentModal .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-bottom: 1px solid #334155 !important;
    color: white !important;
}

body.night-mode #smsContentModal .modal-body,
body.dark-mode #smsContentModal .modal-body,
[data-bs-theme="dark"] #smsContentModal .modal-body {
    background: #0f172a !important;
    color: #f1f5f9 !important;
}

body.night-mode #smsContentModal .modal-footer,
body.dark-mode #smsContentModal .modal-footer,
[data-bs-theme="dark"] #smsContentModal .modal-footer {
    background: #1e293b !important;
    border-top: 1px solid #334155 !important;
}

body.night-mode #smsContentModal .text-muted,
body.dark-mode #smsContentModal .text-muted,
[data-bs-theme="dark"] #smsContentModal .text-muted {
    color: #94a3b8 !important;
}

body.night-mode #smsContentModal .sms-content-display,
body.dark-mode #smsContentModal .sms-content-display,
[data-bs-theme="dark"] #smsContentModal .sms-content-display {
    background: #1e293b !important;
    border: 1px solid #334155 !important;
    color: #f1f5f9 !important;
}
</style>

<!-- Animated Background for Night Mode -->
<div id="animated-bg"></div>

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <div class="loader-wrapper dark-loader">
        <div class="loader-circle"></div>
        <div class="loader-text">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
    <div class="loader-wrapper light-loader">
        <div class="loader-circle-light"></div>
        <div class="loader-text-light">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
</div>

<div class="sms-historique-container" id="mainContent" style="display: none;">
    <!-- En-tête moderne -->
    <div class="page-header">
        <h1><i class="fas fa-history me-3"></i>Historique des SMS</h1>
        <p class="subtitle">Consultez et analysez tous les SMS envoyés depuis votre système</p>
        <div class="mt-4">
            <a href="index.php?page=campagne_sms" class="btn btn-light me-3">
                <i class="fas fa-paper-plane me-2"></i>Campagnes SMS
            </a>
            <?php if ($is_admin): ?>

            <a href="index.php?page=sms_templates" class="btn btn-outline-light">
                <i class="fas fa-cog me-2"></i>Gérer les modèles
            </a>
            <?php endif; ?>

        </div>
    </div>
    
    <!-- Filtres modernes -->
    <div class="filters-card">
        <!-- Bandeau stats -->
        <div class="stats-row">
            <div class="stat-card stat-total">
                <div class="stat-number"><?php echo number_format($stats_total); ?></div>

                <div class="stat-label">Total SMS</div>
            </div>
            <div class="stat-card stat-sent">
                <div class="stat-number"><?php echo number_format($stats_sent); ?></div>

                <div class="stat-label">Envoyés</div>
            </div>
            <div class="stat-card stat-failed">
                <div class="stat-number"><?php echo number_format($stats_failed); ?></div>

                <div class="stat-label">Échecs</div>
            </div>
        </div>
        
        <h5><i class="fas fa-filter me-2"></i>Filtres de recherche</h5>
        <form method="get">
            <input type="hidden" name="page" value="sms_historique">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-6">
                    <label for="recherche" class="form-label"><i class="fas fa-search me-1"></i>Recherche</label>
                    <input type="text" class="form-control" id="recherche" name="recherche"
                           value="<?php echo htmlspecialchars($recherche_globale ?? ''); ?>" 
                           placeholder="ID, téléphone, nom, appareil, contenu...">
                </div>
                
                <div class="col-6 col-lg-3">
                    <label for="statut_id" class="form-label"><i class="fas fa-tag me-1"></i>Statut</label>
                    <select class="form-select" id="statut_id" name="statut_id">
                        <option value="">Tous</option>
                        <option value="sent" <?php echo $statut_filter === 'sent' ? 'selected' : ''; ?>>Envoyé</option>
                        <option value="failed" <?php echo $statut_filter === 'failed' ? 'selected' : ''; ?>>Échec</option>
                    </select>
                </div>
                
                <div class="col-6 col-lg-3 text-end d-flex justify-content-end align-items-center">
                    <?php if ($stats_failed > 0): ?>
                    <button type="button" class="btn btn-warning me-2 fw-bold d-flex align-items-center" id="btnResendFailed" title="Renvoyer les échecs" style="color: #1f2937;">
                        <i class="fas fa-sync-alt me-2"></i>Renvoyer (<?php echo number_format($stats_failed); ?>)
                    </button>
                    <?php endif; ?>
                    <a href="index.php?page=sms_historique" class="btn btn-reset me-2" title="Réinitialiser">
                        <i class="fas fa-times"></i>
                    </a>
                    <button type="submit" class="btn btn-filter" title="Filtrer">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- En-tête des résultats -->
    <div class="results-header">
        <h2 class="results-title mb-0">
            <i class="fas fa-envelope me-2"></i>SMS envoyés
        </h2>
        <div class="quick-filters">
            <?php 

                // Construit URLs rapides
                $baseUrl = 'index.php?page=sms_historique';
                $qs = $_GET; unset($qs['statut_id']);
                $urlTous   = $baseUrl . (empty($qs) ? '' : '&' . http_build_query($qs));
                $qsSent    = $_GET; $qsSent['statut_id'] = 'sent';
                $urlSent   = $baseUrl . '&' . http_build_query($qsSent);
                $qsFailed  = $_GET; $qsFailed['statut_id'] = 'failed';
                $urlFailed = $baseUrl . '&' . http_build_query($qsFailed);
            ?>
            <a href="<?php echo htmlspecialchars($urlTous); ?>" class="btn btn-outline-secondary <?php echo empty($statut_filter) ? 'active' : ''; ?>">Tous</a>

            <a href="<?php echo htmlspecialchars($urlSent); ?>" class="btn btn-outline-success <?php echo $statut_filter==='sent' ? 'active' : ''; ?>">Envoyé</a>

            <a href="<?php echo htmlspecialchars($urlFailed); ?>" class="btn btn-outline-danger <?php echo $statut_filter==='failed' ? 'active' : ''; ?>">Échec</a>

        </div>
        <div class="results-count">
            <?php echo $total_items; ?> résultat<?php echo $total_items > 1 ? 's' : ''; ?>

        </div>
    </div>
    
    <!-- Résultats -->
    <?php if (empty($historique)): ?>

    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Aucun SMS dans l'historique</h3>
        <p>Aucun SMS correspondant à vos critères de recherche n'a été trouvé.</p>
    </div>
    <?php else: ?>

    <?php if ($view_mode === 'hybrid'): ?>

        <!-- Table desktop -->
        <div class="table-container d-none d-lg-block">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th class="col-date">Date</th>
                        <th class="col-client">Client</th>
                        <th class="col-phone">Téléphone</th>
                        <th class="col-rep">Réparation</th>
                        <th class="col-device">Appareil</th>
                        <th class="col-status">Statut</th>
                        <th class="col-actions">Contenu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historique as $sms): ?>

                    <tr>
                        <td class="col-date"><?php echo date('d/m/Y H:i', strtotime($sms['date_envoi'])); ?></td>

                        <td class="col-client"><span class="truncate"><?php echo htmlspecialchars(trim($sms['client_nom'] . ' ' . $sms['client_prenom'])); ?></span></td>

                        <td class="col-phone"><?php echo htmlspecialchars($sms['client_telephone']); ?></td>

                        <td class="col-rep">#<?php echo (int)$sms['repair_id']; ?></td>

                        <td class="col-device"><span class="truncate"><?php echo htmlspecialchars(trim($sms['marque'] . ' ' . $sms['modele'])); ?></span></td>

                        <td class="col-status"><?php echo htmlspecialchars($sms['statut_nom']); ?></td>

                        <td class="col-actions">
                            <button type="button" class="btn btn-sm btn-outline-primary view-sms"
                                data-bs-toggle="modal"
                                data-bs-target="#smsContentModal"
                                data-content="<?php echo htmlspecialchars($sms['message']); ?>"

                                data-date="<?php echo date('d/m/Y H:i', strtotime($sms['date_envoi'])); ?>"

                                data-client="<?php echo htmlspecialchars(trim($sms['client_nom'] . ' ' . $sms['client_prenom'])); ?>">

                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

        <!-- Cartes mobile/tablette -->
        <div class="sms-grid d-lg-none">
        <?php foreach ($historique as $sms): ?>

        <div class="sms-card">
            <div class="sms-header">
                <div class="sms-info">
                    <div class="sms-date">
                        <i class="fas fa-clock me-1"></i>
                        <?php echo date('d/m/Y à H:i', strtotime($sms['date_envoi'])); ?>

                    </div>
                    <div class="sms-client">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($sms['client_nom'] . ' ' . $sms['client_prenom']); ?>

                    </div>
                    <div class="sms-phone">
                        <i class="fas fa-phone me-1"></i>
                        <?php echo htmlspecialchars($sms['client_telephone']); ?>

                    </div>
                </div>
                <div class="sms-badges">
                    <div class="badge-custom badge-repair">
                        <i class="fas fa-tools"></i>
                        #<?php echo $sms['repair_id']; ?>

                    </div>
                    <?php if ($sms['statut_nom']): ?>

                    <div class="badge-custom badge-status">
                        <i class="fas fa-check"></i>
                        <?php echo htmlspecialchars($sms['statut_nom']); ?>

                    </div>
                    <?php endif; ?>

                    <div class="badge-custom badge-template">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($sms['template_nom']); ?>

                    </div>
                </div>
            </div>
            <div class="sms-body">
                <div class="sms-device">
                    <i class="fas fa-mobile-alt me-2"></i>
                    <?php echo htmlspecialchars($sms['marque'] . ' ' . $sms['modele']); ?>

                </div>
                <button type="button" class="view-sms-btn view-sms" 
                        data-bs-toggle="modal" 
                        data-bs-target="#smsContentModal"
                        data-content="<?php echo htmlspecialchars($sms['message']); ?>"

                        data-date="<?php echo date('d/m/Y à H:i', strtotime($sms['date_envoi'])); ?>"

                        data-client="<?php echo htmlspecialchars($sms['client_nom'] . ' ' . $sms['client_prenom']); ?>">

                    <i class="fas fa-eye"></i>
                    Voir le SMS
                </button>
            </div>
        </div>
        <?php endforeach; ?>

        </div>
    <?php endif; ?>

    <?php endif; ?>

    
    <!-- Pagination moderne -->
    <?php if ($total_pages > 1): ?>

    <div class="pagination-modern">
        <nav aria-label="Pagination">
            <ul class="pagination">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">

                    <a class="page-link" href="javascript:void(0);" onclick="changePage(<?php echo $page - 1; ?>)" aria-label="Précédent">

                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>

                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">

                    <a class="page-link" href="javascript:void(0);" onclick="changePage(<?php echo $i; ?>)"><?php echo $i; ?></a>

                </li>
                <?php endfor; ?>

                
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">

                    <a class="page-link" href="javascript:void(0);" onclick="changePage(<?php echo $page + 1; ?>)" aria-label="Suivant">

                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>

</div>

<!-- Modal moderne pour afficher le contenu d'un SMS -->
<div class="modal fade modal-modern" id="smsContentModal" tabindex="-1" aria-labelledby="smsContentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="smsContentModalLabel">
                    <i class="fas fa-sms me-2"></i>Contenu du SMS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <strong>Date d'envoi:</strong> <span id="smsDate"></span>
                            </small>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                <strong>Client:</strong> <span id="smsClient"></span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="sms-content-display" id="smsContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuration du modal d'affichage du contenu SMS
    const smsContentModal = document.getElementById('smsContentModal');
    smsContentModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const content = button.getAttribute('data-content');
        const date = button.getAttribute('data-date');
        const client = button.getAttribute('data-client');
        
        document.getElementById('smsContent').textContent = content;
        document.getElementById('smsDate').textContent = date;
        document.getElementById('smsClient').textContent = client;
    });
    
    // Validation du formulaire de filtres
    const filterForm = document.querySelector('form');
    filterForm.addEventListener('submit', function(event) {
        // Supprimer les champs vides pour éviter les paramètres inutiles dans l'URL
        const formElements = Array.from(this.elements);
        formElements.forEach(element => {
            if (element.value === '' && element.name !== 'page') {
                element.disabled = true;
            }
        });
    });
    
    // Animation des cartes au scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    document.querySelectorAll('.sms-card').forEach(card => {
        observer.observe(card);
    });
});

// Fonction pour ajouter des paramètres à l'URL actuelle
function changePage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page_num', page);
    window.location.href = url.toString();
}
</script>

<?php if ($is_admin): ?>

<div class="text-center mt-4 mb-4">
    <div class="card" style="background: var(--info-gradient); border: none; border-radius: var(--border-radius);">
        <div class="card-body">
            <h6 class="card-title text-white mb-2">
                <i class="fas fa-info-circle me-2"></i>Administration
            </h6>
            <p class="card-text text-white mb-0">
                Pour configurer ou modifier des modèles de SMS, accédez à la 
                <a href="index.php?page=sms_templates" class="text-white fw-bold text-decoration-underline">
                    gestion des modèles de SMS
                </a>.
            </p>
        </div>
    </div>
</div>
<?php endif; ?>


</div> <!-- Fermeture de mainContent -->

<style>
.loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000);
}

.loader-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 180px;
  height: 180px;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: white;
  border-radius: 50%;
  background-color: transparent;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

.loader-circle {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined 2.3s linear infinite;
  z-index: 0;
}
@keyframes loader-combined {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #38bdf8 inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #0099ff inset,
      0 12px 18px 0 #38bdf8 inset,
      0 36px 36px 0 #005dff inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #60a5fa inset,
      0 12px 6px 0 #0284c7 inset,
      0 24px 36px 0 #005dff inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #0ea5e9 inset,
      0 36px 36px 0 #2563eb inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #4dc8fd inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
}

.loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim 2.4s infinite;
  z-index: 1;
  border-radius: 50ch;
  border: none;
}

.loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #f8fcff 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

#mainContent.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
  display: flex;
}

.loader-circle-light {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined-light 2.3s linear infinite;
  z-index: 0;
}

@keyframes loader-combined-light {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #3b82f6 inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #2563eb inset,
      0 12px 18px 0 #1e40af inset,
      0 36px 36px 0 #3b82f6 inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 6px 0 #1d4ed8 inset,
      0 24px 36px 0 #2563eb inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
}

.loader-text-light {
  display: flex;
  gap: 2px;
  z-index: 1;
}

.loader-text-light .loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim-light 2.4s infinite;
  z-index: 1;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: #1f2937;
  border-radius: 50ch;
  border: none;
}

.loader-text-light .loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-text-light .loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-text-light .loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-text-light .loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-text-light .loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim-light {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #1e40af 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

body,
body.dark-mode,
body.light-mode,
html {
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

.sms-historique-container,
.sms-historique-container * {
  background: transparent !important;
}

.sms-card,
.modal-content,
.page-header {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .sms-card,
.dark-mode .modal-content,
.dark-mode .page-header {
  background: rgba(30, 41, 59, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}
</style>

<!-- Mini Games Lib -->
<script src="assets/js/mini_games.js"></script>

<script>
// Fonction de masquage du loader
function hideLoader() {
    try {
        const loader = document.getElementById('pageLoader');
        const mainContent = document.getElementById('mainContent');
        
        if (loader && mainContent) {
            loader.classList.add('fade-out');
            setTimeout(function() {
                loader.classList.add('hidden');
                loader.style.display = 'none';
                mainContent.style.display = 'block';
                mainContent.classList.add('fade-in');
            }, 500);
        }
    } catch (e) {
        console.error('Erreur loader:', e);
        // Fallback en cas d'erreur
        const loader = document.getElementById('pageLoader');
        const mainContent = document.getElementById('mainContent');
        if (loader) loader.style.display = 'none';
        if (mainContent) mainContent.style.display = 'block';
    }
}

// Exécution normale après DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(hideLoader, 300);
});

// Timeout de sécurité
setTimeout(function() {
    const loader = document.getElementById('pageLoader');
    if (loader && loader.style.display !== 'none' && !loader.classList.contains('hidden')) {
        console.warn('⚠️ Loader forcé à se masquer après timeout de sécurité');
        hideLoader();
    }
}, 3000);
</script>

<!-- Modal de Renvoi des SMS en Echec -->
<div class="modal fade" id="resendFailedModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-warning text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-gamepad me-2"></i>Zone d'attente ludique</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="btnCloseResendModal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- STEP 1 : CONFIRMATION -->
                <div id="resendStep1" class="p-5 text-center">
                    <div class="mb-4">
                        <span class="fa-stack fa-3x">
                          <i class="fas fa-circle fa-stack-2x text-warning opacity-25"></i>
                          <i class="fas fa-rocket fa-stack-1x text-warning"></i>
                        </span>
                    </div>
                    
                    <h3 class="fw-bold mb-3">Envoi Progressif Intelligent</h3>
                    <p class="lead mb-4">
                        Il y a <span id="failedCountBadge" class="badge bg-danger rounded-pill">0</span> SMS en attente.
                    </p>
                    
                    <div class="card bg-light border-0 mb-4 text-start mx-auto shadow-sm" style="max-width: 500px;">
                        <div class="card-body">
                            <h6 class="fw-bold text-dark"><i class="fas fa-info-circle me-2"></i>Pourquoi est-ce lent ?</h6>
                            <p class="small text-muted mb-0">
                                Pour garantir une délivrabilité maximale (100%), nous envoyons les messages <strong>1 par 1</strong> avec une pause de sécurité de <strong>10 secondes</strong>. Cela évite le blocage par les opérateurs.
                            </p>
                        </div>
                    </div>

                    <button type="button" class="btn btn-warning btn-lg px-5 py-3 text-white fw-bold shadow-sm rounded-pill hover-scale" id="btnConfirmResend">
                        <i class="fas fa-play me-2"></i>Lancer la réparation
                    </button>
                    
                    <p class="text-muted mt-3 small">
                        Pendant l'attente, nous vous proposons quelques jeux pour passer le temps ! 🎮
                    </p>
                </div>

                <!-- STEP 2 : GAMES & PROCESS -->
                <div id="resendStep2" style="display:none; height: 600px; display: flex; flex-direction: column;">
                    
                    <!-- Game Header -->
                    <div class="bg-dark text-white p-2 text-center">
                        <span class="small text-warning fw-bold"><i class="fas fa-ghost me-2"></i>PACMAN - Patientez en jouant</span>
                    </div>

                    <!-- Game Canvas Area -->
                    <div class="game-area position-relative flex-grow-1 bg-black" style="overflow: hidden;">
                        <canvas id="gameCanvas" style="width: 100%; height: 100%; display: block;"></canvas>
                    </div>

                    <!-- Status Footer (Progress & Discreet Logs) -->
                    <div class="process-footer bg-light border-top p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-primary"><i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...</strong>
                            <span class="badge bg-secondary rounded-pill" id="statusLine">Initialisation...</span>
                        </div>
                        
                        <div class="progress" style="height: 10px;">
                            <div id="resendProgressBar" class="progress-bar bg-warning" role="progressbar" style="width: 0%"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-2 small text-muted">
                            <span>Traités: <strong id="statProcessed" class="text-dark">0</strong>/<span id="statTotal">0</span></span>
                            <span class="text-success">Succès: <strong id="statSent">0</strong></span>
                            <span class="text-secondary">Ignorés: <strong id="statSkipped">0</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-warning { background: linear-gradient(135deg, #fce38a 0%, #f38181 100%); }
.btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }
.hover-scale { transition: transform 0.2s; }
.hover-scale:hover { transform: scale(1.05); }
.modal-lg { max-width: 900px; }
canvas { outline: none; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnResendFailed = document.getElementById('btnResendFailed');
    if (!btnResendFailed) return; 
    
    const resendModal = new bootstrap.Modal(document.getElementById('resendFailedModal'));
    const btnConfirmResend = document.getElementById('btnConfirmResend');
    const statusLine = document.getElementById('statusLine');
    
    let isProcessing = false;
    let totalFailed = 0;
    let processedCount = 0;
    
    // Game Manager
    let gameManager = null;

    // 1. Open Modal & Fetch Count
    btnResendFailed.addEventListener('click', function() {
        // Reset UI
        document.getElementById('resendStep1').style.display = 'block';
        document.getElementById('resendStep2').style.display = 'none'; // Keep hidden initially
        
        // Fetch count
        fetch('ajax/resend_failed_sms.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=count'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                totalFailed = parseInt(data.total_failed);
                document.getElementById('failedCountBadge').innerText = totalFailed;
                document.getElementById('statTotal').innerText = totalFailed;
                
                if (totalFailed === 0) {
                    btnConfirmResend.disabled = true;
                    btnConfirmResend.innerHTML = "Tout est propre !";
                } else {
                    btnConfirmResend.disabled = false;
                    btnConfirmResend.innerHTML = '<i class="fas fa-play me-2"></i>Lancer la réparation';
                }
                resendModal.show();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(err => alert('Erreur réseau'));
    });

    // 2. Start Games & Process
    btnConfirmResend.addEventListener('click', function() {
        document.getElementById('resendStep1').style.display = 'none';
        document.getElementById('resendStep2').style.display = 'flex'; // Enable flex layout
        
        document.getElementById('btnCloseResendModal').disabled = true;
        
        // Init Game Manager
        if(!gameManager) gameManager = new MiniGameManager('gameCanvas');
        gameManager.resize();
        gameManager.start('pacman'); // Default game & ONLY game

        processedCount = 0;
        document.getElementById('statProcessed').innerText = '0';
        document.getElementById('statSent').innerText = '0';
        document.getElementById('statSkipped').innerText = '0';
        updateProgress(0);
        
        isProcessing = true;
        statusLine.innerText = "Démarrage...";
        processNextItem();
    });

    function updateProgress(percent) {
        document.getElementById('resendProgressBar').style.width = percent + '%';
    }

    function updateStatus(msg, type='info') {
        statusLine.innerText = msg;
        statusLine.className = 'badge rounded-pill ' + 
            (type === 'success' ? 'bg-success' : 
             type === 'error' ? 'bg-danger' : 
             type === 'warning' ? 'bg-warning text-dark' : 'bg-secondary');
    }

    function processNextItem() {
        if (!isProcessing) return;

        fetch('ajax/resend_failed_sms.php', {
            method: 'POST',
            body: 'action=batch_resend&limit=1', // Limit 1 per 10s
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                updateStatus(data.message, 'error');
                finishProcess();
                return;
            }

            if (data.processed.length === 0) {
                updateStatus("Terminé !", 'success');
                finishProcess();
                return;
            }

            // Stats
            const sent = parseInt(document.getElementById('statSent').innerText) + data.stats.sent;
            const skipped = parseInt(document.getElementById('statSkipped').innerText) + data.stats.skipped;
            document.getElementById('statSent').innerText = sent;
            document.getElementById('statSkipped').innerText = skipped;
            
            processedCount += data.processed.length;
            document.getElementById('statProcessed').innerText = processedCount;

            // Log
            const item = data.processed[0];
            if(item) {
                if (item.status === 'sent') updateStatus(`Envoyé > ${item.phone}`, 'success');
                else if (item.status === 'skipped') updateStatus(`Déjà traité > ${item.phone}`, 'warning');
                else updateStatus(`Echec > ${item.phone}`, 'error');
            }

            // Progress
            let p = (processedCount / totalFailed) * 100;
            if (p > 100) p = 100;
            updateProgress(p);

            // Loop 10s
            // statusLine.innerText += " (Pause 10s)";
            setTimeout(processNextItem, 10000); 
        })
        .catch(err => {
            updateStatus("Erreur réseau (retry 10s)", 'error');
            setTimeout(processNextItem, 10000);
        });
    }

    function finishProcess() {
        isProcessing = false;
        updateProgress(100);
        document.getElementById('btnCloseResendModal').disabled = false;
        
        // Stop Game & Show Finished
        if(gameManager) {
            gameManager.stop();
            const canvas = document.getElementById('gameCanvas');
            const ctx = canvas.getContext('2d');
            
            // Overlay
            ctx.fillStyle = 'rgba(0,0,0,0.85)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.fillStyle = '#48bb78';
            ctx.font = 'bold 32px Arial';
            ctx.textAlign = 'center';
            ctx.fillText("TRAITEMENT TERMINÉ !", canvas.width/2, canvas.height/2);
            
            ctx.fillStyle = '#ffffff';
            ctx.font = '16px Arial';
            ctx.fillText("Vous pouvez fermer cette fenêtre.", canvas.width/2, canvas.height/2 + 40);
        }
        
        statusLine.innerText = "Traitement terminé !";
        statusLine.className = "badge bg-success rounded-pill";
        
        // Click to close
        document.getElementById('btnCloseResendModal').onclick = function() {
            location.reload();
        };
    }
    
    // Cleanup on modal hide
    document.getElementById('resendFailedModal').addEventListener('hidden.bs.modal', function () {
        if(gameManager) gameManager.stop();
        isProcessing = false;
    });
});
</script> 