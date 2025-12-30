// Variables globales pour le modal d'activité
let currentActivityUserId = null;
let currentActivityEmployeeName = null;
let activityDatePicker = null;

// Fonction globale pour ouvrir le modal d'activité employé
window.openEmployeeActivityModal = function (userId, employeeName) {
    console.log('Real openEmployeeActivityModal executing for:', userId, employeeName);
    currentActivityUserId = userId;
    currentActivityEmployeeName = employeeName;

    // Vérifier si Bootstrap est disponible
    if (typeof bootstrap === 'undefined') {
        alert('Erreur: Bootstrap n\'est pas chargé.');
        return;
    }

    try {
        const modalElement = document.getElementById('employeeActivityModal');
        if (!modalElement) {
            console.error('Modal employeeActivityModal not found!');
            return;
        }

        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        // Initialiser Flatpickr si ce n'est pas déjà fait
        if (!activityDatePicker) {
            activityDatePicker = flatpickr("#activityDateInput", {
                mode: "range",
                dateFormat: "Y-m-d",
                locale: "fr",
                defaultDate: [new Date(), new Date()],
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        const startDate = instance.formatDate(selectedDates[0], "Y-m-d");
                        const endDate = instance.formatDate(selectedDates[1], "Y-m-d");
                        loadEmployeeActivity(currentActivityUserId, currentActivityEmployeeName, startDate, endDate);
                    }
                }
            });
        }

        // Réinitialiser à aujourd'hui à l'ouverture
        const today = new Date();
        activityDatePicker.setDate([today, today]);

        // Charger les données pour aujourd'hui
        const todayStr = today.toISOString().split('T')[0];
        loadEmployeeActivity(userId, employeeName, todayStr, todayStr);

    } catch (error) {
        console.error('Erreur modal:', error);
    }
};

// Fonction pour changer la date via les boutons
window.changeActivityDate = function (days) {
    if (!activityDatePicker || activityDatePicker.selectedDates.length === 0) return;

    const currentStart = activityDatePicker.selectedDates[0];
    const currentEnd = activityDatePicker.selectedDates[1] || currentStart;

    const newStart = new Date(currentStart);
    newStart.setDate(newStart.getDate() + days);

    const newEnd = new Date(currentEnd);
    newEnd.setDate(newEnd.getDate() + days);

    activityDatePicker.setDate([newStart, newEnd]);

    const startStr = activityDatePicker.formatDate(newStart, "Y-m-d");
    const endStr = activityDatePicker.formatDate(newEnd, "Y-m-d");

    loadEmployeeActivity(currentActivityUserId, currentActivityEmployeeName, startStr, endStr);
};

function loadEmployeeActivity(userId, employeeName, startDate, endDate) {
    document.getElementById('activityLoadingSpinner').style.display = 'block';
    document.getElementById('activityContent').style.display = 'none';
    document.getElementById('activityError').style.display = 'none';
    document.getElementById('employeeName').textContent = employeeName;

    fetch(`/ajax/get_employee_daily_activity.php?user_id=${userId}&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.message);

            document.getElementById('activityLoadingSpinner').style.display = 'none';
            document.getElementById('activityContent').style.display = 'block';

            const timeline = document.getElementById('activityTimeline');
            const noActivityMsg = document.getElementById('noActivityMessage');

            if (data.logs.length === 0) {
                timeline.innerHTML = '';
                noActivityMsg.style.display = 'block';
            } else {
                noActivityMsg.style.display = 'none';
                const groupedLogs = groupLogsByRepair(data.logs);
                document.getElementById('activityCount').textContent = groupedLogs.length;
                timeline.innerHTML = groupedLogs.map(group => createGroupedTimelineItem(group)).join('');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('activityLoadingSpinner').style.display = 'none';
            document.getElementById('activityError').style.display = 'block';
            document.getElementById('activityErrorMessage').textContent = error.message;
        });
}

function groupLogsByRepair(logs) {
    const groups = [];
    let currentGroup = null;

    logs.forEach(log => {
        if (log.log_type === 'task') {
            if (currentGroup && currentGroup.log_type === 'task' && currentGroup.task_id === log.task_id) {
                currentGroup.logs.push(log);
            } else {
                currentGroup = {
                    log_type: 'task',
                    task_id: log.task_id,
                    task_title: log.task_title,
                    task_description: log.task_description,
                    logs: [log]
                };
                groups.push(currentGroup);
            }
        } else if (log.log_type === 'time_tracking') {
            // Time tracking entries are standalone, not grouped
            currentGroup = {
                log_type: 'time_tracking',
                tracking_id: log.id,
                clock_in: log.clock_in,
                clock_out: log.clock_out,
                break_start: log.break_start,
                break_end: log.break_end,
                total_hours: log.total_hours,
                work_duration: log.work_duration,
                break_duration: log.break_duration,
                status: log.status,
                logs: [log]
            };
            groups.push(currentGroup);
        } else {
            if (currentGroup && currentGroup.log_type === 'repair' && currentGroup.reparation_id === log.reparation_id) {
                currentGroup.logs.push(log);
            } else {
                currentGroup = {
                    log_type: 'repair',
                    reparation_id: log.reparation_id,
                    repair_model: log.repair_model,
                    repair_problem: log.repair_problem,
                    client: log.client,
                    logs: [log]
                };
                groups.push(currentGroup);
            }
        }
    });

    return groups;
}

function createGroupedTimelineItem(group) {
    if (group.log_type === 'task') {
        const internalTimeline = group.logs.map(log => {
            const badgeClass = log.action_type === 'start' ? 'bg-success' : 'bg-danger';
            const icon = log.action_type === 'start' ? 'fas fa-play' : 'fas fa-stop';

            return `
                <div class="d-flex mb-3 position-relative">
                    <div class="me-3 d-flex flex-column align-items-center" style="width: 24px;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white ${badgeClass}" style="width: 24px; height: 24px; font-size: 0.7rem;">
                            <i class="${icon}"></i>
                        </div>
                        ${group.logs.indexOf(log) !== group.logs.length - 1 ? '<div class="flex-grow-1 bg-light" style="width: 2px; margin-top: 4px;"></div>' : ''}
                    </div>
                    <div class="flex-grow-1 pb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">${log.action_label}</span>
                            <span class="text-muted small">${log.time}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        return `
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content p-0 overflow-hidden">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                <span class="text-dark fw-bold">📋 Tâche #${group.task_id}</span>
                                <span class="ms-2 badge bg-primary text-white fw-normal">
                                    <i class="fas fa-tasks me-1"></i> Tâche
                                </span>
                            </h6>
                            <div class="small text-dark fw-semibold mb-1">
                                ${group.task_title || 'Sans titre'}
                            </div>
                            ${group.task_description ? `
                            <div class="small text-muted" title="${group.task_description}">
                                <i class="fas fa-info-circle me-1"></i> 
                                ${group.task_description.length > 60 ? group.task_description.substring(0, 60) + '...' : group.task_description}
                            </div>
                            ` : ''}
                        </div>
                        <div class="text-end">
                            <span class="badge bg-white text-dark border shadow-sm">
                                ${group.logs.length} action${group.logs.length > 1 ? 's' : ''}
                            </span>
                        </div>
                    </div>
                    <div class="p-4">
                        ${internalTimeline}
                    </div>
                </div>
            </div>
        `;
    } else if (group.log_type === 'time_tracking') {
        // Rendu pour les pointages
        const clockInTime = group.clock_in ? new Date(group.clock_in).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '-';
        const clockOutTime = group.clock_out ? new Date(group.clock_out).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : 'En cours';
        const workDuration = group.work_duration ? parseFloat(group.work_duration).toFixed(2) + 'h' : '-';
        const breakDuration = group.break_duration ? parseFloat(group.break_duration).toFixed(2) + 'h' : '-';

        const statusBadge = group.status === 'completed' ? 'bg-success' : (group.status === 'break' ? 'bg-warning' : 'bg-primary');
        const statusLabel = group.status === 'completed' ? 'Terminé' : (group.status === 'break' ? 'En pause' : 'Actif');

        return `
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content p-0 overflow-hidden">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                <span class="text-dark fw-bold">🕐 Pointage</span>
                                <span class="ms-2 badge ${statusBadge} text-white fw-normal">
                                    ${statusLabel}
                                </span>
                            </h6>
                            <div class="small text-dark mb-1">
                                <i class="fas fa-sign-in-alt me-1 text-success"></i> ${clockInTime}
                                <i class="fas fa-sign-out-alt ms-3 me-1 text-danger"></i> ${clockOutTime}
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Travail: ${workDuration}</div>
                            ${group.break_duration > 0 ? `<div class="small text-muted">Pause: ${breakDuration}</div>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else {
        // Rendu pour les réparations (code existant)
        const internalTimeline = group.logs.map(log => {
            let badgeClass = 'bg-secondary';
            let icon = 'fas fa-circle';

            switch (log.action_type) {
                case 'demarrage': badgeClass = 'bg-success'; icon = 'fas fa-play'; break;
                case 'terminer': badgeClass = 'bg-success'; icon = 'fas fa-check'; break;
                case 'changement_statut': badgeClass = 'bg-info text-dark'; icon = 'fas fa-sync-alt'; break;
                case 'ajout_note': badgeClass = 'bg-warning text-dark'; icon = 'fas fa-sticky-note'; break;
            }

            let content = '';
            if (log.statut_avant || log.statut_apres) {
                content += `
                    <div class="mt-2 small text-dark">
                        ${log.statut_avant ? formatStatut(log.statut_avant) : '...'} 
                        <i class="fas fa-arrow-right mx-1 text-muted"></i> 
                        <strong>${log.statut_apres ? formatStatut(log.statut_apres) : '...'}</strong>
                    </div>`;
            }
            if (log.details) {
                content += `<div class="mt-2 p-2 bg-light rounded small border-start border-3 border-warning text-dark">${log.details}</div>`;
            }

            return `
                <div class="d-flex mb-3 position-relative">
                    <div class="me-3 d-flex flex-column align-items-center" style="width: 24px;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white ${badgeClass}" style="width: 24px; height: 24px; font-size: 0.7rem;">
                            <i class="${icon}"></i>
                        </div>
                        ${group.logs.indexOf(log) !== group.logs.length - 1 ? '<div class="flex-grow-1 bg-light" style="width: 2px; margin-top: 4px;"></div>' : ''}
                    </div>
                    <div class="flex-grow-1 pb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">${log.action_label}</span>
                            <span class="text-muted small">${log.time}</span>
                        </div>
                        ${content}
                    </div>
                </div>
            `;
        }).join('');

        return `
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content p-0 overflow-hidden">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                <a href="?page=reparations&open_modal=${group.reparation_id}&view=cards" target="_blank" class="text-decoration-none text-dark fw-bold">
                                    Réparation #${group.reparation_id}
                                </a>
                                <span class="ms-2 badge bg-light text-dark border fw-normal">
                                    <i class="far fa-user me-1"></i> ${group.client}
                                </span>
                            </h6>
                            <div class="small text-muted mb-1">
                                <i class="fas fa-mobile-alt me-1"></i> ${group.repair_model}${group.repair_problem ? ' - ' + group.repair_problem : ''}
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-white text-dark border shadow-sm">
                                ${group.logs.length} action${group.logs.length > 1 ? 's' : ''}
                            </span>
                        </div>
                    </div>
                    <div class="p-4">
                        ${internalTimeline}
                    </div>
                </div>
            </div>
        `;
    }
}

function formatStatut(statut) {
    const statusMap = {
        'nouvelle_intervention': '🆕 Nouvelle intervention',
        'en_cours': '🔄 En cours',
        'diagnostic': '🔍 Diagnostic',
        'attente_piece': '📦 Attente pièce',
        'reparation_en_cours': '🔧 Réparation en cours',
        'reparation_effectue': '✅ Réparation effectuée',
        'restitue': '✅ Restitué'
    };
    return statusMap[statut] || statut;
}

console.log('✅ employee-activity-modal.js loaded successfully');
