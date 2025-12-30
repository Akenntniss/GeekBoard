<?php
/**
 * Visualiseur de fichiers de debug pour les réparations
 */

// Sécurité basique
session_start();
if (!isset($_SESSION['shop_id'])) {
    die('Accès non autorisé');
}

$debug_dir = 'assets/debug/';

// Lister tous les fichiers de debug
$debug_files = [];
if (is_dir($debug_dir)) {
    $files = scandir($debug_dir);
    foreach ($files as $file) {
        if (strpos($file, 'debug_reparation_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $debug_files[] = $file;
        }
    }
    // Trier par date (plus récent en premier)
    rsort($debug_files);
}

// Afficher un fichier spécifique
if (isset($_GET['file']) && in_array($_GET['file'], $debug_files)) {
    $file_path = $debug_dir . $_GET['file'];
    $content = file_get_contents($file_path);
    $data = json_decode($content, true);
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Viewer - Réparations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .debug-file {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
        }
        .debug-content {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-wrap;
            max-height: 600px;
            overflow-y: auto;
        }
        .timestamp {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
        }
        .success {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🔍 Debug Viewer - Réparations</h1>
        <p class="text-muted">Consultation des fichiers de debug générés lors de la soumission des formulaires de réparation.</p>
        
        <?php if (empty($debug_files)): ?>
            <div class="alert alert-info">
                <h5>Aucun fichier de debug trouvé</h5>
                <p>Les fichiers de debug seront créés automatiquement lors de la soumission du formulaire de réparation.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-4">
                    <h5>📁 Fichiers de debug (<?= count($debug_files) ?>)</h5>
                    <div class="list-group">
                        <?php foreach ($debug_files as $file): ?>
                            <a href="#" class="list-group-item list-group-item-action debug-file-link" 
                               data-file="<?= htmlspecialchars($file) ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= substr($file, 16, 19) ?></h6>
                                    <small><?= date('d/m/Y H:i', filemtime($debug_dir . $file)) ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($file) ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-8">
                    <h5>📄 Contenu du fichier</h5>
                    <div id="debug-content" class="debug-content">
                        Sélectionnez un fichier de debug à gauche pour voir son contenu...
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="index.php?page=ajouter_reparation" class="btn btn-primary">← Retour au formulaire</a>
            <a href="index.php?page=reparations" class="btn btn-secondary">Liste des réparations</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.debug-file-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const file = this.dataset.file;
                
                // Marquer comme actif
                document.querySelectorAll('.debug-file-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                // Charger le contenu
                fetch(`?file=${encodeURIComponent(file)}`)
                    .then(response => response.json())
                    .then(data => {
                        const content = document.getElementById('debug-content');
                        content.innerHTML = formatDebugData(data);
                    })
                    .catch(error => {
                        document.getElementById('debug-content').innerHTML = 
                            `<span class="error">Erreur lors du chargement: ${error.message}</span>`;
                    });
            });
        });
        
        function formatDebugData(data) {
            let html = '';
            
            html += `<div class="timestamp">🕐 Timestamp: ${data.timestamp}</div>\n`;
            html += `<div class="success">🏪 Shop ID: ${data.shop_id}</div>\n`;
            html += `<div class="success">💾 Database: ${data.database}</div>\n\n`;
            
            html += `<div style="color: #ffd700;">📊 RÉSULTATS:</div>\n`;
            html += `  reparation_id (lastInsertId): ${data.reparation_id_lastInsertId}\n`;
            html += `  real_repair_id (requête): ${data.real_repair_id_requete}\n`;
            html += `  execution_result: ${data.execution_result}\n\n`;
            
            html += `<div style="color: #87ceeb;">🔧 VARIABLES D'INSERTION:</div>\n`;
            for (const [key, value] of Object.entries(data.variables_insertion)) {
                html += `  ${key}: ${value}\n`;
            }
            
            html += `\n<div style="color: #dda0dd;">📝 DONNÉES POST:</div>\n`;
            html += JSON.stringify(data.post_data, null, 2);
            
            html += `\n\n<div style="color: #98fb98;">🔐 DONNÉES SESSION:</div>\n`;
            html += JSON.stringify(data.session_data, null, 2);
            
            return html;
        }
    </script>
</body>
</html>
