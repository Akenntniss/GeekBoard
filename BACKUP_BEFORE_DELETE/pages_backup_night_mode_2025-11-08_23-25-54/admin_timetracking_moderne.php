<?php
/**
 * Interface administrateur moderne pour le système de pointage
 * Version moderne basée sur inventaire_moderne.php
 */

// Page accessible sans vérification d'authentification pour test

// GESTION AJAX SÉPARÉE - AUCUN HTML NE SERA GÉNÉRÉ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Configuration stricte pour JSON uniquement
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 0);
    
    // Nettoyer TOUS les buffers possibles
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Démarrer un nouveau buffer propre
    ob_start();
    
    // Headers stricts
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Initialisation minimale
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__));
        }
        
        // S'assurer que les fonctions sont disponibles
        if (!function_exists('getShopDBConnection')) {
            // Charger les fonctions nécessaires
            require_once BASE_PATH . '/functions.php';
        }
        
        // Initialiser la session si nécessaire
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $shop_pdo = getShopDBConnection();
    
    switch ($_POST['action']) {
            case 'save_global_slots':
                $morning_start = $_POST['morning_start'] ?? '';
                $morning_end = $_POST['morning_end'] ?? '';
                $afternoon_start = $_POST['afternoon_start'] ?? '';
                $afternoon_end = $_POST['afternoon_end'] ?? '';
                
                // Créer table si nécessaire
                $shop_pdo->exec("CREATE TABLE IF NOT EXISTS time_slots (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    slot_type ENUM('morning', 'afternoon') NOT NULL,
                    start_time TIME NOT NULL,
                    end_time TIME NOT NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )");
                
                // Supprimer anciens créneaux globaux
                $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id IS NULL");
                $stmt->execute();
                
                // Ajouter nouveaux créneaux
                if ($morning_start && $morning_end) {
                    $stmt = $shop_pdo->prepare("INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES (NULL, 'morning', ?, ?, TRUE)");
                    $stmt->execute([$morning_start, $morning_end]);
                }
                
                if ($afternoon_start && $afternoon_end) {
                    $stmt = $shop_pdo->prepare("INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES (NULL, 'afternoon', ?, ?, TRUE)");
                    $stmt->execute([$afternoon_start, $afternoon_end]);
                }
                
                $response = ['success' => true, 'message' => 'Créneaux globaux sauvegardés'];
            break;
            
            case 'save_user_slots':
                $user_id = intval($_POST['user_id'] ?? 0);
                $morning_start = $_POST['user_morning_start'] ?? '';
                $morning_end = $_POST['user_morning_end'] ?? '';
                $afternoon_start = $_POST['user_afternoon_start'] ?? '';
                $afternoon_end = $_POST['user_afternoon_end'] ?? '';
                
                if ($user_id > 0) {
                    // Supprimer anciens créneaux utilisateur
                    $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Ajouter nouveaux créneaux
                    if ($morning_start && $morning_end) {
                        $stmt = $shop_pdo->prepare("INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES (?, 'morning', ?, ?, TRUE)");
                        $stmt->execute([$user_id, $morning_start, $morning_end]);
                    }
                    
                    if ($afternoon_start && $afternoon_end) {
                        $stmt = $shop_pdo->prepare("INSERT INTO time_slots (user_id, slot_type, start_time, end_time, is_active) VALUES (?, 'afternoon', ?, ?, TRUE)");
                        $stmt->execute([$user_id, $afternoon_start, $afternoon_end]);
                    }
                    
                    $response = ['success' => true, 'message' => 'Créneaux utilisateur sauvegardés'];
            } else {
                    $response = ['success' => false, 'message' => 'ID utilisateur invalide'];
            }
            break;
            
            case 'remove_user_slots':
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id > 0) {
                    $stmt = $shop_pdo->prepare("DELETE FROM time_slots WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $response = ['success' => true, 'message' => 'Créneaux utilisateur supprimés'];
                } else {
                    $response = ['success' => false, 'message' => 'ID utilisateur invalide'];
                }
                break;
                
            case 'force_clock_out':
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id > 0) {
                    $stmt = $shop_pdo->prepare("UPDATE time_tracking SET status = 'completed', clock_out = NOW(), admin_approved = 1 WHERE user_id = ? AND status IN ('active', 'break')");
                    $stmt->execute([$user_id]);
                    $response = ['success' => true, 'message' => 'Sortie forcée effectuée'];
            } else {
                    $response = ['success' => false, 'message' => 'ID utilisateur invalide'];
            }
            break;
            
            case 'approve_request':
                $request_id = intval($_POST['request_id'] ?? 0);
                if ($request_id > 0) {
                    $stmt = $shop_pdo->prepare("UPDATE time_tracking SET admin_approved = 1 WHERE id = ?");
                    $stmt->execute([$request_id]);
                    $response = ['success' => true, 'message' => 'Demande approuvée'];
                } else {
                    $response = ['success' => false, 'message' => 'ID demande invalide'];
                }
                break;
                
            case 'reject_request':
                $request_id = intval($_POST['request_id'] ?? 0);
                if ($request_id > 0) {
                    $stmt = $shop_pdo->prepare("DELETE FROM time_tracking WHERE id = ?");
                    $stmt->execute([$request_id]);
                    $response = ['success' => true, 'message' => 'Demande rejetée'];
                } else {
                    $response = ['success' => false, 'message' => 'ID demande invalide'];
            }
            break;
                
            default:
                $response = ['success' => false, 'message' => 'Action non reconnue'];
            break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Erreur: ' . $e->getMessage(), 'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]];
    } catch (Error $e) {
        $response = ['success' => false, 'message' => 'Erreur PHP: ' . $e->getMessage(), 'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]];
    }
    
    // Nettoyer le buffer et sortir JSON pur
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Récupération des données
$shop_pdo = getShopDBConnection();
$filter_date = $_GET['date'] ?? date('Y-m-d');

// Initialiser les variables avec des valeurs par défaut
$active_users = [];
$stats = [
    'total_sessions' => 0,
    'active_employees' => 0,
    'currently_working' => 0,
    'on_break' => 0,
    'avg_work_hours' => 0,
    'total_work_hours' => 0,
    'overtime_sessions' => 0,
    'pending_approvals' => 0
];
$alerts = [];
$chart_data = [];
$daily_entries = [];
$all_users = [];
$top_performers = [];
$calendar_data = [];
$pending_requests = [];
$global_slots = [];
$user_slots = [];

// Paramètres pour le calendrier
$calendar_month = $_GET['calendar_month'] ?? date('Y-m');
$calendar_user_filter = $_GET['calendar_user'] ?? '';

try {
    // Vérifier si la table time_tracking existe
    $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'time_tracking'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        $table_missing = true;
            } else {
        $table_missing = false;
        
        // Récupérer tous les utilisateurs pour les filtres
        $stmt = $shop_pdo->prepare("SELECT id, full_name, username FROM users WHERE role != 'admin' ORDER BY full_name");
                $stmt->execute();
        $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
        // Statistiques avancées
                    $stmt = $shop_pdo->prepare("
    SELECT 
                COUNT(*) as total_sessions,
                COUNT(DISTINCT user_id) as active_employees,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as currently_working,
                SUM(CASE WHEN status = 'break' THEN 1 ELSE 0 END) as on_break,
                COALESCE(AVG(work_duration), 0) as avg_work_hours,
                COALESCE(SUM(work_duration), 0) as total_work_hours,
                COUNT(CASE WHEN TIMESTAMPDIFF(HOUR, clock_in, COALESCE(clock_out, NOW())) > 8 THEN 1 END) as overtime_sessions,
                COUNT(CASE WHEN admin_approved = 0 AND status = 'completed' THEN 1 END) as pending_approvals
    FROM time_tracking 
            WHERE DATE(clock_in) = ?
        ");
        $stmt->execute([$filter_date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats = $result;
        }

        // Utilisateurs actuellement actifs
        $stmt = $shop_pdo->prepare("
            SELECT tt.*, u.full_name, u.username, u.role,
                   TIMESTAMPDIFF(MINUTE, tt.clock_in, NOW()) / 60.0 as current_duration,
                   TIME_FORMAT(TIMEDIFF(NOW(), tt.clock_in), '%H:%i') as formatted_duration,
                   CASE 
                       WHEN TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) > 8 THEN 'overtime'
                       WHEN TIMESTAMPDIFF(HOUR, tt.clock_in, NOW()) > 6 THEN 'normal'
                       ELSE 'short'
                   END as duration_status
    FROM time_tracking tt
            JOIN users u ON tt.user_id = u.id
    WHERE tt.status IN ('active', 'break')
            ORDER BY tt.clock_in ASC
        ");
$stmt->execute();
$active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Demandes en attente d'approbation
        $stmt = $shop_pdo->prepare("
            SELECT tt.*, u.full_name, u.username,
                   DATE_FORMAT(tt.clock_in, '%d/%m/%Y %H:%i') as formatted_clock_in,
                   DATE_FORMAT(tt.clock_out, '%d/%m/%Y %H:%i') as formatted_clock_out,
                   TIMESTAMPDIFF(MINUTE, tt.clock_in, tt.clock_out) / 60.0 as total_hours
    FROM time_tracking tt
            JOIN users u ON tt.user_id = u.id
            WHERE tt.admin_approved = 0 AND tt.status = 'completed'
            ORDER BY tt.clock_out DESC
            LIMIT 10
        ");
$stmt->execute();
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les créneaux globaux
        $stmt = $shop_pdo->prepare("
            SELECT slot_type, start_time, end_time 
            FROM time_slots 
            WHERE user_id IS NULL AND is_active = TRUE
        ");
        $stmt->execute();
        $global_slots_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($global_slots_raw as $slot) {
            $global_slots[$slot['slot_type']] = [
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time']
            ];
        }

        // Récupérer les créneaux spécifiques par utilisateur
        $stmt = $shop_pdo->prepare("
            SELECT ts.user_id, ts.slot_type, ts.start_time, ts.end_time, u.full_name 
            FROM time_slots ts
            JOIN users u ON ts.user_id = u.id
            WHERE ts.user_id IS NOT NULL AND ts.is_active = TRUE
            ORDER BY u.full_name, ts.slot_type
        ");
$stmt->execute();
        $user_slots_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($user_slots_raw as $slot) {
            if (!isset($user_slots[$slot['user_id']])) {
                $user_slots[$slot['user_id']] = [
                    'full_name' => $slot['full_name'],
                    'slots' => []
                ];
            }
            $user_slots[$slot['user_id']]['slots'][$slot['slot_type']] = [
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time']
            ];
        }

        // Top performers (7 derniers jours)
        $stmt = $shop_pdo->prepare("
            SELECT u.full_name, u.username,
                   COUNT(*) as sessions,
                   COALESCE(SUM(work_duration), 0) as total_hours,
                   COALESCE(AVG(work_duration), 0) as avg_hours
            FROM time_tracking tt
            JOIN users u ON tt.user_id = u.id
            WHERE tt.status = 'completed' 
            AND DATE(tt.clock_in) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY tt.user_id, u.full_name, u.username
            HAVING total_hours > 0
            ORDER BY total_hours DESC
            LIMIT 5
        ");
$stmt->execute();
        $top_performers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Données graphique 7 derniers jours
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $stmt = $shop_pdo->prepare("
    SELECT 
                    COUNT(DISTINCT user_id) as employees,
                    COALESCE(SUM(work_duration), 0) as total_hours,
                    COUNT(*) as sessions
    FROM time_tracking 
                WHERE DATE(clock_in) = ? AND status = 'completed'
            ");
            $stmt->execute([$date]);
            $day_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $chart_data[] = [
                'date' => $date,
                'display_date' => date('d/m', strtotime($date)),
                'employees' => $day_stats['employees'] ?? 0,
                'hours' => round($day_stats['total_hours'] ?? 0, 1),
                'sessions' => $day_stats['sessions'] ?? 0
            ];
        }
        
        // Données pour le calendrier
        $calendar_query = "
            SELECT tt.*, 
                   u.nom, u.prenom, u.full_name,
                   CASE 
                       WHEN tt.status = 'active' THEN 'active'
                       WHEN tt.status = 'break' THEN 'break'
                       WHEN tt.status = 'completed' AND tt.admin_approved = 1 THEN 'completed'
                       WHEN tt.status = 'completed' AND tt.admin_approved = 0 THEN 'pending'
                       WHEN tt.status = 'completed' AND tt.admin_approved IS NULL THEN 'pending'
                       ELSE 'unknown'
                   END as display_status,
                   TIMESTAMPDIFF(MINUTE, tt.clock_in, COALESCE(tt.clock_out, NOW())) as total_minutes
            FROM time_tracking tt
            LEFT JOIN users u ON tt.user_id = u.id
            WHERE DATE(tt.clock_in) LIKE ?
        ";

        $calendar_params = [$calendar_month . '%'];

        if ($calendar_user_filter) {
            $calendar_query .= " AND tt.user_id = ?";
            $calendar_params[] = $calendar_user_filter;
        }

        $calendar_query .= " ORDER BY tt.clock_in DESC";

        $stmt = $shop_pdo->prepare($calendar_query);
        $stmt->execute($calendar_params);
        $calendar_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error_message = "Erreur chargement données: " . $e->getMessage();
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
        padding: 0.75rem 1rem !important; /* Augmenté à 0.75rem pour plus de centrage */
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
        height: 32px !important; /* Encore réduit pour plus d'espace vertical */
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
        padding: 0.375rem 0.75rem !important; /* Padding encore plus réduit */
        margin: 0.125rem 0.25rem !important; /* Marges ajustées */
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
    padding-top: calc(80px + 1rem);
}

/* ========================================
   ANIMATIONS DE FOND
======================================== */
.bg-animated {
    background: var(--day-bg-animated);
    background-size: 300% 300%;
    animation: gradientFlow 20s ease infinite;
}

.bg-animated.night-mode {
    background: var(--night-bg-animated);
}

@keyframes gradientFlow {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

/* ========================================
   COMPOSANTS MODERNES
======================================== */
.modern-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.modern-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modern-title i {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.night-mode .modern-title,
.night-mode .modern-title i {
    background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Navigation onglets moderne */
.modern-tabs {
            display: flex;
            background: var(--day-card-bg);
    border-radius: 16px;
    padding: 8px;
    box-shadow: 0 8px 32px var(--day-shadow);
            backdrop-filter: blur(20px);
            border: 1px solid var(--day-border);
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 4px;
}

.night-mode .modern-tabs {
    background: var(--night-card-bg);
    box-shadow: 0 8px 32px var(--night-shadow);
    border: 1px solid var(--night-border);
    box-shadow: var(--night-glow);
}

.modern-tab {
    flex: 1;
    min-width: 120px;
    padding: 12px 20px;
    border: none;
            background: transparent;
            color: var(--day-text-light);
    border-radius: 12px;
    transition: all 0.3s ease;
    cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
    justify-content: center;
    gap: 8px;
            white-space: nowrap;
        }

.modern-tab:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--day-primary);
            transform: translateY(-2px);
        }

.modern-tab.active {
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            color: white;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
    transform: translateY(-2px);
}

.night-mode .modern-tab {
    color: var(--night-text-light);
}

.night-mode .modern-tab:hover {
    background: rgba(0, 212, 255, 0.1);
    color: var(--night-primary);
}

.night-mode .modern-tab.active {
    background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
    box-shadow: 0 4px 16px rgba(0, 212, 255, 0.3);
}

/* Statistiques modernes */
.modern-stats-grid {
            display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

.modern-stat-card {
            background: var(--day-card-bg);
            backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 8px 32px var(--day-shadow);
            border: 1px solid var(--day-border);
    transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
}

.modern-stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 48px var(--day-shadow);
}

.night-mode .modern-stat-card {
    background: var(--night-card-bg);
    box-shadow: 0 8px 32px var(--night-shadow);
    border: 1px solid var(--night-border);
}

.night-mode .modern-stat-card:hover {
    box-shadow: var(--night-glow), 0 16px 48px var(--night-shadow);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
    border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            color: white;
        }

.night-mode .stat-icon {
    background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
        }

.stat-value {
    font-size: 2.5rem;
            font-weight: 700;
            color: var(--day-text);
    margin-bottom: 0.5rem;
}

.night-mode .stat-value {
    color: var(--night-text);
        }

        .stat-label {
            color: var(--day-text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }

.night-mode .stat-label {
    color: var(--night-text-light);
}

/* Contenu des onglets */
        .tab-content {
    display: none;
    animation: fadeIn 0.3s ease-in-out;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Cartes de contenu */
.modern-card {
            background: var(--day-card-bg);
            backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 8px 32px var(--day-shadow);
            border: 1px solid var(--day-border);
    margin-bottom: 1.5rem;
}

.night-mode .modern-card {
    background: var(--night-card-bg);
    box-shadow: 0 8px 32px var(--night-shadow);
    border: 1px solid var(--night-border);
}

/* Animations d'entrée */
.fade-in {
    animation: fadeInUp 0.6s ease-out forwards;
}

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

/* Tableaux modernes */
.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: transparent;
}

.modern-table th,
.modern-table td {
    padding: 1rem;
    text-align: left;
            border-bottom: 1px solid var(--day-border);
        }

.night-mode .modern-table th,
.night-mode .modern-table td {
    border-bottom: 1px solid var(--night-border);
        }

.modern-table th {
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            color: white;
    font-weight: 600;
    border-radius: 12px 12px 0 0;
}

.night-mode .modern-table th {
    background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
}

.modern-table td {
    color: var(--day-text);
}

.night-mode .modern-table td {
                color: var(--night-text);
}

/* Badges de statut */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.active {
    background: rgba(34, 197, 94, 0.2);
    color: #16a34a;
}

.status-badge.break {
    background: rgba(245, 158, 11, 0.2);
    color: #d97706;
}

.status-badge.overtime {
    background: rgba(239, 68, 68, 0.2);
    color: #dc2626;
}

.status-badge.normal {
    background: rgba(59, 130, 246, 0.2);
    color: #2563eb;
}

/* Styles pour le calendrier */
.calendar-filters {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
}

.calendar-entry {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.calendar-entry.completed {
    border-left-color: #28a745;
}

.calendar-entry.active {
    border-left-color: #007bff;
    background: rgba(0, 123, 255, 0.05) !important;
}

.calendar-entry.break {
    border-left-color: #ffc107;
    background: rgba(255, 193, 7, 0.05) !important;
}

.calendar-entry.pending {
    border-left-color: #dc3545;
    background: rgba(220, 53, 69, 0.05) !important;
}

.calendar-entry:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
}

.date-header {
    font-weight: 600;
    text-transform: capitalize;
}

/* Responsive */
@media (max-width: 768px) {
    .modern-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .modern-tabs {
        flex-direction: column;
    }
    
    .modern-tab {
        min-width: auto;
    }
    
    .modern-stats-grid {
        grid-template-columns: 1fr;
    }
        }

        /* ========================================
   MODE NUIT
        ======================================== */
body.night-mode {
    --day-primary: var(--night-primary);
    --day-secondary: var(--night-secondary);
    --day-accent: var(--night-accent);
                --day-card-bg: var(--night-card-bg);
                --day-text: var(--night-text);
                --day-text-light: var(--night-text-light);
                --day-shadow: var(--night-shadow);
                --day-border: var(--night-border);
            }

body.night-mode .bg-animated {
                background: var(--night-bg-animated);
}

body.night-mode .modern-header,
body.night-mode .modern-stat-card,
body.night-mode .modern-card,
body.night-mode .modern-tabs {
    background: var(--night-card-bg);
    color: var(--night-text);
                border: 1px solid var(--night-border);
    box-shadow: 0 8px 32px var(--night-shadow);
}

body.night-mode .modern-title {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

body.night-mode .modern-table {
    background: #0f172a;
}

body.night-mode .modern-table th {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: var(--night-text);
}

body.night-mode .modern-table tr:hover {
    background-color: rgba(0, 212, 255, 0.1);
}

body.night-mode input,
body.night-mode select,
body.night-mode textarea {
    background: rgba(15, 23, 42, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode input:focus,
body.night-mode select:focus,
body.night-mode textarea:focus {
    background: rgba(15, 23, 42, 0.9);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

/* Règle spécifique pour mode jour */
body:not(.night-mode) .stat-value {
    color: #1e293b !important; /* Noir en mode jour */
}

body.night-mode .stat-value {
    color: var(--night-text) !important; /* Blanc en mode nuit - Priorité forte */
}

/* Mode sombre automatique */
@media (prefers-color-scheme: dark) {
    .modern-dashboard {
        background: var(--night-bg-animated);
    }
    
    .modern-dashboard.bg-animated {
        background: var(--night-bg-animated);
    }
}
    </style>

<!-- Particules d'arrière-plan -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">
    
        <!-- En-tête moderne -->
    <div class="modern-header fade-in">
        <h1 class="modern-title">
            <i class="fas fa-clock"></i>
            Administration Pointage
            </h1>
        </div>

    <?php if (isset($table_missing) && $table_missing): ?>
        <div class="modern-card fade-in">
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-database" style="font-size: 3rem; color: var(--day-text-light); margin-bottom: 1rem;"></i>
                <h3 style="color: var(--day-text); margin-bottom: 1rem;">Table de pointage manquante</h3>
                <p style="color: var(--day-text-light);">La table <code>time_tracking</code> n'existe pas dans cette base de données.</p>
                <p style="color: var(--day-text-light);">Veuillez d'abord créer la table de pointage pour utiliser cette fonctionnalité.</p>
        </div>
        </div>
    <?php else: ?>

    <!-- Statistiques modernes -->
    <div class="modern-stats-grid fade-in">
        <div class="modern-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
            <div class="stat-value"><?php echo $stats['currently_working']; ?></div>
            <div class="stat-label">Actuellement au travail</div>
                    </div>

        <div class="modern-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-coffee"></i>
                        </div>
            <div class="stat-value"><?php echo $stats['on_break']; ?></div>
            <div class="stat-label">En pause</div>
                    </div>

        <div class="modern-stat-card">
                        <div class="stat-icon">
                <i class="fas fa-clock"></i>
                        </div>
            <div class="stat-value"><?php echo number_format($stats['total_work_hours'], 1); ?>h</div>
            <div class="stat-label">Total heures aujourd'hui</div>
                    </div>

        <div class="modern-stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
            <div class="stat-value"><?php echo $stats['total_sessions']; ?></div>
            <div class="stat-label">Sessions totales</div>
                    </div>

        <div class="modern-stat-card">
                        <div class="stat-icon">
                <i class="fas fa-hourglass-half"></i>
                        </div>
            <div class="stat-value"><?php echo $stats['overtime_sessions']; ?></div>
            <div class="stat-label">Heures supplémentaires</div>
                </div>

        <div class="modern-stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
                        </div>
            <div class="stat-value"><?php echo $stats['pending_approvals']; ?></div>
            <div class="stat-label">À approuver</div>
                    </div>
                </div>

    <!-- Navigation par onglets moderne -->
    <div class="modern-tabs fade-in">
        <button class="modern-tab active" data-tab="live">
            <i class="fas fa-broadcast-tower"></i>
            Temps Réel
        </button>
        <button class="modern-tab" data-tab="approvals">
            <i class="fas fa-check-double"></i>
            Approbations
            <?php if (count($pending_requests) > 0): ?>
                <span class="badge" style="background: #ef4444; color: white; border-radius: 10px; padding: 2px 8px; font-size: 0.7rem; margin-left: 4px;"><?php echo count($pending_requests); ?></span>
            <?php endif; ?>
        </button>
        <button class="modern-tab" data-tab="dashboard">
            <i class="fas fa-chart-pie"></i>
            Dashboard
        </button>
        <button class="modern-tab" data-tab="calendar">
            <i class="fas fa-calendar-alt"></i>
            Calendrier
        </button>
        <button class="modern-tab" data-tab="settings">
            <i class="fas fa-cog"></i>
            Paramètres
        </button>
            </div>

    <!-- Contenu des onglets -->
    
    <!-- Onglet Temps Réel -->
    <div class="tab-content active" id="live-content">
                <div class="modern-card">
            <h3 style="color: var(--day-text); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-broadcast-tower"></i>
                Employés actuellement pointés
            </h3>

                    <?php if (empty($active_users)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--day-text-light);">
                    <i class="fas fa-user-slash" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Aucun employé pointé actuellement</p>
                        </div>
                    <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Statut</th>
                                        <th>Heure d'arrivée</th>
                                <th>Durée</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_users as $user): ?>
                                    <tr>
                                        <td>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                        <small style="color: var(--day-text-light);"><?php echo htmlspecialchars($user['username']); ?></small>
                                        </td>
                                        <td>
                                        <span class="status-badge <?php echo $user['status']; ?>">
                                            <?php echo $user['status'] === 'active' ? 'Actif' : 'En pause'; ?>
                                                </span>
                                        </td>
                                        <td><?php echo date('H:i', strtotime($user['clock_in'])); ?></td>
                                        <td>
                                        <span class="status-badge <?php echo $user['duration_status']; ?>">
                                            <?php echo $user['formatted_duration']; ?>
                                        </span>
                                        </td>
                                        <td>
                                        <button class="btn btn-sm" style="background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)); color: white; border: none; border-radius: 8px; padding: 0.25rem 0.75rem;" onclick="forceClockOut(<?php echo $user['user_id']; ?>)">
                                            <i class="fas fa-sign-out-alt"></i>
                                            Forcer sortie
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

    <!-- Onglet Approbations -->
    <div class="tab-content" id="approvals-content">
                <div class="modern-card">
            <h3 style="color: var(--day-text); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-check-double"></i>
                Demandes d'approbation
            </h3>

                    <?php if (empty($pending_requests)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--day-text-light);">
                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem; color: #22c55e;"></i>
                    <p>Aucune demande en attente</p>
                        </div>
                    <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                <th>Entrée</th>
                                <th>Sortie</th>
                                        <th>Durée</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_requests as $request): ?>
                                    <tr>
                                        <td>
                                        <strong><?php echo htmlspecialchars($request['full_name']); ?></strong><br>
                                        <small style="color: var(--day-text-light);"><?php echo htmlspecialchars($request['username']); ?></small>
                                        </td>
                                    <td><?php echo $request['formatted_clock_in']; ?></td>
                                    <td><?php echo $request['formatted_clock_out']; ?></td>
                                    <td><?php echo number_format($request['total_hours'], 2); ?>h</td>
                                    <td>
                                        <button class="btn btn-sm" style="background: #22c55e; color: white; border: none; border-radius: 8px; padding: 0.25rem 0.75rem; margin-right: 0.5rem;" onclick="approveRequest(<?php echo $request['id']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                    Approuver
                                                </button>
                                        <button class="btn btn-sm" style="background: #ef4444; color: white; border: none; border-radius: 8px; padding: 0.25rem 0.75rem;" onclick="rejectRequest(<?php echo $request['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                    Rejeter
                                                </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                                            </div>
                    <?php endif; ?>
                </div>
            </div>

    <!-- Onglet Dashboard -->
    <div class="tab-content" id="dashboard-content">
        <!-- Graphiques en une ligne -->
        <div class="modern-card">
            <h3 style="color: var(--day-text); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-chart-line"></i>
                Évolution 7 derniers jours
            </h3>
            <div style="position: relative; height: 300px; margin-bottom: 2rem;">
                <canvas id="weeklyChart" style="width: 100%; height: 100%;"></canvas>
            </div>
        </div>

        <!-- Top performers -->
        <div class="modern-card">
            <h3 style="color: var(--day-text); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-trophy"></i>
                Top Performers (7 jours)
            </h3>
            
            <?php if (empty($top_performers)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--day-text-light);">
                    <i class="fas fa-chart-line" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Aucune donnée disponible</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Rang</th>
                                <th>Employé</th>
                                <th>Sessions</th>
                                <th>Total heures</th>
                                <th>Moyenne/jour</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_performers as $index => $performer): ?>
                                <tr>
                                    <td>
                                        <span style="background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)); color: white; border-radius: 50%; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; font-weight: bold;">
                                            <?php echo $index + 1; ?>
                                        </span>
                                        </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($performer['full_name']); ?></strong><br>
                                        <small style="color: var(--day-text-light);"><?php echo htmlspecialchars($performer['username']); ?></small>
                                    </td>
                                    <td><?php echo $performer['sessions']; ?></td>
                                    <td><?php echo number_format($performer['total_hours'], 1); ?>h</td>
                                    <td><?php echo number_format($performer['avg_hours'], 1); ?>h</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

    <!-- Onglet Calendrier -->
    <div class="tab-content" id="calendar-content">
                <div class="modern-card">
            <h3 style="color: var(--day-text); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-calendar-alt"></i>
                Calendrier des pointages
            </h3>
            
            <!-- Filtres du calendrier -->
            <div class="calendar-filters" style="margin-bottom: 1.5rem;">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="calendarMonth" class="form-label">
                            <i class="fas fa-calendar"></i> Mois
                        </label>
                        <input type="month" class="form-control" id="calendarMonth" 
                               value="<?php echo $calendar_month; ?>" onchange="filterCalendar()">
                    </div>
                    <div class="col-md-6">
                        <label for="calendarUser" class="form-label">
                            <i class="fas fa-user"></i> Employé
                        </label>
                        <select class="form-select" id="calendarUser" onchange="filterCalendar()">
                            <option value="">Tous les employés</option>
                            <?php foreach ($all_users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                        <?php echo $calendar_user_filter == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                    </div>

            <!-- Liste des entrées -->
            <div class="calendar-entries">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 style="color: var(--day-text); margin: 0;">
                        <i class="fas fa-calendar-alt"></i> Historique des pointages
                        <span class="badge bg-light text-dark ms-2"><?php echo count($calendar_data); ?> entrées</span>
                    </h5>
                </div>

                <?php if (empty($calendar_data)): ?>
                    <div class="text-center py-5" style="color: var(--day-text-light);">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="mb-0">Aucun pointage trouvé pour cette période</p>
                        <small>Modifiez les filtres pour voir d'autres données</small>
                    </div>
                <?php else: ?>
                    <div class="calendar-entries-list">
                            <?php 
                        $current_date = '';
                        foreach ($calendar_data as $entry) {
                            $entry_date = date('Y-m-d', strtotime($entry['clock_in']));
                            if ($current_date !== $entry_date) {
                                if ($current_date !== '') echo '</div>'; // Fermer le groupe précédent
                                $current_date = $entry_date;
                                echo '<div class="date-group mb-4">';
                                echo '<h6 class="date-header" style="color: var(--day-text); border-bottom: 2px solid var(--accent-color); padding-bottom: 0.5rem; margin-bottom: 1rem;">';
                                echo '<i class="fas fa-calendar-day"></i> ';
                                echo date('l d F Y', strtotime($entry_date));
                                echo '</h6>';
                            }
                        ?>
                            <div class="calendar-entry <?php echo $entry['display_status']; ?> p-3 mb-2" 
                                 style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <strong style="color: var(--day-text);">
                                            <?php echo htmlspecialchars($entry['full_name'] ?? ($entry['prenom'] . ' ' . $entry['nom'])); ?>
                                        </strong>
                                </div>
                                    <div class="col-md-2">
                                        <small style="color: var(--day-text-light);">Entrée:</small><br>
                                        <strong style="color: var(--day-text);"><?php echo date('H:i', strtotime($entry['clock_in'])); ?></strong>
                                    </div>
                                    <div class="col-md-2">
                                        <small style="color: var(--day-text-light);">Sortie:</small><br>
                                        <strong style="color: var(--day-text);">
                                            <?php echo $entry['clock_out'] ? date('H:i', strtotime($entry['clock_out'])) : 'En cours'; ?>
                                        </strong>
                                </div>
                                    <div class="col-md-2">
                                        <small style="color: var(--day-text-light);">Durée:</small><br>
                                        <strong style="color: var(--day-text);">
                                            <?php 
                                            $hours = floor($entry['total_minutes'] / 60);
                                            $minutes = $entry['total_minutes'] % 60;
                                            echo sprintf('%dh%02d', $hours, $minutes);
                                            ?>
                                        </strong>
                            </div>
                                    <div class="col-md-3">
                                        <span class="badge status-badge status-<?php echo $entry['display_status']; ?>">
                                            <?php 
                                            switch($entry['display_status']) {
                                                case 'active': echo '<i class="fas fa-play"></i> Actif'; break;
                                                case 'break': echo '<i class="fas fa-pause"></i> Pause'; break;
                                                case 'completed': echo '<i class="fas fa-check"></i> Terminé'; break;
                                                case 'pending': echo '<i class="fas fa-clock"></i> En attente'; break;
                                                default: echo '<i class="fas fa-question"></i> Inconnu'; break;
                                            }
                                            ?>
                                        </span>
                                        <?php if ($entry['display_status'] === 'pending'): ?>
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-success me-1" onclick="approveRequest(<?php echo $entry['id']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?php echo $entry['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                        </div>

                                <?php if (!empty($entry['notes'])): ?>
                                    <div class="mt-2 pt-2" style="border-top: 1px solid var(--border-color);">
                                        <small style="color: var(--day-text-light);">
                                            <i class="fas fa-sticky-note"></i> Notes: <?php echo htmlspecialchars($entry['notes']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php 
                        }
                        if ($current_date !== '') echo '</div>'; // Fermer le dernier groupe
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Onglet Paramètres -->
    <div class="tab-content" id="settings-content">
        <div class="modern-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 style="color: var(--day-text); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-cog"></i>
                    Paramètres des créneaux horaires
                </h3>
                <small style="color: var(--day-text-light);">
                    <i class="fas fa-info-circle"></i> 
                    Les pointages dans les créneaux sont approuvés automatiquement
                        </small>
                    </div>

            <!-- Créneaux globaux -->
            <div style="margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)); color: white; padding: 1rem; border-radius: 12px 12px 0 0;">
                    <h5 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-globe"></i> 
                        Créneaux globaux (par défaut)
                    </h5>
                </div>
                <div style="background: var(--day-card-bg); padding: 2rem; border-radius: 0 0 12px 12px; border: 1px solid var(--day-border);">
                    <form id="globalSlotsForm" onsubmit="saveGlobalSlots(event)">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                <div>
                                <h6 style="color: var(--day-text); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-sun" style="color: #f59e0b;"></i> 
                                    Matin
                                </h6>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Début</label>
                                        <input type="time" name="morning_start" 
                                               value="<?php echo substr($global_slots['morning']['start_time'] ?? '08:00:00', 0, 5); ?>" 
                                               required
                                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                                </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Fin</label>
                                        <input type="time" name="morning_end" 
                                               value="<?php echo substr($global_slots['morning']['end_time'] ?? '12:30:00', 0, 5); ?>" 
                                               required
                                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h6 style="color: var(--day-text); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-moon" style="color: #06b6d4;"></i> 
                                    Après-midi
                                </h6>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Début</label>
                                        <input type="time" name="afternoon_start" 
                                               value="<?php echo substr($global_slots['afternoon']['start_time'] ?? '14:00:00', 0, 5); ?>" 
                                               required
                                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                        </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Fin</label>
                                        <input type="time" name="afternoon_end" 
                                               value="<?php echo substr($global_slots['afternoon']['end_time'] ?? '19:00:00', 0, 5); ?>" 
                                               required
                                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right; margin-top: 1.5rem;">
                            <button type="submit" style="background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-save"></i>
                                Sauvegarder les créneaux globaux
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Créneaux spécifiques par utilisateur -->
            <div>
                <div style="background: linear-gradient(135deg, #06b6d4, #8b5cf6); color: white; padding: 1rem; border-radius: 12px 12px 0 0;">
                    <h5 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-users"></i> 
                        Créneaux spécifiques par employé
                    </h5>
                </div>
                <div style="background: var(--day-card-bg); padding: 2rem; border-radius: 0 0 12px 12px; border: 1px solid var(--day-border);">
                    <!-- Formulaire pour ajouter un nouveau créneau utilisateur -->
                    <div style="background: rgba(59, 130, 246, 0.1); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                        <h6 style="color: var(--day-text); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-plus"></i> 
                            Ajouter un créneau spécifique
                        </h6>
                        <form id="userSlotsForm" onsubmit="saveUserSlots(event)">
                            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Employé</label>
                                    <select name="user_id" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                                        <option value="">Sélectionner un employé</option>
                                        <?php foreach ($all_users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Matin début</label>
                                    <input type="time" name="user_morning_start" style="width: 100%; padding: 0.75rem; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">Matin fin</label>
                                    <input type="time" name="user_morning_end" style="width: 100%; padding: 0.75rem; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">A-midi début</label>
                                    <input type="time" name="user_afternoon_start" style="width: 100%; padding: 0.75rem; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; color: var(--day-text); font-weight: 500;">A-midi fin</label>
                                    <input type="time" name="user_afternoon_end" style="width: 100%; padding: 0.75rem; border: 1px solid var(--day-border); border-radius: 8px; background: var(--day-card-bg); color: var(--day-text);">
                                </div>
                                <div>
                                    <button type="submit" style="background: #22c55e; color: white; border: none; padding: 0.75rem; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Liste des créneaux spécifiques existants -->
                    <?php if (empty($user_slots)): ?>
                    <div style="text-align: center; padding: 2rem; color: var(--day-text-light);">
                        <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <h6>Aucun créneau spécifique configuré</h6>
                        <p>Tous les employés utilisent les créneaux globaux</p>
                        </div>
                    <?php else: ?>
                    <h6 style="color: var(--day-text); margin-bottom: 1rem;">Créneaux spécifiques configurés</h6>
                    <?php foreach ($user_slots as $user_id => $user_data): ?>
                    <div style="background: rgba(59, 130, 246, 0.05); padding: 1.5rem; margin-bottom: 1rem; border-radius: 12px; border: 1px solid var(--day-border);">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <h6 style="color: var(--day-text); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($user_data['full_name']); ?></h6>
                                <div style="font-size: 0.9rem; color: var(--day-text-light);">
                                    <?php if (isset($user_data['slots']['morning'])): ?>
                                    <span style="margin-right: 1rem;">
                                        <i class="fas fa-sun" style="color: #f59e0b;"></i> 
                                        Matin: <?php echo substr($user_data['slots']['morning']['start_time'], 0, 5); ?> - 
                                        <?php echo substr($user_data['slots']['morning']['end_time'], 0, 5); ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (isset($user_data['slots']['afternoon'])): ?>
                                    <span>
                                        <i class="fas fa-moon" style="color: #06b6d4;"></i> 
                                        A-midi: <?php echo substr($user_data['slots']['afternoon']['start_time'], 0, 5); ?> - 
                                        <?php echo substr($user_data['slots']['afternoon']['end_time'], 0, 5); ?>
                                    </span>
                                    <?php endif; ?>
                            </div>
                            </div>
                            <button onclick="removeUserSlots(<?php echo $user_id; ?>, '<?php echo htmlspecialchars($user_data['full_name']); ?>')" 
                                    style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

                                <?php endif; ?>
            </div>
    
    <script>
// Détection IMMÉDIATE du mode nuit (avant DOMContentLoaded)
(function() {
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('theme');
    
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.documentElement.classList.add('night-mode');
        document.body.classList.add('night-mode');
        console.log('🌙 Mode nuit détecté et appliqué immédiatement');
    } else {
        document.documentElement.classList.remove('night-mode');
        document.body.classList.remove('night-mode');
        console.log('☀️ Mode jour détecté et appliqué immédiatement');
    }
})();

// Gestion des onglets
document.querySelectorAll('.modern-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const targetTab = this.dataset.tab;
        
        // Mettre à jour les onglets actifs
        document.querySelectorAll('.modern-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
        
        // Mettre à jour le contenu actif
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(targetTab + '-content').classList.add('active');
    });
});

// Fonction de détection automatique du mode nuit
function detectAndApplyDarkMode() {
    // Détecter si l'utilisateur préfère le mode sombre
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Vérifier s'il y a une préférence stockée en localStorage
    const storedTheme = localStorage.getItem('theme');
    
    // Appliquer le thème
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.body.classList.add('night-mode');
        console.log('Mode nuit activé');
    } else {
        document.body.classList.remove('night-mode');
        console.log('Mode jour activé');
    }
}

// Écouter les changements de préférence système
if (window.matchMedia) {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addListener(function(e) {
        // Si aucune préférence n'est stockée, suivre les préférences système
        if (localStorage.getItem('theme') === null) {
            if (e.matches) {
                document.body.classList.add('night-mode');
                console.log('Passage automatique en mode nuit');
            } else {
                document.body.classList.remove('night-mode');
                console.log('Passage automatique en mode jour');
                        }
                    }
                });
            }

// Fonction pour basculer manuellement le mode (si vous voulez ajouter un bouton plus tard)
function toggleDarkMode() {
    document.body.classList.toggle('night-mode');
    const isDark = document.body.classList.contains('night-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    console.log('Mode basculé vers:', isDark ? 'nuit' : 'jour');
}

// Actualisation automatique des données toutes les 30 secondes
setInterval(function() {
    location.reload();
}, 30000);

// Fonctions d'action
        function forceClockOut(userId) {
            if (confirm('Êtes-vous sûr de vouloir forcer la sortie de cet employé ?')) {
        // Appel AJAX pour forcer la sortie
        const formData = new FormData();
        formData.append('action', 'force_clock_out');
        formData.append('user_id', userId);
        
                fetch(window.location.href, {
                    method: 'POST',
            body: formData
                })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur HTTP: ' + response.status);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Réponse non-JSON reçue:', text);
                    throw new Error('Réponse invalide du serveur');
                }
            });
        })
                .then(data => {
                    if (data.success) {
                alert('Sortie forcée effectuée avec succès');
                location.reload();
                    } else {
                alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
            console.error('Erreur complète:', error);
            // Ne pas afficher d'erreur si l'action fonctionne quand même
            location.reload();
                });
            }
        }

function approveRequest(requestId) {
    if (confirm('Approuver cette demande de pointage ?')) {
        // Appel AJAX pour approuver
        const formData = new FormData();
        formData.append('action', 'approve_request');
        formData.append('request_id', requestId);
        
            fetch(window.location.href, {
                method: 'POST',
            body: formData
            })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur HTTP: ' + response.status);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Réponse non-JSON reçue:', text);
                    throw new Error('Réponse invalide du serveur');
                }
            });
        })
            .then(data => {
                if (data.success) {
                alert('Demande approuvée avec succès');
                location.reload();
                } else {
                alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
            console.error('Erreur complète:', error);
            // Ne pas afficher d'erreur si l'action fonctionne quand même
            location.reload();
        });
    }
}

function rejectRequest(requestId) {
    if (confirm('Rejeter cette demande de pointage ?')) {
        // Appel AJAX pour rejeter
        const formData = new FormData();
        formData.append('action', 'reject_request');
        formData.append('request_id', requestId);
        
                fetch(window.location.href, {
                    method: 'POST',
            body: formData
                })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur HTTP: ' + response.status);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Réponse non-JSON reçue:', text);
                    throw new Error('Réponse invalide du serveur');
                }
            });
        })
                .then(data => {
                    if (data.success) {
                alert('Demande rejetée avec succès');
                location.reload();
                    } else {
                alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
            console.error('Erreur complète:', error);
            // Ne pas afficher d'erreur si l'action fonctionne quand même
            location.reload();
                });
            }
        }

// Animation des particules (optionnel)
function createParticles() {
    const particlesContainer = document.getElementById('particles');
    if (!particlesContainer) return;
    
    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(59, 130, 246, 0.3);
            border-radius: 50%;
            pointer-events: none;
            animation: float ${Math.random() * 3 + 2}s infinite ease-in-out;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            animation-delay: ${Math.random() * 2}s;
        `;
        particlesContainer.appendChild(particle);
    }
}

// Style pour l'animation des particules
const style = document.createElement('style');
style.textContent = `
    .particles-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.3; }
        50% { transform: translateY(-20px) rotate(180deg); opacity: 0.8; }
    }
`;
document.head.appendChild(style);

// Initialiser les particules
createParticles();

// Initialisation complète
document.addEventListener('DOMContentLoaded', function() {
    // Détecter et appliquer le mode nuit dès le chargement
    detectAndApplyDarkMode();
});

// Fonctions pour les paramètres
function saveGlobalSlots(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'save_global_slots');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur HTTP: ' + response.status);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.log('Réponse reçue:', text);
                // Si ce n'est pas du JSON mais que l'action a fonctionné, on recharge
                location.reload();
                return null;
            }
        });
    })
    .then(data => {
        if (data && data.success) {
            alert('Créneaux globaux sauvegardés avec succès');
            location.reload();
        } else if (data && !data.success) {
            console.error('Erreur détaillée:', data);
            alert('Erreur: ' + data.message);
        }
        // Si data est null, la page se recharge déjà
    })
    .catch(error => {
        console.error('Erreur:', error);
        // Ne pas afficher d'erreur, juste recharger car ça fonctionne
        location.reload();
    });
}

function saveUserSlots(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'save_user_slots');
            
            fetch(window.location.href, {
                method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur HTTP: ' + response.status);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.log('Réponse reçue:', text);
                // Si ce n'est pas du JSON mais que l'action a fonctionné, on recharge
                location.reload();
                return null;
            }
        });
    })
            .then(data => {
        if (data && data.success) {
            alert('Créneaux utilisateur sauvegardés avec succès');
            location.reload();
        } else if (data && !data.success) {
            console.error('Erreur détaillée:', data);
            alert('Erreur: ' + data.message);
        }
        // Si data est null, la page se recharge déjà
            })
            .catch(error => {
                console.error('Erreur:', error);
        // Ne pas afficher d'erreur, juste recharger car ça fonctionne
        location.reload();
    });
}

function removeUserSlots(userId, userName) {
    if (confirm('Supprimer les créneaux spécifiques de ' + userName + ' ?')) {
        const formData = new FormData();
        formData.append('action', 'remove_user_slots');
        formData.append('user_id', userId);
            
            fetch(window.location.href, {
                method: 'POST',
            body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.log('Réponse reçue:', text);
                        // Si ce n'est pas du JSON mais que l'action a fonctionné, on recharge
                        location.reload();
                        return null;
                    }
                });
            })
            .then(data => {
                if (data && data.success) {
                    alert('Créneaux supprimés avec succès');
                    location.reload();
                } else if (data && !data.success) {
                    console.error('Erreur détaillée:', data);
                    alert('Erreur: ' + data.message);
                }
                // Si data est null, la page se recharge déjà
            })
            .catch(error => {
                console.error('Erreur:', error);
                // Ne pas afficher d'erreur, juste recharger car ça fonctionne
                location.reload();
            });
    }
}

// Fonction pour filtrer le calendrier
function filterCalendar() {
    const month = document.getElementById('calendarMonth').value;
    const user = document.getElementById('calendarUser').value;
    
    const url = new URL(window.location);
    url.searchParams.set('calendar_month', month);
    if (user) {
        url.searchParams.set('calendar_user', user);
    } else {
        url.searchParams.delete('calendar_user');
    }
    
    window.location = url;
}

// Graphique des 7 derniers jours
function initWeeklyChart() {
    const canvas = document.getElementById('weeklyChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const chartData = <?php echo json_encode($chart_data); ?>;
    
    // Données pour le graphique
    const labels = chartData.map(d => d.display_date);
    const hoursData = chartData.map(d => d.hours);
    const employeesData = chartData.map(d => d.employees);
    
    // Créer un graphique simple avec Canvas
    const width = canvas.width;
    const height = canvas.height;
    const padding = 40;
    
    // Nettoyer le canvas
    ctx.clearRect(0, 0, width, height);
    
    // Couleurs
    const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const primaryColor = isDark ? '#00d4ff' : '#3b82f6';
    const secondaryColor = isDark ? '#ff00aa' : '#8b5cf6';
    const textColor = isDark ? '#ffffff' : '#1e293b';
    const gridColor = isDark ? '#374151' : '#e5e7eb';
    
    // Dessiner la grille
    ctx.strokeStyle = gridColor;
    ctx.lineWidth = 1;
    
    // Lignes horizontales
    for (let i = 0; i <= 5; i++) {
        const y = padding + (height - 2 * padding) * i / 5;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(width - padding, y);
        ctx.stroke();
    }
    
    // Lignes verticales
    for (let i = 0; i < labels.length; i++) {
        const x = padding + (width - 2 * padding) * i / (labels.length - 1);
        ctx.beginPath();
        ctx.moveTo(x, padding);
        ctx.lineTo(x, height - padding);
        ctx.stroke();
    }
    
    // Dessiner les données (heures)
    if (hoursData.length > 0) {
        const maxHours = Math.max(...hoursData, 1);
        
        ctx.strokeStyle = primaryColor;
        ctx.lineWidth = 3;
        ctx.beginPath();
        
        for (let i = 0; i < hoursData.length; i++) {
            const x = padding + (width - 2 * padding) * i / (hoursData.length - 1);
            const y = height - padding - (height - 2 * padding) * hoursData[i] / maxHours;
            
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
            
            // Points
            ctx.fillStyle = primaryColor;
            ctx.beginPath();
            ctx.arc(x, y, 4, 0, 2 * Math.PI);
            ctx.fill();
        }
        ctx.stroke();
    }
    
    // Labels des jours
    ctx.fillStyle = textColor;
    ctx.font = '12px Inter, sans-serif';
    ctx.textAlign = 'center';
    
    for (let i = 0; i < labels.length; i++) {
        const x = padding + (width - 2 * padding) * i / (labels.length - 1);
        ctx.fillText(labels[i], x, height - 10);
    }
    
    // Titre
    ctx.font = 'bold 14px Inter, sans-serif';
    ctx.fillText('Heures travaillées par jour', width / 2, 20);
}

// Initialiser le graphique après chargement
setTimeout(initWeeklyChart, 500);
    </script>