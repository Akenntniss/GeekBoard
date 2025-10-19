<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Variables par défaut pour la personnalisation des pages
$page_title = $page_title ?? 'GeekBoard SuperAdmin';
$page_heading = $page_heading ?? 'Administration GeekBoard';
$page_subtitle = $page_subtitle ?? 'Plateforme de gestion centralisée';
$page_icon = $page_icon ?? 'fas fa-cogs';
$extra_head_html = $extra_head_html ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Interface d'administration GeekBoard - Gestion centralisée des boutiques">
    <meta name="author" content="GeekBoard">
    <meta name="theme-color" content="#2563eb">
    
    <!-- Title -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/apple-touch-icon.png">
    
    <!-- Preconnect pour performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Fonts modernes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Frameworks CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">
    
    <!-- Styles superadmin -->
    <link rel="stylesheet" href="assets/superadmin.css?v=<?php echo filemtime(__DIR__ . '/../assets/superadmin.css'); ?>">
    
    <?php echo $extra_head_html; ?>
</head>
<body>
    <!-- Loader initial (optionnel) -->
    <div id="initial-loader" style="
        position: fixed; 
        top: 0; left: 0; right: 0; bottom: 0; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        z-index: 9999; 
        display: flex; 
        align-items: center; 
        justify-content: center;
        transition: opacity 0.5s ease;
    ">
        <div style="text-align: center; color: white;">
            <i class="fas fa-cogs fa-3x fa-spin mb-3"></i>
            <p style="font-weight: 600; margin: 0;">Chargement...</p>
        </div>
    </div>

    <div class="sa-container">
        <!-- En-tête modernisé -->
        <header class="header-section">
            <!-- Navigation rapide si nécessaire -->
            <?php if (isset($_SESSION['superadmin_id'])): ?>
            <nav class="quick-nav" style="position: absolute; top: 1rem; left: 2rem; right: 2rem; display: flex; justify-content: space-between; align-items: center; z-index: 10;">
                <div class="nav-left">
                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" style="color: rgba(255,255,255,0.9); text-decoration: none; padding: 0.5rem 1rem; border-radius: 2rem; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); transition: all 0.3s ease;">
                        <i class="fas fa-home me-2"></i>Accueil
                    </a>
                </div>
                <div class="nav-right" style="display: flex; gap: 0.5rem; align-items: center;">
                    <button class="btn btn-sm glass-effect" onclick="saToggleTheme()" style="border: 1px solid rgba(255,255,255,0.3); color: white;">
                        <i class="fas fa-moon me-1"></i><span>Thème</span>
                    </button>
                    <a href="logout.php" class="btn btn-sm" style="background: rgba(220,38,38,0.2); border: 1px solid rgba(220,38,38,0.3); color: white;">
                        <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                    </a>
                </div>
            </nav>
            <?php endif; ?>

            <!-- Titre principal -->
            <div style="margin-top: 4rem;">
                <h1>
                    <i class="<?php echo htmlspecialchars($page_icon); ?>"></i>
                    <?php echo htmlspecialchars($page_heading); ?>
                </h1>
                <p><?php echo htmlspecialchars($page_subtitle); ?></p>
                
                <?php if (isset($_SESSION['superadmin_id'])): ?>
                <div class="user-info">
                    <i class="fas fa-user-shield"></i>
                    <span>
                        Connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['superadmin_name'] ?? $_SESSION['superadmin_username'] ?? 'SuperAdmin'); ?></strong>
                    </span>
                    <span style="opacity: 0.7; font-size: 0.875rem; margin-left: 1rem;">
                        <i class="fas fa-clock me-1"></i>
                        <?php echo date('H:i'); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Contenu principal -->
        <main class="content-section">

    <!-- Scripts critiques pour l'initialisation -->
    <script>
        // Masquer le loader après le chargement du DOM
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const loader = document.getElementById('initial-loader');
                if (loader) {
                    loader.style.opacity = '0';
                    setTimeout(() => loader.remove(), 500);
                }
            }, 500);
        });
    </script>
    
    <!-- JavaScript superadmin moderne (chargé de manière non-bloquante) -->
    <script src="assets/superadmin.js?v=<?php echo filemtime(__DIR__ . '/../assets/superadmin.js'); ?>" defer></script>


