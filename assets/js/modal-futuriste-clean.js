/* ====================================================================
   🎨 MODAL FUTURISTE CLEAN - VERSION SANS INTERFÉRENCES
   Supprime TOUS les événements automatiques qui causent focus/blur
==================================================================== */

(function() {
    'use strict';
    
    console.log('🧹 [FUTURISTE-CLEAN] ✅ NOUVEAU SCRIPT CLEAN CHARGÉ - Ancien script supprimé');
    
    // Fonction ultra-propre sans aucun événement automatique
    window.createNewClientModal = function() {
        console.log('🎨 [FUTURISTE-CLEAN] Création modal ultra-propre...');
        
        // Supprimer tout modal existant  
        const existing = document.querySelectorAll('#nouveauClientModal_temp, #modal-overlay-ultra, #futuristeUltraSimple, #futuristeClean');
        existing.forEach(el => el.remove());
        
        // HTML ultra-simple sans événements avec classes pour le theming
        const modalHTML = `
            <div id="futuristeClean" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 2000;
                display: flex;
                justify-content: center;
                align-items: center;
            ">
                <div class="modal-container" style="
                    width: 500px;
                    max-width: 90%;
                    border-radius: 20px;
                    padding: 0;
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                ">
                    <!-- Header -->
                    <div class="modal-header" style="
                        padding: 2rem;
                        text-align: center;
                        position: relative;
                        border-radius: 20px 20px 0 0;
                    ">
                        <h2 class="modal-title" style="
                            margin: 0;
                            font-size: 1.8rem;
                            font-weight: 700;
                            letter-spacing: 0.5px;
                        ">👤 NOUVEAU CLIENT</h2>
                        <button onclick="closeCleanModal()" class="modal-close" style="
                            cursor: pointer;
                            transition: all 0.2s ease;
                        ">✕</button>
                    </div>
                    
                    <!-- Body -->
                    <div style="padding: 2.5rem;">
                        <div style="margin-bottom: 2rem;">
                            <label class="modal-label" style="
                                display: block;
                                margin-bottom: 0.8rem;
                                font-size: 0.875rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            ">👤 NOM *</label>
                            <input type="text" id="clean_nom" class="modal-input" placeholder="Nom de famille" style="
                                width: 100%;
                                padding: 1rem;
                                border-radius: 12px;
                                font-size: 1rem;
                                outline: none;
                                box-sizing: border-box;
                                font-family: inherit;
                                position: relative;
                                z-index: 2001;
                                transition: all 0.2s ease;
                            ">
                        </div>
                        
                        <div style="margin-bottom: 2rem;">
                            <label class="modal-label" style="
                                display: block;
                                margin-bottom: 0.8rem;
                                font-size: 0.875rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            ">👤 PRÉNOM *</label>
                            <input type="text" id="clean_prenom" class="modal-input" placeholder="Prénom" style="
                                width: 100%;
                                padding: 1rem;
                                border-radius: 12px;
                                font-size: 1rem;
                                outline: none;
                                box-sizing: border-box;
                                font-family: inherit;
                                position: relative;
                                z-index: 2001;
                                transition: all 0.2s ease;
                            ">
                        </div>
                        
                        <div style="margin-bottom: 2.5rem;">
                            <label class="modal-label" style="
                                display: block;
                                margin-bottom: 0.8rem;
                                font-size: 0.875rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            ">📞 TÉLÉPHONE * (11 chiffres obligatoire)</label>
                            <input type="tel" id="clean_telephone" class="modal-input" placeholder="Exemple : 33782962901" maxlength="11" pattern="[0-9]{11}" style="
                                width: 100%;
                                padding: 1rem;
                                border-radius: 12px;
                                font-size: 1rem;
                                outline: none;
                                box-sizing: border-box;
                                font-family: inherit;
                                position: relative;
                                z-index: 2001;
                                transition: all 0.2s ease;
                            ">
                        </div>
                        
                        <!-- Boutons -->
                        <div style="
                            display: flex;
                            gap: 1rem;
                            justify-content: flex-end;
                            margin-top: 2rem;
                        ">
                            <button onclick="closeCleanModal()" class="modal-btn-cancel" style="
                                padding: 0.875rem 1.5rem;
                                border-radius: 12px;
                                cursor: pointer;
                                font-family: inherit;
                                font-weight: 600;
                                font-size: 0.875rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                                transition: all 0.2s ease;
                            ">ANNULER</button>
                            <button onclick="saveCleanClient()" class="modal-btn-save" style="
                                padding: 0.875rem 2rem;
                                border-radius: 12px;
                                cursor: pointer;
                                font-family: inherit;
                                font-weight: 600;
                                font-size: 0.875rem;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                                transition: all 0.2s ease;
                            ">💾 ENREGISTRER</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Ajouter au DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Focus simple SANS événements automatiques - Focus garanti
        setTimeout(() => {
            const nomInput = document.getElementById('clean_nom');
            if (nomInput) {
                // Focus simple sans aucun événement lié
                nomInput.focus();
                console.log('✅ [FUTURISTE-CLEAN] Modal créé, focus sur nom - AUCUN ÉVÉNEMENT AUTOMATIQUE');
                
                // Vérifier que le focus reste actif
                setTimeout(() => {
                    if (document.activeElement === nomInput) {
                        console.log('🎯 [FUTURISTE-CLEAN] Focus maintenu sur le champ nom');
                    } else {
                        console.log('⚠️ [FUTURISTE-CLEAN] Focus perdu, tentative de restauration...');
                        nomInput.focus();
                    }
                }, 500);
            }
        }, 200);
    };
    
    // Fonction de fermeture simple
    window.closeCleanModal = function() {
        console.log('🔒 [FUTURISTE-CLEAN] Fermeture');
        const modal = document.getElementById('futuristeClean');
        if (modal) {
            modal.remove();
        }
    };
    
    // Fonction de sauvegarde simple
    window.saveCleanClient = function() {
        console.log('💾 [FUTURISTE-CLEAN] Sauvegarde');
        
        const nom = document.getElementById('clean_nom')?.value?.trim() || '';
        const prenom = document.getElementById('clean_prenom')?.value?.trim() || '';
        const telephone = document.getElementById('clean_telephone')?.value?.trim() || '';
        
        // Validation des champs obligatoires
        if (!nom) {
            alert('❌ Le nom est obligatoire !');
            document.getElementById('clean_nom')?.focus();
            return;
        }
        
        if (!prenom) {
            alert('❌ Le prénom est obligatoire !');
            document.getElementById('clean_prenom')?.focus();
            return;
        }
        
        if (!telephone) {
            alert('❌ Le téléphone est obligatoire !');
            document.getElementById('clean_telephone')?.focus();
            return;
        }
        
        // Validation format téléphone (11 chiffres)
        if (!/^[0-9]{11}$/.test(telephone)) {
            alert('❌ Le téléphone doit contenir exactement 11 chiffres !\\nExemple : 33782962901');
            document.getElementById('clean_telephone')?.focus();
            return;
        }
        
        console.log('📝 Client à sauvegarder:', { nom, prenom, telephone });
        
        // Sauvegarder le client dans la base de données via AJAX
        const formData = new FormData();
        formData.append('nom', nom);
        formData.append('prenom', prenom);
        formData.append('telephone', telephone);
        formData.append('action', 'ajouter_client');
        
        console.log('🔄 [FUTURISTE-CLEAN] Envoi des données au serveur...');
        
        fetch('ajax/ajouter_client.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('📥 [FUTURISTE-CLEAN] Réponse du serveur:', data);
            
            if (data.success) {
                const nomComplet = `${prenom} ${nom}`;
                
                // Mettre à jour le modal de commande avec le vrai ID client
                const clientSearchInput = document.getElementById('nom_client_selectionne');
                const clientIdInput = document.getElementById('client_id');
                
                if (clientSearchInput) {
                    clientSearchInput.value = nomComplet;
                }
                if (clientIdInput) {
                    clientIdInput.value = data.client_id;
                }
                
                alert(`✅ Client "${nomComplet}" créé avec succès dans la base de données !\\n📞 Téléphone : ${telephone}\\n🆔 ID : ${data.client_id}`);
                closeCleanModal();
            } else {
                alert(`❌ Erreur lors de la création du client :\\n${data.message || 'Erreur inconnue'}`);
                console.error('❌ [FUTURISTE-CLEAN] Erreur serveur:', data);
            }
        })
        .catch(error => {
            console.error('❌ [FUTURISTE-CLEAN] Erreur AJAX:', error);
            alert(`❌ Erreur de connexion au serveur :\\n${error.message}`);
        });
    };
    
    // Test simple
    window.testCleanModal = function() {
        console.log('🧪 [FUTURISTE-CLEAN] Test du modal CLEAN');
        createNewClientModal();
        
        setTimeout(() => {
            const nomInput = document.getElementById('clean_nom');
            const prenomInput = document.getElementById('clean_prenom');
            const telephoneInput = document.getElementById('clean_telephone');
            
            if (nomInput && prenomInput && telephoneInput) {
                nomInput.value = 'DUPONT';
                prenomInput.value = 'Jean';
                telephoneInput.value = '33782962901';
                nomInput.focus();
                console.log('✅ Modal créé avec valeurs de test');
                console.log('🎯 Nom: DUPONT, Prénom: Jean, Téléphone: 33782962901');
                console.log('📝 Testez maintenant la saisie dans les champs !');
            }
        }, 300);
    };
    
    // Fonction de diagnostic instantané
    window.diagnoseCleanModal = function() {
        console.log('🔬 [FUTURISTE-CLEAN] === DIAGNOSTIC INSTANTANÉ ===');
        console.log('🔍 Fonction createNewClientModal:', typeof window.createNewClientModal);
        console.log('🔍 Modal actuel présent:', !!document.getElementById('futuristeClean'));
        
        if (document.getElementById('futuristeClean')) {
            const nomInput = document.getElementById('clean_nom');
            console.log('🔍 Champ nom présent:', !!nomInput);
            console.log('🔍 Champ nom a le focus:', document.activeElement === nomInput);
            console.log('🔍 Valeur actuelle:', nomInput?.value || 'vide');
        }
        
        console.log('🔍 === FIN DIAGNOSTIC ===');
    };
    
    // Protection anti-écrasement de la fonction
    Object.defineProperty(window, 'createNewClientModal', {
        writable: false,
        configurable: false
    });
    
    console.log('🧹 [FUTURISTE-CLEAN] ✅ Version propre chargée - SANS ÉVÉNEMENTS AUTOMATIQUES');
    console.log('🛡️ [FUTURISTE-CLEAN] ✅ Fonction protégée contre l\'écrasement par d\'autres scripts');
    console.log('💡 Utilisez window.testCleanModal() pour tester');
    console.log('🎯 Cette version élimine les focus/blur automatiques qui causaient les problèmes');
    
})();








