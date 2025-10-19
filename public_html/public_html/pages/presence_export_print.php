<?php
// Page d'export HTML pour impression

// Inclure les fichiers nécessaires
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/session_config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Variables d'authentification
$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

if (!$current_user_id) {
    header('Location: index.php');
    exit;
}

// Obtenir la connexion à la base de données
$shop_pdo = getShopDBConnection();

if (!$shop_pdo) {
    die('Erreur de connexion à la base de données');
}

// Récupérer les paramètres d'export
$export_user = $_GET['export_user'] ?? $_POST['export_user'] ?? '';
$export_type = $_GET['export_type'] ?? $_POST['export_type'] ?? '';
$export_status = $_GET['export_status'] ?? $_POST['export_status'] ?? '';
$export_date_start = $_GET['export_date_start'] ?? $_POST['export_date_start'] ?? '';
$export_date_end = $_GET['export_date_end'] ?? $_POST['export_date_end'] ?? '';
$report_title = $_GET['report_title'] ?? $_POST['report_title'] ?? 'Rapport des événements de présence';
$columns = $_GET['columns'] ?? $_POST['columns'] ?? ['user', 'type', 'date_start', 'status'];
// Assurer que columns est un array
if (!is_array($columns)) {
    $columns = ['user', 'type', 'date_start', 'status'];
}

// Construire la requête
$query = "
    SELECT 
        pe.id,
        COALESCE(u.full_name, u.username) as user_name,
        pt.name as type_name,
        pt.color as type_color,
        pe.date_start,
        pe.date_end,
        pe.duration_minutes,
        pe.status,
        pe.comment,
        pe.created_at,
        COALESCE(approver.full_name, approver.username) as approver_name
    FROM presence_events pe
    LEFT JOIN users u ON pe.employee_id = u.id
    LEFT JOIN presence_types pt ON pe.type_id = pt.id
    LEFT JOIN users approver ON pe.approved_by = approver.id
    WHERE 1=1
";

$params = [];

// Filtres
if ($export_user && $export_user !== 'all') {
    $query .= " AND pe.employee_id = ?";
    $params[] = $export_user;
} elseif (!$is_admin && $current_user_id) {
    $query .= " AND pe.employee_id = ?";
    $params[] = $current_user_id;
}

if ($export_type && $export_type !== 'all') {
    $query .= " AND pe.type_id = ?";
    $params[] = $export_type;
}

if ($export_status && $export_status !== 'all') {
    $query .= " AND pe.status = ?";
    $params[] = $export_status;
}

if ($export_date_start) {
    $query .= " AND pe.date_start >= ?";
    $params[] = $export_date_start . ' 00:00:00';
}

if ($export_date_end) {
    $query .= " AND pe.date_start <= ?";
    $params[] = $export_date_end . ' 23:59:59';
}

$query .= " ORDER BY pe.date_start DESC";

$stmt = $shop_pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les informations pour l'en-tête
$user_name = '';
if ($export_user && $export_user !== 'all') {
    $user_stmt = $shop_pdo->prepare("SELECT COALESCE(full_name, username) as name FROM users WHERE id = ?");
    $user_stmt->execute([$export_user]);
    $user_result = $user_stmt->fetch();
    $user_name = $user_result ? $user_result['name'] : '';
}

$type_name = '';
if ($export_type && $export_type !== 'all') {
    $type_stmt = $shop_pdo->prepare("SELECT name FROM presence_types WHERE id = ?");
    $type_stmt->execute([$export_type]);
    $type_result = $type_stmt->fetch();
    $type_name = $type_result ? $type_result['name'] : '';
}

$status_labels = [
    'pending' => 'En attente',
    'approved' => 'Approuvé', 
    'rejected' => 'Rejeté',
    'cancelled' => 'Annulé'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report_title); ?></title>
    <style>
        /* Styles pour l'écran */
        @media screen {
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                margin: 20px;
                background-color: #f8f9fa;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .no-print {
                display: block;
            }
        }

        /* Styles pour l'impression */
        @media print {
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                background: white !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            .container {
                max-width: none;
                margin: 0;
                padding: 20px;
                box-shadow: none;
                border-radius: 0;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
        }

        /* Styles communs */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #dee2e6;
        }

        .header h1 {
            color: #495057;
            margin: 0 0 10px 0;
            font-size: 24px;
        }

        .header .subtitle {
            color: #6c757d;
            font-size: 14px;
        }

        .filters-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }

        .filters-info h3 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 16px;
        }

        .filter-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
        }

        .filter-label {
            font-weight: bold;
            color: #495057;
        }

        /* Tableau simple sans Bootstrap */
        .simple-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-family: Arial, sans-serif;
            font-size: 10px;
        }

        .simple-table th,
        .simple-table td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }

        .simple-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
            text-align: center;
            font-size: 9px;
            text-transform: uppercase;
        }

        .simple-table tr:nth-child(even) {
            background-color: #fafafa;
        }

        @media screen {
            .simple-table tr:hover {
                background-color: #f0f0f0;
            }
        }

        /* Largeurs spécifiques */
        .simple-table th:nth-child(1), .simple-table td:nth-child(1) { width: 140px; } /* Utilisateur */
        .simple-table th:nth-child(2), .simple-table td:nth-child(2) { width: 100px; } /* Type */
        .simple-table th:nth-child(3), .simple-table td:nth-child(3) { width: 100px; } /* Date début */
        .simple-table th:nth-child(4), .simple-table td:nth-child(4) { width: 100px; } /* Date fin */
        .simple-table th:nth-child(5), .simple-table td:nth-child(5) { width: 60px; }  /* Durée */
        .simple-table th:nth-child(6), .simple-table td:nth-child(6) { width: 80px; }  /* Statut */
        .simple-table th:nth-child(7), .simple-table td:nth-child(7) { width: 200px; } /* Commentaire */
        .simple-table th:nth-child(8), .simple-table td:nth-child(8) { width: 100px; } /* Créé le */

        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d1edff;
            color: #0c5460;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-cancelled {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .summary {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }

        .summary-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
        }

        .btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .actions {
            text-align: center;
            margin-bottom: 20px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Actions (masquées à l'impression) -->
        <div class="actions no-print">
            <a href="javascript:window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimer
            </a>
            <a href="index.php?page=presence_gestion" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
            <a href="javascript:window.close()" class="btn btn-secondary">
                <i class="fas fa-times"></i> Fermer
            </a>
        </div>

        <!-- En-tête -->
        <div class="header">
            <h1><?php echo htmlspecialchars($report_title); ?></h1>
            <div class="subtitle">
                Généré le <?php echo date('d/m/Y à H:i'); ?> 
                <?php if (isset($_SESSION['shop_name'])): ?>
                    - Magasin: <?php echo htmlspecialchars($_SESSION['shop_name']); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informations sur les filtres -->
        <?php if ($export_user || $export_type || $export_status || $export_date_start || $export_date_end): ?>
        <div class="filters-info">
            <h3>Filtres appliqués :</h3>
            <?php if ($user_name): ?>
                <div class="filter-item">
                    <span class="filter-label">Utilisateur :</span> <?php echo htmlspecialchars($user_name); ?>
                </div>
            <?php endif; ?>
            <?php if ($type_name): ?>
                <div class="filter-item">
                    <span class="filter-label">Type :</span> <?php echo htmlspecialchars($type_name); ?>
                </div>
            <?php endif; ?>
            <?php if ($export_status && $export_status !== 'all'): ?>
                <div class="filter-item">
                    <span class="filter-label">Statut :</span> <?php echo htmlspecialchars($status_labels[$export_status] ?? $export_status); ?>
                </div>
            <?php endif; ?>
            <?php if ($export_date_start): ?>
                <div class="filter-item">
                    <span class="filter-label">Date début :</span> <?php echo date('d/m/Y', strtotime($export_date_start)); ?>
                </div>
            <?php endif; ?>
            <?php if ($export_date_end): ?>
                <div class="filter-item">
                    <span class="filter-label">Date fin :</span> <?php echo date('d/m/Y', strtotime($export_date_end)); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Résumé -->
        <?php
        $total = count($events);
        $pending = count(array_filter($events, fn($e) => $e['status'] === 'pending'));
        $approved = count(array_filter($events, fn($e) => $e['status'] === 'approved'));
        $rejected = count(array_filter($events, fn($e) => $e['status'] === 'rejected'));
        ?>
        <div class="summary">
            <div class="summary-item">
                <div class="summary-number"><?php echo $total; ?></div>
                <div class="summary-label">Total</div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?php echo $pending; ?></div>
                <div class="summary-label">En attente</div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?php echo $approved; ?></div>
                <div class="summary-label">Approuvés</div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?php echo $rejected; ?></div>
                <div class="summary-label">Rejetés</div>
            </div>
        </div>

        <!-- Tableau simple des événements -->
        <table class="simple-table">
            <tr>
                <th>Utilisateur</th>
                <th>Type</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Durée</th>
                <th>Statut</th>
                <th>Commentaire</th>
                <th>Créé le</th>
            </tr>
            <?php if (empty($events)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; color: #999;">
                        Aucun événement trouvé avec les critères sélectionnés.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['user_name']); ?></td>
                        <td style="color: <?php echo htmlspecialchars($event['type_color']); ?>; font-weight: bold;">
                            <?php echo htmlspecialchars($event['type_name']); ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($event['date_start'])); ?></td>
                        <td><?php echo $event['date_end'] ? date('d/m/Y H:i', strtotime($event['date_end'])) : '-'; ?></td>
                        <td>
                            <?php 
                            if ($event['duration_minutes']) {
                                $hours = intval($event['duration_minutes'] / 60);
                                $minutes = $event['duration_minutes'] % 60;
                                echo $hours > 0 ? $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'min' : '') : $minutes . 'min';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $event['status']; ?>">
                                <?php echo htmlspecialchars($status_labels[$event['status']] ?? $event['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($event['comment']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($event['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <!-- Pied de page -->
        <div class="footer">
            <p>
                Document généré automatiquement par GeekBoard TechBoard Assistant<br>
                <?php echo count($events); ?> événement(s) affiché(s) sur cette page
            </p>
        </div>
    </div>

    <script>
        // Auto-focus sur l'impression si demandé
        if (window.location.search.includes('print=1')) {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>
