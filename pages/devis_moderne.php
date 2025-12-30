<?php
// include_once 'includes/night-mode-system.php'; // Inclus globalement dans header.php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Initialisation de la session magasin pour iframe si nécessaire
function initializeShopSessionForIframe() {
    if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
        try {
            // Utiliser le système multi-magasin standard
            require_once __DIR__ . '/../config/database.php';
            $general_pdo = getMainDBConnection();
            
            if (!$general_pdo) {
                throw new Exception('Impossible de se connecter à la base principale');
            }
            
            $subdomain = detectShopFromSubdomain();
            if ($subdomain) {
                $stmt = $general_pdo->prepare("SELECT id, name, db_name FROM shops WHERE subdomain = ?");
                $stmt->execute([$subdomain]);
                $shop = $stmt->fetch();
                
                if ($shop) {
                    $_SESSION['shop_id'] = $shop['id'];
                    $_SESSION['shop_name'] = $shop['name'];
                    $_SESSION['current_database'] = $shop['db_name'];
                    error_log("Session magasin initialisée pour iframe devis: " . $shop['name'] . " (ID: " . $shop['id'] . ")");
                } else {
                    error_log("Aucun magasin trouvé pour le sous-domaine dans iframe devis: " . $subdomain);
                    // Afficher une page d'erreur personnalisée
                    echo '<!DOCTYPE html>
                    <html><head><title>Erreur</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">';
                    include_once 'includes/night-mode-system.php';
                    echo '</head><body class="bg-light">
                    <div class="container mt-5">
                        <div class="alert alert-danger">
                            <h4><i class="fas fa-exclamation-triangle"></i> Erreur de Configuration</h4>
                            <p>Impossible de déterminer le magasin pour le sous-domaine: <strong>' . htmlspecialchars($subdomain) . '</strong></p>
                            <p>Veuillez contacter l\'administrateur.</p>
                        </div>
                    </div>
                    </body></html>';
                    exit();
                }
            } else {
                error_log("Impossible de se connecter à la base principale dans iframe devis");
                echo '<!DOCTYPE html>
                <html><head><title>Erreur</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">';
                include_once 'includes/night-mode-system.php';
                echo '</head><body class="bg-light">
                <div class="container mt-5">
                    <div class="alert alert-danger">
                        <h4><i class="fas fa-database"></i> Erreur de Connexion</h4>
                        <p>Impossible de se connecter à la base de données.</p>
                        <p>Veuillez réessayer plus tard.</p>
                    </div>
                </div>
                </body></html>';
                exit();
            }
        } catch (Exception $e) {
            error_log("Erreur lors de l'initialisation de la session magasin pour iframe devis: " . $e->getMessage());
            echo '<!DOCTYPE html>
            <html><head><title>Erreur</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">';
            include_once 'includes/night-mode-system.php';
            echo '</head><body class="bg-light">
            <div class="container mt-5">
                <div class="alert alert-danger">
                    <h4><i class="fas fa-bug"></i> Erreur Système</h4>
                    <p>Une erreur s\'est produite lors de l\'initialisation.</p>
                    <p>Détails: ' . htmlspecialchars($e->getMessage()) . '</p>
                </div>
            </div>
            </body></html>';
            exit();
        }
    }
}

// Vérifier si on est dans un iframe et initialiser si nécessaire
if (isset($_GET['iframe']) && $_GET['iframe'] == '1') {
    initializeShopSessionForIframe();
}

// Fonctions utilitaires pour les devis
function getStatutLabel($statut) {
    switch ($statut) {
        case 'envoye':
            return 'En Attente';
        case 'accepte':
            return 'Accepté';
        case 'refuse':
            return 'Refusé';
        case 'brouillon':
            return 'Brouillon';
        case 'expire':
            return 'Expiré';
        default:
            return ucfirst($statut);
    }
}

function getStatutClass($statut) {
    switch ($statut) {
        case 'envoye':
            return 'envoye';
        case 'accepte':
            return 'accepte';
        case 'refuse':
            return 'refuse';
        case 'brouillon':
            return 'brouillon';
        case 'expire':
            return 'expire';
        default:
            return 'envoye';
    }
}

// Obtenir la connexion à la base de données du magasin de l'utilisateur
if (isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
    $shop_pdo = getShopDBConnectionById($_SESSION['shop_id']);
} else {
    $shop_pdo = getShopDBConnection();
}

// Récupérer et stocker l'ID du magasin actuel
$current_shop_id = $_SESSION['shop_id'] ?? null;
if (!$current_shop_id) {
    $current_shop_id = $_GET['shop_id'] ?? null;
    if ($current_shop_id) {
        $_SESSION['shop_id'] = $current_shop_id;
    }
}

// Vérifier que $shop_pdo est accessible et initialisé
if (!isset($shop_pdo) || $shop_pdo === null) {
    $total_en_attente = 0;
    $total_acceptes = 0;
    $total_refuses = 0;
    $total_expires = 0;
    $total_devis = 0;
    $devis = [];
} else {
    // Paramètres de filtrage
    $statut_filter = isset($_GET['statut']) ? cleanInput($_GET['statut']) : '';
    $statut_ids = isset($_GET['statut_ids']) ? cleanInput($_GET['statut_ids']) : 'envoye';
    $client_search = isset($_GET['client_search']) ? cleanInput($_GET['client_search']) : '';
    $date_debut = isset($_GET['date_debut']) ? cleanInput($_GET['date_debut']) : '';
    $date_fin = isset($_GET['date_fin']) ? cleanInput($_GET['date_fin']) : '';
    
    // Compter les devis par catégorie de statut
    try {
        // Total des devis (tous statuts)
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM devis");
        $total_devis = $stmt->fetch()['total'];

        // Devis en attente (envoyés et non expirés)
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM devis 
            WHERE statut = 'envoye' AND date_expiration > NOW()
        ");
        $total_en_attente = $stmt->fetch()['total'];

        // Devis acceptés
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM devis WHERE statut = 'accepte'");
        $total_acceptes = $stmt->fetch()['total'];

        // Devis refusés
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM devis WHERE statut = 'refuse'");
        $total_refuses = $stmt->fetch()['total'];

        // Devis expirés
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM devis 
            WHERE statut = 'envoye' AND date_expiration <= NOW()
        ");
        $total_expires = $stmt->fetch()['total'];

    } catch (Exception $e) {
        error_log("Erreur lors du comptage des devis: " . $e->getMessage());
        $total_en_attente = 0;
        $total_acceptes = 0;
        $total_refuses = 0;
        $total_expires = 0;
        $total_devis = 0;
    }

    // Construire la requête SQL pour récupérer les devis (même structure que devis.php)
    $where_conditions = [];
    $params = [];
    
    // Vérifier s'il y a une recherche active
    $is_searching = !empty($client_search);
    
    // Si pas de recherche, appliquer le filtre de statut par défaut
    if (!$is_searching) {
        // Par défaut, afficher les devis en attente si aucun statut spécifié
        if (empty($statut_ids)) {
            $statut_ids = 'envoye';
        }
        
        // Condition de base selon le filtre de statut
        if ($statut_ids === 'envoye') {
            $where_conditions[] = "d.statut = 'envoye' AND d.date_expiration > NOW()";
        } elseif ($statut_ids === 'accepte') {
            $where_conditions[] = "d.statut = 'accepte'";
        } elseif ($statut_ids === 'refuse') {
            $where_conditions[] = "d.statut = 'refuse'";
        } elseif ($statut_ids === 'expire') {
            $where_conditions[] = "d.statut = 'envoye' AND d.date_expiration <= NOW()";
        }
    }
    
    // Recherche client (dans TOUS les devis si recherche active)
    if (!empty($client_search)) {
        $where_conditions[] = "(c.nom LIKE ? OR c.prenom LIKE ? OR c.telephone LIKE ? OR r.type_appareil LIKE ? OR r.modele LIKE ?)";
        $search_term = "%$client_search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Filtre par date
    if (!empty($date_debut)) {
        $where_conditions[] = "d.date_creation >= ?";
        $params[] = $date_debut;
    }
    
    if (!empty($date_fin)) {
        $where_conditions[] = "d.date_creation <= ?";
        $params[] = $date_fin . ' 23:59:59';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "
        SELECT 
            d.*,
            c.nom as client_nom,
            c.prenom as client_prenom,
            c.telephone as client_telephone,
            c.email as client_email,
            r.description_probleme as reparation_probleme,
            r.type_appareil as reparation_appareil,
            r.modele as reparation_modele
        FROM devis d
        LEFT JOIN reparations r ON d.reparation_id = r.id
        LEFT JOIN clients c ON r.client_id = c.id
        $where_clause
        ORDER BY d.date_creation DESC
        LIMIT 50
    ";
    
    try {
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute($params);
        $devis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log du nombre de devis trouvés
        error_log("DEBUG devis_moderne.php: " . count($devis) . " devis trouvés avec la requête");
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des devis : " . $e->getMessage());
        $devis = [];
    }
}
?>

<style>
/* FIX NAVBAR - Obligatoire pour affichage correct */
/* Masquer dock mobile sur desktop */
@media (min-width: 992px) {
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    /* Forcer navbar desktop visible */
    #desktop-navbar, nav#desktop-navbar, .navbar, nav.navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 10000 !important;
        height: 60px !important;
        width: 100% !important;
    }
    /* Surcharger navbar-servo-fix.css */
    body #desktop-navbar, html body #desktop-navbar {
        height: 60px !important;
        min-height: 60px !important;
        max-height: 60px !important;
    }
    /* Éléments navbar visibles */
    #desktop-navbar * {
        visibility: visible !important;
        opacity: 1 !important;
    }
    /* Container navbar avec centrage vertical parfait */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.75rem 1rem !important;
        min-height: 60px !important;
    }
    /* Logo avec centrage vertical parfait */
    #desktop-navbar .navbar-brand {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        line-height: 1 !important;
    }
    #desktop-navbar .navbar-brand img {
        height: 32px !important;
        width: auto !important;
        vertical-align: middle !important;
    }
    /* Boutons avec centrage vertical parfait */
    #desktop-navbar .btn,
    #desktop-navbar .navbar-nav .nav-link,
    #desktop-navbar .dropdown-toggle {
        display: flex !important;
        align-items: center !important;
        height: auto !important;
        padding: 0.375rem 0.75rem !important;
        margin: 0.125rem 0.25rem !important;
        line-height: 1.2 !important;
        vertical-align: middle !important;
    }
    /* Correction spécifique pour les icônes dans les boutons */
    #desktop-navbar .btn i,
    #desktop-navbar .navbar-nav .nav-link i,
    #desktop-navbar .dropdown-toggle i {
        vertical-align: middle !important;
        line-height: 1 !important;
    }
    /* Messages de bienvenue centrés */
    #desktop-navbar .d-none.d-md-flex {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
    }
    /* Forcer l'alignement vertical pour tous les éléments flex */
    #desktop-navbar .d-flex {
        align-items: center !important;
    }
    /* Animation SERVO centrée parfaitement */
    body .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 10001 !important;
        display: flex !important;
        align-items: center !important;
    }
    /* Réserver espace navbar */
    body {
        padding-top: 80px !important;
        margin: 0 !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        overflow-x: hidden !important;
    }
}

/* Styles généraux navbar (mobile + desktop) */
#desktop-navbar, nav#desktop-navbar {
    display: block !important;
    visibility: visible !important;
    position: fixed !important;
    top: 0 !important;
    z-index: 10000 !important;
}

/* Masquer navbar sur mobile */
@media (max-width: 767px) {
    #desktop-navbar, nav#desktop-navbar {
        display: none !important;
    }
}

/* ========================================
   VARIABLES CSS POUR LES THÈMES
======================================== */
:root {
    /* Mode Jour - Moderne Dynamique */
    --day-primary: #3b82f6;
    --day-secondary: #8b5cf6;
    --day-accent: #06b6d4;
    --day-bg: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    --day-bg-animated: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff);
    --day-card-bg: rgba(255, 255, 255, 0.95);
    --day-text: #1e293b;
    --day-text-light: #64748b;
    --day-shadow: rgba(59, 130, 246, 0.15);
    --day-border: rgba(148, 163, 184, 0.2);

    /* Mode Nuit - Futuriste */
    --night-primary: #00d4ff;
    --night-secondary: #7c3aed;
    --night-accent: #ff00aa;
    --night-bg: #0a0a0a;
    --night-bg-animated: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483);
    --night-card-bg: rgba(15, 15, 25, 0.95);
    --night-text: #ffffff;
    --night-text-light: #a0aec0;
    --night-shadow: rgba(0, 212, 255, 0.25);
    --night-border: rgba(0, 212, 255, 0.3);
    --night-glow: 0 0 20px rgba(0, 212, 255, 0.5);
}

/* ========================================
   STRUCTURE DE BASE
======================================== */
body {
    margin: 0;
    padding: 0;
    padding-top: 80px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow-x: hidden;
}

.modern-dashboard {
    position: relative;
    min-height: 100vh;
    padding: 1rem;
    transition: all 0.3s ease;
    margin-top: -80px;
    padding-top: 100px;
}

/* Mode Jour */
body:not(.night-mode):not(.dark-mode) .modern-dashboard {
    background: var(--day-bg);
    color: var(--day-text);
}

/* Mode Nuit */
body.night-mode .modern-dashboard,
body.dark-mode .modern-dashboard {
    background: var(--night-bg);
    color: var(--night-text);
}

/* Arrière-plan animé pour mode nuit */
body.night-mode .modern-dashboard::before,
body.dark-mode .modern-dashboard::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--night-bg-animated);
    background-size: 400% 400%;
    animation: gradientShift 15s ease infinite;
    z-index: -1;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* ========================================
   HEADER ET TITRE
======================================== */
.page-header {
    text-align: center;
    margin-bottom: 2rem;
    position: relative;
    z-index: 2;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-align: center;
}

body.night-mode .page-title,
body.dark-mode .page-title {
    background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    font-size: 1.1rem;
    opacity: 0.8;
    margin-bottom: 0;
}

/* ========================================
   STATISTIQUES
======================================== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    display: block;
}

.stat-card:hover {
    text-decoration: none;
    color: inherit;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-card:active {
    transform: translateY(0);
}

.stat-card.active {
    border-width: 2px;
}

/* Mode jour - cartes actives */
body:not(.night-mode):not(.dark-mode) .stat-card.active {
    border-color: var(--day-primary);
    background: rgba(59, 130, 246, 0.05);
    box-shadow: 0 4px 20px rgba(59, 130, 246, 0.2);
}

body:not(.night-mode):not(.dark-mode) .stat-card:hover {
    border-color: var(--day-primary);
    background: rgba(59, 130, 246, 0.02);
}

body.night-mode .stat-card,
body.dark-mode .stat-card {
    background: var(--night-card-bg);
    border-color: var(--night-border);
    box-shadow: var(--night-glow);
}

/* Mode nuit - cartes actives */
body.night-mode .stat-card.active,
body.dark-mode .stat-card.active {
    border-color: var(--night-primary);
    background: rgba(0, 212, 255, 0.1);
    box-shadow: 0 4px 20px rgba(0, 212, 255, 0.4);
}

body.night-mode .stat-card:hover,
body.dark-mode .stat-card:hover {
    border-color: var(--night-primary);
    background: rgba(0, 212, 255, 0.05);
    box-shadow: 0 6px 25px rgba(0, 212, 255, 0.3);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px var(--day-shadow);
}

body.night-mode .stat-card:hover,
body.dark-mode .stat-card:hover {
    box-shadow: var(--night-glow), 0 20px 40px var(--night-shadow);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    display: block;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: block;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Couleurs spécifiques pour chaque stat */
.stat-total { color: var(--day-primary); }
.stat-pending { color: #f59e0b; }
.stat-accepted { color: #10b981; }
.stat-refused { color: #ef4444; }
.stat-expired { color: #6b7280; }

body.night-mode .stat-total,
body.dark-mode .stat-total { color: var(--night-primary); }
body.night-mode .stat-pending,
body.dark-mode .stat-pending { color: #fbbf24; }
body.night-mode .stat-accepted,
body.dark-mode .stat-accepted { color: #34d399; }
body.night-mode .stat-refused,
body.dark-mode .stat-refused { color: #f87171; }
body.night-mode .stat-expired,
body.dark-mode .stat-expired { color: #9ca3af; }

/* ========================================
   FILTRES ET RECHERCHE
======================================== */
.filters-section {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(10px);
}

body.night-mode .filters-section,
body.dark-mode .filters-section {
    background: var(--night-card-bg);
    border-color: var(--night-border);
}

.search-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: end;
}

.form-group {
    flex: 1;
    min-width: 200px;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    font-size: 0.9rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--day-border);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.8);
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

body.night-mode .form-control,
body.dark-mode .form-control {
    background: rgba(255, 255, 255, 0.1);
    border-color: var(--night-border);
    color: var(--night-text);
}

.form-control:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

body.night-mode .form-control:focus,
body.dark-mode .form-control:focus {
    border-color: var(--night-primary);
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
}

body.night-mode .btn-primary,
body.dark-mode .btn-primary {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

/* Filtres par statut */
.status-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
}

.filter-btn {
    padding: 0.5rem 1rem;
    border: 2px solid var(--day-border);
    border-radius: 25px;
    background: transparent;
    color: var(--day-text);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

body.night-mode .filter-btn,
body.dark-mode .filter-btn {
    border-color: var(--night-border);
    color: var(--night-text);
}

.filter-btn:hover,
.filter-btn.active {
    background: var(--day-primary);
    border-color: var(--day-primary);
    color: white;
    transform: translateY(-2px);
}

body.night-mode .filter-btn:hover,
body.night-mode .filter-btn.active,
body.dark-mode .filter-btn:hover,
body.dark-mode .filter-btn.active {
    background: var(--night-primary);
    border-color: var(--night-primary);
    color: var(--night-bg);
}

.filter-count {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* ========================================
   LISTE DES DEVIS
======================================== */
.devis-grid {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: 1fr;
}

.devis-card {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid var(--day-border);
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

body.night-mode .devis-card,
body.dark-mode .devis-card {
    background: var(--night-card-bg);
    border-color: var(--night-border);
}

.devis-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px var(--day-shadow);
}

body.night-mode .devis-card:hover,
body.dark-mode .devis-card:hover {
    box-shadow: var(--night-glow), 0 20px 40px var(--night-shadow);
}

.devis-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.devis-number {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--day-primary);
}

body.night-mode .devis-number,
body.dark-mode .devis-number {
    color: var(--night-primary);
}

.devis-status {
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-envoye { background: #fef3c7; color: #92400e; }
.status-accepte { background: #d1fae5; color: #065f46; }
.status-refuse { background: #fee2e2; color: #991b1b; }
.status-expire { background: #f3f4f6; color: #374151; }

body.night-mode .status-envoye,
body.dark-mode .status-envoye { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
body.night-mode .status-accepte,
body.dark-mode .status-accepte { background: rgba(52, 211, 153, 0.2); color: #34d399; }
body.night-mode .status-refuse,
body.dark-mode .status-refuse { background: rgba(248, 113, 113, 0.2); color: #f87171; }
body.night-mode .status-expire,
body.dark-mode .status-expire { background: rgba(156, 163, 175, 0.2); color: #9ca3af; }

.devis-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.8rem;
    opacity: 0.7;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-weight: 500;
}

.devis-amount {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--day-primary);
    text-align: right;
}

body.night-mode .devis-amount,
body.dark-mode .devis-amount {
    color: var(--night-primary);
}

.devis-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--day-primary);
    color: var(--day-primary);
}

body.night-mode .btn-outline,
body.dark-mode .btn-outline {
    border-color: var(--night-primary);
    color: var(--night-primary);
}

.btn-outline:hover {
    background: var(--day-primary);
    color: white;
}

body.night-mode .btn-outline:hover,
body.dark-mode .btn-outline:hover {
    background: var(--night-primary);
    color: var(--night-bg);
}

/* ========================================
   RESPONSIVE
======================================== */
@media (max-width: 768px) {
    .modern-dashboard {
        padding: 0.5rem;
        padding-top: 80px;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .form-group {
        min-width: auto;
    }
    
    .status-filters {
        justify-content: center;
    }
    
    .devis-info {
        grid-template-columns: 1fr;
    }
    
    .devis-header {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .devis-amount {
        text-align: left;
    }
}

/* ========================================
   CORRECTIONS SPÉCIFIQUES MODE NUIT
======================================== */

/* Forcer le mode nuit sur tous les éléments */
body.night-mode,
body.dark-mode {
    background: var(--night-bg) !important;
    color: var(--night-text) !important;
}

/* Textes en mode nuit */
body.night-mode *,
body.dark-mode * {
    color: var(--night-text) !important;
}

/* Exceptions pour les éléments avec couleurs spécifiques */
body.night-mode .stat-total,
body.dark-mode .stat-total { color: var(--night-primary) !important; }

body.night-mode .stat-pending,
body.dark-mode .stat-pending { color: #fbbf24 !important; }

body.night-mode .stat-accepted,
body.dark-mode .stat-accepted { color: #34d399 !important; }

body.night-mode .stat-refused,
body.dark-mode .stat-refused { color: #f87171 !important; }

body.night-mode .stat-expired,
body.dark-mode .stat-expired { color: #9ca3af !important; }

/* Placeholder des inputs en mode nuit */
body.night-mode .form-control::placeholder,
body.dark-mode .form-control::placeholder {
    color: var(--night-text-light) !important;
    opacity: 0.7 !important;
}

/* Labels en mode nuit */
body.night-mode .form-label,
body.night-mode .info-label,
body.dark-mode .form-label,
body.dark-mode .info-label {
    color: var(--night-text-light) !important;
}

/* Valeurs en mode nuit */
body.night-mode .info-value,
body.dark-mode .info-value {
    color: var(--night-text) !important;
}

/* Montants en mode nuit */
body.night-mode .devis-amount,
body.night-mode .devis-number,
body.dark-mode .devis-amount,
body.dark-mode .devis-number {
    color: var(--night-primary) !important;
}

/* États vides en mode nuit */
body.night-mode .empty-state,
body.dark-mode .empty-state {
    color: var(--night-text-light) !important;
}

body.night-mode .empty-state h3,
body.dark-mode .empty-state h3 {
    color: var(--night-text) !important;
}

body.night-mode .empty-state i,
body.dark-mode .empty-state i {
    color: var(--night-text-light) !important;
    opacity: 0.5 !important;
}

/* ========================================
   BOUTON DE BASCULEMENT DE THÈME
======================================== */
.theme-toggle-btn {
    position: relative;
    width: 50px;
    height: 50px;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    z-index: 1000;
    margin-top: 10px;
}

/* Mode jour */
body:not(.night-mode):not(.dark-mode) .theme-toggle-btn {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    box-shadow: 0 4px 15px var(--day-shadow);
}

body:not(.night-mode):not(.dark-mode) .theme-toggle-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px var(--day-shadow);
}

/* Mode nuit */
body.night-mode .theme-toggle-btn,
body.dark-mode .theme-toggle-btn {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    color: var(--night-bg);
    box-shadow: var(--night-glow);
}

body.night-mode .theme-toggle-btn:hover,
body.dark-mode .theme-toggle-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 25px rgba(0, 212, 255, 0.7);
}

.theme-toggle-btn:active {
    transform: scale(0.95);
}

/* Responsive pour le bouton */
@media (max-width: 768px) {
    .theme-toggle-btn {
        width: 45px;
        height: 45px;
        font-size: 1.1rem;
    }
}

/* ========================================
   BOUTONS D'ACTION PRINCIPAUX - DESIGN MODERNE
======================================== */
.action-buttons-container {
    margin: 1.5rem 0;
    padding: 1rem;
    border-radius: 16px;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Mode jour */
body:not(.night-mode):not(.dark-mode) .action-buttons-container {
    background: rgba(255, 255, 255, 0.4);
    border-color: rgba(148, 163, 184, 0.2);
}

/* Mode nuit */
body.night-mode .action-buttons-container,
body.dark-mode .action-buttons-container {
    background: rgba(15, 15, 25, 0.6);
    border-color: rgba(0, 212, 255, 0.2);
}

.modern-action-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    justify-content: center;
}

.action-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border: 1px solid transparent;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 500;
    font-size: 13px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    background: none;
    white-space: nowrap;
    min-width: auto;
    max-width: fit-content;
}

/* Mode jour */
body:not(.night-mode):not(.dark-mode) .action-button {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.9));
    color: #475569;
    border-color: rgba(148, 163, 184, 0.3);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

body:not(.night-mode):not(.dark-mode) .action-button:hover {
    transform: translateY(-1px) scale(1.02);
    background: linear-gradient(135deg, rgba(255, 255, 255, 1), rgba(248, 250, 252, 0.95));
    color: var(--day-primary);
    border-color: var(--day-primary);
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.2);
    text-decoration: none;
}

/* Mode nuit */
body.night-mode .action-button,
body.dark-mode .action-button {
    background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(124, 58, 237, 0.1));
    border-color: rgba(0, 212, 255, 0.3);
    color: var(--night-text);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

body.night-mode .action-button:hover,
body.dark-mode .action-button:hover {
    transform: translateY(-1px) scale(1.02);
    background: linear-gradient(135deg, rgba(0, 212, 255, 0.2), rgba(124, 58, 237, 0.2));
    color: var(--night-primary);
    border-color: var(--night-primary);
    box-shadow: 0 4px 16px rgba(0, 212, 255, 0.4);
    text-decoration: none;
}

.action-button i {
    font-size: 14px;
    opacity: 0.8;
}

.action-button:hover i {
    opacity: 1;
}

/* Bouton principal (renvoyer tous) - style spécial */
.action-button:first-child {
    font-weight: 600;
}

body:not(.night-mode):not(.dark-mode) .action-button:first-child {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    border-color: transparent;
}

body:not(.night-mode):not(.dark-mode) .action-button:first-child:hover {
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

body.night-mode .action-button:first-child,
body.dark-mode .action-button:first-child {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    color: var(--night-bg);
    border-color: transparent;
}

body.night-mode .action-button:first-child:hover,
body.dark-mode .action-button:first-child:hover {
    background: linear-gradient(135deg, #00e5ff, #8b5cf6);
    box-shadow: 0 6px 20px rgba(0, 212, 255, 0.6);
}

/* Responsive pour les boutons d'action */
@media (max-width: 768px) {
    .action-buttons-container {
        margin: 1rem 0;
        padding: 0.75rem;
        border-radius: 12px;
    }
    
    .modern-action-buttons {
        gap: 0.5rem;
    }
    
    .action-button {
        padding: 0.625rem 1rem;
        font-size: 12px;
        border-radius: 10px;
    }
    
    .action-button i {
        font-size: 13px;
    }
}

/* ========================================
   STYLES POUR LES MODALS - PRIORITÉ MAXIMALE
======================================== */

/* Centrage et positionnement des modals */
.modal {
    z-index: 1055 !important;
}

.modal:not(.show) {
    display: none !important;
}

.modal.show {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.modal-dialog {
    margin: 0 auto !important;
    max-width: 500px !important;
    width: 90% !important;
    position: relative !important;
    pointer-events: auto !important;
    transform: none !important;
}

.modal.fade .modal-dialog {
    transition: transform 0.3s ease-out !important;
    transform: scale(0.9) !important;
}

.modal.show .modal-dialog {
    transform: scale(1) !important;
}

/* Backdrop personnalisé */
.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.6) !important;
    backdrop-filter: blur(4px) !important;
}

body.night-mode .modal-backdrop,
body.dark-mode .modal-backdrop {
    background-color: rgba(0, 0, 0, 0.8) !important;
    backdrop-filter: blur(8px) !important;
}

/* Styles pour le mode jour - PRIORITÉ MAXIMALE */
body:not(.night-mode):not(.dark-mode) .modal-content {
    background: rgba(255, 255, 255, 0.98) !important;
    border: 1px solid rgba(148, 163, 184, 0.2) !important;
    border-radius: 16px !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15) !important;
    backdrop-filter: blur(20px) !important;
}

body:not(.night-mode):not(.dark-mode) .modal-header {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05)) !important;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2) !important;
    border-radius: 16px 16px 0 0 !important;
    padding: 1.5rem !important;
}

body:not(.night-mode):not(.dark-mode) .modal-title {
    color: #1e293b !important;
    font-weight: 600 !important;
    font-size: 1.25rem !important;
}

body:not(.night-mode):not(.dark-mode) .modal-body {
    padding: 1.5rem !important;
    color: #475569 !important;
}

body:not(.night-mode):not(.dark-mode) .modal-body p {
    color: #475569 !important;
}

body:not(.night-mode):not(.dark-mode) .modal-footer {
    background: rgba(248, 250, 252, 0.8) !important;
    border-top: 1px solid rgba(148, 163, 184, 0.2) !important;
    border-radius: 0 0 16px 16px !important;
    padding: 1rem 1.5rem !important;
}

/* Styles pour le mode nuit - PRIORITÉ MAXIMALE */
body.night-mode .modal-content,
body.dark-mode .modal-content,
.night-mode .modal-content,
.dark-mode .modal-content,
html.night-mode .modal-content,
html.dark-mode .modal-content {
    background: #0f0f19 !important;
    background: linear-gradient(135deg, #0a0f19, #111827, #0f172a) !important;
    border: 1px solid rgba(0, 212, 255, 0.5) !important;
    border-radius: 16px !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8), 0 0 40px rgba(0, 212, 255, 0.3) !important;
    backdrop-filter: blur(20px) !important;
}

body.night-mode .modal-header,
body.dark-mode .modal-header,
.night-mode .modal-header,
.dark-mode .modal-header,
html.night-mode .modal-header,
html.dark-mode .modal-header {
    background: #0a0f19 !important;
    background: linear-gradient(135deg, #0a0f19, #111827) !important;
    border-bottom: 1px solid rgba(0, 212, 255, 0.5) !important;
    border-radius: 16px 16px 0 0 !important;
    padding: 1.5rem !important;
}

body.night-mode .modal-title,
body.dark-mode .modal-title,
.night-mode .modal-title,
.dark-mode .modal-title,
html.night-mode .modal-title,
html.dark-mode .modal-title {
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 1.25rem !important;
}

body.night-mode .modal-title i,
body.dark-mode .modal-title i,
.night-mode .modal-title i,
.dark-mode .modal-title i,
html.night-mode .modal-title i,
html.dark-mode .modal-title i {
    color: #00d4ff !important;
    margin-right: 0.5rem !important;
}

body.night-mode .modal-body,
body.dark-mode .modal-body,
.night-mode .modal-body,
.dark-mode .modal-body,
html.night-mode .modal-body,
html.dark-mode .modal-body {
    background: #0f0f19 !important;
    padding: 1.5rem !important;
    color: #e2e8f0 !important;
}

body.night-mode .modal-body p,
body.dark-mode .modal-body p,
.night-mode .modal-body p,
.dark-mode .modal-body p,
html.night-mode .modal-body p,
html.dark-mode .modal-body p {
    color: #e2e8f0 !important;
}

body.night-mode .modal-body ul,
body.dark-mode .modal-body ul,
.night-mode .modal-body ul,
.dark-mode .modal-body ul,
html.night-mode .modal-body ul,
html.dark-mode .modal-body ul {
    color: #e2e8f0 !important;
}

body.night-mode .modal-body li,
body.dark-mode .modal-body li,
.night-mode .modal-body li,
.dark-mode .modal-body li,
html.night-mode .modal-body li,
html.dark-mode .modal-body li {
    color: #e2e8f0 !important;
}

body.night-mode .modal-footer,
body.dark-mode .modal-footer,
.night-mode .modal-footer,
.dark-mode .modal-footer,
html.night-mode .modal-footer,
html.dark-mode .modal-footer {
    background: #0a0f19 !important;
    background: linear-gradient(135deg, #0a0f19, #111827) !important;
    border-top: 1px solid rgba(0, 212, 255, 0.5) !important;
    border-radius: 0 0 16px 16px !important;
    padding: 1rem 1.5rem !important;
}

/* Bouton de fermeture - PRIORITÉ MAXIMALE */
body:not(.night-mode):not(.dark-mode) .btn-close {
    background: none !important;
    border: none !important;
    font-size: 1.25rem !important;
    color: #64748b !important;
    opacity: 0.7 !important;
}

body:not(.night-mode):not(.dark-mode) .btn-close:hover {
    opacity: 1 !important;
    color: #475569 !important;
}

body.night-mode .btn-close,
body.dark-mode .btn-close {
    background: none !important;
    border: none !important;
    font-size: 1.25rem !important;
    color: var(--night-text-light) !important;
    opacity: 0.7 !important;
    filter: invert(1) !important;
}

body.night-mode .btn-close:hover,
body.dark-mode .btn-close:hover {
    opacity: 1 !important;
    color: var(--night-text) !important;
}

/* Styles pour les alertes dans les modals - PRIORITÉ MAXIMALE */
body:not(.night-mode):not(.dark-mode) .modal .alert-info {
    background: rgba(59, 130, 246, 0.1) !important;
    border: 1px solid rgba(59, 130, 246, 0.2) !important;
    color: #1e40af !important;
    border-radius: 12px !important;
}

body.night-mode .modal .alert-info,
body.dark-mode .modal .alert-info,
.night-mode .modal .alert-info,
.dark-mode .modal .alert-info,
html.night-mode .modal .alert-info,
html.dark-mode .modal .alert-info {
    background: rgba(0, 212, 255, 0.15) !important;
    background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(59, 130, 246, 0.1)) !important;
    border: 1px solid rgba(0, 212, 255, 0.5) !important;
    color: #00d4ff !important;
    border-radius: 12px !important;
}

body.night-mode .modal .alert-info i,
body.dark-mode .modal .alert-info i,
.night-mode .modal .alert-info i,
.dark-mode .modal .alert-info i,
html.night-mode .modal .alert-info i,
html.dark-mode .modal .alert-info i {
    color: #00d4ff !important;
}

/* Styles pour les boutons dans les modals - PRIORITÉ MAXIMALE */
body:not(.night-mode):not(.dark-mode) .modal .btn-secondary {
    background: rgba(148, 163, 184, 0.1) !important;
    border: 1px solid rgba(148, 163, 184, 0.3) !important;
    color: #475569 !important;
}

body:not(.night-mode):not(.dark-mode) .modal .btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    border: none !important;
    color: white !important;
}

body.night-mode .modal .btn-secondary,
body.dark-mode .modal .btn-secondary,
.night-mode .modal .btn-secondary,
.dark-mode .modal .btn-secondary,
html.night-mode .modal .btn-secondary,
html.dark-mode .modal .btn-secondary {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(0, 212, 255, 0.3) !important;
    color: #a0aec0 !important;
}

body.night-mode .modal .btn-warning,
body.dark-mode .modal .btn-warning,
.night-mode .modal .btn-warning,
.dark-mode .modal .btn-warning,
html.night-mode .modal .btn-warning,
html.dark-mode .modal .btn-warning {
    background: linear-gradient(135deg, #00d4ff, #7c3aed) !important;
    border: none !important;
    color: #0a0a0a !important;
    box-shadow: 0 0 15px rgba(0, 212, 255, 0.4) !important;
}

/* Responsive pour les modals */
@media (max-width: 576px) {
    .modal-dialog {
        margin: 1rem;
        max-width: none;
    }
    
    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 1rem;
    }
}

/* ========================================
   CORRECTION DES ÉLÉMENTS BLOQUANTS
======================================== */

/* S'assurer qu'aucun élément ne bloque les interactions */
body:not(.modal-open) {
    overflow: auto !important;
    padding-right: 0 !important;
}

/* Supprimer les pointer-events: none problématiques */
.modern-dashboard,
.modern-dashboard * {
    pointer-events: auto !important;
}

/* Exception pour les éléments qui doivent être non-cliquables */
.modal-dialog:not(.show) {
    pointer-events: none !important;
}

/* S'assurer que les backdrops ne restent pas */
.modal-backdrop:not(.show) {
    display: none !important;
}

/* Forcer la fermeture des modals cachés */
.modal:not(.show) {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
}

/* Correction spécifique pour les overlays problématiques */
[class*="nouvelles_actions_modal"],
[id*="nouvelles_actions_modal"] {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
    z-index: -1 !important;
}

/* Forcer la visibilité des modals actifs en mode nuit */
body.night-mode .modal.show,
body.dark-mode .modal.show,
.night-mode .modal.show,
.dark-mode .modal.show,
html.night-mode .modal.show,
html.dark-mode .modal.show {
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
    z-index: 1055 !important;
}

body.night-mode .modal.show .modal-dialog,
body.dark-mode .modal.show .modal-dialog,
.night-mode .modal.show .modal-dialog,
.dark-mode .modal.show .modal-dialog,
html.night-mode .modal.show .modal-dialog,
html.dark-mode .modal.show .modal-dialog {
    opacity: 1 !important;
    visibility: visible !important;
    transform: scale(1) !important;
}

/* Backdrop pour mode nuit */
body.night-mode .modal-backdrop,
body.dark-mode .modal-backdrop,
.night-mode .modal-backdrop,
.dark-mode .modal-backdrop,
html.night-mode .modal-backdrop,
html.dark-mode .modal-backdrop {
    background-color: rgba(0, 0, 0, 0.95) !important;
    backdrop-filter: blur(10px) !important;
}

/* Forcer le backdrop sombre même sans classe spécifique */
.modal-backdrop.show {
    opacity: 0.95 !important;
}

body.night-mode .modal-backdrop.show,
body.dark-mode .modal-backdrop.show,
.night-mode .modal-backdrop.show,
.dark-mode .modal-backdrop.show,
html.night-mode .modal-backdrop.show,
html.dark-mode .modal-backdrop.show {
    background-color: rgba(0, 0, 0, 0.95) !important;
    opacity: 1 !important;
}

/* S'assurer que tous les éléments interactifs fonctionnent */
button,
a,
input,
select,
textarea,
.stat-card,
.action-button,
.btn,
.form-control {
    pointer-events: auto !important;
    cursor: pointer !important;
}

input,
select,
textarea,
.form-control {
    cursor: text !important;
}

/* ========================================
   STYLES POUR LE MODAL DÉTAILS DU DEVIS
======================================== */

/* Modal plus large pour les détails du devis */
.modal-devis-details {
    max-width: 95vw !important;
    width: 95vw !important;
    margin: 1rem auto !important;
}

@media (min-width: 1200px) {
    .modal-devis-details {
        max-width: 90vw !important;
        width: 90vw !important;
    }
}

@media (min-width: 1400px) {
    .modal-devis-details {
        max-width: 85vw !important;
        width: 85vw !important;
    }
}

@media (min-width: 1600px) {
    .modal-devis-details {
        max-width: 80vw !important;
        width: 80vw !important;
    }
}

/* Styles pour le modal détaillé des devis */
.devis-details-container {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.devis-details-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 25px;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.devis-details-header h4 {
    color: white;
    margin: 0;
    font-weight: 600;
}

.devis-status-badge .badge {
    font-size: 0.9rem;
    padding: 8px 12px;
}

.total-amount {
    background: rgba(255, 255, 255, 0.1);
    padding: 15px;
    border-radius: 10px;
    text-align: center;
}

.info-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.card-title {
    font-weight: 600;
    margin-bottom: 15px;
    color: #2d3748;
}

.client-info-detailed {
    display: flex;
    align-items: center;
    gap: 15px;
}

.client-avatar-large {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.client-details-extended h5 {
    margin: 0 0 8px 0;
    color: #2d3748;
    font-weight: 600;
}

.section-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.section-title {
    font-weight: 600;
    margin-bottom: 15px;
    color: #2d3748;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 8px;
}

.pannes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
}

.panne-card {
    background: #f7fafc;
    border-radius: 8px;
    padding: 15px;
    border-left: 4px solid #e53e3e;
}

.panne-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.panne-title {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: #2d3748;
}

.panne-description {
    margin: 0;
    font-size: 0.85rem;
    color: #4a5568;
}

.solutions-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.solution-card {
    background: #f7fafc;
    border-radius: 12px;
    padding: 20px;
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
}

.solution-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
}

.solution-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.solution-title {
    margin: 0;
    font-weight: 600;
    color: #2d3748;
}

.solution-description {
    color: #4a5568;
    margin-bottom: 15px;
}

.solution-elements {
    background: white;
    border-radius: 8px;
    padding: 15px;
}

.elements-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: #2d3748;
}

.element-name {
    font-weight: 500;
    color: #2d3748;
}

.element-description {
    font-size: 0.85rem;
}

.element-price {
    font-weight: 600;
    color: #38a169;
}

.notes-content, .message-content {
    background: #f7fafc;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #3182ce;
    font-size: 0.9rem;
    line-height: 1.5;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e2e8f0;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 30px;
    height: 30px;
    background: white;
    border: 2px solid #667eea;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    color: #667eea;
}

.timeline-content {
    background: #f7fafc;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.timeline-title {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: #2d3748;
}

.timeline-description {
    margin: 0 0 8px 0;
    font-size: 0.85rem;
    color: #4a5568;
}

.timeline-date {
    font-size: 0.8rem;
}

.prolonger-btn {
    font-size: 0.8rem;
    padding: 5px 10px;
}

/* Responsive pour le modal détaillé */
@media (max-width: 768px) {
    .modal-devis-details {
        max-width: 98vw !important;
        width: 98vw !important;
        margin: 0.5rem auto !important;
    }
    
    .devis-details-header {
        padding: 20px;
    }
    
    .devis-details-header .row {
        text-align: center;
    }
    
    .total-amount {
        margin-top: 15px;
    }
    
    .client-info-detailed {
        flex-direction: column;
        text-align: center;
    }
    
    .pannes-grid {
        grid-template-columns: 1fr;
    }
    
    .solution-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .timeline {
        padding-left: 20px;
    }
    
    .timeline-marker {
        left: -15px;
        width: 25px;
        height: 25px;
    }
}

@media (max-width: 576px) {
    .modal-devis-details {
        max-width: 100vw !important;
        width: 100vw !important;
        margin: 0 !important;
        border-radius: 0 !important;
    }
    
    .modal-devis-details .modal-content {
        border-radius: 0 !important;
        border: none !important;
        min-height: 100vh !important;
    }
}

/* Mode nuit pour le modal détails */
body.night-mode .devis-details-container,
body.dark-mode .devis-details-container {
    color: var(--night-text) !important;
}

/* Header avec dégradé coloré sombre */
body.night-mode .devis-details-header,
body.dark-mode .devis-details-header {
    background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460) !important;
    background-image: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(124, 58, 237, 0.1)) !important;
    border: 1px solid rgba(0, 212, 255, 0.3) !important;
    color: white !important;
    box-shadow: 0 8px 25px rgba(0, 212, 255, 0.2) !important;
}

/* Montant total avec accent coloré */
body.night-mode .total-amount,
body.dark-mode .total-amount {
    background: linear-gradient(135deg, rgba(0, 212, 255, 0.15), rgba(124, 58, 237, 0.15)) !important;
    border: 1px solid rgba(0, 212, 255, 0.4) !important;
    box-shadow: 0 0 15px rgba(0, 212, 255, 0.1) !important;
}

/* Cartes avec bordures colorées */
body.night-mode .info-card,
body.night-mode .section-card,
body.dark-mode .info-card,
body.dark-mode .section-card {
    background: linear-gradient(135deg, #1a1a2e, #16213e) !important;
    border: 1px solid rgba(0, 212, 255, 0.2) !important;
    color: var(--night-text) !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3), 0 0 10px rgba(0, 212, 255, 0.1) !important;
}

/* Titres avec couleurs d'accent */
body.night-mode .card-title,
body.night-mode .section-title,
body.dark-mode .card-title,
body.dark-mode .section-title {
    color: #00d4ff !important;
    border-bottom: 2px solid rgba(0, 212, 255, 0.4) !important;
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.3) !important;
}

body.night-mode .client-details-extended h5,
body.dark-mode .client-details-extended h5 {
    color: #ffffff !important;
    text-shadow: 0 0 8px rgba(0, 212, 255, 0.2) !important;
}

/* Avatar client avec dégradé coloré */
body.night-mode .client-avatar-large,
body.dark-mode .client-avatar-large {
    background: linear-gradient(135deg, #00d4ff, #7c3aed) !important;
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.4) !important;
}

/* Cartes avec accents colorés */
body.night-mode .panne-card,
body.dark-mode .panne-card {
    background: linear-gradient(135deg, #2d1b69, #1a1a2e) !important;
    border-left: 4px solid #ff6b6b !important;
    border-top: 1px solid rgba(255, 107, 107, 0.2) !important;
    box-shadow: 0 2px 10px rgba(255, 107, 107, 0.1) !important;
    color: var(--night-text-light) !important;
}

body.night-mode .solution-card,
body.dark-mode .solution-card {
    background: linear-gradient(135deg, #1a2332, #16213e) !important;
    border: 2px solid rgba(0, 212, 255, 0.3) !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3), 0 0 10px rgba(0, 212, 255, 0.1) !important;
    color: var(--night-text-light) !important;
}

body.night-mode .solution-card:hover,
body.dark-mode .solution-card:hover {
    border-color: rgba(0, 212, 255, 0.6) !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4), 0 0 20px rgba(0, 212, 255, 0.2) !important;
    transform: translateY(-2px) !important;
}

/* Timeline avec couleurs */
body.night-mode .timeline-content,
body.dark-mode .timeline-content {
    background: linear-gradient(135deg, #1a1a2e, #16213e) !important;
    border: 1px solid rgba(0, 212, 255, 0.2) !important;
    color: var(--night-text-light) !important;
}

body.night-mode .timeline::before,
body.dark-mode .timeline::before {
    background: linear-gradient(180deg, #00d4ff, #7c3aed) !important;
    box-shadow: 0 0 10px rgba(0, 212, 255, 0.3) !important;
}

body.night-mode .timeline-marker,
body.dark-mode .timeline-marker {
    background: linear-gradient(135deg, #1a1a2e, #16213e) !important;
    border: 2px solid #00d4ff !important;
    color: #00d4ff !important;
    box-shadow: 0 0 15px rgba(0, 212, 255, 0.4) !important;
}

/* Notes et messages avec accents */
body.night-mode .notes-content,
body.dark-mode .notes-content {
    background: linear-gradient(135deg, #1a2332, #16213e) !important;
    border-left: 4px solid #00d4ff !important;
    border-top: 1px solid rgba(0, 212, 255, 0.2) !important;
    color: var(--night-text-light) !important;
}

body.night-mode .message-content,
body.dark-mode .message-content {
    background: linear-gradient(135deg, #2d1b69, #1a1a2e) !important;
    border-left: 4px solid #7c3aed !important;
    border-top: 1px solid rgba(124, 58, 237, 0.2) !important;
    color: var(--night-text-light) !important;
}

/* Éléments de solution avec fond sombre */
body.night-mode .solution-elements,
body.dark-mode .solution-elements {
    background: linear-gradient(135deg, #0f1419, #1a1a2e) !important;
    border: 1px solid rgba(0, 212, 255, 0.1) !important;
}

/* Badges colorés en mode nuit */
body.night-mode .badge.bg-success,
body.dark-mode .badge.bg-success {
    background: linear-gradient(135deg, #10b981, #059669) !important;
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.3) !important;
}

body.night-mode .badge.bg-warning,
body.dark-mode .badge.bg-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.3) !important;
}

body.night-mode .badge.bg-danger,
body.dark-mode .badge.bg-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    box-shadow: 0 0 10px rgba(239, 68, 68, 0.3) !important;
}

body.night-mode .badge.bg-info,
body.dark-mode .badge.bg-info {
    background: linear-gradient(135deg, #00d4ff, #0ea5e9) !important;
    box-shadow: 0 0 10px rgba(0, 212, 255, 0.3) !important;
}

/* Icônes avec couleurs d'accent */
body.night-mode .info-card .fas,
body.night-mode .section-card .fas,
body.dark-mode .info-card .fas,
body.dark-mode .section-card .fas {
    text-shadow: 0 0 8px currentColor !important;
}

/* Liens avec couleurs vives */
body.night-mode .devis-details-container a,
body.dark-mode .devis-details-container a {
    color: #00d4ff !important;
    text-shadow: 0 0 5px rgba(0, 212, 255, 0.3) !important;
}

body.night-mode .devis-details-container a:hover,
body.dark-mode .devis-details-container a:hover {
    color: #7c3aed !important;
    text-shadow: 0 0 8px rgba(124, 58, 237, 0.4) !important;
}

/* Bouton prolonger avec style coloré */
body.night-mode .prolonger-btn,
body.dark-mode .prolonger-btn {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    border: none !important;
    box-shadow: 0 0 15px rgba(245, 158, 11, 0.3) !important;
}

body.night-mode .prolonger-btn:hover,
body.dark-mode .prolonger-btn:hover {
    background: linear-gradient(135deg, #fbbf24, #f59e0b) !important;
    box-shadow: 0 0 20px rgba(245, 158, 11, 0.5) !important;
    transform: translateY(-1px) !important;
}

/* ========================================
   ÉTATS VIDES
======================================== */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    opacity: 0.7;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
}

.empty-state p {
    margin-bottom: 0;
}
</style>

<div class="modern-dashboard">
    <!-- Header -->
    <div class="page-header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-file-invoice-dollar"></i>
                    Gestion des Devis
                </h1>
                <p class="page-subtitle">Suivez et gérez tous vos devis en temps réel</p>
            </div>
            
            <!-- Bouton de basculement du thème -->
            <button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleNightMode()" title="Basculer le mode nuit">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </div>

    <!-- Statistiques Cliquables -->
    <div class="stats-grid">
        <a href="?page=devis_moderne" class="stat-card stat-total <?php echo empty($statut_ids) ? 'active' : ''; ?>" title="Voir tous les devis">
            <i class="fas fa-file-invoice stat-icon"></i>
            <span class="stat-number"><?php echo $total_devis; ?></span>
            <span class="stat-label">Total Devis</span>
        </a>
        <a href="?page=devis_moderne&statut_ids=envoye" class="stat-card stat-pending <?php echo $statut_ids == 'envoye' ? 'active' : ''; ?>" title="Filtrer les devis en attente">
            <i class="fas fa-clock stat-icon"></i>
            <span class="stat-number"><?php echo $total_en_attente; ?></span>
            <span class="stat-label">En Attente</span>
        </a>
        <a href="?page=devis_moderne&statut_ids=accepte" class="stat-card stat-accepted <?php echo $statut_ids == 'accepte' ? 'active' : ''; ?>" title="Filtrer les devis acceptés">
            <i class="fas fa-check-circle stat-icon"></i>
            <span class="stat-number"><?php echo $total_acceptes; ?></span>
            <span class="stat-label">Acceptés</span>
        </a>
        <a href="?page=devis_moderne&statut_ids=refuse" class="stat-card stat-refused <?php echo $statut_ids == 'refuse' ? 'active' : ''; ?>" title="Filtrer les devis refusés">
            <i class="fas fa-times-circle stat-icon"></i>
            <span class="stat-number"><?php echo $total_refuses; ?></span>
            <span class="stat-label">Refusés</span>
        </a>
        <a href="?page=devis_moderne&statut_ids=expire" class="stat-card stat-expired <?php echo $statut_ids == 'expire' ? 'active' : ''; ?>" title="Filtrer les devis expirés">
            <i class="fas fa-calendar-times stat-icon"></i>
            <span class="stat-number"><?php echo $total_expires; ?></span>
            <span class="stat-label">Expirés</span>
        </a>
    </div>

    <!-- Boutons d'action principaux -->
    <div class="action-buttons-container">
        <div class="modern-action-buttons">
            <button type="button" class="action-button" onclick="renvoyerTousLesDevis()">
                <i class="fas fa-paper-plane"></i>
                <span>RENVOYER TOUS LES DEVIS</span>
            </button>
            <a href="index.php?page=reparations" class="action-button">
                <i class="fas fa-arrow-left"></i>
                <span>RETOUR RÉPARATIONS</span>
            </a>
        </div>
    </div>

    <!-- Filtres et Recherche -->
    <div class="filters-section">
        <form class="search-form" method="GET" action="">
            <input type="hidden" name="page" value="devis_moderne">
            
            <div class="form-group">
                <label class="form-label">Rechercher</label>
                <input type="text" name="client_search" class="form-control" 
                       placeholder="Nom, téléphone, appareil..." 
                       value="<?php echo htmlspecialchars($client_search); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Date début</label>
                <input type="date" name="date_debut" class="form-control" 
                       value="<?php echo htmlspecialchars($date_debut); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Date fin</label>
                <input type="date" name="date_fin" class="form-control" 
                       value="<?php echo htmlspecialchars($date_fin); ?>">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Rechercher
                </button>
            </div>
        </form>
    </div>

    <!-- Liste des Devis -->
    <div class="devis-grid">
        <?php if (empty($devis)): ?>
            <div class="empty-state">
                <i class="fas fa-file-invoice-dollar"></i>
                <h3>Aucun devis trouvé</h3>
                <p>Aucun devis ne correspond aux critères de recherche.</p>
            </div>
        <?php else: ?>
            <?php foreach ($devis as $d): ?>
                <div class="devis-card">
                    <div class="devis-header">
                        <div class="devis-number">
                            Devis #<?php echo htmlspecialchars($d['numero_devis'] ?? $d['id']); ?>
                        </div>
                        <div class="devis-status status-<?php echo getStatutClass($d['statut']); ?>">
                            <?php echo getStatutLabel($d['statut']); ?>
                        </div>
                    </div>

                    <div class="devis-info">
                        <div class="info-item">
                            <span class="info-label">Client</span>
                            <span class="info-value">
                                <?php 
                                $client_nom = trim(($d['client_nom'] ?? '') . ' ' . ($d['client_prenom'] ?? ''));
                                echo htmlspecialchars($client_nom ?: 'N/A'); 
                                ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Téléphone</span>
                            <span class="info-value"><?php echo htmlspecialchars($d['client_telephone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Appareil</span>
                            <span class="info-value">
                                <?php 
                                $appareil = trim(($d['reparation_appareil'] ?? '') . ' ' . ($d['reparation_modele'] ?? ''));
                                echo htmlspecialchars($appareil ?: 'N/A'); 
                                ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date création</span>
                            <span class="info-value">
                                <?php echo date('d/m/Y', strtotime($d['date_creation'])); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date expiration</span>
                            <span class="info-value">
                                <?php echo date('d/m/Y', strtotime($d['date_expiration'])); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Montant</span>
                            <span class="info-value devis-amount">
                                <?php echo number_format($d['montant_total'] ?? 0, 2, ',', ' '); ?> €
                            </span>
                        </div>
                    </div>

                    <div class="devis-actions">
                        <button class="btn btn-outline btn-sm" onclick="voirDetailsDevis(<?php echo $d['id']; ?>)">
                            <i class="fas fa-eye"></i>
                            Détails
                        </button>
                        <?php if ($d['statut'] == 'envoye' && strtotime($d['date_expiration']) > time()): ?>
                            <button class="btn btn-outline btn-sm" onclick="renvoyerDevis(<?php echo $d['id']; ?>)">
                                <i class="fas fa-paper-plane"></i>
                                Renvoyer
                            </button>
                        <?php endif; ?>
                        <?php if ($d['statut'] == 'envoye'): ?>
                            <button class="btn btn-outline btn-sm" onclick="prolongerDevis(<?php echo $d['id']; ?>)">
                                <i class="fas fa-calendar-plus"></i>
                                Prolonger
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal pour les détails du devis -->
<div class="modal fade" id="devisDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-devis-details">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice-dollar"></i>
                    Détails du Devis
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="devisDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement des détails du devis...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                    Fermer
                </button>
                <button type="button" class="btn btn-primary" id="imprimerDevisBtn" onclick="telechargerDevisPDF()">
                    <i class="fas fa-print"></i>
                    Imprimer
                </button>
                <button type="button" class="btn btn-warning" id="renvoyerDevisBtn" onclick="renvoyerDevisIndividuel()">
                    <i class="fas fa-paper-plane"></i>
                    Renvoyer SMS
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour prolonger le devis -->
<div class="modal fade" id="prolongerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Prolonger le Devis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="prolongerForm">
                    <input type="hidden" id="prolongerDevisId" name="devis_id">
                    <div class="mb-3">
                        <label for="nouvelleDateExpiration" class="form-label">Nouvelle date d'expiration</label>
                        <input type="date" class="form-control" id="nouvelleDateExpiration" name="nouvelle_date" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="confirmerProlongation()">Prolonger</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour confirmer le renvoi de tous les devis -->
<div class="modal fade" id="renvoyerTousModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-paper-plane"></i>
                    Renvoyer tous les devis
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="renvoyerTousContent">
                    <p>Souhaitez-vous vraiment renvoyer tous les devis éligibles par SMS ?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Cette action enverra un SMS à tous les clients ayant des devis :
                        <ul class="mb-0 mt-2">
                            <li>En attente (non expirés)</li>
                            <li>Expirés depuis moins de 15 jours</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning" onclick="confirmerRenvoyerTous()">
                    <i class="fas fa-paper-plane"></i>
                    Confirmer l'envoi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour voir les détails d'un devis
// Variables globales pour le modal
let currentDevisId = null;
let currentShopId = <?php echo json_encode($_SESSION['shop_id'] ?? null); ?>;

// Fonction pour voir les détails du devis
function voirDetailsDevis(devisId) {
    currentDevisId = devisId;
    
    if (!currentShopId) {
        alert('Erreur: ID du magasin non trouvé');
        return;
    }
    
    // Ouvrir le modal avec le spinner
    const modal = new bootstrap.Modal(document.getElementById('devisDetailsModal'));
    modal.show();
    
    // Charger les détails
    fetch(`ajax/get_devis_details.php?shop_id=${currentShopId}&id=${devisId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                afficherDetailsDevisContenu(data.devis);
            } else {
                document.getElementById('devisDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Erreur lors du chargement des détails du devis: ${data.message || 'Erreur inconnue'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('devisDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Erreur lors du chargement des détails du devis.
                </div>
            `;
        });
}

// Fonctions helper pour le modal
function getStatutLabel(statut) {
    const labels = {
        'envoye': 'En Attente',
        'accepte': 'Accepté',
        'refuse': 'Refusé',
        'brouillon': 'Brouillon',
        'expire': 'Expiré'
    };
    return labels[statut] || statut;
}

function getStatutColorClass(statut) {
    const colors = {
        'envoye': 'bg-warning text-dark',
        'accepte': 'bg-success',
        'refuse': 'bg-danger',
        'brouillon': 'bg-secondary',
        'expire': 'bg-dark'
    };
    return colors[statut] || 'bg-primary';
}

function getStatutIcon(statut) {
    const icons = {
        'envoye': 'fa-paper-plane',
        'accepte': 'fa-check-circle',
        'refuse': 'fa-times-circle',
        'brouillon': 'fa-edit',
        'expire': 'fa-clock'
    };
    return icons[statut] || 'fa-file-invoice-dollar';
}

function getGraviteBadgeClass(gravite) {
    const classes = {
        'Critique': 'bg-danger',
        'Élevée': 'bg-warning text-dark',
        'Moyenne': 'bg-info',
        'Faible': 'bg-success',
        'Normal': 'bg-secondary'
    };
    return classes[gravite] || 'bg-secondary';
}

function getActionIcon(action) {
    const icons = {
        'creation': 'fa-plus-circle',
        'envoi': 'fa-paper-plane',
        'acceptation': 'fa-check-circle',
        'refus': 'fa-times-circle',
        'modification': 'fa-edit',
        'suppression': 'fa-trash',
        'renvoi': 'fa-redo'
    };
    return icons[action] || 'fa-info-circle';
}

// Fonction principale pour afficher le contenu du devis
function afficherDetailsDevisContenu(devis) {
    const container = document.getElementById('devisDetailsContent');
    
    // Calculer les informations d'expiration
    const now = new Date();
    const expiration = new Date(devis.date_expiration);
    const isExpired = expiration < now;
    const diffTime = Math.abs(expiration - now);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    container.innerHTML = `
        <div class="devis-details-container">
            <!-- En-tête du devis -->
            <div class="devis-details-header">
                <div class="row align-items-center mb-4">
                    <div class="col-md-8">
                        <h4 class="mb-2">
                            <i class="fas fa-file-invoice-dollar text-primary me-2"></i>
                            Devis ${devis.numero_devis}
                        </h4>
                        <div class="devis-status-badge">
                            <span class="badge fs-6 ${getStatutColorClass(devis.statut)}">
                                <i class="fas ${getStatutIcon(devis.statut)} me-1"></i>
                                ${getStatutLabel(devis.statut)}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="total-amount">
                            <span class="text-muted">Montant total</span>
                            <h3 class="text-success mb-0">${parseFloat(devis.total_ttc || 0).toFixed(2)}€</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations principales -->
            <div class="row mb-4">
                <!-- Informations client -->
                <div class="col-md-6">
                    <div class="info-card">
                        <h6 class="card-title">
                            <i class="fas fa-user text-primary me-2"></i>
                            Informations Client
                        </h6>
                        <div class="client-info-detailed">
                            <div class="client-avatar-large">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="client-details-extended">
                                <h5>${devis.client_nom} ${devis.client_prenom || ''}</h5>
                                ${devis.client_telephone ? `
                                    <p class="mb-1">
                                        <i class="fas fa-phone text-success me-2"></i>
                                        <a href="tel:${devis.client_telephone}">${devis.client_telephone}</a>
                                    </p>
                                ` : ''}
                                ${devis.client_email ? `
                                    <p class="mb-0">
                                        <i class="fas fa-envelope text-info me-2"></i>
                                        <a href="mailto:${devis.client_email}">${devis.client_email}</a>
                                    </p>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations réparation -->
                <div class="col-md-6">
                    <div class="info-card">
                        <h6 class="card-title">
                            <i class="fas fa-tools text-warning me-2"></i>
                            Réparation #${devis.reparation_id}
                        </h6>
                        <div class="reparation-details">
                            ${devis.reparation_marque || devis.reparation_modele ? `
                                <p class="mb-2">
                                    <i class="fas fa-mobile-alt text-primary me-2"></i>
                                    <strong>${devis.reparation_marque || ''} ${devis.reparation_modele || ''}</strong>
                                </p>
                            ` : ''}
                            ${devis.reparation_probleme ? `
                                <p class="mb-0">
                                    <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                    ${devis.reparation_probleme}
                                </p>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dates et statut -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="info-card text-center">
                        <i class="fas fa-calendar-plus text-primary fs-3 mb-2"></i>
                        <h6 class="mb-1">Créé le</h6>
                        <p class="mb-0">${new Date(devis.date_creation).toLocaleDateString('fr-FR')}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card text-center">
                        <i class="fas fa-paper-plane text-info fs-3 mb-2"></i>
                        <h6 class="mb-1">Envoyé le</h6>
                        <p class="mb-0">${devis.date_envoi ? new Date(devis.date_envoi).toLocaleDateString('fr-FR') : 'Non envoyé'}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card text-center">
                        <i class="fas fa-clock ${isExpired ? 'text-danger' : 'text-warning'} fs-3 mb-2"></i>
                        <h6 class="mb-1">${isExpired ? 'Expiré depuis' : 'Expire dans'}</h6>
                        ${isExpired ? 
                            `<button class="btn btn-sm btn-warning prolonger-btn" onclick="ouvrirModalProlonger(${devis.id}, '${devis.numero_devis}')">
                                <i class="fas fa-clock me-1"></i>Prolonger
                            </button>` :
                            `<p class="mb-0 text-warning fw-bold">${diffDays} jour${diffDays > 1 ? 's' : ''}</p>`
                        }
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card text-center">
                        <i class="fas fa-reply text-success fs-3 mb-2"></i>
                        <h6 class="mb-1">Réponse client</h6>
                        <p class="mb-0">${devis.date_reponse ? new Date(devis.date_reponse).toLocaleDateString('fr-FR') : 'Aucune'}</p>
                    </div>
                </div>
            </div>

            ${isExpired && devis.gardiennage_facture > 0 ? `
                <!-- Alerte gardiennage -->
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Gardiennage facturé :</strong> ${devis.gardiennage_facture}€ 
                    (${diffDays} jour${diffDays > 1 ? 's' : ''} × 5€/jour)
                </div>
            ` : ''}

            <!-- Pannes identifiées -->
            ${devis.pannes && devis.pannes.length > 0 ? `
                <div class="section-card mb-4">
                    <h6 class="section-title">
                        <i class="fas fa-bug text-danger me-2"></i>
                        Pannes Identifiées (${devis.pannes.length})
                    </h6>
                    <div class="pannes-grid">
                        ${devis.pannes.map(panne => `
                            <div class="panne-card">
                                <div class="panne-header">
                                    <h6 class="panne-title">${panne.nom || panne.titre}</h6>
                                    <span class="badge ${getGraviteBadgeClass(panne.gravite)}">${panne.gravite || 'Normal'}</span>
                                </div>
                                ${panne.description ? `<p class="panne-description">${panne.description}</p>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}

            <!-- Solutions proposées -->
            ${devis.solutions && devis.solutions.length > 0 ? `
                <div class="section-card mb-4">
                    <h6 class="section-title">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        Solutions Proposées (${devis.solutions.length})
                    </h6>
                    <div class="solutions-container">
                        ${devis.solutions.map((solution, index) => `
                            <div class="solution-card">
                                <div class="solution-header">
                                    <h6 class="solution-title">
                                        Solution ${String.fromCharCode(65 + index)} - ${solution.nom}
                                        ${solution.recommandee ? '<span class="badge bg-success ms-2">Recommandée</span>' : ''}
                                    </h6>
                                    <div class="solution-price">
                                        <span class="text-success fw-bold fs-5">${parseFloat(solution.prix_total || 0).toFixed(2)}€</span>
                                    </div>
                                </div>
                                ${solution.description ? `<p class="solution-description">${solution.description}</p>` : ''}
                                
                                ${solution.elements && solution.elements.length > 0 ? `
                                    <div class="solution-elements">
                                        <h6 class="elements-title">Détail des prestations :</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    ${solution.elements.map(element => `
                                                        <tr>
                                                            <td class="element-name">${element.nom}</td>
                                                            <td class="element-description text-muted">${element.description || ''}</td>
                                                            <td class="element-price text-end">${parseFloat(element.prix || 0).toFixed(2)}€</td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}

            <!-- Messages et notes -->
            <div class="row mb-4">
                ${devis.notes_techniques ? `
                    <div class="col-md-6">
                        <div class="section-card">
                            <h6 class="section-title">
                                <i class="fas fa-clipboard-list text-info me-2"></i>
                                Notes Techniques
                            </h6>
                            <div class="notes-content">
                                ${devis.notes_techniques.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    </div>
                ` : ''}
                
                ${devis.message_client ? `
                    <div class="col-md-6">
                        <div class="section-card">
                            <h6 class="section-title">
                                <i class="fas fa-comment text-primary me-2"></i>
                                Message Client
                            </h6>
                            <div class="message-content">
                                ${devis.message_client.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    </div>
                ` : ''}
            </div>

            <!-- Historique des actions -->
            ${devis.logs && devis.logs.length > 0 ? `
                <div class="section-card">
                    <h6 class="section-title">
                        <i class="fas fa-history text-secondary me-2"></i>
                        Historique des Actions
                    </h6>
                    <div class="timeline">
                        ${devis.logs.map(log => `
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <i class="fas ${getActionIcon(log.action)}"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">${log.action}</h6>
                                    <p class="timeline-description">${log.description || ''}</p>
                                    <small class="timeline-date text-muted">
                                        ${new Date(log.date_action).toLocaleString('fr-FR')}
                                    </small>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}
        </div>
    `;
}

// Fonctions pour les actions du modal
function telechargerDevisPDF() {
    if (!currentDevisId) return;
    
    // Récupérer le lien sécurisé du devis pour l'impression
    fetch(`ajax/get_devis_details.php?shop_id=${currentShopId}&id=${currentDevisId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.devis && data.devis.lien_securise) {
                // Ouvrir la page d'impression avec le lien sécurisé
                window.open(`pages/devis_print.php?lien=${data.devis.lien_securise}&print=1`, '_blank');
            } else {
                console.error('Lien sécurisé non trouvé:', data);
                // Fallback vers l'ancien système
                window.open(`pages/devis_print.php?devis_id=${currentDevisId}&shop_id=${currentShopId}&print=1`, '_blank');
            }
        })
        .catch(error => {
            console.error('Erreur lors de la récupération du lien:', error);
            // Fallback vers l'ancien système
            window.open(`pages/devis_print.php?devis_id=${currentDevisId}&shop_id=${currentShopId}&print=1`, '_blank');
        });
}

function renvoyerDevisIndividuel() {
    if (!currentDevisId) return;
    
    if (!confirm('Êtes-vous sûr de vouloir renvoyer ce devis par SMS ?')) return;
    
    fetch(`ajax/renvoyer_devis.php?shop_id=${currentShopId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            devis_ids: [currentDevisId]
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Devis renvoyé avec succès !');
            location.reload();
        } else {
            alert('Erreur lors du renvoi : ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du renvoi du devis.');
    });
}

// Fonction pour renvoyer un devis
function renvoyerDevis(devisId) {
    if (!confirm('Êtes-vous sûr de vouloir renvoyer ce devis par SMS ?')) return;
    
    const shopId = <?php echo json_encode($_SESSION['shop_id'] ?? null); ?>;
    
    if (!shopId) {
        alert('Erreur: ID du magasin non trouvé');
        return;
    }
    
    fetch(`ajax/renvoyer_devis.php?shop_id=${shopId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            devis_ids: [devisId]
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Devis renvoyé avec succès');
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'envoi du devis');
    });
}

// Fonction pour prolonger un devis
function prolongerDevis(devisId) {
    document.getElementById('prolongerDevisId').value = devisId;
    
    // Définir la date minimale (aujourd'hui)
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('nouvelleDateExpiration').min = today;
    
    new bootstrap.Modal(document.getElementById('prolongerModal')).show();
}

// Confirmer la prolongation
function confirmerProlongation() {
    const form = document.getElementById('prolongerForm');
    const formData = new FormData(form);
    
    fetch('ajax/prolonger_devis.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Devis prolongé avec succès');
            bootstrap.Modal.getInstance(document.getElementById('prolongerModal')).hide();
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la prolongation du devis');
    });
}

// Fonction pour basculer le mode nuit
function toggleNightMode() {
    const body = document.body;
    const html = document.documentElement;
    const toggleBtn = document.getElementById('themeToggleBtn');
    const icon = toggleBtn ? toggleBtn.querySelector('i') : null;
    
    const isNightMode = body.classList.contains('night-mode') || body.classList.contains('dark-mode');
    
    if (isNightMode) {
        // Passer en mode jour
        body.classList.remove('night-mode', 'dark-mode');
        html.classList.remove('night-mode', 'dark-mode');
        if (icon) icon.className = 'fas fa-moon';
        
        // Sauvegarder la préférence
        if (window.GeekBoardTheme) {
            window.GeekBoardTheme.setPreference('light');
        } else {
            localStorage.setItem('geekboard_theme', 'light');
            localStorage.setItem('theme', 'light');
        }
        console.log('☀️ Mode jour activé');
    } else {
        // Passer en mode nuit
        body.classList.add('night-mode', 'dark-mode');
        html.classList.add('night-mode', 'dark-mode');
        if (icon) icon.className = 'fas fa-sun';
        
        // Sauvegarder la préférence
        if (window.GeekBoardTheme) {
            window.GeekBoardTheme.setPreference('dark');
        } else {
            localStorage.setItem('geekboard_theme', 'dark');
            localStorage.setItem('theme', 'dark');
        }
        console.log('🌙 Mode nuit activé');
    }
    
    // Animation du bouton
    if (toggleBtn) {
        toggleBtn.style.transform = 'scale(0.95)';
        setTimeout(() => {
            toggleBtn.style.transform = '';
        }, 150);
    }
}

// Fonction pour appliquer le thème au chargement
// Fonction pour appliquer le thème au chargement (Obsolète - géré par unified-night-mode.js)
// On utilise l'API globale si disponible
function applyStoredTheme() {
    if (window.GeekBoardTheme) {
        window.GeekBoardTheme.apply();
    }
}


// Fonction pour ouvrir le modal de renvoi de tous les devis
function renvoyerTousLesDevis() {
    const modal = new bootstrap.Modal(document.getElementById('renvoyerTousModal'));
    modal.show();
}

// Fonction pour confirmer le renvoi de tous les devis
function confirmerRenvoyerTous() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('renvoyerTousModal'));
    modal.hide();
    
    const shopId = <?php echo json_encode($_SESSION['shop_id'] ?? null); ?>;
    
    if (!shopId) {
        alert('Erreur: ID du magasin non trouvé');
        return;
    }
    
    // Afficher un indicateur de chargement
    const originalContent = document.querySelector('.action-button').innerHTML;
    const button = document.querySelector('.action-button');
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Envoi en cours...</span>';
    button.disabled = true;
    
    fetch(`ajax/renvoyer_tous_devis.php?shop_id=${shopId}`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const envoyes = data.envoyes || 0;
            const total = data.total_devis || 0;
            alert(`${envoyes} devis renvoyés avec succès sur ${total} éligibles !`);
            location.reload();
        } else {
            alert('Erreur lors du renvoi : ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du renvoi des devis.');
    })
    .finally(() => {
        // Restaurer le bouton
        button.innerHTML = originalContent;
        button.disabled = false;
    });
}

// Fonction pour renvoyer automatiquement les devis éligibles
function renvoyerDevisAutomatique() {
    // Vérifier si le renvoi automatique a déjà été fait aujourd'hui
    const today = new Date().toDateString();
    const lastAutoSend = localStorage.getItem('devis_auto_send_date');
    
    if (lastAutoSend === today) {
        console.log('📧 Renvoi automatique déjà effectué aujourd\'hui');
        return;
    }
    
    console.log('🔄 Vérification des devis à renvoyer automatiquement...');
    
    // Récupérer l'ID du magasin depuis la session
    const shopId = <?php echo json_encode($_SESSION['shop_id'] ?? null); ?>;
    
    if (!shopId) {
        console.error('❌ ID du magasin non trouvé pour le renvoi automatique');
        return;
    }
    
    // Appeler l'API de renvoi automatique
    fetch(`ajax/renvoyer_tous_devis.php?shop_id=${shopId}`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const envoyes = data.envoyes || 0;
            const total = data.total_devis || 0;
            
            if (envoyes > 0) {
                console.log(`✅ Renvoi automatique réussi: ${envoyes} devis renvoyés sur ${total} éligibles`);
                
                // Afficher une notification discrète
                showAutoSendNotification(envoyes, total);
                
                // Marquer comme fait aujourd'hui
                localStorage.setItem('devis_auto_send_date', today);
                
                // Recharger la page après 3 secondes pour voir les mises à jour
                setTimeout(() => {
                    location.reload();
                }, 3000);
            } else {
                console.log('ℹ️ Aucun devis éligible pour le renvoi automatique');
            }
        } else {
            console.warn('⚠️ Erreur lors du renvoi automatique:', data.message);
        }
    })
    .catch(error => {
        console.error('❌ Erreur lors du renvoi automatique des devis:', error);
    });
}

// Fonction pour afficher une notification discrète
function showAutoSendNotification(envoyes, total) {
    // Créer la notification
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
        z-index: 10000;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        max-width: 350px;
        animation: slideInRight 0.5s ease-out;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-paper-plane" style="font-size: 18px;"></i>
            <div>
                <div style="font-weight: 600; margin-bottom: 2px;">Renvoi automatique effectué</div>
                <div style="opacity: 0.9; font-size: 12px;">${envoyes} devis renvoyés sur ${total} éligibles</div>
            </div>
        </div>
    `;
    
    // Ajouter les styles d'animation
    if (!document.getElementById('auto-send-styles')) {
        const styles = document.createElement('style');
        styles.id = 'auto-send-styles';
        styles.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(notification);
    
    // Supprimer la notification après 5 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.5s ease-out';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 500);
    }, 5000);
}

// Fonction pour fermer tous les modals ouverts
function fermerTousLesModals() {
    // Fermer tous les modals Bootstrap
    const modals = document.querySelectorAll('.modal.show');
    modals.forEach(modal => {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    });
    
    // Supprimer tous les backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Restaurer le scroll du body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    console.log('🔧 Tous les modals fermés');
}

// Fonction pour déboguer les éléments qui bloquent
function debugElementsBloquants() {
    console.log('🔍 Recherche d\'éléments bloquants...');
    
    // Vérifier les modals ouverts
    const modalsOuverts = document.querySelectorAll('.modal.show');
    if (modalsOuverts.length > 0) {
        console.log('⚠️ Modals ouverts trouvés:', modalsOuverts);
    }
    
    // Vérifier les backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    if (backdrops.length > 0) {
        console.log('⚠️ Backdrops trouvés:', backdrops);
    }
    
    // Vérifier les éléments avec pointer-events: none
    const elementsBloquants = document.querySelectorAll('*');
    const elementsAvecPointerEvents = [];
    elementsBloquants.forEach(el => {
        const style = window.getComputedStyle(el);
        if (style.pointerEvents === 'none' && el.offsetParent !== null) {
            elementsAvecPointerEvents.push(el);
        }
    });
    
    if (elementsAvecPointerEvents.length > 0) {
        console.log('⚠️ Éléments avec pointer-events: none:', elementsAvecPointerEvents);
    }
    
    // Vérifier les overlays
    const overlays = document.querySelectorAll('[class*="overlay"], [class*="fade"], [id*="modal"]');
    console.log('🔍 Overlays potentiels:', overlays);
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page Devis Moderne chargée');
    
    // Appliquer le thème stocké
    applyStoredTheme();
    
    // Fermer tous les modals au chargement (sécurité)
    setTimeout(() => {
        fermerTousLesModals();
        debugElementsBloquants();
    }, 500);
    
    // Écouter les changements de préférence système
    // Écouter les changements de préférence système - Géré par unified-night-mode.js
    // if (window.matchMedia) { ... }
    
    // Ajouter un raccourci clavier pour déboguer (Ctrl+D)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'd') {
            e.preventDefault();
            debugElementsBloquants();
            fermerTousLesModals();
        }
    });
    
    // Note: Le renvoi automatique a été désactivé - utiliser le bouton "Renvoyer tous les devis"
});
</script>
