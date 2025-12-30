/*
 * JavaScript pour le menu latéral moderne
 * Gestion des interactions et fermeture automatique
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎛️ Menu latéral moderne initialisé');
    
    initializeSidebarMenu();

/**
 * Initialise le menu latéral
 */
function initializeSidebarMenu() {
    const menuCheckbox = document.getElementById('modern-menu-checkbox');
    const menuOverlay = document.querySelector('.menu-overlay');
    const menuLinks = document.querySelectorAll('.menu-links a');
    
    if (!menuCheckbox) {
        console.warn('⚠️ Menu checkbox non trouvé');
        return;
    }
    
    // Fermer le menu quand on clique sur l'overlay
    if (menuOverlay) {
        menuOverlay.addEventListener('click', function() {
            menuCheckbox.checked = false;
            console.log('🔒 Menu fermé via overlay');
    }
    
    // Fermer le menu quand on clique sur un lien
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Ne pas fermer pour les liens avec # (sections)
            if (this.getAttribute('href') !== '#') {
                setTimeout(() => {
                    menuCheckbox.checked = false;
                    console.log('🔒 Menu fermé via lien');
                }, 150);
            }
    
    // Fermer le menu avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && menuCheckbox.checked) {
            menuCheckbox.checked = false;
            console.log('🔒 Menu fermé via Escape');
        }
    
    // Marquer le lien actif selon la page courante
    markActiveMenuItem();
    
    // Gérer les sous-menus si nécessaire
    handleSubMenus();
    
    console.log('✅ Menu latéral configuré');
}

/**
 * Marque l'élément de menu actif selon la page courante
 */
function markActiveMenuItem() {
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'accueil';
    const menuLinks = document.querySelectorAll('.menu-links a');
    
    menuLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(`page=${currentPage}`)) {
            link.classList.add('active');
            console.log(`📍 Élément actif marqué: ${currentPage}`);
        } else {
            link.classList.remove('active');
        }
}

/**
 * Gère les sous-menus et les interactions avancées
 */
function handleSubMenus() {
    // Gestion des sections avec sous-éléments
    const sectionHeaders = document.querySelectorAll('.menu-section-title');
    
    sectionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const section = this.closest('.menu-section');
            const links = section.querySelectorAll('.menu-links');
            
            // Animation de toggle si nécessaire
            links.forEach(linkGroup => {
                linkGroup.style.transition = 'all 0.3s ease';
}

/**
 * Gestion des notifications et badges
 */
function updateMenuBadges() {
    // Mettre à jour le badge des tâches
    fetch('ajax/get_tasks_count.php')
        .then(response => response.json())
        .then(data => {
            const tasksBadge = document.querySelector('[href*="page=taches"] .menu-badge');
            if (tasksBadge && data.count > 0) {
                tasksBadge.textContent = data.count;
                tasksBadge.style.display = 'inline-block';
            } else if (tasksBadge) {
                tasksBadge.style.display = 'none';
            }
        })
        .catch(error => {
            console.log('Info: Comptage des tâches non disponible');
}

/**
 * Animation d'ouverture du menu
 */
function animateMenuOpening() {
    const menuPane = document.querySelector('.menu-pane');
    const menuItems = document.querySelectorAll('.menu-links a');
    
    if (menuPane && menuPane.classList.contains('open')) {
        menuItems.forEach((item, index) => {
            setTimeout(() => {
                item.style.animation = `slideInLeft 0.4s ease forwards`;
                item.style.animationDelay = `${index * 0.05}s`;
            }, 100);
    }
}

/**
 * Gestion du thème (jour/nuit)
 */
function handleMenuTheme() {
    const isDarkMode = document.body.classList.contains('dark-mode') || 
                      document.documentElement.getAttribute('data-theme') === 'dark';
    
    const menuPane = document.querySelector('.menu-pane');
    if (menuPane) {
        if (isDarkMode) {
            menuPane.classList.add('dark-theme');
        } else {
            menuPane.classList.remove('dark-theme');
        }
    }
}

// Observer les changements de thème
if (window.MutationObserver) {
    const themeObserver = new MutationObserver(handleMenuTheme);
    themeObserver.observe(document.body, { 
        attributes: true
        attributeFilter: ['class', 'data-theme'] 
    themeObserver.observe(document.documentElement, { 
        attributes: true
        attributeFilter: ['data-theme'] 
}

// Mettre à jour les badges périodiquement
setInterval(updateMenuBadges, 30000); // Toutes les 30 secondes
updateMenuBadges(); // Première exécution

console.log('🎛️ Menu latéral moderne - JavaScript chargé');
