<?php
/**
 * Script pour ajouter les boutons de pointage à navbar_new.php
 */

$navbar_file = '/var/www/mdgeek.top/includes/navbar_new.php';

// Lire le contenu du fichier
$content = file_get_contents($navbar_file);

if (!$content) {
    die("Erreur: Impossible de lire navbar_new.php\n");
}

// Code à insérer pour les boutons de pointage
$timetracking_code = '        
        <!-- SYSTÈME DE POINTAGE - Boutons Clock-In/Clock-Out -->
        <div class="time-tracking-controls d-none d-lg-flex align-items-center me-3">
            <!-- Statut actuel -->
            <div id="time-status-display" class="time-tracking-status me-2">
                <small class="text-muted">Chargement...</small>
            </div>
            
            <!-- Bouton principal Clock-In/Clock-Out -->
            <button id="clock-button" class="btn btn-success btn-sm mx-1" onclick="timeTracking?.clockIn()">
                <i class="fas fa-sign-in-alt"></i> Clock-In
            </button>
            
            <!-- Bouton Pause (affiché seulement quand pointé) -->
            <button id="break-button" class="btn btn-outline-secondary btn-sm mx-1" style="display: none;">
                <i class="fas fa-pause"></i> Pause
            </button>
        </div>
';

// Insérer avant le bouton "Nouvelle"
$pattern = "/(\s*<!-- Bouton Nouvelle avec dropdown -->)/";
$replacement = $timetracking_code . "$1";

$new_content = preg_replace($pattern, $replacement, $content);

// Vérifier que la modification a été appliquée
if (strpos($new_content, 'time-status-display') === false) {
    die("Erreur: Impossible d'ajouter les boutons de pointage\n");
}

// Sauvegarder
if (file_put_contents($navbar_file, $new_content)) {
    echo "✅ Boutons de pointage ajoutés à navbar_new.php !\n";
} else {
    echo "❌ Erreur lors de la sauvegarde\n";
}

// Maintenant ajouter le support mobile
$mobile_pattern = "/(\s*<!-- Navbar mobile classique)/";
$mobile_code = '            
            <!-- Système de pointage pour mobile -->
            <div class="mobile-time-tracking mt-1">
                <div id="mobile-time-status-display" class="mb-1">
                    <small class="text-muted">Chargement...</small>
                </div>
                <div class="d-flex justify-content-center gap-2">
                    <button id="mobile-clock-button" class="btn btn-success btn-sm" onclick="timeTracking?.clockIn()">
                        <i class="fas fa-sign-in-alt"></i> Clock-In
                    </button>
                    <button id="mobile-break-button" class="btn btn-outline-secondary btn-sm" style="display: none;">
                        <i class="fas fa-pause"></i> Pause
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
$1';

$final_content = preg_replace($mobile_pattern, $mobile_code, $new_content);

if (file_put_contents($navbar_file, $final_content)) {
    echo "✅ Support mobile ajouté à navbar_new.php !\n";
} else {
    echo "❌ Erreur lors de l'ajout du support mobile\n";
}
?>
