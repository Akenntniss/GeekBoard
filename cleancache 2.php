<?php
require_once 'config/session_config.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: index.php');
    exit;
}

// Traitement de la demande de nettoyage
if (isset($_POST['clean'])) {
    $command = 'rm -rf /var/www/html/public_html/cache/* /var/www/html/public_html/tmp/* /var/www/html/public_html/logs/*';
    $output = shell_exec($command);
    
    // Recréer les répertoires avec les bonnes permissions
    $mkdirCommand = 'mkdir -p /var/www/html/public_html/cache /var/www/html/public_html/tmp /var/www/html/public_html/logs && chmod 777 /var/www/html/public_html/cache /var/www/html/public_html/tmp /var/www/html/public_html/logs';
    shell_exec($mkdirCommand);
    
    $message = 'Le cache a été nettoyé avec succès !';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nettoyage du Cache - TechBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Nettoyage du Cache</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($message)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                        <?php endif; ?>
                        
                        <p class="mb-4">Cette page permet de nettoyer le cache du système en supprimant tous les fichiers temporaires.</p>
                        
                        <form method="post" class="text-center">
                            <button type="submit" name="clean" class="btn btn-primary btn-lg">
                                <i class="fas fa-broom me-2"></i>Nettoyer le Cache
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-kit-code.js"></script>
</body>
</html>