document.addEventListener('DOMContentLoaded', function() {
    // Ajouter les gestionnaires d'événements pour les boutons de changement de statut de tâche
    document.getElementById('start-task-btn')?.addEventListener('click', updateTaskStatus);
    document.getElementById('complete-task-btn')?.addEventListener('click', updateTaskStatus);
});

// Fonction pour afficher les détails d'une tâche
function afficherDetailsTache(event, taskId) {
    console.log("Fonction afficherDetailsTache appelée avec taskId:", taskId);
    // Empêcher la propagation de l'événement
    event.stopPropagation();
    
    // Trouver l'élément de la tâche correspondante (moderne ou classique)
    const taskElement = document.querySelector(`[data-task-id="${taskId}"]`);
    console.log("Élément de tâche trouvé:", taskElement);
    
    let title, priority;
    
    if (taskElement) {
        // Vérifier si c'est une structure moderne (div) ou classique (tr)
        if (taskElement.classList.contains('modern-table-row')) {
            // Structure moderne avec divs
            title = taskElement.querySelector('.modern-table-text').textContent.trim();
            priority = taskElement.querySelector('.modern-badge').textContent.trim();
        } else {
            // Structure classique avec tableau
            const taskRow = taskElement.closest('tr');
            if (taskRow) {
                title = taskRow.querySelector('td:nth-child(1)').textContent.trim();
                priority = taskRow.querySelector('td:nth-child(2) .badge').textContent.trim();
            }
        }
        console.log("Informations de la tâche:", { title, priority });
        
        // Vérifier que nous avons les informations nécessaires
        if (!title || !priority) {
            console.error("Impossible de récupérer les informations de la tâche");
            return;
        }
        
        // Remplir le modal avec les informations de la tâche
        document.getElementById('task-title').textContent = title;
        
        // Mettre à jour le badge de priorité avec les bonnes couleurs
        const priorityElement = document.getElementById('task-priority');
        priorityElement.textContent = priority;
        priorityElement.className = 'modern-priority-badge';
        
        // Ajouter la classe de couleur appropriée
        switch(priority.toLowerCase()) {
            case 'haute':
                priorityElement.style.background = 'linear-gradient(135deg, #ff4757, #c44569)';
                priorityElement.style.color = 'white';
                break;
            case 'moyenne':
                priorityElement.style.background = 'linear-gradient(135deg, #ffa502, #ff6348)';
                priorityElement.style.color = 'white';
                break;
            case 'basse':
                priorityElement.style.background = 'linear-gradient(135deg, #3742fa, #2f3542)';
                priorityElement.style.color = 'white';
                break;
            default:
                priorityElement.style.background = 'linear-gradient(135deg, #57606f, #3d4454)';
                priorityElement.style.color = 'white';
                break;
        }
        
        // Afficher le loader et masquer la description
        document.getElementById('task-description-loader').style.display = 'flex';
        document.getElementById('task-description').style.display = 'none';
        
        // Mettre à jour les attributs data-task-id des boutons
        document.getElementById('start-task-btn').setAttribute('data-task-id', taskId);
        document.getElementById('complete-task-btn').setAttribute('data-task-id', taskId);
        
        // Gérer l'état actif/inactif des boutons
        const startButton = document.getElementById('start-task-btn');
        const completeButton = document.getElementById('complete-task-btn');
        
        // Par défaut, activer les deux boutons
        startButton.disabled = false;
        startButton.classList.remove('btn-secondary');
        startButton.classList.add('btn-primary');
        
        completeButton.disabled = false;
        completeButton.classList.remove('btn-secondary');
        completeButton.classList.add('btn-success');
        
        // Afficher le modal
        const taskModal = document.getElementById('taskDetailsModal');
        console.log("Modal trouvé:", taskModal);
        if (taskModal) {
            const bsModal = new bootstrap.Modal(taskModal);
            console.log("Modal Bootstrap créé:", bsModal);
            bsModal.show();
            console.log("Modal affiché");
            
            // Charger la description de la tâche via AJAX
            fetch(`ajax/get_tache_details.php?id=${taskId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Masquer le loader et afficher la description
                        document.getElementById('task-description-loader').style.display = 'none';
                        document.getElementById('task-description').style.display = 'block';
                        document.getElementById('task-description').textContent = data.description || "Aucune description disponible";
                        
                        // Afficher les pièces jointes si elles existent
                        const attachmentsContainer = document.getElementById('task-attachments');
                        if (attachmentsContainer) {
                            if (data.attachments && data.attachments.length > 0) {
                                attachmentsContainer.style.display = 'block';
                                displayAttachments(data.attachments);
                            } else {
                                attachmentsContainer.style.display = 'none';
                            }
                        }
                        
                        // Mettre à jour le statut dans le modal
                        const statusElement = document.getElementById('task-status');
                        if (statusElement) {
                            let statusText = 'En attente';
                            switch(data.status) {
                                case 'en_cours':
                                    statusText = 'En cours';
                                    break;
                                case 'termine':
                                    statusText = 'Terminée';
                                    break;
                                case 'a_faire':
                                    statusText = 'À faire';
                                    break;
                            }
                            statusElement.textContent = statusText;
                        }

                        // Renseigner Date de création si disponible
                        const createdEl = document.getElementById('task-created-date');
                        if (createdEl) {
                            // Supporte plusieurs clés possibles côté API
                            const rawDate = data.created_at || data.date_creation || (data.task && (data.task.created_at || data.task.date_creation));
                            if (rawDate) {
                                try {
                                    const d = new Date(rawDate);
                                    const formatted = isNaN(d.getTime()) ? String(rawDate) : d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
                                    createdEl.textContent = formatted;
                                } catch (e) {
                                    createdEl.textContent = String(rawDate);
                                }
                            }
                        }

                        // Renseigner Assigné à si disponible
                        const assigneeEl = document.getElementById('task-assignee');
                        if (assigneeEl) {
                            const assignee = data.assignee || (data.task && (data.task.assignee || data.task.assigned_to || data.task.user_name));
                            if (assignee) {
                                assigneeEl.textContent = assignee;
                            }
                        }
                        
                        // Mettre à jour les boutons en fonction du statut
                        if (data.status === 'termine') {
                            startButton.disabled = true;
                            startButton.style.opacity = '0.5';
                            startButton.style.cursor = 'not-allowed';
                            
                            completeButton.disabled = true;
                            completeButton.style.opacity = '0.5';
                            completeButton.style.cursor = 'not-allowed';
                        } else if (data.status === 'en_cours') {
                            startButton.disabled = true;
                            startButton.style.opacity = '0.5';
                            startButton.style.cursor = 'not-allowed';
                        }
                    } else {
                        // Masquer le loader et afficher l'erreur
                        document.getElementById('task-description-loader').style.display = 'none';
                        document.getElementById('task-description').style.display = 'block';
                        document.getElementById('task-description').textContent = "Erreur lors du chargement de la description";
                        document.getElementById('task-error-container').style.display = 'flex';
                        const errorMessageElement = document.querySelector('#task-error-container .error-message');
                        if (errorMessageElement) {
                            errorMessageElement.textContent = data.message || "Une erreur est survenue";
                        }
                    }
                })
                .catch(error => {
                    console.error("Erreur lors du chargement des détails de la tâche:", error);
                    // Masquer le loader et afficher l'erreur
                    document.getElementById('task-description-loader').style.display = 'none';
                    document.getElementById('task-description').style.display = 'block';
                    document.getElementById('task-description').textContent = "Erreur lors du chargement de la description";
                    document.getElementById('task-error-container').style.display = 'flex';
                    const errorMessageElement = document.querySelector('#task-error-container .error-message');
                    if (errorMessageElement) {
                        errorMessageElement.textContent = "Erreur de connexion";
                    }
                });
        } else {
            console.error("Le modal n'a pas été trouvé dans le DOM");
        }
    }
}

// Fonction pour mettre à jour le statut d'une tâche
function updateTaskStatus(e) {
    const taskId = this.getAttribute('data-task-id');
    const newStatus = this.getAttribute('data-status');
    
    if (!taskId) {
        console.error("ID de tâche manquant");
        alert("Erreur: Impossible d'identifier la tâche");
        return;
    }
    
    // Vérifier si la fonction startProcessingEffect existe (intégration futuriste)
    const hasFuturisticEffects = typeof startProcessingEffect === 'function';
    
    // Afficher un spinner pendant le traitement
    const originalContent = this.innerHTML;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
    this.disabled = true;
    
    // Appliquer l'effet de traitement futuriste si disponible
    let processingPromise = Promise.resolve();
    if (hasFuturisticEffects) {
        processingPromise = startProcessingEffect('taskDetailsModal');
    }
    
    // Attendre que l'effet soit terminé avant de continuer
    processingPromise.then(() => {
        // Envoyer la requête pour mettre à jour le statut
        return fetch('ajax/update_tache_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${taskId}&statut=${newStatus}`
        });
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Afficher l'effet de succès si disponible
            if (hasFuturisticEffects && typeof showSuccessEffect === 'function') {
                showSuccessEffect(this);
            }
            
            // Afficher une notification de succès
            setTimeout(() => {
                alert(`Statut de la tâche mis à jour avec succès.`);
                
                // Fermer le modal
                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('taskDetailsModal'));
                if (modalInstance) modalInstance.hide();
                
                // Recharger la page pour afficher les changements
                window.location.reload();
            }, hasFuturisticEffects ? 1000 : 0);
        } else {
            // Effet de secousse si disponible
            if (hasFuturisticEffects && typeof shakeModal === 'function') {
                shakeModal('taskDetailsModal');
            }
            alert(data.message || "Erreur lors de la mise à jour du statut de la tâche");
            // Rétablir le contenu original du bouton
            this.innerHTML = originalContent;
            this.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        // Effet de secousse si disponible
        if (hasFuturisticEffects && typeof shakeModal === 'function') {
            shakeModal('taskDetailsModal');
        }
        alert("Erreur lors de la communication avec le serveur. Veuillez réessayer.");
        // Rétablir le contenu original du bouton
        this.innerHTML = originalContent;
        this.disabled = false;
    });
}

// Fonction pour afficher les pièces jointes
function displayAttachments(attachments) {
    const attachmentsList = document.getElementById('task-attachments-list');
    if (!attachmentsList) return;
    
    // Vider la liste existante
    attachmentsList.innerHTML = '';
    
    attachments.forEach(attachment => {
        const attachmentItem = document.createElement('div');
        attachmentItem.className = 'attachment-item d-flex align-items-center p-2 mb-2 border rounded';
        
        // Déterminer l'icône selon le type de fichier
        let iconClass = 'fas fa-file';
        let iconColor = '#6c757d';
        
        const fileExtension = attachment.file_type.toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
            iconClass = 'fas fa-image';
            iconColor = '#28a745';
        } else if (['pdf'].includes(fileExtension)) {
            iconClass = 'fas fa-file-pdf';
            iconColor = '#dc3545';
        } else if (['doc', 'docx'].includes(fileExtension)) {
            iconClass = 'fas fa-file-word';
            iconColor = '#2b579a';
        } else if (['xlsx', 'xls'].includes(fileExtension)) {
            iconClass = 'fas fa-file-excel';
            iconColor = '#217346';
        } else if (['zip', 'rar'].includes(fileExtension)) {
            iconClass = 'fas fa-file-archive';
            iconColor = '#ffc107';
        }
        
        // Formater la taille du fichier
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        attachmentItem.innerHTML = `
            <div class="attachment-icon me-3" style="color: ${iconColor}; font-size: 1.5em;">
                <i class="${iconClass}"></i>
            </div>
            <div class="attachment-info flex-grow-1">
                <div class="attachment-name fw-medium">${attachment.file_name}</div>
                <div class="attachment-size text-muted small">${formatFileSize(attachment.file_size)}</div>
            </div>
            <div class="attachment-actions">
                <a href="download_attachment.php?id=${attachment.id}" 
                   class="btn btn-sm btn-outline-primary" 
                   title="Télécharger">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        `;
        
        attachmentsList.appendChild(attachmentItem);
    });
}