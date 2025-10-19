<?php
/**
 * Code à ajouter dans modals.php pour l'entrée de menu Gestion Pointage
 * À insérer après l'entrée "Absences & Retards" (après la ligne avec absences-card)
 */

// Code HTML pour l'entrée de menu
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
                        <?php endif; ?>
';

// CSS à ajouter pour le style de l'entrée de pointage
$css_styles = '
<style>
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
}
</style>
';

// JavaScript pour mettre à jour les indicateurs en temps réel
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
</script>
';

echo "Code pour ajouter l'entrée Gestion Pointage au menu\n";
echo "====================================================\n\n";

echo "1. PLACEMENT DANS LE FICHIER:\n";
echo "   Le code doit être inséré dans includes/modals.php\n";
echo "   APRÈS la section avec 'absences-card' (ligne ~460)\n";
echo "   AVANT la fermeture de </div> de la nav-grid-row\n\n";

echo "2. CODE HTML À INSÉRER:\n";
echo $menu_html . "\n\n";

echo "3. CSS À AJOUTER (dans la section <style> du fichier):\n";
echo $css_styles . "\n\n";

echo "4. JAVASCRIPT À AJOUTER (à la fin du fichier):\n";
echo $javascript . "\n\n";

echo "5. VÉRIFICATIONS:\n";
echo "   - L'entrée sera visible uniquement pour les admins\n";
echo "   - Un indicateur rouge apparaîtra s'il y a des utilisateurs actifs\n";
echo "   - Le compteur se met à jour automatiquement\n";
echo "   - Le lien pointe vers pages/admin_timetracking.php\n\n";

// Créer le fichier de patch complet
$full_patch = "
MODIFICATION DE includes/modals.php
===================================

1. CHERCHER cette ligne (vers ligne 460):
                        </a>

                    </div>

2. REMPLACER par:
                        </a>
$menu_html
                    </div>

3. AJOUTER le CSS dans la section <style> existante:
$css_styles

4. AJOUTER le JavaScript à la fin du fichier:
$javascript
";

file_put_contents('modals_timetracking_patch.txt', $full_patch);
echo "6. FICHIER DE PATCH CRÉÉ:\n";
echo "   Un fichier 'modals_timetracking_patch.txt' a été créé avec toutes les modifications.\n\n";
?>

EXEMPLE DE STRUCTURE FINALE:
============================

                        <a href="index.php?page=absences_retards" class="modern-nav-card absences-card">
                            <!-- contenu de la carte absences -->
                        </a>

                        <!-- NOUVEAU CODE ICI -->
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
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
                        <?php endif; ?>
                        <!-- FIN NOUVEAU CODE -->

                    </div>

ATTENTION: Assurez-vous de bien insérer le code au bon endroit pour maintenir la structure HTML valide.

