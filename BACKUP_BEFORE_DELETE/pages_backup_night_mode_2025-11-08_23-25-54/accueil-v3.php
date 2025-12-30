<?php
/**
 * ====================================================================
 * 🚀 GEEKBOARD ACCUEIL V3 - VERSION COMPLÈTEMENT INDÉPENDANTE
 * ====================================================================
 * 
 * Page d'accueil moderne et futuriste avec toutes les fonctionnalités
 * intégrées sans dépendance aux fichiers existants
 * 
 * Fonctionnalités incluses :
 * - Dashboard avec statistiques en temps réel
 * - Actions rapides (Recherche, Nouvelle tâche, Nouvelle réparation, Nouvelle commande)
 * - Tableaux des tâches et réparations récentes
 * - Design futuriste avec animations
 * - Mode sombre/clair automatique
 * - Responsive design
 * - Modals intégrés
 */

// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'accueil-v3.php') {
    header('Location: ../index.php?page=accueil-v3');
    exit();
}

// Inclusions essentielles
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser la session shop si nécessaire
if (!isset($_SESSION['shop_id'])) {
    initializeShopSession();
}

// ====================================================================
// FONCTIONS UTILITAIRES INTÉGRÉES
// ====================================================================

/**
 * Obtenir la couleur Bootstrap selon la priorité
 */
function getPriorityColor($priority) {
    switch(strtolower($priority)) {
        case 'haute': return 'danger';
        case 'moyenne': return 'warning';
        case 'basse': return 'info';
        default: return 'secondary';
    }
}

/**
 * Obtenir les statistiques du dashboard
 */
function getDashboardStats() {
    try {
        $pdo = getShopDBConnection();
        
        // Statistiques des réparations
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut_id IN (1,2,3,19,20) THEN 1 ELSE 0 END) as actives,
                SUM(CASE WHEN statut_id = 1 THEN 1 ELSE 0 END) as nouvelles,
                SUM(CASE WHEN statut_id IN (2,3) THEN 1 ELSE 0 END) as en_cours,
                SUM(CASE WHEN statut_id = 19 THEN 1 ELSE 0 END) as en_attente
            FROM reparations 
            WHERE DATE(date_creation) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $reparations_stats = $stmt->fetch();
        
        // Statistiques des tâches
        $stmt = $pdo->query("
            SELECT COUNT(*) as total 
            FROM taches 
            WHERE statut != 'terminee' 
            AND DATE(date_creation) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $taches_stats = $stmt->fetch();
        
        // Statistiques des clients
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
        $clients_stats = $stmt->fetch();
        
        // Statistiques des commandes
        $stmt = $pdo->query("
            SELECT COUNT(*) as total 
            FROM commandes_pieces 
            WHERE statut IN ('en_attente', 'urgent')
        ");
        $commandes_stats = $stmt->fetch();
        
        return [
            'reparations' => $reparations_stats,
            'taches' => $taches_stats,
            'clients' => $clients_stats,
            'commandes' => $commandes_stats
        ];
        
    } catch (PDOException $e) {
        error_log("Erreur dashboard stats: " . $e->getMessage());
        return [
            'reparations' => ['total' => 0, 'actives' => 0, 'nouvelles' => 0, 'en_cours' => 0, 'en_attente' => 0],
            'taches' => ['total' => 0],
            'clients' => ['total' => 0],
            'commandes' => ['total' => 0]
        ];
    }
}

/**
 * Obtenir les tâches récentes
 */
function getRecentTasks($limit = 5) {
    try {
        $pdo = getShopDBConnection();
        $stmt = $pdo->prepare("
            SELECT t.*, u.nom as utilisateur_nom, u.prenom as utilisateur_prenom
            FROM taches t
            LEFT JOIN utilisateurs u ON t.utilisateur_id = u.id
            WHERE t.statut != 'terminee'
            ORDER BY t.date_creation DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur recent tasks: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtenir les réparations récentes
 */
function getRecentRepairs($limit = 5) {
    try {
        $pdo = getShopDBConnection();
        $stmt = $pdo->prepare("
            SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, s.nom as statut_nom
            FROM reparations r
            LEFT JOIN clients c ON r.client_id = c.id
            LEFT JOIN statuts s ON r.statut_id = s.id
            WHERE r.statut_id IN (1,2,3,19,20)
            ORDER BY r.date_creation DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur recent repairs: " . $e->getMessage());
        return [];
    }
}

// Récupérer les données
$stats = getDashboardStats();
$recent_tasks = getRecentTasks();
$recent_repairs = getRecentRepairs();

?>

<!DOCTYPE html>
<html lang="fr" data-page="accueil-v3">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeekBoard V3 - Dashboard Futuriste</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    /* ====================================================================
       🎨 VARIABLES CSS FUTURISTES
    ==================================================================== */
    :root {
        /* Couleurs principales */
        --primary-bg: #0f0f23;
        --secondary-bg: #1a1a2e;
        --accent-bg: #16213e;
        --surface-bg: rgba(255, 255, 255, 0.05);
        
        /* Couleurs néon */
        --neon-cyan: #00ffff;
        --neon-purple: #8a2be2;
        --neon-pink: #ff1493;
        --neon-blue: #0080ff;
        --neon-green: #00ff41;
        --neon-orange: #ff8c00;
        
        /* Texte */
        --text-primary: #ffffff;
        --text-secondary: #e2e8f0;
        --text-muted: #94a3b8;
        
        /* Bordures et ombres */
        --border-color: rgba(255, 255, 255, 0.1);
        --glow-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        --card-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        
        /* Transitions */
        --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --transition-fast: all 0.2s ease;
    }
    
    /* Mode clair */
    @media (prefers-color-scheme: light) {
        :root {
            --primary-bg: #f8fafc;
            --secondary-bg: #ffffff;
            --accent-bg: #e2e8f0;
            --surface-bg: rgba(0, 0, 0, 0.02);
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border-color: rgba(0, 0, 0, 0.1);
            --glow-shadow: 0 0 20px rgba(59, 130, 246, 0.2);
            --card-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
    }
    
    /* ====================================================================
       🎯 STYLES DE BASE
    ==================================================================== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', sans-serif;
        background: var(--primary-bg);
        color: var(--text-primary);
        line-height: 1.6;
        overflow-x: hidden;
    }
    
    /* Fond animé futuriste */
    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: 
            radial-gradient(circle at 20% 30%, rgba(0, 212, 255, 0.1) 0%, transparent 60%),
            radial-gradient(circle at 80% 70%, rgba(138, 43, 226, 0.08) 0%, transparent 60%),
            radial-gradient(circle at 60% 20%, rgba(255, 20, 147, 0.06) 0%, transparent 50%);
        pointer-events: none;
        z-index: -2;
        animation: backgroundShift 20s ease-in-out infinite alternate;
    }
    
    body::after {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: 
            linear-gradient(rgba(0, 255, 255, 0.02) 1px, transparent 1px),
            linear-gradient(90deg, rgba(138, 43, 226, 0.02) 1px, transparent 1px);
        background-size: 50px 50px;
        pointer-events: none;
        z-index: -1;
        opacity: 0.5;
    }
    
    @keyframes backgroundShift {
        0% { transform: translateX(0) translateY(0); }
        25% { transform: translateX(-20px) translateY(-10px); }
        50% { transform: translateX(20px) translateY(10px); }
        75% { transform: translateX(-10px) translateY(20px); }
        100% { transform: translateX(10px) translateY(-20px); }
    }
    
    /* ====================================================================
       📱 CONTAINER PRINCIPAL
    ==================================================================== */
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
        position: relative;
        z-index: 1;
    }
    
    /* ====================================================================
       🎯 ACTIONS RAPIDES
    ==================================================================== */
    .quick-actions {
        margin-bottom: 3rem;
    }
    
    .quick-actions h2 {
        font-family: 'Orbitron', monospace;
        font-size: 2rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 2rem;
        background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
    }
    
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .action-card {
        background: var(--surface-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        text-decoration: none;
        color: var(--text-primary);
        transition: var(--transition-smooth);
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(10px);
        cursor: pointer;
    }
    
    .action-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transition: left 0.5s;
    }
    
    .action-card:hover::before {
        left: 100%;
    }
    
    .action-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--glow-shadow);
        border-color: var(--neon-cyan);
    }
    
    .action-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--neon-cyan);
    }
    
    .action-text {
        font-size: 1.1rem;
        font-weight: 600;
        font-family: 'Orbitron', monospace;
    }
    
    /* Couleurs spécifiques par action */
    .action-search:hover { border-color: var(--neon-cyan); }
    .action-search:hover .action-icon { color: var(--neon-cyan); }
    
    .action-task:hover { border-color: var(--neon-purple); }
    .action-task:hover .action-icon { color: var(--neon-purple); }
    
    .action-repair:hover { border-color: var(--neon-green); }
    .action-repair:hover .action-icon { color: var(--neon-green); }
    
    .action-order:hover { border-color: var(--neon-orange); }
    .action-order:hover .action-icon { color: var(--neon-orange); }
    
    /* ====================================================================
       📊 STATISTIQUES
    ==================================================================== */
    .stats-section {
        margin-bottom: 3rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: var(--surface-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 2rem;
        text-decoration: none;
        color: var(--text-primary);
        transition: var(--transition-smooth);
        backdrop-filter: blur(10px);
        position: relative;
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--card-shadow);
        border-color: var(--neon-blue);
        color: var(--text-primary);
        text-decoration: none;
    }
    
    .stat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }
    
    .stat-icon {
        font-size: 2rem;
        color: var(--neon-blue);
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        font-family: 'Orbitron', monospace;
        color: var(--neon-cyan);
    }
    
    .stat-label {
        font-size: 1rem;
        color: var(--text-secondary);
        margin-top: 0.5rem;
    }
    
    .stat-link {
        color: var(--neon-blue);
        font-size: 1.2rem;
    }
    
    /* ====================================================================
       📋 TABLEAUX
    ==================================================================== */
    .tables-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }
    
    .table-container {
        background: var(--surface-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 2rem;
        backdrop-filter: blur(10px);
    }
    
    .table-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }
    
    .table-title {
        font-family: 'Orbitron', monospace;
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .table-title i {
        color: var(--neon-purple);
    }
    
    .table-badge {
        background: var(--neon-purple);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .futuristic-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .futuristic-table th {
        background: rgba(0, 255, 255, 0.1);
        color: var(--neon-cyan);
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        font-family: 'Orbitron', monospace;
        border-bottom: 2px solid var(--neon-cyan);
    }
    
    .futuristic-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-secondary);
    }
    
    .futuristic-table tr:hover {
        background: rgba(0, 255, 255, 0.05);
    }
    
    .priority-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .priority-haute { background: var(--neon-pink); color: white; }
    .priority-moyenne { background: var(--neon-orange); color: white; }
    .priority-basse { background: var(--neon-blue); color: white; }
    
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        background: var(--neon-green);
        color: white;
    }
    
    /* ====================================================================
       🎬 LOADER FUTURISTE
    ==================================================================== */
    .futuristic-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--primary-bg);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        transition: opacity 0.5s ease;
    }
    
    .loader-content {
        text-align: center;
    }
    
    .loader-text {
        font-family: 'Orbitron', monospace;
        font-size: 3rem;
        font-weight: 700;
        color: var(--neon-cyan);
        margin-bottom: 2rem;
        text-shadow: 0 0 30px var(--neon-cyan);
        animation: textPulse 2s ease-in-out infinite;
    }
    
    .loader-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        position: relative;
        margin: 0 auto;
        animation: loaderRotate 2s linear infinite;
    }
    
    .loader-circle::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        box-shadow: 
            0 0 0 4px rgba(0, 255, 255, 0.2),
            0 0 0 8px rgba(138, 43, 226, 0.2),
            0 0 0 12px rgba(255, 20, 147, 0.2);
        animation: loaderPulse 2s ease-in-out infinite;
    }
    
    @keyframes textPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.05); }
    }
    
    @keyframes loaderRotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @keyframes loaderPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.7; }
    }
    
    .loader-hidden {
        opacity: 0;
        pointer-events: none;
    }
    
    /* ====================================================================
       📱 RESPONSIVE
    ==================================================================== */
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }
        
        .actions-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .action-card {
            padding: 1.5rem;
        }
        
        .action-icon {
            font-size: 2.5rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .tables-section {
            grid-template-columns: 1fr;
        }
        
        .loader-text {
            font-size: 2rem;
        }
        
        .loader-circle {
            width: 80px;
            height: 80px;
        }
    }
    
    /* ====================================================================
       🎨 ANIMATIONS D'ENTRÉE
    ==================================================================== */
    .fade-in {
        opacity: 0;
        transform: translateY(30px);
        animation: fadeInUp 0.8s ease-out forwards;
    }
    
    .fade-in-delay-1 { animation-delay: 0.2s; }
    .fade-in-delay-2 { animation-delay: 0.4s; }
    .fade-in-delay-3 { animation-delay: 0.6s; }
    
    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    </style>
</head>

<body>
    <!-- Loader futuriste - DÉSACTIVÉ -->
    <!-- 
    <div id="futuristicLoader" class="futuristic-loader">
        <div class="loader-content">
            <div class="loader-text">SERVO</div>
            <div class="loader-circle"></div>
        </div>
    </div>
    -->

    <!-- Container principal -->
    <div class="dashboard-container">
        
        <!-- Actions rapides -->
        <section class="quick-actions fade-in">
            <h2>GeekBoard V3 - Dashboard Futuriste</h2>
            <div class="actions-grid">
                <div class="action-card action-search" onclick="openSearchModal()">
                    <div class="action-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="action-text">Rechercher</div>
                </div>
                
                <div class="action-card action-task" onclick="openTaskModal()">
                    <div class="action-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="action-text">Nouvelle tâche</div>
                </div>
                
                <div class="action-card action-repair" onclick="openRepairPage()">
                    <div class="action-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="action-text">Nouvelle réparation</div>
                </div>
                
                <div class="action-card action-order" onclick="openOrderModal()">
                    <div class="action-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="action-text">Nouvelle commande</div>
                </div>
            </div>
        </section>

        <!-- Statistiques -->
        <section class="stats-section fade-in fade-in-delay-1">
            <div class="stats-grid">
                <a href="index.php?page=reparations" class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="stat-link">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['reparations']['actives']; ?></div>
                    <div class="stat-label">Réparations actives</div>
                </a>
                
                <a href="index.php?page=taches" class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-link">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['taches']['total']; ?></div>
                    <div class="stat-label">Tâches en cours</div>
                </a>
                
                <a href="index.php?page=clients" class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-link">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['clients']['total']; ?></div>
                    <div class="stat-label">Clients</div>
                </a>
                
                <a href="index.php?page=commandes_pieces" class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-link">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['commandes']['total']; ?></div>
                    <div class="stat-label">Commandes en attente</div>
                </a>
            </div>
        </section>

        <!-- Tableaux -->
        <section class="tables-section fade-in fade-in-delay-2">
            <!-- Tâches récentes -->
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">
                        <i class="fas fa-tasks"></i>
                        Tâches récentes
                    </h3>
                    <span class="table-badge"><?php echo count($recent_tasks); ?></span>
                </div>
                
                <table class="futuristic-table">
                    <thead>
                        <tr>
                            <th>Tâche</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_tasks)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted);">
                                    Aucune tâche récente
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_tasks as $task): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($task['titre'] ?? 'Sans titre'); ?></strong>
                                        <?php if (!empty($task['description'])): ?>
                                            <br><small style="color: var(--text-muted);">
                                                <?php echo htmlspecialchars(substr($task['description'], 0, 50)) . '...'; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="priority-badge priority-<?php echo strtolower($task['priorite'] ?? 'basse'); ?>">
                                            <?php echo ucfirst($task['priorite'] ?? 'Basse'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge">
                                            <?php echo ucfirst($task['statut'] ?? 'En cours'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($task['date_creation'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Réparations récentes -->
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">
                        <i class="fas fa-tools"></i>
                        Réparations récentes
                    </h3>
                    <span class="table-badge"><?php echo count($recent_repairs); ?></span>
                </div>
                
                <table class="futuristic-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Appareil</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_repairs)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted);">
                                    Aucune réparation récente
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_repairs as $repair): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?php echo htmlspecialchars(($repair['client_nom'] ?? '') . ' ' . ($repair['client_prenom'] ?? '')); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($repair['appareil'] ?? 'Non spécifié'); ?>
                                        <?php if (!empty($repair['modele'])): ?>
                                            <br><small style="color: var(--text-muted);">
                                                <?php echo htmlspecialchars($repair['modele']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge">
                                            <?php echo htmlspecialchars($repair['statut_nom'] ?? 'En cours'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($repair['date_creation'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // ====================================================================
    // 🚀 JAVASCRIPT FUTURISTE INTÉGRÉ
    // ====================================================================
    
    /**
     * Initialisation au chargement de la page
     */
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 GeekBoard V3 - Initialisation...');
        
        // Loader désactivé - pas besoin de le masquer
        /* ANCIEN CODE LOADER - DÉSACTIVÉ
        setTimeout(() => {
            const loader = document.getElementById('futuristicLoader');
            if (loader) {
                loader.classList.add('loader-hidden');
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 500);
            }
        }, 2000);
        */
        
        // Initialiser les animations
        initAnimations();
        
        // Initialiser les interactions
        initInteractions();
        
        console.log('✅ GeekBoard V3 - Initialisé avec succès !');
    });
    
    /**
     * Initialiser les animations
     */
    function initAnimations() {
        // Observer pour les animations d'entrée
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        });
        
        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });
    }
    
    /**
     * Initialiser les interactions
     */
    function initInteractions() {
        // Effet de ripple sur les cartes
        document.querySelectorAll('.action-card, .stat-card').forEach(card => {
            card.addEventListener('click', createRippleEffect);
        });
    }
    
    /**
     * Créer un effet de ripple
     */
    function createRippleEffect(event) {
        const card = event.currentTarget;
        const rect = card.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        const ripple = document.createElement('span');
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(0, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
            z-index: 1000;
        `;
        
        card.style.position = 'relative';
        card.appendChild(ripple);
        
        // Supprimer le ripple après l'animation
        setTimeout(() => {
            ripple.remove();
        }, 600);
        
        // Ajouter l'animation CSS si elle n'existe pas
        if (!document.getElementById('ripple-animation')) {
            const style = document.createElement('style');
            style.id = 'ripple-animation';
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // ====================================================================
    // 🎯 FONCTIONS D'ACTION
    // ====================================================================
    
    /**
     * Ouvrir le modal de recherche
     */
    function openSearchModal() {
        console.log('🔍 Ouverture du modal de recherche');
        // Rediriger vers la page de recherche ou ouvrir un modal
        window.location.href = 'index.php?page=recherche';
    }
    
    /**
     * Ouvrir le modal de nouvelle tâche
     */
    function openTaskModal() {
        console.log('📋 Ouverture du modal de nouvelle tâche');
        // Rediriger vers la page d'ajout de tâche
        window.location.href = 'index.php?page=ajouter_tache';
    }
    
    /**
     * Ouvrir la page de nouvelle réparation
     */
    function openRepairPage() {
        console.log('🔧 Ouverture de la page de nouvelle réparation');
        // Rediriger vers la page d'ajout de réparation
        window.location.href = 'index.php?page=ajouter_reparation';
    }
    
    /**
     * Ouvrir le modal de nouvelle commande
     */
    function openOrderModal() {
        console.log('🛒 Ouverture du modal de nouvelle commande');
        // Rediriger vers la page de commandes
        window.location.href = 'index.php?page=commandes_pieces';
    }
    
    // ====================================================================
    // 🎨 EFFETS VISUELS AVANCÉS
    // ====================================================================
    
    /**
     * Effet de parallaxe sur le fond
     */
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const parallax = document.querySelector('body::before');
        if (parallax) {
            const speed = scrolled * 0.5;
            parallax.style.transform = `translateY(${speed}px)`;
        }
    });
    
    /**
     * Mise à jour des statistiques en temps réel (simulation)
     */
    function updateStatsRealTime() {
        const statValues = document.querySelectorAll('.stat-value');
        statValues.forEach(stat => {
            // Animation de compteur
            const finalValue = parseInt(stat.textContent);
            let currentValue = 0;
            const increment = finalValue / 50;
            
            const counter = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    stat.textContent = finalValue;
                    clearInterval(counter);
                } else {
                    stat.textContent = Math.floor(currentValue);
                }
            }, 30);
        });
    }
    
    // Démarrer l'animation des compteurs après le chargement
    window.addEventListener('load', () => {
        setTimeout(updateStatsRealTime, 2500);
    });
    
    // ====================================================================
    // 📱 RESPONSIVE ET PERFORMANCE
    // ====================================================================
    
    /**
     * Optimisations pour mobile
     */
    if (window.innerWidth <= 768) {
        // Réduire les animations sur mobile
        document.documentElement.style.setProperty('--transition-smooth', 'all 0.2s ease');
        
        // Désactiver le parallaxe sur mobile
        window.removeEventListener('scroll', () => {});
    }
    
    /**
     * Gestion du mode sombre/clair
     */
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    prefersDark.addEventListener('change', (e) => {
        console.log('🌙 Mode sombre:', e.matches ? 'activé' : 'désactivé');
        // Les variables CSS se mettent à jour automatiquement
    });
    
    console.log('🎉 GeekBoard V3 - Tous les scripts chargés !');
    </script>
</body>
</html>
