/**
 * FORÇAGE JAVASCRIPT DES STYLES NAVBAR BUTTONS
 * Solution ultime pour forcer les styles des boutons navbar en mode nuit
 * Applique les styles directement via JavaScript pour contourner tous les conflits CSS
 */

console.log('🎨 [NAVBAR-FORCE] Script de forçage des styles navbar chargé');

// Fonction pour forcer les styles des boutons navbar
function forceNavbarButtonsStyles() {
    console.log('🎨 [NAVBAR-FORCE] Application forcée des styles...');
    
    // Détecter le mode sombre
    const isDarkMode = document.body.classList.contains('dark-mode') || 
                      window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (!isDarkMode) {
        console.log('🎨 [NAVBAR-FORCE] Mode jour détecté, pas de forçage nécessaire');
        return;
    }
    
    console.log('🎨 [NAVBAR-FORCE] Mode sombre détecté, application des styles...');
    
    // Corriger les z-index des navbars pour s'assurer qu'elles sont visibles
    const navbars = document.querySelectorAll('nav.navbar, #desktop-navbar');
    navbars.forEach((navbar, index) => {
        console.log(`🎨 [NAVBAR-FORCE] Correction z-index navbar ${index + 1}`);
        navbar.style.setProperty('z-index', '999999', 'important');
        navbar.style.setProperty('position', 'relative', 'important');
    });
    
    // Chercher les boutons dans toutes les navbars (principale et de secours)
    const btnNouvelle = document.getElementById('btnNouvelle') || 
                       document.querySelector('.btn-primary[id*="nouvelle"], .btn-primary[class*="nouvelle"]') ||
                       document.querySelector('button[data-bs-target*="nouvelles_actions"]');
    
    if (btnNouvelle) {
        console.log('🎨 [NAVBAR-FORCE] Application des styles au bouton Nouvelle');
        
        // Styles de base
        btnNouvelle.style.setProperty('background', 'linear-gradient(135deg, #00d4ff, #00ffff)', 'important');
        btnNouvelle.style.setProperty('border', 'none', 'important');
        btnNouvelle.style.setProperty('color', '#000', 'important');
        btnNouvelle.style.setProperty('font-weight', '600', 'important');
        btnNouvelle.style.setProperty('box-shadow', '0 0 15px rgba(0, 255, 255, 0.3)', 'important');
        btnNouvelle.style.setProperty('border-radius', '8px', 'important');
        btnNouvelle.style.setProperty('transition', 'all 0.3s ease', 'important');
        
        // Événements hover
        btnNouvelle.addEventListener('mouseenter', function() {
            this.style.setProperty('background', 'linear-gradient(135deg, #00ffff, #00d4ff)', 'important');
            this.style.setProperty('box-shadow', '0 0 25px rgba(0, 255, 255, 0.5)', 'important');
            this.style.setProperty('transform', 'translateY(-2px)', 'important');
        });
        
        btnNouvelle.addEventListener('mouseleave', function() {
            this.style.setProperty('background', 'linear-gradient(135deg, #00d4ff, #00ffff)', 'important');
            this.style.setProperty('box-shadow', '0 0 15px rgba(0, 255, 255, 0.3)', 'important');
            this.style.setProperty('transform', 'translateY(0)', 'important');
        });
        
        console.log('✅ [NAVBAR-FORCE] Styles appliqués au bouton Nouvelle');
    } else {
        console.log('❌ [NAVBAR-FORCE] Bouton Nouvelle non trouvé');
    }
    
    // Styles pour le bouton hamburger - recherche élargie
    const hamburgerBtns = document.querySelectorAll('.futuristic-hamburger-btn, .main-menu-btn, .navbar-toggler, button[data-bs-target*="menu"], button[data-bs-target*="futuristic"]');
    hamburgerBtns.forEach((btn, index) => {
        console.log(`🎨 [NAVBAR-FORCE] Application des styles au bouton hamburger ${index + 1}`);
        
        // Styles de base
        btn.style.setProperty('background', '#1a1a1a', 'important');
        btn.style.setProperty('border', '1px solid #333', 'important');
        btn.style.setProperty('color', '#ffffff', 'important');
        btn.style.setProperty('border-radius', '8px', 'important');
        btn.style.setProperty('transition', 'all 0.3s ease', 'important');
        
        // Événements hover
        btn.addEventListener('mouseenter', function() {
            this.style.setProperty('background', '#2a2a2a', 'important');
            this.style.setProperty('border-color', '#00ffff', 'important');
            this.style.setProperty('color', '#00ffff', 'important');
            this.style.setProperty('box-shadow', '0 0 15px rgba(0, 255, 255, 0.3)', 'important');
            this.style.setProperty('transform', 'translateY(-2px)', 'important');
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.setProperty('background', '#1a1a1a', 'important');
            this.style.setProperty('border-color', '#333', 'important');
            this.style.setProperty('color', '#ffffff', 'important');
            this.style.setProperty('box-shadow', 'none', 'important');
            this.style.setProperty('transform', 'translateY(0)', 'important');
        });
        
        console.log(`✅ [NAVBAR-FORCE] Styles appliqués au bouton hamburger ${index + 1}`);
    });
    
    console.log('✅ [NAVBAR-FORCE] Tous les styles forcés appliqués');
}

// Fonction pour surveiller les changements de thème
function watchThemeChanges() {
    // Observer les changements de classe sur body
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                console.log('🎨 [NAVBAR-FORCE] Changement de thème détecté, réapplication des styles...');
                setTimeout(forceNavbarButtonsStyles, 100);
            }
        });
    });
    
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    // Observer les changements de préférence système
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addListener(function(e) {
        console.log('🎨 [NAVBAR-FORCE] Changement de préférence système détecté:', e.matches ? 'sombre' : 'clair');
        setTimeout(forceNavbarButtonsStyles, 100);
    });
    
    console.log('👁️ [NAVBAR-FORCE] Surveillance des changements de thème activée');
}

// Fonction publique pour forcer manuellement
window.forceNavbarStyles = function() {
    console.log('🎨 [NAVBAR-FORCE] Forçage manuel des styles navbar...');
    forceNavbarButtonsStyles();
};

// Fonction pour forcer les styles même en mode jour (pour les tests)
window.forceNavbarStylesDarkMode = function() {
    console.log('🎨 [NAVBAR-FORCE] Forçage FORCÉ des styles mode sombre...');
    
    // Corriger les z-index des navbars pour s'assurer qu'elles sont visibles
    const navbars = document.querySelectorAll('nav.navbar, #desktop-navbar');
    navbars.forEach((navbar, index) => {
        console.log(`🎨 [NAVBAR-FORCE] Correction z-index navbar ${index + 1}`);
        navbar.style.setProperty('z-index', '999999', 'important');
        navbar.style.setProperty('position', 'relative', 'important');
    });
    
    // Chercher les boutons dans la vraie navbar (pas celle de secours)
    const realNavbar = document.querySelector('nav.navbar:not(#desktop-navbar)') || document.querySelector('.navbar-container nav');
    const btnNouvelle = realNavbar ? realNavbar.querySelector('#btnNouvelle') : document.getElementById('btnNouvelle');
    
    if (btnNouvelle) {
        console.log('🎨 [NAVBAR-FORCE] Application FORCÉE des styles au bouton Nouvelle (vraie navbar)');
        
        // Styles de base
        btnNouvelle.style.setProperty('background', 'linear-gradient(135deg, #00d4ff, #00ffff)', 'important');
        btnNouvelle.style.setProperty('border', 'none', 'important');
        btnNouvelle.style.setProperty('color', '#000', 'important');
        btnNouvelle.style.setProperty('font-weight', '600', 'important');
        btnNouvelle.style.setProperty('box-shadow', '0 0 15px rgba(0, 255, 255, 0.3)', 'important');
        btnNouvelle.style.setProperty('border-radius', '8px', 'important');
        btnNouvelle.style.setProperty('transition', 'all 0.3s ease', 'important');
        
        // Supprimer les anciens événements
        btnNouvelle.removeEventListener('mouseenter', btnNouvelle._hoverEnter);
        btnNouvelle.removeEventListener('mouseleave', btnNouvelle._hoverLeave);
        
        // Événements hover
        btnNouvelle._hoverEnter = function() {
            this.style.setProperty('background', 'linear-gradient(135deg, #00ffff, #00d4ff)', 'important');
            this.style.setProperty('box-shadow', '0 0 25px rgba(0, 255, 255, 0.5)', 'important');
            this.style.setProperty('transform', 'translateY(-2px)', 'important');
        };
        
        btnNouvelle._hoverLeave = function() {
            this.style.setProperty('background', 'linear-gradient(135deg, #00d4ff, #00ffff)', 'important');
            this.style.setProperty('box-shadow', '0 0 15px rgba(0, 255, 255, 0.3)', 'important');
            this.style.setProperty('transform', 'translateY(0)', 'important');
        };
        
        btnNouvelle.addEventListener('mouseenter', btnNouvelle._hoverEnter);
        btnNouvelle.addEventListener('mouseleave', btnNouvelle._hoverLeave);
        
        console.log('✅ [NAVBAR-FORCE] Styles FORCÉS appliqués au bouton Nouvelle');
    } else {
        console.log('❌ [NAVBAR-FORCE] Bouton Nouvelle non trouvé');
    }
    
    // Styles pour le bouton hamburger - cibler la vraie navbar
    const hamburgerBtns = realNavbar ? 
        realNavbar.querySelectorAll('.main-menu-btn, .navbar-toggler, button[data-bs-target*="futuristic"]') :
        document.querySelectorAll('.main-menu-btn, .navbar-toggler, button[data-bs-target*="futuristic"]');
    hamburgerBtns.forEach((btn, index) => {
        console.log(`🎨 [NAVBAR-FORCE] Application FORCÉE des styles au bouton hamburger ${index + 1} (vraie navbar)`);
        
        // Styles de base
        btn.style.setProperty('background', '#1a1a1a', 'important');
        btn.style.setProperty('border', '1px solid #333', 'important');
        btn.style.setProperty('color', '#ffffff', 'important');
        btn.style.setProperty('border-radius', '8px', 'important');
        btn.style.setProperty('transition', 'all 0.3s ease', 'important');
        
        // Supprimer les anciens événements
        btn.removeEventListener('mouseenter', btn._hoverEnter);
        btn.removeEventListener('mouseleave', btn._hoverLeave);
        
        // Événements hover
        btn._hoverEnter = function() {
            this.style.setProperty('background', '#2a2a2a', 'important');
            this.style.setProperty('border-color', '#00ffff', 'important');
            this.style.setProperty('color', '#00ffff', 'important');
            this.style.setProperty('box-shadow', '0 0 15px rgba(0, 255, 255, 0.3)', 'important');
            this.style.setProperty('transform', 'translateY(-2px)', 'important');
        };
        
        btn._hoverLeave = function() {
            this.style.setProperty('background', '#1a1a1a', 'important');
            this.style.setProperty('border-color', '#333', 'important');
            this.style.setProperty('color', '#ffffff', 'important');
            this.style.setProperty('box-shadow', 'none', 'important');
            this.style.setProperty('transform', 'translateY(0)', 'important');
        };
        
        btn.addEventListener('mouseenter', btn._hoverEnter);
        btn.addEventListener('mouseleave', btn._hoverLeave);
        
        console.log(`✅ [NAVBAR-FORCE] Styles FORCÉS appliqués au bouton hamburger ${index + 1}`);
    });
    
    console.log('✅ [NAVBAR-FORCE] Tous les styles FORCÉS appliqués (mode test)');
};

// Fonction de debug pour voir les éléments trouvés
window.debugNavbarElements = function() {
    console.log('🔍 [NAVBAR-DEBUG] Analyse des éléments navbar...');
    
    // Navbars
    const navbars = document.querySelectorAll('nav.navbar, #desktop-navbar');
    console.log(`🔍 [NAVBAR-DEBUG] Navbars trouvées: ${navbars.length}`);
    navbars.forEach((navbar, index) => {
        console.log(`  - Navbar ${index + 1}:`, navbar.id || navbar.className, navbar);
        console.log(`    - Visible:`, window.getComputedStyle(navbar).display !== 'none');
        console.log(`    - Z-index:`, window.getComputedStyle(navbar).zIndex);
    });
    
    // Bouton Nouvelle
    const btnNouvelle = document.getElementById('btnNouvelle');
    const btnNouvelleAlt = document.querySelector('.btn-primary[id*="nouvelle"], .btn-primary[class*="nouvelle"]');
    const btnNouvelleModal = document.querySelector('button[data-bs-target*="nouvelles_actions"]');
    console.log('🔍 [NAVBAR-DEBUG] Boutons Nouvelle:');
    console.log('  - Par ID:', btnNouvelle);
    console.log('  - Par classe:', btnNouvelleAlt);
    console.log('  - Par modal:', btnNouvelleModal);
    
    // Boutons hamburger
    const hamburgerBtns = document.querySelectorAll('.futuristic-hamburger-btn, .main-menu-btn, .navbar-toggler, button[data-bs-target*="menu"], button[data-bs-target*="futuristic"]');
    console.log(`🔍 [NAVBAR-DEBUG] Boutons hamburger trouvés: ${hamburgerBtns.length}`);
    hamburgerBtns.forEach((btn, index) => {
        console.log(`  - Hamburger ${index + 1}:`, btn.className, btn);
    });
    
    // Mode sombre
    const isDarkMode = document.body.classList.contains('dark-mode') || 
                      window.matchMedia('(prefers-color-scheme: dark)').matches;
    console.log('🔍 [NAVBAR-DEBUG] Mode sombre:', isDarkMode);
    console.log('🔍 [NAVBAR-DEBUG] Classes body:', document.body.className);
};

// Fonction pour identifier quelle navbar est active
window.identifyActiveNavbar = function() {
    console.log('🔍 [NAVBAR-IDENTIFY] Identification de la navbar active...');
    
    const allNavbars = document.querySelectorAll('nav');
    console.log(`🔍 [NAVBAR-IDENTIFY] Total navbars trouvées: ${allNavbars.length}`);
    
    allNavbars.forEach((navbar, index) => {
        const style = window.getComputedStyle(navbar);
        const isVisible = style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
        
        console.log(`🔍 [NAVBAR-IDENTIFY] Navbar ${index + 1}:`);
        console.log(`  - ID: ${navbar.id || 'aucun'}`);
        console.log(`  - Classes: ${navbar.className}`);
        console.log(`  - Display: ${style.display}`);
        console.log(`  - Visibility: ${style.visibility}`);
        console.log(`  - Opacity: ${style.opacity}`);
        console.log(`  - Z-index: ${style.zIndex}`);
        console.log(`  - Visible: ${isVisible ? '✅ OUI' : '❌ NON'}`);
        
        if (isVisible) {
            console.log(`  - 🎯 NAVBAR ACTIVE DÉTECTÉE !`);
            
            // Chercher les boutons dans cette navbar
            const btnNouvelle = navbar.querySelector('#btnNouvelle');
            const hamburgerBtns = navbar.querySelectorAll('.main-menu-btn, .navbar-toggler');
            
            console.log(`  - Bouton Nouvelle: ${btnNouvelle ? '✅ Trouvé' : '❌ Non trouvé'}`);
            console.log(`  - Boutons hamburger: ${hamburgerBtns.length} trouvé(s)`);
            
            if (btnNouvelle) {
                console.log(`    - Bouton Nouvelle ID: ${btnNouvelle.id}`);
                console.log(`    - Bouton Nouvelle classes: ${btnNouvelle.className}`);
            }
            
            hamburgerBtns.forEach((btn, btnIndex) => {
                console.log(`    - Hamburger ${btnIndex + 1} classes: ${btn.className}`);
            });
        }
        
        console.log(''); // Ligne vide pour séparer
    });
};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎨 [NAVBAR-FORCE] DOM chargé, initialisation...');
    
    // Application initiale
    setTimeout(forceNavbarButtonsStyles, 500);
    
    // Surveillance des changements
    watchThemeChanges();
    
    // Réapplication périodique pour s'assurer que les styles restent
    setInterval(function() {
        const isDarkMode = document.body.classList.contains('dark-mode') || 
                          window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (isDarkMode) {
            forceNavbarButtonsStyles();
        }
    }, 5000); // Toutes les 5 secondes
    
    console.log('✅ [NAVBAR-FORCE] Initialisation terminée');
});

console.log('🎨 [NAVBAR-FORCE] Script prêt');
console.log('💡 Utilisez window.forceNavbarStyles() pour forcer selon le mode détecté');
console.log('💡 Utilisez window.forceNavbarStylesDarkMode() pour forcer les styles sombres même en mode jour');
console.log('💡 Utilisez window.debugNavbarElements() pour diagnostiquer');
console.log('💡 Utilisez window.identifyActiveNavbar() pour identifier la navbar active');
