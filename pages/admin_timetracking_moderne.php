<?php
/**
 * Interface administrateur moderne pour le système de pointage
 * Version complètement refaite basée sur accueil-modern.php
 * Design moderne avec glassmorphism et animations
 */

// Inclusion des fichiers de configuration
// Éviter le conflit dbDebugLog en incluant database.php en premier
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Session pour les fonctionnalités de base
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialiser la session magasin si nécessaire
if (!isset($_SESSION['shop_id'])) {
    initializeShopSession();
}

$current_user_id = $_SESSION['user_id'] ?? 1;
$shop_pdo = getShopDBConnection();

// Vérifier que la connexion est établie
if (!$shop_pdo) {
    die('Erreur: Impossible de se connecter à la base de données du magasin');
}

// Traitement des actions admin
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'force_clock_out':
            $user_id = intval($_POST['user_id']);
            $stmt = $shop_pdo->prepare("
                UPDATE time_tracking 
                SET clock_out = NOW(), 
                    status = 'completed',
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nForced clock-out by admin at ', NOW()),
                    total_hours = TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60.0,
                    work_duration = (TIMESTAMPDIFF(MINUTE, clock_in, NOW()) / 60.0) - IFNULL(break_duration, 0)
                WHERE user_id = ? AND status IN ('active', 'break')
            ");
            
            if ($stmt->execute([$user_id])) {
                $response = ['success' => true, 'message' => 'Pointage forcé avec succès'];
            } else {
                $response = ['success' => false, 'message' => 'Erreur lors du pointage forcé'];
            }
            break;
            
        case 'approve_entry':
            $entry_id = intval($_POST['entry_id']);
            $stmt = $shop_pdo->prepare("
                UPDATE time_tracking 
                SET status = 'approved', 
                    approved_by = ?,
                    approved_at = NOW(),
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nApproved by admin at ', NOW())
                WHERE id = ?
            ");
            
            if ($stmt->execute([$current_user_id, $entry_id])) {
                $response = ['success' => true, 'message' => 'Pointage approuvé avec succès'];
            } else {
                $response = ['success' => false, 'message' => 'Erreur lors de l\'approbation'];
            }
            break;
            
        case 'reject_entry':
            $entry_id = intval($_POST['entry_id']);
            $reason = $_POST['reason'] ?? 'Aucune raison spécifiée';
            $stmt = $shop_pdo->prepare("
                UPDATE time_tracking 
                SET status = 'rejected', 
                    rejected_by = ?,
                    rejected_at = NOW(),
                    rejection_reason = ?,
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\nRejected by admin at ', NOW(), ' - Reason: ', ?)
                WHERE id = ?
            ");
            
            if ($stmt->execute([$current_user_id, $reason, $reason, $entry_id])) {
                $response = ['success' => true, 'message' => 'Pointage rejeté avec succès'];
            } else {
                $response = ['success' => false, 'message' => 'Erreur lors du rejet'];
            }
            break;
            
        case 'save_global_slots':
            $slots = json_decode($_POST['slots'], true);
            
            try {
                $shop_pdo->beginTransaction();
                
                $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id IS NULL");
                $stmt->execute();
                
                foreach ($slots as $slot) {
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO time_slots (day_of_week, start_time, end_time, is_active) 
                        VALUES (?, ?, ?, 1)
                    ");
                    $stmt->execute([$slot['day'], $slot['start'], $slot['end']]);
                }
                
                $shop_pdo->commit();
                $response = ['success' => true, 'message' => 'Créneaux sauvegardés avec succès'];
            } catch (Exception $e) {
                $shop_pdo->rollback();
                $response = ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}

// Récupération des données
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$month_start = date('Y-m-01');

// Statistiques générales
$stats_query = "
    SELECT 
        COUNT(DISTINCT user_id) as total_users,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as users_active,
        SUM(CASE WHEN status = 'break' THEN 1 ELSE 0 END) as users_on_break,
        SUM(CASE WHEN DATE(clock_in) = CURDATE() THEN 1 ELSE 0 END) as today_sessions,
        AVG(CASE WHEN status = 'completed' AND DATE(clock_in) >= ? THEN work_duration ELSE NULL END) as avg_hours_week
    FROM time_tracking 
    WHERE DATE(clock_in) >= ?
";
$stmt = $shop_pdo->prepare($stats_query);
$stmt->execute([$week_start, $week_start]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Utilisateurs actifs actuellement
$active_users_query = "
    SELECT tt.*, u.username, u.full_name,
           TIMESTAMPDIFF(MINUTE, tt.clock_in, NOW()) as minutes_worked
    FROM time_tracking tt
    LEFT JOIN users u ON tt.user_id = u.id 
    WHERE tt.status IN ('active', 'break')
    ORDER BY tt.clock_in DESC
";
$stmt = $shop_pdo->prepare($active_users_query);
$stmt->execute();
$active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tous les utilisateurs pour les rapports
$all_users_query = "SELECT id, username, full_name FROM users WHERE role IN ('technicien', 'admin') ORDER BY full_name";
$stmt = $shop_pdo->prepare($all_users_query);
$stmt->execute();
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Demandes en attente d'approbation
$pending_requests_query = "
    SELECT tt.*, u.username, u.full_name 
    FROM time_tracking tt
    LEFT JOIN users u ON tt.user_id = u.id 
    WHERE tt.status = 'pending_approval'
    ORDER BY tt.created_at DESC
";
$stmt = $shop_pdo->prepare($pending_requests_query);
$stmt->execute();
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Alertes système
$alerts = [];

// Vérifier les utilisateurs en retard de pointage sortie (plus de 10h)
$overdue_query = "
    SELECT tt.*, u.username, u.full_name
    FROM time_tracking tt
    LEFT JOIN users u ON tt.user_id = u.id 
    WHERE tt.status = 'active' 
    AND TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) > 10
";
$stmt = $shop_pdo->prepare($overdue_query);
$stmt->execute();
$overdue_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($overdue_users as $user) {
    $alerts[] = [
        'type' => 'warning',
        'title' => 'Pointage en retard',
        'message' => $user['first_name'] . ' ' . $user['last_name'] . ' n\'a pas pointé depuis ' . 
                    round((time() - strtotime($user['clock_in'])) / 3600, 1) . 'h',
        'action' => 'force_clock_out',
        'user_id' => $user['user_id']
    ];
}

// Créneaux horaires globaux
$slots_query = "SELECT * FROM time_slots WHERE user_id IS NULL ORDER BY day_of_week, start_time";
$stmt = $shop_pdo->prepare($slots_query);
$stmt->execute();
$global_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Données pour les graphiques
$daily_stats_query = "
    SELECT 
        DATE(clock_in) as date,
        COUNT(*) as sessions,
        AVG(work_duration) as avg_duration,
        SUM(work_duration) as total_hours
    FROM time_tracking 
    WHERE DATE(clock_in) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND status = 'completed'
    GROUP BY DATE(clock_in)
    ORDER BY date DESC
";
$stmt = $shop_pdo->prepare($daily_stats_query);
$stmt->execute();
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Temps de Travail - GeekBoard</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* ========================================
           VARIABLES CSS MODERNES
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
           STYLES DE BASE MODERNES
        ======================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--day-bg);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            color: var(--day-text);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
            padding-top: 80px;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* ========================================
           FIX NAVBAR - OBLIGATOIRE
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
            
            body #desktop-navbar, html body #desktop-navbar {
                height: 60px !important;
                min-height: 60px !important;
                max-height: 60px !important;
            }
            
            #desktop-navbar * {
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            #desktop-navbar .container-fluid {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                height: 100% !important;
                padding: 0.3rem 1rem !important;
            }
            
            body .servo-logo-container {
                position: absolute !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                z-index: 10001 !important;
            }
            
            body {
                padding-top: 80px !important;
            }
        }

        /* ========================================
           CONTENEUR PRINCIPAL MODERNE
        ======================================== */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        /* ========================================
           EN-TÊTE MODERNE
        ======================================== */
        .page-header {
            background: var(--day-card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--day-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px var(--day-shadow);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .page-header:hover::before {
            left: 100%;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .page-subtitle {
            color: var(--day-text-light);
            font-size: 1.1rem;
            font-weight: 400;
            position: relative;
            z-index: 2;
        }

        /* ========================================
           ONGLETS MODERNES
        ======================================== */
        .modern-nav-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 0.5rem;
            background: var(--day-card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--day-border);
            border-radius: 15px;
            box-shadow: 0 4px 20px var(--day-shadow);
            overflow-x: auto;
            animation: fadeInUp 0.8s ease-out;
        }

        .modern-tab-button {
            background: transparent;
            border: 1px solid transparent;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            color: var(--day-text-light);
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            white-space: nowrap;
            min-width: fit-content;
        }

        .modern-tab-button:hover {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.2);
            color: var(--day-primary);
            transform: translateY(-2px);
        }

        .modern-tab-button.active {
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            border-color: var(--day-primary);
            color: white;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            font-weight: 600;
        }

        .modern-tab-button .badge-notification {
            background: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        /* ========================================
           CARTES MODERNES AVEC GLASSMORPHISM
        ======================================== */
        .modern-card {
            background: var(--day-card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--day-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px var(--day-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .modern-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px var(--day-shadow);
            border-color: var(--day-primary);
        }

        .modern-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .modern-card:hover::before {
            left: 100%;
        }

        /* ========================================
           GRILLE DE STATISTIQUES
        ======================================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--day-card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--day-border);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px var(--day-shadow);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px var(--day-shadow);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .stat-card:hover::before {
            left: 100%;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .stat-content {
            flex: 1;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--day-text);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--day-text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Couleurs des cartes statistiques */
        .stat-card:nth-child(1) .stat-icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .stat-card:nth-child(2) .stat-icon { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-card:nth-child(3) .stat-icon { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-card:nth-child(4) .stat-icon { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .stat-card:nth-child(5) .stat-icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        /* ========================================
           CONTENU DES ONGLETS
        ======================================== */
        .tab-content {
            min-height: 500px;
            position: relative;
        }

        .tab-pane {
            display: none;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        .tab-pane.active {
            display: block !important;
            opacity: 1 !important;
            transform: translateY(0) !important;
            animation: fadeInUp 0.6s ease-out;
        }

        /* ========================================
           TABLEAUX MODERNES
        ======================================== */
        .table-container {
            background: var(--day-card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--day-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px var(--day-shadow);
            margin-bottom: 2rem;
        }

        .table-header {
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .table {
            margin: 0;
            background: transparent;
        }

        .table th {
            background: rgba(59, 130, 246, 0.1);
            border: none;
            padding: 1rem;
            font-weight: 600;
            color: var(--day-text);
        }

        .table td {
            border: none;
            padding: 1rem;
            color: var(--day-text);
            vertical-align: middle;
        }

        .table tbody tr {
            border-bottom: 1px solid var(--day-border);
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        /* ========================================
           BOUTONS MODERNES
        ======================================== */
        .btn-modern {
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
            color: white;
        }

        .btn-success-modern {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-success-modern:hover {
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-warning-modern {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-warning-modern:hover {
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
        }

        .btn-danger-modern {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-danger-modern:hover {
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        /* ========================================
           BADGES MODERNES
        ======================================== */
        .badge-modern {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .badge-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .badge-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        .badge-info { background: linear-gradient(135deg, #06b6d4, #0891b2); color: white; }
        .badge-secondary { background: linear-gradient(135deg, #6b7280, #4b5563); color: white; }

        /* ========================================
           ANIMATIONS
        ======================================== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        /* ========================================
           FORMULAIRES MODERNES
        ======================================== */
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid var(--day-border);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            color: var(--day-text);
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: var(--day-primary);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        /* ========================================
           MODE SOMBRE AUTOMATIQUE
        ======================================== */
        @media (prefers-color-scheme: dark) {
            :root {
                --day-card-bg: var(--night-card-bg);
                --day-text: var(--night-text);
                --day-text-light: var(--night-text-light);
                --day-shadow: var(--night-shadow);
                --day-border: var(--night-border);
                --day-primary: var(--night-primary);
                --day-bg: var(--night-bg);
                --day-bg-animated: var(--night-bg-animated);
            }

            body {
                background: var(--night-bg-animated);
                background-size: 400% 400%;
            }

            .modern-card, .stat-card, .table-container, .modern-nav-tabs, .page-header {
                border: 1px solid var(--night-border);
                box-shadow: var(--night-glow);
            }

            .form-control, .form-select {
                background: rgba(15, 15, 25, 0.9);
                color: var(--night-text);
            }
        }

        /* ========================================
           RESPONSIVE
        ======================================== */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .modern-nav-tabs {
                flex-wrap: wrap;
            }

            .modern-tab-button {
                flex: 1;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .modern-card {
                padding: 1.5rem;
            }
        }

        @media (max-width: 992px) {
            #desktop-navbar, nav#desktop-navbar {
                display: none !important;
            }
        }

        /* ========================================
           GRAPHIQUES
        ======================================== */
        .chart-container {
            background: var(--day-card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--day-border);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px var(--day-shadow);
            height: 400px;
        }

        /* Styles pour les créneaux horaires */
        .time-slot {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert-item {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .alert-content {
            flex: 1;
        }

        .alert-actions {
            margin-left: 1rem;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- En-tête moderne -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-clock me-3"></i>
                Gestion du Temps de Travail
            </h1>
            <p class="page-subtitle">
                Administration et surveillance des pointages en temps réel
            </p>
        </div>

        <!-- Navigation par onglets moderne -->
        <div class="modern-nav-tabs">
            <button class="modern-tab-button active" data-target="dashboard">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </button>
            <button class="modern-tab-button" data-target="live">
                <i class="fas fa-satellite-dish"></i>
                Temps Réel
            </button>
            <button class="modern-tab-button" data-target="calendar">
                <i class="fas fa-calendar-alt"></i>
                Calendrier
            </button>
            <button class="modern-tab-button" data-target="approvals">
                <i class="fas fa-check-double"></i>
                Approbations
                <?php if (count($pending_requests) > 0): ?>
                <span class="badge-notification"><?php echo count($pending_requests); ?></span>
                <?php endif; ?>
            </button>
            <button class="modern-tab-button" data-target="settings">
                <i class="fas fa-cog"></i>
                Paramètres
            </button>
            <button class="modern-tab-button" data-target="alerts">
                <i class="fas fa-exclamation-triangle"></i>
                Alertes
                <?php if (count($alerts) > 0): ?>
                <span class="badge-notification"><?php echo count($alerts); ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Contenu des onglets -->
        <div class="tab-content">
            
            <!-- Dashboard Principal -->
            <div class="tab-pane active" id="dashboard">
                
                <!-- Statistiques en temps réel -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['users_active']; ?></div>
                            <div class="stat-label">Employés Actifs</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-coffee"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['users_on_break']; ?></div>
                            <div class="stat-label">En Pause</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['today_sessions']; ?></div>
                            <div class="stat-label">Sessions Aujourd'hui</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stats['avg_hours_week'] ?? 0, 1); ?>h</div>
                            <div class="stat-label">Moyenne Semaine</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-label">Total Employés</div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <h5 class="mb-3">
                                <i class="fas fa-chart-area text-primary me-2"></i>
                                Évolution des heures cette semaine
                            </h5>
                            <canvas id="weeklyChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <h5 class="mb-3">
                                <i class="fas fa-chart-pie text-success me-2"></i>
                                Répartition des statuts
                            </h5>
                            <canvas id="statusChart" width="200" height="200"></canvas>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Temps Réel -->
            <div class="tab-pane" id="live">
                <div class="modern-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>
                            <i class="fas fa-satellite-dish text-success me-2"></i>
                            Activité en Temps Réel
                        </h4>
                        <span class="badge-modern badge-success pulse-animation">
                            <i class="fas fa-circle me-1"></i>LIVE
                        </span>
                    </div>

                    <?php if (empty($active_users)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-user-clock fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucun employé n'est actuellement pointé</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Statut</th>
                                        <th>Heure d'arrivée</th>
                                        <th>Temps écoulé</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <span class="badge-modern badge-success">
                                                    <i class="fas fa-play"></i> Actif
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-modern badge-warning">
                                                    <i class="fas fa-pause"></i> Pause
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('H:i', strtotime($user['clock_in'])); ?></td>
                                        <td>
                                            <?php 
                                            $minutes = $user['minutes_worked'];
                                            $hours = floor($minutes / 60);
                                            $mins = $minutes % 60;
                                            echo $hours . 'h ' . $mins . 'min';
                                            ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-danger-modern btn-sm" 
                                                    onclick="forceClockOut(<?php echo $user['user_id']; ?>)">
                                                <i class="fas fa-stop"></i>
                                                Forcer Sortie
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <button class="btn btn-modern" onclick="refreshLiveData()">
                            <i class="fas fa-sync-alt"></i>
                            Actualiser
                        </button>
                    </div>
                </div>
            </div>

            <!-- Calendrier -->
            <div class="tab-pane" id="calendar">
                <div class="modern-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>
                            <i class="fas fa-calendar-alt text-primary me-2"></i>
                            Vue Calendrier
                        </h4>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" id="calendarUserFilter">
                                <option value="">Tous les employés</option>
                                <?php foreach ($all_users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-modern btn-sm" onclick="exportCalendarData()">
                                <i class="fas fa-download"></i>
                                Exporter
                            </button>
                        </div>
                    </div>
                    
                    <div id="calendar-placeholder" style="height: 500px; display: flex; align-items: center; justify-content: center;">
                        <div class="text-center">
                            <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Calendrier en cours de développement</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demandes d'approbation -->
            <div class="tab-pane" id="approvals">
                <div class="modern-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>
                            <i class="fas fa-check-double text-warning me-2"></i>
                            Demandes à approuver
                        </h4>
                        <span class="badge-modern badge-warning">
                            <?php echo count($pending_requests); ?> en attente
                        </span>
                    </div>

                    <?php if (empty($pending_requests)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">Aucune demande en attente d'approbation</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Date</th>
                                        <th>Heures</th>
                                        <th>Durée</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_requests as $request): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($request['clock_in'])); ?></td>
                                        <td>
                                            <?php echo date('H:i', strtotime($request['clock_in'])); ?>
                                            -
                                            <?php echo $request['clock_out'] ? date('H:i', strtotime($request['clock_out'])) : 'En cours'; ?>
                                        </td>
                                        <td><?php echo number_format($request['work_duration'] ?? 0, 2); ?>h</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-success-modern btn-sm" 
                                                        onclick="approveEntry(<?php echo $request['id']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                    Approuver
                                                </button>
                                                <button class="btn btn-danger-modern btn-sm" 
                                                        onclick="rejectEntry(<?php echo $request['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                    Rejeter
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Paramètres des créneaux -->
            <div class="tab-pane" id="settings">
                <div class="modern-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>
                            <i class="fas fa-cog text-primary me-2"></i>
                            Créneaux horaires globaux
                        </h4>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Définissez les heures de travail autorisées
                        </small>
                    </div>

                    <form id="slotsForm">
                        <div id="timeSlots">
                            <?php 
                            $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                            foreach ($days as $index => $day): 
                            ?>
                            <div class="time-slot">
                                <div>
                                    <strong><?php echo $day; ?></strong>
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="time" class="form-control form-control-sm" 
                                           name="start_<?php echo $index; ?>" 
                                           value="08:00" style="width: 120px;">
                                    <span>à</span>
                                    <input type="time" class="form-control form-control-sm" 
                                           name="end_<?php echo $index; ?>" 
                                           value="18:00" style="width: 120px;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="active_<?php echo $index; ?>" checked>
                                        <label class="form-check-label">Actif</label>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-modern">
                                <i class="fas fa-save"></i>
                                Sauvegarder les créneaux
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Alertes -->
            <div class="tab-pane" id="alerts">
                <div class="modern-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Alertes actives
                        </h4>
                        <span class="badge-modern badge-warning"><?php echo count($alerts); ?></span>
                    </div>

                    <?php if (empty($alerts)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                            <p class="text-muted">Aucune alerte active</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($alerts as $alert): ?>
                        <div class="alert-item">
                            <div class="alert-content">
                                <h6 class="mb-1">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    <?php echo htmlspecialchars($alert['title']); ?>
                                </h6>
                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($alert['message']); ?></p>
                            </div>
                            <div class="alert-actions">
                                <?php if ($alert['action'] === 'force_clock_out'): ?>
                                <button class="btn btn-danger-modern btn-sm" 
                                        onclick="forceClockOut(<?php echo $alert['user_id']; ?>)">
                                    <i class="fas fa-stop"></i>
                                    Action
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Gestion des onglets
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.modern-tab-button');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    
                    // Retirer les classes actives
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabPanes.forEach(pane => pane.classList.remove('active'));
                    
                    // Activer le bon onglet et panneau
                    this.classList.add('active');
                    document.getElementById(targetId).classList.add('active');
                });
            });

            // Initialiser les graphiques
            initCharts();
        });

        // Initialisation des graphiques
        function initCharts() {
            // Graphique hebdomadaire
            const weeklyCtx = document.getElementById('weeklyChart');
            if (weeklyCtx) {
                new Chart(weeklyCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode(array_reverse(array_column($daily_stats, 'date'))); ?>,
                        datasets: [{
                            label: 'Heures travaillées',
                            data: <?php echo json_encode(array_reverse(array_column($daily_stats, 'total_hours'))); ?>,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Heures'
                                }
                            }
                        }
                    }
                });
            }

            // Graphique en secteurs
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Actifs', 'En pause', 'Terminé'],
                        datasets: [{
                            data: [
                                <?php echo $stats['users_active']; ?>,
                                <?php echo $stats['users_on_break']; ?>,
                                <?php echo $stats['today_sessions'] - $stats['users_active'] - $stats['users_on_break']; ?>
                            ],
                            backgroundColor: [
                                'rgb(16, 185, 129)',
                                'rgb(245, 158, 11)',
                                'rgb(59, 130, 246)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        }

        // Actions administrateur
        function forceClockOut(userId) {
            if (confirm('Êtes-vous sûr de vouloir forcer la sortie de cet employé ?')) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=force_clock_out&user_id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Succès', data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Erreur', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('Erreur', 'Une erreur est survenue', 'error');
                });
            }
        }

        function approveEntry(entryId) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=approve_entry&entry_id=${entryId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Succès', data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Erreur', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur', 'Une erreur est survenue', 'error');
            });
        }

        function rejectEntry(entryId) {
            const reason = prompt('Raison du rejet:');
            if (reason) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=reject_entry&entry_id=${entryId}&reason=${encodeURIComponent(reason)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Succès', data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Erreur', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('Erreur', 'Une erreur est survenue', 'error');
                });
            }
        }

        function refreshLiveData() {
            location.reload();
        }

        function exportCalendarData() {
            showNotification('Info', 'Fonction d\'export en développement', 'info');
        }

        // Gestion des créneaux horaires
        document.getElementById('slotsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const slots = [];
            
            for (let i = 0; i < 7; i++) {
                const start = formData.get(`start_${i}`);
                const end = formData.get(`end_${i}`);
                const active = formData.get(`active_${i}`);
                
                if (active) {
                    slots.push({
                        day: i,
                        start: start,
                        end: end
                    });
                }
            }
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=save_global_slots&slots=${encodeURIComponent(JSON.stringify(slots))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Succès', data.message, 'success');
                } else {
                    showNotification('Erreur', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur', 'Une erreur est survenue', 'error');
            });
        });

        // Système de notifications
        function showNotification(title, message, type = 'info') {
            // Créer l'élément de notification
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <strong>${title}</strong>
                    <p>${message}</p>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">×</button>
            `;

            // Ajouter les styles si pas déjà présents
            if (!document.querySelector('#notification-styles')) {
                const styles = document.createElement('style');
                styles.id = 'notification-styles';
                styles.textContent = `
                    .notification {
                        position: fixed;
                        top: 100px;
                        right: 20px;
                        background: white;
                        border-radius: 12px;
                        padding: 1rem;
                        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
                        border-left: 4px solid #3b82f6;
                        z-index: 10001;
                        min-width: 300px;
                        animation: slideInRight 0.3s ease;
                    }
                    .notification-success { border-left-color: #10b981; }
                    .notification-error { border-left-color: #ef4444; }
                    .notification-warning { border-left-color: #f59e0b; }
                    .notification-close {
                        position: absolute;
                        top: 0.5rem;
                        right: 0.5rem;
                        background: none;
                        border: none;
                        font-size: 1.2rem;
                        cursor: pointer;
                        color: #6b7280;
                    }
                    @keyframes slideInRight {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(styles);
            }

            // Ajouter au DOM
            document.body.appendChild(notification);

            // Supprimer automatiquement après 5 secondes
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>