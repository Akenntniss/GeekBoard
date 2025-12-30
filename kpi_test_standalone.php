<?php
// Script de test AUTONOME pour la logique KPI
// On copie la logique de KPIManager ici pour tester les requêtes SQL sans dépendre de l'infra complexe

// Configuration DB directe
$host = '127.0.0.1'; // ou localhost
$db   = 'geekboard_mkmkmk';
$user = 'gb_mkmkmk';
$pass = 'Admin123!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "✅ Connexion DB OK\n";
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Classe Mockée pour le test
class KPIManagerTest {
    private $pdo;
    private $current_user_id = 1; // Admin
    private $is_admin = true;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // --- COPIE DES MÉTHODES DE kpi_api.php ---

    public function getTurnoverAnalysis($date_start, $date_end, $user_id = null) {
        if (!$this->is_admin && $user_id && $user_id != $this->current_user_id) {
            throw new Exception('Accès non autorisé');
        }

        $employeeFilter = "";
        $params = [$date_start, $date_end];

        if ($user_id && $user_id !== 'all') {
            $employeeFilter = "
                AND EXISTS (
                    SELECT 1 FROM reparation_logs l1 
                    WHERE l1.reparation_id = r.id 
                    AND l1.statut_apres = 'en_cours' 
                    AND l1.changed_by = ?
                )
                AND EXISTS (
                    SELECT 1 FROM reparation_logs l2 
                    WHERE l2.reparation_id = r.id 
                    AND l2.statut_apres = 'reparation_effectuee' 
                    AND l2.changed_by = ?
                )
            ";
            $params[] = $user_id;
            $params[] = $user_id;
        }

        $sqlCashIn = "
            SELECT 
                COUNT(DISTINCT r.id) as count,
                COALESCE(SUM(r.prix_reparation), 0) as total
            FROM reparations r
            JOIN reparation_logs l ON r.id = l.reparation_id
            WHERE l.statut_apres = 'restituee'
            AND DATE(l.changed_at) BETWEEN ? AND ?
            $employeeFilter
        ";

        $sqlPotential = "
            SELECT 
                COUNT(DISTINCT r.id) as count,
                COALESCE(SUM(r.prix_reparation), 0) as total
            FROM reparations r
            JOIN reparation_logs l ON r.id = l.reparation_id
            WHERE l.statut_apres IN ('reparation_effectuee', 'restituee')
            AND DATE(l.changed_at) BETWEEN ? AND ?
            $employeeFilter
        ";

        $stmt = $this->pdo->prepare($sqlCashIn);
        $stmt->execute($params);
        $cashIn = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare($sqlPotential);
        $stmt->execute($params);
        $potential = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'cash_in' => ['amount' => (float)$cashIn['total'], 'count' => (int)$cashIn['count']],
            'potential' => ['amount' => (float)$potential['total'], 'count' => (int)$potential['count']]
        ];
    }

    public function getRepairKPIs($date_start, $date_end, $user_id = null) {
        $params = [$date_start, $date_end];
        $userFilterLogs = "";
        
        if ($user_id && $user_id !== 'all') {
             $userFilterLogs = "
                AND EXISTS (
                    SELECT 1 FROM reparation_logs l_start 
                    WHERE l_start.reparation_id = r.id 
                    AND l_start.statut_apres = 'en_cours' 
                    AND l_start.changed_by = ?
                )
                AND EXISTS (
                    SELECT 1 FROM reparation_logs l_end 
                    WHERE l_end.reparation_id = r.id 
                    AND l_end.statut_apres = 'reparation_effectuee' 
                    AND l_end.changed_by = ?
                )
            ";
            $params[] = $user_id;
            $params[] = $user_id;
        }

        $newRepairs = 0;
        if (!$user_id || $user_id === 'all') {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM reparations WHERE DATE(date_creation) BETWEEN ? AND ?");
            $stmt->execute([$date_start, $date_end]);
            $newRepairs = $stmt->fetchColumn();
        }

        $sqlEffectuees = "
            SELECT COUNT(DISTINCT r.id) 
            FROM reparations r
            JOIN reparation_logs l ON r.id = l.reparation_id
            WHERE l.statut_apres = 'reparation_effectuee'
            AND DATE(l.changed_at) BETWEEN ? AND ?
            $userFilterLogs
        ";
        $stmt = $this->pdo->prepare($sqlEffectuees);
        $stmt->execute($params);
        $doneRepairs = $stmt->fetchColumn();

        $sqlRestituees = "
            SELECT COUNT(DISTINCT r.id) 
            FROM reparations r
            JOIN reparation_logs l ON r.id = l.reparation_id
            WHERE l.statut_apres = 'restituee'
            AND DATE(l.changed_at) BETWEEN ? AND ?
            $userFilterLogs
        ";
        $stmt = $this->pdo->prepare($sqlRestituees);
        $stmt->execute($params);
        $returnedRepairs = $stmt->fetchColumn();

        $autonomySql = "
            SELECT COUNT(DISTINCT r.id)
            FROM reparations r
            JOIN reparation_logs l ON r.id = l.reparation_id
            WHERE l.statut_apres = 'reparation_effectuee'
            AND DATE(l.changed_at) BETWEEN ? AND ?
            AND (
                SELECT COUNT(DISTINCT changed_by) 
                FROM reparation_logs sub_l 
                WHERE sub_l.reparation_id = r.id
            ) = 1
        ";
        
        $autonomyParams = [$date_start, $date_end];
        if ($user_id && $user_id !== 'all') {
            $autonomySql .= " AND l.changed_by = ?";
            $autonomyParams[] = $user_id;
        }
        
        $stmt = $this->pdo->prepare($autonomySql);
        $stmt->execute($autonomyParams);
        $autonomyRepairs = $stmt->fetchColumn();

        $timeSql = "
            SELECT 
                r.type_appareil,
                AVG(TIMESTAMPDIFF(MINUTE, l_start.changed_at, l_end.changed_at)) as avg_minutes
            FROM reparations r
            JOIN reparation_logs l_end ON r.id = l_end.reparation_id AND l_end.statut_apres = 'reparation_effectuee'
            JOIN reparation_logs l_start ON r.id = l_start.reparation_id AND l_start.statut_apres = 'en_cours'
            WHERE DATE(l_end.changed_at) BETWEEN ? AND ?
            AND l_start.changed_at < l_end.changed_at
            AND l_start.id = (
                SELECT MAX(id) FROM reparation_logs 
                WHERE reparation_id = r.id 
                AND statut_apres = 'en_cours' 
                AND changed_at < l_end.changed_at
            )
            $userFilterLogs
            GROUP BY r.type_appareil
        ";
        
        $stmt = $this->pdo->prepare($timeSql);
        $stmt->execute($params);
        $timeByDevice = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $avgBasketSql = "
            SELECT AVG(r.prix_reparation)
            FROM reparations r
            JOIN reparation_logs l ON r.id = l.reparation_id
            WHERE l.statut_apres = 'restituee'
            AND DATE(l.changed_at) BETWEEN ? AND ?
            $userFilterLogs
        ";
        $stmt = $this->pdo->prepare($avgBasketSql);
        $stmt->execute($params);
        $avgBasket = $stmt->fetchColumn();

        return [
            'new_repairs' => (int)$newRepairs,
            'done_repairs' => (int)$doneRepairs,
            'returned_repairs' => (int)$returnedRepairs,
            'autonomy_repairs' => (int)$autonomyRepairs,
            'avg_basket' => (float)$avgBasket,
            'time_by_device' => $timeByDevice
        ];
    }

    public function getEmployeeBehavior($date_start, $date_end) {
        $sqlRepairs = "
            SELECT 
                u.id, 
                u.full_name,
                COUNT(DISTINCT r.id) as repairs_count
            FROM users u
            JOIN reparation_logs l_end ON l_end.changed_by = u.id AND l_end.statut_apres = 'reparation_effectuee'
            JOIN reparations r ON r.id = l_end.reparation_id
            WHERE DATE(l_end.changed_at) BETWEEN ? AND ?
            AND EXISTS (
                SELECT 1 FROM reparation_logs l_start 
                WHERE l_start.reparation_id = r.id 
                AND l_start.statut_apres = 'en_cours' 
                AND l_start.changed_by = u.id
            )
            GROUP BY u.id, u.full_name
        ";
        $stmt = $this->pdo->prepare($sqlRepairs);
        $stmt->execute([$date_start, $date_end]);
        $repairsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $behaviorData = [];
        foreach ($repairsData as $row) {
            $behaviorData[$row['id']] = [
                'name' => $row['full_name'],
                'repairs' => $row['repairs_count'],
                'lates' => 0,
                'present_days' => 0,
                'absent_days' => 0
            ];
        }
        
        // Simulation présence (car pas sûr de la structure presence_history)
        // On teste si la table existe
        try {
             $checkTable = $this->pdo->query("SHOW TABLES LIKE 'presence_history'");
             if ($checkTable->rowCount() > 0) {
                 // Table existe
             } else {
                 // Fallback time_tracking
                 $sqlPresence = "
                    SELECT 
                        user_id,
                        COUNT(DISTINCT DATE(clock_in)) as present_days,
                        SUM(CASE WHEN TIME(clock_in) > '09:00:00' THEN 1 ELSE 0 END) as late_days
                    FROM time_tracking
                    WHERE DATE(clock_in) BETWEEN ? AND ?
                    GROUP BY user_id
                ";
                $stmt = $this->pdo->prepare($sqlPresence);
                $stmt->execute([$date_start, $date_end]);
                $presenceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($presenceData as $row) {
                    if (isset($behaviorData[$row['user_id']])) {
                        $behaviorData[$row['user_id']]['lates'] = $row['late_days'];
                        $behaviorData[$row['user_id']]['present_days'] = $row['present_days'];
                    }
                }
             }
        } catch (Exception $e) {}

        return array_values($behaviorData);
    }
}

// --- EXÉCUTION DU TEST ---

$kpi = new KPIManagerTest($pdo);
$date_start = date('Y-m-d', strtotime('-30 days'));
$date_end = date('Y-m-d');

echo "\n--- TEST TURNOVER ---\n";
$turnover = $kpi->getTurnoverAnalysis($date_start, $date_end);
print_r($turnover);

echo "\n--- TEST REPAIRS ---\n";
$repairs = $kpi->getRepairKPIs($date_start, $date_end);
print_r($repairs);

echo "\n--- TEST BEHAVIOR ---\n";
$behavior = $kpi->getEmployeeBehavior($date_start, $date_end);
print_r($behavior);

?>
