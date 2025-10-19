<!-- MODAL DE RECHERCHE PREMIUM AVEC FILTRES FONCTIONNELS -->
<div class="modal fade" id="rechercheModal" tabindex="-1" aria-labelledby="rechercheModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content modal-premium">
            <!-- En-tête premium avec effet de brillance -->
            <div class="modal-header modal-header-premium">
                <h4 class="modal-title modal-title-premium" id="rechercheModalLabel">
                    <i class="fas fa-search search-icon-premium"></i>
                    <span class="title-text-premium">Recherche Avancée</span>
                    <div class="title-shimmer"></div>
                </h4>
                <button type="button" class="btn-close btn-close-premium" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            
            <div class="modal-body modal-body-premium">
                <!-- Zone de recherche premium -->
                <div class="search-container-premium">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search search-input-icon"></i>
                        <input type="text" class="form-control search-input-premium" id="rechercheInput" 
                               placeholder="Rechercher client, téléphone, appareil, problème ou pièce...">
                        <div class="search-input-glow"></div>
                    </div>
                    <button type="button" class="btn btn-search-premium" id="rechercheBtn">
                        <span class="btn-text">Rechercher</span>
                        <div class="btn-ripple"></div>
                    </button>
                </div>
                
                <!-- Zone de chargement premium -->
                <div id="rechercheLoading" class="loading-container-premium" style="display: none;">
                    <div class="loading-spinner-premium">
                        <div class="spinner-ring"></div>
                        <div class="spinner-ring"></div>
                        <div class="spinner-ring"></div>
                    </div>
                    <p class="loading-text-premium">Recherche en cours...</p>
                    <div class="loading-particles">
                        <div class="particle"></div>
                        <div class="particle"></div>
                        <div class="particle"></div>
                    </div>
                </div>
                
                <!-- Boutons de filtre premium -->
                <div id="rechercheBtns" class="filter-buttons-container" style="display: none;">
                    <div class="filter-buttons-wrapper">
                        <button class="filter-btn filter-btn-active" id="btn-clients" data-filter="clients">
                            <i class="fas fa-users"></i>
                            <span class="filter-text">Clients</span>
                            <span class="filter-count" id="clientsCount">0</span>
                            <div class="filter-btn-glow"></div>
                        </button>
                        
                        <button class="filter-btn" id="btn-reparations" data-filter="reparations">
                            <i class="fas fa-wrench"></i>
                            <span class="filter-text">Réparations</span>
                            <span class="filter-count" id="reparationsCount">0</span>
                            <div class="filter-btn-glow"></div>
                        </button>
                        
                        <button class="filter-btn" id="btn-commandes" data-filter="commandes">
                            <i class="fas fa-box"></i>
                            <span class="filter-text">Commandes</span>
                            <span class="filter-count" id="commandesCount">0</span>
                            <div class="filter-btn-glow"></div>
                        </button>
                    </div>
                </div>
                
                <!-- Conteneurs de résultats premium -->
                <div id="clients-results" class="result-container result-container-premium" style="display: none;">
                    <div class="result-header">
                        <h5><i class="fas fa-users result-icon"></i> Clients trouvés</h5>
                    </div>
                    <div class="table-container-premium">
                        <table class="table table-premium">
                            <thead>
                                <tr>
                                    <th>Nom Complet</th>
                                    <th>Téléphone</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="clientsTableBody">
                                <!-- Résultats clients -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="reparations-results" class="result-container result-container-premium" style="display: none;">
                    <div class="result-header">
                        <h5><i class="fas fa-wrench result-icon"></i> Réparations trouvées</h5>
                    </div>
                    <div class="table-container-premium">
                        <table class="table table-premium">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Appareil</th>
                                    <th>Problème</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="reparationsTableBody">
                                <!-- Résultats réparations -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="commandes-results" class="result-container result-container-premium" style="display: none;">
                    <div class="result-header">
                        <h5><i class="fas fa-box result-icon"></i> Commandes trouvées</h5>
                    </div>
                    <div class="table-container-premium">
                        <table class="table table-premium">
                            <thead>
                                <tr>
                                    <th>Réparation</th>
                                    <th>Pièce</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="commandesTableBody">
                                <!-- Résultats commandes -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Zone aucun résultat -->
                <div id="rechercheEmpty" class="no-results-container" style="display: none;">
                    <div class="no-results-content">
                        <div class="no-results-icon">
                            <i class="fas fa-search-minus"></i>
                        </div>
                        <h4>Aucun résultat trouvé</h4>
                        <p>Essayez avec d'autres termes de recherche ou vérifiez l'orthographe.</p>
                        <div class="search-suggestions">
                            <small>💡 Astuces : Utilisez des mots-clés simples ou des numéros de téléphone complets</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer modal-footer-premium">
                <button type="button" class="btn btn-close-premium" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div> 