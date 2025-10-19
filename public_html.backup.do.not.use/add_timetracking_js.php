<?php
/**
 * Script pour ajouter le JavaScript time_tracking.js à navbar_new.php
 */

$navbar_file = '/var/www/mdgeek.top/includes/navbar_new.php';

// Lire le contenu du fichier
$content = file_get_contents($navbar_file);

if (!$content) {
    die("Erreur: Impossible de lire navbar_new.php\n");
}

// Code à ajouter à la fin du fichier
$js_code = '
<!-- Inclure le JavaScript de time tracking -->
<script src="<?php echo $navbar_assets_path; ?>js/time_tracking.js"></script>

<!-- Script pour synchroniser les boutons mobile et desktop -->
<script>
    // Écouter les changements de statut pour mettre à jour les boutons mobiles
    document.addEventListener("timeTrackingStatusUpdated", function(event) {
        const status = event.detail;
        
        // Mettre à jour l\'affichage mobile
        const mobileClockButton = document.getElementById(\'mobile-clock-button\');
        const mobileBreakButton = document.getElementById(\'mobile-break-button\');
        const mobileStatusDisplay = document.getElementById(\'mobile-time-status-display\');
        
        if (mobileClockButton) {
            if (status.is_clocked_in) {
                mobileClockButton.innerHTML = \'<i class="fas fa-sign-out-alt"></i> Clock-Out\';
                mobileClockButton.className = \'btn btn-danger btn-sm\';
                mobileClockButton.onclick = () => timeTracking?.clockOut();
            } else {
                mobileClockButton.innerHTML = \'<i class="fas fa-sign-in-alt"></i> Clock-In\';
                mobileClockButton.className = \'btn btn-success btn-sm\';
                mobileClockButton.onclick = () => timeTracking?.clockIn();
            }
        }
        
        if (mobileBreakButton) {
            if (status.is_clocked_in) {
                mobileBreakButton.style.display = \'inline-block\';
                if (status.is_on_break) {
                    mobileBreakButton.innerHTML = \'<i class="fas fa-play"></i> Reprendre\';
                    mobileBreakButton.className = \'btn btn-warning btn-sm\';
                    mobileBreakButton.onclick = () => timeTracking?.endBreak();
                } else {
                    mobileBreakButton.innerHTML = \'<i class="fas fa-pause"></i> Pause\';
                    mobileBreakButton.className = \'btn btn-outline-secondary btn-sm\';
                    mobileBreakButton.onclick = () => timeTracking?.startBreak();
                }
            } else {
                mobileBreakButton.style.display = \'none\';
            }
        }
        
        if (mobileStatusDisplay) {
            if (status.is_clocked_in) {
                const duration = status.formatted_duration || \'00:00\';
                const statusText = status.is_on_break ? \'En pause\' : \'Actif\';
                mobileStatusDisplay.innerHTML = `<small class="text-${status.is_on_break ? \'warning\' : \'success\'}">${statusText} - ${duration}</small>`;
            } else {
                mobileStatusDisplay.innerHTML = \'<small class="text-muted">Non pointé</small>\';
            }
        }
    });
</script>
';

// Ajouter le code à la fin du fichier (avant la balise de fermeture PHP si elle existe)
if (strpos($content, '?>') !== false) {
    // Il y a une balise PHP de fermeture, insérer avant
    $new_content = str_replace('?>', $js_code . "\n?>", $content);
} else {
    // Pas de balise de fermeture, ajouter à la fin
    $new_content = $content . $js_code;
}

// Vérifier que le script a été ajouté
if (strpos($new_content, 'time_tracking.js') === false) {
    die("Erreur: Impossible d'ajouter le script time_tracking.js\n");
}

// Sauvegarder
if (file_put_contents($navbar_file, $new_content)) {
    echo "✅ Script time_tracking.js ajouté à navbar_new.php !\n";
} else {
    echo "❌ Erreur lors de la sauvegarde\n";
}
?>
