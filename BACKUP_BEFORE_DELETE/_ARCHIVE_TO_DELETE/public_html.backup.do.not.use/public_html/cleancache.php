<?php
require_once 'config/session_config.php';

// Fonction pour nettoyer le cache
function cleanCache() {
    $output = shell_exec('rm -rf /var/www/html/public_html/cache/* /var/www/html/public_html/tmp/* /var/www/html/public_html/logs/*');
    
    // Recréation des répertoires avec les bonnes permissions
    shell_exec('mkdir -p /var/www/html/public_html/cache /var/www/html/public_html/tmp /var/www/html/public_html/logs');
    shell_exec('chmod 777 /var/www/html/public_html/cache /var/www/html/public_html/tmp /var/www/html/public_html/logs');
    
    return true;
}

$success = false;
$message = '';

// Traitement de la demande de nettoyage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (cleanCache()) {
            $success = true;
            $message = 'Le cache a été nettoyé avec succès.';
        } else {
            $message = 'Une erreur est survenue lors du nettoyage du cache.';
        }
    } catch (Exception $e) {
        $message = 'Erreur : ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nettoyage du Cache - TechBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 600px; margin-top: 50px; }
        .card { border-radius: 15px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .btn-clean { background-color: #0d6efd; color: white; padding: 10px 30px; }
        .btn-clean:hover { background-color: #0b5ed7; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Nettoyage du Cache</h2>
                
                <?php if ($message): ?>
                <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="text-center">
                    <button type="submit" class="btn btn-clean btn-lg">
                        Nettoyer le Cache
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>