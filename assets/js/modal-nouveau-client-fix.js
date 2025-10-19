/* ====================================================================
   🔧 CORRECTION MODAL NOUVEAU CLIENT - SAISIE INTERACTIVE
   Résout les problèmes d'interaction avec les champs de saisie
==================================================================== */

(function() {
    'use strict';
    
    console.log('🔧 [NOUVEAU-CLIENT-FIX] Initialisation de la correction...');
    
    /**
     * Créer un modal nouveau client avec z-index raisonnable mais interactif
     */
    function createInteractiveNewClientModal() {
        console.log('👤 [NOUVEAU-CLIENT-FIX] Création du modal interactif...');
        
        // Supprimer tout modal existant
        const existingModal = document.getElementById('nouveauClientModal_interactive');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Créer un overlay avec z-index raisonnable
        const overlay = document.createElement('div');
        overlay.id = 'nouveauClientModal_interactive_overlay';
        overlay.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.85) !important;
            z-index: 15000 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            backdrop-filter: blur(10px) !important;
        `;
        
        // Modal HTML avec z-index raisonnable
        const modalHTML = `
            <div id="nouveauClientModal_interactive" style="
                width: 500px;
                max-width: 95%;
                background: #ffffff;
                border: 2px solid #007bff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                position: relative;
                z-index: 15001 !important;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            ">
                <!-- Header -->
                <div style="
                    padding: 1.5rem;
                    text-align: center;
                    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
                    color: #ffffff;
                    position: relative;
                ">
                    <h3 style="
                        margin: 0;
                        font-size: 1.4rem;
                        font-weight: 600;
                    ">👤 Nouveau Client</h3>
                    <button id="interactive_close" style="
                        position: absolute;
                        top: 1rem;
                        right: 1rem;
                        background: rgba(255, 255, 255, 0.2);
                        border: 1px solid rgba(255, 255, 255, 0.3);
                        color: #ffffff;
                        width: 32px;
                        height: 32px;
                        border-radius: 50%;
                        cursor: pointer;
                        font-size: 1.2rem;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-weight: bold;
                        z-index: 15002 !important;
                    ">×</button>
                </div>
                
                <!-- Body -->
                <div style="
                    padding: 2rem;
                    background: #ffffff;
                    color: #333333;
                ">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="
                            display: block;
                            margin-bottom: 0.5rem;
                            font-weight: 600;
                            font-size: 0.9rem;
                            color: #333333;
                        ">Nom complet *</label>
                        <input type="text" id="interactive_nom" placeholder="Saisir le nom du client" style="
                            width: 100%;
                            padding: 0.75rem;
                            border: 2px solid #dee2e6;
                            border-radius: 8px;
                            font-size: 1rem;
                            box-sizing: border-box;
                            background: #ffffff;
                            color: #333333;
                            outline: none;
                            transition: border-color 0.2s;
                            z-index: 15002 !important;
                            position: relative;
                        ">
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="
                            display: block;
                            margin-bottom: 0.5rem;
                            font-weight: 600;
                            font-size: 0.9rem;
                            color: #333333;
                        ">Téléphone</label>
                        <input type="tel" id="interactive_telephone" placeholder="Numéro de téléphone" style="
                            width: 100%;
                            padding: 0.75rem;
                            border: 2px solid #dee2e6;
                            border-radius: 8px;
                            font-size: 1rem;
                            box-sizing: border-box;
                            background: #ffffff;
                            color: #333333;
                            outline: none;
                            transition: border-color 0.2s;
                            z-index: 15002 !important;
                            position: relative;
                        ">
                    </div>
                    
                    <div style="margin-bottom: 2rem;">
                        <label style="
                            display: block;
                            margin-bottom: 0.5rem;
                            font-weight: 600;
                            font-size: 0.9rem;
                            color: #333333;
                        ">Email</label>
                        <input type="email" id="interactive_email" placeholder="Adresse email" style="
                            width: 100%;
                            padding: 0.75rem;
                            border: 2px solid #dee2e6;
                            border-radius: 8px;
                            font-size: 1rem;
                            box-sizing: border-box;
                            background: #ffffff;
                            color: #333333;
                            outline: none;
                            transition: border-color 0.2s;
                            z-index: 15002 !important;
                            position: relative;
                        ">
                    </div>
                    
                    <!-- Footer -->
                    <div style="
                        display: flex;
                        gap: 1rem;
                        justify-content: flex-end;
                    ">
                        <button id="interactive_cancel" style="
                            background: #6c757d;
                            border: none;
                            color: #ffffff;
                            padding: 0.75rem 1.5rem;
                            border-radius: 8px;
                            cursor: pointer;
                            font-size: 0.9rem;
                            font-weight: 500;
                            z-index: 15002 !important;
                            position: relative;
                            transition: background-color 0.2s;
                        ">Annuler</button>
                        <button id="interactive_save" style="
                            background: #007bff;
                            border: none;
                            color: #ffffff;
                            padding: 0.75rem 1.5rem;
                            border-radius: 8px;
                            cursor: pointer;
                            font-size: 0.9rem;
                            font-weight: 500;
                            z-index: 15002 !important;
                            position: relative;
                            transition: background-color 0.2s;
                        ">Enregistrer</button>
                    </div>
                </div>
            </div>`;
        
        // Injecter l'overlay et le modal
        overlay.innerHTML = modalHTML;
        document.body.appendChild(overlay);
        
        // Récupérer les éléments
        const modal = document.getElementById('nouveauClientModal_interactive');
        const closeBtn = document.getElementById('interactive_close');
        const cancelBtn = document.getElementById('interactive_cancel');
        const saveBtn = document.getElementById('interactive_save');
        const nomInput = document.getElementById('interactive_nom');
        const telInput = document.getElementById('interactive_telephone');
        const emailInput = document.getElementById('interactive_email');
        
        // Fonction de fermeture
        function closeModal() {
            console.log('🔒 [NOUVEAU-CLIENT-FIX] Fermeture du modal');
            overlay.remove();
            document.body.style.overflow = '';
        }
        
        // Événements de fermeture
        closeBtn.onclick = closeModal;
        cancelBtn.onclick = closeModal;
        
        // Fermeture sur clic backdrop
        overlay.onclick = function(e) {
            if (e.target === overlay) {
                closeModal();
            }
        };
        
        // Fermeture sur Escape
        const escapeHandler = function(e) {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
        
        // Améliorer l'interactivité des champs
        [nomInput, telInput, emailInput].forEach(input => {
            if (input) {
                // Focus styles
                input.addEventListener('focus', function() {
                    this.style.borderColor = '#007bff';
                    this.style.boxShadow = '0 0 0 0.2rem rgba(0, 123, 255, 0.25)';
                });
                
                input.addEventListener('blur', function() {
                    this.style.borderColor = '#dee2e6';
                    this.style.boxShadow = 'none';
                });
                
                // Forcer les événements natifs
                input.addEventListener('input', function(e) {
                    console.log(`📝 [NOUVEAU-CLIENT-FIX] Saisie dans ${this.id}:`, this.value);
                });
                
                input.addEventListener('keydown', function(e) {
                    // S'assurer que les événements de clavier fonctionnent
                    e.stopPropagation();
                });
                
                input.addEventListener('keyup', function(e) {
                    e.stopPropagation();
                });
            }
        });
        
        // Améliorer l'interactivité des boutons
        [closeBtn, cancelBtn, saveBtn].forEach(btn => {
            if (btn) {
                btn.addEventListener('mouseenter', function() {
                    this.style.opacity = '0.8';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.opacity = '1';
                });
            }
        });
        
        // Sauvegarde
        saveBtn.onclick = function() {
            const nom = nomInput.value.trim();
            const telephone = telInput.value.trim();
            const email = emailInput.value.trim();
            
            if (!nom) {
                alert('Le nom est obligatoire !');
                nomInput.focus();
                return;
            }
            
            console.log('💾 [NOUVEAU-CLIENT-FIX] Client créé:', { nom, telephone, email });
            
            // Mettre à jour le modal principal
            const clientSearchInput = document.getElementById('nom_client_selectionne');
            const clientIdInput = document.getElementById('client_id');
            
            if (clientSearchInput) {
                clientSearchInput.value = nom;
            }
            if (clientIdInput) {
                clientIdInput.value = 'new_' + Date.now();
            }
            
            // Afficher un message de succès
            const successMsg = document.createElement('div');
            successMsg.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                z-index: 20000;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            `;
            successMsg.textContent = `✅ Client "${nom}" créé avec succès !`;
            document.body.appendChild(successMsg);
            
            setTimeout(() => {
                if (successMsg.parentNode) {
                    successMsg.remove();
                }
            }, 3000);
            
            closeModal();
        };
        
        // Focus automatique sur le premier champ
        setTimeout(() => {
            if (nomInput) {
                nomInput.focus();
                console.log('🎯 [NOUVEAU-CLIENT-FIX] Focus automatique sur le champ nom');
            }
        }, 100);
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
        
        console.log('✅ [NOUVEAU-CLIENT-FIX] Modal interactif créé et prêt');
        
        return {
            modal,
            overlay,
            close: closeModal,
            focusName: () => nomInput?.focus()
        };
    }
    
    /**
     * Remplacer la fonction existante de création de modal
     */
    function interceptNewClientCreation() {
        // Intercepter les clics sur les boutons "nouveau client"
        document.addEventListener('click', function(e) {
            const target = e.target;
            
            // Vérifier si c'est un bouton nouveau client
            if (target && (
                target.id === 'nouveau-client-btn' ||
                target.textContent?.includes('Nouveau client') ||
                target.textContent?.includes('nouveau client') ||
                target.classList?.contains('nouveau-client') ||
                target.closest('[data-action="nouveau-client"]')
            )) {
                console.log('🎯 [NOUVEAU-CLIENT-FIX] Clic intercepté sur bouton nouveau client');
                
                e.preventDefault();
                e.stopPropagation();
                
                // Créer le modal interactif
                createInteractiveNewClientModal();
                
                return false;
            }
        }, true); // Utiliser la phase de capture
    }
    
    /**
     * Fonction globale pour créer le modal manuellement
     */
    window.createInteractiveNewClientModal = createInteractiveNewClientModal;
    
    /**
     * Fonction de test
     */
    window.testInteractiveNewClient = function() {
        console.log('🧪 [NOUVEAU-CLIENT-FIX] Test du modal interactif');
        const modal = createInteractiveNewClientModal();
        
        setTimeout(() => {
            const nomInput = document.getElementById('interactive_nom');
            if (nomInput) {
                nomInput.value = 'Test Client Interactif';
                nomInput.focus();
                console.log('✅ [NOUVEAU-CLIENT-FIX] Test effectué - saisie devrait fonctionner');
            }
        }, 200);
        
        return modal;
    };
    
    /**
     * Initialisation
     */
    function initialize() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(interceptNewClientCreation, 1000);
            });
        } else {
            setTimeout(interceptNewClientCreation, 1000);
        }
    }
    
    // Démarrer l'initialisation
    initialize();
    
    console.log('🚀 [NOUVEAU-CLIENT-FIX] Script de correction chargé');
    console.log('💡 [NOUVEAU-CLIENT-FIX] Utilisez window.testInteractiveNewClient() pour tester');
    
})();
