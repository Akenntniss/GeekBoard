/**
 * JavaScript pour la gestion du calendrier de pointage avec données réelles
 */

let currentDate = new Date();
let calendarData = {};

// Charger les données du calendrier depuis l'API
function loadCalendarData() {
    const employeeId = document.getElementById('employeeFilter').value;
    const month = document.getElementById('monthFilter').value;
    const year = document.getElementById('yearFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    // Construire l'URL de l'API
    const params = new URLSearchParams({
        action: 'get_calendar_data',
        month: month,
        year: year
    });
    
    if (employeeId) {
        params.append('employee_id', employeeId);
    }
    
    if (status) {
        params.append('status', status);
    }
    
    // Afficher un indicateur de chargement
    showLoadingIndicator(true);
    
    fetch(`calendar_api.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                calendarData = data.data.calendar_data;
                generateCalendar(parseInt(year), parseInt(month) - 1);
                updateCalendarInfo(data.data);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des données:', error);
            showError('Erreur lors du chargement des données: ' + error.message);
        })
        .finally(() => {
            showLoadingIndicator(false);
        });
}

function generateCalendar(year, month) {
    currentDate = new Date(year, month, 1);
    
    // Mettre à jour le titre
    const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                       'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    document.getElementById('currentMonthYear').textContent = `${monthNames[month]} ${year}`;
    
    const grid = document.getElementById('calendarGrid');
    
    // Garder les en-têtes des jours
    const dayHeaders = grid.querySelectorAll('.calendar-day-header');
    grid.innerHTML = '';
    dayHeaders.forEach(header => grid.appendChild(header));
    
    // Calculer le premier jour du mois (lundi = 0)
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    const mondayOffset = (firstDay.getDay() + 6) % 7; // Convertir dimanche=0 en lundi=0
    startDate.setDate(firstDay.getDate() - mondayOffset);
    
    // Générer 42 jours (6 semaines)
    for (let i = 0; i < 42; i++) {
        const currentDay = new Date(startDate);
        currentDay.setDate(startDate.getDate() + i);
        
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        
        if (currentDay.getMonth() !== month) {
            dayElement.classList.add('other-month');
        }
        
        if (isToday(currentDay)) {
            dayElement.classList.add('today');
        }
        
        const dateString = formatDateForAPI(currentDay);
        
        dayElement.innerHTML = `
            <div class="day-number">${currentDay.getDate()}</div>
            <div class="day-entries">
                ${generateDayEntries(dateString)}
            </div>
        `;
        
        grid.appendChild(dayElement);
    }
}

function generateDayEntries(dateString) {
    const entries = calendarData[dateString] || [];
    
    if (entries.length === 0) {
        return '';
    }
    
    const entryHtml = [];
    
    // Grouper par période (matin/après-midi)
    const morningEntries = entries.filter(e => e.period === 'morning');
    const afternoonEntries = entries.filter(e => e.period === 'afternoon');
    
    // Afficher les entrées du matin
    if (morningEntries.length > 0) {
        const morning = morningEntries[0];
        const cssClass = getEntryClass(morning);
        const timeText = morning.start_time ? morning.start_time.substring(0, 5) : '';
        entryHtml.push(`<span class="entry-time ${cssClass}" title="${getEntryTooltip(morning)}">Matin: ${timeText}</span>`);
    }
    
    // Afficher les entrées de l'après-midi
    if (afternoonEntries.length > 0) {
        const afternoon = afternoonEntries[0];
        const cssClass = getEntryClass(afternoon);
        const timeText = afternoon.start_time ? afternoon.start_time.substring(0, 5) : '';
        entryHtml.push(`<span class="entry-time ${cssClass}" title="${getEntryTooltip(afternoon)}">A-midi: ${timeText}</span>`);
    }
    
    // Si plusieurs employés le même jour
    if (entries.length > 2) {
        entryHtml.push(`<small class="text-muted">+${entries.length - 2} autres</small>`);
    }
    
    return entryHtml.join('');
}

function getEntryClass(entry) {
    let cssClass = entry.period; // 'morning' ou 'afternoon'
    
    if (entry.approval_status === 'pending') {
        cssClass += ' pending';
    }
    
    return cssClass;
}

function getEntryTooltip(entry) {
    let tooltip = `${entry.employee}\n`;
    tooltip += `Heure: ${entry.start_time}`;
    
    if (entry.end_time) {
        tooltip += ` - ${entry.end_time}`;
    }
    
    tooltip += `\nStatut: `;
    
    switch (entry.approval_status) {
        case 'auto':
            tooltip += 'Approuvé automatiquement';
            break;
        case 'approved':
            tooltip += 'Approuvé manuellement';
            break;
        case 'pending':
            tooltip += 'En attente d\'approbation';
            break;
    }
    
    if (entry.approval_reason) {
        tooltip += `\nRaison: ${entry.approval_reason}`;
    }
    
    if (entry.work_duration) {
        tooltip += `\nDurée: ${parseFloat(entry.work_duration).toFixed(1)}h`;
    }
    
    return tooltip;
}

function formatDateForAPI(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function isToday(date) {
    const today = new Date();
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear();
}

function navigateMonth(delta) {
    currentDate.setMonth(currentDate.getMonth() + delta);
    document.getElementById('monthFilter').value = currentDate.getMonth() + 1;
    document.getElementById('yearFilter').value = currentDate.getFullYear();
    loadCalendarData();
}

function goToToday() {
    const today = new Date();
    document.getElementById('monthFilter').value = today.getMonth() + 1;
    document.getElementById('yearFilter').value = today.getFullYear();
    loadCalendarData();
}

function updateCalendarInfo(data) {
    // Mettre à jour les informations du calendrier
    console.log(`Calendrier chargé: ${data.total_entries} entrées pour ${data.month}/${data.year}`);
}

function showLoadingIndicator(show) {
    const indicator = document.getElementById('loadingIndicator');
    if (indicator) {
        indicator.style.display = show ? 'block' : 'none';
    } else if (show) {
        // Créer un indicateur de chargement si il n'existe pas
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'loadingIndicator';
        loadingDiv.className = 'text-center p-3';
        loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement du calendrier...';
        
        const calendarContainer = document.querySelector('.calendar-container');
        if (calendarContainer) {
            calendarContainer.appendChild(loadingDiv);
        }
    }
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
    
    const calendarContainer = document.querySelector('.calendar-container');
    if (calendarContainer) {
        calendarContainer.insertBefore(errorDiv, calendarContainer.firstChild);
        
        // Supprimer l'erreur après 5 secondes
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
}

// Initialiser le calendrier au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter un indicateur de chargement au HTML
    const calendarGrid = document.getElementById('calendarGrid');
    if (calendarGrid && !document.getElementById('loadingIndicator')) {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'loadingIndicator';
        loadingDiv.className = 'text-center p-3';
        loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement du calendrier...';
        loadingDiv.style.display = 'none';
        
        calendarGrid.parentNode.appendChild(loadingDiv);
    }
    
    // Charger les données initiales
    loadCalendarData();
});

// Exporter les fonctions pour pouvoir les appeler depuis le HTML
window.loadCalendarData = loadCalendarData;
window.navigateMonth = navigateMonth;
window.goToToday = goToToday;
