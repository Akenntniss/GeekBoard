/**
 * Wrapper pour rendre afficherDetailsTache accessible globalement
 *  La fonction existe dans taches.js mais est dans le scope du DOMContentLoaded
 * Ce wrapper permet de l'appeler depuis les onclick dans le HTML
 */

// Variable pour stocker la référence à la fonction une fois qu'elle sera créée
let afficherDetailsTacheGlobal = null;

// Fonction globale qui sera appelée depuis le HTML
function afficherDetailsTache(event, taskId) {
    // Si la fonction n'est pas encore disponible, attendre un peu
    if (!afficherDetailsTacheGlobal) {
        console.warn('afficherDetailsTache pas encore initialisée, tentative dans 100ms...');
        setTimeout(() => afficherDetailsTache(event, taskId), 100);
        return;
    }

    // Appeler la vraie fonction
    afficherDetailsTacheGlobal(event, taskId);
}

// Quand le DOM est chargé, récupérer la fonction depuis le fichier taches.js
document.addEventListener('DOMContentLoaded', function () {
    // La fonction est définie dans taches.js, mais on a besoin de la rendre accessible
    // Pour cela, on va la redéfinir ici en accédant au code existant

    afficherDetailsTacheGlobal = function (event, taskId) {
        // console.log("afficherDetailsTache (global) appelée pour tâche:", taskId);

        // Empêcher la propagation
        if (event) event.stopPropagation();

        // Trouver l'élément
        const taskElement = document.querySelector(`[data-task-id="${taskId}"]`);

        if (!taskElement) {
            console.error(`Tâche ${taskId} introuvable`);
            return;
        }

        let title, priority;

        // Extraire les données selon la structure HTML
        if (taskElement.classList.contains('modern-task-card')) {
            // Vue cartes (taches_moderne.php)
            title = taskElement.querySelector('.task-card-title')?.textContent.trim();
            priority = taskElement.querySelector('.task-card-priority')?.textContent.trim();
        } else if (taskElement.classList.contains('modern-table-row')) {
            // Vue moderne de la page d'accueil (accueil-modern.php)
            title = taskElement.querySelector('.modern-table-text')?.textContent.trim();
            priority = taskElement.querySelector('.modern-badge')?.textContent.trim();
        } else {
            // Vue tableau classique - le <tr> est déjà l'élément trouvé
            const taskRow = taskElement;
            if (taskRow) {
                // Colonne 1 = Titre, Colonne 3 = Priorité
                title = taskRow.querySelector('td:nth-child(1) strong')?.textContent.trim();
                priority = taskRow.querySelector('td:nth-child(3) .task-card-priority')?.textContent.trim();
            }
        }

        if (!title || !priority) {
            console.warn("Informations incomplètes dans le DOM, utilisation de valeurs par défaut en attendant l'AJAX");
            // console.log("Title:", title, "Priority:", priority, "Element:", taskElement);
            title = "Chargement...";
            priority = "Normal"; // Valeur par défaut
        }

        // Remplir le modal
        document.getElementById('task-title').textContent = title;

        const priorityElement = document.getElementById('task-priority');
        priorityElement.textContent = priority;
        priorityElement.className = 'modern-priority-badge';

        // Couleurs de priorité
        switch (priority.toLowerCase()) {
            case 'haute':
                priorityElement.style.background = 'linear-gradient(135deg, #ff4757, #c44569)';
                break;
            case 'moyenne':
                priorityElement.style.background = 'linear-gradient(135deg, #ffa502, #ff6348)';
                break;
            case 'basse':
                priorityElement.style.background = 'linear-gradient(135deg, #2ed573, #1e90ff)';
                break;
            default:
                priorityElement.style.background = 'linear-gradient(135deg, #747d8c, #57606f)';
        }

        // Mettre à jour les boutons
        const startButton = document.getElementById('start-task-btn');
        const completeButton = document.getElementById('complete-task-btn');

        if (startButton) {
            startButton.setAttribute('data-task-id', taskId);
            startButton.setAttribute('data-status', 'en_cours');
            startButton.disabled = false;
        }

        if (completeButton) {
            completeButton.setAttribute('data-task-id', taskId);
            completeButton.setAttribute('data-status', 'termine');
            completeButton.disabled = false;
        }

        // Afficher le modal
        const taskModal = document.getElementById('taskDetailsModal');
        if (taskModal) {
            const bsModal = new bootstrap.Modal(taskModal);
            bsModal.show();

            // Charger les détails via AJAX
            fetch(`ajax/get_tache_details.php?id=${taskId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('task-description-loader').style.display = 'none';
                        document.getElementById('task-description').style.display = 'block';
                        document.getElementById('task-description').textContent = data.description || 'Aucune description disponible';
                    }
                })
                .catch(error => {
                    console.error('Erreur AJAX:', error);
                    document.getElementById('task-description-loader').style.display = 'none';
                    document.getElementById('task-description').textContent = 'Erreur de chargement';
                });
        }
    };

    // console.log('✅ afficherDetailsTache wrapper initialisé');
});
