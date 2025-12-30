// Fonction pour ouvrir le modal d'infos rapides d'une tâche
window.openTaskQuickInfo = function (taskId) {
    if (!taskId) {
        console.error('Task ID is required');
        return;
    }

    // Ouvrir le modal
    const modalElement = document.getElementById('taskQuickInfoModal');
    if (!modalElement) {
        console.error('Modal taskQuickInfoModal not found');
        return;
    }

    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    // Afficher le loading
    document.getElementById('taskQuickInfoLoading').style.display = 'block';
    document.getElementById('taskQuickInfoContent').style.display = 'none';
    document.getElementById('taskQuickInfoError').style.display = 'none';

    // Charger les données
    fetch(`/ajax/get_task_quick_info.php?id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);

            // Cacher le loading
            document.getElementById('taskQuickInfoLoading').style.display = 'none';
            document.getElementById('taskQuickInfoContent').style.display = 'block';

            // Remplir les données
            document.getElementById('taskTitle').textContent = data.data.title || '-';
            document.getElementById('taskDescription').textContent = data.data.description || 'Aucune description';

            // Statut
            const statusBadge = document.getElementById('taskStatus');
            statusBadge.textContent = data.data.status_label;
            statusBadge.className = `badge bg-${data.data.status_color}`;

            // Priorité
            const priorityBadge = document.getElementById('taskPriority');
            priorityBadge.textContent = data.data.priority_label;
            priorityBadge.className = `badge bg-${data.data.priority_color}`;

            // Assigné à
            document.getElementById('taskAssignedTo').textContent = data.data.assigned_to || 'Non assigné';

            // Créé par
            document.getElementById('taskCreatedBy').textContent = data.data.created_by || '-';

            // Date d'échéance
            if (data.data.due_date) {
                const dueDate = new Date(data.data.due_date);
                document.getElementById('taskDueDate').textContent = dueDate.toLocaleDateString('fr-FR');
            } else {
                document.getElementById('taskDueDate').textContent = 'Aucune échéance';
            }

            // Date de création
            if (data.data.created_at) {
                const createdAt = new Date(data.data.created_at);
                document.getElementById('taskCreatedAt').textContent = createdAt.toLocaleDateString('fr-FR') + ' ' + createdAt.toLocaleTimeString('fr-FR');
            }
        })
        .catch(error => {
            console.error('Error loading task info:', error);
            document.getElementById('taskQuickInfoLoading').style.display = 'none';
            document.getElementById('taskQuickInfoError').style.display = 'block';
            document.getElementById('taskQuickInfoErrorMessage').textContent = error.message;
        });
};

// Wrapper pour compatibilité avec l'ancien nom
window.afficherDetailsTache = function (event, taskId) {
    if (event) event.stopPropagation();
    openTaskQuickInfo(taskId);
};

console.log('✅ task-quick-info.js loaded successfully');
