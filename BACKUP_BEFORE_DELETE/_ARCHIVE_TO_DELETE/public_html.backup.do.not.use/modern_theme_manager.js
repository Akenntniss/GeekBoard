// Système de thème ultra moderne
class ThemeManager {
    constructor() {
        this.currentTheme = localStorage.getItem('theme') || 'light';
        this.init();
    }

    init() {
        this.setTheme(this.currentTheme);
        this.setupEventListeners();
        this.setupSystemDetection();
    }

    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        this.currentTheme = theme;
        localStorage.setItem('theme', theme);
        this.updateIcon();
        this.animateToggle();
        
        // Force les styles pour le mode sombre
        if (theme === 'dark') {
            document.body.style.background = 'linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%)';
            document.body.style.color = '#ffffff';
        } else {
            document.body.style.background = 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)';
            document.body.style.color = '#1a202c';
        }
        
        console.log('Theme set to:', theme);
    }

    updateIcon() {
        const icon = document.getElementById('themeIcon');
        if (icon) {
            if (this.currentTheme === 'dark') {
                icon.className = 'fas fa-moon';
            } else {
                icon.className = 'fas fa-sun';
            }
        }
    }

    animateToggle() {
        const toggle = document.getElementById('themeToggle');
        if (toggle) {
            toggle.style.transform = 'scale(0.8) rotate(180deg)';
            setTimeout(() => {
                toggle.style.transform = 'scale(1) rotate(0deg)';
            }, 200);
        }
    }

    setupEventListeners() {
        const toggleBtn = document.getElementById('themeToggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
                this.setTheme(newTheme);
            });
        }
    }

    setupSystemDetection() {
        // Détection automatique du thème système
        if (!localStorage.getItem('theme')) {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
            this.setTheme(prefersDark.matches ? 'dark' : 'light');
            
            // Écoute des changements système
            prefersDark.addEventListener('change', (e) => {
                if (!localStorage.getItem('theme')) {
                    this.setTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
    }
}

// Animations d'entrée
function animateCards() {
    const cards = document.querySelectorAll('.glass-card, .stat-card, .card, .mission-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease-out';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 50);
    });
}

// Gestion des filtres pour mes_missions.php
class FilterManager {
    constructor() {
        this.currentFilter = 'all';
        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const filter = e.target.dataset.filter;
                this.setFilter(filter);
            });
        });
    }

    setFilter(filter) {
        // Mise à jour des tabs
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        const activeTab = document.querySelector(`[data-filter="${filter}"]`);
        if (activeTab) {
            activeTab.classList.add('active');
        }

        // Logique de filtrage
        this.currentFilter = filter;
        this.filterMissions();
    }

    filterMissions() {
        // Exemple de filtrage - à adapter selon vos données
        const missions = document.querySelectorAll('.mission-card');
        missions.forEach(mission => {
            const isVisible = this.shouldShowMission(mission);
            mission.style.display = isVisible ? 'block' : 'none';
        });
    }

    shouldShowMission(mission) {
        if (this.currentFilter === 'all') return true;
        
        // Logique de filtrage basée sur les badges
        const badge = mission.querySelector('.badge');
        if (!badge) return false;
        
        const status = badge.textContent.toLowerCase();
        
        switch (this.currentFilter) {
            case 'active':
                return status.includes('cours') || status.includes('nouvelle');
            case 'completed':
                return status.includes('validé') || status.includes('terminé');
            case 'pending':
                return status.includes('attente');
            default:
                return true;
        }
    }
}

// Effets de parallax subtils
function setupParallaxEffects() {
    const cards = document.querySelectorAll('.glass-card, .stat-card, .card');
    
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(0)`;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0)';
        });
    });
}

// Initialisation globale
document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing modern theme system...');
    
    // Initialisation du gestionnaire de thème
    new ThemeManager();
    
    // Initialisation des animations
    setTimeout(() => {
        animateCards();
    }, 100);
    
    // Initialisation des filtres si on est sur mes_missions.php
    if (document.querySelector('.filter-tab')) {
        new FilterManager();
    }
    
    // Initialisation des effets de parallax
    setTimeout(() => {
        setupParallaxEffects();
    }, 500);
    
    console.log('Modern theme system initialized successfully!');
});

// Ajout du bouton toggle s'il n'existe pas
function ensureToggleButton() {
    if (!document.getElementById('themeToggle')) {
        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'themeToggle';
        toggleBtn.className = 'theme-toggle';
        toggleBtn.innerHTML = '<i class="fas fa-sun" id="themeIcon"></i>';
        document.body.appendChild(toggleBtn);
    }
}

// Assurer que le bouton toggle existe
document.addEventListener('DOMContentLoaded', ensureToggleButton); 