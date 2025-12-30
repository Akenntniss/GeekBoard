<?php
/**
 * Script pour insérer automatiquement le menu de pointage dans modals.php
 */

$modals_file = '/var/www/mdgeek.top/includes/modals.php';

// Lire le contenu du fichier
$content = file_get_contents($modals_file);

// Code HTML à insérer
$menu_html = '
                        <!-- Gestion Pointage (Admin uniquement) -->
                        <?php if (isset($_SESSION[\'user_role\']) && $_SESSION[\'user_role\'] === \'admin\'): ?>
                        <a href="pages/admin_timetracking.php" class="modern-nav-card timetracking-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-timetracking">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                                <div class="live-indicator" id="active-users-indicator" style="display: none;"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Gestion Pointage</h6>
                                <p class="nav-subtitle">Suivi temps réel</p>
                                <div class="live-stats" id="active-users-count-text" style="display: none;">
                                    <small class="text-success"><i class="fas fa-circle pulse"></i> <span id="active-count">0</span> actifs</small>
                                </div>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                        <?php endif; ?>';

// Chercher le point d'insertion - après la fermeture de la carte absences
$pattern = '/(<a href="index\.php\?page=absences_retards"[^>]*absences-card[^>]*>.*?<\/a>)(\s*<\/div>)/s';

if (preg_match($pattern, $content)) {
    // Insérer le menu après la carte absences et avant la fermeture du div
    $new_content = preg_replace($pattern, '$1' . $menu_html . '$2', $content);
    
    // CSS à ajouter
    $css_styles = '
/* Styles pour l\'entrée de menu Gestion Pointage */
.bg-gradient-timetracking {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.timetracking-card:hover .bg-gradient-timetracking {
    background: linear-gradient(135deg, #20c997, #17a2b8);
}

.live-indicator {
    position: absolute;
    top: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    background: #dc3545;
    border-radius: 50%;
    border: 2px solid white;
    animation: pulse 2s infinite;
}

.live-stats {
    font-size: 0.75em;
    margin-top: 2px;
}

.pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.7; }
    100% { transform: scale(1); opacity: 1; }
}

.timetracking-card .nav-content {
    position: relative;
}';
    
    // Chercher la section <style> existante et ajouter les styles
    if (strpos($new_content, '<style>') !== false) {
        $new_content = str_replace('</style>', $css_styles . "\n</style>", $new_content);
    }
    
    // JavaScript à ajouter
    $javascript = '
<script>
// Mettre à jour le compteur d\'utilisateurs actifs pour le menu pointage
function updateTimetrackingMenuIndicator() {
    fetch(\'time_tracking_api.php?action=admin_get_active\')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const count = data.data.count || 0;
                const indicator = document.getElementById(\'active-users-indicator\');
                const countText = document.getElementById(\'active-users-count-text\');
                const countElement = document.getElementById(\'active-count\');
                
                if (indicator && countText && countElement) {
                    countElement.textContent = count;
                    
                    if (count > 0) {
                        indicator.style.display = \'block\';
                        countText.style.display = \'block\';
                    } else {
                        indicator.style.display = \'none\';
                        countText.style.display = \'none\';
                    }
                }
            }
        })
        .catch(error => console.log(\'Erreur indicateur pointage:\', error));
}

// Mettre à jour toutes les 30 secondes
document.addEventListener(\'DOMContentLoaded\', function() {
    // Vérifier si on est admin avant de faire les appels
    if (document.querySelector(\'.timetracking-card\')) {
        updateTimetrackingMenuIndicator();
        setInterval(updateTimetrackingMenuIndicator, 30000);
    }
});
</script>';
    
    // Ajouter le JavaScript à la fin
    if (strpos($new_content, '</body>') !== false) {
        $new_content = str_replace('</body>', $javascript . '</body>', $new_content);
    } else if (strpos($new_content, '</html>') !== false) {
        $new_content = str_replace('</html>', $javascript . '</html>', $new_content);
    } else {
        $new_content .= $javascript;
    }
    
    // Écrire le nouveau contenu
    file_put_contents($modals_file, $new_content);
    echo "✅ Menu de pointage ajouté avec succès dans modals.php\n";
    
} else {
    echo "❌ Impossible de trouver le point d'insertion dans modals.php\n";
    exit(1);
}

echo "✅ Menu de gestion pointage inséré avec succès !\n";
echo "📍 Emplacement : Après 'Absences & Retards' dans la section Administration\n";
echo "🎨 CSS et JavaScript ajoutés\n";
echo "👀 Menu visible uniquement pour les administrateurs\n";
?>

