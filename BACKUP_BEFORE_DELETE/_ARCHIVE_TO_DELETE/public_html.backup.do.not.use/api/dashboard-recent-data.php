<?php
/**
 * API optimisée pour les données récentes du dashboard
 * Utilise la mise en cache pour améliorer les performances
 */

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=60'); // Cache 1 minute

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification de session
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Fonction pour obtenir les données récentes avec cache
function get_cached_recent_data() {
    $cache_key = 'dashboard_recent_data_' . ($_SESSION['shop_id'] ?? 'default');
    
    // Vérifier le cache APCu si disponible
    if (function_exists('apcu_exists') && apcu_exists($cache_key)) {
        return apcu_fetch($cache_key);
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
            LIMIT 10
        ");
        $reparations = $reparations_stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
            LIMIT 10
        ");
        $taches = $taches_stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
            LIMIT 10
        ");
        $commandes = $commandes_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $data = [
            'reparations' => $reparations,
            'taches' => $taches,
            'commandes' => $commandes,
            'timestamp' => time()
        ];
        
        // Mettre en cache pour 60 secondes
        if (function_exists('apcu_store')) {
            apcu_store($cache_key, $data, 60);
        }
        
        return $data;
        
    } catch (PDOException $e) {
        error_log("Erreur API dashboard recent data: " . $e->getMessage());
        return [
            'reparations' => [],
            'taches' => [],
            'commandes' => [],
            'error' => 'Erreur de base de données'
        ];
    }
}

// Fonctions utilitaires
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

// Récupérer les données
$data = get_cached_recent_data();
?>

<!-- HTML optimisé pour les données récentes -->
<div class="container-fluid">
    <div class="row">
        <!-- Réparations récentes -->
        <div class="col-lg-6 mb-4">
            <div class="card animate-on-scroll">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Réparations Récentes</h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary active" data-filter="all">Toutes</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-filter="nouvelle">Nouvelles</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-filter="en cours">En cours</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($data['reparations'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><button class="btn btn-sm btn-link p-0" data-sort="appareil">Appareil <i class="fas fa-sort"></i></button></th>
                                    <th><button class="btn btn-sm btn-link p-0" data-sort="client">Client <i class="fas fa-sort"></i></button></th>
                                    <th><button class="btn btn-sm btn-link p-0" data-sort="statut">Statut <i class="fas fa-sort"></i></button></th>
                                    <th><button class="btn btn-sm btn-link p-0" data-sort="date">Date <i class="fas fa-sort"></i></button></th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['reparations'] as $reparation): ?>
                                <tr data-status="<?= strtolower($reparation['statut_libelle']) ?>" data-id="<?= $reparation['id'] ?>">
                                    <td data-value="appareil">
                                        <strong><?= htmlspecialchars($reparation['appareil'] ?? 'N/A') ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($reparation['marque'] ?? '') ?></small>
                                    </td>
                                    <td data-value="client">
                                        <?= htmlspecialchars(trim(($reparation['prenom'] ?? '') . ' ' . ($reparation['nom'] ?? ''))) ?: 'N/A' ?>
                                        <?php if (!empty($reparation['telephone'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($reparation['telephone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td data-value="statut">
                                        <span class="badge badge-<?= get_priority_color($reparation['statut_libelle'] ?? '') ?>">
                                            <?= htmlspecialchars($reparation['statut_libelle'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td data-value="date">
                                        <?= date('d/m/Y', strtotime($reparation['date_reception'] ?? 'now')) ?>
                                        <br><small class="text-muted"><?= date('H:i', strtotime($reparation['date_reception'] ?? 'now')) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="window.location.href='index.php?page=reparation_details&id=<?= $reparation['id'] ?>'">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    onclick="window.location.href='index.php?page=modifier_reparation&id=<?= $reparation['id'] ?>'">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="card-body text-center text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Aucune réparation récente</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tâches en cours -->
        <div class="col-lg-6 mb-4">
            <div class="card animate-on-scroll">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Tâches En Cours</h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary active" data-filter="all">Toutes</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-filter="expired">Expirées</button>
                        <button type="button" class="btn btn-sm btn-outline-warning" data-filter="today">Aujourd'hui</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($data['taches'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><button class="btn btn-sm btn-link p-0" data-sort="titre">Tâche <i class="fas fa-sort"></i></button></th>
                                    <th><button class="btn btn-sm btn-link p-0" data-sort="priorite">Priorité <i class="fas fa-sort"></i></button></th>
                                    <th><button class="btn btn-sm btn-link p-0" data-sort="echeance">Échéance <i class="fas fa-sort"></i></button></th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['taches'] as $tache): ?>
                                <tr data-status="<?= $tache['urgence_status'] ?>" data-id="<?= $tache['id'] ?>">
                                    <td data-value="titre">
                                        <strong><?= htmlspecialchars($tache['titre'] ?? 'N/A') ?></strong>
                                        <?php if (!empty($tache['description'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(substr($tache['description'], 0, 50)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td data-value="priorite">
                                        <span class="badge badge-<?= get_priority_color($tache['priorite'] ?? '') ?>">
                                            <?= htmlspecialchars($tache['priorite'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td data-value="echeance">
                                        <?= date('d/m/Y', strtotime($tache['date_echeance'] ?? 'now')) ?>
                                        <br><span class="badge badge-<?= get_urgence_class($tache['urgence_status']) ?> badge-sm">
                                            <?php
                                            switch($tache['urgence_status']) {
                                                case 'expired': echo 'Expirée'; break;
                                                case 'today': echo 'Aujourd\'hui'; break;
                                                case 'soon': echo 'Bientôt'; break;
                                                default: echo 'Normal';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    onclick="markTaskComplete(<?= $tache['id'] ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    onclick="editTask(<?= $tache['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="card-body text-center text-muted">
                        <i class="fas fa-tasks fa-3x mb-3"></i>
                        <p>Aucune tâche en cours</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Commandes urgentes -->
    <?php if (!empty($data['commandes'])): ?>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card animate-on-scroll">
                <div class="card-header">
                    <h5 class="mb-0">Commandes Urgentes <span class="badge badge-warning"><?= count($data['commandes']) ?></span></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Client</th>
                                    <th>Fournisseur</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($data['commandes'] as $commande): ?>
                                <tr data-id="<?= $commande['id'] ?>">
                                    <td>
                                        <strong>#<?= htmlspecialchars($commande['reference'] ?? $commande['id']) ?></strong>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(trim(($commande['client_prenom'] ?? '') . ' ' . ($commande['client_nom'] ?? ''))) ?: 'N/A' ?>
                                    </td>
                                    <td><?= htmlspecialchars($commande['fournisseur_nom'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge badge-<?= $commande['statut'] === 'urgent' ? 'danger' : 'warning' ?>">
                                            <?= htmlspecialchars(ucfirst($commande['statut'] ?? 'N/A')) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($commande['date_creation'] ?? 'now')) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="viewOrder(<?= $commande['id'] ?>)">
                                            <i class="fas fa-eye"></i> Voir
                                        </button>
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
    <?php endif; ?>
</div>

<!-- Scripts spécifiques pour les actions -->
<script>
function markTaskComplete(taskId) {
    if (confirm('Marquer cette tâche comme terminée ?')) {
        // Utiliser l'API du dashboard pour marquer la tâche comme terminée
        window.DashboardAPI.utils.fetchData('api/update-task.php', {
            method: 'POST',
            body: JSON.stringify({ id: taskId, statut: 'termine' })
        }).then(() => {
            window.DashboardAPI.showNotification('Tâche marquée comme terminée', 'success');
            // Recharger les données
            setTimeout(() => location.reload(), 1000);
        }).catch(error => {
            window.DashboardAPI.showNotification('Erreur lors de la mise à jour', 'error');
        });
    }
}

function editTask(taskId) {
    window.location.href = `index.php?page=taches&edit=${taskId}`;
}

function viewOrder(orderId) {
    window.DashboardAPI.openModal('#orderModal');
    // Charger les détails de la commande
}
</script>
