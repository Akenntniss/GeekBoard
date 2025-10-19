<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Vérifier l'authentification admin
checkAuth();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    echo '<div class="alert alert-danger">ID invalide</div>';
    exit;
}

$pdo = getMainDBConnection();
$stmt = $pdo->prepare("SELECT * FROM contact_requests WHERE id = ?");
$stmt->execute([$id]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$submission) {
    echo '<div class="alert alert-danger">Soumission non trouvée</div>';
    exit;
}
?>

<div class="row g-3">
    <div class="col-md-6">
        <h6 class="fw-bold text-primary">Informations du contact</h6>
        <table class="table table-borderless table-sm">
            <tr>
                <td class="fw-semibold">Prénom :</td>
                <td><?= htmlspecialchars($submission['first_name']) ?></td>
            </tr>
            <tr>
                <td class="fw-semibold">Nom :</td>
                <td><?= htmlspecialchars($submission['last_name']) ?></td>
            </tr>
            <tr>
                <td class="fw-semibold">Email :</td>
                <td>
                    <a href="mailto:<?= htmlspecialchars($submission['email']) ?>" class="text-primary">
                        <?= htmlspecialchars($submission['email']) ?>
                    </a>
                </td>
            </tr>
            <?php if (!empty($submission['phone'])): ?>
            <tr>
                <td class="fw-semibold">Téléphone :</td>
                <td>
                    <a href="tel:<?= htmlspecialchars($submission['phone']) ?>" class="text-success">
                        <?= htmlspecialchars($submission['phone']) ?>
                    </a>
                </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($submission['company'])): ?>
            <tr>
                <td class="fw-semibold">Entreprise :</td>
                <td><?= htmlspecialchars($submission['company']) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="fw-bold text-info">Détails de la demande</h6>
        <table class="table table-borderless table-sm">
            <tr>
                <td class="fw-semibold">Sujet :</td>
                <td>
                    <span class="badge bg-info"><?= htmlspecialchars($submission['subject']) ?></span>
                </td>
            </tr>
            <tr>
                <td class="fw-semibold">Date :</td>
                <td><?= date('d/m/Y à H:i', strtotime($submission['created_at'])) ?></td>
            </tr>
            <?php if (!empty($submission['ip_address'])): ?>
            <tr>
                <td class="fw-semibold">IP :</td>
                <td><code><?= htmlspecialchars($submission['ip_address']) ?></code></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="mt-4">
    <h6 class="fw-bold text-success">Message</h6>
    <div class="card bg-light">
        <div class="card-body">
            <?= nl2br(htmlspecialchars($submission['message'])) ?>
        </div>
    </div>
</div>

<script>
document.getElementById('replyButton').onclick = function() {
    const email = '<?= htmlspecialchars($submission['email']) ?>';
    const subject = 'Re: <?= addslashes($submission['subject']) ?>';
    const name = '<?= htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']) ?>';
    
    const body = encodeURIComponent(`Bonjour ${name},\n\nMerci pour votre demande concernant GeekBoard.\n\n\n\nCordialement,\nL'équipe GeekBoard`);
    
    window.open(`mailto:${email}?subject=${encodeURIComponent(subject)}&body=${body}`);
};
</script>
