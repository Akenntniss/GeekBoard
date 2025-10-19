<?php
/**
 * Page devis iframe - Version corrigée avec chemins absolus
 */

// Démarrer la session
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

// Configuration de la base de données (copiée depuis config/database.php)
define('DB_HOST', 'localhost');
define('DB_NAME', 'geekboard_general');
define('DB_USER', 'root');
define('DB_PASS', 'Mamanmaman01#');

// Fonction de connexion principale simplifiée
function getMainDBConnectionSimple() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        // Forcer le fuseau horaire MySQL à Paris
        $pdo->exec("SET time_zone = 'Europe/Paris'");
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erreur connexion principale: " . $e->getMessage());
        return null;
    }
}

// Fonction pour formater le prix
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

try {
    // Connexion à la base principale
    $main_pdo = getMainDBConnectionSimple();
    if (!$main_pdo) {
        throw new Exception("Impossible de se connecter à la base principale");
    }
    
    // Récupérer les infos du magasin
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1 LIMIT 1");
    $stmt->execute([$subdomain]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        throw new Exception("Magasin non trouvé pour le sous-domaine: " . $subdomain);
    }
    
    // Stocker en session
    $_SESSION['shop_id'] = $shop['id'];
    $_SESSION['shop_name'] = $shop['name'];
    $_SESSION['current_database'] = $shop['db_name'];
    
    // Connexion directe au magasin
    $shop_dsn = "mysql:host={$shop['db_host']};port=" . ($shop['db_port'] ?? '3306') . ";dbname={$shop['db_name']};charset=utf8mb4";
    $shop_pdo = new PDO($shop_dsn, $shop['db_user'], $shop['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Paramètres de filtrage
    $statut_filter = isset($_GET['statut']) ? $_GET['statut'] : '';
    $statut_ids = isset($_GET['statut_ids']) ? $_GET['statut_ids'] : 'envoye'; // Par défaut, afficher les devis en attente
    $client_search = isset($_GET['client_search']) ? $_GET['client_search'] : '';
    $date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
    $date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
    
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
    
    // Construire la requête de filtrage
    $where_conditions = [];
    $params = [];
    
    // Filtre par statut
    if ($statut_ids === 'envoye') {
        $where_conditions[] = "d.statut = 'envoye' AND d.date_expiration > NOW()";
    } elseif ($statut_ids === 'expire') {
        $where_conditions[] = "d.statut = 'envoye' AND d.date_expiration <= NOW()";
    } elseif (!empty($statut_ids)) {
        $where_conditions[] = "d.statut = ?";
        $params[] = $statut_ids;
    }
    
    // Filtre par recherche client
    if (!empty($client_search)) {
        $where_conditions[] = "(c.nom LIKE ? OR c.prenom LIKE ? OR c.telephone LIKE ?)";
        $search_term = "%$client_search%";
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
    
    // Récupérer les devis selon les filtres
    $query = "
        SELECT d.id, d.date_creation, d.date_expiration, d.total_ttc, d.numero_devis, d.titre, d.statut,
               c.nom, c.prenom, c.telephone 
        FROM devis d
        LEFT JOIN clients c ON d.client_id = c.id 
        $where_clause
        ORDER BY d.date_creation DESC
        LIMIT 50
    ";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute($params);
    $devis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    // Afficher une page d'erreur
    echo '<!DOCTYPE html>
    <html><head><title>Erreur - Devis en attente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body class="bg-light">
    <div class="container mt-5">
        <div class="alert alert-danger">
            <h4><i class="fas fa-exclamation-triangle"></i> Erreur</h4>
            <p>Impossible de charger les devis en attente.</p>
            <p>Détails: ' . htmlspecialchars($e->getMessage()) . '</p>
            <p>Sous-domaine: ' . htmlspecialchars($subdomain) . '</p>
            <button class="btn btn-primary" onclick="window.location.reload()">Réessayer</button>
        </div>
    </div>
    </body></html>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis en attente - <?php echo htmlspecialchars($shop['name'] ?? 'Magasin'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background: #f8f9fa; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .devis-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .devis-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .status-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }
        .client-info {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .price-highlight {
            font-size: 1.25rem;
            font-weight: bold;
            color: #28a745;
        }
        
        /* Styles pour la recherche et les filtres */
        .search-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            padding: 1rem;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .search-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .search-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .modern-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .modern-filter {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            text-decoration: none;
            color: #495057;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 120px;
            transition: all 0.3s ease;
        }
        
        .modern-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            color: #495057;
            text-decoration: none;
        }
        
        .modern-filter.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .filter-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .filter-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .filter-count {
            font-size: 1.2rem;
            font-weight: bold;
            margin-top: 0.25rem;
        }
        
        .action-buttons-container {
            margin-bottom: 1rem;
        }
        
        .modern-action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .action-button {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            color: white;
            text-decoration: none;
        }
        
        .relance-auto-container {
            display: flex;
            align-items: center;
            background: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .relance-auto-container:hover {
            border-color: #007bff;
            box-shadow: 0 4px 8px rgba(0,123,255,0.2);
        }
        
        .relance-auto-switch {
            margin: 0;
        }
        
        .relance-auto-switch .form-check-label {
            font-weight: 600;
            color: #495057;
            cursor: pointer;
        }
        
        .relance-horaire-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .relance-horaire-item input[type="time"] {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 14px;
        }
        
        .relance-horaire-item .btn-remove {
            color: #dc3545;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            transition: all 0.2s ease;
        }
        
        .relance-horaire-item .btn-remove:hover {
            background: #dc3545;
            color: white;
        }
        
        /* Mode nuit */
        @media (prefers-color-scheme: dark) {
            body {
                background: #1a1a1a !important;
                color: #e0e0e0 !important;
            }
            
            .search-section {
                background: #2d3748 !important;
                border: 1px solid #4a5568 !important;
            }
            
            .search-input {
                background: #374151 !important;
                border-color: #4a5568 !important;
                color: #e0e0e0 !important;
            }
            
            .search-input:focus {
                background: #374151 !important;
                border-color: #6b7280 !important;
                color: #e0e0e0 !important;
            }
            
            .modern-filter {
                background: #2d3748 !important;
                border-color: #4a5568 !important;
                color: #e0e0e0 !important;
            }
            
            .modern-filter:hover {
                color: #e0e0e0 !important;
            }
            
            .header-section {
                background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%) !important;
            }
            
            .devis-card {
                background: #2d3748 !important;
                border: 1px solid #4a5568 !important;
                color: #e0e0e0 !important;
            }
            
            .devis-card:hover {
                background: #374151 !important;
                border-color: #6b7280 !important;
            }
            
            .client-info {
                color: #9ca3af !important;
            }
            
            .text-muted {
                color: #9ca3af !important;
            }
            
            .card-footer {
                border-top: 1px solid #4a5568 !important;
                background: transparent !important;
            }
            
            .btn-outline-primary {
                border-color: #3b82f6 !important;
                color: #3b82f6 !important;
            }
            
            .btn-outline-primary:hover {
                background: #3b82f6 !important;
                color: white !important;
            }
            
            .btn-outline-success {
                border-color: #10b981 !important;
                color: #10b981 !important;
            }
            
            .btn-outline-success:hover {
                background: #10b981 !important;
                color: white !important;
            }
        }
        
        /* Mode nuit forcé par classe CSS */
        body.dark-mode {
            background: #1a1a1a !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .search-section {
            background: #2d3748 !important;
            border: 1px solid #4a5568 !important;
        }
        
        body.dark-mode .search-input {
            background: #374151 !important;
            border-color: #4a5568 !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .search-input:focus {
            background: #374151 !important;
            border-color: #6b7280 !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .modern-filter {
            background: #2d3748 !important;
            border-color: #4a5568 !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .modern-filter:hover {
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .relance-auto-container {
            background: #2d3748 !important;
            border-color: #4a5568 !important;
        }
        
        body.dark-mode .relance-auto-container:hover {
            border-color: #3b82f6 !important;
        }
        
        body.dark-mode .relance-auto-switch .form-check-label {
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .relance-horaire-item {
            background: #374151 !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .relance-horaire-item input[type="time"] {
            background: #4a5568 !important;
            border-color: #6b7280 !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .header-section {
            background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%) !important;
        }
        
        body.dark-mode .devis-card {
            background: #2d3748 !important;
            border: 1px solid #4a5568 !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .devis-card:hover {
            background: #374151 !important;
            border-color: #6b7280 !important;
        }
        
        body.dark-mode .client-info {
            color: #9ca3af !important;
        }
        
        body.dark-mode .text-muted {
            color: #9ca3af !important;
        }
        
        body.dark-mode .card-footer {
            border-top: 1px solid #4a5568 !important;
            background: transparent !important;
        }
        
        body.dark-mode .btn-outline-primary {
            border-color: #3b82f6 !important;
            color: #3b82f6 !important;
        }
        
        body.dark-mode .btn-outline-primary:hover {
            background: #3b82f6 !important;
            color: white !important;
        }
        
        body.dark-mode .btn-outline-success {
            border-color: #10b981 !important;
            color: #10b981 !important;
        }
        
        body.dark-mode .btn-outline-success:hover {
            background: #10b981 !important;
            color: white !important;
        }
        
        /* Modal de détails en mode nuit */
        body.dark-mode .modal-content {
            background: #2d3748 !important;
            border: 1px solid #4a5568 !important;
        }
        
        body.dark-mode .modal-header {
            background: #374151 !important;
            border-bottom: 1px solid #4a5568 !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .modal-footer {
            background: #374151 !important;
            border-top: 1px solid #4a5568 !important;
        }
        
        body.dark-mode .card {
            background: #374151 !important;
            border: 1px solid #4a5568 !important;
        }
        
        body.dark-mode .card-header {
            background: #4a5568 !important;
            border-bottom: 1px solid #6b7280 !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .card-body {
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .btn-close {
            filter: invert(1) !important;
        }
        
        /* Styles pour le modal de détails (identiques à la page principale) */
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

        .info-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
            height: 100%;
        }

        .section-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .section-title {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .client-info-detailed {
            display: flex;
            align-items: center;
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
            font-size: 24px;
            margin-right: 15px;
        }

        .client-details-extended h5 {
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 600;
        }

        .total-amount {
            text-align: right;
        }

        .total-amount span {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .pannes-grid {
            display: grid;
            gap: 15px;
        }

        .panne-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            border-left: 4px solid #dc3545;
        }

        .panne-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }

        .panne-title {
            color: #dc3545;
            font-weight: 600;
            margin: 0;
            flex-grow: 1;
        }

        .solutions-container {
            display: grid;
            gap: 20px;
        }

        .solution-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .solution-card.solution-selected {
            border-color: #28a745;
            background: #f0fff4;
        }

        .solution-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .solution-title {
            color: #2d3748;
            font-weight: 600;
            margin: 0;
            flex-grow: 1;
        }

        .solution-price {
            margin-left: 15px;
        }

        .solution-description {
            color: #6c757d;
            margin-bottom: 15px;
        }

        .solution-elements {
            margin-top: 15px;
        }

        .elements-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .element-name {
            font-weight: 600;
            color: #2d3748;
        }

        .element-description {
            font-size: 0.9rem;
        }

        .element-price {
            font-weight: 600;
            color: #28a745;
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
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }

        .timeline-marker {
            position: absolute;
            left: -23px;
            top: 0;
            width: 30px;
            height: 30px;
            background: #007bff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }

        .timeline-content {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .timeline-title {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .timeline-description {
            color: #6c757d;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .timeline-date {
            font-size: 0.8rem;
        }

        /* Mode nuit pour les nouveaux éléments */
        body.dark-mode .info-card,
        body.dark-mode .section-card {
            background: #2d3748 !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .section-title {
            color: #e0e0e0 !important;
        }

        body.dark-mode .client-details-extended h5 {
            color: #e0e0e0 !important;
        }

        body.dark-mode .panne-card,
        body.dark-mode .solution-card {
            background: #374151 !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .solution-card.solution-selected {
            background: #1f2937 !important;
            border-color: #10b981 !important;
        }

        body.dark-mode .panne-title,
        body.dark-mode .solution-title {
            color: #e0e0e0 !important;
        }

        body.dark-mode .timeline-content {
            background: #374151 !important;
            color: #e0e0e0 !important;
        }

        body.dark-mode .timeline-title {
            color: #e0e0e0 !important;
        }

        body.dark-mode .element-name {
            color: #e0e0e0 !important;
        }

        @media (prefers-color-scheme: dark) {
            .info-card,
            .section-card {
                background: #2d3748 !important;
                color: #e0e0e0 !important;
            }

            .section-title {
                color: #e0e0e0 !important;
            }

            .client-details-extended h5 {
                color: #e0e0e0 !important;
            }

            .panne-card,
            .solution-card {
                background: #374151 !important;
                color: #e0e0e0 !important;
            }

            .solution-card.solution-selected {
                background: #1f2937 !important;
                border-color: #10b981 !important;
            }

            .panne-title,
            .solution-title {
                color: #e0e0e0 !important;
            }

            .timeline-content {
                background: #374151 !important;
                color: #e0e0e0 !important;
            }

            .timeline-title {
                color: #e0e0e0 !important;
            }

            .element-name {
                color: #e0e0e0 !important;
            }
        }
    </style>
</head>
<body>
    <!-- Section de recherche -->
    <div class="search-section">
        <form class="search-form" method="GET" action="">
            <input type="text" name="client_search" class="search-input" 
                   placeholder="Rechercher par nom, téléphone..." 
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
                <small class="d-block mt-1 opacity-75" style="font-size: 0.75em;">En attente + Expirés récents</small>
            </button>
            
            <!-- Toggle Relance Automatique -->
            <div class="relance-auto-container">
                <div class="form-check form-switch relance-auto-switch">
                    <input class="form-check-input" type="checkbox" id="relanceAutoToggle">
                    <label class="form-check-label" for="relanceAutoToggle">
                        <i class="fas fa-clock me-2"></i>
                        Relance Automatique
                    </label>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm ms-2" onclick="ouvrirConfigRelanceAuto()">
                    <i class="fas fa-cog"></i>
                    Configurer
                </button>
            </div>
        </div>
    </div>


    <div class="container-fluid px-3">
        <?php if (empty($devis)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Aucun devis en attente</h5>
                <p class="text-muted">Tous les devis ont été traités ou ont expiré.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($devis as $devis_item): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card devis-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-file-invoice me-2"></i>
                                        Devis #<?php echo htmlspecialchars($devis_item['id']); ?>
                                    </h6>
                                    <span class="badge bg-warning status-badge">
                                        En Attente
                                    </span>
                                </div>
                                
                                <div class="client-info mb-3">
                                    <i class="fas fa-user me-2"></i>
                                    <strong><?php echo htmlspecialchars($devis_item['nom'] . ' ' . $devis_item['prenom']); ?></strong>
                                    <?php if (!empty($devis_item['telephone'])): ?>
                                        <br><i class="fas fa-phone me-2"></i>
                                        <?php echo htmlspecialchars($devis_item['telephone']); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Créé le <?php echo date('d/m/Y', strtotime($devis_item['date_creation'])); ?>
                                    </small>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Expire le <?php echo date('d/m/Y', strtotime($devis_item['date_expiration'])); ?>
                                    </small>
                                </div>
                                
                                <?php if (!empty($devis_item['total_ttc'])): ?>
                                    <div class="text-center mt-3">
                                        <div class="price-highlight">
                                            <?php echo formatPrice($devis_item['total_ttc']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-footer bg-transparent">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary flex-fill" 
                                            onclick="afficherDetailsDevis(<?php echo $devis_item['id']; ?>)">
                                        <i class="fas fa-eye me-1"></i> Détails
                                    </button>
                                    <button class="btn btn-sm btn-outline-success flex-fill"
                                            onclick="sendReminder(<?php echo $devis_item['id']; ?>)">
                                        <i class="fas fa-paper-plane me-1"></i> Relancer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de configuration des relances automatiques -->
    <div class="modal fade" id="configRelanceAutoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-clock text-primary"></i>
                        Configuration des Relances Automatiques
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Fonctionnement :</strong> Les relances automatiques envoient des SMS aux clients 
                        pour les devis <strong>en attente</strong> et les devis <strong>expirés depuis moins de 15 jours</strong> 
                        aux heures que vous définissez. Maximum 10 relances par jour.
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="relanceAutoActive">
                            <label class="form-check-label fw-bold" for="relanceAutoActive">
                                Activer les relances automatiques
                            </label>
                        </div>
                    </div>
                    
                    <div id="relanceConfigSection" style="display: none;">
                        <h6 class="mb-3">
                            <i class="fas fa-clock me-2"></i>
                            Horaires des relances
                        </h6>
                        
                        <div id="relancesHoraires">
                            <!-- Les horaires seront ajoutés dynamiquement ici -->
                        </div>
                        
                        <button type="button" class="btn btn-outline-success btn-sm mt-3" onclick="ajouterRelance()" id="btnAjouterRelance">
                            <i class="fas fa-plus me-2"></i>
                            Ajouter une relance
                        </button>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Les relances sont envoyées pour :
                                <br>• Les devis avec le statut "En Attente" (non expirés)
                                <br>• Les devis expirés depuis moins de 15 jours
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="sauvegarderConfigRelance()">
                        <i class="fas fa-save me-2"></i>
                        Sauvegarder
                    </button>
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
                    <button type="button" class="btn btn-primary" onclick="telechargerDevisPDF()">
                        <i class="fas fa-print"></i>
                        Imprimer / PDF
                    </button>
                    <button type="button" class="btn btn-warning" onclick="renvoyerDevisIndividuel()">
                        <i class="fas fa-paper-plane"></i>
                        Renvoyer par SMS
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Détection du mode nuit
        function detectDarkMode() {
            try {
                // Essayer de détecter le mode nuit de la page parent
                if (window.parent && window.parent !== window) {
                    const parentBody = window.parent.document.body;
                    if (parentBody && parentBody.classList.contains('dark-mode')) {
                        document.body.classList.add('dark-mode');
                        console.log('🌙 Mode nuit détecté depuis la page parent');
                        return;
                    }
                }
            } catch (e) {
                // Erreur d'accès cross-origin, utiliser d'autres méthodes
                console.log('🔒 Cross-origin, utilisation de la détection locale');
            }
            
            // Utiliser la préférence système si pas de détection parent
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.body.classList.add('dark-mode');
                console.log('🌙 Mode nuit détecté via préférence système');
            }
            
            // Écouter les changements de préférence système
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                    if (e.matches) {
                        document.body.classList.add('dark-mode');
                        console.log('🌙 Passage en mode nuit');
                    } else {
                        document.body.classList.remove('dark-mode');
                        console.log('☀️ Passage en mode jour');
                    }
                });
            }
        }
        
        // Appliquer le mode nuit au chargement
        document.addEventListener('DOMContentLoaded', function() {
            detectDarkMode();
        });
        
        // Réessayer après un délai pour laisser le temps à la page parent de se charger
        setTimeout(detectDarkMode, 500);
        
        // Gestion des filtres de statut
        document.addEventListener('DOMContentLoaded', function() {
            const filters = document.querySelectorAll('.modern-filter');
            
            filters.forEach(filter => {
                filter.addEventListener('click', function(e) {
                    e.preventDefault();
                    const statut = this.getAttribute('data-statut');
                    
                    // Construire l'URL avec le nouveau filtre
                    const url = new URL(window.location);
                    url.searchParams.set('statut_ids', statut);
                    
                    // Conserver les autres paramètres de recherche
                    const clientSearch = document.querySelector('input[name="client_search"]').value;
                    const dateDebut = document.querySelector('input[name="date_debut"]').value;
                    const dateFin = document.querySelector('input[name="date_fin"]').value;
                    
                    if (clientSearch) url.searchParams.set('client_search', clientSearch);
                    if (dateDebut) url.searchParams.set('date_debut', dateDebut);
                    if (dateFin) url.searchParams.set('date_fin', dateFin);
                    
                    // Recharger la page avec les nouveaux filtres
                    window.location.href = url.toString();
                });
            });
        });
        
        // Variables globales
        let currentDevisId = null;
        let devisDetailsModal = null;
        
        // Variables globales pour la relance automatique
        let configRelanceAutoModal = null;
        let relanceConfig = {
            est_active: false,
            relances_horaires: ['09:00', '14:00', '17:00']
        };
        
        // Initialiser les modals Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            devisDetailsModal = new bootstrap.Modal(document.getElementById('devisDetailsModal'));
            configRelanceAutoModal = new bootstrap.Modal(document.getElementById('configRelanceAutoModal'));
            
            // Charger la configuration actuelle
            chargerConfigRelanceAuto();
        });
        
        // Fonction utilitaire pour formater les prix
        function formatPrice(price) {
            if (price === null || price === undefined || price === '') return '';
            
            const numPrice = parseFloat(price);
            return numPrice.toLocaleString('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            });
        }
        
        // Fonctions helper pour le modal (identiques à la page principale)
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

        function getActionIcon(action) {
            const icons = {
                'creation': 'fa-plus-circle',
                'envoi': 'fa-paper-plane',
                'acceptation': 'fa-check-circle',
                'refus': 'fa-times-circle',
                'expiration': 'fa-clock',
                'prolongation': 'fa-calendar-plus',
                'modification': 'fa-edit',
                'suppression': 'fa-trash',
                'renvoi': 'fa-redo'
            };
            return icons[action] || 'fa-info-circle';
        }

        function getGraviteBadgeClass(gravite) {
            const classes = {
                'Critique': 'bg-danger',
                'Importante': 'bg-warning',
                'Normal': 'bg-info',
                'Mineure': 'bg-secondary'
            };
            return classes[gravite] || 'bg-info';
        }
        
        // Fonction pour afficher les détails du devis dans un modal
        function afficherDetailsDevis(devisId) {
            console.log('🔍 Ouverture des détails du devis:', devisId);
            currentDevisId = devisId;
            
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
            if (devisDetailsModal) {
                devisDetailsModal.show();
            }
            
            // Charger les détails via AJAX
            fetch(`../ajax/get_devis_details.php?shop_id=${<?php echo $shop['id']; ?>}&id=${devisId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        afficherDetailsDevisContenu(data.devis);
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
        }
        
        // Fonction pour afficher le contenu des détails (identique à la page principale)
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
                                    <span class="text-muted">MONTANT TOTAL</span>
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

                    <!-- Timeline des dates -->
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
                                <p class="mb-0">${devis.date_envoi ? new Date(devis.date_envoi).toLocaleDateString('fr-FR') : new Date(devis.date_creation).toLocaleDateString('fr-FR')}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-card text-center">
                                <i class="fas fa-clock ${isExpired ? 'text-danger' : 'text-warning'} fs-3 mb-2"></i>
                                <h6 class="mb-1">${isExpired ? 'Expiré depuis' : 'Expire dans'}</h6>
                                <p class="mb-0 ${isExpired ? 'text-danger' : 'text-warning'} fw-bold">${diffDays} jour${diffDays > 1 ? 's' : ''}</p>
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

                    <!-- Pannes identifiées -->
                    ${devis.pannes && devis.pannes.length > 0 ? `
                        <div class="section-card mb-4">
                            <h6 class="section-title">
                                <i class="fas fa-bug text-danger me-2"></i>
                                PANNES IDENTIFIÉES (${devis.pannes.length})
                            </h6>
                            <div class="pannes-grid">
                                ${devis.pannes.map(panne => `
                                    <div class="panne-card">
                                        <div class="panne-header">
                                            <h6 class="panne-title">${panne.nom || panne.titre}</h6>
                                            <span class="badge ${getGraviteBadgeClass(panne.gravite)}">${panne.gravite || 'Normal'}</span>
                                        </div>
                                        ${panne.description ? `<p class="panne-description">${panne.description}</p>` : ''}
                                        ${panne.cout ? `<div class="text-end"><span class="badge bg-secondary">Coût: ${panne.cout}</span></div>` : ''}
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
                                SOLUTIONS PROPOSÉES (${devis.solutions.length})
                            </h6>
                            <div class="solutions-container">
                                ${devis.solutions.map((solution, index) => `
                                    <div class="solution-card ${solution.choisie ? 'solution-selected' : ''}">
                                        <div class="solution-header">
                                            <h6 class="solution-title">
                                                Solution ${String.fromCharCode(65 + index)} - ${solution.nom}
                                                ${solution.recommandee ? '<span class="badge bg-success ms-2">Recommandée</span>' : ''}
                                                ${solution.choisie ? '<span class="badge bg-primary ms-2">Choisie</span>' : ''}
                                            </h6>
                                            <div class="solution-price">
                                                <span class="text-success fw-bold fs-5">${parseFloat(solution.prix_total || solution.prix_ttc || 0).toFixed(2)}€</span>
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
        
        // Fonctions pour les boutons du modal
        function telechargerDevisPDF() {
            if (currentDevisId) {
                window.open(`devis_client.php?id=${currentDevisId}`, '_blank');
            }
        }
        
        function renvoyerDevisIndividuel() {
            if (currentDevisId) {
                if (confirm('Voulez-vous renvoyer ce devis par SMS ?')) {
                    // Implémenter la logique de renvoi
                    alert('Fonction de renvoi à implémenter');
                }
            }
        }
        
        function sendReminder(devisId) {
            if (!confirm('Êtes-vous sûr de vouloir renvoyer ce devis par SMS ?')) return;
            
            const shopId = <?php echo json_encode($_SESSION['shop_id'] ?? null); ?>;
            if (!shopId) {
                alert('Erreur: ID du magasin non trouvé');
                return;
            }
            
            // Afficher un indicateur de chargement sur le bouton
            const button = event.target.closest('.btn-outline-success');
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Envoi...';
            button.disabled = true;
            
            fetch(`../ajax/renvoyer_devis.php?shop_id=${shopId}`, {
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
                    alert('✅ Devis renvoyé avec succès !');
                } else {
                    alert('❌ Erreur lors du renvoi : ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('❌ Erreur de connexion lors du renvoi du devis.');
            })
            .finally(() => {
                // Restaurer le bouton
                button.innerHTML = originalContent;
                button.disabled = false;
            });
        }
        
        // Fonction pour renvoyer tous les devis
        function renvoyerTousLesDevis() {
            if (!confirm('Êtes-vous sûr de vouloir renvoyer TOUS les devis par SMS ?\n\n📋 Seront renvoyés :\n• Les devis EN ATTENTE (non expirés)\n• Les devis EXPIRÉS depuis moins de 15 jours\n\n❌ Ne seront PAS renvoyés :\n• Les devis acceptés, refusés\n• Les devis expirés depuis plus de 15 jours\n\nCette action peut prendre du temps et consommer des crédits SMS.')) {
                return;
            }
            
            // Récupérer l'ID du magasin depuis la session PHP
            const shopId = <?php echo json_encode($_SESSION['shop_id'] ?? null); ?>;
            
            if (!shopId) {
                alert('Erreur: ID du magasin non trouvé');
                return;
            }
            
            // Afficher un indicateur de chargement
            const button = document.querySelector('.action-button');
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Envoi en cours...</span>';
            button.disabled = true;
            
            // Envoyer la requête vers la bonne API
            fetch(`../ajax/renvoyer_tous_devis.php?shop_id=${shopId}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`✅ ${data.envoyes || 0} devis renvoyés avec succès !`);
                    // Recharger la page pour mettre à jour les compteurs
                    window.location.reload();
                } else {
                    alert('❌ Erreur lors du renvoi : ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'envoi:', error);
                alert('❌ Erreur de connexion. Veuillez réessayer.');
            })
            .finally(() => {
                // Restaurer le bouton
                button.innerHTML = originalContent;
                button.disabled = false;
            });
        }
        
        // Fonctions pour la relance automatique
        function chargerConfigRelanceAuto() {
            const shopId = <?php echo json_encode($_SESSION['shop_id'] ?? null); ?>;
            if (!shopId) return;
            
            fetch(`../ajax/get_relance_auto_config.php?shop_id=${shopId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        relanceConfig = data.config;
                        
                        // Mettre à jour le toggle principal
                        document.getElementById('relanceAutoToggle').checked = relanceConfig.est_active;
                        
                        // Mettre à jour le label du toggle
                        const label = document.querySelector('label[for="relanceAutoToggle"]');
                        if (relanceConfig.est_active) {
                            label.innerHTML = '<i class="fas fa-clock me-2 text-success"></i>Relance Automatique <small class="text-success">(Active)</small>';
                        } else {
                            label.innerHTML = '<i class="fas fa-clock me-2"></i>Relance Automatique <small class="text-muted">(Inactive)</small>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement de la config:', error);
                });
        }
        
        function ouvrirConfigRelanceAuto() {
            // Remplir le modal avec la configuration actuelle
            document.getElementById('relanceAutoActive').checked = relanceConfig.est_active;
            
            // Afficher/masquer la section de configuration
            toggleConfigSection();
            
            // Remplir les horaires
            afficherRelancesHoraires();
            
            // Ouvrir le modal
            configRelanceAutoModal.show();
        }
        
        function toggleConfigSection() {
            const isActive = document.getElementById('relanceAutoActive').checked;
            const section = document.getElementById('relanceConfigSection');
            
            if (isActive) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        }
        
        function afficherRelancesHoraires() {
            const container = document.getElementById('relancesHoraires');
            container.innerHTML = '';
            
            relanceConfig.relances_horaires.forEach((heure, index) => {
                const item = document.createElement('div');
                item.className = 'relance-horaire-item';
                item.innerHTML = `
                    <span class="me-2">Relance ${index + 1} :</span>
                    <input type="time" value="${heure}" onchange="modifierHoraire(${index}, this.value)">
                    ${relanceConfig.relances_horaires.length > 1 ? `
                        <button type="button" class="btn-remove" onclick="supprimerRelance(${index})" title="Supprimer cette relance">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : ''}
                `;
                container.appendChild(item);
            });
            
            // Gérer le bouton d'ajout
            const btnAjouter = document.getElementById('btnAjouterRelance');
            if (relanceConfig.relances_horaires.length >= 10) {
                btnAjouter.style.display = 'none';
            } else {
                btnAjouter.style.display = 'inline-block';
            }
        }
        
        function ajouterRelance() {
            if (relanceConfig.relances_horaires.length < 10) {
                // Ajouter une nouvelle relance à 12:00 par défaut
                relanceConfig.relances_horaires.push('12:00');
                afficherRelancesHoraires();
            }
        }
        
        function supprimerRelance(index) {
            if (relanceConfig.relances_horaires.length > 1) {
                relanceConfig.relances_horaires.splice(index, 1);
                afficherRelancesHoraires();
            }
        }
        
        function modifierHoraire(index, nouvelleHeure) {
            relanceConfig.relances_horaires[index] = nouvelleHeure;
        }
        
        function sauvegarderConfigRelance() {
            const shopId = <?php echo json_encode($_SESSION['shop_id'] ?? null); ?>;
            if (!shopId) {
                alert('Erreur: ID du magasin non trouvé');
                return;
            }
            
            // Récupérer l'état du toggle
            const estActive = document.getElementById('relanceAutoActive').checked;
            
            // Trier les horaires
            relanceConfig.relances_horaires.sort();
            
            const configData = {
                est_active: estActive,
                relances_horaires: relanceConfig.relances_horaires
            };
            
            // Sauvegarder via AJAX
            fetch(`../ajax/save_relance_auto_config.php?shop_id=${shopId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(configData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour la configuration locale
                    relanceConfig.est_active = estActive;
                    
                    // Fermer le modal
                    configRelanceAutoModal.hide();
                    
                    // Recharger l'affichage
                    chargerConfigRelanceAuto();
                    
                    // Message de succès
                    alert('✅ Configuration des relances automatiques sauvegardée avec succès !');
                } else {
                    alert('❌ Erreur lors de la sauvegarde : ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur lors de la sauvegarde:', error);
                alert('❌ Erreur de connexion lors de la sauvegarde.');
            });
        }
        
        // Event listener pour le toggle principal
        document.addEventListener('DOMContentLoaded', function() {
            const togglePrincipal = document.getElementById('relanceAutoToggle');
            if (togglePrincipal) {
                togglePrincipal.addEventListener('change', function() {
                    const shopId = <?php echo json_encode($_SESSION['shop_id'] ?? null); ?>;
                    if (!shopId) return;
                    
                    const estActive = this.checked;
                    
                    // Sauvegarder immédiatement le changement
                    fetch(`../ajax/toggle_relance_auto.php?shop_id=${shopId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ est_active: estActive })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            chargerConfigRelanceAuto(); // Recharger l'affichage
                        } else {
                            // Remettre l'ancien état en cas d'erreur
                            this.checked = !estActive;
                            alert('❌ Erreur lors de la modification : ' + (data.message || 'Erreur inconnue'));
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        this.checked = !estActive;
                        alert('❌ Erreur de connexion.');
                    });
                });
            }
            
            // Event listener pour le toggle dans le modal
            const toggleModal = document.getElementById('relanceAutoActive');
            if (toggleModal) {
                toggleModal.addEventListener('change', toggleConfigSection);
            }
        });
        
        // Rafraîchir la page toutes les 5 minutes
        setTimeout(() => {
            window.location.reload();
        }, 5 * 60 * 1000);
    </script>
</body>
</html>
