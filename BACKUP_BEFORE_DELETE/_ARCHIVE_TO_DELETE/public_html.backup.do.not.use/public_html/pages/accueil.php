<?php
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'accueil-optimized.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=accueil');
    exit();
}

// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT (mise en cache)
require_once __DIR__ . '/../includes/subscription_redirect_middleware.php';

// Vérifier l'accès avec cache
$cache_key = 'subscription_check_' . ($_SESSION['user_id'] ?? 'guest');
$subscription_valid = apcu_exists($cache_key) ? apcu_fetch($cache_key) : null;

if ($subscription_valid === null) {
    $subscription_valid = checkSubscriptionAccess();
    apcu_store($cache_key, $subscription_valid, 300); // Cache 5 minutes
}

if (!$subscription_valid) {
    exit;
}

// Fonction optimisée pour obtenir toutes les statistiques en une seule requête
function get_dashboard_stats_optimized() {
    static $cached_stats = null;
    static $cache_time = 0;
    
    // Cache de 2 minutes pour les statistiques
    if ($cached_stats !== null && (time() - $cache_time) < 120) {
        return $cached_stats;
    }
    
try {
    $shop_pdo = getShopDBConnection();
        
        // Une seule requête complexe pour obtenir toutes les statistiques
    $stmt = $shop_pdo->query("
            SELECT 
                -- Statistiques des réparations
                COUNT(CASE WHEN statut_categorie = 1 THEN 1 END) as nouvelles_reparations,
                COUNT(CASE WHEN statut_categorie = 2 THEN 1 END) as reparations_en_cours,
                COUNT(CASE WHEN statut_categorie = 3 THEN 1 END) as reparations_en_attente,
                COUNT(CASE WHEN statut_categorie IN (1,2,3) THEN 1 END) as reparations_actives,
                COUNT(CASE WHEN DATE(date_reception) = CURDATE() AND statut_categorie = 1 THEN 1 END) as nouvelles_aujourd_hui,
                COUNT(CASE WHEN DATE(date_modification) = CURDATE() AND statut = 'reparation_effectue' THEN 1 END) as effectuees_aujourd_hui,
                COUNT(CASE WHEN DATE(date_modification) = CURDATE() AND statut = 'restitue' THEN 1 END) as restituees_aujourd_hui,
                
                -- Statistiques des clients
                (SELECT COUNT(DISTINCT client_id) FROM reparations WHERE client_id IS NOT NULL) as total_clients,
                
                -- Statistiques des tâches
                (SELECT COUNT(*) FROM taches WHERE statut IN ('en_cours', 'en_attente') AND date_echeance >= CURDATE()) as taches_actives,
                
                -- Statistiques des commandes
                (SELECT COUNT(*) FROM commandes_pieces WHERE statut IN ('en_attente', 'urgent')) as commandes_urgentes
                
            FROM reparations 
        ");
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Requête séparée pour les devis (table peut ne pas exister)
        try {
            $devis_stmt = $shop_pdo->query("
                SELECT COUNT(CASE WHEN DATE(date_envoi) = CURDATE() AND statut = 'envoye' THEN 1 END) as devis_envoyes_aujourd_hui
                FROM devis 
            ");
            $devis_stats = $devis_stmt->fetch(PDO::FETCH_ASSOC);
            $stats['devis_envoyes_aujourd_hui'] = $devis_stats['devis_envoyes_aujourd_hui'] ?? 0;
        } catch (PDOException $e) {
            $stats['devis_envoyes_aujourd_hui'] = 0;
        }
        
        // Mettre en cache
        $cached_stats = $stats;
        $cache_time = time();
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques optimisées: " . $e->getMessage());
        return [
            'nouvelles_reparations' => 0,
            'reparations_en_cours' => 0,
            'reparations_en_attente' => 0,
            'reparations_actives' => 0,
            'total_clients' => 0,
            'taches_actives' => 0,
            'commandes_urgentes' => 0,
            'nouvelles_aujourd_hui' => 0,
            'effectuees_aujourd_hui' => 0,
            'restituees_aujourd_hui' => 0,
            'devis_envoyes_aujourd_hui' => 0
        ];
    }
}

// Fonction optimisée pour obtenir les données récentes
function get_recent_data_optimized() {
    static $cached_data = null;
    static $cache_time = 0;
    
    // Cache de 1 minute pour les données récentes
    if ($cached_data !== null && (time() - $cache_time) < 60) {
        return $cached_data;
    }
    
    try {
        $shop_pdo = getShopDBConnection();
        
        // Récupérer les réparations récentes avec informations client
        $reparations_stmt = $shop_pdo->query("
            SELECT r.*, c.nom, c.prenom, c.telephone, c.email,
                   CASE 
                       WHEN r.statut_categorie = 1 THEN 'Nouvelle'
                       WHEN r.statut_categorie = 2 THEN 'En cours'
                       WHEN r.statut_categorie = 3 THEN 'En attente'
                       WHEN r.statut_categorie = 4 THEN 'Terminée'
                       ELSE 'Autre'
                   END as statut_libelle
            FROM reparations r
            LEFT JOIN clients c ON r.client_id = c.id
            ORDER BY r.date_reception DESC
            LIMIT 5
        ");
        $reparations_recentes = $reparations_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les tâches en cours
        $taches_stmt = $shop_pdo->query("
            SELECT *, 
                   CASE 
                       WHEN date_echeance < CURDATE() THEN 'expired'
                       WHEN date_echeance = CURDATE() THEN 'today'
                       WHEN date_echeance <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'soon'
                       ELSE 'normal'
                   END as urgence_status
            FROM taches 
            WHERE statut IN ('en_cours', 'en_attente')
            ORDER BY date_echeance ASC, priorite DESC
            LIMIT 5
        ");
        $taches_recentes = $taches_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les commandes urgentes
        $commandes_stmt = $shop_pdo->query("
            SELECT c.*, cl.nom as client_nom, cl.prenom as client_prenom, f.nom as fournisseur_nom
            FROM commandes_pieces c
            LEFT JOIN clients cl ON c.client_id = cl.id
            LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id
            WHERE c.statut IN ('en_attente', 'urgent')
            ORDER BY 
                CASE WHEN c.statut = 'urgent' THEN 1 ELSE 2 END,
                c.date_creation DESC
            LIMIT 5
        ");
        $commandes_recentes = $commandes_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cached_data = [
            'reparations' => $reparations_recentes,
            'taches' => $taches_recentes,
            'commandes' => $commandes_recentes
        ];
        $cache_time = time();
        
        return $cached_data;
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des données récentes: " . $e->getMessage());
        return [
            'reparations' => [],
            'taches' => [],
            'commandes' => []
        ];
    }
}

// Récupérer toutes les données optimisées
$dashboard_stats = get_dashboard_stats_optimized();
$recent_data = get_recent_data_optimized();

// Fonction pour obtenir la couleur en fonction de la priorité
function get_priority_color($priority) {
    switch(strtolower($priority)) {
        case 'haute':
            return 'danger';
        case 'moyenne':
            return 'warning';
        case 'basse':
            return 'info';
        default:
            return 'secondary';
    }
}

// Fonction get_urgence_class() déjà définie dans includes/functions.php
?>

                                <?php 
// ⭐ AFFICHER LE BANDEAU D'AVERTISSEMENT SI L'ESSAI VA EXPIRER
displayTrialWarning(); 
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GeekBoard</title>
    
    <!-- Préchargement des ressources critiques -->
    <link rel="preload" href="assets/css/dashboard-optimized.css" as="style">
    <link rel="preload" href="assets/js/dashboard-optimized.js" as="script">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" as="style">
    
    <!-- CSS critique inline pour éviter le FOUC -->
<style>
        /* Styles critiques inline */
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #f8f9fa; }
        .loader { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); display: flex; justify-content: center; align-items: center; z-index: 9999; }
        .loader-spinner { width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .critical-content { opacity: 0; animation: fadeIn 0.5s ease-in-out 0.1s forwards; }
        @keyframes fadeIn { to { opacity: 1; } }
</style>

    <!-- CSS principal optimisé -->
    <link href="assets/css/dashboard-optimized.css" rel="stylesheet">
    
    <!-- Police Google Fonts avec display=swap pour éviter le blocage -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Loader optimisé -->
    <div id="pageLoader" class="loader">
        <div class="loader-spinner"></div>
</div>

    <!-- Contenu critique above-the-fold -->
    <div class="critical-content">
                <div class="container-fluid">
            <!-- Statistiques principales -->
            <div class="hero-stats">
                <div class="stats-grid">
                    <div class="stat-card animate-delay-1">
                        <div class="stat-number" data-stat="nouvelles_reparations"><?= $dashboard_stats['nouvelles_reparations'] ?></div>
                        <div class="stat-label">Nouvelles Réparations</div>
                            </div>
                    <div class="stat-card animate-delay-2">
                        <div class="stat-number" data-stat="reparations_en_cours"><?= $dashboard_stats['reparations_en_cours'] ?></div>
                        <div class="stat-label">En Cours</div>
                                    </div>
                    <div class="stat-card animate-delay-3">
                        <div class="stat-number" data-stat="reparations_actives"><?= $dashboard_stats['reparations_actives'] ?></div>
                        <div class="stat-label">Réparations Actives</div>
                                </div>
                    <div class="stat-card animate-delay-3">
                        <div class="stat-number" data-stat="total_clients"><?= $dashboard_stats['total_clients'] ?></div>
                        <div class="stat-label">Clients Total</div>
                            </div>
                                </div>
                            </div>
                            </div>
                        </div>

    <!-- Contenu lazy loading -->
    <div class="lazy-content" data-url="api/dashboard-recent-data.php">
        <div class="container-fluid">
                                <div class="row">
                <!-- Réparations récentes -->
                <div class="col-lg-6 mb-4">
                    <div class="card animate-on-scroll">
                        <div class="card-header">
                            <h5 class="mb-0">Réparations Récentes</h5>
                                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th data-sort="appareil">Appareil</th>
                                            <th data-sort="client">Client</th>
                                            <th data-sort="statut">Statut</th>
                                            <th data-sort="date">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recent_data['reparations'] as $reparation): ?>
                                        <tr data-status="<?= strtolower($reparation['statut_libelle']) ?>">
                                            <td data-value="appareil"><?= htmlspecialchars($reparation['appareil'] ?? 'N/A') ?></td>
                                            <td data-value="client"><?= htmlspecialchars(($reparation['prenom'] ?? '') . ' ' . ($reparation['nom'] ?? '')) ?></td>
                                            <td data-value="statut">
                                                <span class="badge badge-<?= get_priority_color($reparation['statut_libelle'] ?? '') ?>">
                                                    <?= htmlspecialchars($reparation['statut_libelle'] ?? 'N/A') ?>
                                                </span>
                                            </td>
                                            <td data-value="date"><?= date('d/m/Y', strtotime($reparation['date_reception'] ?? 'now')) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                <!-- Tâches en cours -->
                <div class="col-lg-6 mb-4">
                    <div class="card animate-on-scroll">
                        <div class="card-header">
                            <h5 class="mb-0">Tâches En Cours</h5>
                            </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th data-sort="titre">Tâche</th>
                                            <th data-sort="priorite">Priorité</th>
                                            <th data-sort="echeance">Échéance</th>
                                            <th data-sort="statut">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recent_data['taches'] as $tache): ?>
                                        <tr data-status="<?= $tache['urgence_status'] ?>">
                                            <td data-value="titre"><?= htmlspecialchars($tache['titre'] ?? 'N/A') ?></td>
                                            <td data-value="priorite">
                                                <span class="badge badge-<?= get_priority_color($tache['priorite'] ?? '') ?>">
                                                    <?= htmlspecialchars($tache['priorite'] ?? 'N/A') ?>
                                                </span>
                                            </td>
                                            <td data-value="echeance"><?= date('d/m/Y', strtotime($tache['date_echeance'] ?? 'now')) ?></td>
                                            <td data-value="statut">
                                                <span class="badge badge-<?= get_urgence_class($tache['urgence_status']) ?>">
                                                    <?= htmlspecialchars($tache['statut'] ?? 'N/A') ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

            <!-- Statistiques du jour -->
                                <div class="row">
                <div class="col-12">
                    <div class="card animate-on-scroll">
                        <div class="card-header">
                            <h5 class="mb-0">Statistiques du Jour</h5>
                                        </div>
                        <div class="card-body">
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-number" data-stat="nouvelles_aujourd_hui"><?= $dashboard_stats['nouvelles_aujourd_hui'] ?></div>
                                    <div class="stat-label">Nouvelles Réparations</div>
                                    </div>
                                <div class="stat-card">
                                    <div class="stat-number" data-stat="effectuees_aujourd_hui"><?= $dashboard_stats['effectuees_aujourd_hui'] ?></div>
                                    <div class="stat-label">Réparations Effectuées</div>
                                        </div>
                                <div class="stat-card">
                                    <div class="stat-number" data-stat="restituees_aujourd_hui"><?= $dashboard_stats['restituees_aujourd_hui'] ?></div>
                                    <div class="stat-label">Réparations Restituées</div>
                                    </div>
                                <div class="stat-card">
                                    <div class="stat-number" data-stat="devis_envoyes_aujourd_hui"><?= $dashboard_stats['devis_envoyes_aujourd_hui'] ?></div>
                                    <div class="stat-label">Devis Envoyés</div>
                                </div>
                            </div>
                        </div>
                            </div>
                            </div>
                        </div>
                        </div>
                    </div>
                    
    <!-- JavaScript optimisé chargé de manière asynchrone -->
    <script src="assets/js/dashboard-optimized.js" async></script>
    
    <!-- Préchargement des pages suivantes probables -->
    <link rel="prefetch" href="index.php?page=reparations">
    <link rel="prefetch" href="index.php?page=taches">
    
    <!-- Service Worker pour la mise en cache -->
<script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js').catch(console.error);
        }
</script>
</body>
</html>
