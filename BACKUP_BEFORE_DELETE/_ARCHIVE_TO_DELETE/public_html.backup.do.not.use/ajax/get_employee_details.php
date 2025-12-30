<?php
require_once '../config/database.php';

// Vérifier si un ID d'employé est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<p style="color: #e74c3c; text-align: center;">ID d\'employé invalide.</p>';
    exit;
}

$employee_id = (int)$_GET['id'];

// Connexion à la base de données du magasin
$pdo = getShopDBConnection();

if (!$pdo) {
    echo '<p style="color: #e74c3c; text-align: center;">Erreur de connexion à la base de données.</p>';
    exit;
}

try {
    // Vérifier d'abord quelles tables existent
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $has_reparations = in_array('reparations', $tables);
    $has_reparation_attributions = in_array('reparation_attributions', $tables);
    $has_time_tracking = in_array('time_tracking', $tables);
    $has_clients = in_array('clients', $tables);
    
    // Récupération des informations de base de l'employé
    $stmt = $pdo->prepare("
        SELECT u.*
        FROM users u 
        WHERE u.id = ?
    ");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        echo '<p style="color: #e74c3c; text-align: center;">Employé non trouvé.</p>';
        exit;
    }
    
    // Initialiser les statistiques
    $stats = [
        'total_reparations' => 0,
        'reparations_7j' => 0,
        'reparations_30j' => 0,
        'heures_total' => 0,
        'heures_30j' => 0,
        'total_pointages' => 0,
        'actuellement_connecte' => 0,
        'derniere_connexion' => null,
        'premiere_connexion' => null,
        'retards_30j' => 0
    ];
    
    // Récupération des statistiques de réparations si la table existe
    if ($has_reparation_attributions) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT ra.reparation_id) as total_reparations,
                    COUNT(DISTINCT CASE WHEN ra.date_debut >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN ra.reparation_id END) as reparations_7j,
                    COUNT(DISTINCT CASE WHEN ra.date_debut >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN ra.reparation_id END) as reparations_30j
                FROM reparation_attributions ra
                WHERE ra.employe_id = ?
            ");
            $stmt->execute([$employee_id]);
            $repair_stats = $stmt->fetch();
            
            if ($repair_stats) {
                $stats['total_reparations'] = $repair_stats['total_reparations'];
                $stats['reparations_7j'] = $repair_stats['reparations_7j'];
                $stats['reparations_30j'] = $repair_stats['reparations_30j'];
            }
        } catch (PDOException $e) {
            // Ignorer les erreurs de réparations
        }
    }
    
    // Récupération des statistiques de temps si la table existe
    if ($has_time_tracking) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN work_duration ELSE 0 END), 0) as heures_total,
                    COALESCE(SUM(CASE WHEN status = 'completed' AND clock_in >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN work_duration ELSE 0 END), 0) as heures_30j,
                    COUNT(DISTINCT id) as total_pointages,
                    COUNT(DISTINCT CASE WHEN DATE(clock_in) = CURDATE() AND clock_out IS NULL THEN id END) as actuellement_connecte,
                    MAX(clock_in) as derniere_connexion,
                    MIN(clock_in) as premiere_connexion,
                    COUNT(DISTINCT CASE WHEN TIME(clock_in) > '09:00:00' AND clock_in >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN DATE(clock_in) END) as retards_30j
                FROM time_tracking 
                WHERE user_id = ?
            ");
            $stmt->execute([$employee_id]);
            $time_stats = $stmt->fetch();
            
            if ($time_stats) {
                $stats = array_merge($stats, $time_stats);
            }
        } catch (PDOException $e) {
            // Ignorer les erreurs de time tracking
        }
    }
    
    // Récupération des réparations récentes
    $recent_repairs = [];
    if ($has_reparation_attributions && $has_reparations) {
        try {
            // Vérifier d'abord la structure de la table reparations
            $stmt = $pdo->query("DESCRIBE reparations");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $select_fields = ['r.id', 'ra.date_debut', 'ra.date_fin', 'r.statut'];
            
            // Ajouter les colonnes seulement si elles existent
            if (in_array('type_appareil', $columns)) {
                $select_fields[] = "COALESCE(r.type_appareil, 'N/A') as type_appareil";
            } else {
                $select_fields[] = "'N/A' as type_appareil";
            }
            
            if (in_array('marque', $columns)) {
                $select_fields[] = "COALESCE(r.marque, 'N/A') as marque";
            } else {
                $select_fields[] = "'N/A' as marque";
            }
            
            if (in_array('modele', $columns)) {
                $select_fields[] = "COALESCE(r.modele, 'N/A') as modele";
            } else {
                $select_fields[] = "'N/A' as modele";
            }
            
            $join_client = "";
            $client_field = "'Client' as client_nom";
            
            if ($has_clients) {
                $join_client = "LEFT JOIN clients c ON r.client_id = c.id";
                $client_field = "CONCAT(COALESCE(c.nom, ''), ' ', COALESCE(c.prenom, '')) as client_nom";
            }
            
            $select_fields[] = $client_field;
            
            $sql = "
                SELECT " . implode(', ', $select_fields) . "
                FROM reparation_attributions ra
                JOIN reparations r ON ra.reparation_id = r.id
                $join_client
                WHERE ra.employe_id = ?
                ORDER BY ra.date_debut DESC
                LIMIT 10
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$employee_id]);
            $recent_repairs = $stmt->fetchAll();
        } catch (PDOException $e) {
            // Ignorer les erreurs et continuer sans les réparations
            $recent_repairs = [];
        }
    }
    
    // Récupération des pointages récents
    $recent_timetracking = [];
    if ($has_time_tracking) {
        try {
            $stmt = $pdo->prepare("
                SELECT DATE(clock_in) as date_pointage,
                       TIME(clock_in) as heure_arrivee,
                       TIME(clock_out) as heure_depart,
                       work_duration,
                       status
                FROM time_tracking 
                WHERE user_id = ?
                ORDER BY clock_in DESC
                LIMIT 15
            ");
            $stmt->execute([$employee_id]);
            $recent_timetracking = $stmt->fetchAll();
        } catch (PDOException $e) {
            // Ignorer les erreurs
            $recent_timetracking = [];
        }
    }
    
} catch (PDOException $e) {
    echo '<p style="color: #e74c3c; text-align: center;">Erreur lors de la récupération des données : ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}
?>

<style>
.employee-details {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.detail-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    color: white;
}

.detail-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    text-transform: uppercase;
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.detail-info h3 {
    font-size: 1.8em;
    margin-bottom: 5px;
}

.detail-username {
    font-size: 1.1em;
    opacity: 0.9;
    margin-bottom: 10px;
}

.detail-role {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    border-left: 4px solid #667eea;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-value {
    font-size: 2em;
    font-weight: bold;
    color: #2c3e50;
    display: block;
    margin-bottom: 5px;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    margin-bottom: 20px;
}

.status-online {
    background: #d4edda;
    color: #155724;
}

.status-offline {
    background: #f8d7da;
    color: #721c24;
}

.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: currentColor;
}

.detail-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.section-title {
    font-size: 1.3em;
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.data-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
    text-transform: uppercase;
    font-size: 0.8em;
    letter-spacing: 0.5px;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.repair-id {
    color: #667eea;
    font-weight: 600;
}

.device-info {
    font-weight: 600;
    color: #2c3e50;
}

.client-name {
    color: #7f8c8d;
    font-style: italic;
}

.time-duration {
    color: #27ae60;
    font-weight: 600;
}

.status-completed {
    color: #27ae60;
    font-weight: 600;
}

.status-active {
    color: #f39c12;
    font-weight: 600;
}

.empty-message {
    text-align: center;
    color: #7f8c8d;
    font-style: italic;
    padding: 30px;
}

.info-notice {
    background: #e3f2fd;
    color: #1976d2;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .detail-header {
        flex-direction: column;
        text-align: center;
    }
    
    .stats-overview {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .data-table {
        font-size: 0.9em;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px 6px;
    }
}
</style>

<div class="employee-details">
    <div class="detail-header">
        <div class="detail-avatar">
            <?php echo strtoupper(substr($employee['full_name'], 0, 1)); ?>
        </div>
        <div class="detail-info">
            <h3><?php echo htmlspecialchars($employee['full_name']); ?></h3>
            <div class="detail-username">@<?php echo htmlspecialchars($employee['username']); ?></div>
            <span class="detail-role">
                <?php echo ucfirst($employee['role']); ?>
            </span>
        </div>
    </div>

    <div class="status-badge <?php echo $stats['actuellement_connecte'] > 0 ? 'status-online' : 'status-offline'; ?>">
        <span class="status-dot"></span>
        <?php echo $stats['actuellement_connecte'] > 0 ? 'Actuellement en ligne' : 'Hors ligne'; ?>
    </div>

    <div class="stats-overview">
        <div class="stat-card">
            <span class="stat-value"><?php echo $stats['total_reparations']; ?></span>
            <span class="stat-label">Réparations Total</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo $stats['reparations_30j']; ?></span>
            <span class="stat-label">Ce mois</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo $stats['reparations_7j']; ?></span>
            <span class="stat-label">Cette semaine</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo round($stats['heures_total'], 1); ?>h</span>
            <span class="stat-label">Heures Total</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo round($stats['heures_30j'], 1); ?>h</span>
            <span class="stat-label">Heures ce mois</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo $stats['retards_30j']; ?></span>
            <span class="stat-label">Retards (30j)</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?php echo $stats['total_pointages']; ?></span>
            <span class="stat-label">Total pointages</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">
                <?php 
                if ($stats['premiere_connexion']) {
                    $date = new DateTime($stats['premiere_connexion']);
                    echo $date->format('d/m/Y');
                } else {
                    echo 'N/A';
                }
                ?>
            </span>
            <span class="stat-label">Première connexion</span>
        </div>
    </div>

    <?php if (!$has_reparations || !$has_reparation_attributions): ?>
        <div class="info-notice">
            ℹ️ <strong>Information :</strong> 
            <?php if (!$has_reparations): ?>
                La table des réparations n'existe pas encore dans cette base de données.
            <?php endif; ?>
            <?php if (!$has_reparation_attributions): ?>
                La table d'attribution des réparations n'existe pas encore.
            <?php endif; ?>
            Les statistiques de réparations ne sont pas disponibles.
        </div>
    <?php endif; ?>

    <div class="detail-section">
        <h4 class="section-title">
            🔧 Réparations récentes
        </h4>
        
        <?php if (empty($recent_repairs)): ?>
            <div class="empty-message">
                <?php if (!$has_reparations || !$has_reparation_attributions): ?>
                    Les données de réparations ne sont pas disponibles (tables manquantes).
                <?php else: ?>
                    Aucune réparation assignée à cet employé.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Appareil</th>
                        <th>Client</th>
                        <th>Date début</th>
                        <th>Durée</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_repairs as $repair): ?>
                        <tr>
                            <td class="repair-id">#<?php echo $repair['id']; ?></td>
                            <td class="device-info">
                                <?php echo htmlspecialchars(($repair['type_appareil'] ?? 'N/A') . ' ' . ($repair['marque'] ?? '') . ' ' . ($repair['modele'] ?? '')); ?>
                            </td>
                            <td class="client-name"><?php echo htmlspecialchars($repair['client_nom'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                if ($repair['date_debut']) {
                                    $date = new DateTime($repair['date_debut']);
                                    echo $date->format('d/m/Y H:i');
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td class="time-duration">
                                <?php 
                                if ($repair['date_fin']) {
                                    $debut = new DateTime($repair['date_debut']);
                                    $fin = new DateTime($repair['date_fin']);
                                    $diff = $debut->diff($fin);
                                    
                                    if ($diff->days > 0) {
                                        echo $diff->days . 'j ' . $diff->h . 'h';
                                    } else {
                                        echo $diff->h . 'h ' . $diff->i . 'm';
                                    }
                                } else {
                                    echo 'En cours';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="<?php echo $repair['date_fin'] ? 'status-completed' : 'status-active'; ?>">
                                    <?php echo htmlspecialchars($repair['statut'] ?? 'N/A'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="detail-section">
        <h4 class="section-title">
            ⏰ Pointages récents
        </h4>
        
        <?php if (!$has_time_tracking): ?>
            <div class="info-notice">
                ℹ️ <strong>Information :</strong> La table de pointage n'existe pas encore dans cette base de données. 
                Les données de pointage ne sont pas disponibles.
            </div>
        <?php elseif (empty($recent_timetracking)): ?>
            <div class="empty-message">
                Aucun pointage enregistré pour cet employé.
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Arrivée</th>
                        <th>Départ</th>
                        <th>Durée</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_timetracking as $tracking): ?>
                        <tr>
                            <td>
                                <?php 
                                $date = new DateTime($tracking['date_pointage']);
                                echo $date->format('d/m/Y');
                                ?>
                            </td>
                            <td><?php echo $tracking['heure_arrivee'] ? substr($tracking['heure_arrivee'], 0, 5) : 'N/A'; ?></td>
                            <td><?php echo $tracking['heure_depart'] ? substr($tracking['heure_depart'], 0, 5) : 'En cours'; ?></td>
                            <td class="time-duration">
                                <?php echo $tracking['work_duration'] ? round($tracking['work_duration'], 1) . 'h' : 'N/A'; ?>
                            </td>
                            <td>
                                <span class="<?php echo $tracking['status'] === 'completed' ? 'status-completed' : 'status-active'; ?>">
                                    <?php 
                                    $status_labels = [
                                        'active' => 'Actif',
                                        'completed' => 'Terminé',
                                        'break' => 'Pause'
                                    ];
                                    echo $status_labels[$tracking['status']] ?? $tracking['status'];
                                    ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($stats['derniere_connexion']): ?>
        <div class="detail-section">
            <h4 class="section-title">
                📅 Informations temporelles
            </h4>
            <p><strong>Dernière connexion :</strong> 
                <?php 
                $date = new DateTime($stats['derniere_connexion']);
                echo $date->format('d/m/Y à H:i');
                ?>
            </p>
            <p><strong>Membre depuis :</strong> 
                <?php 
                $date = new DateTime($employee['created_at']);
                echo $date->format('d/m/Y');
                ?>
            </p>
        </div>
    <?php endif; ?>
</div>