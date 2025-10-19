<?php
// Script pour mettre à jour mes_missions.php avec le nouveau design

echo "Début de la mise à jour du design modern glass pour mes_missions.php...\n";

// Lire le fichier CSS moderne
$modernCSS = file_get_contents('/var/www/mdgeek.top/modern_dark_theme.css');
$modernJS = file_get_contents('/var/www/mdgeek.top/modern_theme_manager.js');

// Lire le fichier mes_missions.php existant
$mesMissionsContent = file_get_contents('/var/www/mdgeek.top/mes_missions.php');

// Trouver la position du tag </head>
$headEndPos = strpos($mesMissionsContent, '</head>');

if ($headEndPos !== false) {
    // Injecter le nouveau CSS avant </head>
    $newCSS = "\n    <style>\n" . $modernCSS . "\n    </style>\n";
    $mesMissionsContent = substr_replace($mesMissionsContent, $newCSS, $headEndPos, 0);
    
    // Trouver la position du tag </body>
    $bodyEndPos = strrpos($mesMissionsContent, '</body>');
    
    if ($bodyEndPos !== false) {
        // Injecter le nouveau JavaScript avant </body>
        $newJS = "\n    <script>\n" . $modernJS . "\n    </script>\n";
        $mesMissionsContent = substr_replace($mesMissionsContent, $newJS, $bodyEndPos, 0);
        
        // Ajouter le bouton toggle après l'ouverture du body
        $bodyStartPos = strpos($mesMissionsContent, '<body>');
        if ($bodyStartPos !== false) {
            $toggleButton = "\n    <!-- Toggle Button -->\n    <button class=\"theme-toggle\" id=\"themeToggle\">\n        <i class=\"fas fa-sun\" id=\"themeIcon\"></i>\n    </button>\n";
            $mesMissionsContent = substr_replace($mesMissionsContent, $toggleButton, $bodyStartPos + 6, 0);
        }
        
        // Ajouter une classe modern-header au header existant
        $mesMissionsContent = str_replace('class="container-fluid"', 'class="container-fluid modern-header"', $mesMissionsContent);
        
        // Ajouter des classes glass-card aux éléments existants
        $mesMissionsContent = str_replace('class="card"', 'class="card glass-card"', $mesMissionsContent);
        $mesMissionsContent = str_replace('class="table table-striped"', 'class="table table-striped table-modern"', $mesMissionsContent);
        
        // Ajouter des classes pour les statistiques (spécifiques à mes_missions.php)
        $mesMissionsContent = preg_replace('/<div class="col-lg-3 col-md-6 mb-4">/', '<div class="col-lg-3 col-md-6 mb-4"><div class="mission-stat-card">', $mesMissionsContent);
        
        // Ajouter des classes pour les cartes de missions
        $mesMissionsContent = str_replace('class="mission-item"', 'class="mission-item mission-card"', $mesMissionsContent);
        
        // Sauvegarder le fichier mis à jour
        file_put_contents('/var/www/mdgeek.top/mes_missions.php', $mesMissionsContent);
        
        echo "✅ mes_missions.php mis à jour avec succès!\n";
    } else {
        echo "❌ Erreur: Impossible de trouver le tag </body>\n";
    }
} else {
    echo "❌ Erreur: Impossible de trouver le tag </head>\n";
}

echo "Fin de la mise à jour\n";
?> 