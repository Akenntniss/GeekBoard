/**
 * JavaScript pour le système de pointage Clock-In/Clock-Out
 * Version améliorée avec système de créneaux horaires
 */

class TimeTracking {
    constructor() {
        this.apiUrl = 'time_tracking_api_with_slots.php';
        this.updateInterval = null;
        this.currentStatus = {
            is_clocked_in: false,
            is_on_break: false,
            current_session: null
        };
        
        // Initialiser le système
        this.init();
    }
    
    async init() {
        await this.getCurrentStatus();
        this.updateUI();
        this.startAutoUpdate();
        this.bindEvents();
    }
    
    async getCurrentStatus() {
        try {
            const response = await fetch(`${this.apiUrl}?action=get_status`);
            const result = await response.json();
            
            if (result.success) {
                this.currentStatus = result.data;
                return result.data;
            } else {
                console.error('Erreur lors de la récupération du statut:', result.message);
                return null;
            }
        } catch (error) {
            console.error('Erreur de connexion:', error);
            return null;
        }
    }
    
    async clockIn() {
        try {
            // Obtenir la géolocalisation si possible
            let location = null;
            if (navigator.geolocation) {
                try {
                    const position = await this.getCurrentPosition();
                    location = `${position.coords.latitude},${position.coords.longitude}`;
                } catch (geoError) {
                    console.warn('Géolocalisation non disponible:', geoError);
                }
            }
            
            const formData = new FormData();
            formData.append('action', 'clock_in');
            if (location) {
                formData.append('location', location);
            }
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Afficher un message personnalisé selon l'approbation
                let message = '✅ ' + result.message;
                let alertClass = 'success';
                
                if (!result.data.auto_approved) {
                    message = '⚠️ Pointage enregistré - En attente d\'approbation\n';
                    if (result.data.approval_reason) {
                        message += `Raison: ${result.data.approval_reason}`;
                    }
                    alertClass = 'warning';
                }
                
                this.showNotification(message, alertClass);
                await this.getCurrentStatus();
                this.updateUI();
                
                // Dispatch event pour mettre à jour d'autres composants
                document.dispatchEvent(new CustomEvent('timeTrackingStatusUpdated', {
                    detail: this.currentStatus
                }));
                
            } else {
                this.showNotification('❌ ' + result.message, 'danger');
            }
        } catch (error) {
            console.error('Erreur lors du clock-in:', error);
            this.showNotification('❌ Erreur de connexion lors du pointage d\'entrée', 'danger');
        }
    }
    
    async clockOut() {
        try {
            // Confirmer avec l'utilisateur
            if (!confirm('Êtes-vous sûr de vouloir terminer votre session de travail ?')) {
                return;
            }
            
            // Obtenir la géolocalisation si possible
            let location = null;
            if (navigator.geolocation) {
                try {
                    const position = await this.getCurrentPosition();
                    location = `${position.coords.latitude},${position.coords.longitude}`;
                } catch (geoError) {
                    console.warn('Géolocalisation non disponible:', geoError);
                }
            }
            
            const formData = new FormData();
            formData.append('action', 'clock_out');
            if (location) {
                formData.append('location', location);
            }
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Afficher un message personnalisé selon l'approbation
                let message = '✅ ' + result.message;
                let alertClass = 'success';
                
                if (!result.data.auto_approved) {
                    message = '⚠️ Pointage de sortie enregistré - En attente d\'approbation\n';
                    if (result.data.approval_reason) {
                        message += `Raison: ${result.data.approval_reason}`;
                    }
                    alertClass = 'warning';
                } else {
                    message += `\n⏱️ Durée travaillée: ${result.data.work_duration}h`;
                }
                
                this.showNotification(message, alertClass);
                await this.getCurrentStatus();
                this.updateUI();
                
                // Dispatch event pour mettre à jour d'autres composants
                document.dispatchEvent(new CustomEvent('timeTrackingStatusUpdated', {
                    detail: this.currentStatus
                }));
                
            } else {
                this.showNotification('❌ ' + result.message, 'danger');
            }
        } catch (error) {
            console.error('Erreur lors du clock-out:', error);
            this.showNotification('❌ Erreur de connexion lors du pointage de sortie', 'danger');
        }
    }
    
    async startBreak() {
        try {
            const formData = new FormData();
            formData.append('action', 'start_break');
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('☕ Pause commencée', 'info');
                await this.getCurrentStatus();
                this.updateUI();
                
                // Dispatch event
                document.dispatchEvent(new CustomEvent('timeTrackingStatusUpdated', {
                    detail: this.currentStatus
                }));
            } else {
                this.showNotification('❌ ' + result.message, 'danger');
            }
        } catch (error) {
            console.error('Erreur lors du début de pause:', error);
            this.showNotification('❌ Erreur lors du début de pause', 'danger');
        }
    }
    
    async endBreak() {
        try {
            const formData = new FormData();
            formData.append('action', 'end_break');
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                const breakDuration = result.data.break_duration || 0;
                this.showNotification(`✅ Pause terminée (${breakDuration.toFixed(1)} min)`, 'success');
                await this.getCurrentStatus();
                this.updateUI();
                
                // Dispatch event
                document.dispatchEvent(new CustomEvent('timeTrackingStatusUpdated', {
                    detail: this.currentStatus
                }));
            } else {
                this.showNotification('❌ ' + result.message, 'danger');
            }
        } catch (error) {
            console.error('Erreur lors de la fin de pause:', error);
            this.showNotification('❌ Erreur lors de la fin de pause', 'danger');
        }
    }
    
    updateUI() {
        const status = this.currentStatus;
        
        // Mettre à jour les boutons principaux
        this.updateMainButtons(status);
        
        // Mettre à jour les boutons du modal
        this.updateModalButtons(status);
        
        // Mettre à jour l'affichage du statut
        this.updateStatusDisplay(status);
        
        // Mettre à jour les boutons mobiles
        this.updateMobileButtons(status);
    }
    
    updateMainButtons(status) {
        const clockInBtn = document.getElementById('clock-in-btn');
        const clockOutBtn = document.getElementById('clock-out-btn');
        const breakBtn = document.getElementById('break-btn');
        
        if (clockInBtn && clockOutBtn) {
            if (status.is_clocked_in) {
                clockInBtn.style.display = 'none';
                clockOutBtn.style.display = 'inline-block';
                
                if (breakBtn) {
                    breakBtn.style.display = 'inline-block';
                    if (status.is_on_break) {
                        breakBtn.innerHTML = '<i class="fas fa-play"></i> Reprendre';
                        breakBtn.className = 'btn btn-success btn-sm';
                        breakBtn.onclick = () => this.endBreak();
                    } else {
                        breakBtn.innerHTML = '<i class="fas fa-pause"></i> Pause';
                        breakBtn.className = 'btn btn-warning btn-sm';
                        breakBtn.onclick = () => this.startBreak();
                    }
                }
            } else {
                clockInBtn.style.display = 'inline-block';
                clockOutBtn.style.display = 'none';
                if (breakBtn) {
                    breakBtn.style.display = 'none';
                }
            }
        }
    }
    
    updateModalButtons(status) {
        const modalClockInBtn = document.getElementById('modal-clock-in-btn');
        const modalClockOutBtn = document.getElementById('modal-clock-out-btn');
        
        if (modalClockInBtn && modalClockOutBtn) {
            if (status.is_clocked_in) {
                modalClockInBtn.style.display = 'none';
                modalClockOutBtn.style.display = 'inline-block';
            } else {
                modalClockInBtn.style.display = 'inline-block';
                modalClockOutBtn.style.display = 'none';
            }
        }
    }
    
    updateStatusDisplay(status) {
        const statusElements = document.querySelectorAll('[id*="time-status"], [id*="status-display"]');
        
        statusElements.forEach(element => {
            if (status.is_clocked_in) {
                if (status.is_on_break) {
                    element.innerHTML = '<span class="text-warning"><i class="fas fa-pause"></i> En pause</span>';
                } else {
                    const duration = this.formatDuration(status.current_duration || 0);
                    element.innerHTML = `<span class="text-success"><i class="fas fa-clock"></i> Au travail (${duration})</span>`;
                }
            } else {
                element.innerHTML = '<span class="text-muted"><i class="fas fa-home"></i> Hors service</span>';
            }
        });
    }
    
    updateMobileButtons(status) {
        const mobileClockButton = document.getElementById('mobile-clock-button');
        const mobileBreakButton = document.getElementById('mobile-break-button');
        
        if (mobileClockButton) {
            if (status.is_clocked_in) {
                mobileClockButton.innerHTML = '<i class="fas fa-sign-out-alt"></i> Clock-Out';
                mobileClockButton.className = 'btn btn-danger btn-sm';
                mobileClockButton.onclick = () => this.clockOut();
            } else {
                mobileClockButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> Clock-In';
                mobileClockButton.className = 'btn btn-success btn-sm';
                mobileClockButton.onclick = () => this.clockIn();
            }
        }
        
        if (mobileBreakButton) {
            if (status.is_clocked_in) {
                mobileBreakButton.style.display = 'inline-block';
                if (status.is_on_break) {
                    mobileBreakButton.innerHTML = '<i class="fas fa-play"></i> Reprendre';
                    mobileBreakButton.className = 'btn btn-success btn-sm';
                    mobileBreakButton.onclick = () => this.endBreak();
                } else {
                    mobileBreakButton.innerHTML = '<i class="fas fa-pause"></i> Pause';
                    mobileBreakButton.className = 'btn btn-warning btn-sm';
                    mobileBreakButton.onclick = () => this.startBreak();
                }
            } else {
                mobileBreakButton.style.display = 'none';
            }
        }
    }
    
    formatDuration(hours) {
        const h = Math.floor(hours);
        const m = Math.floor((hours - h) * 60);
        return `${h}h${m.toString().padStart(2, '0')}`;
    }
    
    showNotification(message, type = 'info') {
        // Utiliser les notifications natives du navigateur si disponibles
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('GeekBoard - Pointage', {
                body: message.replace(/[✅❌⚠️☕]/g, '').trim(),
                icon: '/assets/images/logo/AppIcons_lightMode/appstore.png'
            });
        }
        
        // Affichage dans la console pour debug
        console.log(`TimeTracking [${type}]:`, message);
        
        // Affichage sous forme d'alerte si pas d'autre système de notification
        if (type === 'danger' || message.includes('❌')) {
            alert(message);
        } else if (type === 'warning' || message.includes('⚠️')) {
            alert(message);
        } else {
            // Pour les succès, on peut utiliser un simple log ou une alerte discrète
            console.info(message);
        }
        
        // Essayer d'utiliser le système de toast si disponible
        if (typeof showToast === 'function') {
            showToast(message, type);
        }
    }
    
    startAutoUpdate() {
        // Mettre à jour le statut toutes les 30 secondes
        this.updateInterval = setInterval(async () => {
            await this.getCurrentStatus();
            this.updateUI();
        }, 30000);
    }
    
    stopAutoUpdate() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }
    
    bindEvents() {
        // Bind keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl+Shift+I pour Clock-In
            if (e.ctrlKey && e.shiftKey && e.key === 'I') {
                e.preventDefault();
                if (!this.currentStatus.is_clocked_in) {
                    this.clockIn();
                }
            }
            
            // Ctrl+Shift+O pour Clock-Out
            if (e.ctrlKey && e.shiftKey && e.key === 'O') {
                e.preventDefault();
                if (this.currentStatus.is_clocked_in) {
                    this.clockOut();
                }
            }
        });
        
        // Avertir avant fermeture si l'utilisateur est pointé
        window.addEventListener('beforeunload', (e) => {
            if (this.currentStatus.is_clocked_in) {
                e.preventDefault();
                e.returnValue = 'Vous êtes actuellement pointé. Êtes-vous sûr de vouloir quitter ?';
                return e.returnValue;
            }
        });
        
        // Demander la permission pour les notifications
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
    
    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 300000
            });
        });
    }
}

// Initialiser le système de pointage
let timeTracking;
document.addEventListener('DOMContentLoaded', () => {
    timeTracking = new TimeTracking();
});

// Fonctions globales pour compatibilité
function safeClockIn() {
    if (timeTracking) {
        timeTracking.clockIn();
    }
}

function safeClockOut() {
    if (timeTracking) {
        timeTracking.clockOut();
    }
}

function safeStartBreak() {
    if (timeTracking) {
        timeTracking.startBreak();
    }
}

function safeEndBreak() {
    if (timeTracking) {
        timeTracking.endBreak();
    }
}
