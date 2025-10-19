// SCRIPT DE PRIORITÉ MAXIMALE - Correction finale pour les pièces multiples
// Ce script doit être chargé en dernier pour écraser tous les autres gestionnaires

console.log('🚀 [PRIORITY-FIX] Chargement du script de priorité maximale pour les pièces multiples...');

// Attendre que le DOM soit complètement chargé ET que tous les autres scripts soient exécutés
document.addEventListener('DOMContentLoaded', function() {
    // Utiliser setTimeout pour s'assurer que ce script s'exécute après tous les autres
    setTimeout(function() {
        console.log('🚀 [PRIORITY-FIX] Initialisation de la correction prioritaire...');
        
        // Variables globales pour éviter les conflits
        let isSubmittingPriority = false;
        let pieceCounterPriority = 1;
        
        // Fonction pour collecter toutes les pièces (version prioritaire)
        function collecterToutesLesPiecesPriority() {
            console.log('🚀 [PRIORITY-FIX] Collecte des pièces...');
            const pieces = [];
            
            // Pièce principale
            const nomPiecePrincipal = document.getElementById('nom_piece')?.value?.trim() || '';
            const quantitePrincipal = document.getElementById('quantite')?.value || '0';
            const prixEstimePrincipal = document.getElementById('prix_estime')?.value || '0';
            const codeBarrePrincipal = document.getElementById('code_barre')?.value?.trim() || '';
            
            if (nomPiecePrincipal && parseInt(quantitePrincipal) > 0 && parseFloat(prixEstimePrincipal) > 0) {
                pieces.push({
                    nom_piece: nomPiecePrincipal,
                    code_barre: codeBarrePrincipal,
                    quantite: parseInt(quantitePrincipal),
                    prix_estime: parseFloat(prixEstimePrincipal)
                });
                console.log('🚀 [PRIORITY-FIX] Pièce principale ajoutée:', pieces[0]);
            }
            
            // Pièces supplémentaires
            const orderGrids = document.querySelectorAll('.order-grid.mt-2');
            console.log(`🚀 [PRIORITY-FIX] Recherche de pièces supplémentaires: ${orderGrids.length} grilles trouvées`);
            
            orderGrids.forEach((grid, index) => {
                const nomPieceInput = grid.querySelector('input[name^="nom_piece_"]');
                const codeBarreInput = grid.querySelector('input[name^="code_barre_"]');
                const quantiteInput = grid.querySelector('input[name^="quantite_"]');
                const prixEstimeInput = grid.querySelector('input[name^="prix_estime_"]');
                
                if (nomPieceInput && quantiteInput && prixEstimeInput) {
                    const nomPiece = nomPieceInput.value?.trim() || '';
                    const quantite = quantiteInput.value || '0';
                    const prixEstime = prixEstimeInput.value || '0';
                    const codeBarre = codeBarreInput?.value?.trim() || '';
                    
                    if (nomPiece && parseInt(quantite) > 0 && parseFloat(prixEstime) > 0) {
                        const piece = {
                            nom_piece: nomPiece,
                            code_barre: codeBarre,
                            quantite: parseInt(quantite),
                            prix_estime: parseFloat(prixEstime)
                        };
                        pieces.push(piece);
                        console.log(`🚀 [PRIORITY-FIX] Pièce supplémentaire ${index + 1} ajoutée:`, piece);
                    }
                }
            });
            
            console.log(`🚀 [PRIORITY-FIX] Total pièces collectées: ${pieces.length}`);
            return pieces;
        }
        
        // Fonction de soumission multiple (version prioritaire)
        function soumettreCommandesMultiplesPriority(informationsCommunes, pieces, modal) {
            console.log(`🚀 [PRIORITY-FIX] Soumission de ${pieces.length} commande(s)`);
            let commandesReussies = 0;
            let commandesEchouees = 0;
            const promesses = [];
            
            pieces.forEach((piece, index) => {
                const formData = new FormData();
                formData.append('client_id', informationsCommunes.client_id);
                formData.append('fournisseur_id', informationsCommunes.fournisseur_id);
                formData.append('statut', informationsCommunes.statut);
                
                if (informationsCommunes.reparation_id) {
                    formData.append('reparation_id', informationsCommunes.reparation_id);
                }
                
                formData.append('nom_piece', piece.nom_piece);
                formData.append('code_barre', piece.code_barre || '');
                formData.append('quantite', piece.quantite);
                formData.append('prix_estime', piece.prix_estime);
                
                const promise = fetch('ajax/simple_commande_no_user.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        commandesReussies++;
                        console.log(`🚀 [PRIORITY-FIX] ✅ Commande ${index + 1}/${pieces.length} ajoutée: ${piece.nom_piece}`);
                    } else {
                        commandesEchouees++;
                        console.error(`🚀 [PRIORITY-FIX] ❌ Erreur commande ${index + 1}/${pieces.length}:`, data.message || data);
                    }
                })
                .catch(err => {
                    commandesEchouees++;
                    console.error(`🚀 [PRIORITY-FIX] ❌ Erreur réseau commande ${index + 1}/${pieces.length}:`, err);
                });
                
                promesses.push(promise);
            });
            
            Promise.all(promesses).then(() => {
                console.log(`🚀 [PRIORITY-FIX] Traitement terminé: ${commandesReussies} succès, ${commandesEchouees} échecs`);
                
                // Réactiver le bouton
                isSubmittingPriority = false;
                const saveBtn = document.getElementById('saveCommandeBtn');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Enregistrer';
                }
                
                if (commandesReussies > 0) {
                    const message = commandesReussies === 1 
                        ? 'Commande ajoutée avec succès !' 
                        : `${commandesReussies} commande(s) ajoutée(s) avec succès !`;
                    
                    // Notification
                    if (typeof showNotification === 'function') {
                        showNotification(message, 'success');
                    } else {
                        alert(message);
                    }
                    
                    // Fermer le modal
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    
                    // Recharger la page
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    alert('Erreur: Aucune commande n\'a pu être créée. Veuillez vérifier vos données et réessayer.');
                }
                
                if (commandesEchouees > 0 && commandesReussies > 0) {
                    alert(`Attention: ${commandesEchouees} commande(s) n'ont pas pu être créées sur ${pieces.length} total.`);
                }
            });
        }
        
        // Fonction de sauvegarde prioritaire
        function saveCommandePriority() {
            console.log('🚀 [PRIORITY-FIX] Début de la sauvegarde prioritaire');
            
            if (isSubmittingPriority) {
                console.log('🚀 [PRIORITY-FIX] Soumission déjà en cours, ignorée');
                return;
            }
            
            isSubmittingPriority = true;
            
            // Collecter toutes les pièces
            const pieces = collecterToutesLesPiecesPriority();
            
            if (pieces.length === 0) {
                alert('Veuillez ajouter au moins une pièce à la commande');
                isSubmittingPriority = false;
                return;
            }
            
            // Vérifier les champs obligatoires
            const clientIdInput = document.getElementById('client_id');
            const fournisseurSelect = document.getElementById('fournisseur_id_ajout');
            const statutRadio = document.querySelector('input[name="statut"]:checked');
            
            const clientId = clientIdInput?.value || '';
            const fournisseurId = fournisseurSelect?.value || '';
            
            console.log('🚀 [PRIORITY-FIX] Validation:', { clientId, fournisseurId, statut: statutRadio?.value });
            
            if (!clientId) {
                alert('Veuillez sélectionner un client');
                isSubmittingPriority = false;
                return;
            }
            if (!fournisseurId) {
                alert('Veuillez sélectionner un fournisseur');
                isSubmittingPriority = false;
                return;
            }
            if (!statutRadio) {
                alert('Veuillez sélectionner un statut');
                isSubmittingPriority = false;
                return;
            }
            
            // Désactiver le bouton
            const saveBtn = document.getElementById('saveCommandeBtn');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...';
            }
            
            // Préparer les informations communes
            const informationsCommunes = {
                client_id: clientId,
                fournisseur_id: fournisseurId,
                statut: statutRadio.value,
                reparation_id: new URLSearchParams(window.location.search).get('id')
            };
            
            // Lancer la soumission
            const modal = document.getElementById('ajouterCommandeModal');
            soumettreCommandesMultiplesPriority(informationsCommunes, pieces, modal);
        }
        
        // ÉCRASER TOUS LES GESTIONNAIRES EXISTANTS
        console.log('🚀 [PRIORITY-FIX] Écrasement des gestionnaires existants...');
        
        // Attendre un peu plus pour être sûr que tous les autres scripts sont chargés
        setTimeout(function() {
            const saveBtn = document.getElementById('saveCommandeBtn');
            if (saveBtn) {
                // Supprimer TOUS les événements existants
                const newBtn = saveBtn.cloneNode(true);
                saveBtn.parentNode.replaceChild(newBtn, saveBtn);
                
                // Ajouter NOTRE gestionnaire
                newBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🚀 [PRIORITY-FIX] Bouton cliqué - Gestionnaire prioritaire');
                    saveCommandePriority();
                });
                
                console.log('🚀 [PRIORITY-FIX] ✅ Gestionnaire prioritaire installé sur le bouton');
            }
            
            // Gérer l'ajout de pièces
            const ajouterPieceBtn = document.getElementById('ajouter-piece-btn');
            if (ajouterPieceBtn) {
                ajouterPieceBtn.addEventListener('click', function() {
                    pieceCounterPriority++;
                    console.log(`🚀 [PRIORITY-FIX] Ajout pièce #${pieceCounterPriority}`);
                    
                    const grid = document.querySelector('.order-grid');
                    if (!grid) return;
                    
                    const wrapper = document.createElement('div');
                    wrapper.className = 'order-grid mt-2';
                    wrapper.innerHTML = `
                        <input type="text" class="form-control" name="nom_piece_${pieceCounterPriority}" placeholder="Désignation de la pièce" required>
                        <input type="text" class="form-control" name="code_barre_${pieceCounterPriority}" placeholder="Code barre">
                        <div class="quantity-selector">
                            <button type="button" class="btn btn-outline-secondary quantity-decrease">–</button>
                            <input type="number" class="form-control text-center" name="quantite_${pieceCounterPriority}" value="1" min="1">
                            <button type="button" class="btn btn-outline-secondary quantity-increase">+</button>
                        </div>
                        <input type="number" class="form-control" name="prix_estime_${pieceCounterPriority}" placeholder="0.00" step="0.01" min="0" required>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-piece-btn" title="Supprimer cette pièce">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    
                    // Événements pour les boutons
                    const decreaseBtn = wrapper.querySelector('.quantity-decrease');
                    const increaseBtn = wrapper.querySelector('.quantity-increase');
                    const quantityInput = wrapper.querySelector(`input[name="quantite_${pieceCounterPriority}"]`);
                    const removeBtn = wrapper.querySelector('.remove-piece-btn');
                    
                    decreaseBtn.addEventListener('click', function() {
                        const currentValue = parseInt(quantityInput.value) || 1;
                        if (currentValue > 1) {
                            quantityInput.value = currentValue - 1;
                        }
                    });
                    
                    increaseBtn.addEventListener('click', function() {
                        const currentValue = parseInt(quantityInput.value) || 1;
                        quantityInput.value = currentValue + 1;
                    });
                    
                    removeBtn.addEventListener('click', function() {
                        wrapper.remove();
                        console.log(`🚀 [PRIORITY-FIX] Pièce #${pieceCounterPriority} supprimée`);
                    });
                    
                    grid.parentNode.appendChild(wrapper);
                    
                    // Focus sur le nouveau champ
                    const newNameInput = wrapper.querySelector(`input[name="nom_piece_${pieceCounterPriority}"]`);
                    if (newNameInput) {
                        setTimeout(() => newNameInput.focus(), 100);
                    }
                });
                
                console.log('🚀 [PRIORITY-FIX] ✅ Gestionnaire ajout de pièces installé');
            }
            
        }, 500); // Attendre encore plus pour être sûr
        
    }, 100); // Attendre que tous les autres scripts soient exécutés
});

console.log('🚀 [PRIORITY-FIX] Script de priorité maximale chargé');
