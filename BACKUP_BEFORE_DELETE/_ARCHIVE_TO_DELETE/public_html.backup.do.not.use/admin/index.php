<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Vérifier l'authentification admin
checkAuth();

// Statistiques rapides
$pdo = getMainDBConnection();

// Créer la table si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS contact_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    company VARCHAR(255),
    employees VARCHAR(50),
    repairs VARCHAR(50),
    subject VARCHAR(150) NOT NULL,
    message TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM contact_requests")->fetchColumn(),
    'today' => $pdo->query("SELECT COUNT(*) FROM contact_requests WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    'week' => $pdo->query("SELECT COUNT(*) FROM contact_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
    'month' => $pdo->query("SELECT COUNT(*) FROM contact_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn()
];

// Dernières soumissions
$recent_submissions = $pdo->query("
    SELECT id, first_name, last_name, email, company, subject, created_at 
    FROM contact_requests 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Administration SERVO</h2>
            <p class="text-muted mb-0">Gestion des demandes de contact et configuration</p>
        </div>
        <div class="badge bg-primary fs-6">
            <?= date('d/m/Y H:i') ?>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <div class="display-6 text-primary fw-bold"><?= $stats['total'] ?></div>
                    <div class="fs-6 text-muted">Total contacts</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <div class="display-6 text-success fw-bold"><?= $stats['today'] ?></div>
                    <div class="fs-6 text-muted">Aujourd'hui</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <div class="display-6 text-info fw-bold"><?= $stats['week'] ?></div>
                    <div class="fs-6 text-muted">7 derniers jours</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <div class="display-6 text-warning fw-bold"><?= $stats['month'] ?></div>
                    <div class="fs-6 text-muted">30 derniers jours</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions rapides -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-inbox fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title">Soumissions de Contact</h5>
                    <p class="card-text text-muted">Voir et gérer toutes les demandes de contact reçues</p>
                    <a href="contact_submissions.php" class="btn btn-primary">
                        <i class="fas fa-eye me-2"></i>
                        Voir les soumissions
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-envelope-open-text fa-3x text-success"></i>
                    </div>
                    <h5 class="card-title">Test Email SMTP</h5>
                    <p class="card-text text-muted">Tester la configuration et l'envoi d'emails</p>
                    <a href="test_email.php" class="btn btn-success">
                        <i class="fas fa-paper-plane me-2"></i>
                        Tester les emails
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-globe fa-3x text-info"></i>
                    </div>
                    <h5 class="card-title">Site Web</h5>
                    <p class="card-text text-muted">Accéder au site web public SERVO</p>
                    <a href="https://servo.tools" target="_blank" class="btn btn-info">
                        <i class="fas fa-external-link-alt me-2"></i>
                        Ouvrir le site
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Dernières soumissions -->
    <?php if (!empty($recent_submissions)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Dernières soumissions
                    </h5>
                    <a href="contact_submissions.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Contact</th>
                                    <th>Entreprise</th>
                                    <th>Sujet</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_submissions as $submission): ?>
                                    <tr>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d/m H:i', strtotime($submission['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']) ?></strong>
                                            </div>
                                            <div>
                                                <a href="mailto:<?= htmlspecialchars($submission['email']) ?>" class="text-primary small">
                                                    <?= htmlspecialchars($submission['email']) ?>
                                                </a>
                                            </div>
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
                                            <div class="btn-group btn-group-sm">
                                                <a href="contact_submissions.php?search=<?= urlencode($submission['email']) ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucune soumission</h5>
                    <p class="text-muted">Aucune demande de contact n'a encore été reçue.</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: box-shadow 0.15s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.display-6 {
    font-size: 2.5rem;
}
</style>

<?php include '../includes/footer.php'; ?>
