/**
 * DASHBOARD OPTIMISÉ - JAVASCRIPT COMBINÉ ET MINIFIÉ
 * Combine tous les scripts critiques pour améliorer les performances
 */

// Configuration globale optimisée
const DashboardConfig = {
    animationSpeed: 300,
    debounceDelay: 250,
    cacheTimeout: 5 * 60 * 1000, // 5 minutes
    lazyLoadOffset: 100
};

// Cache simple pour les données
const DataCache = {
    data: new Map(),
    timestamps: new Map(),
    
    set(key, value) {
        this.data.set(key, value);
        this.timestamps.set(key, Date.now());
    },
    
    get(key) {
        const timestamp = this.timestamps.get(key);
        if (!timestamp || Date.now() - timestamp > DashboardConfig.cacheTimeout) {
            this.data.delete(key);
            this.timestamps.delete(key);
            return null;
        }
        return this.data.get(key);
    },
    
    clear() {
        this.data.clear();
        this.timestamps.clear();
    }
};

// Utilitaires de performance
const Utils = {
    // Debounce optimisé
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Throttle pour les événements fréquents
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    // Intersection Observer pour lazy loading
    createLazyObserver(callback) {
        return new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    callback(entry.target);
                }
            });
        }, {
            rootMargin: `${DashboardConfig.lazyLoadOffset}px`
        });
    },
    
    // Requête AJAX optimisée avec cache
    async fetchData(url, options = {}) {
        const cacheKey = url + JSON.stringify(options);
        const cached = DataCache.get(cacheKey);
        if (cached) return cached;
        
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                }
            });
            
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const data = await response.json();
            DataCache.set(cacheKey, data);
            return data;
        } catch (error) {
            console.error('Fetch error:', error);
            throw error;
        }
    }
};

// Gestionnaire de modals optimisé
class ModalManager {
    constructor() {
        this.activeModals = new Set();
        this.init();
    }
    
    init() {
        // Délégation d'événements pour les modals
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-bs-toggle="modal"]');
            if (trigger) {
                e.preventDefault();
                this.openModal(trigger.dataset.bsTarget);
            }
            
            const close = e.target.closest('[data-bs-dismiss="modal"]');
            if (close) {
                this.closeModal(close.closest('.modal'));
            }
        });
        
        // Fermeture par ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeModals.size > 0) {
                const lastModal = Array.from(this.activeModals).pop();
                this.closeModal(lastModal);
            }
        });
    }
    
    openModal(selector) {
        const modal = document.querySelector(selector);
        if (!modal) return;
        
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        this.activeModals.add(modal);
        
        // Animation d'entrée
        requestAnimationFrame(() => {
            modal.style.opacity = '1';
        });
    }
    
    closeModal(modal) {
        if (!modal) return;
        
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            modal.classList.remove('show');
            this.activeModals.delete(modal);
            
            if (this.activeModals.size === 0) {
                document.body.classList.remove('modal-open');
            }
        }, DashboardConfig.animationSpeed);
    }
}

// Gestionnaire de statistiques optimisé
class StatsManager {
    constructor() {
        this.charts = new Map();
        this.updateInterval = null;
    }
    
    async loadStats() {
        try {
            const stats = await Utils.fetchData('api/dashboard-stats.php');
            this.updateDisplay(stats);
        } catch (error) {
            console.error('Erreur chargement stats:', error);
            this.showError();
        }
    }
    
    updateDisplay(stats) {
        // Mise à jour des compteurs avec animation
        Object.entries(stats).forEach(([key, value]) => {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) {
                this.animateNumber(element, parseInt(element.textContent) || 0, value);
            }
        });
    }
    
    animateNumber(element, from, to) {
        const duration = 1000;
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(from + (to - from) * easeOut);
            
            element.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }
    
    showError() {
        const statsElements = document.querySelectorAll('[data-stat]');
        statsElements.forEach(el => {
            el.textContent = '--';
            el.parentElement.classList.add('error');
        });
    }
    
    startAutoUpdate(interval = 30000) {
        this.updateInterval = setInterval(() => {
            this.loadStats();
        }, interval);
    }
    
    stopAutoUpdate() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }
}

// Gestionnaire de tableaux optimisé
class TableManager {
    constructor() {
        this.tables = new Map();
        this.init();
    }
    
    init() {
        // Délégation pour les actions de tableau
        document.addEventListener('click', (e) => {
            const sortBtn = e.target.closest('[data-sort]');
            if (sortBtn) {
                this.sortTable(sortBtn);
            }
            
            const filterBtn = e.target.closest('[data-filter]');
            if (filterBtn) {
                this.filterTable(filterBtn);
            }
        });
    }
    
    sortTable(button) {
        const table = button.closest('table');
        const column = button.dataset.sort;
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // Déterminer l'ordre de tri
        const isAsc = button.classList.contains('asc');
        button.classList.toggle('asc', !isAsc);
        button.classList.toggle('desc', isAsc);
        
        // Trier les lignes
        rows.sort((a, b) => {
            const aVal = a.querySelector(`[data-value="${column}"]`)?.dataset.value || 
                        a.cells[button.cellIndex]?.textContent || '';
            const bVal = b.querySelector(`[data-value="${column}"]`)?.dataset.value || 
                        b.cells[button.cellIndex]?.textContent || '';
            
            const comparison = aVal.localeCompare(bVal, undefined, { numeric: true });
            return isAsc ? -comparison : comparison;
        });
        
        // Réinsérer les lignes triées
        rows.forEach(row => tbody.appendChild(row));
    }
    
    filterTable(button) {
        const table = button.closest('table');
        const filter = button.dataset.filter;
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const shouldShow = filter === 'all' || row.dataset.status === filter;
            row.style.display = shouldShow ? '' : 'none';
        });
        
        // Mise à jour des boutons de filtre
        const filterButtons = button.parentElement.querySelectorAll('[data-filter]');
        filterButtons.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
    }
}

// Gestionnaire d'animations optimisé
class AnimationManager {
    constructor() {
        this.observer = null;
        this.init();
    }
    
    init() {
        // Observer pour les animations à l'entrée
        this.observer = Utils.createLazyObserver((target) => {
            target.classList.add('animate-in');
            this.observer.unobserve(target);
        });
        
        // Observer tous les éléments avec la classe animate-on-scroll
        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            this.observer.observe(el);
        });
    }
    
    addElement(element) {
        if (this.observer) {
            this.observer.observe(element);
        }
    }
}

// Gestionnaire de notifications optimisé
class NotificationManager {
    constructor() {
        this.container = null;
        this.init();
    }
    
    init() {
        // Créer le conteneur de notifications
        this.container = document.createElement('div');
        this.container.className = 'notification-container';
        this.container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            pointer-events: none;
        `;
        document.body.appendChild(this.container);
    }
    
    show(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: ${this.getColor(type)};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            pointer-events: auto;
            cursor: pointer;
        `;
        notification.textContent = message;
        
        this.container.appendChild(notification);
        
        // Animation d'entrée
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
        });
        
        // Auto-suppression
        setTimeout(() => {
            this.hide(notification);
        }, duration);
        
        // Suppression au clic
        notification.addEventListener('click', () => {
            this.hide(notification);
        });
        
        return notification;
    }
    
    hide(notification) {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
    
    getColor(type) {
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        return colors[type] || colors.info;
    }
}

// Initialisation du dashboard
class Dashboard {
    constructor() {
        this.modalManager = new ModalManager();
        this.statsManager = new StatsManager();
        this.tableManager = new TableManager();
        this.animationManager = new AnimationManager();
        this.notificationManager = new NotificationManager();
        
        this.init();
    }
    
    async init() {
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.onReady());
        } else {
            this.onReady();
        }
    }
    
    async onReady() {
        try {
            // Cacher le loader
            this.hideLoader();
            
            // Charger les statistiques
            await this.statsManager.loadStats();
            
            // Démarrer les mises à jour automatiques
            this.statsManager.startAutoUpdate();
            
            // Initialiser les lazy loading
            this.initLazyLoading();
            
            // Optimiser les performances
            this.optimizePerformance();
            
            console.log('Dashboard initialisé avec succès');
            
        } catch (error) {
            console.error('Erreur initialisation dashboard:', error);
            this.notificationManager.show('Erreur de chargement du dashboard', 'error');
        }
    }
    
    hideLoader() {
        const loader = document.getElementById('pageLoader');
        if (loader) {
            loader.classList.add('hide');
            setTimeout(() => {
                loader.style.display = 'none';
            }, 300);
        }
    }
    
    initLazyLoading() {
        // Images lazy loading
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = Utils.createLazyObserver((img) => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
            imageObserver.unobserve(img);
        });
        
        images.forEach(img => imageObserver.observe(img));
        
        // Contenu lazy loading
        const lazyContents = document.querySelectorAll('.lazy-content');
        const contentObserver = Utils.createLazyObserver((content) => {
            this.loadLazyContent(content);
            contentObserver.unobserve(content);
        });
        
        lazyContents.forEach(content => contentObserver.observe(content));
    }
    
    async loadLazyContent(element) {
        const url = element.dataset.url;
        if (!url) return;
        
        try {
            const response = await fetch(url);
            const html = await response.text();
            element.innerHTML = html;
            element.classList.remove('lazy-content');
            
            // Réinitialiser les animations pour le nouveau contenu
            element.querySelectorAll('.animate-on-scroll').forEach(el => {
                this.animationManager.addElement(el);
            });
            
        } catch (error) {
            console.error('Erreur chargement lazy content:', error);
            element.innerHTML = '<p class="text-muted">Erreur de chargement</p>';
        }
    }
    
    optimizePerformance() {
        // Précharger les ressources critiques
        this.preloadCriticalResources();
        
        // Optimiser les événements de scroll
        const scrollHandler = Utils.throttle(() => {
            this.handleScroll();
        }, 16); // 60fps
        
        window.addEventListener('scroll', scrollHandler, { passive: true });
        
        // Nettoyer le cache périodiquement
        setInterval(() => {
            DataCache.clear();
        }, 10 * 60 * 1000); // 10 minutes
    }
    
    preloadCriticalResources() {
        const criticalImages = [
            'assets/images/logo.png',
            'assets/images/dashboard-bg.jpg'
        ];
        
        criticalImages.forEach(src => {
            const img = new Image();
            img.src = src;
        });
    }
    
    handleScroll() {
        // Logique de scroll optimisée si nécessaire
    }
    
    // API publique
    showNotification(message, type = 'info') {
        return this.notificationManager.show(message, type);
    }
    
    refreshStats() {
        return this.statsManager.loadStats();
    }
    
    openModal(selector) {
        return this.modalManager.openModal(selector);
    }
}

// Initialisation globale
window.dashboard = new Dashboard();

// Export pour utilisation externe
window.DashboardAPI = {
    showNotification: (message, type) => window.dashboard.showNotification(message, type),
    refreshStats: () => window.dashboard.refreshStats(),
    openModal: (selector) => window.dashboard.openModal(selector),
    cache: DataCache,
    utils: Utils
};
