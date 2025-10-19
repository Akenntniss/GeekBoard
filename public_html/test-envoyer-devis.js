// Script de test spécifique pour le bouton "Envoyer le devis"
console.log('🧪 [TEST-ENVOYER] Script de test du bouton Envoyer le devis chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🧪 [TEST-ENVOYER] === DIAGNOSTIC COMPLET DU BOUTON ENVOYER ===');
    
    // Fonction pour diagnostiquer le bouton
    window.diagnosticEnvoyerDevis = function() {
        console.log('🔍 [DIAGNOSTIC] === ANALYSE COMPLÈTE ===');
        
        const createBtn = document.getElementById('creerEtEnvoyer');
        
        console.log('📋 [DIAGNOSTIC] Bouton envoyer:', {
            existe: !!createBtn,
            visible: createBtn ? createBtn.offsetParent !== null : false,
            disabled: createBtn ? createBtn.disabled : 'N/A',
            styles: createBtn ? {
                display: getComputedStyle(createBtn).display,
                visibility: getComputedStyle(createBtn).visibility,
                opacity: getComputedStyle(createBtn).opacity,
                pointerEvents: getComputedStyle(createBtn).pointerEvents
            } : 'N/A',
            classes: createBtn ? createBtn.className : 'N/A',
            text: createBtn ? createBtn.textContent.trim() : 'N/A',
            hasModernEvent: createBtn ? createBtn.hasAttribute('data-modern-event') : false
        });
        
        console.log('🔧 [DIAGNOSTIC] DevisManager:', {
            existe: !!window.devisManager,
            sauvegarderDevis: window.devisManager ? typeof window.devisManager.sauvegarderDevis : 'N/A'
        });
        
        console.log('🔧 [DIAGNOSTIC] Gestionnaire moderne:', {
            existe: !!window.modernDevisModalManager,
            methodes: window.modernDevisModalManager ? [
                'forceShowFinalButtons',
                'ensureButtonEvents',
                'updateNavigationButtons'
            ].map(method => ({
                [method]: typeof window.modernDevisModalManager[method]
            })) : 'N/A'
        });
        
        // Vérifier les événements
        if (createBtn) {
            const events = getEventListeners ? getEventListeners(createBtn) : 'Fonction getEventListeners non disponible (uniquement dans DevTools)';
            console.log('🎯 [DIAGNOSTIC] Événements attachés:', events);
        }
        
        return createBtn;
    };
    
    // Fonction pour forcer la réparation du bouton
    window.reparerBoutonEnvoyer = function() {
        console.log('🔧 [RÉPARATION] === RÉPARATION FORCÉE DU BOUTON ===');
        
        const createBtn = document.getElementById('creerEtEnvoyer');
        
        if (!createBtn) {
            console.error('❌ [RÉPARATION] Bouton introuvable');
            return false;
        }
        
        // Étape 1: Supprimer tous les événements existants
        const newBtn = createBtn.cloneNode(true);
        createBtn.parentNode.replaceChild(newBtn, createBtn);
        console.log('✅ [RÉPARATION] Bouton cloné pour supprimer les anciens événements');
        
        // Étape 2: Forcer l'affichage
        newBtn.style.display = 'inline-flex';
        newBtn.style.visibility = 'visible';
        newBtn.style.opacity = '1';
        newBtn.style.pointerEvents = 'auto';
        newBtn.disabled = false;
        newBtn.classList.remove('d-none');
        newBtn.classList.add('force-show');
        console.log('✅ [RÉPARATION] Styles forcés');
        
        // Étape 3: Ajouter un événement de test simple
        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🎯 [RÉPARATION] Bouton cliqué! Événement capturé');
            
            if (window.devisManager && typeof window.devisManager.sauvegarderDevis === 'function') {
                console.log('✅ [RÉPARATION] DevisManager trouvé, appel de sauvegarderDevis');
                try {
                    window.devisManager.sauvegarderDevis('envoyer');
                    console.log('✅ [RÉPARATION] Fonction sauvegarderDevis appelée avec succès');
                } catch (error) {
                    console.error('❌ [RÉPARATION] Erreur lors de l\'appel de sauvegarderDevis:', error);
                }
            } else {
                console.error('❌ [RÉPARATION] DevisManager non disponible');
                alert('Erreur: Gestionnaire de devis non disponible');
            }
        });
        
        newBtn.setAttribute('data-test-event', 'true');
        console.log('✅ [RÉPARATION] Événement de test ajouté');
        
        return newBtn;
    };
    
    // Fonction pour tester le clic direct
    window.testerClicEnvoyer = function() {
        console.log('🧪 [TEST-CLIC] === TEST DE CLIC DIRECT ===');
        
        const createBtn = document.getElementById('creerEtEnvoyer');
        
        if (!createBtn) {
            console.error('❌ [TEST-CLIC] Bouton introuvable');
            return;
        }
        
        console.log('🎯 [TEST-CLIC] Simulation de clic...');
        
        // Test 1: Event programmatique
        const clickEvent = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
            view: window
        });
        
        createBtn.dispatchEvent(clickEvent);
        console.log('✅ [TEST-CLIC] Événement click dispatché');
        
        // Test 2: Clic direct
        setTimeout(() => {
            console.log('🎯 [TEST-CLIC] Appel direct de click()');
            createBtn.click();
        }, 500);
    };
    
    console.log('✅ [TEST-ENVOYER] Fonctions de test disponibles:');
    console.log('  - diagnosticEnvoyerDevis() : Diagnostic complet du bouton');
    console.log('  - reparerBoutonEnvoyer() : Réparation forcée du bouton');
    console.log('  - testerClicEnvoyer() : Test de clic direct');
    
    // Diagnostic automatique après 2 secondes
    setTimeout(() => {
        console.log('🔄 [AUTO-DIAGNOSTIC] Diagnostic automatique...');
        window.diagnosticEnvoyerDevis();
    }, 2000);
});



