<?php
/**
 * Visualiseur simple des soumissions de contact
 * Version sans authentification pour débogage
 */

// Configuration basique
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la connexion à la base de données
require_once '../config/database.php';

try {
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
    
    // Statistiques
    $total = $pdo->query("SELECT COUNT(*) FROM contact_requests")->fetchColumn();
    $today = $pdo->query("SELECT COUNT(*) FROM contact_requests WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $week = $pdo->query("SELECT COUNT(*) FROM contact_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    
    // Récupérer les soumissions
    $stmt = $pdo->query("SELECT * FROM contact_requests ORDER BY created_at DESC LIMIT 50");
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumissions Contact - SERVO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid py-4">
    
    <!-- Header -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h1 class="h3 mb-3">
                        <i class="fas fa-envelope me-2 text-primary"></i>
                        Soumissions de Contact SERVO
                    </h1>
                    
                    <!-- Statistiques -->
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h4 class="text-primary"><?= $total ?></h4>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h4 class="text-success"><?= $today ?></h4>
                                    <small class="text-muted">Aujourd'hui</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h4 class="text-info"><?= $week ?></h4>
                                    <small class="text-muted">7 derniers jours</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation rapide -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="simple_contact_viewer.php" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i>Voir les contacts
                        </a>
                        <a href="simple_email_test.php" class="btn btn-success">
                            <i class="fas fa-envelope me-2"></i>Tester les emails
                        </a>
                        <a href="https://servo.tools/contact" target="_blank" class="btn btn-info">
                            <i class="fas fa-external-link-alt me-2"></i>Page contact
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste des soumissions -->
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Dernières soumissions (50 max)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($submissions)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucune soumission</h5>
                            <p class="text-muted">Aucune demande de contact n'a encore été reçue.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
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
                                            <td><?= $submission['id'] ?></td>
                                            <td>
                                                <small>
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
                                                <?php if (!empty($submission['employees'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($submission['employees']) ?> employés</small>
                                                <?php endif; ?>
                                                <?php if (!empty($submission['repairs'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($submission['repairs']) ?> réparations/mois</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars($submission['subject']) ?>
                                                </span>
                                            </td>
                                            <td style="max-width: 200px;">
                                                <?php
                                                $message = htmlspecialchars($submission['message'] ?? '');
                                                echo strlen($message) > 100 ? substr($message, 0, 100) . '...' : $message;
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="showDetails(<?= $submission['id'] ?>)"
                                                            data-bs-toggle="modal" data-bs-target="#detailsModal">
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour détails -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la soumission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Contenu chargé dynamiquement -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const submissions = <?= json_encode($submissions) ?>;

function showDetails(id) {
    const submission = submissions.find(s => s.id == id);
    if (!submission) return;
    
    const content = `
        <div class="row g-3">
            <div class="col-md-6">
                <h6 class="fw-bold text-primary">Informations du contact</h6>
                <table class="table table-borderless table-sm">
                    <tr><td class="fw-semibold">Prénom :</td><td>${submission.first_name}</td></tr>
                    <tr><td class="fw-semibold">Nom :</td><td>${submission.last_name}</td></tr>
                    <tr><td class="fw-semibold">Email :</td><td><a href="mailto:${submission.email}">${submission.email}</a></td></tr>
                    ${submission.phone ? `<tr><td class="fw-semibold">Téléphone :</td><td><a href="tel:${submission.phone}">${submission.phone}</a></td></tr>` : ''}
                    ${submission.company ? `<tr><td class="fw-semibold">Entreprise :</td><td>${submission.company}</td></tr>` : ''}
                    ${submission.employees ? `<tr><td class="fw-semibold">Employés :</td><td>${submission.employees}</td></tr>` : ''}
                    ${submission.repairs ? `<tr><td class="fw-semibold">Réparations/mois :</td><td>${submission.repairs}</td></tr>` : ''}
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold text-info">Détails de la demande</h6>
                <table class="table table-borderless table-sm">
                    <tr><td class="fw-semibold">Sujet :</td><td><span class="badge bg-info">${submission.subject}</span></td></tr>
                    <tr><td class="fw-semibold">Date :</td><td>${new Date(submission.created_at).toLocaleString('fr-FR')}</td></tr>
                    ${submission.ip_address ? `<tr><td class="fw-semibold">IP :</td><td><code>${submission.ip_address}</code></td></tr>` : ''}
                </table>
            </div>
        </div>
        ${submission.message ? `
            <div class="mt-4">
                <h6 class="fw-bold text-success">Message</h6>
                <div class="card bg-light">
                    <div class="card-body">
                        ${submission.message.replace(/\n/g, '<br>')}
                    </div>
                </div>
            </div>
        ` : ''}
        <div class="mt-3 text-center">
            <a href="mailto:${submission.email}?subject=Re: ${encodeURIComponent(submission.subject)}" class="btn btn-primary">
                <i class="fas fa-reply me-2"></i>Répondre par email
            </a>
        </div>
    `;
    
    document.getElementById('modalContent').innerHTML = content;
}
</script>

</body>
</html>
