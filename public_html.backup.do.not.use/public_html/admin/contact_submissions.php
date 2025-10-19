<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Vérifier l'authentification admin
checkAuth();

$pdo = getMainDBConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtres
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Construction de la requête
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR company LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params = array_fill(0, 5, $search_param);
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Compter le total
$count_sql = "SELECT COUNT(*) FROM contact_requests $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Récupérer les données
$sql = "SELECT * FROM contact_requests $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-envelope me-2"></i>
                        Soumissions de Contact
                    </h4>
                    <span class="badge bg-primary fs-6"><?= number_format($total_records) ?> total</span>
                </div>
                
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Recherche</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Nom, email, entreprise, message...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date de début</label>
                            <input type="date" class="form-control" name="date_from" 
                                   value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date de fin</label>
                            <input type="date" class="form-control" name="date_to" 
                                   value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="contact_submissions.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Statistiques rapides -->
                    <div class="row g-3 mb-4">
                        <?php
                        $today_count = $pdo->query("SELECT COUNT(*) FROM contact_requests WHERE DATE(created_at) = CURDATE()")->fetchColumn();
                        $week_count = $pdo->query("SELECT COUNT(*) FROM contact_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
                        $month_count = $pdo->query("SELECT COUNT(*) FROM contact_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
                        ?>
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h5 class="text-primary"><?= $today_count ?></h5>
                                    <small class="text-muted">Aujourd'hui</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h5 class="text-success"><?= $week_count ?></h5>
                                    <small class="text-muted">7 derniers jours</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h5 class="text-info"><?= $month_count ?></h5>
                                    <small class="text-muted">30 derniers jours</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tableau des soumissions -->
                    <?php if (empty($submissions)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucune soumission trouvée</h5>
                            <p class="text-muted">Aucune demande de contact ne correspond à vos critères.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Contact</th>
                                        <th>Entreprise</th>
                                        <th>Sujet</th>
                                        <th>Message</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $submission): ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y H:i', strtotime($submission['created_at'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']) ?></strong>
                                                </div>
                                                <div>
                                                    <a href="mailto:<?= htmlspecialchars($submission['email']) ?>" class="text-primary">
                                                        <?= htmlspecialchars($submission['email']) ?>
                                                    </a>
                                                </div>
                                                <?php if (!empty($submission['phone'])): ?>
                                                    <div>
                                                        <a href="tel:<?= htmlspecialchars($submission['phone']) ?>" class="text-success">
                                                            <?= htmlspecialchars($submission['phone']) ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($submission['company'] ?? '-') ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars($submission['subject']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="message-preview" style="max-width: 200px;">
                                                    <?php
                                                    $message = htmlspecialchars($submission['message']);
                                                    echo strlen($message) > 100 ? substr($message, 0, 100) . '...' : $message;
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="viewSubmission(<?= $submission['id'] ?>)"
                                                            data-bs-toggle="modal" data-bs-target="#submissionModal">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="mailto:<?= htmlspecialchars($submission['email']) ?>?subject=Re: <?= urlencode($submission['subject']) ?>" 
                                                       class="btn btn-outline-success">
                                                        <i class="fas fa-reply"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Navigation des soumissions">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                                Précédent
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                                Suivant
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour voir les détails -->
<div class="modal fade" id="submissionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la soumission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="submissionContent">
                <!-- Contenu chargé dynamiquement -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="replyButton">Répondre par email</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewSubmission(id) {
    fetch(`contact_submission_details.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('submissionContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('submissionContent').innerHTML = 
                '<div class="alert alert-danger">Erreur lors du chargement des détails.</div>';
        });
}
</script>

<?php include '../includes/footer.php'; ?>
