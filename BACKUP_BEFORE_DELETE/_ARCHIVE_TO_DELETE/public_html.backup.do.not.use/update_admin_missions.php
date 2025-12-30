<?php
// Script pour mettre à jour admin_missions.php avec le nouveau design

echo "Début de la mise à jour du design modern glass...\n";

// Lire le fichier CSS moderne
$modernCSS = file_get_contents('/var/www/mdgeek.top/modern_dark_theme.css');
$modernJS = file_get_contents('/var/www/mdgeek.top/modern_theme_manager.js');

// Lire le fichier admin_missions.php existant
$adminContent = file_get_contents('/var/www/mdgeek.top/admin_missions.php');

// Trouver la position du tag </head>
$headEndPos = strpos($adminContent, '</head>');

if ($headEndPos !== false) {
    // Injecter le nouveau CSS avant </head>
    $newCSS = "\n    <style>\n" . $modernCSS . "\n    </style>\n";
    $adminContent = substr_replace($adminContent, $newCSS, $headEndPos, 0);
    
    // Trouver la position du tag </body>
    $bodyEndPos = strrpos($adminContent, '</body>');
    
    if ($bodyEndPos !== false) {
        // Injecter le nouveau JavaScript avant </body>
        $newJS = "\n    <script>\n" . $modernJS . "\n    </script>\n";
        $adminContent = substr_replace($adminContent, $newJS, $bodyEndPos, 0);
        
        // Ajouter le bouton toggle après l'ouverture du body
        $bodyStartPos = strpos($adminContent, '<body>');
        if ($bodyStartPos !== false) {
            $toggleButton = "\n    <!-- Toggle Button -->\n    <button class=\"theme-toggle\" id=\"themeToggle\">\n        <i class=\"fas fa-sun\" id=\"themeIcon\"></i>\n    </button>\n";
            $adminContent = substr_replace($adminContent, $toggleButton, $bodyStartPos + 6, 0);
        }
        
        // Ajouter une classe modern-header au header existant
        $adminContent = str_replace('class="container-fluid"', 'class="container-fluid modern-header"', $adminContent);
        
        // Ajouter des classes glass-card aux éléments existants
        $adminContent = str_replace('class="card"', 'class="card glass-card"', $adminContent);
        $adminContent = str_replace('class="table table-striped"', 'class="table table-striped table-modern"', $adminContent);
        
        // Ajouter des classes pour les statistiques
        $adminContent = preg_replace('/<div class="col-lg-3 col-md-6 mb-4">/', '<div class="col-lg-3 col-md-6 mb-4"><div class="stat-card">', $adminContent);
        
        // Sauvegarder le fichier mis à jour
        file_put_contents('/var/www/mdgeek.top/admin_missions.php', $adminContent);
        
        echo "✅ admin_missions.php mis à jour avec succès!\n";
    } else {
        echo "❌ Erreur: Impossible de trouver le tag </body>\n";
    }
} else {
    echo "❌ Erreur: Impossible de trouver le tag </head>\n";
}

echo "Fin de la mise à jour\n";
?> 