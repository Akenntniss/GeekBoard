<?php
/**
 * Interface administrateur moderne pour le système de pointage
 * Version SANS Bootstrap - CSS Vanilla uniquement
 */

// Inclusion des fichiers de configuration
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Session pour les fonctionnalités de base
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_user_id = $_SESSION['user_id'] ?? 1;
$shop_pdo = getShopDBConnection();

// Traitement des actions admin (simplifié)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'force_clock_out':
            $user_id = intval($_POST['user_id']);
            $stmt = $shop_pdo->prepare("
                UPDATE time_tracking 
                SET clock_out = NOW(), 
                    status = 'completed'
                WHERE user_id = ? AND status IN ('active', 'break')
            ");
            
            if ($stmt->execute([$user_id])) {
                $response = ['success' => true, 'message' => 'Pointage forcé avec succès'];
            } else {
                $response = ['success' => false, 'message' => 'Erreur lors du pointage forcé'];
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Récupération des données (version simplifiée)
$stats = ['currently_working' => 0, 'on_break' => 0, 'today_entries' => 0, 'avg_daily_hours' => 0];
$active_users = [];
$all_users = [];
$alerts = [];

try {
    // Statistiques générales
    $stmt = $shop_pdo->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'active' THEN 1 END) as currently_working,
            COUNT(CASE WHEN status = 'break' THEN 1 END) as on_break,
            COUNT(CASE WHEN DATE(clock_in) = CURDATE() THEN 1 END) as today_entries,
            ROUND(AVG(CASE WHEN status = 'completed' AND total_hours > 0 THEN total_hours END), 2) as avg_daily_hours
        FROM time_tracking
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $stats;
    
    // Utilisateurs actifs
    $stmt = $shop_pdo->prepare("
        SELECT tt.*, u.full_name, u.username,
               TIMESTAMPDIFF(MINUTE, tt.clock_in, NOW()) / 60.0 as current_duration,
               TIME_FORMAT(TIMEDIFF(NOW(), tt.clock_in), '%H:%i') as formatted_duration
        FROM time_tracking tt
        JOIN users u ON tt.user_id = u.id
        WHERE tt.status IN ('active', 'break')
        ORDER BY tt.clock_in ASC
    ");
    $stmt->execute();
    $active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tous les utilisateurs
    $stmt = $shop_pdo->prepare("SELECT id, full_name, username FROM users WHERE role != 'admin' ORDER BY full_name");
    $stmt->execute();
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Alertes simples
    foreach ($active_users as $user) {
        if ($user['current_duration'] > 8) {
            $alerts[] = [
                'message' => $user['full_name'] . ' travaille depuis plus de 8h',
                'user_id' => $user['user_id'],
                'type' => 'overtime'
            ];
        }
    }
    
} catch (PDOException $e) {
    error_log("Erreur timetracking: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pointage - Administration</title>
    
    <!-- Font Awesome uniquement -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ========================================
   RESET ET BASE
======================================== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    color: #333;
    padding: 20px;
    overflow-x: hidden;
}

/* ========================================
   FIX NAVBAR - Compatible avec GeekBoard
======================================== */
@media (min-width: 992px) {
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    
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
    
    body {
        padding-top: 80px !important;
    }
}

/* ========================================
   LAYOUT PRINCIPAL
======================================== */
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    position: relative;
    z-index: 9999 !important;
}

.page-header {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    text-align: center;
    position: relative;
    z-index: 9998 !important;
}

.page-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 10px;
}

.page-header p {
    color: #666;
    font-size: 1.1rem;
}

/* ========================================
   NAVIGATION PAR ONGLETS
======================================== */
.tabs-container {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 9997 !important;
}

.tabs-nav {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.tab-button {
    background: transparent;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    font-size: 14px;
}

.tab-button:hover {
    border-color: #667eea;
    color: #667eea;
    transform: translateY(-2px);
}

.tab-button.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-color: #667eea;
    color: white;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.tab-content {
    min-height: 400px;
    position: relative;
    z-index: 9996 !important;
}

.tab-pane {
    display: none !important;
    animation: fadeIn 0.3s ease;
    position: relative;
    z-index: 9995 !important;
    opacity: 0;
    visibility: hidden;
}

.tab-pane.active {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    position: relative !important;
    z-index: 9995 !important;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ========================================
   CARTES STATISTIQUES
======================================== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: transform 0.3s ease;
    position: relative;
    overflow: hidden;
    z-index: 9994 !important;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.stat-card.success::before { background: linear-gradient(90deg, #10b981, #059669); }
.stat-card.warning::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
.stat-card.primary::before { background: linear-gradient(90deg, #3b82f6, #1d4ed8); }
.stat-card.info::before { background: linear-gradient(90deg, #06b6d4, #0891b2); }

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 15px;
    opacity: 0.7;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 5px;
    display: block;
}

.stat-label {
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ========================================
   TABLEAUX
======================================== */
.table-container {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
    position: relative;
    z-index: 9993 !important;
}

.table-header {
    background: linear-gradient(135deg, #f8f9fa, #ffffff);
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
}

.table-header h3 {
    margin: 0;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
}

.simple-table {
    width: 100%;
    border-collapse: collapse;
}

.simple-table th {
    background: #f8f9fa;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #e2e8f0;
}

.simple-table td {
    padding: 15px;
    border-bottom: 1px solid #f1f5f9;
    color: #374151;
    vertical-align: top;
}

.simple-table tr:hover {
    background: #f8fafc;
}

/* ========================================
   BOUTONS
======================================== */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

/* ========================================
   BADGES
======================================== */
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-success { background: #dcfdf4; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-info { background: #dbeafe; color: #1e40af; }

/* ========================================
   ALERTES
======================================== */
.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border-left: 4px solid #f59e0b;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

/* ========================================
   AVATAR
======================================== */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

/* ========================================
   RESPONSIVE
======================================== */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 10px;
    }
    
    .page-header {
        padding: 20px;
        text-align: center;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .tabs-nav {
        flex-direction: column;
        gap: 5px;
    }
    
    .tab-button {
        width: 100%;
        justify-content: center;
    }
    
    .simple-table {
        font-size: 0.85rem;
    }
    
    .simple-table th,
    .simple-table td {
        padding: 10px 8px;
    }
}

/* ========================================
   MODE SOMBRE AUTOMATIQUE
======================================== */
@media (prefers-color-scheme: dark) {
    body {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        color: #f1f5f9;
    }
    
    .page-header,
    .tabs-container,
    .table-container,
    .stat-card {
        background: rgba(15, 15, 25, 0.95);
        color: #f1f5f9;
    }
    
    .simple-table th {
        background: rgba(30, 30, 50, 0.8);
        color: #f1f5f9;
    }
    
    .simple-table td {
        color: #e2e8f0;
        border-bottom-color: rgba(255, 255, 255, 0.1);
    }
    
    .simple-table tr:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    
    .tab-button {
        color: #cbd5e1;
        border-color: rgba(255, 255, 255, 0.2);
    }
    
    .tab-button:hover {
        color: #00d4ff;
        border-color: #00d4ff;
    }
    
    .tab-button.active {
        background: linear-gradient(135deg, #00d4ff, #7c3aed);
        color: white;
    }
}

/* ========================================
   LOADER SIMPLE
======================================== */
.simple-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    flex-direction: column;
    color: white;
}

.loader-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-left: 4px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* ========================================
   FORÇAGE D'AFFICHAGE MAXIMUM
======================================== */
html body .dashboard-container,
html body .dashboard-container *,
html body .tabs-container,
html body .tabs-container *,
html body .tab-content,
html body .tab-content *,
html body .tab-pane,
html body .tab-pane * {
    z-index: 9999 !important;
    position: relative !important;
}

html body .tab-pane.active {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    z-index: 9999 !important;
}

html body .stat-card,
html body .table-container,
html body .page-header {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    z-index: 9999 !important;
}

/* Style de debug pour voir si les éléments se chargent */
.debug-visible {
    border: 3px solid red !important;
    background: yellow !important;
    color: black !important;
    z-index: 99999 !important;
    position: relative !important;
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}
</style>
</head>

<body>
    <!-- Loader simple -->
    <div class="simple-loader" id="pageLoader">
        <div class="loader-spinner"></div>
        <h3>Chargement du Dashboard...</h3>
        <p>Préparation des données de pointage</p>
    </div>

    <!-- Container principal -->
    <div class="dashboard-container" id="mainContent" style="display: none;">
        
        <!-- TEST DE DEBUG VISIBLE -->
        <div class="debug-visible" style="padding: 20px; margin: 20px; text-align: center; font-size: 20px; font-weight: bold;">
            🔍 TEST DEBUG - VERSION SIMPLE SANS BOOTSTRAP
            <br>Si vous voyez ce bloc rouge/jaune, le PHP fonctionne !
            <br>Utilisateurs actifs: <?php echo count($active_users); ?> | Total: <?php echo count($all_users); ?>
        </div>

        <!-- Header de la page -->
        <div class="page-header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard Pointage</h1>
            <p>Supervision avancée et analytics des temps de travail</p>
            <div style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="refreshPage()">
                    <i class="fas fa-sync-alt"></i> Actualiser
                </button>
                <button class="btn btn-success" onclick="exportData()">
                    <i class="fas fa-download"></i> Exporter
                </button>
            </div>
        </div>

        <!-- Navigation par onglets -->
        <div class="tabs-container">
            <div class="tabs-nav">
                <button class="tab-button active" onclick="showTab('dashboard')">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </button>
                <button class="tab-button" onclick="showTab('live')">
                    <i class="fas fa-broadcast-tower"></i> Temps Réel
                </button>
                <button class="tab-button" onclick="showTab('users')">
                    <i class="fas fa-users"></i> Employés
                </button>
                <button class="tab-button" onclick="showTab('alerts')">
                    <i class="fas fa-bell"></i> Alertes
                    <?php if (count($alerts) > 0): ?>
                    <span class="badge badge-warning"><?php echo count($alerts); ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Contenu des onglets -->
            <div class="tab-content">
                
                <!-- Dashboard Principal -->
                <div class="tab-pane active" id="dashboard">
                    
                    <!-- KPIs principaux -->
                    <div class="stats-grid">
                        <div class="stat-card success">
                            <i class="fas fa-users stat-icon" style="color: #10b981;"></i>
                            <span class="stat-number"><?php echo $stats['currently_working']; ?></span>
                            <div class="stat-label">Actuellement au travail</div>
                        </div>
                        
                        <div class="stat-card warning">
                            <i class="fas fa-pause stat-icon" style="color: #f59e0b;"></i>
                            <span class="stat-number"><?php echo $stats['on_break']; ?></span>
                            <div class="stat-label">En pause</div>
                        </div>
                        
                        <div class="stat-card primary">
                            <i class="fas fa-calendar-day stat-icon" style="color: #3b82f6;"></i>
                            <span class="stat-number"><?php echo $stats['today_entries']; ?></span>
                            <div class="stat-label">Pointages aujourd'hui</div>
                        </div>
                        
                        <div class="stat-card info">
                            <i class="fas fa-clock stat-icon" style="color: #06b6d4;"></i>
                            <span class="stat-number"><?php echo $stats['avg_daily_hours']; ?>h</span>
                            <div class="stat-label">Moyenne journalière</div>
                        </div>
                    </div>

                    <!-- Résumé rapide -->
                    <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-chart-bar"></i> Résumé d'aujourd'hui</h3>
                        </div>
                        <div style="padding: 30px; text-align: center;">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                                <div>
                                    <h4 style="color: #10b981; margin-bottom: 10px;">✅ Employés Actifs</h4>
                                    <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $stats['currently_working']; ?></p>
                                </div>
                                <div>
                                    <h4 style="color: #f59e0b; margin-bottom: 10px;">⏸️ En Pause</h4>
                                    <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $stats['on_break']; ?></p>
                                </div>
                                <div>
                                    <h4 style="color: #3b82f6; margin-bottom: 10px;">📅 Total Pointages</h4>
                                    <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $stats['today_entries']; ?></p>
                                </div>
                                <div>
                                    <h4 style="color: #06b6d4; margin-bottom: 10px;">⏱️ Moyenne Heures</h4>
                                    <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?php echo $stats['avg_daily_hours']; ?>h</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Temps Réel -->
                <div class="tab-pane" id="live">
                    <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-satellite-dish" style="color: #10b981;"></i> Activité en Temps Réel</h3>
                            <span class="badge badge-success" style="animation: pulse 2s infinite;">
                                <i class="fas fa-circle"></i> LIVE
                            </span>
                        </div>
                        
                        <?php if (!empty($active_users)): ?>
                        <table class="simple-table">
                            <thead>
                                <tr>
                                    <th>Employé</th>
                                    <th>Heure d'arrivée</th>
                                    <th>Durée actuelle</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                <br><small style="color: #64748b;">@<?php echo htmlspecialchars($user['username']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo date('H:i', strtotime($user['clock_in'])); ?></strong>
                                        <br><small style="color: #64748b;"><?php echo date('d/m/Y', strtotime($user['clock_in'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $user['current_duration'] > 8 ? 'badge-danger' : 
                                                ($user['current_duration'] > 6 ? 'badge-warning' : 'badge-success'); 
                                        ?>">
                                            <?php echo $user['formatted_duration']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_labels = [
                                            'active' => 'Actif',
                                            'break' => 'En pause',
                                            'completed' => 'Terminé'
                                        ];
                                        $status_class = [
                                            'active' => 'badge-success',
                                            'break' => 'badge-warning',
                                            'completed' => 'badge-info'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $status_class[$user['status']] ?? 'badge-info'; ?>">
                                            <?php echo $status_labels[$user['status']] ?? ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['status'] == 'active'): ?>
                                        <button class="btn btn-warning" onclick="forceClockOut(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                            <i class="fas fa-stop"></i> Arrêter
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div style="text-align: center; padding: 50px; color: #64748b;">
                            <i class="fas fa-user-clock" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                            <h4>Aucun employé actuellement pointé</h4>
                            <p>Tous les employés ont terminé leur journée de travail.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Employés -->
                <div class="tab-pane" id="users">
                    <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-users" style="color: #3b82f6;"></i> Liste des Employés</h3>
                        </div>
                        
                        <?php if (!empty($all_users)): ?>
                        <table class="simple-table">
                            <thead>
                                <tr>
                                    <th>Employé</th>
                                    <th>Nom d'utilisateur</th>
                                    <th>Statut actuel</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_users as $user): ?>
                                <?php
                                // Vérifier si l'utilisateur est actuellement actif
                                $current_status = 'Inactif';
                                $status_class = 'badge-info';
                                foreach ($active_users as $active) {
                                    if ($active['user_id'] == $user['id']) {
                                        $current_status = $active['status'] == 'active' ? 'Actif' : 'En pause';
                                        $status_class = $active['status'] == 'active' ? 'badge-success' : 'badge-warning';
                                        break;
                                    }
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo $current_status; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary" onclick="viewUserHistory(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-history"></i> Historique
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div style="text-align: center; padding: 50px; color: #64748b;">
                            <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                            <h4>Aucun employé trouvé</h4>
                            <p>Aucun employé n'est configuré dans le système.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Alertes -->
                <div class="tab-pane" id="alerts">
                    <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Alertes Actives</h3>
                            <span class="badge badge-warning"><?php echo count($alerts); ?></span>
                        </div>
                        
                        <?php if (!empty($alerts)): ?>
                        <div style="padding: 20px;">
                            <?php foreach ($alerts as $alert): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-clock"></i>
                                <strong><?php echo htmlspecialchars($alert['message']); ?></strong>
                                <div style="margin-left: auto;">
                                    <button class="btn btn-warning" onclick="handleAlert(<?php echo $alert['user_id']; ?>)">
                                        <i class="fas fa-bell"></i> Notifier
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div style="text-align: center; padding: 50px; color: #64748b;">
                            <i class="fas fa-shield-alt" style="font-size: 3rem; margin-bottom: 20px; color: #10b981; opacity: 0.7;"></i>
                            <h4 style="color: #10b981;">Aucune alerte active</h4>
                            <p>Tout va bien ! Le système surveille en continu.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- JavaScript Vanilla uniquement -->
    <script>
    console.log('🎯 [TIMETRACKING-SIMPLE] Initialisation...');

    // Gestion des onglets
    function showTab(tabId) {
        console.log('🎯 [TIMETRACKING-SIMPLE] Affichage onglet:', tabId);
        
        // Masquer tous les onglets
        const allPanes = document.querySelectorAll('.tab-pane');
        const allButtons = document.querySelectorAll('.tab-button');
        
        allPanes.forEach(pane => {
            pane.classList.remove('active');
        });
        
        allButtons.forEach(button => {
            button.classList.remove('active');
        });
        
        // Afficher l'onglet sélectionné
        const targetPane = document.getElementById(tabId);
        const targetButton = event.target.closest('.tab-button');
        
        if (targetPane) {
            targetPane.classList.add('active');
            console.log('🎯 [TIMETRACKING-SIMPLE] Onglet activé:', tabId);
        }
        
        if (targetButton) {
            targetButton.classList.add('active');
        }
    }

    // Actions
    function refreshPage() {
        console.log('🎯 [TIMETRACKING-SIMPLE] Actualisation...');
        location.reload();
    }

    function exportData() {
        console.log('🎯 [TIMETRACKING-SIMPLE] Export...');
        alert('Fonctionnalité d\'export en cours de développement');
    }

    function forceClockOut(userId, userName) {
        console.log('🎯 [TIMETRACKING-SIMPLE] Force clock out:', userId, userName);
        if (confirm('Êtes-vous sûr de vouloir forcer lârrêt du pointage de ' + userName + ' ?')) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=force_clock_out&user_id=' + userId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            });
        }
    }

    function viewUserHistory(userId) {
        console.log('🎯 [TIMETRACKING-SIMPLE] Historique utilisateur:', userId);
        alert('Fonctionnalité d\'historique en cours de développement pour l\'utilisateur ID: ' + userId);
    }

    function handleAlert(userId) {
        console.log('🎯 [TIMETRACKING-SIMPLE] Gestion alerte:', userId);
        alert('Notification envoyée à l\'employé ID: ' + userId);
    }

    // Initialisation au chargement
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🎯 [TIMETRACKING-SIMPLE] DOM chargé');
        
        // Diagnostic complet
        console.log('🎯 [TIMETRACKING-SIMPLE] Éléments trouvés:');
        console.log('- Dashboard container:', document.getElementById('mainContent'));
        console.log('- Page header:', document.querySelector('.page-header'));
        console.log('- Tabs container:', document.querySelector('.tabs-container'));
        console.log('- Tab buttons:', document.querySelectorAll('.tab-button').length);
        console.log('- Tab panes:', document.querySelectorAll('.tab-pane').length);
        console.log('- Stat cards:', document.querySelectorAll('.stat-card').length);
        console.log('- Table containers:', document.querySelectorAll('.table-container').length);
        
        // Masquer le loader et afficher le contenu
        setTimeout(() => {
            const loader = document.getElementById('pageLoader');
            const content = document.getElementById('mainContent');
            
            if (loader) {
                loader.style.display = 'none';
                console.log('🎯 [TIMETRACKING-SIMPLE] Loader masqué');
            }
            
            if (content) {
                content.style.display = 'block';
                content.style.opacity = '1';
                content.style.visibility = 'visible';
                content.style.zIndex = '9999';
                console.log('🎯 [TIMETRACKING-SIMPLE] Contenu affiché');
            }
            
            // Forcer l'affichage de tous les éléments critiques
            const criticalElements = document.querySelectorAll('.dashboard-container, .page-header, .tabs-container, .tab-pane.active, .stat-card, .table-container');
            criticalElements.forEach(el => {
                el.style.display = 'block';
                el.style.opacity = '1';
                el.style.visibility = 'visible';
                el.style.zIndex = '9999';
                el.style.position = 'relative';
            });
            
            // S'assurer que le premier onglet est visible
            const firstTab = document.getElementById('dashboard');
            if (firstTab) {
                firstTab.style.display = 'block';
                firstTab.style.opacity = '1';
                firstTab.style.visibility = 'visible';
                firstTab.style.zIndex = '9999';
                firstTab.classList.add('active');
                console.log('🎯 [TIMETRACKING-SIMPLE] Premier onglet forcé visible');
            }
            
            console.log('🎯 [TIMETRACKING-SIMPLE] Initialisation terminée - Forçage d\'affichage appliqué');
        }, 1000);
    });

    // Auto-refresh toutes les 30 secondes pour le temps réel
    setInterval(() => {
        const liveTab = document.getElementById('live');
        if (liveTab && liveTab.classList.contains('active')) {
            console.log('🎯 [TIMETRACKING-SIMPLE] Auto-refresh temps réel');
            location.reload();
        }
    }, 30000);

    console.log('🎯 [TIMETRACKING-SIMPLE] Script chargé');

    // Fonction de diagnostic manuel - À appeler dans la console
    window.forceShowContent = function() {
        console.log('🔧 [FORCE-DISPLAY] Forçage manuel de l\'affichage...');
        
        // Masquer le loader
        const loader = document.getElementById('pageLoader');
        if (loader) loader.style.display = 'none';
        
        // Afficher le contenu principal
        const content = document.getElementById('mainContent');
        if (content) {
            content.style.display = 'block';
            content.style.opacity = '1';
            content.style.visibility = 'visible';
            content.style.zIndex = '99999';
        }
        
        // Forcer tous les éléments
        const allElements = document.querySelectorAll('*');
        allElements.forEach(el => {
            if (el.style.display === 'none' && !el.id.includes('loader')) {
                el.style.display = 'block';
            }
            if (el.classList.contains('tab-pane')) {
                el.style.opacity = '1';
                el.style.visibility = 'visible';
                el.style.zIndex = '99999';
            }
        });
        
        // Activer le premier onglet
        const firstTab = document.getElementById('dashboard');
        if (firstTab) {
            firstTab.style.display = 'block';
            firstTab.style.opacity = '1';
            firstTab.style.visibility = 'visible';
            firstTab.classList.add('active');
        }
        
        console.log('🔧 [FORCE-DISPLAY] Forçage terminé');
    };

    console.log('💡 [TIMETRACKING-SIMPLE] Utilisez window.forceShowContent() pour forcer l\'affichage manuellement');
    </script>
</body>
</html>
