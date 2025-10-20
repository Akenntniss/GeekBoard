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
   MODAL DE RECHERCHE MODERNE - THÈME ADAPTATIF MOBILE OPTIMISÉ
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
    --btn-hover-bg: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    
    --tab-bg: #ffffff;
    --tab-border: #e9ecef;
    --tab-text: #6c757d;
    --tab-active-bg: #667eea;
    --tab-active-text: #ffffff;
    --tab-hover-bg: #f8f9fa;
    
    --content-bg: #ffffff;
    --content-border: #e9ecef;
    
    --result-bg: #ffffff;
    --result-border: #e9ecef;
    --result-hover-bg: #f8f9fa;
    --result-text: #495057;
    --result-meta: #6c757d;
    
    --loading-color: #667eea;
    --empty-color: #6c757d;
}

/* Variables pour le mode sombre */
body.night-mode {
    --modal-overlay-bg: rgba(0, 0, 0, 0.8);
    --modal-bg: rgba(15, 15, 35, 0.95);
    --modal-shadow: 0 20px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(0, 212, 255, 0.2);
    --modal-border: rgba(0, 212, 255, 0.3);
    
    --header-bg: linear-gradient(135deg, rgba(0, 212, 255, 0.2) 0%, rgba(102, 126, 234, 0.2) 100%);
    --header-text: #ffffff;
    
    --input-section-bg: rgba(22, 33, 62, 0.8);
    --input-bg: rgba(15, 15, 35, 0.9);
    --input-border: rgba(0, 212, 255, 0.3);
    --input-focus-border: rgba(0, 212, 255, 0.6);
    --input-focus-shadow: rgba(0, 212, 255, 0.2);
    --input-text: #ffffff;
    
    --btn-bg: linear-gradient(135deg, rgba(0, 212, 255, 0.3) 0%, rgba(102, 126, 234, 0.3) 100%);
    --btn-text: #ffffff;
    --btn-hover-bg: linear-gradient(135deg, rgba(0, 212, 255, 0.4) 0%, rgba(102, 126, 234, 0.4) 100%);
    
    --tab-bg: rgba(15, 15, 35, 0.8);
    --tab-border: rgba(0, 212, 255, 0.2);
    --tab-text: #94a3b8;
    --tab-active-bg: rgba(0, 212, 255, 0.3);
    --tab-active-text: #ffffff;
    --tab-hover-bg: rgba(0, 212, 255, 0.1);
    
    --content-bg: rgba(15, 15, 35, 0.9);
    --content-border: rgba(0, 212, 255, 0.2);
    
    --result-bg: rgba(22, 33, 62, 0.6);
    --result-border: rgba(0, 212, 255, 0.2);
    --result-hover-bg: rgba(0, 212, 255, 0.1);
    --result-text: #ffffff;
    --result-meta: #94a3b8;
    
    --loading-color: #00d4ff;
    --empty-color: #94a3b8;
}

/* ====================================================================
   OVERLAY ET MODAL PRINCIPAL
==================================================================== */

.recherche-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--modal-overlay-bg);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1500;
    opacity: 0;
    transition: opacity 0.3s ease;
    padding: 20px;
}

.recherche-modal-overlay.show {
    opacity: 1;
}

.recherche-modal {
    background: var(--modal-bg);
    border: 2px solid var(--modal-border);
    border-radius: 20px;
    box-shadow: var(--modal-shadow);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    width: 100%;
    max-width: 800px;
    max-height: 90vh;
    overflow: hidden;
    transform: scale(0.9) translateY(20px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}

.recherche-modal-overlay.show .recherche-modal {
    transform: scale(1) translateY(0);
}

/* ====================================================================
   HEADER
==================================================================== */

.recherche-modal-header {
    background: var(--header-bg);
    padding: 25px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-radius: 20px 20px 0 0;
    position: relative;
    overflow: hidden;
}

.recherche-modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
    opacity: 0.3;
}

.recherche-modal-title {
    color: var(--header-text);
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    z-index: 2;
}

.recherche-modal-title i {
    font-size: 1.3rem;
    opacity: 0.9;
}

.recherche-modal-close {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--header-text);
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    position: relative;
    z-index: 2;
}

.recherche-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}

/* ====================================================================
   SECTION RECHERCHE
==================================================================== */

.recherche-input-section {
    background: var(--input-section-bg);
    padding: 25px 30px;
    border-bottom: 1px solid var(--modal-border);
}

.recherche-input-container {
    display: flex;
    gap: 15px;
    align-items: center;
}

.recherche-input {
    flex: 1;
    background: var(--input-bg);
    border: 2px solid var(--input-border);
    border-radius: 12px;
    padding: 15px 20px;
    font-size: 1rem;
    color: var(--input-text);
    transition: all 0.3s ease;
    outline: none;
}

.recherche-input:focus {
    border-color: var(--input-focus-border);
    box-shadow: 0 0 0 4px var(--input-focus-shadow);
}

.recherche-input::placeholder {
    color: var(--result-meta);
    opacity: 0.7;
}

.recherche-btn {
    background: var(--btn-bg);
    border: none;
    border-radius: 12px;
    padding: 15px 25px;
    color: var(--btn-text);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.recherche-btn:hover {
    background: var(--btn-hover-bg);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

/* ====================================================================
   LOADING
==================================================================== */

.recherche-loading {
    padding: 40px;
    text-align: center;
    color: var(--loading-color);
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(102, 126, 234, 0.1);
    border-left-color: var(--loading-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.recherche-loading p {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 500;
}

/* ====================================================================
   ONGLETS
==================================================================== */

.recherche-tabs {
    display: flex;
    background: var(--tab-bg);
    border-bottom: 1px solid var(--modal-border);
    padding: 0 30px;
    gap: 5px;
}

.tab-btn {
    background: transparent;
    border: none;
    padding: 15px 20px;
    color: var(--tab-text);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 12px 12px 0 0;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
    margin-bottom: -1px;
}

.tab-btn:hover {
    background: var(--tab-hover-bg);
    color: var(--tab-active-text);
}

.tab-btn.active {
    background: var(--tab-active-bg);
    color: var(--tab-active-text);
    border-bottom: 2px solid var(--tab-active-bg);
}

.tab-count {
    background: rgba(255, 255, 255, 0.2);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.tab-btn.active .tab-count {
    background: rgba(255, 255, 255, 0.3);
}

/* ====================================================================
   CONTENU DES RÉSULTATS
==================================================================== */

.recherche-content {
    background: var(--content-bg);
    flex: 1;
    overflow-y: auto;
    min-height: 200px;
    max-height: 400px;
}

.tab-content {
    display: none;
    padding: 0;
}

.tab-content.active {
    display: block;
}

.results-header {
    padding: 20px 30px 15px;
    border-bottom: 1px solid var(--content-border);
    background: var(--content-bg);
    position: sticky;
    top: 0;
    z-index: 10;
}

.results-header h4 {
    margin: 0;
    color: var(--result-text);
    font-size: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.results-list {
    padding: 0;
}

/* ====================================================================
   MESSAGE VIDE
==================================================================== */

.recherche-empty {
    padding: 60px 40px;
    text-align: center;
    color: var(--empty-color);
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.recherche-empty h4 {
    margin: 0 0 10px;
    color: var(--result-text);
    font-weight: 600;
}

.recherche-empty p {
    margin: 0;
    opacity: 0.7;
}

/* ====================================================================
   RESPONSIVE MOBILE OPTIMISÉ
==================================================================== */

/* Tablettes */
@media (max-width: 768px) {
    .recherche-modal-overlay {
        padding: 15px;
        align-items: flex-start;
        padding-top: 5vh;
    }
    
    .recherche-modal {
        width: 100%;
        max-width: none;
        max-height: 85vh;
        border-radius: 16px;
    }
    
    .recherche-modal-header {
        padding: 20px 25px;
        border-radius: 16px 16px 0 0;
    }
    
    .recherche-modal-title {
        font-size: 1.3rem;
    }
    
    .recherche-modal-close {
        width: 40px;
        height: 40px;
    }
    
    .recherche-input-section {
        padding: 20px 25px;
    }
    
    .recherche-input-container {
        flex-direction: column;
        gap: 12px;
    }
    
    .recherche-btn {
        width: 100%;
        justify-content: center;
        padding: 12px 20px;
    }
    
    .recherche-tabs {
        padding: 0 25px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .tab-btn {
        padding: 12px 16px;
        font-size: 0.9rem;
    }
    
    .results-header {
        padding: 15px 25px 12px;
    }
    
    .recherche-content {
        max-height: 300px;
    }
}

/* Mobiles */
@media (max-width: 480px) {
    .recherche-modal-overlay {
        padding: 10px;
        align-items: flex-start;
        padding-top: 2vh;
    }
    
    .recherche-modal {
        width: 100%;
        max-height: 90vh;
        border-radius: 12px;
        /* CORRECTION: Hauteur automatique au lieu de fixe */
        height: auto !important;
        min-height: auto !important;
    }
    
    .recherche-modal-header {
        padding: 18px 20px;
        border-radius: 12px 12px 0 0;
    }
    
    .recherche-modal-title {
        font-size: 1.2rem;
        gap: 8px;
    }
    
    .recherche-modal-close {
        width: 36px;
        height: 36px;
        border-radius: 8px;
    }
    
    .recherche-input-section {
        padding: 18px 20px;
    }
    
    .recherche-input {
        padding: 12px 16px;
        font-size: 16px; /* Évite le zoom sur iOS */
    }
    
    .recherche-btn {
        padding: 12px 16px;
        font-size: 0.9rem;
    }
    
    .recherche-tabs {
        padding: 0 20px;
        gap: 3px;
    }
    
    .tab-btn {
        padding: 10px 12px;
        font-size: 0.85rem;
        flex: 1;
        justify-content: center;
    }
    
    .results-header {
        padding: 12px 20px 10px;
    }
    
    .results-header h4 {
        font-size: 1.1rem;
    }
    
    .recherche-content {
        max-height: 250px;
        /* CORRECTION: Hauteur flexible */
        min-height: 0 !important;
    }
    
    .recherche-loading {
        padding: 30px 20px;
    }
    
    .recherche-empty {
        padding: 40px 20px;
    }
    
    .empty-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
    }
}

/* ====================================================================
   CORRECTIONS SPÉCIFIQUES POUR LE PROBLÈME MOBILE
==================================================================== */

/* S'assurer que le modal s'adapte à son contenu sur mobile */
@media (max-width: 480px) {
    /* Quand seule la barre de recherche est visible */
    .recherche-modal:not(:has(#rechercheContent[style*="block"])):not(:has(#rechercheLoading[style*="block"])):not(:has(#rechercheEmpty[style*="block"])) {
        height: auto !important;
        min-height: auto !important;
    }
    
    /* Alternative pour navigateurs sans support :has() */
    .recherche-modal.compact-mode {
        height: auto !important;
        min-height: auto !important;
        max-height: 60vh !important;
    }
}

/* Mode nuit - Bouton fermer amélioré */
body.night-mode .recherche-modal-close {
    background: rgba(0, 255, 255, 0.15) !important;
    border: 1px solid rgba(0, 255, 255, 0.4) !important;
    color: #ffffff !important;
    box-shadow: 0 0 15px rgba(0, 255, 255, 0.2) !important;
}

body.night-mode .recherche-modal-close:hover {
    background: rgba(0, 255, 255, 0.25) !important;
    border-color: rgba(0, 255, 255, 0.6) !important;
    box-shadow: 0 0 25px rgba(0, 255, 255, 0.4) !important;
}
</style>
