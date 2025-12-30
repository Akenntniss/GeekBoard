<?php
/**
 * Testeur d'email simple pour SERVO
 * Version sans authentification pour débogage
 */

// Configuration basique
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_email = $_POST['test_email'] ?? '';
    $test_type = $_POST['test_type'] ?? '';
    
    if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Veuillez saisir un email valide.';
        $message_type = 'danger';
    } else {
        try {
            // Inclure la configuration email
            require_once '../config/email.php';
            
            switch ($test_type) {
                case 'simple':
                    $result = sendEmail(
                        $test_email,
                        'Destinataire Test',
                        'Test SMTP SERVO - ' . date('d/m/Y H:i'),
                        '<h2>Test de configuration SMTP</h2>
                         <p>Si vous recevez cet email, la configuration SMTP fonctionne correctement !</p>
                         <p><strong>Date/Heure :</strong> ' . date('d/m/Y à H:i:s') . '</p>
                         <p><strong>Serveur :</strong> ' . SMTP_HOST . ':' . SMTP_PORT . '</p>',
                        'Test SMTP - Configuration fonctionnelle'
                    );
                    break;
                    
                case 'notification':
                    $test_data = [
                        'firstName' => 'Test',
                        'lastName' => 'Notification',
                        'email' => $test_email,
                        'phone' => '06 12 34 56 78',
                        'company' => 'Test Company',
                        'employees' => '1-3',
                        'repairs' => '100-300',
                        'subject' => 'Test - Notification équipe',
                        'message' => 'Ceci est un test de notification envoyé à l\'équipe.'
                    ];
                    $result = sendContactNotification($test_data);
                    break;
                    
                case 'confirmation':
                    $test_data = [
                        'firstName' => 'Test',
                        'lastName' => 'Client',
                        'email' => $test_email,
                        'phone' => '06 12 34 56 78',
                        'company' => 'Test Company',
                        'subject' => 'Test - Confirmation client',
                        'message' => 'Test de confirmation client'
                    ];
                    $result = sendContactConfirmation($test_data);
                    break;
                    
                default:
                    $message = 'Type de test non reconnu.';
                    $message_type = 'danger';
                    break;
            }
            
            if (isset($result)) {
                if ($result['success']) {
                    $message = 'Email envoyé avec succès ! Vérifiez votre boîte mail.';
                    $message_type = 'success';
                } else {
                    $message = 'Erreur lors de l\'envoi : ' . $result['message'];
                    $message_type = 'danger';
                }
            }
            
        } catch (Exception $e) {
            $message = 'Erreur technique : ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email SMTP - SERVO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h1 class="h3 mb-3">
                        <i class="fas fa-envelope-open-text me-2 text-primary"></i>
                        Test Email SMTP - SERVO
                    </h1>
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <a href="simple_contact_viewer.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Voir les contacts
                        </a>
                        <a href="simple_email_test.php" class="btn btn-primary">
                            <i class="fas fa-envelope me-2"></i>Tester les emails
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Configuration -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Configuration SMTP</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?php if (defined('SMTP_HOST')): ?>
                                <table class="table table-borderless table-sm">
                                    <tr><td class="fw-semibold">Serveur :</td><td><?= SMTP_HOST ?></td></tr>
                                    <tr><td class="fw-semibold">Port :</td><td><?= SMTP_PORT ?></td></tr>
                                    <tr><td class="fw-semibold">Encryption :</td><td><?= SMTP_ENCRYPTION ?></td></tr>
                                    <tr><td class="fw-semibold">Utilisateur :</td><td><?= SMTP_USERNAME ?></td></tr>
                                </table>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Configuration SMTP non chargée. Vérifiez le fichier config/email.php
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php if (defined('FROM_EMAIL')): ?>
                                <table class="table table-borderless table-sm">
                                    <tr><td class="fw-semibold">Email expéditeur :</td><td><?= FROM_EMAIL ?></td></tr>
                                    <tr><td class="fw-semibold">Nom expéditeur :</td><td><?= FROM_NAME ?></td></tr>
                                    <tr><td class="fw-semibold">Reply-To :</td><td><?= REPLY_TO_EMAIL ?></td></tr>
                                    <tr><td class="fw-semibold">Contact équipe :</td><td><?= CONTACT_EMAIL ?></td></tr>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulaire de test -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Test d'envoi</h5>
                </div>
                <div class="card-body">
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email de test *</label>
                                <input type="email" class="form-control" name="test_email" 
                                       value="<?= htmlspecialchars($_POST['test_email'] ?? '') ?>" 
                                       placeholder="votre@email.com" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type de test *</label>
                                <select class="form-select" name="test_type" required>
                                    <option value="">Choisir un test</option>
                                    <option value="simple" <?= ($_POST['test_type'] ?? '') === 'simple' ? 'selected' : '' ?>>
                                        Test simple SMTP
                                    </option>
                                    <option value="notification" <?= ($_POST['test_type'] ?? '') === 'notification' ? 'selected' : '' ?>>
                                        Email notification équipe
                                    </option>
                                    <option value="confirmation" <?= ($_POST['test_type'] ?? '') === 'confirmation' ? 'selected' : '' ?>>
                                        Email confirmation client
                                    </option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Envoyer le test
                                </button>
                            </div>
                        </div>
                    </form>
                    
                </div>
            </div>
            
            <!-- Aide -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="fw-bold">Types de tests disponibles :</h6>
                    <ul class="mb-0">
                        <li><strong>Test simple SMTP :</strong> Vérifie la connexion de base au serveur SMTP</li>
                        <li><strong>Email notification équipe :</strong> Test du template d'email envoyé à l'équipe</li>
                        <li><strong>Email confirmation client :</strong> Test du template d'email de confirmation</li>
                    </ul>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
