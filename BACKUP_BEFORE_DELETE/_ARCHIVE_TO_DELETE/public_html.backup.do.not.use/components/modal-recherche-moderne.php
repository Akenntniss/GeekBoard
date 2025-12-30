<!-- MODAL DE RECHERCHE MODERNE - SANS BOOTSTRAP -->
<div class="recherche-modal-overlay" id="rechercheModalModerne" style="display: none;">
    <div class="recherche-modal">
        <!-- Header -->
        <div class="recherche-modal-header">
            <h3 class="recherche-modal-title">
                <i class="fas fa-search"></i>
                Recherche Universelle
            </h3>
            <button class="recherche-modal-close" id="rechercheModalClose">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Barre de recherche -->
        <div class="recherche-input-section">
            <div class="recherche-input-container">
                <input 
                    type="text" 
                    class="recherche-input" 
                    id="rechercheInputModerne" 
                    placeholder="Tapez votre recherche..."
                    autocomplete="off"
                >
                <button class="recherche-btn" id="rechercheBtnModerne">
                    <i class="fas fa-search"></i>
                    Rechercher
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div class="recherche-loading" id="rechercheLoading" style="display: none;">
            <div class="loading-spinner"></div>
            <p>Recherche en cours...</p>
        </div>

        <!-- Onglets -->
        <div class="recherche-tabs" id="rechercheTabs" style="display: none;">
            <button class="tab-btn active" data-tab="reparations">
                <i class="fas fa-tools"></i>
                Réparations
                <span class="tab-count" id="reparationsCount">0</span>
            </button>
            <button class="tab-btn" data-tab="clients">
                <i class="fas fa-users"></i>
                Clients
                <span class="tab-count" id="clientsCount">0</span>
            </button>
            <button class="tab-btn" data-tab="commandes">
                <i class="fas fa-shopping-cart"></i>
                Commandes
                <span class="tab-count" id="commandesCount">0</span>
            </button>
        </div>

        <!-- Contenu des résultats -->
        <div class="recherche-content" id="rechercheContent" style="display: none;">
            
            <!-- Onglet Réparations -->
            <div class="tab-content active" id="tab-reparations">
                <div class="results-header">
                    <h4><i class="fas fa-tools"></i> Réparations</h4>
                </div>
                <div class="results-list" id="reparationsList">
                    <!-- Les résultats seront insérés ici -->
                </div>
            </div>

            <!-- Onglet Clients -->
            <div class="tab-content" id="tab-clients">
                <div class="results-header">
                    <h4><i class="fas fa-users"></i> Clients</h4>
                </div>
                <div class="results-list" id="clientsList">
                    <!-- Les résultats seront insérés ici -->
                </div>
            </div>

            <!-- Onglet Commandes -->
            <div class="tab-content" id="tab-commandes">
                <div class="results-header">
                    <h4><i class="fas fa-shopping-cart"></i> Commandes</h4>
                </div>
                <div class="results-list" id="commandesList">
                    <!-- Les résultats seront insérés ici -->
                </div>
            </div>


        </div>

        <!-- Message vide -->
        <div class="recherche-empty" id="rechercheEmpty" style="display: none;">
            <div class="empty-icon">
                <i class="fas fa-search"></i>
            </div>
            <h4>Aucun résultat trouvé</h4>
            <p>Essayez avec d'autres mots-clés</p>
        </div>

    </div>
</div>

<!-- CSS du modal moderne avec support thème adaptatif -->
<style>
/* ====================================================================
   MODAL DE RECHERCHE MODERNE - THÈME ADAPTATIF
   Mode Clair: Design professionnel harmonieux
   Mode Sombre: Design futuriste avec effets néon
==================================================================== */

/* Variables CSS pour le thème clair (par défaut) */
:root {
    --modal-overlay-bg: rgba(0, 0, 0, 0.5);
    --modal-bg: #ffffff;
    --modal-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    --modal-border: #e9ecef;
    
    --header-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --header-text: #ffffff;
    
    --input-section-bg: #f8f9fa;
    --input-bg: #ffffff;
    --input-border: #e9ecef;
    --input-focus-border: #667eea;
    --input-focus-shadow: rgba(102, 126, 234, 0.1);
    --input-text: #495057;
    
    --btn-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --btn-text: #ffffff;
    --btn-hover-shadow: rgba(102, 126, 234, 0.3);
    
    --tabs-bg: #f8f9fa;
    --tab-text: #6c757d;
    --tab-text-hover: #495057;
    --tab-active-text: #667eea;
    --tab-active-border: #667eea;
    --tab-hover-bg: rgba(102, 126, 234, 0.1);
    
    --content-bg: #ffffff;
    --text-primary: #495057;
    --text-secondary: #6c757d;
    --text-muted: #dee2e6;
    
    --result-bg: #ffffff;
    --result-border: #e9ecef;
    --result-hover-border: #667eea;
    --result-hover-shadow: rgba(102, 126, 234, 0.1);
    
    --spinner-border: #e9ecef;
    --spinner-active: #667eea;
}

/* Variables CSS pour le mode clair (appliquées explicitement) */
.recherche-modal-overlay:not(.dark-mode) {
    --modal-overlay-bg: rgba(0, 0, 0, 0.5);
    --modal-bg: #ffffff;
    --modal-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    --modal-border: #e9ecef;
    
    --header-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --header-text: #ffffff;
    
    --input-section-bg: #f8f9fa;
    --input-bg: #ffffff;
    --input-border: #e9ecef;
    --input-focus-border: #667eea;
    --input-focus-shadow: rgba(102, 126, 234, 0.1);
    --input-text: #495057;
    
    --btn-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --btn-text: #ffffff;
    --btn-hover-shadow: rgba(102, 126, 234, 0.3);
    
    --tabs-bg: #f8f9fa;
    --tab-text: #6c757d;
    --tab-text-hover: #495057;
    --tab-active-text: #667eea;
    --tab-active-border: #667eea;
    --tab-hover-bg: rgba(102, 126, 234, 0.1);
    
    --content-bg: #ffffff;
    --text-primary: #495057;
    --text-secondary: #6c757d;
    --text-muted: #dee2e6;
    
    --result-bg: #ffffff;
    --result-border: #e9ecef;
    --result-hover-border: #667eea;
    --result-hover-shadow: rgba(102, 126, 234, 0.1);
    
    --spinner-border: #e9ecef;
    --spinner-active: #667eea;
}

/* Variables CSS pour le thème sombre */
.recherche-modal-overlay.dark-mode {
    --modal-overlay-bg: rgba(0, 0, 0, 0.8);
    --modal-bg: #1a1a2e;
    --modal-shadow: 0 20px 60px rgba(0, 255, 255, 0.2);
    --modal-border: #16213e;
    
    --header-bg: linear-gradient(135deg, #0f3460 0%, #16213e 50%, #0f3460 100%);
    --header-text: #00ffff;
    
    --input-section-bg: #16213e;
    --input-bg: #0f3460;
    --input-border: #00ffff;
    --input-focus-border: #00ffff;
    --input-focus-shadow: rgba(0, 255, 255, 0.3);
    --input-text: #ffffff;
    
    --btn-bg: linear-gradient(135deg, #00ffff 0%, #0099cc 100%);
    --btn-text: #0f3460;
    --btn-hover-shadow: rgba(0, 255, 255, 0.5);
    
    --tabs-bg: #16213e;
    --tab-text: #8892b0;
    --tab-text-hover: #ccd6f6;
    --tab-active-text: #00ffff;
    --tab-active-border: #00ffff;
    --tab-hover-bg: rgba(0, 255, 255, 0.1);
    
    --content-bg: #1a1a2e;
    --text-primary: #ccd6f6;
    --text-secondary: #8892b0;
    --text-muted: #495670;
    
    --result-bg: #16213e;
    --result-border: #0f3460;
    --result-hover-border: #00ffff;
    --result-hover-shadow: rgba(0, 255, 255, 0.2);
    
    --spinner-border: #16213e;
    --spinner-active: #00ffff;
}

/* Overlay du modal */
.recherche-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--modal-overlay-bg);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 1500;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all 0.3s ease;
}

.recherche-modal-overlay.show {
    opacity: 1;
}

/* Modal principal */
.recherche-modal {
    background: var(--modal-bg);
    border: 1px solid var(--modal-border);
    border-radius: 20px;
    width: 90%;
    max-width: 1200px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: var(--modal-shadow);
    transform: scale(0.9) translateY(20px);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.recherche-modal-overlay.dark-mode .recherche-modal {
    box-shadow: 
        0 0 30px rgba(0, 255, 255, 0.3),
        0 20px 60px rgba(0, 0, 0, 0.8),
        inset 0 1px 0 rgba(0, 255, 255, 0.2);
}

.recherche-modal-overlay.show .recherche-modal {
    transform: scale(1) translateY(0);
}

/* Header */
.recherche-modal-header {
    padding: 25px 30px;
    background: var(--header-bg);
    color: var(--header-text);
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
}

.recherche-modal-overlay.dark-mode .recherche-modal-header {
    border-bottom: 1px solid rgba(0, 255, 255, 0.3);
    box-shadow: 0 0 20px rgba(0, 255, 255, 0.2);
}

.recherche-modal-overlay.dark-mode .recherche-modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.1), transparent);
    animation: scan 3s linear infinite;
}

@keyframes scan {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.recherche-modal-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 12px;
    text-shadow: var(--header-text) == #00ffff ? 0 0 10px currentColor : none;
}

.recherche-modal-close {
    background: none;
    border: none;
    color: var(--header-text);
    font-size: 1.5rem;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.2s ease;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.recherche-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.1);
}

.recherche-modal-overlay.dark-mode .recherche-modal-close:hover {
    background: rgba(0, 255, 255, 0.2);
    box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
}

/* Section de recherche */
.recherche-input-section {
    padding: 30px;
    background: var(--input-section-bg);
    border-bottom: 1px solid var(--modal-border);
}

.recherche-input-container {
    display: flex;
    gap: 15px;
    max-width: 800px;
    margin: 0 auto;
}

.recherche-input {
    flex: 1;
    padding: 15px 20px;
    border: 2px solid var(--input-border);
    border-radius: 12px;
    font-size: 1.1rem;
    outline: none;
    transition: all 0.2s ease;
    background: var(--input-bg);
    color: var(--input-text);
}

.recherche-input:focus {
    border-color: var(--input-focus-border);
    box-shadow: 0 0 0 3px var(--input-focus-shadow);
}

.recherche-modal-overlay.dark-mode .recherche-input:focus {
    box-shadow: 
        0 0 0 3px var(--input-focus-shadow),
        0 0 20px rgba(0, 255, 255, 0.3);
}

.recherche-input::placeholder {
    color: var(--text-secondary);
}

.recherche-btn {
    padding: 15px 25px;
    background: var(--btn-bg);
    color: var(--btn-text);
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 140px;
    justify-content: center;
}

.recherche-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--btn-hover-shadow);
}

.recherche-modal-overlay.dark-mode .recherche-btn:hover {
    box-shadow: 
        0 8px 25px var(--btn-hover-shadow),
        0 0 30px rgba(0, 255, 255, 0.4);
}

/* Loading */
.recherche-loading {
    padding: 60px;
    text-align: center;
    color: var(--text-secondary);
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid var(--spinner-border);
    border-top: 4px solid var(--spinner-active);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

.recherche-modal-overlay.dark-mode .loading-spinner {
    box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Onglets */
.recherche-tabs {
    display: flex;
    justify-content: center;
    background: var(--tabs-bg);
    border-bottom: 1px solid var(--modal-border);
    padding: 0 30px;
    overflow-x: auto;
}

.tab-btn {
    padding: 15px 20px;
    background: none;
    border: none;
    color: var(--tab-text);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    border-bottom: 3px solid transparent;
    position: relative;
}

.tab-btn:hover {
    color: var(--tab-text-hover);
    background: var(--tab-hover-bg);
}

.tab-btn.active {
    color: var(--tab-active-text);
    border-bottom-color: var(--tab-active-border);
    background: var(--tab-hover-bg);
}

.recherche-modal-overlay.dark-mode .tab-btn.active {
    text-shadow: 0 0 10px currentColor;
}

.tab-count {
    background: var(--tab-active-border);
    color: var(--btn-text);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    min-width: 20px;
    text-align: center;
}

.recherche-modal-overlay.dark-mode .tab-count {
    background: var(--tab-active-border);
    color: var(--modal-bg);
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
}

.tab-btn.active .tab-count {
    background: var(--tab-active-border);
}

/* Contenu des résultats */
.recherche-content {
    flex: 1;
    overflow-y: auto;
    max-height: 500px;
    background: var(--content-bg);
}

.tab-content {
    display: none;
    padding: 30px;
}

.tab-content.active {
    display: block;
}

.results-header {
    margin-bottom: 20px;
}

.results-header h4 {
    color: var(--text-primary);
    margin: 0;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Liste des résultats */
.results-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.result-item {
    background: var(--result-bg);
    border: 1px solid var(--result-border);
    border-radius: 12px;
    padding: 20px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.result-item:hover {
    border-color: var(--result-hover-border);
    box-shadow: 0 4px 15px var(--result-hover-shadow);
    transform: translateY(-2px);
}

.recherche-modal-overlay.dark-mode .result-item:hover {
    box-shadow: 
        0 4px 15px var(--result-hover-shadow),
        0 0 20px rgba(0, 255, 255, 0.2);
}

.result-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
    gap: 15px;
}

.result-title {
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1;
}

.result-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

/* Badges adaptatifs */
.badge-success { 
    background: #d4edda; 
    color: #155724; 
}
.recherche-modal-overlay.dark-mode .badge-success { 
    background: rgba(0, 255, 0, 0.2); 
    color: #00ff00; 
    box-shadow: 0 0 10px rgba(0, 255, 0, 0.3);
}

.badge-warning { 
    background: #fff3cd; 
    color: #856404; 
}
.recherche-modal-overlay.dark-mode .badge-warning { 
    background: rgba(255, 255, 0, 0.2); 
    color: #ffff00; 
    box-shadow: 0 0 10px rgba(255, 255, 0, 0.3);
}

.badge-danger { 
    background: #f8d7da; 
    color: #721c24; 
}
.recherche-modal-overlay.dark-mode .badge-danger { 
    background: rgba(255, 0, 0, 0.2); 
    color: #ff0000; 
    box-shadow: 0 0 10px rgba(255, 0, 0, 0.3);
}

.badge-info { 
    background: #d1ecf1; 
    color: #0c5460; 
}
.recherche-modal-overlay.dark-mode .badge-info { 
    background: rgba(0, 255, 255, 0.2); 
    color: #00ffff; 
    box-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
}

.badge-primary { 
    background: #cce5ff; 
    color: #004085; 
}
.recherche-modal-overlay.dark-mode .badge-primary { 
    background: rgba(0, 123, 255, 0.2); 
    color: #007bff; 
    box-shadow: 0 0 10px rgba(0, 123, 255, 0.3);
}

.result-details {
    color: var(--text-secondary);
    font-size: 0.9rem;
    line-height: 1.5;
}

.result-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 10px;
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Message vide */
.recherche-empty {
    padding: 80px 30px;
    text-align: center;
    color: var(--text-secondary);
}

.empty-icon {
    font-size: 4rem;
    color: var(--text-muted);
    margin-bottom: 20px;
}

.recherche-modal-overlay.dark-mode .empty-icon {
    text-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
}

.recherche-empty h4 {
    color: var(--text-primary);
    margin-bottom: 10px;
}

/* ====================================================================
   GESTION DES Z-INDEX AVEC AUTRES MODALS
==================================================================== */

/* S'assurer que les modals de détails s'affichent au-dessus du modal de recherche */
#taskDetailsModal.show,
#commandeDetailsModal.show,
#repairDetailsModal.show,
.modal.show {
    z-index: 1600 !important;
}

#taskDetailsModal.show .modal-dialog,
#commandeDetailsModal.show .modal-dialog,
#repairDetailsModal.show .modal-dialog,
.modal.show .modal-dialog {
    z-index: 1601 !important;
}

#taskDetailsModal.show .modal-content,
#commandeDetailsModal.show .modal-content,
#repairDetailsModal.show .modal-content,
.modal.show .modal-content {
    z-index: 1602 !important;
}

/* Backdrop des modals de détails */
.modal-backdrop.taskDetailsModal-backdrop,
.modal-backdrop.commandeDetailsModal-backdrop,
.modal-backdrop.repairDetailsModal-backdrop {
    z-index: 1595 !important;
}

/* Quand un modal de détails est ouvert, réduire la priorité du modal de recherche */
body:has(#taskDetailsModal.show) .recherche-modal-overlay,
body:has(#commandeDetailsModal.show) .recherche-modal-overlay,
body:has(#repairDetailsModal.show) .recherche-modal-overlay,
body:has(.modal.show) .recherche-modal-overlay {
    z-index: 1400 !important;
}

/* Alternative pour navigateurs sans support :has() */
body.modal-open .recherche-modal-overlay {
    z-index: 1400 !important;
}

/* ====================================================================
   RESPONSIVE
==================================================================== */

/* Responsive */
@media (max-width: 768px) {
    .recherche-modal {
        width: 95%;
        max-height: 95vh;
        border-radius: 15px;
    }
    
    .recherche-modal-header {
        padding: 20px;
    }
    
    .recherche-modal-title {
        font-size: 1.3rem;
    }
    
    .recherche-input-section {
        padding: 20px;
    }
    
    .recherche-input-container {
        flex-direction: column;
    }
    
    .recherche-btn {
        width: 100%;
    }
    
    .recherche-tabs {
        padding: 0 20px;
        justify-content: center;
    }
    
    .tab-content {
        padding: 20px;
    }
    
    .result-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .result-meta {
        flex-direction: column;
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .recherche-modal {
        width: 100%;
        height: 100%;
        border-radius: 0;
        max-height: none;
    }
    
    .recherche-content {
        max-height: none;
    }
}
</style>

