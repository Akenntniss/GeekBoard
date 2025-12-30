// Correctif pour la navigation des onglets dans la page parametre.php
// Ce script remplace complètement la gestion des onglets

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Correctif navigation des paramètres chargé');
    
    // Attendre que le loader soit terminé et que le contenu soit visible
    function waitForContent() {
        const mainContent = document.getElementById('mainContent');
        
        if (!mainContent || mainContent.style.display === 'none') {
            console.log('⏳ Attente du chargement du contenu...');
            setTimeout(waitForContent, 100);
            return;
        }
        
        console.log('✅ Contenu principal visible, initialisation de la navigation');
        initializeNavigation();
    }
    
    function initializeNavigation() {
        // Supprimer tous les event listeners existants en clonant les éléments
        const navItems = document.querySelectorAll('.nav-item');
        const contentSections = document.querySelectorAll('.content-section');
        
        console.log('📋 Navigation items trouvés:', navItems.length);
        console.log('📄 Content sections trouvées:', contentSections.length);
        
        // Nettoyer les event listeners existants
        navItems.forEach((item, index) => {
            const newItem = item.cloneNode(true);
            item.parentNode.replaceChild(newItem, item);
        
        // Récupérer les nouveaux éléments après clonage
        const newNavItems = document.querySelectorAll('.nav-item');
        
        // Vérifier s'il y a une ancre dans l'URL pour ouvrir un onglet spécifique
        let activeTab = 'profile';
        const hash = window.location.hash.substring(1);
        if (hash && document.getElementById(hash)) {
            activeTab = hash;
        } else {
            // Afficher l'onglet sauvegardé ou le premier par défaut
            activeTab = localStorage.getItem('active_settings_tab') || 'profile';
        }
        
        // Fonction pour afficher un onglet
        function showTab(tabId) {
            console.log('👁️ Affichage de l\'onglet:', tabId);
            
            // Retirer la classe active de tous les onglets
            newNavItems.forEach(item => item.classList.remove('active'));
            contentSections.forEach(section => section.classList.remove('active'));
            
            // Ajouter la classe active à l'onglet et section correspondants
            const activeNavItem = document.querySelector(`[data-tab="${tabId}"]`);
            const activeSection = document.getElementById(tabId);
            
            if (activeNavItem && activeSection) {
                activeNavItem.classList.add('active');
                activeSection.classList.add('active');
                console.log('✅ Onglet activé:', tabId);
                
                // Sauvegarder l'onglet actif
                localStorage.setItem('active_settings_tab', tabId);
                
                // Mettre à jour l'URL sans recharger la page
                if (history.replaceState) {
                    history.replaceState(null, null, '#' + tabId);
                }
            } else {
                console.log('❌ Onglet non trouvé:', tabId);
                console.log('activeNavItem:', activeNavItem);
                console.log('activeSection:', activeSection);
            }
        }
        
        // Attacher les event listeners aux nouveaux éléments
        newNavItems.forEach((item, index) => {
            console.log(`🔗 Attachement event listener pour l'item ${index}:`, item.getAttribute('data-tab'));
            
            item.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const tabId = this.getAttribute('data-tab');
                console.log('🖱️ Clic sur onglet:', tabId);
                
                if (tabId) {
                    showTab(tabId);
                } else {
                    console.log('❌ Pas de data-tab trouvé pour cet élément');
                }
            
            // Ajouter un style pour indiquer que l'élément est cliquable
            item.style.cursor = 'pointer';
            item.style.userSelect = 'none';
        
        // Afficher l'onglet initial
        console.log('🎯 Affichage de l\'onglet initial:', activeTab);
        showTab(activeTab);
        
        // Fonction utilitaire pour les notifications
        window.showNotification = function(message, type = 'success') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 16px 24px;
                background: ${type === 'success' ? '#10b981' : '#ef4444'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.3);
                z-index: 9999;
                transform: translateX(400px);
                transition: all 0.3s ease;
                max-width: 300px;
                font-family: inherit;
            `;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            // Animation d'entrée
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Supprimer après 4 secondes
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.parentElement.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        };
        
        console.log('🎉 Navigation des paramètres initialisée avec succès');
    }
    
    // Démarrer le processus d'attente
    waitForContent();
