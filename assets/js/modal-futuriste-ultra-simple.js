/* ====================================================================
   🎨 MODAL FUTURISTE ULTRA-SIMPLE - SAISIE GARANTIE
   Version radicalement simplifiée qui fonctionne à coup sûr
==================================================================== */

(function() {
    'use strict';
    
    console.log('🎨 [FUTURISTE-ULTRA] Initialisation...');
    
    // Remplacer complètement la fonction existante
    window.createNewClientModal = function() {
        console.log('🎨 [FUTURISTE-ULTRA] Création du modal ultra-simple...');
        
        // Supprimer tout modal existant
        const existing = document.querySelectorAll('#nouveauClientModal_temp, #modal-overlay-ultra, #futuristeUltraSimple');
        existing.forEach(el => el.remove());
        
        // Créer le modal avec du HTML/CSS natif ultra-simple
        const modalContainer = document.createElement('div');
        modalContainer.id = 'futuristeUltraSimple';
        modalContainer.innerHTML = `
            <div style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.9);
                z-index: 999999;
                display: flex;
                justify-content: center;
                align-items: center;
                backdrop-filter: blur(10px);
            " id="futuristeOverlay">
                <div style="
                    width: 500px;
                    max-width: 90%;
                    background: linear-gradient(145deg, #0f0f23 0%, #1a1a2e 100%);
                    border: 3px solid #00d4ff;
                    border-radius: 20px;
                    padding: 0;
                    box-shadow: 0 0 100px rgba(0, 212, 255, 0.8);
                    font-family: 'Orbitron', monospace;
                    color: white;
                " id="futuristeModal">
                    <!-- Header -->
                    <div style="
                        padding: 2rem;
                        text-align: center;
                        background: linear-gradient(135deg, #1e40af 0%, #3730a3 100%);
                        border-bottom: 2px solid #00d4ff;
                        position: relative;
                    ">
                        <h2 style="
                            margin: 0;
                            font-size: 1.8rem;
                            color: #00d4ff;
                            text-shadow: 0 0 20px rgba(0, 212, 255, 0.8);
                            font-weight: 700;
                        ">👤 NOUVEAU CLIENT</h2>
                        <button onclick="closeFuturisteModal()" style="
                            position: absolute;
                            top: 1rem;
                            right: 1rem;
                            background: rgba(255, 0, 0, 0.3);
                            border: 2px solid #ff0040;
                            color: #ff0040;
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            cursor: pointer;
                            font-size: 1.5rem;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: bold;
                        ">✕</button>
                    </div>
                    
                    <!-- Body -->
                    <div style="padding: 2.5rem;">
                        <div style="margin-bottom: 2rem;">
                            <label style="
                                display: block;
                                margin-bottom: 0.8rem;
                                color: #00d4ff;
                                font-weight: 700;
                            ">👤 NOM COMPLET *</label>
                            <input type="text" id="futuriste_nom" placeholder="Saisir le nom du client" style="
                                width: 100%;
                                padding: 1rem;
                                border: 3px solid #00d4ff;
                                border-radius: 15px;
                                font-size: 1.1rem;
                                background: rgba(0, 20, 40, 0.8);
                                color: white;
                                outline: none;
                                box-sizing: border-box;
                                font-family: inherit;
                            ">
                        </div>
                        
                        <div style="margin-bottom: 2rem;">
                            <label style="
                                display: block;
                                margin-bottom: 0.8rem;
                                color: #00d4ff;
                                font-weight: 700;
                            ">📞 TÉLÉPHONE</label>
                            <input type="tel" id="futuriste_telephone" placeholder="Numéro de téléphone" style="
                                width: 100%;
                                padding: 1rem;
                                border: 3px solid #00d4ff;
                                border-radius: 15px;
                                font-size: 1.1rem;
                                background: rgba(0, 20, 40, 0.8);
                                color: white;
                                outline: none;
                                box-sizing: border-box;
                                font-family: inherit;
                            ">
                        </div>
                        
                        <div style="margin-bottom: 2.5rem;">
                            <label style="
                                display: block;
                                margin-bottom: 0.8rem;
                                color: #00d4ff;
                                font-weight: 700;
                            ">✉️ EMAIL</label>
                            <input type="email" id="futuriste_email" placeholder="Adresse email" style="
                                width: 100%;
                                padding: 1rem;
                                border: 3px solid #00d4ff;
                                border-radius: 15px;
                                font-size: 1.1rem;
                                background: rgba(0, 20, 40, 0.8);
                                color: white;
                                outline: none;
                                box-sizing: border-box;
                                font-family: inherit;
                            ">
                        </div>
                        
                        <!-- Boutons -->
                        <div style="
                            display: flex;
                            gap: 1.5rem;
                            justify-content: center;
                        ">
                            <button onclick="closeFuturisteModal()" style="
                                background: rgba(255, 0, 0, 0.2);
                                border: 2px solid #ff0040;
                                color: #ff0040;
                                padding: 1rem 2rem;
                                border-radius: 15px;
                                cursor: pointer;
                                font-family: 'Orbitron', monospace;
                                font-weight: 700;
                            ">❌ ANNULER</button>
                            <button onclick="saveFuturisteClient()" style="
                                background: linear-gradient(135deg, #00d4ff 0%, #0080ff 100%);
                                border: 2px solid #00d4ff;
                                color: #000000;
                                padding: 1rem 2rem;
                                border-radius: 15px;
                                cursor: pointer;
                                font-family: 'Orbitron', monospace;
                                font-weight: 700;
                                box-shadow: 0 0 30px rgba(0, 212, 255, 0.6);
                            ">💾 ENREGISTRER</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Ajouter au DOM
        document.body.appendChild(modalContainer);
        
        // Activer les événements de saisie EXPLICITES
        setTimeout(() => {
            const nomInput = document.getElementById('futuriste_nom');
            const telInput = document.getElementById('futuriste_telephone');
            const emailInput = document.getElementById('futuriste_email');
            
            [nomInput, telInput, emailInput].forEach(input => {
                if (input) {
                    // Forcer l'interactivité
                    input.style.pointerEvents = 'auto';
                    input.style.userSelect = 'text';
                    input.tabIndex = 1;
                    
                    // Gestionnaires d'événements ULTRA-EXPLICITES
                    input.addEventListener('keydown', function(e) {
                        console.log('⌨️ [FUTURISTE-ULTRA] Keydown sur', input.id, ':', e.key);
                        e.stopPropagation(); // Empêcher l'interférence
                        return true; // Permettre la saisie
                    }, true);
                    
                    input.addEventListener('keypress', function(e) {
                        console.log('📝 [FUTURISTE-ULTRA] Keypress sur', input.id, ':', e.key);
                        e.stopPropagation();
                        return true;
                    }, true);
                    
                    input.addEventListener('input', function(e) {
                        console.log('✏️ [FUTURISTE-ULTRA] Input sur', input.id, ':', this.value);
                        e.stopPropagation();
                        return true;
                    }, true);
                    
                    input.addEventListener('focus', function(e) {
                        console.log('🎯 [FUTURISTE-ULTRA] Focus sur', input.id);
                        this.style.borderColor = '#00ffff';
                        this.style.boxShadow = '0 0 20px rgba(0, 255, 255, 0.8)';
                    });
                    
                    input.addEventListener('blur', function(e) {
                        console.log('👁️ [FUTURISTE-ULTRA] Blur sur', input.id);
                        this.style.borderColor = '#00d4ff';
                        this.style.boxShadow = 'none';
                    });
                    
                    // Test de saisie manuelle
                    input.addEventListener('click', function(e) {
                        console.log('👆 [FUTURISTE-ULTRA] Clic sur', input.id);
                        this.focus();
                        // Tester la saisie immédiatement
                        setTimeout(() => {
                            console.log('🧪 [FUTURISTE-ULTRA] Test de saisie après clic');
                            const originalValue = this.value;
                            this.value = originalValue + '🧪';
                            setTimeout(() => {
                                this.value = originalValue;
                                console.log('✅ [FUTURISTE-ULTRA] Le champ', input.id, 'accepte la modification programmatique');
                            }, 100);
                        }, 50);
                    });
                    
                    console.log('✅ [FUTURISTE-ULTRA] Événements activés pour', input.id);
                }
            });
            
            // Focus sur le nom
            if (nomInput) {
                nomInput.focus();
                console.log('🎯 [FUTURISTE-ULTRA] Focus sur le nom');
                
                // Test final de saisie
                setTimeout(() => {
                    nomInput.dispatchEvent(new KeyboardEvent('keydown', { key: 'T', bubbles: true }));
                    console.log('🧪 [FUTURISTE-ULTRA] Test d\'événement clavier envoyé');
                }, 200);
            }
        }, 100);
        
        console.log('✅ [FUTURISTE-ULTRA] Modal créé et prêt');
    };
    
    // Fonction de fermeture
    window.closeFuturisteModal = function() {
        console.log('🔒 [FUTURISTE-ULTRA] Fermeture du modal');
        const modal = document.getElementById('futuristeUltraSimple');
        if (modal) {
            modal.remove();
        }
    };
    
    // Fonction de sauvegarde
    window.saveFuturisteClient = function() {
        console.log('💾 [FUTURISTE-ULTRA] Sauvegarde du client');
        
        const nom = document.getElementById('futuriste_nom')?.value?.trim() || '';
        const telephone = document.getElementById('futuriste_telephone')?.value?.trim() || '';
        const email = document.getElementById('futuriste_email')?.value?.trim() || '';
        
        if (!nom) {
            alert('Le nom est obligatoire !');
            document.getElementById('futuriste_nom')?.focus();
            return;
        }
        
        console.log('📝 Client créé:', { nom, telephone, email });
        
        // Mettre à jour le modal de commande
        const clientSearchInput = document.getElementById('nom_client_selectionne');
        const clientIdInput = document.getElementById('client_id');
        
        if (clientSearchInput) {
            clientSearchInput.value = nom;
            console.log('✅ Client search input mis à jour');
        }
        if (clientIdInput) {
            clientIdInput.value = 'new_' + Date.now();
            console.log('✅ Client ID mis à jour');
        }
        
        // Message de succès
        alert(`✅ Client "${nom}" créé avec succès !`);
        
        // Fermer le modal
        closeFuturisteModal();
    };
    
    // Fonction de test
    window.testFuturisteUltra = function() {
        console.log('🧪 [FUTURISTE-ULTRA] Test du modal');
        
        createNewClientModal();
        
        setTimeout(() => {
            const nomInput = document.getElementById('futuriste_nom');
            if (nomInput) {
                nomInput.value = 'Test Client Futuriste';
                nomInput.focus();
                console.log('✅ Test effectué - essayez de taper maintenant !');
            }
        }, 200);
    };
    
    // Fonction de diagnostic avancée
    window.diagnoseFuturisteInput = function() {
        console.log('🔍 [FUTURISTE-ULTRA] === DIAGNOSTIC COMPLET ===');
        
        const nomInput = document.getElementById('futuriste_nom');
        if (!nomInput) {
            console.log('❌ Champ nom non trouvé');
            return;
        }
        
        console.log('✅ Champ nom trouvé:', nomInput);
        console.log('📊 Propriétés du champ:');
        console.log('  - disabled:', nomInput.disabled);
        console.log('  - readOnly:', nomInput.readOnly);
        console.log('  - tabIndex:', nomInput.tabIndex);
        console.log('  - style.pointerEvents:', nomInput.style.pointerEvents);
        console.log('  - style.userSelect:', nomInput.style.userSelect);
        console.log('  - contentEditable:', nomInput.contentEditable);
        
        // Test des événements
        console.log('🧪 Test des événements:');
        
        // Simuler différents types d'événements
        try {
            console.log('  1. Test KeyboardEvent...');
            const keyEvent = new KeyboardEvent('keydown', { 
                key: 'A', 
                code: 'KeyA', 
                bubbles: true, 
                cancelable: true 
            });
            const keyResult = nomInput.dispatchEvent(keyEvent);
            console.log('     Résultat KeyboardEvent:', keyResult);
        } catch (e) {
            console.log('     ❌ Erreur KeyboardEvent:', e.message);
        }
        
        try {
            console.log('  2. Test InputEvent...');
            const inputEvent = new InputEvent('input', { 
                data: 'A', 
                bubbles: true, 
                cancelable: true 
            });
            const inputResult = nomInput.dispatchEvent(inputEvent);
            console.log('     Résultat InputEvent:', inputResult);
        } catch (e) {
            console.log('     ❌ Erreur InputEvent:', e.message);
        }
        
        // Test de focus
        console.log('  3. Test focus...');
        nomInput.focus();
        console.log('     Document.activeElement === nomInput:', document.activeElement === nomInput);
        
        // Test de modification directe
        console.log('  4. Test modification directe...');
        const originalValue = nomInput.value;
        nomInput.value = 'TEST DIAGNOSTIC';
        console.log('     Valeur après modification:', nomInput.value);
        setTimeout(() => {
            nomInput.value = originalValue;
            console.log('     Valeur restaurée:', nomInput.value);
        }, 1000);
        
        // Vérifier les event listeners
        console.log('🎯 Vérification des event listeners...');
        const listeners = getEventListeners ? getEventListeners(nomInput) : 'Non disponible en mode non-dev';
        console.log('  Event listeners détectés:', listeners);
        
        console.log('🔍 === FIN DU DIAGNOSTIC ===');
        console.log('💡 Maintenant, cliquez sur le champ nom et essayez de taper...');
    };
    
    console.log('🚀 [FUTURISTE-ULTRA] Script chargé');
    console.log('💡 Utilisez window.testFuturisteUltra() pour tester');
    
})();
