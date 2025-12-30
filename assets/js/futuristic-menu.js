/**
 * MENU FUTURISTE/CORPORATE - JavaScript
 * Gestion des animations et interactions
 */

document.addEventListener('DOMContentLoaded', function () {

    // ===== VARIABLES GLOBALES =====
    const futuristicParticles = document.getElementById('futuristicParticles');
    const menuCards = document.querySelectorAll('.menu-card');

    // Détecter automatiquement le mode sombre du système
    function detectDarkMode() {
        const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const hasBodyDarkClass = document.body.classList.contains('dark-mode') ||
            document.body.classList.contains('dark-theme') ||
            document.body.classList.contains('night-mode');
        const hasHtmlDarkClass = document.documentElement.classList.contains('dark-mode') ||
            document.documentElement.classList.contains('dark-theme') ||
            document.documentElement.classList.contains('night-mode');
        const hasDataTheme = document.documentElement.getAttribute('data-theme') === 'dark';

        // Vérifier aussi les classes Bootstrap dark
        const hasBootstrapDark = document.body.classList.contains('bg-dark') ||
            document.documentElement.classList.contains('bg-dark') ||
            document.documentElement.getAttribute('data-bs-theme') === 'dark';

        const isDark = prefersDarkMode || hasBodyDarkClass || hasHtmlDarkClass || hasDataTheme || hasBootstrapDark;

        // console.log('🔍 [FUTURISTIC-MENU] Détection mode sombre:', {
        prefersDarkMode,
            hasBodyDarkClass,
            hasHtmlDarkClass,
            hasDataTheme,
            hasBootstrapDark,
            result: isDark,
                bodyClasses: document.body.className,
                    htmlClasses: document.documentElement.className,
                        dataTheme: document.documentElement.getAttribute('data-theme'),
                            dataBsTheme: document.documentElement.getAttribute('data-bs-theme')
    });

return isDark;
    }

const isDarkModeActive = detectDarkMode();

let currentTheme = localStorage.getItem('futuristicMenuTheme') || (isDarkModeActive ? 'futuristic' : 'corporate');
let particleSystem = null;

// ===== INITIALISATION =====
initializeTheme();
initializeAnimations();
initializeParticles();
setupEventListeners();

// ===== GESTION DU THÈME =====
function initializeTheme() {
    // Forcer le thème selon la détection du mode système
    if (isDarkModeActive) {
        currentTheme = 'futuristic';
        // console.log('🌙 [FUTURISTIC-MENU] Mode sombre détecté → Thème futuriste');
    } else {
        currentTheme = 'corporate';
        // console.log('☀️ [FUTURISTIC-MENU] Mode jour détecté → Thème corporate');
    }

    // Sauvegarder le thème détecté
    localStorage.setItem('futuristicMenuTheme', currentTheme);

    const modal = document.getElementById('futuristicMenuModal');

    if (currentTheme === 'futuristic') {
        document.body.classList.add('futuristic-theme');
        if (modal) {
            modal.classList.add('futuristic-mode');
        }
        // console.log('🌙 [FUTURISTIC-MENU] Mode futuriste activé');
    } else {
        document.body.classList.remove('futuristic-theme');
        if (modal) {
            modal.classList.remove('futuristic-mode');
        }
        // console.log('☀️ [FUTURISTIC-MENU] Mode corporate activé');
    }
    updateThemeLabels();
}

// Fonction toggle supprimée - thème automatique basé sur le mode sombre

function updateThemeLabels() {
    // Labels supprimés - fonction conservée pour compatibilité
}

function animateThemeSwitch() {
    const modal = document.querySelector('.futuristic-menu-content');
    if (modal) {
        modal.style.transform = 'scale(0.95)';
        setTimeout(() => {
            modal.style.transform = 'scale(1)';
        }, 200);
    }
}

// ===== SYSTÈME DE PARTICULES =====
function initializeParticles() {
    if (!futuristicParticles) return;

    // Créer les particules de base
    createStaticParticles();
}

function createStaticParticles() {
    if (!futuristicParticles) return;

    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
                position: absolute;
                width: 2px;
                height: 2px;
                background: rgba(0, 212, 255, 0.6);
                border-radius: 50%;
                top: ${Math.random() * 100}%;
                left: ${Math.random() * 100}%;
                animation: twinkle ${2 + Math.random() * 3}s infinite ease-in-out;
                animation-delay: ${Math.random() * 2}s;
            `;
        futuristicParticles.appendChild(particle);
    }

    // Ajouter le CSS d'animation
    addParticleStyles();
}

function addParticleStyles() {
    if (document.getElementById('particle-styles')) return;

    const style = document.createElement('style');
    style.id = 'particle-styles';
    style.textContent = `
            @keyframes twinkle {
                0%, 100% { opacity: 0; transform: scale(0); }
                50% { opacity: 1; transform: scale(1); }
            }
            
            @keyframes float-particle {
                0% { transform: translateY(0px) translateX(0px) rotate(0deg); }
                33% { transform: translateY(-20px) translateX(10px) rotate(120deg); }
                66% { transform: translateY(-10px) translateX(-10px) rotate(240deg); }
                100% { transform: translateY(0px) translateX(0px) rotate(360deg); }
            }
        `;
    document.head.appendChild(style);
}

function startParticles() {
    if (particleSystem) return;

    particleSystem = setInterval(() => {
        createFloatingParticle();
    }, 500);
}

function stopParticles() {
    if (particleSystem) {
        clearInterval(particleSystem);
        particleSystem = null;
    }
}

function createFloatingParticle() {
    if (!futuristicParticles || currentTheme !== 'futuristic') return;

    const particle = document.createElement('div');
    const size = 1 + Math.random() * 3;
    const duration = 3 + Math.random() * 4;

    particle.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background: rgba(0, 212, 255, ${0.3 + Math.random() * 0.7});
            border-radius: 50%;
            top: ${Math.random() * 100}%;
            left: ${Math.random() * 100}%;
            animation: float-particle ${duration}s infinite ease-in-out;
            pointer-events: none;
            z-index: 1000;
        `;

    futuristicParticles.appendChild(particle);

    // Supprimer la particule après animation
    setTimeout(() => {
        if (particle.parentNode) {
            particle.parentNode.removeChild(particle);
        }
    }, duration * 1000);
}

// ===== ANIMATIONS DES CARTES =====
function initializeAnimations() {
    menuCards.forEach((card, index) => {
        // Animation d'entrée décalée
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';

        setTimeout(() => {
            card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);

        // Effet de survol avancé
        setupCardHoverEffects(card);
    });
}

function setupCardHoverEffects(card) {
    const cardIcon = card.querySelector('.card-icon');
    const cardGlow = card.querySelector('.card-glow');

    card.addEventListener('mouseenter', function () {
        // Effet de rotation de l'icône
        if (cardIcon) {
            cardIcon.style.transform = 'scale(1.1) rotate(5deg)';
        }

        // Intensifier les particules sur hover en mode futuriste
        if (currentTheme === 'futuristic') {
            createHoverParticles(card);
        }

        // Son de hover (optionnel)
        playHoverSound();
    });

    card.addEventListener('mouseleave', function () {
        if (cardIcon) {
            cardIcon.style.transform = 'scale(1) rotate(0deg)';
        }
    });

    // Effet de clic
    card.addEventListener('click', function (e) {
        createClickRipple(e, card);
        playClickSound();
    });
}

function createHoverParticles(card) {
    const rect = card.getBoundingClientRect();
    const modalRect = document.querySelector('.futuristic-menu-content').getBoundingClientRect();

    for (let i = 0; i < 5; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
                position: absolute;
                width: 4px;
                height: 4px;
                background: rgba(0, 212, 255, 0.8);
                border-radius: 50%;
                top: ${rect.top - modalRect.top + Math.random() * rect.height}px;
                left: ${rect.left - modalRect.left + Math.random() * rect.width}px;
                animation: hover-spark 1s ease-out forwards;
                pointer-events: none;
                z-index: 1001;
            `;

        document.querySelector('.futuristic-menu-content').appendChild(particle);

        setTimeout(() => {
            if (particle.parentNode) {
                particle.parentNode.removeChild(particle);
            }
        }, 1000);
    }

    // Ajouter l'animation hover-spark si elle n'existe pas
    if (!document.getElementById('hover-spark-style')) {
        const style = document.createElement('style');
        style.id = 'hover-spark-style';
        style.textContent = `
                @keyframes hover-spark {
                    0% { opacity: 1; transform: scale(0) translateY(0); }
                    100% { opacity: 0; transform: scale(1) translateY(-20px); }
                }
            `;
        document.head.appendChild(style);
    }
}

function createClickRipple(event, element) {
    const ripple = document.createElement('div');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;

    ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(37, 99, 235, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple-effect 0.6s ease-out;
            pointer-events: none;
            z-index: 1000;
        `;

    element.appendChild(ripple);

    setTimeout(() => {
        if (ripple.parentNode) {
            ripple.parentNode.removeChild(ripple);
        }
    }, 600);

    // Ajouter l'animation ripple si elle n'existe pas
    if (!document.getElementById('ripple-style')) {
        const style = document.createElement('style');
        style.id = 'ripple-style';
        style.textContent = `
                @keyframes ripple-effect {
                    to { transform: scale(2); opacity: 0; }
                }
            `;
        document.head.appendChild(style);
    }
}

// ===== EFFETS SONORES (OPTIONNELS) =====
function playThemeTransitionSound() {
    // Créer un son synthétique pour la transition de thème
    if (typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined') {
        try {
            const audioContext = new (AudioContext || webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.setValueAtTime(400, audioContext.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(800, audioContext.currentTime + 0.3);

            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (e) {
            // Ignorer les erreurs audio
        }
    }
}

function playHoverSound() {
    // Son subtil au survol
    if (typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined') {
        try {
            const audioContext = new (AudioContext || webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.setValueAtTime(600, audioContext.currentTime);
            gainNode.gain.setValueAtTime(0.02, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.1);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
        } catch (e) {
            // Ignorer les erreurs audio
        }
    }
}

function playClickSound() {
    // Son de clic
    if (typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined') {
        try {
            const audioContext = new (AudioContext || webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(400, audioContext.currentTime + 0.1);

            gainNode.gain.setValueAtTime(0.05, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.1);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
        } catch (e) {
            // Ignorer les erreurs audio
        }
    }
}

// ===== EVENT LISTENERS =====
function setupEventListeners() {
    // Détection des changements de thème système
    if (window.matchMedia) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addListener(handleSystemThemeChange);
    }

    // Animation d'ouverture du modal
    const modal = document.getElementById('futuristicMenuModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function () {
            // Re-détecter le thème à chaque ouverture
            const currentDarkMode = detectDarkMode();
            if (currentDarkMode !== isDarkModeActive) {
                // console.log('🔄 [FUTURISTIC-MENU] Changement de thème détecté, re-initialisation...');
                initializeTheme();
            }

            animateMenuOpen();
            if (currentTheme === 'futuristic') {
                startParticles();
            }
        });

        modal.addEventListener('hidden.bs.modal', function () {
            stopParticles();
        });
    }

    // Gestion du redimensionnement
    window.addEventListener('resize', debounce(handleResize, 250));
}

function handleSystemThemeChange(e) {
    // Optionnel: synchroniser avec le thème système
    if (e.matches && currentTheme === 'corporate') {
        // Le système est en mode sombre, on peut proposer le thème futuriste
    }
}

function animateMenuOpen() {
    const sections = document.querySelectorAll('.menu-section');
    sections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateX(-20px)';

        setTimeout(() => {
            section.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
            section.style.opacity = '1';
            section.style.transform = 'translateX(0)';
        }, index * 100);
    });
}

function handleResize() {
    // Réajuster les particules si nécessaire
    if (currentTheme === 'futuristic' && futuristicParticles) {
        // Repositionner les particules existantes
        const particles = futuristicParticles.children;
        for (let particle of particles) {
            particle.style.top = Math.random() * 100 + '%';
            particle.style.left = Math.random() * 100 + '%';
        }
    }
}

// ===== UTILITAIRES =====
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ===== NETTOYAGE =====
window.addEventListener('beforeunload', function () {
    stopParticles();
});

// ===== EASTER EGGS =====
let konami = [];
const konamiCode = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65]; // Up Up Down Down Left Right Left Right B A

document.addEventListener('keydown', function (e) {
    konami.push(e.keyCode);
    if (konami.length > konamiCode.length) {
        konami.shift();
    }

    if (konami.toString() === konamiCode.toString()) {
        // Easter egg: mode Matrix
        activateMatrixMode();
        konami = [];
    }
});

function activateMatrixMode() {
    // Mode Matrix temporaire (effet spécial)
    document.body.style.filter = 'hue-rotate(120deg) contrast(1.2)';

    // Créer des particules "Matrix"
    for (let i = 0; i < 100; i++) {
        setTimeout(() => {
            createMatrixParticle();
        }, i * 50);
    }

    // Retour normal après 10 secondes
    setTimeout(() => {
        document.body.style.filter = '';
    }, 10000);
}

function createMatrixParticle() {
    if (!futuristicParticles) return;

    const chars = '01アカサタナハマヤラワ';
    const particle = document.createElement('div');
    particle.textContent = chars[Math.floor(Math.random() * chars.length)];
    particle.style.cssText = `
            position: absolute;
            color: #00ff00;
            font-family: monospace;
            font-size: ${12 + Math.random() * 8}px;
            top: -20px;
            left: ${Math.random() * 100}%;
            animation: matrix-fall ${3 + Math.random() * 2}s linear forwards;
            pointer-events: none;
            z-index: 1002;
        `;

    futuristicParticles.appendChild(particle);

    setTimeout(() => {
        if (particle.parentNode) {
            particle.parentNode.removeChild(particle);
        }
    }, 5000);

    // Ajouter l'animation matrix si elle n'existe pas
    if (!document.getElementById('matrix-style')) {
        const style = document.createElement('style');
        style.id = 'matrix-style';
        style.textContent = `
                @keyframes matrix-fall {
                    to { transform: translateY(100vh); opacity: 0; }
                }
            `;
        document.head.appendChild(style);
    }
}
});

// ===== UTILITAIRES GLOBAUX =====
window.FuturisticMenu = {
    toggleTheme: function () {
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.click();
        }
    },

    getCurrentTheme: function () {
        return localStorage.getItem('futuristicMenuTheme') || 'corporate';
    },

    setTheme: function (theme) {
        if (theme === 'futuristic' || theme === 'corporate') {
            localStorage.setItem('futuristicMenuTheme', theme);
            location.reload(); // Recharger pour appliquer le thème
        }
    }
};
