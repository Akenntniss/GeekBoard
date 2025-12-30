<?php
/**
 * Script pour corriger les fonctions timeTracking dans le modal
 */

$modals_file = '/var/www/mdgeek.top/includes/modals.php';

// Lire le contenu du fichier
$content = file_get_contents($modals_file);

if (!$content) {
    die("Erreur: Impossible de lire modals.php\n");
}

// Remplacer les appels onclick par des fonctions plus sûres
$old_clock_in = 'onclick="timeTracking?.clockIn(); closeModal();"';
$new_clock_in = 'onclick="safeClockIn()"';

$old_clock_out = 'onclick="timeTracking?.clockOut(); closeModal();"';
$new_clock_out = 'onclick="safeClockOut()"';

$content = str_replace($old_clock_in, $new_clock_in, $content);
$content = str_replace($old_clock_out, $new_clock_out, $content);

// Chercher le script existant et le remplacer
$old_script_pattern = '/<script>.*?function updateModalTimeButtons.*?<\/script>/s';

$new_script = '<script>
// Fonctions sécurisées pour le pointage depuis le modal
async function safeClockIn() {
    try {
        // Attendre que timeTracking soit disponible ou utiliser l\'API directement
        if (window.timeTracking && window.timeTracking.clockIn) {
            await window.timeTracking.clockIn();
        } else {
            // Appel direct à l\'API si timeTracking n\'est pas encore disponible
            const response = await fetch(\'time_tracking_api.php\', {
                method: \'POST\',
                headers: {
                    \'Content-Type\': \'application/x-www-form-urlencoded\',
                },
                body: \'action=clock_in\'
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(\'✅ Pointage d\\\'entrée enregistré !\', \'success\');
                // Actualiser le statut après un court délai
                setTimeout(() => {
                    if (window.timeTracking && window.timeTracking.getCurrentStatus) {
                        window.timeTracking.getCurrentStatus();
                    }
                }, 500);
            } else {
                showToast(\'❌ Erreur: \' + result.message, \'error\');
            }
        }
        
        closeModal();
    } catch (error) {
        console.error(\'Erreur Clock-In:\', error);
        showToast(\'❌ Erreur de connexion\', \'error\');
    }
}

async function safeClockOut() {
    try {
        // Attendre que timeTracking soit disponible ou utiliser l\'API directement
        if (window.timeTracking && window.timeTracking.clockOut) {
            await window.timeTracking.clockOut();
        } else {
            // Appel direct à l\'API si timeTracking n\'est pas encore disponible
            const response = await fetch(\'time_tracking_api.php\', {
                method: \'POST\',
                headers: {
                    \'Content-Type\': \'application/x-www-form-urlencoded\',
                },
                body: \'action=clock_out\'
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(\'✅ Pointage de sortie enregistré !\', \'success\');
                // Actualiser le statut après un court délai
                setTimeout(() => {
                    if (window.timeTracking && window.timeTracking.getCurrentStatus) {
                        window.timeTracking.getCurrentStatus();
                    }
                }, 500);
            } else {
                showToast(\'❌ Erreur: \' + result.message, \'error\');
            }
        }
        
        closeModal();
    } catch (error) {
        console.error(\'Erreur Clock-Out:\', error);
        showToast(\'❌ Erreur de connexion\', \'error\');
    }
}

// Fonction pour afficher les notifications
function showToast(message, type = \'info\') {
    // Utiliser le système de notification GeekBoard s\'il existe
    if (window.showNotification) {
        window.showNotification(message, type);
    } else if (window.toastr) {
        window.toastr[type](message);
    } else {
        // Fallback simple
        alert(message);
    }
}

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
            updateStatusFromAPI();
        });
    }
});

// Fonction pour récupérer le statut directement depuis l\'API
async function updateStatusFromAPI() {
    try {
        const response = await fetch(\'time_tracking_api.php?action=get_status\');
        const result = await response.json();
        
        if (result.success && result.data) {
            updateModalTimeButtons(result.data);
        }
    } catch (error) {
        console.error(\'Erreur récupération statut:\', error);
    }
}
</script>';

// Remplacer l'ancien script
$content = preg_replace($old_script_pattern, $new_script, $content);

// Sauvegarder
if (file_put_contents($modals_file, $content)) {
    echo "✅ Fonctions timeTracking corrigées dans le modal !\n";
    echo "- Fonctions sécurisées safeClockIn() et safeClockOut()\n";
    echo "- Appels directs à l'API en fallback\n";
    echo "- Notifications d'erreur incluses\n";
} else {
    echo "❌ Erreur lors de la sauvegarde\n";
}
?>
