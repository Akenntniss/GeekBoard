/**
 * NAVBAR BUTTONS DEBUG
 * Script de diagnostic pour vérifier l'état des boutons navbar
 */

(function() {
    'use strict';
    
    console.log('🔍 [NAVBAR-DEBUG] Initialisation du diagnostic des boutons...');
    
    function debugNavbarButtons() {
        console.log('🔍 [NAVBAR-DEBUG] === DIAGNOSTIC BOUTONS NAVBAR ===');
        
        // Vérifier la navbar
        const navbar = document.getElementById('desktop-navbar');
        console.log('📋 [NAVBAR-DEBUG] Navbar:', {
            display: navbar ? window.getComputedStyle(navbar).display : 'N/A'
            visibility: navbar ? window.getComputedStyle(navbar).visibility : 'N/A'
            opacity: navbar ? window.getComputedStyle(navbar).opacity : 'N/A'
        
        // Vérifier le conteneur des boutons
        const buttonContainer = document.querySelector('.d-none.d-lg-flex');
        console.log('📋 [NAVBAR-DEBUG] Container boutons:', {
            display: buttonContainer ? window.getComputedStyle(buttonContainer).display : 'N/A'
            visibility: buttonContainer ? window.getComputedStyle(buttonContainer).visibility : 'N/A'
            opacity: buttonContainer ? window.getComputedStyle(buttonContainer).opacity : 'N/A'
        
        // Vérifier le bouton Nouvelle
        const btnNouvelle = document.querySelector('.btn-nouvelle-improved');
        console.log('📋 [NAVBAR-DEBUG] Bouton Nouvelle:', {
            display: btnNouvelle ? window.getComputedStyle(btnNouvelle).display : 'N/A'
            visibility: btnNouvelle ? window.getComputedStyle(btnNouvelle).visibility : 'N/A'
            opacity: btnNouvelle ? window.getComputedStyle(btnNouvelle).opacity : 'N/A'
            classes: btnNouvelle ? btnNouvelle.className : 'N/A'
        
        // Vérifier le bouton Menu
        const btnMenu = document.querySelector('.main-menu-btn');
        console.log('📋 [NAVBAR-DEBUG] Bouton Menu:', {
            display: btnMenu ? window.getComputedStyle(btnMenu).display : 'N/A'
            visibility: btnMenu ? window.getComputedStyle(btnMenu).visibility : 'N/A'
            opacity: btnMenu ? window.getComputedStyle(btnMenu).opacity : 'N/A'
            classes: btnMenu ? btnMenu.className : 'N/A'
        
        // Vérifier les CSS chargés
        const geekCSS = Array.from(document.styleSheets).find(sheet => 
            sheet.href && sheet.href.includes('geek-navbar-buttons.css')
        );
        console.log('📋 [NAVBAR-DEBUG] CSS geek-navbar-buttons:', {
            href: geekCSS ? geekCSS.href : 'N/A'
        
        // Vérifier les règles CSS appliquées
        if (btnNouvelle) {
            const styles = window.getComputedStyle(btnNouvelle);
            console.log('📋 [NAVBAR-DEBUG] Styles bouton Nouvelle:', {
                backgroundColor: styles.backgroundColor
                color: styles.color
                border: styles.border
                borderRadius: styles.borderRadius
                padding: styles.padding
                fontSize: styles.fontSize
        }
        
        if (btnMenu) {
            const styles = window.getComputedStyle(btnMenu);
            console.log('📋 [NAVBAR-DEBUG] Styles bouton Menu:', {
                backgroundColor: styles.backgroundColor
                color: styles.color
                border: styles.border
                borderRadius: styles.borderRadius
                padding: styles.padding
                fontSize: styles.fontSize
        }
        
        // Vérifier les classes Bootstrap
        console.log('📋 [NAVBAR-DEBUG] Classes Bootstrap détectées:', {
            'd-none': document.querySelectorAll('.d-none').length
            'd-lg-flex': document.querySelectorAll('.d-lg-flex').length
            'd-none.d-lg-flex': document.querySelectorAll('.d-none.d-lg-flex').length
        
        console.log('🔍 [NAVBAR-DEBUG] === FIN DIAGNOSTIC ===');
    }
    
    // Exécuter le diagnostic
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', debugNavbarButtons);
    } else {
        debugNavbarButtons();
    }
    
    // Exposer la fonction globalement
    window.debugNavbarButtons = debugNavbarButtons;
    
    // Auto-diagnostic toutes les 3 secondes
    setInterval(debugNavbarButtons, 3000);
    
})();
