/* ===============================================
   MOBILE NAV FIX - Correction du modal mobile
   =============================================== */

console.log('🔧 [MOBILE-NAV-FIX] Script de correction du modal mobile chargé');

// Fonction pour forcer l'affichage du modal
function forceShowMobileModal() {
    console.log('🔧 [MOBILE-NAV-FIX] Forçage de l\'affichage du modal mobile');
    
    const modal = document.getElementById('futuristicMenuModal');
    if (!modal) {
        console.error('🔧 [MOBILE-NAV-FIX] ❌ Modal menu_navigation_modal non trouvé');
        return false;
    }
    
    // Forcer les styles d'affichage
    modal.style.display = 'block';
    modal.style.opacity = '1';
    modal.style.visibility = 'visible';
    modal.style.zIndex = '1055';
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    
    // Ajouter les classes nécessaires
    modal.classList.add('show');
    modal.classList.add('modal');
    modal.classList.add('fade');
    
    // Forcer l'affichage du dialog et content
    const dialog = modal.querySelector('.modal-dialog');
    const content = modal.querySelector('.modal-content');
    
    if (dialog) {
        dialog.style.opacity = '1';
        dialog.style.visibility = 'visible';
        dialog.style.transform = 'none';
        dialog.style.pointerEvents = 'auto';
    }
    
    if (content) {
        content.style.opacity = '1';
        content.style.visibility = 'visible';
        content.style.display = 'block';
    }
    
    // Ajouter le backdrop si nécessaire
    let backdrop = document.querySelector('.modal-backdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.style.zIndex = '1050';
        document.body.appendChild(backdrop);
    }
    
    // Bloquer le scroll du body
    document.body.classList.add('modal-open');
    document.body.style.overflow = 'hidden';
    
    console.log('🔧 [MOBILE-NAV-FIX] ✅ Modal forcé à s\'afficher');
    return true;
}

// Fonction pour diagnostiquer le modal
function diagnoseMobileModal() {
    console.log('🔧 [MOBILE-NAV-FIX] 🔍 Diagnostic du modal mobile');
    
    const modal = document.getElementById('futuristicMenuModal');
    if (!modal) {
        console.error('🔧 [MOBILE-NAV-FIX] ❌ Modal non trouvé');
        return;
    }
    
    const computedStyle = window.getComputedStyle(modal);
    const dialog = modal.querySelector('.modal-dialog');
    const content = modal.querySelector('.modal-content');
    
    console.log('🔧 [MOBILE-NAV-FIX] 📊 État du modal:', {
        display: computedStyle.display
        opacity: computedStyle.opacity
        visibility: computedStyle.visibility
        zIndex: computedStyle.zIndex
        position: computedStyle.position
        classes: modal.className
        dialogStyle: dialog ? window.getComputedStyle(dialog).display : 'N/A'
        contentStyle: content ? window.getComputedStyle(content).display : 'N/A'
}

// Intercepter les tentatives d'ouverture du modal
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 [MOBILE-NAV-FIX] DOM chargé, installation des intercepteurs');
    
    // Intercepter tous les clics sur les boutons de menu mobile
    document.addEventListener('click', function(e) {
        const target = e.target.closest('[data-bs-target="#futuristicMenuModal"], [href="#futuristicMenuModal"]');
        if (target) {
            console.log('🔧 [MOBILE-NAV-FIX] 🎯 Clic détecté sur bouton menu mobile');
            
            // Attendre un peu que Bootstrap fasse son travail
            setTimeout(() => {
                const modal = document.getElementById('futuristicMenuModal');
                if (modal && !modal.style.display || modal.style.display === 'none') {
                    console.log('🔧 [MOBILE-NAV-FIX] ⚠️ Modal non visible, forçage...');
                    forceShowMobileModal();
                }
            }, 100);
        }
    
    // Observer les changements sur le modal
    const modal = document.getElementById('futuristicMenuModal');
    if (modal) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const hasShow = modal.classList.contains('show');
                    const isVisible = modal.style.display !== 'none' && modal.style.visibility !== 'hidden';
                    
                    console.log('🔧 [MOBILE-NAV-FIX] 🔄 Changement détecté:', {
                        hasShow
                        isVisible
                        display: modal.style.display
                        classes: modal.className
                    
                    if (hasShow && !isVisible) {
                        console.log('🔧 [MOBILE-NAV-FIX] 🚨 Modal marqué comme show mais non visible, correction...');
                        forceShowMobileModal();
                    }
                }
        
        observer.observe(modal, {
            attributes: true
            attributeFilter: ['class', 'style']
    }

// Exposer les fonctions pour debug
window.forceShowMobileModal = forceShowMobileModal;
window.diagnoseMobileModal = diagnoseMobileModal;

console.log('🔧 [MOBILE-NAV-FIX] ✅ Script initialisé');
console.log('🔧 [MOBILE-NAV-FIX] 💡 Utilisez window.forceShowMobileModal() pour forcer l\'affichage');
console.log('🔧 [MOBILE-NAV-FIX] 💡 Utilisez window.diagnoseMobileModal() pour diagnostiquer');
