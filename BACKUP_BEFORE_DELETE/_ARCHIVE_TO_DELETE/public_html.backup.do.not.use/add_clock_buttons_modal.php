<?php
/**
 * Script pour ajouter les boutons IN/OUT dans le modal nouvelles_actions_modal
 */

$modals_file = '/var/www/mdgeek.top/includes/modals.php';

// Lire le contenu du fichier
$content = file_get_contents($modals_file);

if (!$content) {
    die("Erreur: Impossible de lire modals.php\n");
}

// Code à insérer pour les boutons Clock-In/Clock-Out
$clock_buttons_code = '
                <!-- SYSTÈME DE POINTAGE - Boutons IN/OUT -->
                <div class="row justify-content-center mt-3 mb-2">
                    <div class="col-auto">
                        <div class="d-flex gap-2">
                            <!-- Bouton Clock-IN -->
                            <button type="button" id="modal-clock-in-btn" class="btn btn-success btn-lg px-4" onclick="timeTracking?.clockIn(); closeModal();">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                <strong>IN</strong>
                            </button>
                            
                            <!-- Bouton Clock-OUT -->
                            <button type="button" id="modal-clock-out-btn" class="btn btn-danger btn-lg px-4" onclick="timeTracking?.clockOut(); closeModal();">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                <strong>OUT</strong>
                            </button>
                        </div>
                        
                        <!-- Statut de pointage -->
                        <div id="modal-time-status" class="text-center mt-2">
                            <small class="text-muted">Chargement du statut...</small>
                        </div>
                    </div>
                </div>
';

// Trouver le point d'insertion : après "</div>" qui suit le bouton "Nouvelle Commande" et avant "<!-- Effet scanner animé -->"
$pattern = "/(\s*<\/div>\s*\n\s*<!-- Effet scanner animé -->)/";

$replacement = $clock_buttons_code . "\n$1";

$new_content = preg_replace($pattern, $replacement, $content);

// Vérifier que la modification a été appliquée
if (strpos($new_content, 'modal-clock-in-btn') === false) {
    die("Erreur: Impossible d'ajouter les boutons Clock IN/OUT\n");
}

// Ajouter le CSS pour les boutons
$css_code = '
<style>
/* Styles pour les boutons Clock IN/OUT dans le modal */
#modal-clock-in-btn, #modal-clock-out-btn {
    min-width: 80px;
    transition: all 0.3s ease;
    border-radius: 10px;
    font-weight: bold;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

#modal-clock-in-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(40, 167, 69, 0.3);
}

#modal-clock-out-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(220, 53, 69, 0.3);
}

#modal-time-status {
    min-height: 20px;
}
</style>
';

// Ajouter le CSS avant la fermeture du modal
$css_pattern = "/(<\/div>\s*<!-- ========================================= -->)/";
$css_replacement = $css_code . "\n$1";

$final_content = preg_replace($css_pattern, $css_replacement, $new_content);

// Ajouter le JavaScript pour gérer les boutons
$js_code = '
<script>
// Fonction pour fermer le modal après action
function closeModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById("nouvelles_actions_modal"));
    if (modal) {
        modal.hide();
    }
}

// Mettre à jour les boutons selon le statut
function updateModalTimeButtons(status) {
    const clockInBtn = document.getElementById("modal-clock-in-btn");
    const clockOutBtn = document.getElementById("modal-clock-out-btn");
    const statusDiv = document.getElementById("modal-time-status");
    
    if (clockInBtn && clockOutBtn && statusDiv) {
        if (status && status.is_clocked_in) {
            // Utilisateur pointé
            clockInBtn.style.display = "none";
            clockOutBtn.style.display = "inline-block";
            
            const duration = status.formatted_duration || "00:00";
            const statusText = status.is_on_break ? "En pause" : "Actif";
            const statusClass = status.is_on_break ? "text-warning" : "text-success";
            
            statusDiv.innerHTML = `<small class="${statusClass}"><i class="fas fa-clock"></i> ${statusText} - ${duration}</small>`;
        } else {
            // Utilisateur non pointé
            clockInBtn.style.display = "inline-block";
            clockOutBtn.style.display = "none";
            statusDiv.innerHTML = \'<small class="text-muted">Non pointé</small>\';
        }
    }
}

// Écouter les changements de statut
document.addEventListener("timeTrackingStatusUpdated", function(event) {
    updateModalTimeButtons(event.detail);
});

// Mettre à jour au chargement du modal
document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("nouvelles_actions_modal");
    if (modal) {
        modal.addEventListener("shown.bs.modal", function() {
            // Récupérer le statut actuel quand le modal s\'ouvre
            if (window.timeTracking && window.timeTracking.getStatus) {
                window.timeTracking.getStatus().then(status => {
                    updateModalTimeButtons(status);
                });
            }
        });
    }
});
</script>
';

// Ajouter le JavaScript après le CSS
$js_pattern = "/(<style>.*?<\/style>)/s";
$js_replacement = "$1\n" . $js_code;

$final_content = preg_replace($js_pattern, $js_replacement, $final_content);

// Sauvegarder
if (file_put_contents($modals_file, $final_content)) {
    echo "✅ Boutons Clock IN/OUT ajoutés au modal Nouvelle !\n";
    echo "- Boutons IN et OUT côte à côte\n";
    echo "- Placés sous le bouton 'Nouvelle Commande'\n";
    echo "- CSS et JavaScript inclus\n";
} else {
    echo "❌ Erreur lors de la sauvegarde\n";
}
?>
