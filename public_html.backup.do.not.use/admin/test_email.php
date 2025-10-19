<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/email.php';

// Vérifier l'authentification admin
checkAuth();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_type = $_POST['test_type'] ?? '';
    $test_email = $_POST['test_email'] ?? '';
    
    if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Veuillez saisir un email valide.';
        $message_type = 'danger';
    } else {
        switch ($test_type) {
            case 'notification':
                // Test de notification équipe
                $test_data = [
                    'firstName' => 'Test',
                    'lastName' => 'Utilisateur',
                    'email' => $test_email,
                    'phone' => '06 12 34 56 78',
                    'company' => 'Test Company',
                    'employees' => '1-3',
                    'repairs' => '100-300',
                    'subject' => 'Test - Démo générale',
                    'message' => 'Ceci est un message de test pour vérifier le bon fonctionnement du système d\'email SMTP.'
                ];
                
                $result = sendContactNotification($test_data);
                break;
                
            case 'confirmation':
                // Test de confirmation client
                $test_data = [
                    'firstName' => 'Test',
                    'lastName' => 'Client',
                    'email' => $test_email,
                    'phone' => '06 12 34 56 78',
                    'company' => 'Test Company',
                    'subject' => 'Test - Démo générale',
                    'message' => 'Test de confirmation'
                ];
                
                $result = sendContactConfirmation($test_data);
                break;
                
            case 'simple':
                // Test simple
                $result = sendEmail(
                    $test_email,
                    'Destinataire Test',
                    'Test SMTP SERVO - ' . date('d/m/Y H:i'),
                    '<h2>Test de configuration SMTP</h2>
                     <p>Si vous recevez cet email, la configuration SMTP fonctionne correctement !</p>
                     <p><strong>Date/Heure :</strong> ' . date('d/m/Y à H:i:s') . '</p>
                     <p><strong>Serveur :</strong> ' . SMTP_HOST . ':' . SMTP_PORT . '</p>
                     <p><strong>Encryption :</strong> ' . SMTP_ENCRYPTION . '</p>',
                    'Test de configuration SMTP - Si vous recevez cet email, la configuration fonctionne !'
                );
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
    }
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-envelope-open-text me-2"></i>
                        Test Configuration Email SMTP
                    </h4>
                </div>
                
                <div class="card-body">
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Configuration actuelle -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Configuration SMTP</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="fw-semibold">Serveur :</td>
                                            <td><?= SMTP_HOST ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Port :</td>
                                            <td><?= SMTP_PORT ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Encryption :</td>
                                            <td><?= SMTP_ENCRYPTION ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Utilisateur :</td>
                                            <td><?= SMTP_USERNAME ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Mot de passe :</td>
                                            <td><?= str_repeat('*', strlen(SMTP_PASSWORD)) ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Configuration Emails</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="fw-semibold">Email expéditeur :</td>
                                            <td><?= FROM_EMAIL ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Nom expéditeur :</td>
                                            <td><?= FROM_NAME ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Reply-To :</td>
                                            <td><?= REPLY_TO_EMAIL ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold">Contact équipe :</td>
                                            <td><?= CONTACT_EMAIL ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Formulaire de test -->
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email de test *</label>
                                <input type="email" class="form-control" name="test_email" 
                                       value="<?= htmlspecialchars($_POST['test_email'] ?? '') ?>" 
                                       placeholder="votre@email.com" required>
                                <div class="invalid-feedback">
                                    Veuillez saisir un email valide.
                                </div>
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
                                <div class="invalid-feedback">
                                    Veuillez choisir un type de test.
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>
                                        Envoyer le test
                                    </button>
                                    <a href="contact_submissions.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-inbox me-2"></i>
                                        Voir les soumissions
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Aide -->
                    <div class="mt-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="fw-bold">Types de tests disponibles :</h6>
                                <ul class="mb-0">
                                    <li><strong>Test simple SMTP :</strong> Vérifie la connexion de base au serveur SMTP</li>
                                    <li><strong>Email notification équipe :</strong> Test du template d'email envoyé à l'équipe lors d'une nouvelle demande</li>
                                    <li><strong>Email confirmation client :</strong> Test du template d'email de confirmation envoyé au client</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation Bootstrap
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php include '../includes/footer.php'; ?>
