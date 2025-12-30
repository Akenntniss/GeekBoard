/**
 * JavaScript pour le système de pointage Clock-In/Clock-Out
 * Compatible avec la structure GeekBoard existante
 */

class TimeTracking {
    constructor() {
        this.apiUrl = 'time_tracking_api.php';
        this.updateInterval = null;
        this.currentStatus = {
            is_clocked_in: false
            is_on_break: false
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
                } catch (e) {
                    console.log('Géolocalisation non disponible');
                }
            }
            
            const formData = new FormData();
            formData.append('action', 'clock_in');
            if (location) formData.append('location', location);
            
            const response = await fetch(this.apiUrl, {
                method: 'POST'
                body: formData
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('✅ Pointage d\'arrivée enregistré !', 'success');
                await this.getCurrentStatus();
                this.updateUI();
                this.triggerUIUpdate();
            } else {
                this.showNotification('❌ ' + result.message, 'error');
            }
            
            return result;
        } catch (error) {
            console.error('Erreur clock-in:', error);
            this.showNotification('❌ Erreur de connexion', 'error');
        }
    }
    
    async clockOut() {
        try {
            const formData = new FormData();
            formData.append('action', 'clock_out');
            
            const response = await fetch(this.apiUrl, {
                method: 'POST'
                body: formData
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(`✅ Pointage de sortie enregistré ! Temps travaillé: ${result.data.work_hours}h`, 'success');
                await this.getCurrentStatus();
                this.updateUI();
                this.triggerUIUpdate();
            } else {
                this.showNotification('❌ ' + result.message, 'error');
            }
            
            return result;
        } catch (error) {
            console.error('Erreur clock-out:', error);
            this.showNotification('❌ Erreur de connexion', 'error');
        }
    }
    
    async startBreak() {
        try {
            const formData = new FormData();
            formData.append('action', 'start_break');
            
            const response = await fetch(this.apiUrl, {
                method: 'POST'
                body: formData
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('☕ Pause commencée', 'info');
                await this.getCurrentStatus();
                this.updateUI();
            } else {
                this.showNotification('❌ ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Erreur start break:', error);
            this.showNotification('❌ Erreur de connexion', 'error');
        }
    }
    
    async endBreak() {
        try {
            const formData = new FormData();
            formData.append('action', 'end_break');
            
            const response = await fetch(this.apiUrl, {
                method: 'POST'
                body: formData
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(`💼 Reprise du travail (Pause: ${result.data.break_duration}h)`, 'info');
                await this.getCurrentStatus();
                this.updateUI();
            } else {
                this.showNotification('❌ ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Erreur end break:', error);
            this.showNotification('❌ Erreur de connexion', 'error');
        }
    }
    
    updateUI() {
        // Mettre à jour les boutons de la navbar
        const clockButton = document.getElementById('clock-button');
        const breakButton = document.getElementById('break-button');
        const statusDisplay = document.getElementById('time-status-display');
        
        if (clockButton) {
            if (this.currentStatus.is_clocked_in) {
                clockButton.innerHTML = '<i class="fas fa-sign-out-alt"></i> Clock-Out';
                clockButton.className = 'btn btn-danger btn-sm mx-1';
                clockButton.onclick = () => this.clockOut();
            } else {
                clockButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> Clock-In';
                clockButton.className = 'btn btn-success btn-sm mx-1';
                clockButton.onclick = () => this.clockIn();
            }
        }
        
        if (breakButton) {
            if (this.currentStatus.is_clocked_in) {
                breakButton.style.display = 'inline-block';
                if (this.currentStatus.is_on_break) {
                    breakButton.innerHTML = '<i class="fas fa-play"></i> Reprendre';
                    breakButton.className = 'btn btn-warning btn-sm mx-1';
                    breakButton.onclick = () => this.endBreak();
                } else {
                    breakButton.innerHTML = '<i class="fas fa-pause"></i> Pause';
                    breakButton.className = 'btn btn-outline-secondary btn-sm mx-1';
                    breakButton.onclick = () => this.startBreak();
                }
            } else {
                breakButton.style.display = 'none';
            }
        }
        
        // Mettre à jour l'affichage du statut
        if (statusDisplay) {
            this.updateStatusDisplay(statusDisplay);
        }
    }
    
    updateStatusDisplay(element) {
        if (!this.currentStatus.is_clocked_in) {
            element.innerHTML = '<small class="text-muted">Non pointé</small>';
            return;
        }
        
        const session = this.currentStatus.current_session;
        if (!session) return;
        
        const clockIn = new Date(session.clock_in);
        const now = new Date();
        const workDuration = this.currentStatus.work_duration;
        const breakDuration = this.currentStatus.break_duration;
        
        const formatTime = (hours) => {
            const h = Math.floor(hours);
            const m = Math.floor((hours - h) * 60);
            return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
        };
        
        let statusText = `
            <small class="text-success">
                <i class="fas fa-clock"></i> ${formatTime(workDuration)}
                ${this.currentStatus.is_on_break ? '<span class="text-warning">(Pause)</span>' : ''}
            </small>
        `;
        
        element.innerHTML = statusText;
    }
    
    startAutoUpdate() {
        // Mettre à jour le statut toutes les 30 secondes
        this.updateInterval = setInterval(async () => {
            if (this.currentStatus.is_clocked_in) {
                await this.getCurrentStatus();
                this.updateUI();
            }
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
        
        // Avertir avant fermeture si l'utilisateur est pointé
        window.addEventListener('beforeunload', (e) => {
            if (this.currentStatus.is_clocked_in) {
                e.preventDefault();
                e.returnValue = 'Vous êtes actuellement pointé. Êtes-vous sûr de vouloir quitter ?';
                return e.returnValue;
            }
    }
    
    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true
                timeout: 10000
                maximumAge: 300000
    }
    
    showNotification(message, type = 'info') {
        // Utiliser le système de notification existant de GeekBoard s'il existe
        if (typeof showNotification === 'function') {
            showNotification(message, type);
            return;
        }
        
        // Sinon, créer une notification simple
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;
        
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
    
    triggerUIUpdate() {
        // Déclencher un événement personnalisé pour notifier d'autres composants
        const event = new CustomEvent('timeTrackingUpdate', {
            detail: this.currentStatus
        document.dispatchEvent(event);
    }
    
    // Méthodes utilitaires pour l'administration
    async getActiveUsers() {
        try {
            const response = await fetch(`${this.apiUrl}?action=admin_get_active`);
            const result = await response.json();
            return result.success ? result.data : null;
        } catch (error) {
            console.error('Erreur get active users:', error);
            return null;
        }
    }
    
    async getWeeklyReport(userId = null) {
        try {
            const url = userId ? 
                `${this.apiUrl}?action=get_weekly_report&user_id=${userId}` : 
                `${this.apiUrl}?action=get_weekly_report`;
            
            const response = await fetch(url);
            const result = await response.json();
            return result.success ? result.data : null;
        } catch (error) {
            console.error('Erreur get weekly report:', error);
            return null;
        }
    }
}

// Ajouter les styles CSS nécessaires
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .time-tracking-status {
        display: inline-block;
        font-size: 0.9em;
    }
    
    .time-tracking-controls {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    @media (max-width: 768px) {
        .time-tracking-controls {
            flex-direction: column;
            gap: 4px;
        }
        
        .time-tracking-controls .btn {
            font-size: 0.8em;
            padding: 4px 8px;
        }
    }
`;
document.head.appendChild(style);

// Initialiser le système de pointage quand le DOM est prêt
let timeTrackingInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    // Attendre un peu pour s'assurer que la session est initialisée
    setTimeout(() => {
        timeTrackingInstance = new TimeTracking();
        
        // Rendre l'instance accessible globalement
        window.timeTracking = timeTrackingInstance;
    }, 1000);

// Export pour utilisation dans d'autres scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TimeTracking;
}
