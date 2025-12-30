<?php
/**
 * DIAGNOSTIC DE CACHE - TABLEAUX CLIENTS
 * Script pour identifier et résoudre les problèmes de cache
 */

// Headers anti-cache ultra-agressifs
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Diagnostic Cache - Tableaux Clients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .diagnostic-card { backdrop-filter: blur(10px); background: rgba(255,255,255,0.9); }
        .status-good { color: #28a745; }
        .status-bad { color: #dc3545; }
        .code-block { background: #f8f9fa; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card diagnostic-card shadow-lg rounded-4">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h1 class="mb-0"><i class="fas fa-tools me-3"></i>Diagnostic Cache - Tableaux Clients</h1>
                        <p class="mb-0 mt-2">Identifie et résout les problèmes de cache du navigateur</p>
                    </div>
                    
                    <div class="card-body p-5">
                        
                        <!-- STATUS SERVEUR -->
                        <div class="mb-5">
                            <h3><i class="fas fa-server me-2 text-primary"></i>État du Serveur</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <i class="fas fa-check-circle fa-3x status-good mb-3"></i>
                                            <h5>Fichiers CSS</h5>
                                            <p class="mb-1"><strong>tableaux-master.css</strong></p>
                                            <small class="text-muted">
                                                <?php
                                                $css_file = 'assets/css/tableaux-master.css';
                                                if (file_exists($css_file)) {
                                                    $css_size = filesize($css_file);
                                                    $css_time = filemtime($css_file);
                                                    echo "Taille: " . number_format($css_size) . " bytes<br>";
                                                    echo "Modifié: " . date('d/m/Y H:i:s', $css_time);
                                                } else {
                                                    echo '<span class="status-bad">Fichier non trouvé !</span>';
                                                }
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <i class="fas fa-file-code fa-3x status-good mb-3"></i>
                                            <h5>Page Clients</h5>
                                            <p class="mb-1"><strong>clients.php</strong></p>
                                            <small class="text-muted">
                                                <?php
                                                $php_file = 'pages/clients.php';
                                                if (file_exists($php_file)) {
                                                    $php_size = filesize($php_file);
                                                    $php_time = filemtime($php_file);
                                                    echo "Taille: " . number_format($php_size) . " bytes<br>";
                                                    echo "Modifié: " . date('d/m/Y H:i:s', $php_time);
                                                } else {
                                                    echo '<span class="status-bad">Fichier non trouvé !</span>';
                                                }
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- DIAGNOSTIC CACHE NAVIGATEUR -->
                        <div class="mb-5">
                            <h3><i class="fas fa-browser me-2 text-primary"></i>Diagnostic Cache Navigateur</h3>
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle me-2"></i>Test en cours...</h5>
                                <div id="cacheTest">
                                    <div class="spinner-border text-primary me-2" role="status"></div>
                                    Vérification du cache en cours...
                                </div>
                            </div>
                        </div>
                        
                        <!-- INSTRUCTIONS VIDAGE CACHE -->
                        <div class="mb-5">
                            <h3><i class="fas fa-trash-alt me-2 text-danger"></i>Vider le Cache Navigateur</h3>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="fab fa-chrome fa-3x text-primary mb-3"></i>
                                            <h5>Chrome / Edge</h5>
                                            <div class="code-block p-3 rounded">
                                                <strong>Ctrl + Shift + R</strong><br>
                                                ou<br>
                                                <strong>F12 → Outils → Vider le cache</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="fab fa-firefox fa-3x text-warning mb-3"></i>
                                            <h5>Firefox</h5>
                                            <div class="code-block p-3 rounded">
                                                <strong>Ctrl + Shift + R</strong><br>
                                                ou<br>
                                                <strong>Ctrl + F5</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="fab fa-safari fa-3x text-info mb-3"></i>
                                            <h5>Safari</h5>
                                            <div class="code-block p-3 rounded">
                                                <strong>Cmd + Shift + R</strong><br>
                                                ou<br>
                                                <strong>Cmd + Option + R</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- BOUTONS D'ACTION -->
                        <div class="text-center">
                            <a href="index.php?page=clients&cache_bust=<?php echo time() . '_' . mt_rand(1000, 9999); ?>" 
                               class="btn btn-primary btn-lg me-3">
                                <i class="fas fa-refresh me-2"></i>
                                Tester la Page Clients
                            </a>
                            
                            <button onclick="location.reload(true)" class="btn btn-success btn-lg">
                                <i class="fas fa-sync-alt me-2"></i>
                                Recharger Cette Page
                            </button>
                        </div>
                        
                        <!-- TIMESTAMP DE GENERATION -->
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Généré le <?php echo date('d/m/Y à H:i:s'); ?> 
                                - Timestamp: <?php echo time(); ?>
                            </small>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Test de cache automatique
    setTimeout(function() {
        const cacheTest = document.getElementById('cacheTest');
        const timestamp = new Date().getTime();
        
        // Tester le chargement du CSS
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'assets/css/tableaux-master.css?cache_test=' + timestamp;
        
        link.onload = function() {
            cacheTest.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Cache OK !</strong> Le CSS se charge correctement.
                </div>
            `;
        };
        
        link.onerror = function() {
            cacheTest.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Problème détecté !</strong> Le CSS ne se charge pas correctement.
                </div>
            `;
        };
        
        document.head.appendChild(link);
    }, 2000);
    </script>
</body>
</html> 