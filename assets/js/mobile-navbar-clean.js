/*
 * JavaScript pour la barre de navigation mobile moderne
 * Gère les interactions et le modal du bouton +
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Barre de navigation mobile moderne initialisée');
    
    // Supprimer toutes les autres barres de navigation en conflit
    cleanupConflictingNavbars();
    
    // Initialiser le bouton +
    initializePlusButton();
    
    // Nettoyer périodiquement les éléments en conflit
    setInterval(cleanupConflictingNavbars, 3000);

/**
 * Supprime tous les éléments de navigation en conflit
 */
function cleanupConflictingNavbars() {
    // Sélecteurs pour tous les éléments de navigation mobile à supprimer (SAUF navbar desktop)
    const conflictingSelectors = [
        '.mobile-welcome-banner'
        '.mobile-time-tracking'
        '.bottom-nav'
        '.neo-dock'
        '.navbar-mobile'
        '.mobile-navbar'
        '.mobile-navigation'
        '.bottom-navigation'
        '.fixed-bottom:not(#mobile-dock):not(.navbar)'
        'nav[class*="bottom"]:not(#mobile-dock):not(.navbar)'
        'nav[class*="mobile"]:not(#mobile-dock):not(.navbar)'
        '.nav-bottom'
        '.navbar-bottom'
        'div[class*="dock"]:not(#mobile-dock):not(.mobile-dock-container):not(.dock-item):not(.dock-icon-wrapper)'
        '*[style*="background-color: orange"]'
        '*[style*="background-color: #ffa500"]'
        '.bg-warning:not(.badge)'
    ];
    
    conflictingSelectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            if (element && !element.closest('#mobile-dock')) {
                element.style.display = 'none';
                element.style.visibility = 'hidden';
                element.style.opacity = '0';
                element.style.position = 'absolute';
                element.style.left = '-9999px';
            }
    
    // Masquer tous les autres mobile-dock
    const oldMobileDocks = document.querySelectorAll('#mobile-dock');
    oldMobileDocks.forEach(dock => {
        dock.style.display = 'none';
        dock.style.visibility = 'hidden';
        dock.style.opacity = '0';
    
    // S'assurer que NOTRE barre est visible
    const mobileDockClean = document.getElementById('mobile-dock-clean');
    if (mobileDockClean && window.innerWidth <= 991) {
        mobileDockClean.style.display = 'block';
        mobileDockClean.style.visibility = 'visible';
        mobileDockClean.style.opacity = '1';
    }
}

/**
 * Initialise le bouton + pour ouvrir le modal
 */
function initializePlusButton() {
    const plusButton = document.querySelector('.plus-button');
    if (plusButton) {
        plusButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Effet de clic
            this.style.transform = 'translateY(-10px) scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'translateY(-8px) scale(1)';
            }, 150);
            
            // Ouvrir le modal nouvelles_actions_modal
            const modal = document.getElementById('nouvelles_actions_modal');
            if (modal) {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
                console.log('✅ Modal nouvelles actions ouvert');
            } else {
                console.warn('⚠️ Modal nouvelles_actions_modal non trouvé');
                // Fallback : rediriger vers une page de création
                window.location.href = 'index.php?page=ajouter_reparation';
            }
        
        console.log('✅ Bouton + configuré');
    }
}

/**
 * Gestion du thème (jour/nuit)
 */
function handleThemeChange() {
    const isDarkMode = document.body.classList.contains('dark-mode') || 
                      document.documentElement.getAttribute('data-theme') === 'dark';
    
    const mobileDockClean = document.getElementById('mobile-dock-clean');
    if (mobileDockClean) {
        if (isDarkMode) {
            mobileDockClean.classList.add('dark-theme');
        } else {
            mobileDockClean.classList.remove('dark-theme');
        }
    }
}

// Observer les changements de thème
if (window.MutationObserver) {
    const themeObserver = new MutationObserver(handleThemeChange);
    themeObserver.observe(document.body, { 
        attributes: true
        attributeFilter: ['class', 'data-theme'] 
    themeObserver.observe(document.documentElement, { 
        attributes: true
        attributeFilter: ['data-theme'] 
}

// Gestion du redimensionnement
window.addEventListener('resize', function() {
    // Masquer les anciens docks
    const oldMobileDocks = document.querySelectorAll('#mobile-dock');
    oldMobileDocks.forEach(dock => {
        dock.style.display = 'none';
    
    // Gérer NOTRE dock
    const mobileDockClean = document.getElementById('mobile-dock-clean');
    if (mobileDockClean) {
        if (window.innerWidth <= 991) {
            mobileDockClean.style.display = 'block';
        } else {
            mobileDockClean.style.display = 'none';
        }
    }

console.log('📱 Mobile navbar moderne - JavaScript chargé');
