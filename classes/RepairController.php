<?php

class RepairController {
    private $pdo;
    private $userId;
    private $userRole;

    public function __construct($pdo, $userId, $userRole) {
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->userRole = $userRole;
    }

    public function handleRequest() {
        // Handle actions (POST/GET)
        $this->handleActions();

        // Prepare data for view
        $data = $this->prepareViewData();

        // Load view
        $this->renderView($data);
    }

    private function handleActions() {
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $this->deleteRepair((int)$_GET['id']);
        }
    }

    private function deleteRepair($id) {
        if ($this->userRole !== 'admin') {
            set_message("Vous n'avez pas les droits nécessaires pour supprimer une réparation.", "danger");
            redirect("reparations");
            exit;
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM reparations WHERE id = ?");
            $stmt->execute([$id]);
            set_message("Réparation supprimée avec succès.", "success");
        } catch (PDOException $e) {
            set_message("Erreur lors de la suppression de la réparation: " . $e->getMessage(), "danger");
        }
        redirect("reparations");
        exit;
    }

    private function prepareViewData() {
        // Filter parameters
        $statut = isset($_GET['statut']) ? cleanInput($_GET['statut']) : '';
        $statut_ids = isset($_GET['statut_ids']) ? cleanInput($_GET['statut_ids']) : '1,2,3,4,5,19,20';
        $type_appareil = isset($_GET['type_appareil']) ? cleanInput($_GET['type_appareil']) : '';
        $date_debut = isset($_GET['date_debut']) ? cleanInput($_GET['date_debut']) : '';
        $date_fin = isset($_GET['date_fin']) ? cleanInput($_GET['date_fin']) : '';
        $search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
        
        // Pagination parameters
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = 50; // Load 50 repairs per page

        // Statistics
        $stats = $this->getStatistics();

        // Repairs list with pagination
        $reparations = $this->getRepairs($statut, $statut_ids, $type_appareil, $date_debut, $date_fin, $search, $page, $per_page);
        
        // Get total count for infinite scroll
        $total_repairs = $this->getRepairsCount($statut, $statut_ids, $type_appareil, $date_debut, $date_fin, $search);

        return array_merge($stats, [
            'stats' => $stats,
            'reparations' => $reparations,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total_repairs,
                'total_pages' => ceil($total_repairs / $per_page),
                'has_more' => ($page * $per_page) < $total_repairs
            ],
            'filters' => [
                'statut' => $statut,
                'statut_ids' => $statut_ids,
                'type_appareil' => $type_appareil,
                'date_debut' => $date_debut,
                'date_fin' => $date_fin,
                'search' => $search
            ]
        ]);
    }

    private function getStatistics() {
        $stats = [
            'total_reparations' => 0,
            'total_nouvelles' => 0,
            'total_en_cours' => 0,
            'total_en_attente' => 0,
            'total_termines' => 0,
            'total_archives' => 0
        ];

        try {
            // Helper to execute count query
            $countQuery = function($ids) {
                $sql = "SELECT COUNT(*) as total FROM reparations r WHERE r.statut IN (SELECT code FROM statuts WHERE id IN ($ids))";
                $stmt = $this->pdo->query($sql);
                return $stmt->fetch()['total'];
            };

            // Total (1-5, 19, 20)
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM reparations r WHERE r.statut IN (SELECT code FROM statuts WHERE id BETWEEN 1 AND 5 OR id IN (19,20))");
            $stats['total_reparations'] = $stmt->fetch()['total'];

            $stats['total_nouvelles'] = $countQuery('1,2,3,19,20');
            $stats['total_en_cours'] = $countQuery('4,5');
            $stats['total_en_attente'] = $countQuery('6,7,8');
            $stats['total_termines'] = $countQuery('9,10');
            $stats['total_archives'] = $countQuery('11,12,13');

        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des réparations : " . $e->getMessage());
        }

        return $stats;
    }

    private function getRepairs($statut, $statut_ids, $type_appareil, $date_debut, $date_fin, $search, $page = 1, $per_page = 50) {
        // Build WHERE clause
        $whereClause = $this->buildWhereClause($search, $statut, $statut_ids, $type_appareil, $date_debut, $date_fin);
        $params = $whereClause['params'];
        
        $sql = "
            SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email,
                   u.active_repair_id as user_active_repair_id
            FROM reparations r
            LEFT JOIN clients c ON r.client_id = c.id
            LEFT JOIN users u ON u.id = ?
            WHERE 1=1
        ";
        array_unshift($params, $this->userId); // Add userId at start
        
        $sql .= $whereClause['sql'];
        $sql .= " ORDER BY r.date_reception DESC";
        
        // Add pagination (OPTIMIZATION for large datasets)
        $offset = ($page - 1) * $per_page;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur SQL (RepairController): " . $e->getMessage());
            return [];
        }
    }
    
    private function getRepairsCount($statut, $statut_ids, $type_appareil, $date_debut, $date_fin, $search) {
        $whereClause = $this->buildWhereClause($search, $statut, $statut_ids, $type_appareil, $date_debut, $date_fin);
        $params = $whereClause['params'];
        
        $sql = "
            SELECT COUNT(DISTINCT r.id) as total
            FROM reparations r
            LEFT JOIN clients c ON r.client_id = c.id
            LEFT JOIN users u ON u.id = ?
            WHERE 1=1
        ";
        array_unshift($params, $this->userId);
        
        $sql .= $whereClause['sql'];
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Erreur SQL Count (RepairController): " . $e->getMessage());
            return 0;
        }
    }
    
    private function buildWhereClause($search, $statut, $statut_ids, $type_appareil, $date_debut, $date_fin) {
        $sql = '';
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (
                c.nom LIKE ? OR 
                c.prenom LIKE ? OR 
                c.telephone LIKE ? OR 
                r.type_appareil LIKE ? OR 
                r.modele LIKE ? OR 
                r.id LIKE ? OR
                r.description_probleme LIKE ? OR
                r.notes_techniques LIKE ?
            )";
            $search_param = "%$search%";
            $params = array_merge($params, array_fill(0, 8, $search_param));
        } else {
            if (!empty($statut_ids)) {
                if (strpos($statut_ids, ',') !== false) {
                    $ids = explode(',', $statut_ids);
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $sql .= " AND r.statut IN (SELECT code FROM statuts WHERE id IN ($placeholders))";
                    $params = array_merge($params, $ids);
                } else {
                    $sql .= " AND r.statut = (SELECT code FROM statuts WHERE id = ?)";
                    $params[] = $statut_ids;
                }
            } else if (!empty($statut)) {
                if (strpos($statut, ',') !== false) {
                    $statuts = explode(',', $statut);
                    $placeholders = implode(',', array_fill(0, count($statuts), '?'));
                    $sql .= " AND r.statut IN ($placeholders)";
                    $params = array_merge($params, $statuts);
                } else {
                    $sql .= " AND r.statut = ?";
                    $params[] = $statut;
                }
            }
        }

        if (!empty($type_appareil)) {
            $sql .= " AND r.type_appareil = ?";
            $params[] = $type_appareil;
        }

        if (!empty($date_debut)) {
            $sql .= " AND r.date_reception >= ?";
            $params[] = $date_debut;
        }

        if (!empty($date_fin)) {
            $sql .= " AND r.date_reception <= ?";
            $params[] = $date_fin . ' 23:59:59';
        }
        
        return ['sql' => $sql, 'params' => $params];
    }

    private function renderView($data) {
        // Extract data to variables for the view
        extract($data);
        
        // Include the view template
        // Use BASE_PATH if defined (best for index.php entry), otherwise fallback to __DIR__
        if (defined('BASE_PATH')) {
            $viewPath = BASE_PATH . '/templates/reparations_view.php';
        } else {
            $viewPath = __DIR__ . '/../templates/reparations_view.php';
        }
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            // Fallback for some server configurations or if file is missing
            $altPath = $_SERVER['DOCUMENT_ROOT'] . '/templates/reparations_view.php';
            if (file_exists($altPath)) {
                require $altPath;
            } else {
                error_log("CRITICAL ERROR: View file not found at $viewPath or $altPath");
                echo "<div class='alert alert-danger'>Erreur critique: Le fichier de vue est introuvable ($viewPath). Veuillez contacter le support.</div>";
            }
        }
    }
}
