<?php
/**
 * API KPI pour GeekBoard
 * Système complet de Key Performance Indicators
 * Compatible avec l'architecture multi-magasin
 */

require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Headers pour API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// La session est démarrée dans session_config.php et la détection shop dans subdomain_config.php
initializeShopSession();

class KPIManager {
    private $pdo;
    private $current_user_id;
    private $is_admin;
    private $column_cache = [];
    
    public function __construct() {
        // Connexion automatique basée sur le sous-domaine
        $this->pdo = getShopDBConnection();
        
        if (!$this->pdo) {
            throw new Exception('Impossible de se connecter à la base de données');
        }
        
        // Vérifier l'authentification
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Utilisateur non authentifié');
        }
        
        $this->current_user_id = $_SESSION['user_id'];
        $this->is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    private function columnExists(string $table, string $column): bool {
        $cacheKey = $table . '.' . $column;
        if (isset($this->column_cache[$cacheKey])) {
            return $this->column_cache[$cacheKey];
        }
        $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        $exists = (bool)$stmt->fetch();
        $this->column_cache[$cacheKey] = $exists;
        return $exists;
    }
    
    /**
     * KPI PRINCIPAL: Réparations par heure d'employé
     */
    public function getRepairsByHour($user_id = null, $date_start = null, $date_end = null) {
        // Interpréter 'all' comme aucun filtre d'utilisateur (agrégé) pour les administrateurs
        if ($user_id === 'all') { $user_id = null; }
        // Si pas admin et pas son propre ID, interdire
        if (!$this->is_admin && $user_id && $user_id != $this->current_user_id) {
            throw new Exception('Accès non autorisé');
        }
        
        // Si pas d'utilisateur spécifique, utiliser l'utilisateur actuel
        if (!$user_id && !$this->is_admin) {
            $user_id = $this->current_user_id;
        }
        
        // Dates par défaut (30 derniers jours)
        if (!$date_start) $date_start = date('Y-m-d', strtotime('-30 days'));
        if (!$date_end) $date_end = date('Y-m-d');
        
        // Requête pour obtenir les réparations terminées et les heures travaillées
        $sql = "
            SELECT 
                DATE(tt.clock_in) as work_date,
                u.full_name,
                u.id as user_id,
                -- Heures travaillées par jour
                COALESCE(SUM(tt.work_duration), 0) as total_hours_worked,
                -- Nombre de réparations terminées par jour
                COUNT(DISTINCT CASE 
                    WHEN r.statut IN ('terminee', 'livree', 'reparee') 
                         AND DATE(r.date_modification) = DATE(tt.clock_in)
                    THEN r.id 
                END) as repairs_completed,
                -- Calcul du ratio réparations/heure
                CASE 
                    WHEN SUM(tt.work_duration) > 0 
                    THEN ROUND(COUNT(DISTINCT CASE 
                        WHEN r.statut IN ('terminee', 'livree', 'reparee') 
                             AND DATE(r.date_modification) = DATE(tt.clock_in)
                        THEN r.id 
                    END) / SUM(tt.work_duration), 2)
                    ELSE 0 
                END as repairs_per_hour
            FROM time_tracking tt
            INNER JOIN users u ON tt.user_id = u.id
            LEFT JOIN reparations r ON r.employe_id = u.id 
                AND DATE(r.date_modification) = DATE(tt.clock_in)
                AND r.statut IN ('terminee', 'livree', 'reparee')
            WHERE tt.status = 'completed'
                AND DATE(tt.clock_in) BETWEEN ? AND ?
                " . ($user_id ? "AND u.id = ?" : "") . "
            GROUP BY DATE(tt.clock_in), u.id, u.full_name
            ORDER BY work_date DESC, u.full_name
        ";
        
        $params = [$date_start, $date_end];
        if ($user_id) $params[] = $user_id;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcul des moyennes sur la période
        $summary_sql = "
            SELECT 
                u.full_name,
                u.id as user_id,
                COALESCE(AVG(daily_stats.total_hours_worked), 0) as avg_hours_per_day,
                COALESCE(AVG(daily_stats.repairs_completed), 0) as avg_repairs_per_day,
                COALESCE(AVG(daily_stats.repairs_per_hour), 0) as avg_repairs_per_hour,
                COALESCE(SUM(daily_stats.total_hours_worked), 0) as total_hours_period,
                COALESCE(SUM(daily_stats.repairs_completed), 0) as total_repairs_period
            FROM users u
            LEFT JOIN (
                SELECT 
                    tt.user_id,
                    DATE(tt.clock_in) as work_date,
                    SUM(tt.work_duration) as total_hours_worked,
                    COUNT(DISTINCT CASE 
                        WHEN r.statut IN ('terminee', 'livree', 'reparee') 
                             AND DATE(r.date_modification) = DATE(tt.clock_in)
                        THEN r.id 
                    END) as repairs_completed,
                    CASE 
                        WHEN SUM(tt.work_duration) > 0 
                        THEN COUNT(DISTINCT CASE 
                            WHEN r.statut IN ('terminee', 'livree', 'reparee') 
                                 AND DATE(r.date_modification) = DATE(tt.clock_in)
                            THEN r.id 
                        END) / SUM(tt.work_duration)
                        ELSE 0 
                    END as repairs_per_hour
                FROM time_tracking tt
                LEFT JOIN reparations r ON r.employe_id = tt.user_id 
                    AND DATE(r.date_modification) = DATE(tt.clock_in)
                    AND r.statut IN ('terminee', 'livree', 'reparee')
                WHERE tt.status = 'completed'
                    AND DATE(tt.clock_in) BETWEEN ? AND ?
                GROUP BY tt.user_id, DATE(tt.clock_in)
            ) daily_stats ON u.id = daily_stats.user_id
            WHERE u.role IN ('admin', 'technicien')
                " . ($user_id ? "AND u.id = ?" : "") . "
            GROUP BY u.id, u.full_name
            HAVING total_hours_period > 0
            ORDER BY avg_repairs_per_hour DESC
        ";
        
        $params = [$date_start, $date_end];
        if ($user_id) $params[] = $user_id;
        
        $stmt = $this->pdo->prepare($summary_sql);
        $stmt->execute($params);
        $summary_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'period' => [
                'start' => $date_start,
                'end' => $date_end
            ],
            'daily_details' => $daily_data,
            'period_summary' => $summary_data
        ];
    }
    
    /**
     * KPI: Statistiques générales de productivité
     */
    public function getProductivityStats($user_id = null, $date_start = null, $date_end = null) {
        if ($user_id === 'all') { $user_id = null; }
        if (!$this->is_admin && $user_id && $user_id != $this->current_user_id) {
            throw new Exception('Accès non autorisé');
        }
        
        if (!$user_id && !$this->is_admin) $user_id = $this->current_user_id;
        if (!$date_start) $date_start = date('Y-m-d', strtotime('-30 days'));
        if (!$date_end) $date_end = date('Y-m-d');
        
        // Colonnes optionnelles
        $hasUrgent = $this->columnExists('reparations', 'urgent');
        $hasDevisEnvoye = $this->columnExists('reparations', 'devis_envoye');
        $hasDevisAccepte = $this->columnExists('reparations', 'devis_accepte');

        $urgentSelect = $hasUrgent 
            ? "COUNT(CASE WHEN r.urgent = 1 AND r.statut IN ('terminee', 'livree', 'reparee') THEN 1 END) as urgent_repairs_completed," 
            : "0 as urgent_repairs_completed,";
        $devisEnvoyeSelect = $hasDevisEnvoye 
            ? "COUNT(CASE WHEN r.devis_envoye = 'OUI' THEN 1 END) as quotes_sent," 
            : "0 as quotes_sent,";
        $devisAccepteSelect = $hasDevisAccepte 
            ? "COUNT(CASE WHEN r.devis_accepte = 'oui' THEN 1 END) as quotes_accepted," 
            : "0 as quotes_accepted,";

        $sql = "
            SELECT 
                u.full_name,
                u.id as user_id,
                COUNT(CASE WHEN r.statut IN ('terminee', 'livree', 'reparee') THEN 1 END) as repairs_completed,
                COUNT(CASE WHEN r.statut = 'en_cours' THEN 1 END) as repairs_in_progress,
                $urgentSelect
                $devisEnvoyeSelect
                $devisAccepteSelect
                COALESCE(SUM(CASE WHEN r.statut IN ('terminee', 'livree', 'reparee') THEN r.prix_reparation END), 0) as total_revenue,
                COALESCE(AVG(CASE WHEN r.statut IN ('terminee', 'livree', 'reparee') THEN r.prix_reparation END), 0) as avg_repair_price,
                AVG(CASE 
                    WHEN r.statut IN ('terminee', 'livree', 'reparee') AND r.date_modification IS NOT NULL
                    THEN TIMESTAMPDIFF(HOUR, r.date_reception, r.date_modification)
                END) as avg_resolution_time_hours,
                COUNT(CASE 
                    WHEN r.date_fin_prevue IS NOT NULL 
                         AND r.date_modification IS NOT NULL 
                         AND DATE(r.date_modification) <= r.date_fin_prevue 
                    THEN 1 
                END) as repairs_on_time,
                COUNT(CASE 
                    WHEN r.date_fin_prevue IS NOT NULL 
                         AND r.date_modification IS NOT NULL 
                    THEN 1 
                END) as repairs_with_deadline
            FROM users u
            LEFT JOIN reparations r ON r.employe_id = u.id 
                AND DATE(r.date_reception) BETWEEN ? AND ?
            WHERE u.role IN ('admin', 'technicien')
                " . ($user_id ? "AND u.id = ?" : "") . "
            GROUP BY u.id, u.full_name
            ORDER BY repairs_completed DESC
        ";
        
        $params = [$date_start, $date_end];
        if ($user_id) $params[] = $user_id;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * KPI: Analyse par type d'appareil
     */
    public function getDeviceTypeAnalysis($user_id = null, $date_start = null, $date_end = null) {
        if ($user_id === 'all') { $user_id = null; }
        if (!$this->is_admin && $user_id && $user_id != $this->current_user_id) {
            throw new Exception('Accès non autorisé');
        }
        
        if (!$user_id && !$this->is_admin) $user_id = $this->current_user_id;
        if (!$date_start) $date_start = date('Y-m-d', strtotime('-30 days'));
        if (!$date_end) $date_end = date('Y-m-d');
        
        $hasUrgent2 = $this->columnExists('reparations', 'urgent');
        $urgentCount = $hasUrgent2 ? "COUNT(CASE WHEN r.urgent = 1 THEN 1 END) as urgent_count" : "0 as urgent_count";
        $sql = "
            SELECT 
                COALESCE(r.type_appareil, r.type) as type_appareil,
                COALESCE(r.marque, r.brand) as marque,
                COUNT(*) as total_repairs,
                COUNT(CASE WHEN r.statut IN ('terminee', 'livree', 'reparee') THEN 1 END) as completed_repairs,
                AVG(CASE 
                    WHEN r.statut IN ('terminee', 'livree', 'reparee') AND r.date_modification IS NOT NULL
                    THEN TIMESTAMPDIFF(HOUR, r.date_reception, r.date_modification)
                END) as avg_resolution_time_hours,
                AVG(r.prix_reparation) as avg_price,
                $urgentCount
            FROM reparations r
            WHERE DATE(r.date_reception) BETWEEN ? AND ?
                " . ($user_id ? "AND r.employe_id = ?" : "") . "
            GROUP BY COALESCE(r.type_appareil, r.type), COALESCE(r.marque, r.brand)
            ORDER BY total_repairs DESC
        ";
        
        $params = [$date_start, $date_end];
        if ($user_id) $params[] = $user_id;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * KPI: Présence et temps de travail
     */
    public function getAttendanceStats($user_id = null, $date_start = null, $date_end = null) {
        if ($user_id === 'all') { $user_id = null; }
        if (!$this->is_admin && $user_id && $user_id != $this->current_user_id) {
            throw new Exception('Accès non autorisé');
        }
        
        if (!$user_id && !$this->is_admin) $user_id = $this->current_user_id;
        if (!$date_start) $date_start = date('Y-m-d', strtotime('-30 days'));
        if (!$date_end) $date_end = date('Y-m-d');
        
        $sql = "
            SELECT 
                u.full_name,
                u.id as user_id,
                COUNT(DISTINCT DATE(tt.clock_in)) as days_worked,
                COALESCE(SUM(tt.work_duration), 0) as total_hours_worked,
                COALESCE(AVG(tt.work_duration), 0) as avg_hours_per_day,
                COALESCE(SUM(tt.break_duration), 0) as total_break_time,
                COUNT(CASE WHEN tt.admin_approved = 1 THEN 1 END) as approved_sessions,
                COUNT(CASE WHEN tt.admin_approved = 0 THEN 1 END) as pending_approval,
                -- Ponctualité (basé sur les créneaux si disponibles)
                COUNT(CASE WHEN TIME(tt.clock_in) <= '08:30:00' THEN 1 END) as on_time_arrivals,
                COUNT(*) as total_sessions
            FROM users u
            LEFT JOIN time_tracking tt ON tt.user_id = u.id 
                AND DATE(tt.clock_in) BETWEEN ? AND ?
                AND tt.status = 'completed'
            WHERE u.role IN ('admin', 'technicien')
                " . ($user_id ? "AND u.id = ?" : "") . "
            GROUP BY u.id, u.full_name
            HAVING total_sessions > 0
            ORDER BY total_hours_worked DESC
        ";
        
        $params = [$date_start, $date_end];
        if ($user_id) $params[] = $user_id;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * KPI Dashboard - Vue d'ensemble
     */
    public function getDashboardOverview($date_start = null, $date_end = null) {
        if (!$this->is_admin) {
            throw new Exception('Accès administrateur requis');
        }
        
        if (!$date_start) $date_start = date('Y-m-d', strtotime('-30 days'));
        if (!$date_end) $date_end = date('Y-m-d');
        
        // Statistiques générales
        $overview_sql = "
            SELECT 
                COUNT(DISTINCT r.id) as total_repairs,
                COUNT(DISTINCT CASE WHEN r.statut IN ('terminee', 'livree', 'reparee') THEN r.id END) as completed_repairs,
                COUNT(DISTINCT c.id) as total_clients,
                COUNT(DISTINCT CASE WHEN DATE(c.date_creation) BETWEEN ? AND ? THEN c.id END) as new_clients,
                COALESCE(SUM(CASE WHEN r.statut IN ('terminee', 'livree', 'reparee') THEN r.prix_reparation END), 0) as total_revenue,
                COUNT(DISTINCT u.id) as active_technicians,
                COALESCE(SUM(tt.work_duration), 0) as total_hours_worked
            FROM reparations r
            LEFT JOIN clients c ON r.client_id = c.id
            LEFT JOIN users u ON r.employe_id = u.id AND u.role = 'technicien'
            LEFT JOIN time_tracking tt ON tt.user_id = u.id 
                AND DATE(tt.clock_in) BETWEEN ? AND ?
                AND tt.status = 'completed'
            WHERE DATE(r.date_reception) BETWEEN ? AND ?
        ";
        
        $stmt = $this->pdo->prepare($overview_sql);
        $stmt->execute([$date_start, $date_end, $date_start, $date_end, $date_start, $date_end]);
        $overview = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Top performers
        $top_performers_sql = "
            SELECT 
                u.full_name,
                COUNT(CASE WHEN r.statut IN ('terminee', 'livree', 'reparee') THEN 1 END) as completed_repairs,
                COALESCE(SUM(CASE WHEN r.statut IN ('terminee', 'livree', 'reparee') THEN r.prix_reparation END), 0) as revenue,
                COALESCE(SUM(tt.work_duration), 0) as hours_worked,
                CASE 
                    WHEN SUM(tt.work_duration) > 0 
                    THEN ROUND(COUNT(CASE WHEN r.statut IN ('terminee', 'livree', 'reparee') THEN 1 END) / SUM(tt.work_duration), 2)
                    ELSE 0 
                END as repairs_per_hour
            FROM users u
            LEFT JOIN reparations r ON r.employe_id = u.id 
                AND DATE(r.date_reception) BETWEEN ? AND ?
            LEFT JOIN time_tracking tt ON tt.user_id = u.id 
                AND DATE(tt.clock_in) BETWEEN ? AND ?
                AND tt.status = 'completed'
            WHERE u.role = 'technicien'
            GROUP BY u.id, u.full_name
            HAVING hours_worked > 0
            ORDER BY repairs_per_hour DESC
            LIMIT 5
        ";
        
        $stmt = $this->pdo->prepare($top_performers_sql);
        $stmt->execute([$date_start, $date_end, $date_start, $date_end]);
        $top_performers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'period' => [
                'start' => $date_start,
                'end' => $date_end
            ],
            'overview' => $overview,
            'top_performers' => $top_performers
        ];
    }
}

// Traitement des requêtes
try {
    $kpi = new KPIManager();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'repairs_by_hour':
            $user_id = $_GET['user_id'] ?? null;
            $date_start = $_GET['date_start'] ?? null;
            $date_end = $_GET['date_end'] ?? null;
            $result = $kpi->getRepairsByHour($user_id, $date_start, $date_end);
            break;
            
        case 'productivity_stats':
            $user_id = $_GET['user_id'] ?? null;
            $date_start = $_GET['date_start'] ?? null;
            $date_end = $_GET['date_end'] ?? null;
            $result = $kpi->getProductivityStats($user_id, $date_start, $date_end);
            break;
            
        case 'device_analysis':
            $user_id = $_GET['user_id'] ?? null;
            $date_start = $_GET['date_start'] ?? null;
            $date_end = $_GET['date_end'] ?? null;
            $result = $kpi->getDeviceTypeAnalysis($user_id, $date_start, $date_end);
            break;
            
        case 'attendance_stats':
            $user_id = $_GET['user_id'] ?? null;
            $date_start = $_GET['date_start'] ?? null;
            $date_end = $_GET['date_end'] ?? null;
            $result = $kpi->getAttendanceStats($user_id, $date_start, $date_end);
            break;
            
        case 'dashboard_overview':
            $date_start = $_GET['date_start'] ?? null;
            $date_end = $_GET['date_end'] ?? null;
            $result = $kpi->getDashboardOverview($date_start, $date_end);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
