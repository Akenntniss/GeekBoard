/**
 * Système de modal de remplacement pour contourner les problèmes d'affichage
 * Remplace temporairement les modals problématiques par des versions fonctionnelles
 */

(function() {
    'use strict';
    
    console.log('🔄 Chargement du système de modal de remplacement...');
    
    // Fonction pour créer un modal de remplacement
    function createReplacementModal(originalModalId, title, content) {
        // Supprimer l'ancien modal s'il existe
        const existingModal = document.getElementById(originalModalId + '_replacement');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Créer le nouveau modal
        const modalHTML = `
            <div id="${originalModalId}_replacement" class="modal-replacement" style="
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 99999;
                justify-content: center;
                align-items: center;
            ">
                <div class="modal-replacement-content" style="
                    background: white;
                    border-radius: 8px;
                    max-width: 90%;
                    max-height: 90%;
                    overflow-y: auto;
                    position: relative;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                ">
                    <div class="modal-replacement-header" style="
                        padding: 20px;
                        border-bottom: 1px solid #dee2e6;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        background: linear-gradient(135deg, #1e90ff 0%, #0066cc 100%);
                        color: white;
                        border-radius: 8px 8px 0 0;
                    ">
                        <h5 style="margin: 0; font-weight: 600;">${title}</h5>
                        <button type="button" class="modal-replacement-close" style="
                            background: none;
                            border: none;
                            font-size: 24px;
                            color: white;
                            cursor: pointer;
                            padding: 0;
                            width: 30px;
                            height: 30px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            border-radius: 50%;
                            transition: background-color 0.3s;
                        " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">&times;</button>
                    </div>
                    <div class="modal-replacement-body" style="padding: 20px;">
                        ${content}
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        const replacementModal = document.getElementById(originalModalId + '_replacement');
        
        // Gérer la fermeture
        const closeBtn = replacementModal.querySelector('.modal-replacement-close');
        closeBtn.addEventListener('click', () => {
            replacementModal.style.display = 'none';
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        
        // Fermer en cliquant sur le backdrop
        replacementModal.addEventListener('click', (e) => {
            if (e.target === replacementModal) {
                replacementModal.style.display = 'none';
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
            }
        
        // Fermer avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && replacementModal.style.display === 'flex') {
                replacementModal.style.display = 'none';
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
            }
        
        return replacementModal;
    }
    
    // Contenu pour le modal de mise à jour des statuts
    const updateStatusContent = `
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Mise à jour des statuts par lots</strong><br>
            Sélectionnez les réparations et le nouveau statut à appliquer.
        </div>
        
        <div class="mb-3">
            <label class="form-label">Sélectionner les réparations à mettre à jour:</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="selectAllRepairs">
                <label class="form-check-label" for="selectAllRepairs">
                    Sélectionner toutes les réparations visibles
                </label>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="batchStatusSelect" class="form-label">Nouveau statut:</label>
            <select class="form-select" id="batchStatusSelect">
                <option value="">Sélectionner un statut...</option>
                <option value="en_attente">En attente</option>
                <option value="en_cours">En cours</option>
                <option value="reparation_effectue">Réparation effectuée</option>
                <option value="pret_a_recuperer">Prêt à récupérer</option>
                <option value="recupere">Récupéré</option>
                <option value="annule">Annulé</option>
            </select>
        </div>
        
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary modal-replacement-close">Annuler</button>
            <button type="button" class="btn btn-primary" onclick="alert('Fonctionnalité de mise à jour des statuts à implémenter')">
                <i class="fas fa-save me-1"></i>Mettre à jour
            </button>
        </div>
    `;
    
    // Contenu pour le modal de relance client
    const relanceClientContent = `
        <div class="alert alert-info mb-3">
            <i class="fas fa-bell me-2"></i>
            <strong>Relance des clients</strong><br>
            Envoyez des SMS de relance aux clients dont les réparations sont terminées.
        </div>
        
        <div class="mb-3">
            <label class="form-label">Types de réparations à relancer:</label>
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="relanceDevisAttente" checked>
                        <label class="form-check-label" for="relanceDevisAttente">
                            <i class="fas fa-clock text-warning me-1"></i>
                            Devis en attente
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="relanceReparationTerminee" checked>
                        <label class="form-check-label" for="relanceReparationTerminee">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            Réparation terminée
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="relanceDelai" class="form-label">Délai minimum (jours):</label>
            <input type="number" class="form-control" id="relanceDelai" value="3" min="1">
            <small class="text-muted">Relancer les réparations datant d'au moins X jours.</small>
        </div>
        
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary modal-replacement-close">Annuler</button>
            <button type="button" class="btn btn-primary" onclick="alert('Fonctionnalité de relance client à implémenter')">
                <i class="fas fa-paper-plane me-1"></i>Rechercher les clients
            </button>
        </div>
    `;
    
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            console.log('🔄 Création des modals de remplacement...');
            
            // Créer les modals de remplacement
            const updateStatusModal = createReplacementModal(
                'updateStatusModal'
                '📊 Mise à jour des statuts par lots'
                updateStatusContent
            );
            
            const relanceClientModal = createReplacementModal(
                'relanceClientModal'
                '📱 Relance des clients'
                relanceClientContent
            );
            
            // Remplacer les event listeners des boutons
            const updateStatusBtn = document.querySelector('button[data-bs-target="#updateStatusModal"]');
            const relanceClientBtn = document.querySelector('button[data-bs-target="#relanceClientModal"]');
            
            if (updateStatusBtn) {
                // Supprimer les anciens event listeners
                const newUpdateBtn = updateStatusBtn.cloneNode(true);
                updateStatusBtn.parentNode.replaceChild(newUpdateBtn, updateStatusBtn);
                
                newUpdateBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🔄 Ouverture du modal de mise à jour des statuts (remplacement)');
                    updateStatusModal.style.display = 'flex';
                    document.body.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';
                
                console.log('✅ Bouton mise à jour statut remplacé');
            }
            
            if (relanceClientBtn) {
                // Supprimer les anciens event listeners
                const newRelanceBtn = relanceClientBtn.cloneNode(true);
                relanceClientBtn.parentNode.replaceChild(newRelanceBtn, relanceClientBtn);
                
                newRelanceBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🔄 Ouverture du modal de relance client (remplacement)');
                    relanceClientModal.style.display = 'flex';
                    document.body.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';
                
                console.log('✅ Bouton relance client remplacé');
            }
            
            console.log('✅ Système de modal de remplacement activé');
            
        }, 1000);
    
})();
