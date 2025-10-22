<?php
/**
 * MODALS BOOTSTRAP 5.3.3 - VERSION CLEAN
 * Modals recréés de zéro pour être fonctionnels
 */
?>

<!-- ========================================= -->
<!-- MODAL: NOUVELLES ACTIONS - DESIGN MODERNE -->
<!-- ========================================= -->
<div class="modal fade" id="nouvelles_actions_modal" tabindex="-1" aria-labelledby="nouvelles_actions_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg modern-modal">
            <div class="modal-header border-0 bg-gradient-primary">
                <h5 class="modal-title text-white fw-bold" id="nouvelles_actions_modal_label">
                    <i class="fas fa-sparkles me-2 pulse-icon"></i>
                    Créer quelque chose de nouveau
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 position-relative overflow-hidden">
                <!-- Effet de particules animées -->
                <div class="particles-container">
                    <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
                    <div class="particle" style="left: 30%; animation-delay: 1s;"></div>
                    <div class="particle" style="left: 50%; animation-delay: 2s;"></div>
                    <div class="particle" style="left: 70%; animation-delay: 0.5s;"></div>
                    <div class="particle" style="left: 90%; animation-delay: 1.5s;"></div>
                </div>
                
                <!-- Actions modernes avec cartes -->
                <div class="modern-actions-grid p-4">
                    <!-- Nouvelle Réparation -->
                    <a href="index.php?page=ajouter_reparation" class="modern-action-card repair-card">
                        <div class="card-glow"></div>
                        <div class="action-icon-container">
                            <div class="action-icon bg-gradient-primary">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div class="pulse-ring"></div>
                        </div>
                        <div class="action-content">
                            <h6 class="action-title">Nouvelle Réparation</h6>
                            <p class="action-description">Créer un dossier de réparation complet</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>

                    <!-- Nouvelle Tâche -->
                    <a href="index.php?page=taches" class="modern-action-card task-card">
                        <div class="card-glow"></div>
                        <div class="action-icon-container">
                            <div class="action-icon bg-gradient-success">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="pulse-ring"></div>
                        </div>
                        <div class="action-content">
                            <h6 class="action-title">Nouvelle Tâche</h6>
                            <p class="action-description">Ajouter une tâche à accomplir</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>

                    <!-- Nouvelle Commande -->
                    <button type="button" class="modern-action-card order-card" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal" data-bs-dismiss="modal">
                        <div class="card-glow"></div>
                        <div class="action-icon-container">
                            <div class="action-icon bg-gradient-warning">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="pulse-ring"></div>
                        </div>
                        <div class="action-content">
                            <h6 class="action-title">Nouvelle Commande</h6>
                            <p class="action-description">Commander des pièces et fournitures</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </button>
                </div>

                <!-- Effet scanner animé -->
                <div class="scanner-line"></div>
            </div>
            
            <!-- Footer avec effet holographique -->
            <div class="modal-footer border-0 bg-light bg-opacity-50">
                <small class="text-muted d-flex align-items-center">
                    <i class="fas fa-magic me-1"></i>
                    Choisissez une action pour commencer
                </small>
            </div>
        </div>
    </div>
</div>

<!-- ========================================= -->
<!-- MODAL: MENU NAVIGATION - DESIGN FUTURISTE -->
<!-- ========================================= -->
<div class="modal fade" id="menu_navigation_modal" tabindex="-1" aria-labelledby="menu_navigation_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg modern-navigation-modal">
            <div class="modal-header border-0 bg-gradient-navigation">
                <h5 class="modal-title text-white fw-bold" id="menu_navigation_modal_label">
                    <i class="fas fa-rocket me-2 rocket-pulse"></i>
                    Centre de Navigation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 position-relative overflow-hidden">
                <!-- Effet de grille futuriste en arrière-plan -->
                <div class="cyber-grid"></div>
                
                <!-- Particules de navigation -->
                <div class="nav-particles-container">
                    <div class="nav-particle" style="left: 15%; animation-delay: 0s;"></div>
                    <div class="nav-particle" style="left: 35%; animation-delay: 0.7s;"></div>
                    <div class="nav-particle" style="left: 55%; animation-delay: 1.4s;"></div>
                    <div class="nav-particle" style="left: 75%; animation-delay: 2.1s;"></div>
                    <div class="nav-particle" style="left: 85%; animation-delay: 0.3s;"></div>
                </div>
                
                <!-- Navigation moderne complète avec sections -->
                <div class="modern-nav-grid p-4">
                    
                    <!-- Section: Navigation Principale -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-rocket me-2"></i>
                            Navigation Principale
                        </h6>
                    </div>
                    <div class="nav-grid-row">
                        <a href="index.php?page=accueil" class="modern-nav-card home-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-home">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Accueil</h6>
                                <p class="nav-subtitle">Tableau de bord</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=reparations" class="modern-nav-card repair-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-repair">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Réparations</h6>
                                <p class="nav-subtitle">Gestion des réparations</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=ajouter_reparation" class="modern-nav-card new-repair-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-new-repair">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Nouvelle Réparation</h6>
                                <p class="nav-subtitle">Créer une réparation</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Section: Gestion -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-cogs me-2"></i>
                            Gestion
                        </h6>
                    </div>
                    <div class="nav-grid-row">
                        <a href="index.php?page=commandes_pieces" class="modern-nav-card orders-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-orders">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Commandes</h6>
                                <p class="nav-subtitle">Commandes de pièces</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=taches" class="modern-nav-card tasks-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-tasks">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Tâches</h6>
                                <p class="nav-subtitle">Gestion des tâches</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=rachat_appareils" class="modern-nav-card rachat-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-rachat">
                                    <i class="fas fa-recycle"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Rachat</h6>
                                <p class="nav-subtitle">Rachat d'appareils</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Section: Clients & Support -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-users me-2"></i>
                            Clients & Support
                        </h6>
                    </div>
                    <div class="nav-grid-row">
                        <a href="index.php?page=clients" class="modern-nav-card clients-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-clients">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Clients</h6>
                                <p class="nav-subtitle">Base clients</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=base_connaissance" class="modern-nav-card knowledge-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-knowledge">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Base de connaissance</h6>
                                <p class="nav-subtitle">Documentations</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=devis" class="modern-nav-card devis-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-special">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Devis</h6>
                                <p class="nav-subtitle">Gestion des devis</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Section: Missions -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-crosshairs me-2"></i>
                            Missions
                        </h6>
                    </div>
                    <div class="nav-grid-row">
                        <a href="index.php?page=missions" class="modern-nav-card missions-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-missions">
                                    <i class="fas fa-crosshairs"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Missions</h6>
                                <p class="nav-subtitle">Toutes les missions</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=mes_missions" class="modern-nav-card my-missions-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-my-missions">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Mes missions</h6>
                                <p class="nav-subtitle">Missions personnelles</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=admin_missions" class="modern-nav-card admin-missions-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-admin-missions">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Admin missions</h6>
                                <p class="nav-subtitle">Administration</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Section: Communication -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-comments me-2"></i>
                            Communication
                        </h6>
                    </div>
                    <div class="nav-grid-row">
                        <a href="index.php?page=communication" class="modern-nav-card communication-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-communication">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Communication</h6>
                                <p class="nav-subtitle">Centre de communication</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=campagne_sms" class="modern-nav-card sms-campaign-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-sms-campaign">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Campagne SMS</h6>
                                <p class="nav-subtitle">Envois groupés</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=template_sms" class="modern-nav-card sms-template-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-sms-template">
                                    <i class="fas fa-file-text"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Template SMS</h6>
                                <p class="nav-subtitle">Modèles de messages</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Ligne SMS Histoire -->
                    <div class="nav-grid-row nav-grid-start">
                        <a href="index.php?page=historique_sms" class="modern-nav-card sms-history-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-sms-history">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Historique SMS</h6>
                                <p class="nav-subtitle">Historique des envois</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Section: Administration -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-shield-alt me-2"></i>
                            Administration
                        </h6>
                    </div>
                    <div class="nav-grid-row">
                        <a href="index.php?page=administration" class="modern-nav-card administration-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-administration">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Administration</h6>
                                <p class="nav-subtitle">Panel d'administration</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=employes" class="modern-nav-card employees-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-employees">
                                    <i class="fas fa-id-badge"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Employés</h6>
                                <p class="nav-subtitle">Gestion du personnel</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=absences_retards" class="modern-nav-card absences-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-absences">
                                    <i class="fas fa-calendar-times"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Absences & Retards</h6>
                                <p class="nav-subtitle">Suivi des absences</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Ligne Administration suite -->
                    <div class="nav-grid-row">
                        <a href="index.php?page=journaux_reparation" class="modern-nav-card logs-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-logs">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Journaux de réparation</h6>
                                <p class="nav-subtitle">Logs et historiques</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=signalements_bugs" class="modern-nav-card bugs-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-bugs">
                                    <i class="fas fa-bug"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Signalements bugs</h6>
                                <p class="nav-subtitle">Rapports de bugs</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=parametre" class="modern-nav-card settings-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-settings">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Paramètres</h6>
                                <p class="nav-subtitle">Configuration</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>

                    <!-- Section: Système -->
                    <div class="nav-section-header">
                        <h6 class="section-title">
                            <i class="fas fa-server me-2"></i>
                            Système
                        </h6>
                    </div>
                    <div class="nav-grid-row nav-grid-center">
                        <a href="index.php?page=changer_magasin" class="modern-nav-card special-card shop-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-shop">
                                    <i class="fas fa-store-alt"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                                <div class="special-orbit"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Changer de magasin</h6>
                                <p class="nav-subtitle">Basculer entre magasins</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>

                        <a href="index.php?page=deconnexion" class="modern-nav-card special-card logout-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container">
                                <div class="nav-icon bg-gradient-logout">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                                <div class="nav-pulse-ring"></div>
                                <div class="special-orbit"></div>
                            </div>
                            <div class="nav-content">
                                <h6 class="nav-title">Déconnexion</h6>
                                <p class="nav-subtitle">Quitter la session</p>
                            </div>
                            <div class="nav-glow-effect"></div>
                        </a>
                    </div>
                </div>

                <!-- Scanner horizontal pour le menu -->
                <div class="nav-scanner-line"></div>
            </div>
            
            <!-- Footer futuriste -->
            <div class="modal-footer border-0 bg-dark bg-opacity-10">
                <small class="text-muted d-flex align-items-center">
                    <i class="fas fa-satellite-dish me-1"></i>
                    Navigation GeekBoard - Interface futuriste
                </small>
            </div>
        </div>
    </div>
</div>

<!-- ========================================= -->
<!-- MODAL: AJOUTER COMMANDE - VERSION COMPLÈTE -->
<!-- ========================================= -->
<?php
// S'assurer que la variable dark_mode est définie
$dark_mode = isset($dark_mode) ? $dark_mode : (isset($_SESSION['dark_mode']) && $_SESSION['dark_mode'] === true);
?>

<!-- Modal Ajouter Commande - Design Moderne -->
<div class="modal fade" id="ajouterCommandeModal" tabindex="-1" aria-labelledby="ajouterCommandeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content order-container" style="background-color: <?php echo $dark_mode ? '#1f2937' : '#ffffff'; ?>; opacity: 1 !important;">
            <!-- En-tête du formulaire -->
            <div class="order-header">
                <h2><i class="fas fa-shopping-cart"></i> Nouvelle commande de pièces</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Corps du formulaire -->
            <div class="modal-body p-0">
                <form id="ajouterCommandeForm" method="post" action="ajax/add_commande.php">
                    <!-- Section Client -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-user-circle"></i> Client
                        </div>
                        <div class="order-grid">
                            <div class="form-group">
                                <div class="client-field">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="form-control" id="nom_client_selectionne" placeholder="Saisir ou rechercher un client" aria-label="Rechercher un client">
                                    <input type="hidden" name="client_id" id="client_id" value="">
                                </div>
                                <div id="client_selectionne" class="selected-item-info d-none mt-2">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-icon me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <span class="fw-medium nom_client"></span>
                                            <span class="d-block small text-muted tel_client"></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Résultats de recherche client inline -->
                                <div id="resultats_recherche_client_inline" class="mt-2 d-none">
                                    <div class="card border-0 shadow-sm">
                                        <div class="list-group list-group-flush" id="liste_clients_recherche_inline">
                                            <!-- Les résultats seront ajoutés ici -->
                                        </div>
                                    </div>
                                </div>
                    </div>
                            <div class="form-group">
                                <button type="button" class="btn btn-outline-primary w-100" id="newClientBtn" data-bs-toggle="modal" data-bs-target="#nouveauClientModal_commande">
                                    <i class="fas fa-user-plus"></i> Créer un nouveau client
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Section Réparation liée -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-tools"></i> Réparation liée (optionnel)
                        </div>
                        <div class="form-group">
                            <select class="form-select" name="reparation_id" id="reparation_id" onchange="getClientFromReparation(this.value)">
                                <option value="">Sélectionner une réparation...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Section Fournisseur -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-truck"></i> Fournisseur
                        </div>
                        <div class="form-group">
                            <div class="supplier-select">
                                <select class="form-select" name="fournisseur_id" id="fournisseur_id_ajout" required>
                                    <option value="">Sélectionner un fournisseur</option>
                                    <?php
                                    try {
                                        require_once __DIR__ . '/../config/database.php';
                                        $shop_pdo = getShopDBConnection();
                                        $stmt = $shop_pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
                                        while ($fournisseur = $stmt->fetch()) {
                                            echo "<option value='{$fournisseur['id']}'>" . 
                                                htmlspecialchars($fournisseur['nom']) . "</option>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<option value=''>Erreur de chargement des fournisseurs</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section Pièce commandée -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-microchip"></i> Pièce commandée
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control" name="nom_piece" id="nom_piece" placeholder="Désignation de la pièce" required>
                        </div>
                    </div>

                    <!-- Section Code barre et Quantité -->
                    <div class="order-section">
                        <div class="order-grid">
                            <div class="form-group">
                                <div class="order-section-title">
                                    <i class="fas fa-barcode"></i> Code barre
                                </div>
                                <div class="barcode-field">
                                    <input type="text" class="form-control" name="code_barre" id="code_barre" placeholder="Saisir le code barre">
                                    <button type="button" class="barcode-scan-btn" id="scanBarcodeBtn" title="Scanner">
                                        <i class="fas fa-barcode"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="order-section-title">
                                    <i class="fas fa-sort-amount-up"></i> Quantité
                                </div>
                                <div class="quantity-selector">
                                    <button type="button" class="quantity-decrease" id="decreaseQuantity">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" name="quantite" id="quantite" value="1" min="1" max="99">
                                    <button type="button" class="quantity-increase" id="increaseQuantity">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Prix estimé et Statut -->
                    <div class="order-section">
                        <div class="order-grid">
                            <div class="form-group">
                                <div class="order-section-title">
                                    <i class="fas fa-tag"></i> Prix estimé (€)
                                </div>
                                <div class="price-field">
                                    <input type="number" class="form-control" name="prix_estime" id="prix_estime" placeholder="0.00" step="0.01" min="0" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="order-section-title">
                                    <i class="fas fa-info-circle"></i> Statut
                                </div>
                                <div class="status-options">
                                    <div class="status-option status-option-pending">
                                        <input type="radio" name="statut" id="statusPending" value="en_attente" checked>
                                        <label for="statusPending">
                                            <i class="fas fa-clock"></i>
                                            <span>En attente</span>
                                        </label>
                                    </div>
                                    <div class="status-option status-option-ordered">
                                        <input type="radio" name="statut" id="statusOrdered" value="commande">
                                        <label for="statusOrdered">
                                            <i class="fas fa-shopping-cart"></i>
                                            <span>Commandé</span>
                                        </label>
                                    </div>
                                    <div class="status-option status-option-received">
                                        <input type="radio" name="statut" id="statusReceived" value="recue">
                                        <label for="statusReceived">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Reçu</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Container pour les pièces additionnelles -->
                    <div id="pieces-additionnelles"></div>

                    <!-- Bouton pour ajouter une autre pièce -->
                    <button type="button" class="add-item-btn" id="ajouter-piece-btn">
                        <i class="fas fa-plus-circle"></i> Ajouter une autre pièce
                    </button>

                    <!-- Bouton pour activer/désactiver l'envoi de SMS -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-sms"></i> Notification client
                        </div>
                        <button id="smsToggleButtonAjout" type="button" class="btn btn-danger w-100 py-3" style="font-weight: bold; font-size: 1rem; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                            <i class="fas fa-ban me-2"></i>
                            NE PAS ENVOYER DE SMS AU CLIENT
                        </button>
                        <input type="hidden" id="sendSmsSwitchAjout" name="send_sms" value="0">
                    </div>

                    <!-- Pied de page avec boutons d'actions -->
                    <div class="order-footer">
                        <div>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Annuler
                            </button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-primary" id="debugSessionBtn">
                                <i class="fas fa-bug"></i> Debug Session
                            </button>
                            <button type="submit" class="btn btn-primary" id="saveCommandeBtn">
                                <i class="fas fa-save"></i> Enregistrer la commande
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript pour la gestion des interactions du formulaire
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du compteur de quantité
    const quantityInput = document.getElementById('quantite');
    const decreaseBtn = document.getElementById('decreaseQuantity');
    const increaseBtn = document.getElementById('increaseQuantity');

    if (decreaseBtn && increaseBtn && quantityInput) {
        decreaseBtn.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
            updateDecreaseBtnState();
        });

        increaseBtn.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            quantityInput.value = currentValue + 1;
            updateDecreaseBtnState();
        });

        function updateDecreaseBtnState() {
            decreaseBtn.disabled = parseInt(quantityInput.value) <= 1;
        }

        // Initialisation de l'état du bouton de diminution
        updateDecreaseBtnState();
    }

    // Gestion des boutons radio de statut
    const statusRadios = document.querySelectorAll('input[name="statut"]');
    if (statusRadios.length) {
        statusRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Réinitialiser tous les statuts
                document.querySelectorAll('.status-option').forEach(option => {
                    option.classList.remove('active');
                });
                
                // Activer l'option sélectionnée
                if (this.checked) {
                    this.closest('.status-option').classList.add('active');
                }
            });
        });
        
        // Initialiser le statut actif
        const checkedRadio = document.querySelector('input[name="statut"]:checked');
        if (checkedRadio) {
            checkedRadio.closest('.status-option').classList.add('active');
        }
    }
    
    // Corrige le problème du backdrop qui bloque les interactions
    const fixModalBackdrop = function() {
        const modal = document.getElementById('ajouterCommandeModal');
        
        // Ajuster le modal quand il est ouvert
        modal.addEventListener('shown.bs.modal', function() {
            // Mettre le z-index du contenu modal au-dessus du backdrop
            const modalContent = this.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.zIndex = '1056';
            }
            
            // Vérifier s'il y a un backdrop et ajuster son comportement
            const backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 0) {
                backdrops.forEach(backdrop => {
                    backdrop.style.pointerEvents = 'none';
                    backdrop.style.zIndex = '1040';
                });
            }
        });
        
        // Quand le modal est fermé, nettoyer les backdrops et restaurer le scroll
        modal.addEventListener('hidden.bs.modal', function() {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            if (backdrops.length > 0) {
                backdrops.forEach(backdrop => {
                    backdrop.remove(); // Supprimer tous les backdrops restants
                });
            }
            
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    };
    
    // Initialiser le correctif pour le backdrop
    fixModalBackdrop();
});
</script>

<!-- ========================================= -->
<!-- MODAL: NOUVEAU CLIENT POUR COMMANDES -->
<!-- ========================================= -->
<div class="modal fade" id="nouveauClientModal_commande" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="z-index: 1100;">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2 text-primary"></i>
                    Ajouter un nouveau client
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="nouveauClientCommandeForm" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="nouveau_nom_commande" class="form-label">Nom <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="nouveau_nom_commande" required>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_prenom_commande" class="form-label">Prénom <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="nouveau_prenom_commande" required>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_telephone_commande" class="form-label">Téléphone <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control" id="nouveau_telephone_commande" required>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_email_commande" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="nouveau_email_commande">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_adresse_commande" class="form-label">Adresse</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                            <textarea class="form-control" id="nouveau_adresse_commande" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btn_sauvegarder_client_commande">
                    <i class="fas fa-save me-2"></i>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Script pour le modal nouveau client commande
document.addEventListener('DOMContentLoaded', function() {
    const btnSauvegarder = document.getElementById('btn_sauvegarder_client_commande');
    const modal = document.getElementById('nouveauClientModal_commande');
    const form = document.getElementById('nouveauClientCommandeForm');
    
    if (btnSauvegarder) {
        btnSauvegarder.addEventListener('click', function() {
            // Validation du formulaire
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            
            const formData = {
                nom: document.getElementById('nouveau_nom_commande').value,
                prenom: document.getElementById('nouveau_prenom_commande').value,
                telephone: document.getElementById('nouveau_telephone_commande').value,
                email: document.getElementById('nouveau_email_commande').value,
                adresse: document.getElementById('nouveau_adresse_commande').value
            };
            
            // Envoyer les données
            fetch('ajax/ajouter_client.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fermer le modal nouveau client
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    modalInstance.hide();
                    
                    // Réinitialiser le formulaire
                    form.reset();
                    form.classList.remove('was-validated');
                    
                    // Rouvrir le modal commande et sélectionner le client
                    setTimeout(() => {
                        const commandeModal = document.getElementById('ajouterCommandeModal');
                        const commandeModalInstance = new bootstrap.Modal(commandeModal);
                        commandeModalInstance.show();
                        
                        // Sélectionner automatiquement le nouveau client
                        if (data.client) {
                            const clientSearchInput = document.getElementById('nom_client_selectionne');
                            const clientIdInput = document.getElementById('client_id');
                            const clientSelectionne = document.getElementById('client_selectionne');
                            
                            if (clientSearchInput && clientIdInput && clientSelectionne) {
                                clientIdInput.value = data.client.id;
                                clientSearchInput.value = `${data.client.nom} ${data.client.prenom}`;
                                
                                clientSelectionne.querySelector('.nom_client').textContent = `${data.client.nom} ${data.client.prenom}`;
                                clientSelectionne.querySelector('.tel_client').textContent = data.client.telephone || 'Pas de téléphone';
                                clientSelectionne.classList.remove('d-none');
                            }
                        }
                    }, 300);
                    
                    alert('Client ajouté avec succès !');
                } else {
                    alert(data.message || 'Erreur lors de l\'ajout du client');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'ajout du client');
            });
        });
    }
});
</script>



<!-- ========================================= -->
<!-- SCRIPTS POUR LES MODALS -->
<!-- ========================================= -->
<script>
// Fonction pour ajouter une commande
function ajouterCommande() {
    const form = document.getElementById('ajouterCommandeForm');
    const formData = new FormData(form);
    
    // Ici vous pouvez ajouter votre logique AJAX pour sauvegarder la commande
    console.log('Ajout de commande:', Object.fromEntries(formData));
    
    // Fermer le modal après ajout
    const modal = bootstrap.Modal.getInstance(document.getElementById('ajouterCommandeModal'));
    modal.hide();
    
    // Réinitialiser le formulaire
    form.reset();
    
    // Afficher un message de succès
    showToast('Commande ajoutée avec succès!', 'success');
}



// Fonction utilitaire pour afficher des toasts (messages de succès)
function showToast(message, type = 'info') {
    // Créer un toast simple
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    document.body.appendChild(toast);
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 5000);
}

// Initialisation des modals au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser tous les modals Bootstrap
    const modalElements = document.querySelectorAll('.modal');
    modalElements.forEach(modalElement => {
        new bootstrap.Modal(modalElement);
    });
    
    console.log('✅ Modals Bootstrap initialisés avec succès');
});
</script>