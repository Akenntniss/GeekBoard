/**
 * Missions Modern JavaScript
 * Fonctionnalités JavaScript modernes pour le système de missions GeekBoard
 */

// Configuration globale
const MissionsConfig = {
    animationDuration: 300,
    fadeInDelay: 100,
    notificationDuration: 4000,
    rippleEffectDuration: 600
};

// Utilitaires
const MissionsUtils = {
    /**
     * Crée un effet de ripple sur un élément
     */
    createRipple(element, event) {
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        const ripple = document.createElement('div');
        ripple.className = 'missions-ripple-effect';
        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            pointer-events: none;
            transform: scale(0);
            animation: ripple ${MissionsConfig.rippleEffectDuration}ms ease-out;
            left: ${x}px;
            top: ${y}px;
            width: ${size}px;
            height: ${size}px;
        `;
        
        // Ajouter l'animation ripple si elle n'existe pas
        if (!document.querySelector('#missions-ripple-keyframes')) {
            const style = document.createElement('style');
            style.id = 'missions-ripple-keyframes';
            style.textContent = `
                @keyframes ripple {
                    to { transform: scale(2); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
        
        element.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, MissionsConfig.rippleEffectDuration);
    },

    /**
     * Ajoute des animations d'entrée aux cartes
     */
    animateCards() {
        const cards = document.querySelectorAll('.mission-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * MissionsConfig.fadeInDelay}ms`;
            card.classList.add('missions-fade-in');
        });
    },

    /**
     * Formatte une date au format français
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    },

    /**
     * Calcule le pourcentage de progression
     */
    calculateProgress(completed, total) {
        return total > 0 ? Math.round((completed / total) * 100) : 0;
    },

    /**
     * Ajoute une classe CSS dynamiquement
     */
    addAnimationClasses() {
        const style = document.createElement('style');
        style.textContent = `
            .missions-fade-in {
                animation: fadeInUp 0.6s ease-out forwards;
            }
            
            .missions-ripple-effect {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                pointer-events: none;
                transform: scale(0);
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    }
};

// Gestionnaire des onglets
class MissionsTabManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeActiveTab();
    }

    bindEvents() {
        const filterBtns = document.querySelectorAll('.missions-filter-btn');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', (e) => this.handleTabClick(e));
        });
    }

    handleTabClick(e) {
        e.preventDefault();
        
        const clickedBtn = e.currentTarget;
        const tabId = clickedBtn.getAttribute('data-tab');
        
        // Effet ripple
        MissionsUtils.createRipple(clickedBtn, e);
        
        // Changer l'onglet actif
        this.switchTab(tabId);
    }

    switchTab(tabId) {
        // Retirer les classes actives
        document.querySelectorAll('.missions-filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll('.missions-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Ajouter les classes actives
        document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
        document.getElementById(tabId).classList.add('active');
        
        // Animer les nouvelles cartes
        setTimeout(() => {
            MissionsUtils.animateCards();
        }, 50);
    }

    initializeActiveTab() {
        const activeTab = document.querySelector('.missions-filter-btn.active');
        if (activeTab) {
            const tabId = activeTab.getAttribute('data-tab');
            this.switchTab(tabId);
        }
    }
}

// Gestionnaire des notifications
class NotificationManager {
    constructor() {
        this.container = this.createContainer();
    }

    createContainer() {
        const container = document.createElement('div');
        container.className = 'missions-notifications-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
        `;
        document.body.appendChild(container);
        return container;
    }

    show(message, type = 'info', duration = MissionsConfig.notificationDuration) {
        const notification = document.createElement('div');
        notification.className = `missions-notification missions-notification-${type}`;
        
        const iconMap = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        
        notification.innerHTML = `
            <i class="fas fa-${iconMap[type] || 'info-circle'} me-2"></i>
            ${message}
        `;
        
        notification.style.cssText = `
            background: var(--missions-${type === 'error' ? 'danger' : type}-color, #4361ee);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            pointer-events: auto;
            cursor: pointer;
            max-width: 350px;
            word-wrap: break-word;
        `;
        
        this.container.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Fermeture au clic
        notification.addEventListener('click', () => {
            this.remove(notification);
        });
        
        // Fermeture automatique
        setTimeout(() => {
            this.remove(notification);
        }, duration);
        
        return notification;
    }

    remove(notification) {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
}

// Gestionnaire des requêtes API
class MissionsAPIManager {
    constructor() {
        this.notifications = new NotificationManager();
    }

    async makeRequest(url, data, method = 'POST') {
        try {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            };

            if (data instanceof FormData) {
                delete options.headers['Content-Type'];
                options.body = data;
            } else if (typeof data === 'object') {
                options.body = new URLSearchParams(data).toString();
            } else {
                options.body = data;
            }

            const response = await fetch(url, options);
            const result = await response.json();
            
            return result;
        } catch (error) {
            console.error('Erreur API:', error);
            this.notifications.show('Erreur de communication avec le serveur', 'error');
            return { success: false, message: 'Erreur de communication' };
        }
    }

    async accepterMission(missionId) {
        const result = await this.makeRequest(window.location.href, {
            action: 'accepter_mission',
            mission_id: missionId
        });

        this.notifications.show(result.message, result.success ? 'success' : 'error');
        
        if (result.success) {
            setTimeout(() => location.reload(), 1500);
        }
        
        return result;
    }

    async validerTache(userMissionId, description) {
        const result = await this.makeRequest(window.location.href, {
            action: 'valider_tache',
            user_mission_id: userMissionId,
            description: description
        });

        this.notifications.show(result.message, result.success ? 'success' : 'error');
        
        if (result.success) {
            setTimeout(() => location.reload(), 1500);
        }
        
        return result;
    }

    async creerMission(formData) {
        formData.append('action', 'creer_mission');
        
        const result = await this.makeRequest(window.location.href, formData);
        
        this.notifications.show(result.message, result.success ? 'success' : 'error');
        
        if (result.success) {
            setTimeout(() => location.reload(), 1500);
        }
        
        return result;
    }

    async validerTacheAdmin(validationId, action) {
        const result = await this.makeRequest(window.location.href, {
            action: 'valider_tache',
            validation_id: validationId,
            validation_action: action
        });

        this.notifications.show(result.message, result.success ? 'success' : 'error');
        
        if (result.success) {
            setTimeout(() => location.reload(), 1500);
        }
        
        return result;
    }

    async desactiverMission(missionId) {
        const result = await this.makeRequest(window.location.href, {
            action: 'desactiver_mission',
            mission_id: missionId
        });

        this.notifications.show(result.message, result.success ? 'success' : 'error');
        
        if (result.success) {
            setTimeout(() => location.reload(), 1500);
        }
        
        return result;
    }
}

// Gestionnaire des modales
class MissionsModalManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindValidationModal();
        this.bindCreateMissionModal();
    }

    bindValidationModal() {
        const validationModal = document.getElementById('validationModal');
        if (validationModal) {
            validationModal.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                const missionId = button.getAttribute('data-mission-id');
                document.getElementById('userMissionId').value = missionId;
                document.getElementById('description').value = '';
            });
        }
    }

    bindCreateMissionModal() {
        const createModal = document.getElementById('createMissionModal');
        if (createModal) {
            createModal.addEventListener('show.bs.modal', () => {
                document.getElementById('createMissionForm').reset();
            });
        }
    }

    showValidationModal(userMissionId) {
        document.getElementById('userMissionId').value = userMissionId;
        const modal = new bootstrap.Modal(document.getElementById('validationModal'));
        modal.show();
    }

    hideValidationModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('validationModal'));
        if (modal) {
            modal.hide();
        }
    }

    showCreateMissionModal() {
        const modal = new bootstrap.Modal(document.getElementById('createMissionModal'));
        modal.show();
    }

    hideCreateMissionModal() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('createMissionModal'));
        if (modal) {
            modal.hide();
        }
    }
}

// Gestionnaire principal des missions
class MissionsManager {
    constructor() {
        this.api = new MissionsAPIManager();
        this.tabs = new MissionsTabManager();
        this.modals = new MissionsModalManager();
        this.notifications = new NotificationManager();
        
        this.init();
    }

    init() {
        // Ajouter les styles d'animation
        MissionsUtils.addAnimationClasses();
        
        // Animer les cartes au chargement
        MissionsUtils.animateCards();
        
        // Bind des événements globaux
        this.bindGlobalEvents();
        
        // Initialiser les éléments interactifs
        this.initInteractiveElements();
    }

    bindGlobalEvents() {
        // Gestion des boutons avec effet ripple
        document.addEventListener('click', (e) => {
            if (e.target.closest('.missions-filter-btn')) {
                MissionsUtils.createRipple(e.target.closest('.missions-filter-btn'), e);
            }
        });

        // Gestion des formulaires
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'validationForm') {
                e.preventDefault();
                this.handleValidationSubmit();
            }
            if (e.target.id === 'createMissionForm') {
                e.preventDefault();
                this.handleCreateMissionSubmit();
            }
        });
    }

    initInteractiveElements() {
        // Initialiser les tooltips Bootstrap si disponible
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => 
                new bootstrap.Tooltip(tooltipTriggerEl)
            );
        }

        // Initialiser les popovers Bootstrap si disponible
        if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
            const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
            const popoverList = [...popoverTriggerList].map(popoverTriggerEl => 
                new bootstrap.Popover(popoverTriggerEl)
            );
        }
    }

    async handleValidationSubmit() {
        const form = document.getElementById('validationForm');
        const formData = new FormData(form);
        const userMissionId = formData.get('user_mission_id');
        const description = formData.get('description');

        if (!description.trim()) {
            this.notifications.show('Veuillez décrire la tâche accomplie', 'error');
            return;
        }

        const result = await this.api.validerTache(userMissionId, description);
        
        if (result.success) {
            this.modals.hideValidationModal();
        }
    }

    async handleCreateMissionSubmit() {
        const form = document.getElementById('createMissionForm');
        const formData = new FormData(form);

        const result = await this.api.creerMission(formData);
        
        if (result.success) {
            this.modals.hideCreateMissionModal();
        }
    }

    // Méthodes globales pour les événements onclick
    async accepterMission(missionId) {
        return await this.api.accepterMission(missionId);
    }

    validerTache(userMissionId) {
        this.modals.showValidationModal(userMissionId);
    }

    async soumettreValidation() {
        return await this.handleValidationSubmit();
    }

    async creerMission() {
        return await this.handleCreateMissionSubmit();
    }

    async validerTacheAdmin(validationId, action) {
        return await this.api.validerTacheAdmin(validationId, action);
    }

    async desactiverMission(missionId) {
        if (confirm('Êtes-vous sûr de vouloir désactiver cette mission ?')) {
            return await this.api.desactiverMission(missionId);
        }
    }
}

// Initialisation globale
let missionsManager;

document.addEventListener('DOMContentLoaded', function() {
    missionsManager = new MissionsManager();
    
    // Rendre les méthodes disponibles globalement pour les événements onclick
    window.accepterMission = (missionId) => missionsManager.accepterMission(missionId);
    window.validerTache = (userMissionId) => missionsManager.validerTache(userMissionId);
    window.soumettreValidation = () => missionsManager.soumettreValidation();
    window.creerMission = () => missionsManager.creerMission();
    window.validerTacheAdmin = (validationId, action) => missionsManager.validerTacheAdmin(validationId, action);
    window.desactiverMission = (missionId) => missionsManager.desactiverMission(missionId);
    window.showNotification = (message, type) => missionsManager.notifications.show(message, type);
});

// Export pour les modules ES6 si nécessaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MissionsManager,
        MissionsUtils,
        NotificationManager,
        MissionsAPIManager
    };
} 