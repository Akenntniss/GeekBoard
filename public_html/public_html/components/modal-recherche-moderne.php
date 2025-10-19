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

<!-- CSS du modal moderne -->
<style>
/* Overlay du modal */
.recherche-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.recherche-modal-overlay.show {
    opacity: 1;
}

/* Modal principal */
.recherche-modal {
    background: #fff;
    border-radius: 20px;
    width: 90%;
    max-width: 1200px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transform: scale(0.9) translateY(20px);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.recherche-modal-overlay.show .recherche-modal {
    transform: scale(1) translateY(0);
}

/* Header */
.recherche-modal-header {
    padding: 25px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.recherche-modal-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 12px;
}

.recherche-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: background-color 0.2s ease;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.recherche-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Section de recherche */
.recherche-input-section {
    padding: 30px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
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
    border: 2px solid #e9ecef;
    border-radius: 12px;
    font-size: 1.1rem;
    outline: none;
    transition: all 0.2s ease;
    background: white;
}

.recherche-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.recherche-btn {
    padding: 15px 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
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
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

/* Loading */
.recherche-loading {
    padding: 60px;
    text-align: center;
    color: #6c757d;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e9ecef;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Onglets */
.recherche-tabs {
    display: flex;
    justify-content: center;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 0 30px;
    overflow-x: auto;
}

.tab-btn {
    padding: 15px 20px;
    background: none;
    border: none;
    color: #6c757d;
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
    color: #495057;
    background: rgba(102, 126, 234, 0.1);
}

.tab-btn.active {
    color: #667eea;
    border-bottom-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.tab-count {
    background: #667eea;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    min-width: 20px;
    text-align: center;
}

.tab-btn.active .tab-count {
    background: #5a67d8;
}

/* Contenu des résultats */
.recherche-content {
    flex: 1;
    overflow-y: auto;
    max-height: 500px;
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
    color: #495057;
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
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.result-item:hover {
    border-color: #667eea;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.result-header {
    display: flex;
    justify-content: between;
    align-items: flex-start;
    margin-bottom: 10px;
    gap: 15px;
}

.result-title {
    font-weight: 600;
    color: #495057;
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

.badge-success { background: #d4edda; color: #155724; }
.badge-warning { background: #fff3cd; color: #856404; }
.badge-danger { background: #f8d7da; color: #721c24; }
.badge-info { background: #d1ecf1; color: #0c5460; }
.badge-primary { background: #cce5ff; color: #004085; }

.result-details {
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.5;
}

.result-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 10px;
    font-size: 0.85rem;
    color: #6c757d;
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
    color: #6c757d;
}

.empty-icon {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 20px;
}

.recherche-empty h4 {
    color: #495057;
    margin-bottom: 10px;
}

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

