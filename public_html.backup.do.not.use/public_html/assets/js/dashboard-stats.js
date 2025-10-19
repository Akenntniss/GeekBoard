/**
 * DASHBOARD STATISTIQUES MODERNES
 * Gestion du modal de statistiques avec filtres et graphiques
 */

// Variables globales
let currentStatType = '';
let currentPeriod = 'day';
let currentDate = new Date().toISOString().split('T')[0];
let statsChart = null;

// Configuration des types de statistiques
const STAT_TYPES = {
    'nouvelles_reparations': {
        title: 'Nouvelles réparations',
        subtitle: 'Évolution des nouvelles réparations',
        icon: 'fas fa-plus-circle',
        color: '#667eea',
        gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
    },
    'reparations_effectuees': {
        title: 'Réparations effectuées',
        subtitle: 'Évolution des réparations terminées',
        icon: 'fas fa-wrench',
        color: '#4facfe',
        gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
    },
    'reparations_restituees': {
        title: 'Réparations restituées',
        subtitle: 'Évolution des restitutions clients',
        icon: 'fas fa-handshake',
        color: '#43e97b',
        gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'
    },
    'devis_envoyes': {
        title: 'Devis envoyés',
        subtitle: 'Évolution des devis transmis',
        icon: 'fas fa-file-invoice-dollar',
        color: '#fa709a',
        gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
    }
};

/**
 * Ouvrir le modal des statistiques
 */
function openStatsModal(statType) {
    currentStatType = statType;
    
    const config = STAT_TYPES[statType];
    if (!config) {
        console.error('Type de statistique non reconnu:', statType);
        return;
    }
    
    // Mettre à jour le titre du modal
    document.getElementById('statsModalLabel').textContent = config.title;
    document.getElementById('statsModalSubtitle').textContent = config.subtitle;
    
    // Mettre à jour l'icône
    const modalIcon = document.querySelector('.modal-icon-stats i');
    modalIcon.className = config.icon;
    
    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('statsModal'));
    modal.show();
    
    // Charger les données initiales
    loadStatsData();
}

/**
 * Changer la période
 */
function changePeriod(period) {
    currentPeriod = period;
    
    // Mettre à jour les boutons
    document.querySelectorAll('.filter-btn[data-period]').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-period="${period}"]`).classList.add('active');
    
    // Recharger les données
    loadStatsData();
}

/**
 * Changer la date spécifique
 */
function changeSpecificDate() {
    const dateInput = document.getElementById('specificDate');
    currentDate = dateInput.value;
    loadStatsData();
}

/**
 * Réinitialiser à aujourd'hui
 */
function resetToToday() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('specificDate').value = today;
    currentDate = today;
    loadStatsData();
}

/**
 * Charger les données statistiques
 */
async function loadStatsData() {
    showLoader();
    hideError();
    
    try {
        const response = await fetch('ajax/get_stats_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: currentStatType,
                period: currentPeriod,
                date: currentDate
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            updateChart(data.chartData);
            updateTable(data.tableData);
        } else {
            showError(data.message || 'Erreur lors du chargement des données');
        }
        
    } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error);
        showError('Erreur de connexion. Veuillez réessayer.');
    } finally {
        hideLoader();
    }
}

/**
 * Mettre à jour le graphique
 */
function updateChart(chartData) {
    const ctx = document.getElementById('statsChart').getContext('2d');
    const config = STAT_TYPES[currentStatType];
    
    // Détruire le graphique existant
    if (statsChart) {
        statsChart.destroy();
    }
    
    // Mettre à jour le titre du graphique
    document.getElementById('chartTitle').textContent = `${config.subtitle}`;
    
    // Configuration du graphique
    const chartConfig = {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: config.title,
                data: chartData.values,
                borderColor: config.color,
                backgroundColor: config.color + '20',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: config.color,
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: config.color,
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            return context[0].label;
                        },
                        label: function(context) {
                            return `${config.title}: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 12
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    };
    
    // Créer le nouveau graphique
    statsChart = new Chart(ctx, chartConfig);
    
    // Mettre à jour la légende
    updateLegend(config);
}

/**
 * Mettre à jour la légende du graphique
 */
function updateLegend(config) {
    const legendContainer = document.getElementById('chartLegend');
    legendContainer.innerHTML = `
        <div class="legend-item">
            <div class="legend-color" style="background: ${config.color};"></div>
            <span>${config.title}</span>
        </div>
    `;
}

/**
 * Mettre à jour le tableau moderne avec défilement
 */
function updateTable(tableData) {
    const headerContainer = document.getElementById('statsTableHeader');
    const bodyContainer = document.getElementById('statsTableBody');
    const tableContainer = document.getElementById('tableContainer');
    const scrollableContainer = document.getElementById('tableScrollable');
    
    // Vider le tableau
    headerContainer.innerHTML = '';
    bodyContainer.innerHTML = '';
    
    if (!tableData || !tableData.headers || !tableData.rows) {
        bodyContainer.innerHTML = `
            <div class="modern-table-empty">
                <div class="modern-table-empty-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="modern-table-empty-title">Aucune donnée disponible</div>
                <div class="modern-table-empty-subtitle">Les statistiques apparaîtront ici une fois les données chargées</div>
            </div>
        `;
        // Masquer les indicateurs de défilement
        updateScrollIndicators();
        return;
    }
    
    // Créer les en-têtes du tableau moderne
    tableData.headers.forEach(header => {
        const headerCell = document.createElement('div');
        headerCell.className = 'modern-table-header-cell';
        headerCell.textContent = header;
        headerContainer.appendChild(headerCell);
    });
    
    // Créer les lignes du tableau moderne
    tableData.rows.forEach((row, rowIndex) => {
        const tableRow = document.createElement('div');
        tableRow.className = 'modern-table-row';
        
        row.forEach((cell, cellIndex) => {
            const tableCell = document.createElement('div');
            tableCell.className = 'modern-table-cell';
            
            // Ajouter des classes spéciales selon le type de cellule
            if (cellIndex === 0) {
                // Première colonne = Date
                tableCell.classList.add('date-cell');
            } else if (cellIndex === 1) {
                // Deuxième colonne = Nombre
                tableCell.classList.add('number-cell');
            } else if (cellIndex === 2) {
                // Troisième colonne = Évolution
                tableCell.classList.add('evolution-cell');
                
                // Ajouter des classes selon la valeur
                const evolutionValue = parseFloat(cell);
                if (evolutionValue > 0) {
                    tableCell.classList.add('evolution-positive');
                } else if (evolutionValue < 0) {
                    tableCell.classList.add('evolution-negative');
                } else {
                    tableCell.classList.add('evolution-neutral');
                }
            }
            
            tableCell.textContent = cell;
            tableRow.appendChild(tableCell);
        });
        
        // Ajouter un délai d'animation pour chaque ligne
        tableRow.style.animationDelay = `${rowIndex * 0.05}s`;
        bodyContainer.appendChild(tableRow);
    });
    
    // Initialiser le défilement après un court délai pour laisser le DOM se mettre à jour
    setTimeout(() => {
        initializeScrolling();
    }, 100);
}

/**
 * Initialiser les fonctionnalités de défilement
 */
function initializeScrolling() {
    const scrollableContainer = document.getElementById('tableScrollable');
    const tableContainer = document.getElementById('tableContainer');
    const scrollHint = document.getElementById('scrollHint');
    
    if (!scrollableContainer || !tableContainer) return;
    
    // Vérifier si le défilement est nécessaire
    const hasScrollableContent = scrollableContainer.scrollHeight > scrollableContainer.clientHeight;
    
    if (hasScrollableContent) {
        tableContainer.classList.add('has-scroll');
        
        // Afficher le hint de défilement pendant 3 secondes
        setTimeout(() => {
            scrollHint.classList.add('visible');
        }, 500);
        
        setTimeout(() => {
            scrollHint.classList.remove('visible');
        }, 3500);
        
        // Ajouter l'événement de défilement
        scrollableContainer.addEventListener('scroll', updateScrollIndicators);
        
        // Mettre à jour les indicateurs initialement
        updateScrollIndicators();
        
        // Ajouter des événements pour les gestes tactiles (mobile)
        addTouchScrollSupport(scrollableContainer);
    } else {
        tableContainer.classList.remove('has-scroll');
    }
}

/**
 * Mettre à jour les indicateurs de défilement
 */
function updateScrollIndicators() {
    const scrollableContainer = document.getElementById('tableScrollable');
    const topIndicator = document.getElementById('scrollIndicatorTop');
    const bottomIndicator = document.getElementById('scrollIndicatorBottom');
    
    if (!scrollableContainer || !topIndicator || !bottomIndicator) return;
    
    const scrollTop = scrollableContainer.scrollTop;
    const scrollHeight = scrollableContainer.scrollHeight;
    const clientHeight = scrollableContainer.clientHeight;
    const scrollBottom = scrollHeight - scrollTop - clientHeight;
    
    // Indicateur du haut (visible si on a scrollé vers le bas)
    if (scrollTop > 10) {
        topIndicator.classList.add('visible');
    } else {
        topIndicator.classList.remove('visible');
    }
    
    // Indicateur du bas (visible s'il y a du contenu en dessous)
    if (scrollBottom > 10) {
        bottomIndicator.classList.add('visible');
    } else {
        bottomIndicator.classList.remove('visible');
    }
}

/**
 * Ajouter le support des gestes tactiles pour le défilement
 */
function addTouchScrollSupport(container) {
    let isScrolling = false;
    let startY = 0;
    let scrollStartY = 0;
    
    container.addEventListener('touchstart', (e) => {
        isScrolling = true;
        startY = e.touches[0].clientY;
        scrollStartY = container.scrollTop;
    }, { passive: true });
    
    container.addEventListener('touchmove', (e) => {
        if (!isScrolling) return;
        
        const currentY = e.touches[0].clientY;
        const deltaY = startY - currentY;
        const newScrollTop = scrollStartY + deltaY;
        
        container.scrollTop = newScrollTop;
    }, { passive: true });
    
    container.addEventListener('touchend', () => {
        isScrolling = false;
    }, { passive: true });
}

/**
 * Fonction utilitaire pour faire défiler vers une position spécifique
 */
function scrollToPosition(position) {
    const scrollableContainer = document.getElementById('tableScrollable');
    if (!scrollableContainer) return;
    
    scrollableContainer.scrollTo({
        top: position,
        behavior: 'smooth'
    });
}

/**
 * Fonction utilitaire pour faire défiler vers le haut
 */
function scrollToTop() {
    scrollToPosition(0);
}

/**
 * Fonction utilitaire pour faire défiler vers le bas
 */
function scrollToBottom() {
    const scrollableContainer = document.getElementById('tableScrollable');
    if (!scrollableContainer) return;
    
    scrollToPosition(scrollableContainer.scrollHeight);
}

/**
 * Exporter les données
 */
function exportStatsData() {
    if (!currentStatType) return;
    
    const config = STAT_TYPES[currentStatType];
    const filename = `statistiques_${currentStatType}_${currentPeriod}_${currentDate}.csv`;
    
    // Créer le lien de téléchargement
    const url = `ajax/export_stats.php?type=${currentStatType}&period=${currentPeriod}&date=${currentDate}`;
    
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.style.display = 'none';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Afficher une notification
    showNotification('Export en cours...', 'info');
}

/**
 * Afficher le loader
 */
function showLoader() {
    document.getElementById('statsLoader').style.display = 'flex';
    document.querySelector('.stats-chart-section').style.opacity = '0.5';
    document.querySelector('.stats-table-section').style.opacity = '0.5';
}

/**
 * Masquer le loader
 */
function hideLoader() {
    document.getElementById('statsLoader').style.display = 'none';
    document.querySelector('.stats-chart-section').style.opacity = '1';
    document.querySelector('.stats-table-section').style.opacity = '1';
}

/**
 * Afficher une erreur
 */
function showError(message) {
    const errorContainer = document.getElementById('statsError');
    const errorMessage = errorContainer.querySelector('.error-message');
    
    errorMessage.textContent = message;
    errorContainer.style.display = 'flex';
}

/**
 * Masquer l'erreur
 */
function hideError() {
    document.getElementById('statsError').style.display = 'none';
}

/**
 * Afficher une notification
 */
function showNotification(message, type = 'info') {
    // Créer la notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-radius: 8px;
    `;
    
    notification.innerHTML = `
        <i class="fas fa-info-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Supprimer automatiquement après 3 secondes
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

/**
 * Initialisation
 */
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si Chart.js est chargé
    if (typeof Chart === 'undefined') {
        console.error('Chart.js n\'est pas chargé. Chargement depuis CDN...');
        
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = function() {
            console.log('Chart.js chargé avec succès');
        };
        document.head.appendChild(script);
    }
    
    // Initialiser la date d'aujourd'hui
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('specificDate');
    if (dateInput) {
        dateInput.value = today;
    }
    
    console.log('Dashboard Stats initialisé');
});

// Rendre les fonctions globales pour les onclick
window.openStatsModal = openStatsModal;
window.changePeriod = changePeriod;
window.changeSpecificDate = changeSpecificDate;
window.resetToToday = resetToToday;
window.exportStatsData = exportStatsData;
