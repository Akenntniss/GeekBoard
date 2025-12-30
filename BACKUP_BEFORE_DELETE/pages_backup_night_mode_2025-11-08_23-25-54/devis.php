<?php
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'devis.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=devis');
    exit();
}

// Initialiser la session du magasin pour les appels via iframe
if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
    // Fonction pour détecter et initialiser la session du magasin
    function initializeShopSessionForIframe() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Détecter le sous-domaine
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $subdomain = '';
        
        if (strpos($host, 'localhost') !== false) {
            $subdomain = 'mkmkmk'; // Par défaut en local
        } else {
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                $subdomain = $parts[0];
            }
        }
        
        // Se connecter à la base principale pour récupérer les infos du magasin
        try {
            $main_pdo = getMainDBConnection();
            if ($main_pdo) {
                $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1 LIMIT 1");
                $stmt->execute([$subdomain]);
                $shop = $stmt->fetch(PDO::FETCH_ASSOC);
                
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
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    </head><body class="bg-light">
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
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                </head><body class="bg-light">
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
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            </head><body class="bg-light">
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

// Les fichiers nécessaires sont déjà inclus par index.php
// require_once __DIR__ . '/../includes/config.php';
// require_once __DIR__ . '/../includes/functions.php';

// Obtenir la connexion à la base de données du magasin de l'utilisateur
// Si on a un shop_id en session, utiliser la connexion directe par ID
if (isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
    error_log("DEVIS: Utilisation de la connexion directe par shop_id: " . $_SESSION['shop_id']);
    $shop_pdo = getShopDBConnectionById($_SESSION['shop_id']);
} else {
    error_log("DEVIS: Utilisation de la connexion automatique");
    $shop_pdo = getShopDBConnection();
}

// Récupérer et stocker l'ID du magasin actuel
$current_shop_id = $_SESSION['shop_id'] ?? null;
if (!$current_shop_id) {
    // Essayer de récupérer depuis l'URL
    $current_shop_id = $_GET['shop_id'] ?? null;
    if ($current_shop_id) {
        $_SESSION['shop_id'] = $current_shop_id;
    } else {
        error_log("ALERTE: ID du magasin non trouvé dans la session ou l'URL pour devis.php");
    }
}

// Vérifier que $shop_pdo est accessible et initialisé
if (!isset($shop_pdo) || $shop_pdo === null) {
    echo "<div class='alert alert-danger'>Erreur de connexion à la base de données. La variable \$shop_pdo n'est pas disponible. Veuillez contacter l'administrateur.</div>";
    error_log("ERREUR CRITIQUE dans devis.php: La variable \$shop_pdo n'est pas disponible");
    // Initialiser les variables pour éviter les erreurs
    $total_en_attente = 0;
    $total_acceptes = 0;
    $total_refuses = 0;
    $total_expires = 0;
    $total_devis = 0;
    $devis = [];
} else {
    // Paramètres de filtrage
    $statut_filter = isset($_GET['statut']) ? cleanInput($_GET['statut']) : '';
    $statut_ids = isset($_GET['statut_ids']) ? cleanInput($_GET['statut_ids']) : 'envoye'; // Par défaut, afficher les devis en attente
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
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM devis 
            WHERE statut = 'accepte'
        ");
        $total_acceptes = $stmt->fetch()['total'];

        // Devis refusés
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM devis 
            WHERE statut = 'refuse'
        ");
        $total_refuses = $stmt->fetch()['total'];

        // Devis expirés
        $stmt = $shop_pdo->query("
            SELECT COUNT(*) as total 
            FROM devis 
            WHERE statut = 'envoye' AND date_expiration <= NOW()
        ");
        $total_expires = $stmt->fetch()['total'];

    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des devis : " . $e->getMessage());
        $total_en_attente = 0;
        $total_acceptes = 0;
        $total_refuses = 0;
        $total_expires = 0;
        $total_devis = 0;
    }

    // Récupérer les devis selon les filtres
    $devis = [];
    try {
        $where_conditions = [];
        $params = [];
        
        // Vérifier s'il y a une recherche active
        $is_searching = !empty($client_search);
        
        // Si pas de recherche, appliquer le filtre de statut
        if (!$is_searching) {
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
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute($params);
        $devis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des devis : " . $e->getMessage());
        $devis = [];
    }
}
?>

<!-- Styles personnalisés pour la page devis - Version ULTRA RESPONSIVE -->
<style>
    /* RESET ET NORMALISATION FORCÉE POUR RESPONSIVITÉ */
    * {
        box-sizing: border-box !important;
    }
    
    html, body {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: hidden !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* CONTENEUR PRINCIPAL - RESPONSIVITÉ FORCÉE */
    .dashboard-wrapper {
        width: 100% !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
        padding: 20px !important;
        margin: 0 auto !important;
    }
    
    /* Compensation additionnelle pour la navbar sur desktop */
    @media screen and (min-width: 992px) {
        .dashboard-wrapper {
            padding-top: 40px !important; /* 20px (base) + 20px (compensation navbar) */
            margin-top: 10px !important; /* Espacement supplémentaire */
        }
    }
    
    /* Ajustement pour mobile - navbar en bas */
    @media (max-width: 991px) {
        .dashboard-wrapper {
            padding-top: 20px !important; /* Padding normal sur mobile */
            padding-bottom: 80px !important; /* Compensation pour le dock en bas */
        }
    }
    
    /* EMPÊCHER TOUT DÉBORDEMENT */
    .search-section,
    .modern-filters,
    .action-buttons-container,
    .results-container,
    .card,
    .card-body,
    .devis-grid {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: hidden !important;
    }
    /* FORCE LE FOND NOIR EN MODE NUIT - PRIORITÉ ABSOLUE */
    body.dark-mode {
        background: #0a0f19 !important;
        background-image: linear-gradient(135deg, #0a0f19, #111827, #0f172a) !important;
        background-attachment: fixed !important;
        background-size: 100% 100% !important;
    }
    
    .dark-mode body {
        background: #0a0f19 !important;
        background-image: linear-gradient(135deg, #0a0f19, #111827, #0f172a) !important;
        background-attachment: fixed !important;
        background-size: 100% 100% !important;
    }
    
    /* FORCE LE FOND MODERNE EN MODE CLAIR - PRIORITÉ ABSOLUE */
    body:not(.dark-mode) {
        background: #f1f5f9 !important;
        background-image: linear-gradient(135deg, #e2e8f0, #cbd5e1, #e2e8f0) !important;
        background-attachment: fixed !important;
        background-size: 100% 100% !important;
    }
    
    /* Améliorations mode clair - cartes */
    body:not(.dark-mode) .search-card,
    body:not(.dark-mode) .dashboard-card,
    body:not(.dark-mode) .card {
        background: rgba(255, 255, 255, 0.8) !important;
        border: 1px solid rgba(203, 213, 225, 0.6) !important;
        color: #334155 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
    }
    
    body:not(.dark-mode) .search-card:hover,
    body:not(.dark-mode) .dashboard-card:hover,
    body:not(.dark-mode) .card:hover {
        background: rgba(255, 255, 255, 0.95) !important;
        border-color: rgba(102, 126, 234, 0.4) !important;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.25) !important;
        transform: translateY(-5px) !important;
    }
    
    /* Améliorations mode clair - boutons de filtre */
    body:not(.dark-mode) .modern-filter {
        background: rgba(255, 255, 255, 0.9) !important;
        border: 1px solid rgba(203, 213, 225, 0.5) !important;
        color: #334155 !important;
    }
    
    body:not(.dark-mode) .modern-filter:hover {
        background: rgba(255, 255, 255, 1) !important;
        border-color: rgba(102, 126, 234, 0.5) !important;
        color: #667eea !important;
    }
    
    body:not(.dark-mode) .modern-filter.active {
        background: rgba(102, 126, 234, 0.15) !important;
        border-color: rgba(102, 126, 234, 0.6) !important;
        color: #667eea !important;
    }
    
    /* Améliorations mode clair - boutons d'action */
    body:not(.dark-mode) .action-buttons-container {
        background: rgba(255, 255, 255, 0.4) !important;
        border: 1px solid rgba(203, 213, 225, 0.4) !important;
    }
    
    body:not(.dark-mode) .action-button {
        background: rgba(255, 255, 255, 0.9) !important;
        border: 2px solid #e2e8f0;
        border-radius: 25px;
        text-decoration: none;
        color: #64748b;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    body:not(.dark-mode) .action-button:hover {
        background: rgba(255, 255, 255, 1) !important;
        border-color: rgba(102, 126, 234, 0.5) !important;
        color: #667eea !important;
    }

    /* Structure générale */
    .dashboard-wrapper {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Section de recherche */
    .search-section {
        margin-bottom: 20px;
    }

    .search-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .search-form {
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
    }

    .search-input {
        flex: 1;
        min-width: 250px !important;
        max-width: 100% !important;
        width: 100% !important;
        padding: 12px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 25px;
        font-size: 16px;
        transition: all 0.3s ease;
        box-sizing: border-box !important;
    }
    
    /* SURCHARGER LES STYLES EN LIGNE PROBLÉMATIQUES */
    input[type="date"].search-input {
        min-width: auto !important;
        width: 100% !important;
        max-width: 200px !important;
    }

    .search-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .search-btn {
        padding: 12px 25px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        border-radius: 25px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    /* Filtres modernes */
    .modern-filters {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        overflow-x: auto;
        padding: 10px 0;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
    }

    .modern-filter {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 25px;
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid #e2e8f0;
        border-radius: 50px;
        text-decoration: none;
        color: #64748b;
        font-weight: 600;
        transition: all 0.3s ease;
        white-space: nowrap;
        position: relative;
        overflow: hidden;
    }

    .modern-filter:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        text-decoration: none;
        color: #667eea;
        border-color: #667eea;
    }

    .modern-filter.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-color: transparent;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    .filter-icon {
        font-size: 18px;
    }

    .filter-name {
        font-size: 15px;
    }

    .filter-count {
        background: rgba(255, 255, 255, 0.3);
        color: currentColor;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
    }

    .modern-filter.active .filter-count {
        background: rgba(255, 255, 255, 0.3);
        color: white;
    }

    /* Boutons d'action */
    .action-buttons-container {
        background: rgba(255, 255, 255, 0.6);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 20px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .modern-action-buttons {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .action-button {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid #e2e8f0;
        border-radius: 25px;
        text-decoration: none;
        color: #64748b;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .action-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        text-decoration: none;
        color: #667eea;
        border-color: #667eea;
    }

    .action-button i {
        font-size: 16px;
    }

    /* Cartes de devis */
    .results-container {
        margin-top: 20px;
    }

    .devis-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(280px, 100%), 1fr));
        gap: 20px;
        margin-top: 20px;
        width: 100% !important;
        max-width: 100% !important;
        overflow: hidden !important;
    }
    
    /* FORCER UNE SEULE COLONNE SUR PETITS ÉCRANS */
    @media (max-width: 600px) {
        .devis-grid {
            grid-template-columns: 1fr !important;
            gap: 15px !important;
        }
    }

    .devis-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .devis-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        border-color: rgba(102, 126, 234, 0.3);
    }

    .devis-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #667eea, #764ba2);
    }

    .devis-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .devis-numero {
        font-size: 18px;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 5px;
    }

    .devis-statut {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .statut-envoye {
        background: linear-gradient(135deg, #fed7a1, #fbbf24);
        color: #92400e;
    }

    .statut-accepte {
        background: linear-gradient(135deg, #a7f3d0, #34d399);
        color: #065f46;
    }

    .statut-refuse {
        background: linear-gradient(135deg, #fecaca, #ef4444);
        color: #991b1b;
    }

    .statut-expire {
        background: linear-gradient(135deg, #d1d5db, #9ca3af);
        color: #374151;
    }

    .devis-client {
        margin-bottom: 20px;
    }

    .client-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .client-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        flex-shrink: 0;
    }

    .client-details h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
        font-weight: 600;
        color: #2d3748;
    }

    .client-details p {
        margin: 0;
        font-size: 14px;
        color: #64748b;
    }

    .devis-reparation {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .reparation-info {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }

    .reparation-info i {
        color: #667eea;
        font-size: 16px;
    }

    .devis-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .detail-item {
        text-align: center;
    }

    .detail-label {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .detail-value {
        font-size: 18px;
        font-weight: 700;
        color: #2d3748;
    }

    .detail-value.price {
        color: #059669;
    }

    .detail-value.warning {
        color: #d97706;
    }

    .detail-value.success {
        color: #059669;
    }

    .devis-actions {
        display: flex;
        gap: 10px;
    }

    .devis-action-btn {
        flex: 1;
        padding: 12px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-details {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .btn-details:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }

    .btn-renvoyer {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #92400e;
    }

    .btn-renvoyer:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(251, 191, 36, 0.4);
        color: #92400e;
        text-decoration: none;
    }

    /* État vide */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #64748b;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
        color: #94a3b8;
    }

    .empty-state h3 {
        margin-bottom: 10px;
        color: #475569;
    }

    .empty-state p {
        font-size: 16px;
        margin: 0;
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
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }

    .total-amount {
        background: rgba(255, 255, 255, 0.15);
        padding: 15px;
        border-radius: 10px;
        backdrop-filter: blur(10px);
    }

    .total-amount span {
        color: rgba(255, 255, 255, 0.8);
        font-size: 14px;
        text-transform: uppercase;
        font-weight: 500;
    }

    .total-amount h3 {
        color: #a7f3d0 !important;
        font-weight: 700;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* Cartes d'information */
    .info-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
        height: 100%;
        transition: all 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .info-card .card-title {
        color: #2d3748;
        font-weight: 600;
        margin-bottom: 15px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 10px;
    }

    /* Informations client détaillées */
    .client-info-detailed {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .client-avatar-large {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 28px;
        flex-shrink: 0;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .client-details-extended h5 {
        margin: 0 0 8px 0;
        color: #2d3748;
        font-weight: 600;
    }

    .client-details-extended p {
        margin: 0 0 5px 0;
        font-size: 14px;
    }

    .client-details-extended a {
        text-decoration: none;
        color: inherit;
    }

    .client-details-extended a:hover {
        text-decoration: underline;
    }

    /* Cartes de section */
    .section-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
    }

    .section-title {
        color: #2d3748;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e2e8f0;
    }

    /* Grille des pannes */
    .pannes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 15px;
    }

    .panne-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        border-left: 4px solid #e53e3e;
        transition: all 0.3s ease;
    }

    .panne-card:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 15px rgba(229, 62, 62, 0.2);
    }

    .panne-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .panne-title {
        margin: 0;
        color: #2d3748;
        font-weight: 600;
        font-size: 16px;
    }

    .panne-description {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.5;
    }

    /* Conteneur des solutions */
    .solutions-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .solution-card {
        background: #f8fafc;
        border-radius: 15px;
        padding: 20px;
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .solution-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.15);
    }

    .solution-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e2e8f0;
    }

    .solution-title {
        margin: 0;
        color: #2d3748;
        font-weight: 600;
        font-size: 18px;
    }

    .solution-price {
        font-size: 24px;
        font-weight: 700;
    }

    .solution-description {
        color: #64748b;
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .solution-elements {
        background: white;
        border-radius: 10px;
        padding: 15px;
        border: 1px solid #e2e8f0;
    }

    .elements-title {
        color: #4a5568;
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .element-name {
        font-weight: 600;
        color: #2d3748;
        width: 30%;
    }

    .element-description {
        font-size: 14px;
        width: 50%;
    }

    .element-price {
        font-weight: 600;
        color: #059669;
        width: 20%;
        font-size: 16px;
    }

    /* Contenu des notes et messages */
    .notes-content,
    .message-content {
        background: #f8fafc;
        border-radius: 10px;
        padding: 20px;
        border-left: 4px solid #667eea;
        font-size: 15px;
        line-height: 1.6;
        color: #4a5568;
    }

    /* Timeline pour l'historique */
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
        background: linear-gradient(180deg, #667eea, #764ba2);
    }

    .timeline-item {
        position: relative;
        margin-bottom: 25px;
        padding-left: 25px;
    }

    .timeline-marker {
        position: absolute;
        left: -8px;
        top: 0;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: white;
        border: 3px solid #667eea;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 8px;
        color: #667eea;
    }

    .timeline-content {
        background: white;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
    }

    .timeline-title {
        margin: 0 0 5px 0;
        color: #2d3748;
        font-weight: 600;
        font-size: 16px;
    }

    .timeline-description {
        margin: 0 0 10px 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.5;
    }

    .timeline-date {
        font-size: 12px;
        color: #a0aec0;
        font-weight: 500;
    }

    /* Responsive pour le modal détaillé */
    @media (max-width: 768px) {
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
        
        .timeline-item {
            padding-left: 20px;
        }
    }

    /* Améliorations responsives générales */
    @media (max-width: 1200px) and (min-width: 992px) {
        .dashboard-wrapper {
            padding: 15px !important;
            padding-top: 35px !important; /* Maintenir compensation navbar */
            max-width: 100vw !important;
        }
        
        .devis-grid {
            grid-template-columns: repeat(auto-fill, minmax(min(300px, 100%), 1fr)) !important;
            gap: 15px !important;
        }
    }

    @media (max-width: 768px) {
        .dashboard-wrapper {
            padding: 10px !important;
            width: 100% !important;
            max-width: 100vw !important;
            overflow-x: hidden !important;
        }
        
        /* FORCER LA LARGEUR DE TOUS LES CONTENEURS */
        .search-section,
        .modern-filters,
        .action-buttons-container,
        .results-container {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
        }
        
        /* Amélioration des filtres modernes */
        .modern-filters {
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 10px 5px;
            margin: 0 -10px 20px -10px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
            justify-content: flex-start !important;
        }
        
        .modern-filters::-webkit-scrollbar {
            display: none;
        }
        
        .modern-filter {
            flex-shrink: 0;
            min-width: fit-content;
            padding: 12px 20px;
        }
        
        /* Amélioration du formulaire de recherche */
        .search-form {
            flex-direction: column !important;
            gap: 10px !important;
            width: 100% !important;
        }
        
        .search-input {
            min-width: auto !important;
            width: 100% !important;
            max-width: 100% !important;
            margin-bottom: 5px !important;
            flex: none !important;
        }
        
        input[type="date"].search-input {
            min-width: auto !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        
        .search-btn {
            width: 100% !important;
            margin-top: 10px !important;
        }
        
        /* Amélioration des boutons d'action */
        .action-buttons-container {
            margin: 0 -10px 20px -10px;
            border-radius: 0;
        }
        
        .modern-action-buttons {
            flex-direction: column;
            gap: 10px;
        }
        
        .action-button {
            width: 100%;
            justify-content: center;
            padding: 15px 20px;
        }
        
        /* Amélioration de la grille des devis */
        .devis-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .devis-card {
            margin: 0 auto;
            max-width: 100%;
        }
        
        /* Amélioration des détails de devis */
        .devis-details {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .devis-actions {
            flex-direction: column;
            gap: 8px;
        }
        
        .devis-action-btn {
            padding: 15px;
            font-size: 16px;
        }
        
        /* Amélioration des cartes d'information client */
        .client-info {
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }
        
        .client-details h4 {
            font-size: 16px;
        }
        
        .client-details p {
            font-size: 13px;
        }
        
        /* Amélioration de la section réparation */
        .devis-reparation {
            text-align: center;
        }
        
        .reparation-info {
            justify-content: center;
            flex-wrap: wrap;
        }
    }

    @media (max-width: 480px) {
        .dashboard-wrapper {
            padding: 5px !important;
            width: 100% !important;
            max-width: 100vw !important;
            overflow-x: hidden !important;
            margin: 0 !important;
        }
        
        /* EMPÊCHER TOUT DÉBORDEMENT SUR TRÈS PETITS ÉCRANS */
        body {
            overflow-x: hidden !important;
            width: 100% !important;
            max-width: 100vw !important;
        }
        
        /* FORCER LA RESPONSIVITÉ DE TOUS LES ÉLÉMENTS */
        .search-card,
        .modern-filters,
        .action-buttons-container,
        .results-container,
        .card,
        .devis-grid,
        .devis-card {
            width: 100% !important;
            max-width: 100% !important;
            overflow: hidden !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
        
        /* Optimisation pour très petits écrans */
        .search-card {
            margin: 0 -5px;
            border-radius: 10px;
            padding: 15px;
        }
        
        .modern-filter {
            padding: 10px 15px;
            font-size: 14px;
        }
        
        .filter-count {
            padding: 3px 8px;
            font-size: 12px;
        }
        
        .devis-card {
            padding: 20px;
            border-radius: 15px;
        }
        
        .devis-numero {
            font-size: 16px;
        }
        
        .devis-statut {
            padding: 6px 12px;
            font-size: 11px;
        }
        
        .client-avatar {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }
        
        .detail-value {
            font-size: 16px;
        }
        
        .detail-label {
            font-size: 11px;
        }
        
        /* Optimisation des modals pour mobile */
        .modal-dialog {
            margin: 5px;
            max-width: calc(100% - 10px);
        }
        
        .modal-xl {
            max-width: calc(100% - 10px);
        }
        
        .modal-body {
            padding: 15px;
        }
        
        .modal-header {
            padding: 15px;
        }
        
        .modal-footer {
            padding: 15px;
            flex-direction: column;
            gap: 10px;
        }
        
        .modal-footer .btn {
            width: 100%;
            margin: 0;
        }
    }

    /* Amélioration du scroll horizontal pour les filtres */
    .modern-filters {
        position: relative;
    }
    
    /* Centrage par défaut sur desktop */
    @media (min-width: 769px) {
        .modern-filters {
            justify-content: center !important;
        }
    }
    
    /* Masquer le gradient si les filtres ne débordent pas */
    .modern-filters::after {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 30px;
        background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.8));
        pointer-events: none;
        z-index: 1;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    /* Afficher le gradient seulement quand il y a du scroll */
    .modern-filters:hover::after,
    .modern-filters.has-scroll::after {
        opacity: 1;
    }
    
    @media (max-width: 768px) {
        .modern-filters::after {
            background: linear-gradient(to right, transparent, rgba(241, 245, 249, 0.8));
        }
        
        .dark-mode .modern-filters::after {
            background: linear-gradient(to right, transparent, rgba(10, 15, 25, 0.8));
        }
    }

    /* Amélioration de l'accessibilité tactile */
    @media (max-width: 768px) {
        .devis-card {
            cursor: pointer;
            touch-action: manipulation;
        }
        
        .devis-action-btn,
        .action-button,
        .search-btn,
        .modern-filter {
            touch-action: manipulation;
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Amélioration des espacements tactiles */
        .devis-action-btn {
            min-height: 44px;
        }
        
        .action-button {
            min-height: 48px;
        }
        
        .modern-filter {
            min-height: 44px;
        }
    }

    /* ========================================
       STYLES POUR LE MODE NUIT 
       ======================================== */
    
    /* Section de recherche en mode nuit */
    body.dark-mode .search-section {
        background: transparent;
    }
    
    body.dark-mode .search-card {
        background: var(--dark-card-bg, #1f2937) !important;
        border-color: var(--dark-border-color, #374151) !important;
    }
    
    body.dark-mode .search-input {
        background: var(--dark-input-bg, #1f2937) !important;
        border-color: var(--dark-border-color, #374151) !important;
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .search-input::placeholder {
        color: var(--dark-text-muted, #9ca3af) !important;
    }
    
    body.dark-mode .search-input:focus {
        border-color: var(--primary, #6282ff) !important;
        box-shadow: 0 0 0 3px rgba(98, 130, 255, 0.25) !important;
    }
    
    /* Filtres modernes en mode nuit */
    body.dark-mode .modern-filter {
        background: var(--dark-card-bg, #1f2937) !important;
        border-color: var(--dark-border-color, #374151) !important;
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .modern-filter:hover {
        color: var(--primary, #6282ff) !important;
        border-color: var(--primary, #6282ff) !important;
        background: var(--dark-hover-bg, rgba(255, 255, 255, 0.05)) !important;
    }
    
    body.dark-mode .modern-filter.active {
        background: var(--primary, #6282ff) !important;
        color: white !important;
        border-color: var(--primary, #6282ff) !important;
    }
    
    body.dark-mode .filter-count {
        background: rgba(255, 255, 255, 0.15) !important;
        color: currentColor !important;
    }
    
    body.dark-mode .modern-filter.active .filter-count {
        background: rgba(255, 255, 255, 0.3) !important;
        color: white !important;
    }
    
    /* Boutons d'action en mode nuit */
    body.dark-mode .action-buttons-container {
        background: var(--dark-card-bg, #1f2937) !important;
        border-color: var(--dark-border-color, #374151) !important;
    }
    
    body.dark-mode .action-button {
        background: var(--dark-hover-bg, rgba(255, 255, 255, 0.05)) !important;
        border-color: var(--dark-border-color, #374151) !important;
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .action-button:hover {
        color: var(--primary, #6282ff) !important;
        border-color: var(--primary, #6282ff) !important;
        background: var(--dark-active-bg, rgba(255, 255, 255, 0.1)) !important;
    }
    
    /* Cartes de devis en mode nuit - FORCER LE FOND SOMBRE */
    body.dark-mode .devis-card {
        background: var(--dark-card-bg, #1f2937) !important;
        border-color: var(--dark-border-color, #374151) !important;
        color: var(--dark-text-primary, #f9fafb) !important;
        backdrop-filter: none !important;
        border: 1px solid var(--dark-border-color, #374151) !important;
    }
    
    body.dark-mode .devis-card:hover {
        background: var(--dark-hover-bg, rgba(255, 255, 255, 0.05)) !important;
        border-color: var(--primary, #6282ff) !important;
    }
    
    body.dark-mode .devis-header h3 {
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .devis-meta,
    body.dark-mode .devis-info p {
        color: var(--dark-text-secondary, #e5e7eb) !important;
    }
    
    body.dark-mode .devis-info strong {
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    /* Badge de statut en mode nuit */
    body.dark-mode .badge-envoye {
        background: var(--warning-dark, #f59e0b) !important;
        color: white !important;
    }
    
    body.dark-mode .badge-accepte {
        background: var(--success-dark, #10b981) !important;
        color: white !important;
    }
    
    body.dark-mode .badge-refuse {
        background: var(--danger-dark, #ef4444) !important;
        color: white !important;
    }
    
    body.dark-mode .badge-expire {
        background: var(--gray-dark, #6b7280) !important;
        color: white !important;
    }
    
    /* Boutons d'action des cartes en mode nuit */
    body.dark-mode .devis-action-btn {
        border-color: var(--dark-border-color, #374151) !important;
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .btn-details {
        background: var(--primary, #6282ff) !important;
        border-color: var(--primary, #6282ff) !important;
        color: white !important;
    }
    
    body.dark-mode .btn-renvoyer {
        background: var(--warning-dark, #f59e0b) !important;
        border-color: var(--warning-dark, #f59e0b) !important;
        color: white !important;
    }
    
    body.dark-mode .devis-action-btn:hover {
        background: var(--dark-active-bg, rgba(255, 255, 255, 0.1)) !important;
        border-color: var(--primary, #6282ff) !important;
    }
    
    /* Informations de montant en mode nuit */
    body.dark-mode .devis-montant {
        color: var(--primary, #6282ff) !important;
    }
    
    /* Container principal en mode nuit */
    body.dark-mode .dashboard-wrapper {
        background: transparent !important;
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    /* Titre de section en mode nuit */
    body.dark-mode .results-container h2 {
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    /* CORRECTIONS SUPPLÉMENTAIRES POUR TOUS LES FONDS CLAIRS */
    
    /* État vide en mode nuit */
    body.dark-mode .empty-state {
        color: var(--dark-text-secondary, #e5e7eb) !important;
    }
    
    body.dark-mode .empty-state i {
        color: var(--dark-text-muted, #9ca3af) !important;
    }
    
    body.dark-mode .empty-state h3 {
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    /* Détails spécifiques des cartes de devis */
    body.dark-mode .devis-numero {
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .client-details h4 {
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .client-details p {
        color: var(--dark-text-secondary, #e5e7eb) !important;
    }
    
    body.dark-mode .reparation-info strong,
    body.dark-mode .reparation-info span {
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    /* CORRECTION SPÉCIFIQUE : Section réparation avec fond gris */
    body.dark-mode .devis-reparation {
        background: var(--dark-bg-tertiary, #1e293b) !important;
        border: 1px solid var(--dark-border-color, #374151) !important;
    }
    
    /* Autres sections avec fonds gris clairs */
    body.dark-mode .panne-card,
    body.dark-mode .solution-card,
    body.dark-mode .notes-content,
    body.dark-mode .message-content {
        background: var(--dark-bg-tertiary, #1e293b) !important;
        border-color: var(--dark-border-color, #374151) !important;
    }
    
    /* Surcharger tous les fonds #f8fafc spécifiquement */
    body.dark-mode [style*="background: #f8fafc"],
    body.dark-mode [style*="background-color: #f8fafc"] {
        background-color: var(--dark-bg-tertiary, #1e293b) !important;
    }
    
    body.dark-mode .detail-label {
        color: var(--dark-text-secondary, #e5e7eb) !important;
    }
    
    body.dark-mode .detail-value {
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .detail-value.price {
        color: var(--primary, #6282ff) !important;
    }
    
    /* MASQUER LA BARRE DE NAVIGATION MOBILE DANS LES MODALS */
    body.modal-open #mobile-dock {
        display: none !important;
        visibility: hidden !important;
        z-index: -1 !important;
    }
    
    /* Forcer le masquage avec JavaScript quand modal ouvert */
    .hide-mobile-dock #mobile-dock {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        z-index: -1 !important;
    }
    
    /* Forcer masquage pour tous les modals de devis */
    .modal-open #mobile-dock,
    .modal.show #mobile-dock,
    .modal.fade.show #mobile-dock {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        z-index: -1 !important;
    }
    
    /* Surcharge spécifique pour les vraies tablettes et mobiles seulement */
    @media (max-width: 768px) {
        body.modal-open #mobile-dock,
        .hide-mobile-dock #mobile-dock,
        .modal-open #mobile-dock,
        .modal.show #mobile-dock,
        .modal.fade.show #mobile-dock {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: auto !important;
        }
    }
    
    /* Exception pour les vrais petits écrans tactiles */
    @media (max-width: 480px) {
        body.modal-open #mobile-dock,
        .hide-mobile-dock #mobile-dock {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
    }
    
    /* MODAL BOOTSTRAP FIXES COMPLETS */
    body.dark-mode .modal-content {
        background-color: var(--dark-card-bg, #1f2937) !important;
        border-color: var(--dark-border-color, #374151) !important;
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .modal-header {
        background-color: var(--dark-bg-tertiary, #1e293b) !important;
        border-bottom-color: var(--dark-border-color, #374151) !important;
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .modal-header.bg-light {
        background-color: var(--dark-bg-tertiary, #1e293b) !important;
    }
    
    body.dark-mode .modal-footer {
        background-color: var(--dark-bg-tertiary, #1e293b) !important;
        border-top-color: var(--dark-border-color, #374151) !important;
    }
    
    body.dark-mode .modal-title {
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .modal-body {
        color: var(--dark-text-primary, #f9fafb) !important;
        background-color: var(--dark-card-bg, #1f2937) !important;
    }
    
    /* Modal de détails du devis - styles spécifiques */
    body.dark-mode .devis-details-container {
        background-color: transparent !important;
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .devis-details-header {
        background: var(--primary, #6282ff) !important;
        color: white !important;
    }
    
    body.dark-mode .total-amount {
        background: rgba(255, 255, 255, 0.1) !important;
    }
    
    /* Boutons du modal en mode nuit */
    body.dark-mode .btn-secondary {
        background-color: var(--dark-hover-bg, rgba(255, 255, 255, 0.1)) !important;
        border-color: var(--dark-border-color, #374151) !important;
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .btn-secondary:hover {
        background-color: var(--dark-active-bg, rgba(255, 255, 255, 0.15)) !important;
        border-color: var(--dark-border-color, #374151) !important;
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    body.dark-mode .btn-primary {
        background-color: var(--primary, #6282ff) !important;
        border-color: var(--primary, #6282ff) !important;
    }
    
    body.dark-mode .btn-primary:hover {
        background-color: var(--primary-dark, #4361ee) !important;
        border-color: var(--primary-dark, #4361ee) !important;
    }
    
    body.dark-mode .btn-warning {
        background-color: var(--warning-dark, #f59e0b) !important;
        border-color: var(--warning-dark, #f59e0b) !important;
        color: white !important;
    }
    
    body.dark-mode .btn-warning:hover {
        background-color: var(--warning-darker, #d97706) !important;
        border-color: var(--warning-darker, #d97706) !important;
        color: white !important;
    }
    
    body.dark-mode .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
    
    /* Contenu généré dynamiquement du modal */
    body.dark-mode #devisDetailsContent,
    body.dark-mode #devisDetailsContent * {
        color: var(--dark-text-primary, #f9fafb) !important;
        background-color: transparent !important;
    }
    
    body.dark-mode #devisDetailsContent .card,
    body.dark-mode #devisDetailsContent .bg-light,
    body.dark-mode #devisDetailsContent .bg-white,
    body.dark-mode #devisDetailsContent [style*="background"] {
        background-color: var(--dark-card-bg, #1f2937) !important;
    }
    
    body.dark-mode #devisDetailsContent .table {
        color: var(--dark-text-primary, #f9fafb) !important;
        background-color: transparent !important;
    }
    
    body.dark-mode #devisDetailsContent .table th {
        background-color: var(--dark-bg-tertiary, #1e293b) !important;
        color: var(--dark-text-primary, #f9fafb) !important;
        border-color: var(--dark-border-color, #374151) !important;
    }
    
    body.dark-mode #devisDetailsContent .table td {
        color: var(--dark-text-primary, #f9fafb) !important;
        border-color: var(--dark-border-color, #374151) !important;
    }
    
    /* Spinner de chargement en mode nuit */
    body.dark-mode .spinner-border {
        color: var(--primary, #6282ff) !important;
    }
    
    body.dark-mode .text-muted {
        color: var(--dark-text-secondary, #e5e7eb) !important;
    }
    
    /* Surcharges pour TOUS les éléments avec background inline */
    body.dark-mode [style*="background: white"],
    body.dark-mode [style*="background: #fff"],
    body.dark-mode [style*="background: #ffffff"],
    body.dark-mode [style*="background: rgba(255, 255, 255"],
    body.dark-mode [style*="background-color: white"],
    body.dark-mode [style*="background-color: #fff"],
    body.dark-mode [style*="background-color: #ffffff"] {
        background-color: var(--dark-card-bg, #1f2937) !important;
    }
    
    body.dark-mode [style*="color: #2d3748"],
    body.dark-mode [style*="color: #1a202c"],
    body.dark-mode [style*="color: #2c3e50"] {
        color: var(--dark-text-primary, #f9fafb) !important;
    }
    
    /* Responsive fixes pour le mode nuit */
    @media (max-width: 768px) {
        body.dark-mode .search-card {
            background: var(--dark-card-bg, #1f2937) !important;
        }
        
        body.dark-mode .modern-filter,
        body.dark-mode .action-button {
            background: var(--dark-card-bg, #1f2937) !important;
        }
        
        body.dark-mode .modal-dialog {
            background: transparent !important;
        }
    }
</style>

<div class="dashboard-wrapper">
    <!-- Section de recherche -->
    <div class="search-section">
        <div class="search-card">
            <form class="search-form" method="GET" action="">
                <input type="hidden" name="page" value="devis">
                <input type="text" name="client_search" class="search-input" 
                       placeholder="Rechercher par nom, téléphone, appareil..." 
                       value="<?php echo htmlspecialchars($client_search); ?>">
                
                <input type="date" name="date_debut" class="search-input" 
                       value="<?php echo htmlspecialchars($date_debut); ?>"
                       style="min-width: 150px;">
                       
                <input type="date" name="date_fin" class="search-input" 
                       value="<?php echo htmlspecialchars($date_fin); ?>"
                       style="min-width: 150px;">
                
                <button class="search-btn" type="submit">
                    <i class="fas fa-search"></i>Rechercher
                </button>
            </form>
        </div>
    </div>

    <!-- Filtres modernes -->
    <div class="modern-filters">
        <!-- Bouton En Attente -->
        <a href="javascript:void(0);" 
           class="modern-filter <?php echo $statut_ids == 'envoye' ? 'active' : ''; ?>"
           data-statut="envoye">
            <i class="fas fa-clock filter-icon"></i>
            <span class="filter-name">En Attente</span>
            <span class="filter-count"><?php echo $total_en_attente ?? 0; ?></span>
        </a>
        
        <!-- Bouton Acceptés -->
        <a href="javascript:void(0);" 
           class="modern-filter <?php echo $statut_ids == 'accepte' ? 'active' : ''; ?>"
           data-statut="accepte">
            <i class="fas fa-check-circle filter-icon"></i>
            <span class="filter-name">Acceptés</span>
            <span class="filter-count"><?php echo $total_acceptes ?? 0; ?></span>
        </a>
        
        <!-- Bouton Refusés -->
        <a href="javascript:void(0);" 
           class="modern-filter <?php echo $statut_ids == 'refuse' ? 'active' : ''; ?>"
           data-statut="refuse">
            <i class="fas fa-times-circle filter-icon"></i>
            <span class="filter-name">Refusés</span>
            <span class="filter-count"><?php echo $total_refuses ?? 0; ?></span>
        </a>
        
        <!-- Bouton Expirés -->
        <a href="javascript:void(0);" 
           class="modern-filter <?php echo $statut_ids == 'expire' ? 'active' : ''; ?>"
           data-statut="expire">
            <i class="fas fa-exclamation-triangle filter-icon"></i>
            <span class="filter-name">Expirés</span>
            <span class="filter-count"><?php echo $total_expires ?? 0; ?></span>
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

    <!-- Conteneur pour les résultats -->
    <div class="results-container">
        <div class="card">
            <div class="card-body">
                <!-- Vue cartes -->
                <div id="cards-view">
                    <?php if (!empty($devis)): ?>
                        <div class="devis-grid">
                            <?php foreach ($devis as $devis_item): ?>
                                <div class="devis-card" onclick="window.afficherDetailsDevis(<?php echo $devis_item['id']; ?>)">
                                    <!-- En-tête avec numéro et statut -->
                                    <div class="devis-header">
                                        <div>
                                            <div class="devis-numero">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                                <?php echo htmlspecialchars($devis_item['numero_devis'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                        <div class="devis-statut statut-<?php echo $devis_item['statut']; ?>">
                                            <?php echo getStatutLabel($devis_item['statut']); ?>
                                        </div>
                                    </div>

                                    <!-- Informations client -->
                                    <div class="devis-client">
                                        <div class="client-info">
                                            <div class="client-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="client-details">
                                                <h4><?php echo htmlspecialchars(($devis_item['client_nom'] ?? '') . ' ' . ($devis_item['client_prenom'] ?? '')); ?></h4>
                                                <?php if (!empty($devis_item['client_telephone'])): ?>
                                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($devis_item['client_telephone']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Informations réparation -->
                                    <div class="devis-reparation">
                                        <div class="reparation-info">
                                            <i class="fas fa-tools"></i>
                                            <strong>Réparation #<?php echo $devis_item['reparation_id']; ?></strong>
                                        </div>
                                        <?php if (!empty($devis_item['reparation_appareil']) || !empty($devis_item['reparation_modele'])): ?>
                                            <div class="reparation-info">
                                                <i class="fas fa-mobile-alt"></i>
                                                <span><?php echo htmlspecialchars(($devis_item['reparation_appareil'] ?? '') . ' ' . ($devis_item['reparation_modele'] ?? '')); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Détails du devis -->
                                    <div class="devis-details">
                                        <div class="detail-item">
                                            <div class="detail-label">Montant</div>
                                            <div class="detail-value price">
                                                <?php echo number_format($devis_item['total_ttc'] ?? 0, 2, ',', ' '); ?>€
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="detail-label">
                                                <?php 
                                                    $expire_date = new DateTime($devis_item['date_expiration']);
                                                    $today = new DateTime();
                                                    $diff = $today->diff($expire_date);
                                                    
                                                    if ($expire_date < $today) {
                                                        echo "Expiré depuis";
                                                    } else {
                                                        echo "Expire dans";
                                                    }
                                                ?>
                                            </div>
                                            <div class="detail-value <?php echo $expire_date < $today ? 'warning' : 'success'; ?>">
                                                <?php 
                                                    if ($expire_date < $today) {
                                                        // Si le devis est expiré, afficher un bouton pour prolonger
                                                        echo '<button class="btn btn-sm btn-warning prolonger-btn" onclick="event.stopPropagation(); ouvrirModalProlonger(' . $devis_item['id'] . ', \'' . htmlspecialchars($devis_item['numero_devis']) . '\')">';
                                                        echo '<i class="fas fa-clock me-1"></i>Prolonger';
                                                        echo '</button>';
                                                    } else {
                                                        echo $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="devis-actions">
                                        <button class="devis-action-btn btn-details" onclick="event.stopPropagation(); window.afficherDetailsDevis(<?php echo $devis_item['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                            Détails
                                        </button>
                                        <button class="devis-action-btn btn-renvoyer" onclick="event.stopPropagation(); window.renvoyerDevisIndividuel(<?php echo $devis_item['id']; ?>)">
                                            <i class="fas fa-paper-plane"></i>
                                            Renvoyer
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <h3>Aucun devis trouvé</h3>
                            <p>Il n'y a aucun devis correspondant aux critères sélectionnés.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les détails du devis -->
<div class="modal fade" id="devisDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice-dollar"></i>
                    Détails du Devis
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="devisDetailsContent">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement des détails...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="window.telechargerDevisPDF()">
                    <i class="fas fa-print"></i>
                    Imprimer / PDF
                </button>
                <button type="button" class="btn btn-warning" onclick="window.renvoyerDevisIndividuel()">
                    <i class="fas fa-paper-plane"></i>
                    Renvoyer par SMS
                </button>
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
                    <p>Souhaitez-vous vraiment renvoyer tous les devis en attente par SMS ?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Cette action enverra un SMS à tous les clients ayant des devis en attente.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning" onclick="window.confirmerRenvoyerTous()">
                    <i class="fas fa-paper-plane"></i>
                    Confirmer l'envoi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Définition des fonctions dans le scope global pour éviter les erreurs
window.afficherDetailsDevis = function(devisId) {
    window.currentDevisId = devisId;
    
    // Réinitialiser le contenu
    document.getElementById('devisDetailsContent').innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-2">Chargement des détails...</p>
        </div>
    `;
    
    // Ouvrir le modal
    window.devisDetailsModal.show();
    
    // Charger les détails
    fetch(`ajax/get_devis_details.php?shop_id=${window.currentShopId}&id=${devisId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.afficherDetailsDevisContenu(data.devis);
            } else {
                document.getElementById('devisDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Erreur lors du chargement des détails du devis.
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
};

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

window.afficherDetailsDevisContenu = function(devis) {
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
};

window.telechargerDevisPDF = function() {
    if (!window.currentDevisId) return;
    
    // Récupérer le lien sécurisé du devis pour l'impression
    fetch(`ajax/get_devis_details.php?shop_id=${window.currentShopId}&devis_id=${window.currentDevisId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.devis && data.devis.lien_securise) {
                // Ouvrir la page d'impression avec le lien sécurisé
                window.open(`pages/devis_print.php?lien=${data.devis.lien_securise}&print=1`, '_blank');
            } else {
                console.error('Lien sécurisé non trouvé:', data);
                // Fallback vers l'ancien système
                window.open(`pages/devis_print.php?devis_id=${window.currentDevisId}&shop_id=${window.currentShopId}&print=1`, '_blank');
            }
        })
        .catch(error => {
            console.error('Erreur lors de la récupération du lien:', error);
            // Fallback vers l'ancien système
            window.open(`pages/devis_print.php?devis_id=${window.currentDevisId}&shop_id=${window.currentShopId}&print=1`, '_blank');
        });
};

window.renvoyerDevisIndividuel = function(devisId = null) {
    const id = devisId || window.currentDevisId;
    if (!id) return;
    
    if (!confirm('Êtes-vous sûr de vouloir renvoyer ce devis par SMS ?')) return;
    
    fetch(`ajax/renvoyer_devis.php?shop_id=${window.currentShopId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            devis_ids: [id]
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
};

window.renvoyerTousLesDevis = function() {
    window.renvoyerTousModal.show();
};

window.confirmerRenvoyerTous = function() {
    window.renvoyerTousModal.hide();
    
    fetch(`ajax/renvoyer_tous_devis.php?shop_id=${window.currentShopId}`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`${data.envoyes || 0} devis renvoyés avec succès !`);
            location.reload();
        } else {
            alert('Erreur lors du renvoi : ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors du renvoi des devis.');
    });
};

// Variables globales
window.currentDevisId = null;
window.devisDetailsModal = null;
window.renvoyerTousModal = null;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Définir le shop_id global pour les requêtes AJAX
    if (typeof window.currentShopId === 'undefined') {
        window.currentShopId = <?= $current_shop_id ?? 'null' ?>;
        
        if (!window.currentShopId) {
            console.error('Shop ID non défini. Vérifiez la session ou l\'URL.');
        }
    }
    
    // Initialiser les modals
    window.devisDetailsModal = new bootstrap.Modal(document.getElementById('devisDetailsModal'));
    window.renvoyerTousModal = new bootstrap.Modal(document.getElementById('renvoyerTousModal'));
    
    // Fonction pour masquer/afficher la barre de navigation mobile selon la taille d'écran
    function toggleMobileDockForModal(hide) {
        const mobileDock = document.getElementById('mobile-dock');
        if (!mobileDock) return;
        
        // Masquer sur tous les écrans larges (même ceux détectés comme tablette)
        const isRealMobile = window.innerWidth <= 768 && ('ontouchstart' in window || navigator.maxTouchPoints > 0);
        
        if (!isRealMobile) {
            if (hide) {
                console.log('🔧 Masquage forcé de la barre de navigation pour le modal');
                document.body.classList.add('hide-mobile-dock');
                mobileDock.style.display = 'none !important';
                mobileDock.style.visibility = 'hidden !important';
                mobileDock.style.opacity = '0 !important';
                mobileDock.style.zIndex = '-1';
                mobileDock.setAttribute('data-modal-hidden', 'true');
            } else {
                console.log('🔧 Restauration de la barre de navigation');
                document.body.classList.remove('hide-mobile-dock');
                mobileDock.style.display = '';
                mobileDock.style.visibility = '';
                mobileDock.style.opacity = '';
                mobileDock.style.zIndex = '';
                mobileDock.removeAttribute('data-modal-hidden');
            }
        } else {
            console.log('🔧 Écran tactile détecté - conservation de la barre');
        }
    }
    
    // Écouter les événements d'ouverture/fermeture des modals
    const modals = ['devisDetailsModal', 'renvoyerTousModal', 'prolongerModal'];
    modals.forEach(modalId => {
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            modalElement.addEventListener('show.bs.modal', () => {
                console.log('Modal ouvert:', modalId);
                toggleMobileDockForModal(true);
            });
            
            modalElement.addEventListener('hidden.bs.modal', () => {
                console.log('Modal fermé:', modalId);
                toggleMobileDockForModal(false);
            });
        }
    });
    
    // Attacher les événements aux filtres
    document.querySelectorAll('.modern-filter').forEach(filter => {
        filter.addEventListener('click', function(e) {
            e.preventDefault();
            const statut = this.getAttribute('data-statut');
            if (statut) {
                window.location.href = `index.php?page=devis&statut_ids=${statut}`;
            }
        });
    });
    
    // Fonction pour gérer le centrage des filtres et le gradient
    function handleFiltersLayout() {
        const filtersContainer = document.querySelector('.modern-filters');
        if (filtersContainer) {
            const hasHorizontalScroll = filtersContainer.scrollWidth > filtersContainer.clientWidth;
            
            if (hasHorizontalScroll) {
                filtersContainer.classList.add('has-scroll');
            } else {
                filtersContainer.classList.remove('has-scroll');
            }
        }
    }
    
    // Appeler la fonction au chargement et au redimensionnement
    handleFiltersLayout();
    window.addEventListener('resize', handleFiltersLayout);
});

// Fonction pour ouvrir le modal de prolongation
function ouvrirModalProlonger(devisId, numeroDevis) {
    document.getElementById('prolongerDevisId').value = devisId;
    document.getElementById('prolongerNumeroDevis').textContent = numeroDevis;
    document.getElementById('dureeJours').value = 7; // Valeur par défaut : 7 jours
    
    const modal = new bootstrap.Modal(document.getElementById('prolongerModal'));
    modal.show();
}

// Fonction pour prolonger le devis
function prolongerDevis() {
    const devisId = document.getElementById('prolongerDevisId').value;
    const dureeJours = document.getElementById('dureeJours').value;
    const numeroDevis = document.getElementById('prolongerNumeroDevis').textContent;
    
    if (!dureeJours || dureeJours < 1 || dureeJours > 365) {
        alert('Veuillez saisir une durée valide (entre 1 et 365 jours)');
        return;
    }
    
    // Désactiver le bouton pendant la requête
    const btnProlonger = document.getElementById('btnProlonger');
    const originalText = btnProlonger.innerHTML;
    btnProlonger.disabled = true;
    btnProlonger.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Prolongation...';
    
    fetch('ajax/prolonger_devis_simple.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            devis_id: parseInt(devisId),
            duree_jours: parseInt(dureeJours)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = data.message;
            
            // Ajouter le statut SMS au message
            if (data.sms_envoye) {
                message += '\n✅ SMS de notification envoyé au client.';
            } else if (data.sms_error) {
                message += '\n⚠️ Erreur SMS: ' + data.sms_error;
            }
            
            alert(message);
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('prolongerModal'));
            modal.hide();
            // Recharger la page pour voir les changements
            window.location.reload();
        } else {
            alert('Erreur : ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la prolongation du devis');
    })
    .finally(() => {
        // Réactiver le bouton
        btnProlonger.disabled = false;
        btnProlonger.innerHTML = originalText;
    });
}

</script>

<!-- Modal pour prolonger un devis -->
<div class="modal fade" id="prolongerModal" tabindex="-1" aria-labelledby="prolongerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="prolongerModalLabel">
                    <i class="fas fa-clock text-warning me-2"></i>
                    Prolonger le devis
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="prolongerDevisId">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Devis à prolonger :</label>
                    <p class="text-primary fs-5 mb-0" id="prolongerNumeroDevis"></p>
                </div>
                
                <div class="mb-3">
                    <label for="dureeJours" class="form-label">Nouvelle durée de validité :</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="dureeJours" min="1" max="365" value="7" required>
                        <span class="input-group-text">jour(s)</span>
                    </div>
                    <div class="form-text">Entre 1 et 365 jours</div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Le devis sera automatiquement remis en statut "Envoyé" et le client aura jusqu'à la nouvelle date d'expiration pour l'accepter.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" class="btn btn-warning" id="btnProlonger" onclick="prolongerDevis()">
                    <i class="fas fa-clock me-1"></i>Prolonger
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour le bouton Prolonger */
.prolonger-btn {
    font-size: 12px !important;
    padding: 4px 8px !important;
    border-radius: 4px !important;
    min-width: 80px !important;
    transition: all 0.3s ease !important;
}

.prolonger-btn:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
}

/* Dark mode pour le modal de prolongation */
body.dark-mode #prolongerModal .modal-content {
    background-color: var(--dark-card-bg) !important;
    border-color: var(--dark-border-color) !important;
    color: var(--dark-text-primary) !important;
}

body.dark-mode #prolongerModal .modal-header {
    background-color: var(--dark-bg-tertiary) !important;
    border-bottom-color: var(--dark-border-color) !important;
}

body.dark-mode #prolongerModal .modal-footer {
    background-color: var(--dark-bg-tertiary) !important;
    border-top-color: var(--dark-border-color) !important;
}

body.dark-mode #prolongerModal .form-control {
    background-color: var(--dark-bg-tertiary) !important;
    border-color: var(--dark-border-color) !important;
    color: var(--dark-text-primary) !important;
}

body.dark-mode #prolongerModal .form-control:focus {
    background-color: var(--dark-bg-tertiary) !important;
    border-color: var(--primary-color) !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
}

body.dark-mode #prolongerModal .input-group-text {
    background-color: var(--dark-bg-tertiary) !important;
    border-color: var(--dark-border-color) !important;
    color: var(--dark-text-primary) !important;
}

body.dark-mode #prolongerModal .alert-info {
    background-color: rgba(13, 202, 240, 0.1) !important;
    border-color: rgba(13, 202, 240, 0.2) !important;
    color: var(--dark-text-primary) !important;
}

body.dark-mode #prolongerModal .btn-close {
    filter: invert(1) !important;
}

</style> 