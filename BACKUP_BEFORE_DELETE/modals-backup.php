<?php
/**
 * MODALS BOOTSTRAP 5.3.3 - VERSION CLEAN
 * Modals recréés de zéro pour être fonctionnels
 */
?>

<!-- Styles CSS pour les modals modernes -->
<style>
/* Styles pour le modal nouvelles actions */
.modern-modal {
    border-radius: 20px !important;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25) !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%) !important;
}

.bg-gradient-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
}

.bg-gradient-scanner {
    background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%) !important;
}

/* Particules animées */
.particles-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.particle {
    position: absolute;
    width: 3px;
    height: 3px;
    background: linear-gradient(45deg, #667eea, #764ba2);
    border-radius: 50%;
    animation: particleFloat 4s ease-in-out infinite;
}

@keyframes particleFloat {
    0%, 100% { 
        transform: translateY(0) rotate(0deg); 
        opacity: 0.3; 
    }
    50% { 
        transform: translateY(-20px) rotate(180deg); 
        opacity: 0.8; 
    }
}

/* Grid des actions modernes */
.modern-actions-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    position: relative;
    z-index: 2;
}

/* Cartes d'action modernes */
.modern-action-card {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    background: rgba(255, 255, 255, 0.95);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    text-decoration: none;
    color: #2d3748;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
    cursor: pointer;
}

.modern-action-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    border-color: rgba(102, 126, 234, 0.5);
    color: #2d3748;
    text-decoration: none;
}

/* Effet de lueur sur les cartes */
.card-glow {
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, transparent, rgba(102, 126, 234, 0.1), transparent);
    border-radius: 18px;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
}

.modern-action-card:hover .card-glow {
    opacity: 1;
}

/* Conteneur d'icône */
.action-icon-container {
    position: relative;
    margin-right: 1rem;
    flex-shrink: 0;
}

.action-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    position: relative;
    z-index: 2;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Effet de pulsation */
.pulse-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 50px;
    height: 50px;
    border: 2px solid rgba(102, 126, 234, 0.3);
    border-radius: 12px;
    transform: translate(-50%, -50%);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
    100% {
        transform: translate(-50%, -50%) scale(1.4);
        opacity: 0;
    }
}

/* Contenu de l'action */
.action-content {
    flex: 1;
    min-width: 0;
}

.action-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #2d3748;
}

.action-description {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
    line-height: 1.4;
}

/* Flèche */
.action-arrow {
    color: #cbd5e0;
    font-size: 1rem;
    transition: all 0.3s ease;
    margin-left: 0.75rem;
}

.modern-action-card:hover .action-arrow {
    color: #667eea;
    transform: translateX(4px);
}

/* Animation de l'icône de pulsation */
.pulse-icon {
    animation: iconPulse 2s ease-in-out infinite;
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Styles spécifiques pour chaque type de carte */
.repair-card:hover {
    border-color: rgba(102, 126, 234, 0.5);
}

.task-card:hover {
    border-color: rgba(17, 153, 142, 0.5);
}

.order-card:hover {
    border-color: rgba(240, 147, 251, 0.5);
}

.clock-in-card:hover {
    border-color: rgba(17, 153, 142, 0.5);
}

.clock-out-card:hover {
    border-color: rgba(255, 107, 107, 0.5);
}

.loading-card {
    opacity: 0.7;
    cursor: default;
}

.loading-card:hover {
    transform: none;
    box-shadow: none;
}

/* Mode sombre */
body.dark-mode .modern-action-card {
    background: rgba(51, 65, 85, 0.95);
    border-color: rgba(148, 163, 184, 0.2);
    color: #f1f5f9;
}

body.dark-mode .action-title {
    color: #f1f5f9;
}

body.dark-mode .action-description {
    color: #cbd5e0;
}

body.dark-mode .modern-action-card:hover {
    color: #f1f5f9;
    border-color: rgba(102, 126, 234, 0.5);
}

/* ========================================= */
/* STYLES SCANNER UNIVERSEL */
/* ========================================= */

.scanner-container {
    position: relative;
    width: 100%;
    height: 400px;
    background: #000;
    border-radius: 0;
    overflow: hidden;
}

#universal_scanner_video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.scanner-frame {
    position: relative;
    width: 250px;
    height: 250px;
    border: 2px solid rgba(139, 92, 246, 0.8);
    border-radius: 12px;
    background: rgba(139, 92, 246, 0.1);
}

.scanner-corners {
    position: absolute;
    inset: -8px;
}

.scanner-corners::before,
.scanner-corners::after {
    content: '';
    position: absolute;
    width: 30px;
    height: 30px;
    border: 4px solid #8b5cf6;
}

.scanner-corners::before {
    top: 0;
    left: 0;
    border-right: none;
    border-bottom: none;
    border-radius: 8px 0 0 0;
}

.scanner-corners::after {
    bottom: 0;
    right: 0;
    border-left: none;
    border-top: none;
    border-radius: 0 0 8px 0;
}

.scanner-line {
    position: absolute;
    top: 50%;
    left: 10%;
    right: 10%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #06b6d4, transparent);
    animation: scannerSweep 2s ease-in-out infinite;
}

@keyframes scannerSweep {
    0%, 100% { transform: translateY(-125px); opacity: 0; }
    50% { transform: translateY(125px); opacity: 1; }
}

.scanner-status {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-align: center;
    background: rgba(139, 92, 246, 0.1);
    border: 1px solid rgba(139, 92, 246, 0.3);
    color: #8b5cf6;
    transition: all 0.3s ease;
}

.scanner-status.success {
    background: rgba(16, 185, 129, 0.1);
    border-color: #10b981;
    color: #10b981;
}

.scanner-status.error {
    background: rgba(239, 68, 68, 0.1);
    border-color: #ef4444;
    color: #ef4444;
}

.scan-mode-selector .btn-outline-primary {
    border-color: rgba(139, 92, 246, 0.5);
    color: #8b5cf6;
}

.scan-mode-selector .btn-outline-primary:hover,
.scan-mode-selector .btn-check:checked + .btn-outline-primary {
    background: #8b5cf6;
    border-color: #8b5cf6;
    color: white;
}

.scanner-actions .btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.scanner-actions .btn:hover {
    transform: translateY(-1px);
}

/* Mode sombre pour le scanner */
body.dark-mode .scanner-status {
    background: rgba(139, 92, 246, 0.15);
    border-color: rgba(139, 92, 246, 0.4);
    color: #a78bfa;
}

body.dark-mode .scanner-status.success {
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.4);
    color: #34d399;
}

body.dark-mode .scanner-status.error {
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.4);
    color: #f87171;
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .scanner-container {
        height: 300px;
    }
    
    .scanner-frame {
        width: 200px;
        height: 200px;
    }
    
    .scanner-actions {
        flex-direction: column;
    }
    
    .scanner-actions .btn {
        margin-bottom: 8px;
    }
}
</style>

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
                    <button type="button" class="modern-action-card task-card" id="openNewTaskFromActions">
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
                    </button>

                    <!-- Nouvelle Commande -->
                    <button type="button" class="modern-action-card order-card" id="openNewOrderFromActions">
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

                    <!-- Pointage Dynamique - Sera rempli par JavaScript -->
                    <div id="dynamic-timetracking-button">
                        <!-- Bouton de chargement temporaire -->
                        <div class="modern-action-card loading-card">
                            <div class="card-glow"></div>
                            <div class="action-icon-container">
                                <div class="action-icon bg-gradient-info">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                                <div class="pulse-ring"></div>
                            </div>
                            <div class="action-content">
                                <h6 class="action-title">Chargement...</h6>
                                <p class="action-description">Vérification de l'état du pointage</p>
                            </div>
                        </div>
                    </div>

                    <!-- Scanner Universel -->
                    <button type="button" class="modern-action-card scanner-card" id="openUniversalScanner">
                        <div class="card-glow"></div>
                        <div class="action-icon-container">
                            <div class="action-icon bg-gradient-scanner">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="pulse-ring"></div>
                        </div>
                        <div class="action-content">
                            <h6 class="action-title">Scanner</h6>
                            <p class="action-description">QR codes et codes-barres</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </button>
                </div>
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
<!-- MODAL: SCANNER UNIVERSEL - QR + CODES-BARRES -->
<!-- ========================================= -->
<div class="modal fade" id="universal_scanner_modal" tabindex="-1" aria-labelledby="universal_scanner_modal_label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg modern-modal">
            <div class="modal-header border-0 bg-gradient-scanner">
                <h5 class="modal-title text-white fw-bold" id="universal_scanner_modal_label">
                    <i class="fas fa-qrcode me-2 pulse-icon"></i>
                    Scanner Universel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="stopUniversalScanner()"></button>
            </div>
            <div class="modal-body p-0 position-relative">
                <!-- Scanner Video -->
                <div class="scanner-container">
                    <video id="universal_scanner_video" autoplay muted playsinline></video>
                    <div class="scanner-overlay">
                        <div class="scanner-frame">
                            <div class="scanner-corners"></div>
                        </div>
                        <div class="scanner-line"></div>
                    </div>
                </div>
                
                <!-- Status et contrôles -->
                <div class="scanner-controls p-4">
                    <div class="scanner-status mb-3" id="universal_scanner_status">
                        <i class="fas fa-camera me-2"></i>
                        Positionnez le code dans le cadre
                    </div>
                    
                    <!-- Mode de scan -->
                    <div class="scan-mode-selector mb-3">
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="scanMode" id="modeAuto" value="auto" checked>
                            <label class="btn btn-outline-primary" for="modeAuto">
                                <i class="fas fa-magic me-1"></i>Auto
                            </label>
                            
                            <input type="radio" class="btn-check" name="scanMode" id="modeQR" value="qr">
                            <label class="btn btn-outline-primary" for="modeQR">
                                <i class="fas fa-qrcode me-1"></i>QR Code
                            </label>
                            
                            <input type="radio" class="btn-check" name="scanMode" id="modeBarcode" value="barcode">
                            <label class="btn btn-outline-primary" for="modeBarcode">
                                <i class="fas fa-barcode me-1"></i>Code-barres
                            </label>
                        </div>
                    </div>
                    
                    <!-- Boutons d'action -->
                    <div class="scanner-actions d-flex gap-2">
                        <button class="btn btn-secondary flex-fill" onclick="toggleScannerFlash()">
                            <i class="fas fa-flashlight" id="flashIcon"></i>
                            Flash
                        </button>
                        <button class="btn btn-info flex-fill" onclick="switchCamera()">
                            <i class="fas fa-camera-rotate"></i>
                            Caméra
                        </button>
                        <button class="btn btn-warning flex-fill" onclick="manualCodeEntry()">
                            <i class="fas fa-keyboard"></i>
                            Manuel
                        </button>
                    </div>
                </div>
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
                    <style>
                        /* Masquer l'ancien contenu pour éviter les doublons */
                        #menu_navigation_modal .modern-nav-grid .nav-section-header,
                        #menu_navigation_modal .modern-nav-grid .nav-grid-row { display:none !important; }
                        /* Mise en page des lignes personnalisées */
                        #menu_navigation_modal .curated-row { 
                            display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:12px; margin-bottom:14px;
                        }
                        @media (max-width: 768px){
                            #menu_navigation_modal .curated-row { grid-template-columns: 1fr; }
                        }
                    </style>

                    <!-- CURATED MENU (sans doublons) -->
                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-layer-group me-2"></i>Général</h6></div>
                    <div class="curated-row">
                        <a href="index.php" class="modern-nav-card home-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-home"><i class="fas fa-home"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Accueil</h6><p class="nav-subtitle">Tableau de bord</p></div>
                        </a>
                        <a href="index.php?page=reparations" class="modern-nav-card repair-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-repair"><i class="fas fa-tools"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Réparations</h6><p class="nav-subtitle">Gestion des réparations</p></div>
                        </a>
                        <a href="index.php?page=taches" class="modern-nav-card tasks-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-tasks"><i class="fas fa-tasks"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Tâches</h6><p class="nav-subtitle">Gestion des tâches</p></div>
                        </a>
                    </div>

                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-box-open me-2"></i>Stock & Connaissance</h6></div>
                    <div class="curated-row">
                        <a href="index.php?page=commandes_pieces" class="modern-nav-card orders-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-orders"><i class="fas fa-shopping-cart"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Commandes</h6><p class="nav-subtitle">Pièces</p></div>
                        </a>
                        <a href="index.php?page=rachat_appareils" class="modern-nav-card rachat-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-rachat"><i class="fas fa-exchange-alt"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Rachat</h6><p class="nav-subtitle">Appareils</p></div>
                        </a>
                        <a href="index.php?page=base_connaissances" class="modern-nav-card knowledge-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-knowledge"><i class="fas fa-book"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Base de connaissance</h6><p class="nav-subtitle">Documentation</p></div>
                        </a>
                    </div>

                    <hr class="my-2"/>

                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-users me-2"></i>Clients & Missions</h6></div>
                    <div class="curated-row">
                        <a href="index.php?page=clients" class="modern-nav-card clients-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-clients"><i class="fas fa-users"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Clients</h6><p class="nav-subtitle">Base clients</p></div>
                        </a>
                        <a href="index.php?page=sms_historique" class="modern-nav-card sms-history-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-sms-history"><i class="fas fa-history"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Historique SMS</h6><p class="nav-subtitle">Envois</p></div>
                        </a>
                        <a href="index.php?page=presence_gestion" class="modern-nav-card absences-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-absences"><i class="fas fa-user-clock"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Absence & Retard</h6><p class="nav-subtitle">Présence</p></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=mes_missions" class="modern-nav-card my-missions-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-my-missions"><i class="fas fa-clipboard-check"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Mes missions</h6><p class="nav-subtitle">Personnel</p></div>
                        </a>
                        <a href="index.php?page=inventaire" class="modern-nav-card orders-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-orders"><i class="fas fa-boxes"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Inventaire</h6><p class="nav-subtitle">Produits</p></div>
                        </a>
                    </div>

                    <hr class="my-2"/>

                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-bug me-2"></i>Qualité</h6></div>
                    <div class="curated-row">
                        <a href="index.php?page=bug-reports" class="modern-nav-card bug-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-logout"><i class="fas fa-bug"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Bug report</h6><p class="nav-subtitle">Signalements</p></div>
                        </a>
                    </div>

                    <hr class="my-2"/>

                    <div class="nav-section-header curated-section"><h6 class="section-title"><i class="fas fa-shield-alt me-2"></i>Administration</h6></div>
                    <div class="curated-row">
                        <a href="index.php?page=comptes_partenaires" class="modern-nav-card admin-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-administration"><i class="fas fa-handshake"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Compte partenaire</h6></div>
                        </a>
                        <a href="index.php?page=employes" class="modern-nav-card employees-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-employees"><i class="fas fa-user-tie"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Employé</h6></div>
                        </a>
                        <a href="index.php?page=admin_timetracking" class="modern-nav-card timetracking-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-administration"><i class="fas fa-clock"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Pointage Admin</h6></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=reparation_logs" class="modern-nav-card logs-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-logs"><i class="fas fa-clipboard-list"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Journaux de réparation</h6></div>
                        </a>
                        <a href="index.php?page=parametre" class="modern-nav-card settings-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-special"><i class="fas fa-cog"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Parametre</h6></div>
                        </a>
                        <a href="index.php?page=template_sms" class="modern-nav-card sms-template-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-sms-template"><i class="fas fa-comment-dots"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Template SMS</h6></div>
                        </a>
                    </div>
                    <div class="curated-row">
                        <a href="index.php?page=campagne_sms" class="modern-nav-card sms-campaign-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-sms-campaign"><i class="fas fa-sms"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Campagne SMS</h6></div>
                        </a>
                        <a href="index.php?page=admin_missions" class="modern-nav-card admin-missions-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-admin-missions"><i class="fas fa-tasks"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Admin mission</h6></div>
                        </a>
                    </div>
                    <hr class="my-2"/>
                    <div class="curated-row">
                        <a href="index.php?action=logout" class="modern-nav-card logout-card">
                            <div class="nav-card-background"></div>
                            <div class="nav-icon-container"><div class="nav-icon bg-gradient-logout"><i class="fas fa-sign-out-alt"></i></div></div>
                            <div class="nav-content"><h6 class="nav-title">Déconnexion</h6></div>
                        </a>
                    </div>
                    
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
<style>
/* Correction immédiate mode sombre pour ajouterCommandeModal */
.dark-mode #ajouterCommandeModal .modal-content {
    background-color: #111827 !important;
    border: 1px solid #374151 !important;
}
.dark-mode #ajouterCommandeModal .modal-body {
    background-color: #111827 !important;
    color: #f8fafc !important;
}
.dark-mode #ajouterCommandeModal .modal-header {
    background: linear-gradient(135deg, #1f2937, #111827) !important;
    color: #f8fafc !important;
    border-bottom: 1px solid #374151 !important;
}
.dark-mode #ajouterCommandeModal .form-control,
.dark-mode #ajouterCommandeModal .form-select,
.dark-mode #ajouterCommandeModal input,
.dark-mode #ajouterCommandeModal select {
    background-color: #1f2937 !important;
    border-color: #374151 !important;
    color: #f8fafc !important;
}
.dark-mode #ajouterCommandeModal .form-control:focus,
.dark-mode #ajouterCommandeModal .form-select:focus {
    background-color: #1f2937 !important;
    border-color: #60a5fa !important;
    color: #f8fafc !important;
    box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.25) !important;
}
.dark-mode #ajouterCommandeModal .form-control::placeholder {
    color: #9ca3af !important;
}
.dark-mode #ajouterCommandeModal select option {
    background-color: #1f2937 !important;
    color: #f8fafc !important;
}
.dark-mode #ajouterCommandeModal .order-section-title {
    color: #f8fafc !important;
}

/* Supprimer les contours rouges de validation */
#ajouterCommandeModal .form-control.is-invalid,
#ajouterCommandeModal .form-select.is-invalid {
    border-color: #374151 !important;
    box-shadow: none !important;
}

.dark-mode #ajouterCommandeModal .form-control.is-invalid,
.dark-mode #ajouterCommandeModal .form-select.is-invalid {
    border-color: #374151 !important;
    box-shadow: none !important;
}

/* Supprimer les contours verts aussi */
#ajouterCommandeModal .form-control.is-valid,
#ajouterCommandeModal .form-select.is-valid {
    border-color: #374151 !important;
    box-shadow: none !important;
}

.dark-mode #ajouterCommandeModal .form-control.is-valid,
.dark-mode #ajouterCommandeModal .form-select.is-valid {
    border-color: #374151 !important;
    box-shadow: none !important;
}
.dark-mode #ajouterCommandeModal .btn-outline-primary {
    background-color: transparent !important;
    border-color: #60a5fa !important;
    color: #60a5fa !important;
}
.dark-mode #ajouterCommandeModal .btn-outline-primary:hover {
    background-color: #60a5fa !important;
    color: #ffffff !important;
}
/* Styles supplémentaires pour tous les éléments de formulaire */
.dark-mode #ajouterCommandeModal input[type="text"],
.dark-mode #ajouterCommandeModal input[type="number"],
.dark-mode #ajouterCommandeModal textarea,
.dark-mode #ajouterCommandeModal .form-control,
.dark-mode #ajouterCommandeModal .form-select {
    background-color: #1f2937 !important;
    border-color: #374151 !important;
    color: #f8fafc !important;
}
.dark-mode #ajouterCommandeModal input[type="text"]:focus,
.dark-mode #ajouterCommandeModal input[type="number"]:focus,
.dark-mode #ajouterCommandeModal textarea:focus,
.dark-mode #ajouterCommandeModal .form-control:focus,
.dark-mode #ajouterCommandeModal .form-select:focus {
    background-color: #1f2937 !important;
    border-color: #60a5fa !important;
    color: #f8fafc !important;
    box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.25) !important;
}
/* Champs spécifiques par ID */
.dark-mode #nom_client_selectionne,
.dark-mode #fournisseur_id_ajout,
.dark-mode #nom_piece,
.dark-mode #code_barre,
.dark-mode #quantite,
.dark-mode #prix_estime {
    background-color: #1f2937 !important;
    border-color: #374151 !important;
    color: #f8fafc !important;
}
/* Labels et textes */
.dark-mode #ajouterCommandeModal label,
.dark-mode #ajouterCommandeModal .form-label,
.dark-mode #ajouterCommandeModal span:not(.badge) {
    color: #f8fafc !important;
}
/* Status pills */
.dark-mode #ajouterCommandeModal .status-pill {
    background: #1f2937 !important;
    border: 1px solid #374151 !important;
    color: #e5e7eb !important;
    transition: background-color .2s ease, color .2s ease, border-color .2s ease !important;
}
.dark-mode #ajouterCommandeModal .status-pill:hover {
    background: #374151 !important;
}
.dark-mode #ajouterCommandeModal .status-pill:not(.active) {
    background: #1f2937 !important;
    border-color: #374151 !important;
    color: #e5e7eb !important;
}
.dark-mode #ajouterCommandeModal .status-pill.active {
    background: #2563eb !important; /* bleu vif pour l'actif */
    border-color: #3b82f6 !important;
    color: #ffffff !important;
}
.dark-mode #ajouterCommandeModal .status-pill i {
    color: inherit !important;
}
/* Boutons de quantité */
.dark-mode #ajouterCommandeModal .btn-outline-secondary {
    background-color: #374151 !important;
    border-color: #4b5563 !important;
    color: #f8fafc !important;
}
.dark-mode #ajouterCommandeModal .btn-outline-secondary:hover {
    background-color: #4b5563 !important;
    color: #ffffff !important;
}
/* Fonds sombres pour les conteneurs de section et le corps moderne */
.dark-mode #ajouterCommandeModal .modern-modal-body {
    background: #0b1220 !important;
}
.dark-mode #ajouterCommandeModal .order-section {
    background: #0f172a !important;
    border: 1px solid #1f2937 !important;
    border-radius: 12px !important;
    padding: 16px !important;
}
.dark-mode #ajouterCommandeModal .order-section + .order-section {
    margin-top: 16px !important;
}
.dark-mode #ajouterCommandeModal .order-section-title {
    color: #e5e7eb !important;
}
/* Force maximale pour tous les champs - priorité absolue */
body.dark-mode #ajouterCommandeModal input,
body.dark-mode #ajouterCommandeModal select,
body.dark-mode #ajouterCommandeModal textarea,
html body.dark-mode #ajouterCommandeModal .form-control,
html body.dark-mode #ajouterCommandeModal .form-select {
    background-color: #1f2937 !important;
    border: 1px solid #374151 !important;
    color: #f8fafc !important;
}
body.dark-mode #ajouterCommandeModal input:focus,
body.dark-mode #ajouterCommandeModal select:focus,
body.dark-mode #ajouterCommandeModal textarea:focus,
html body.dark-mode #ajouterCommandeModal .form-control:focus,
html body.dark-mode #ajouterCommandeModal .form-select:focus {
    background-color: #1f2937 !important;
    border: 1px solid #60a5fa !important;
    color: #f8fafc !important;
    box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.25) !important;
}
/* Force pour les champs par ID avec spécificité maximale */
html body.dark-mode div#ajouterCommandeModal input#nom_client_selectionne,
html body.dark-mode div#ajouterCommandeModal select#fournisseur_id_ajout,
html body.dark-mode div#ajouterCommandeModal input#nom_piece,
html body.dark-mode div#ajouterCommandeModal input#code_barre,
html body.dark-mode div#ajouterCommandeModal input#quantite,
html body.dark-mode div#ajouterCommandeModal input#prix_estime {
    background-color: #1f2937 !important;
    border: 1px solid #374151 !important;
    color: #f8fafc !important;
}

/* Styles pour les pièces supplémentaires */
.order-grid {
    display: grid;
    grid-template-columns: 2fr 1fr auto 1fr auto;
    gap: 10px;
    align-items: center;
}

.order-grid.mt-2 {
    margin-top: 15px !important;
    padding: 15px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background-color: #f8f9fa;
    position: relative;
}

.dark-mode .order-grid.mt-2 {
    border-color: #374151 !important;
    background-color: #1f2937 !important;
}

.remove-piece-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.remove-piece-btn i {
    font-size: 12px;
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .order-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .order-grid.mt-2 {
        padding: 10px;
    }
}
</style>
<div class="modal fade" id="ajouterCommandeModal" tabindex="-1" aria-labelledby="ajouterCommandeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content modern-modal">
            <!-- En-tête du formulaire -->
            <div class="modal-header bg-gradient-warning">
                <h2 class="modal-title text-white"><i class="fas fa-shopping-cart me-2"></i> Nouvelle commande de pièces</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Corps du formulaire -->
            <div class="modal-body modern-modal-body p-4">
                <form id="ajouterCommandeForm" method="post" action="ajax/add_commande.php">
                    <!-- Section Client -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-user-circle"></i> Client
                        </div>
                        <div class="form-group mb-3">
                            <input type="hidden" id="client_id" name="client_id" />
                            <div id="client_selectionne" class="d-none mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user me-2"></i>
                                    <span class="nom_client fw-semibold"></span>&nbsp;|&nbsp;
                                    <span class="tel_client text-muted small"></span>
                                </div>
                            </div>
                            <input type="text" class="form-control" id="nom_client_selectionne" placeholder="Saisir ou rechercher un client" aria-label="Rechercher un client">
                            <div id="resultats_recherche_client_inline" class="mt-2 d-none">
                                <div id="liste_clients_recherche_inline" class="list-group"></div>
                            </div>
                            <button type="button" id="newClientBtn" class="btn btn-outline-primary w-100 mt-2">
                                <i class="fas fa-user-plus me-2"></i>+Créer un nouveau client
                            </button>
                        </div>
                    </div>

                    <!-- Section Fournisseur -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-truck"></i> Fournisseur
                        </div>
                        <div class="form-group">
                            <select class="form-select" name="fournisseur_id" id="fournisseur_id_ajout" required>
                                <option value="">Sélectionner un fournisseur...</option>
                                <!-- options chargées dynamiquement -->
                            </select>
                        </div>
                    </div>

                    <!-- Section Pièce -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-cog"></i> Pièce commandée
                        </div>
                        <div class="order-grid">
                            <input type="text" class="form-control" name="nom_piece" id="nom_piece" placeholder="Désignation de la pièce" required>
                            <input type="text" class="form-control" name="code_barre" id="code_barre" placeholder="Saisir le code barre">
                            <div class="quantity-selector">
                                <button type="button" class="btn btn-outline-secondary" id="decrease-qty">–</button>
                                <input type="number" class="form-control text-center" name="quantite" id="quantite" value="1" min="1">
                                <button type="button" class="btn btn-outline-secondary" id="increase-qty">+</button>
                            </div>
                            <input type="number" class="form-control" name="prix_estime" id="prix_estime" placeholder="0.00" step="0.01" min="0" required>
                        </div>
                        <div class="text-end mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="ajouter-piece-btn">
                                <i class="fas fa-plus me-1"></i> Ajouter une autre pièce
                            </button>
                        </div>
                    </div>

                    <!-- Section Statut -->
                    <div class="order-section">
                        <div class="order-section-title">
                            <i class="fas fa-info-circle"></i> Statut
                        </div>
                        <div class="d-flex gap-3">
                            <label class="status-pill active">
                                <input type="radio" name="statut" id="statusPending" value="en_attente" checked>
                                <i class="fas fa-clock"></i> En attente
                            </label>
                            <label class="status-pill">
                                <input type="radio" name="statut" id="statusOrdered" value="commande">
                                <i class="fas fa-shopping-cart"></i> Commandé
                            </label>
                            <label class="status-pill">
                                <input type="radio" name="statut" id="statusReceived" value="recue">
                                <i class="fas fa-check-circle"></i> Reçu
                            </label>
                        </div>
                    </div>

                </form>
            </div>

            <!-- Footer avec boutons d'actions -->
            <div class="modal-footer modern-modal-footer border-0 p-4">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-ripple btn-gradient-primary" id="saveCommandeBtn">
                        <i class="fas fa-save me-2"></i> Enregistrer la commande
                    </button>
                </div>
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

    // Gestion des boutons radio de statut - Géré par modal-commande.js
    
    // Corrige le problème du backdrop qui bloque les interactions
    const fixModalBackdrop = function() {
        const modal = document.getElementById('ajouterCommandeModal');
        
        // Ajuster le modal quand il est ouvert
        modal.addEventListener('shown.bs.modal', function() {
            // Nettoyages agressifs désactivés
        });
        
        // Quand le modal est fermé, nettoyer les backdrops et restaurer le scroll
        modal.addEventListener('hidden.bs.modal', function() {
            // Nettoyages agressifs désactivés
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
<!-- MODAL: NOUVELLE TÂCHE - DESIGN ULTRA-MODERNE -->
<!-- ========================================= -->
<div class="modal fade" id="ajouterTacheModal" tabindex="-1" aria-labelledby="ajouterTacheModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content ultra-modern-task-modal">
            
            <!-- Header Ultra-Moderne -->
            <div class="ultra-modal-header">
                <div class="header-background-effect"></div>
                <div class="header-content">
                    <div class="header-icon-container">
                        <div class="rotating-icon-bg"></div>
                        <i class="fas fa-plus-circle header-main-icon"></i>
                    </div>
                    <div class="header-text-container">
                        <h2 class="modal-title ultra-title" id="ajouterTacheModalLabel">Nouvelle Tâche</h2>
                        <p class="modal-subtitle">Créer une nouvelle tâche pour votre équipe</p>
                    </div>
                </div>
                <button type="button" class="ultra-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <span class="close-icon"></span>
                    <span class="close-icon"></span>
                </button>
            </div>

            <!-- Body Ultra-Moderne -->
            <div class="ultra-modal-body">
                <!-- Effet de grille animée -->
                <div class="animated-grid-bg"></div>
                
                <!-- Alertes modernes -->
                <div id="taskModalErrors" class="ultra-alert ultra-alert-danger d-none">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <ul class="mb-0" id="taskErrorsList"></ul>
                    </div>
                </div>

                <div id="taskModalSuccess" class="ultra-alert ultra-alert-success d-none">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-content">
                        <span id="taskSuccessMessage"></span>
                    </div>
                </div>

                <form id="taskModalForm" class="ultra-task-form">
                    
                    <!-- Section 1: Informations Principales -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-number">01</div>
                            <h3 class="section-title">Informations Principales</h3>
                            <div class="section-line"></div>
                        </div>
                        
                        <div class="form-grid">
                            <!-- Titre -->
                            <div class="form-group full-width">
                                <label for="modal_titre" class="ultra-label">
                                    <span class="label-icon">
                                        <i class="fas fa-heading"></i>
                                    </span>
                                    <span class="label-text">Titre de la tâche *</span>
                                </label>
                                <div class="ultra-input-container">
                                    <input type="text" class="ultra-input" id="modal_titre" name="titre" required
                                           placeholder="Saisissez un titre clair et concis">
                                    <div class="input-focus-line"></div>
                                    <div class="input-floating-placeholder">Titre de la tâche</div>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div class="form-group full-width">
                                <label for="modal_description" class="ultra-label">
                                    <span class="label-icon">
                                        <i class="fas fa-align-left"></i>
                                    </span>
                                    <span class="label-text">Description *</span>
                                </label>
                                <div class="ultra-textarea-container">
                                    <textarea class="ultra-textarea" id="modal_description" name="description" rows="4" required
                                              placeholder="Détaillez la tâche à accomplir..."></textarea>
                                    <div class="textarea-focus-line"></div>
                                    <div class="textarea-floating-placeholder">Description détaillée</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Priorité et Échéance -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-number">02</div>
                            <h3 class="section-title">Priorité & Échéance</h3>
                            <div class="section-line"></div>
                        </div>
                        
                        <div class="form-grid">
                            <!-- Priorité -->
                            <div class="form-group">
                                <label class="ultra-label">
                                    <span class="label-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                    <span class="label-text">Niveau de priorité *</span>
                                </label>
                                <div class="priority-selector">
                                    <button type="button" class="priority-btn priority-low" data-value="basse">
                                        <span class="priority-icon">
                                            <i class="fas fa-angle-down"></i>
                                        </span>
                                        <span class="priority-text">Basse</span>
                                        <div class="priority-indicator"></div>
                                    </button>
                                    <button type="button" class="priority-btn priority-medium" data-value="moyenne">
                                        <span class="priority-icon">
                                            <i class="fas fa-equals"></i>
                                        </span>
                                        <span class="priority-text">Moyenne</span>
                                        <div class="priority-indicator"></div>
                                    </button>
                                    <button type="button" class="priority-btn priority-high" data-value="haute">
                                        <span class="priority-icon">
                                            <i class="fas fa-angle-up"></i>
                                        </span>
                                        <span class="priority-text">Haute</span>
                                        <div class="priority-indicator"></div>
                                    </button>
                                    <button type="button" class="priority-btn priority-urgent" data-value="urgente">
                                        <span class="priority-icon">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </span>
                                        <span class="priority-text">Urgente</span>
                                        <div class="priority-indicator"></div>
                                    </button>
                                </div>
                                <input type="hidden" name="priorite" id="modal_priorite" value="">
                            </div>
                            
                            <!-- Date limite -->
                            <div class="form-group">
                                <label for="modal_date_limite" class="ultra-label">
                                    <span class="label-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                    <span class="label-text">Date limite</span>
                                </label>
                                <div class="ultra-date-container">
                                    <div class="date-input-wrapper">
                                        <input type="date" class="ultra-date-input" id="modal_date_limite" name="date_limite">
                                        <div class="date-icon-overlay">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                    </div>
                                    <div class="input-focus-line"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Attribution -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-number">03</div>
                            <h3 class="section-title">Attribution</h3>
                            <div class="section-line"></div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label class="ultra-label">
                                    <span class="label-icon">
                                        <i class="fas fa-user-check"></i>
                                    </span>
                                    <span class="label-text">Assigner à</span>
                                </label>
                                <div class="user-selection-modern">
                                    <div id="userButtonsContainer" class="users-grid">
                                        <!-- Les boutons utilisateurs seront chargés ici -->
                                        <div class="loading-users">
                                            <div class="loading-spinner-modern">
                                                <div class="spinner-ring"></div>
                                                <div class="spinner-ring"></div>
                                                <div class="spinner-ring"></div>
                                            </div>
                                            <p class="loading-text">Chargement des utilisateurs...</p>
                                        </div>
                                    </div>
                                    <input type="hidden" name="employe_id" id="modal_employe_id" value="">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Pièces Jointes -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-number">04</div>
                            <h3 class="section-title">Pièces Jointes</h3>
                            <div class="section-line"></div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label class="ultra-label">
                                    <span class="label-icon">
                                        <i class="fas fa-paperclip"></i>
                                    </span>
                                    <span class="label-text">Fichiers <small class="optional-text">(facultatif)</small></span>
                                </label>
                                <div class="ultra-dropzone" id="modalFileDropZone">
                                    <div class="dropzone-content">
                                        <div class="upload-icon-container">
                                            <div class="upload-icon-bg"></div>
                                            <i class="fas fa-cloud-upload-alt upload-main-icon"></i>
                                        </div>
                                        <h4 class="dropzone-title">Glissez-déposez vos fichiers</h4>
                                        <p class="dropzone-subtitle">ou cliquez pour parcourir</p>
                                        <button type="button" class="ultra-browse-btn" id="modalSelectFilesBtn">
                                            <i class="fas fa-folder-open me-2"></i>
                                            <span>Parcourir</span>
                                        </button>
                                        <input type="file" name="attachments[]" id="modalFileInput" multiple 
                                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.xlsx,.xls,.zip,.rar" style="display: none;">
                                        <div class="file-types-info">
                                            <small>
                                                <i class="fas fa-info-circle"></i>
                                                JPG, PNG, PDF, DOC, TXT, XLS, ZIP (max 10MB)
                                            </small>
                                        </div>
                                    </div>
                                    <div class="dropzone-overlay">
                                        <div class="drop-indicator">
                                            <i class="fas fa-download"></i>
                                            <span>Relâchez pour uploader</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="modalSelectedFiles" class="selected-files-container" style="display: none;">
                                    <div class="files-header">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Fichiers sélectionnés</span>
                                    </div>
                                    <div id="modalFilesList" class="files-list">
                                        <!-- Les fichiers sélectionnés apparaîtront ici -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Champ statut caché -->
                    <input type="hidden" name="statut" id="modal_statut" value="a_faire">
                </form>
            </div>
            
            <!-- Footer Ultra-Moderne -->
            <div class="ultra-modal-footer">
                <div class="footer-background-effect"></div>
                <div class="footer-content">
                    <div class="footer-info">
                        <div class="info-icon">
                            <i class="fas fa-magic"></i>
                        </div>
                        <span class="info-text">Les champs marqués d'un * sont obligatoires</span>
                    </div>
                    <div class="footer-actions">
                        <button type="button" class="ultra-btn ultra-btn-secondary" data-bs-dismiss="modal">
                            <span class="btn-icon">
                                <i class="fas fa-times"></i>
                            </span>
                            <span class="btn-text">Annuler</span>
                            <div class="btn-ripple"></div>
                        </button>
                        <button type="button" class="ultra-btn ultra-btn-primary" id="saveTaskBtn">
                            <span class="btn-icon">
                                <i class="fas fa-save"></i>
                            </span>
                            <span class="btn-text">Enregistrer</span>
                            <span class="btn-loading d-none">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>Enregistrement...</span>
                            </span>
                            <div class="btn-ripple"></div>
                            <div class="btn-glow"></div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS ultra-moderne pour le modal de tâche -->
<link rel="stylesheet" href="<?php echo $assets_path; ?>css/modal-tache-ultra-moderne.css?v=<?php echo time(); ?>">

<!-- Script d'adaptation pour le nouveau design -->
<script>
console.log('🚀 Script de debug modal ultra-moderne chargé');

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM chargé, initialisation du modal ultra-moderne');
    
    // Debug - Vérifier si les éléments existent
    const modal = document.getElementById('ajouterTacheModal');
    const modalContent = modal ? modal.querySelector('.modal-content') : null;
    const ultraHeader = modal ? modal.querySelector('.ultra-modal-header') : null;
    const ultraBody = modal ? modal.querySelector('.ultra-modal-body') : null;
    
    console.log('🔍 Éléments trouvés:', {
        modal: modal ? '✅' : '❌',
        modalContent: modalContent ? '✅' : '❌',
        ultraHeader: ultraHeader ? '✅' : '❌',
        ultraBody: ultraBody ? '✅' : '❌'
    });
    
    if (modal) {
        modal.addEventListener('show.bs.modal', function() {
            console.log('🎭 Modal en cours d\'ouverture');
        });
        
        modal.addEventListener('shown.bs.modal', function() {
            console.log('✨ Modal ouvert avec succès');
            console.log('📊 Classes du modal-content:', modalContent ? modalContent.className : 'N/A');
        });
    }
    // Adapter les boutons de priorité pour le nouveau design
    const priorityButtons = document.querySelectorAll('.priority-btn');
    priorityButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            priorityButtons.forEach(btn => btn.classList.remove('active'));
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            // Mettre à jour le champ caché
            document.getElementById('modal_priorite').value = this.dataset.value;
        });
    });
    
    // Adapter la dropzone pour le nouveau design
    const dropzone = document.getElementById('modalFileDropZone');
    if (dropzone) {
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            // Le reste de la logique de drop reste inchangé
        });
    }
    
    // Effet ripple pour les boutons
    const ultraBtns = document.querySelectorAll('.ultra-btn');
    ultraBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const ripple = this.querySelector('.btn-ripple');
            if (ripple) {
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                ripple.classList.remove('btn-ripple');
                void ripple.offsetWidth; // Force reflow
                ripple.classList.add('btn-ripple');
            }
        });
    });
});
</script>

<!-- Styles supprimés - utilisation du CSS ultra-moderne uniquement -->

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
}

// Fonction pour ouvrir le modal de nouvelles actions
function openNouvellesActionsModal() {
    const modal = new bootstrap.Modal(document.getElementById('nouvelles_actions_modal'));
    modal.show();
}

// Fonctions pour les différentes actions
function nouvelleReparation() {
    window.location.href = 'reparations.php?action=nouvelle';
}

function nouvelleCommande() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal'));
    if (modal) modal.hide();
    
    setTimeout(() => {
        const commandeModal = new bootstrap.Modal(document.getElementById('ajouterCommandeModal'));
        commandeModal.show();
    }, 300);
}

function nouvelleTache() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal'));
    if (modal) modal.hide();
    
    setTimeout(() => {
        const tacheModal = new bootstrap.Modal(document.getElementById('ajouterTacheModal'));
        tacheModal.show();
    }, 300);
}

function ouvrirRecherche() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal'));
    if (modal) modal.hide();
    
    setTimeout(() => {
        const rechercheModal = new bootstrap.Modal(document.getElementById('rechercheModal'));
        rechercheModal.show();
    }, 300);
}

// Scanner universel
function initScanner(mode = 'auto') {
    console.log('Initialisation du scanner en mode:', mode);
    
    // Logique du scanner...
    if (typeof startScanning === 'function') {
        setTimeout(() => {
            if (mode === 'qr' || mode === 'auto') {
                startScanning();
            }
        }, 100);
    }
}

</script>
    align-items: center;
    gap: 0.5rem;
}

.order-section-title i {
    color: #007bff;
}

/* CSS pour les status pills */
.status-pill {
    background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
    border: 2px solid #dee2e6;
    border-radius: 25px;
    padding: 0.75rem 1.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    color: #6c757d;
    position: relative;
    overflow: hidden;
}

.status-pill input[type="radio"] {
    display: none;
}

.status-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border-color: #007bff;
}

.status-pill.active,
.status-pill:has(input:checked) {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border-color: #007bff;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.3);
}

.status-pill i {
    font-size: 1rem;
}

/* Grid pour les pièces */
.order-grid {
    display: grid;
    grid-template-columns: 2fr 1fr auto 1fr;
    gap: 1rem;
    align-items: center;
}

.quantity-selector {
    display: flex;
    border: 1px solid #ced4da;
    border-radius: 6px;
    overflow: hidden;
}

.quantity-selector button {
    border: none;
    background: #f8f9fa;
    width: 35px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.quantity-selector input {
    border: none;
    width: 60px;
    text-align: center;
}

/* CSS pour le mode nuit */
[data-theme="dark"] .order-section {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    border-color: #444;
}

[data-theme="dark"] .order-section-title {
    color: #ffffff;
}

[data-theme="dark"] .order-section-title i {
    color: #0d6efd;
}

/* Particules animées pour le modal tâche */
.task-particles-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.task-particle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: linear-gradient(45deg, #28a745, #20c997);
    border-radius: 50%;
    animation: taskParticleFloat 6s ease-in-out infinite;
}

@keyframes taskParticleFloat {
    0%, 100% { 
        transform: translateY(0) rotate(0deg); 
        opacity: 0.3; 
    }
    50% { 
        transform: translateY(-30px) rotate(180deg); 
        opacity: 0.8; 
    }
}

/* Labels modernes */
.task-label {
    color: #2d3748;
    font-size: 1rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
}

/* Inputs modernes */
.modern-input, .modern-textarea {
    border: 2px solid #e2e8f0 !important;
    border-radius: 12px !important;
    padding: 0.875rem 1rem !important;
    font-size: 1rem !important;
    transition: all 0.3s ease !important;
    background: rgba(255, 255, 255, 0.9) !important;
    position: relative;
    z-index: 2;
}

.modern-input:focus, .modern-textarea:focus {
    border-color: #28a745 !important;
    box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.15) !important;
    background: #ffffff !important;
    transform: translateY(-2px);
}

.modern-input-group {
    border-radius: 12px !important;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.modern-input-addon {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
    color: white !important;
    border: none !important;
    font-size: 1.1rem;
}

/* Effet de lueur sur les inputs */
.input-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 12px;
    background: linear-gradient(45deg, transparent, rgba(40, 167, 69, 0.1), transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.modern-input:focus + .input-glow,
.modern-textarea:focus + .input-glow {
    opacity: 1;
}

/* Groupes de boutons modernes */
.modern-button-group {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 12px;
    padding: 0.25rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.modern-btn {
    border-radius: 8px !important;
    border-width: 2px !important;
    font-weight: 500 !important;
    transition: all 0.3s ease !important;
    position: relative;
    overflow: hidden;
}

.modern-btn:before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.modern-btn:hover:before {
    left: 100%;
}

.modern-btn.active {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Styles spécifiques pour les boutons de priorité actifs */
.btn-priority[data-value="basse"].active {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
    color: white !important;
    border-color: #28a745 !important;
}

.btn-priority[data-value="moyenne"].active {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
    color: white !important;
    border-color: #007bff !important;
}

.btn-priority[data-value="haute"].active {
    background: linear-gradient(135deg, #ffc107, #e0a800) !important;
    color: #212529 !important;
    border-color: #ffc107 !important;
}

.btn-priority[data-value="urgente"].active {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
    color: white !important;
    border-color: #dc3545 !important;
}

/* Styles spécifiques pour les boutons de statut supprimés car plus utilisés */

/* Boutons utilisateurs */
.user-btn {
    background: rgba(255, 255, 255, 0.9) !important;
    border: 2px solid #e2e8f0 !important;
    border-radius: 12px !important;
    padding: 0.75rem 1rem !important;
    margin: 0.25rem !important;
    transition: all 0.3s ease !important;
    font-weight: 500 !important;
}

.user-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    border-color: #28a745 !important;
}

.user-btn.active {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
    color: white !important;
    border-color: #28a745 !important;
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 16px rgba(40, 167, 69, 0.3);
}

/* Section pièces jointes moderne */
.modern-attachment {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.modern-drop-zone {
    border: 3px dashed #e2e8f0 !important;
    border-radius: 12px !important;
    background: linear-gradient(135deg, #f8f9fa, #ffffff) !important;
    transition: all 0.3s ease !important;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.modern-drop-zone:hover,
.modern-drop-zone.dragover {
    border-color: #28a745 !important;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.05), rgba(32, 201, 151, 0.05)) !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(40, 167, 69, 0.1);
}

.upload-icon {
    animation: uploadPulse 2s ease-in-out infinite;
}

@keyframes uploadPulse {
    0%, 100% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.05); opacity: 1; }
}

/* Liste des fichiers moderne */
.modern-file-list .file-item {
    background: rgba(255, 255, 255, 0.95) !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;
    margin-bottom: 0.75rem !important;
    padding: 1rem !important;
    transition: all 0.3s ease !important;
    position: relative;
    overflow: hidden;
}

.modern-file-list .file-item:hover {
    transform: translateX(8px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    border-color: #28a745 !important;
}

.modern-file-list .file-item:before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(135deg, #28a745, #20c997);
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.modern-file-list .file-item:hover:before {
    transform: scaleY(1);
}

/* Animations */
@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(50px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modern-task-modal.show {
    animation: modalSlideIn 0.4s ease-out;
}

/* Responsive */
@media (max-width: 768px) {
    .modern-task-modal .modal-body {
        padding: 1.5rem !important;
    }
    
    .modern-button-group .modern-btn {
        font-size: 0.875rem !important;
        padding: 0.5rem 0.25rem !important;
    }
    
    .task-label {
        font-size: 0.9rem;
    }
}

/* Mode sombre */
.dark-mode .modern-task-modal {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
}

.dark-mode .modern-task-modal .modal-body {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
}

.dark-mode .task-label {
    color: #f8fafc;
}

.dark-mode .modern-input,
.dark-mode .modern-textarea {
    background: rgba(31, 41, 55, 0.9) !important;
    border-color: #374151 !important;
    color: #f8fafc !important;
}

.dark-mode .modern-input:focus,
.dark-mode .modern-textarea:focus {
    background: #1f2937 !important;
    border-color: #28a745 !important;
}

.dark-mode .modern-button-group {
    background: rgba(31, 41, 55, 0.9);
}

.dark-mode .user-btn {
    background: rgba(31, 41, 55, 0.9) !important;
    border-color: #374151 !important;
    color: #f8fafc !important;
}

.dark-mode .modern-attachment {
    background: rgba(31, 41, 55, 0.9);
}

.dark-mode .modern-drop-zone {
    background: linear-gradient(135deg, #1f2937, #111827) !important;
    border-color: #374151 !important;
}

.dark-mode .modern-file-list .file-item {
    background: rgba(31, 41, 55, 0.95) !important;
    border-color: #374151 !important;
    color: #f8fafc !important;
}
</style>

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



// Fonction utilitaire pour afficher des toasts optimisés pour mode sombre
function showToast(message, type = 'info') {
    // Créer un toast avec styles améliorés
    const toast = document.createElement('div');
    toast.className = `geek-toast geek-toast-${type} position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.style.minWidth = '300px';
    toast.style.maxWidth = '400px';
    toast.style.borderRadius = '8px';
    toast.style.padding = '12px 16px';
    toast.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
    toast.style.border = '1px solid';
    toast.style.fontWeight = '500';
    toast.style.fontSize = '14px';
    toast.style.lineHeight = '1.4';
    
    // Appliquer les couleurs selon le type et le mode
    applyToastStyles(toast, type);
    
    toast.innerHTML = `
        <div style="display: flex; align-items: flex-start; gap: 8px;">
            <div style="flex: 1;">${message}</div>
            <button type="button" class="geek-toast-close" onclick="this.closest('.geek-toast').remove()" 
                    style="background: none; border: none; font-size: 18px; cursor: pointer; padding: 0; margin-left: 8px; opacity: 0.7;">
                ×
            </button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Animation d'entrée
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'transform 0.3s ease-out';
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 10);
    
    // Supprimer automatiquement après 5 secondes avec animation
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Fonction pour appliquer les styles selon le type et le mode
function applyToastStyles(toast, type) {
    const isDarkMode = document.body.classList.contains('dark-mode') || 
                       document.documentElement.classList.contains('dark-mode') ||
                       window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    const styles = {
        info: {
            light: { bg: '#d1ecf1', border: '#bee5eb', color: '#0c5460' },
            dark: { bg: '#1a2332', border: '#2d4a5c', color: '#9dd6e8' }
        },
        success: {
            light: { bg: '#d4edda', border: '#c3e6cb', color: '#155724' },
            dark: { bg: '#1a2e1a', border: '#2d5a2d', color: '#a3d9a3' }
        },
        warning: {
            light: { bg: '#fff3cd', border: '#ffeaa7', color: '#856404' },
            dark: { bg: '#332a1a', border: '#5c4d2d', color: '#f4d35e' }
        },
        error: {
            light: { bg: '#f8d7da', border: '#f5c6cb', color: '#721c24' },
            dark: { bg: '#2e1a1a', border: '#5a2d2d', color: '#f28b82' }
        },
        danger: {
            light: { bg: '#f8d7da', border: '#f5c6cb', color: '#721c24' },
            dark: { bg: '#2e1a1a', border: '#5a2d2d', color: '#f28b82' }
        }
    };
    
    const colorScheme = styles[type] || styles.info;
    const colors = isDarkMode ? colorScheme.dark : colorScheme.light;
    
    toast.style.backgroundColor = colors.bg;
    toast.style.borderColor = colors.border;
    toast.style.color = colors.color;
    
    // Style pour le bouton de fermeture
    const closeBtn = toast.querySelector('.geek-toast-close');
    if (closeBtn) {
        closeBtn.style.color = colors.color;
    }
}

/**
 * Système de pointage dynamique depuis le modal nouvelles_actions
 * Affiche le bon bouton selon l'état actuel de l'utilisateur
 */

// Variable globale pour stocker l'état du pointage
let currentTimeTrackingState = null;

/**
 * Vérifier l'état actuel du pointage utilisateur
 */
async function checkTimeTrackingStatus() {
    try {
        console.log('🔄 Vérification état pointage...');
        
        const response = await fetch('time_tracking_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_status'
        });
        
        console.log('📡 Réponse API reçue:', response.status);
        
        // Gestion spéciale pour les erreurs d'authentification
        if (response.status === 401) {
            console.log('🔐 Utilisateur non connecté - pointage non disponible');
            return { auth_error: true, message: 'Connexion requise' };
        }
        
        if (!response.ok) {
            console.error('❌ Erreur réseau:', response.status);
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📊 Données API reçues:', data);
        
        if (data.success && data.data) {
            currentTimeTrackingState = data.data;
            console.log('✅ État pointage récupéré:', data.data);
            return data.data;
        } else {
            // Gestion des erreurs d'authentification côté serveur
            if (data.message && (data.message.includes('authentifié') || data.message.includes('connecter'))) {
                console.log('🔐 Erreur d\'authentification:', data.message);
                return { auth_error: true, message: data.message };
            }
            console.error('❌ Erreur API:', data.message || 'Erreur inconnue');
            throw new Error(data.message || 'Erreur lors de la récupération du statut');
        }
        
    } catch (error) {
        console.error('❌ Erreur vérification état:', error);
        // Retourner un objet avec plus d'informations pour le debug
        return { error: true, message: error.message };
    }
}

/**
 * Générer le bouton de pointage approprié
 */
function generateTimeTrackingButton(state) {
    // Gestion des erreurs d'authentification
    if (state && state.auth_error) {
        return `
        <button type="button" class="modern-action-card auth-error-card" onclick="window.location.reload()">
            <div class="card-glow"></div>
            <div class="action-icon-container">
                <div class="action-icon bg-gradient-primary">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="pulse-ring"></div>
            </div>
            <div class="action-content">
                <h6 class="action-title">Connexion requise</h6>
                <p class="action-description">${state.message || 'Veuillez vous connecter'}</p>
            </div>
            <div class="action-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </button>`;
    }
    
    // Gestion des erreurs génériques
    if (state && state.error) {
        return `
        <div class="modern-action-card error-card">
            <div class="card-glow"></div>
            <div class="action-icon-container">
                <div class="action-icon bg-gradient-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="action-content">
                <h6 class="action-title">Erreur de connexion</h6>
                <p class="action-description">${state.message || 'Problème de réseau'}</p>
            </div>
        </div>`;
    }
    
    if (!state) {
        // Erreur de chargement
        return `
        <div class="modern-action-card error-card">
            <div class="card-glow"></div>
            <div class="action-icon-container">
                <div class="action-icon bg-gradient-secondary">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="action-content">
                <h6 class="action-title">Erreur</h6>
                <p class="action-description">Impossible de vérifier l'état</p>
            </div>
        </div>`;
    }
    
    if (state.is_clocked_in) {
        // Utilisateur a un pointage en cours → bouton Clock-Out
        const session = state.current_session || state;
        const startTime = session.clock_in ? new Date(session.clock_in).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}) : 'N/A';
        
        // Calculer la durée écoulée si on a une heure de début
        let elapsedTime = '00:00';
        if (session.clock_in) {
            const start = new Date(session.clock_in);
            const now = new Date();
            const diffMs = now - start;
            const hours = Math.floor(diffMs / (1000 * 60 * 60));
            const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            elapsedTime = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
        }
        
        return `
        <button type="button" class="modern-action-card clock-out-card" onclick="modalClockOut()" data-bs-dismiss="modal">
            <div class="card-glow"></div>
            <div class="action-icon-container">
                <div class="action-icon bg-gradient-danger">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="pulse-ring"></div>
            </div>
            <div class="action-content">
                <h6 class="action-title">Pointage Départ</h6>
                <p class="action-description">Fin de journée depuis ${startTime} (${elapsedTime})</p>
            </div>
            <div class="action-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </button>`;
    } else {
        // Utilisateur n'a pas de pointage en cours → bouton Clock-In
        return `
        <button type="button" class="modern-action-card clock-in-card" onclick="modalClockIn()" data-bs-dismiss="modal">
            <div class="card-glow"></div>
            <div class="action-icon-container">
                <div class="action-icon bg-gradient-success">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <div class="pulse-ring"></div>
            </div>
            <div class="action-content">
                <h6 class="action-title">Pointage Arrivée</h6>
                <p class="action-description">Commencer votre journée de travail</p>
            </div>
            <div class="action-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </button>`;
    }
}

/**
 * Mettre à jour le bouton de pointage dans le modal
 */
async function updateTimeTrackingButton() {
    const container = document.getElementById('dynamic-timetracking-button');
    if (!container) return;
    
    // Afficher le chargement
    container.innerHTML = `
    <div class="modern-action-card loading-card">
        <div class="card-glow"></div>
        <div class="action-icon-container">
            <div class="action-icon bg-gradient-info">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <div class="pulse-ring"></div>
        </div>
        <div class="action-content">
            <h6 class="action-title">Chargement...</h6>
            <p class="action-description">Vérification de l'état du pointage</p>
        </div>
    </div>`;
    
    // Vérifier l'état
    const state = await checkTimeTrackingStatus();
    
    // Mettre à jour avec le bon bouton
    container.innerHTML = generateTimeTrackingButton(state);
}

/**
 * Fonctions de pointage avec mise à jour du bouton
 */
async function modalClockIn() {
    console.log('🔄 Tentative de pointage arrivée depuis modal...');
    
    try {
        const response = await fetch('time_tracking_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clock_in'
        });
        
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Afficher un message de succès avec détails d'approbation
            let message = '✅ Pointage d\'arrivée enregistré !';
            if (data.data.auto_approved) {
                message += '\n🟢 Approuvé automatiquement - Dans les créneaux autorisés';
            } else {
                message += '\n🟡 En attente d\'approbation - Hors créneaux ou aucun créneau défini';
            }
            
            showToast(message, data.data.auto_approved ? 'success' : 'warning');
            
            // Mettre à jour le bouton pour la prochaine ouverture du modal
            setTimeout(updateTimeTrackingButton, 1000);
            
            console.log('✅ Clock-in réussi:', data);
            
        } else {
            throw new Error(data.message);
        }
        
    } catch (error) {
        console.error('❌ Erreur Clock-In depuis modal:', error);
        showToast('❌ Erreur: ' + error.message, 'error');
    }
}

async function modalClockOut() {
    console.log('🔄 Tentative de pointage départ depuis modal...');
    
    try {
        const response = await fetch('time_tracking_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clock_out'
        });
        
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Afficher un message de succès avec durée de travail
            let message = '✅ Pointage de départ enregistré !';
            if (data.data.work_duration) {
                message += `\n⏱️ Durée de travail: ${data.data.work_duration}h`;
            }
            
            showToast(message, 'success');
            
            // Mettre à jour le bouton pour la prochaine ouverture du modal
            setTimeout(updateTimeTrackingButton, 1000);
            
            console.log('✅ Clock-out réussi:', data);
            
        } else {
            throw new Error(data.message);
        }
        
    } catch (error) {
        console.error('❌ Erreur Clock-Out depuis modal:', error);
        showToast('❌ Erreur: ' + error.message, 'error');
    }
}

/**
 * ========================================
 * GESTION DU MODAL AJOUTER TÂCHE
 * ========================================
 */

// Variables globales pour le modal tâche
let taskModalFilesArray = [];
let taskModalUsersLoaded = false;

// Données utilisateurs exposées depuis PHP (si disponibles)
let taskModalUsersFromPHP = [];
<?php
// Charger les utilisateurs directement si pas déjà disponibles
if (!isset($utilisateurs) || !is_array($utilisateurs) || empty($utilisateurs)) {
    try {
        $shop_pdo = getShopDBConnection();
        $stmt = $shop_pdo->query("SELECT id, full_name, role FROM users ORDER BY role DESC, full_name ASC");
        $utilisateurs_modal = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($utilisateurs_modal)) {
            echo "taskModalUsersFromPHP = " . json_encode($utilisateurs_modal) . ";\n";
            echo "console.log('🚀 Utilisateurs chargés directement depuis la base pour le modal:', taskModalUsersFromPHP);\n";
        }
    } catch (PDOException $e) {
        echo "console.error('❌ Erreur lors du chargement des utilisateurs pour le modal:', " . json_encode($e->getMessage()) . ");\n";
    }
} else {
    // Utiliser les utilisateurs déjà chargés par la page
    echo "taskModalUsersFromPHP = " . json_encode($utilisateurs) . ";\n";
    echo "console.log('✅ Utilisateurs utilisés depuis la variable de page:', taskModalUsersFromPHP);\n";
}
?>

/**
 * Charger les utilisateurs pour le modal tâche
 */
async function loadTaskModalUsers() {
    if (taskModalUsersLoaded) return; // Ne charger qu'une fois
    
    // D'abord, essayer d'utiliser les données PHP si disponibles
    if (taskModalUsersFromPHP && taskModalUsersFromPHP.length > 0) {
        console.log('🚀 Utilisation des utilisateurs depuis PHP');
        displayTaskModalUsers(taskModalUsersFromPHP);
        taskModalUsersLoaded = true;
        return;
    }
    
    try {
        // Utiliser l'API sans authentification
        const response = await fetch('ajax_simple_no_auth.php?shop_id=63');
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        
        const data = await response.json();
        if (data.success && data.users) {
            console.log('🚀 Utilisateurs chargés SANS authentification:', data.users.length);
            console.log('📊 Shop:', data.shop_id, '- DB:', data.shop_db);
            displayTaskModalUsers(data.users);
            taskModalUsersLoaded = true;
        } else {
            console.error('❌ Erreur API sans auth:', data);
            throw new Error(data.message || 'Erreur lors du chargement des utilisateurs');
        }
    } catch (error) {
        console.error('Erreur chargement utilisateurs via API:', error);
        console.log('Tentative de récupération des utilisateurs depuis la page...');
        
        // Solution de contournement : essayer de récupérer depuis les données de la page
        const fallbackUsers = tryGetUsersFromPage();
        if (fallbackUsers && fallbackUsers.length > 0) {
            displayTaskModalUsers(fallbackUsers);
            taskModalUsersLoaded = true;
            console.log('✅ Utilisateurs récupérés depuis la page');
        } else {
            const container = document.getElementById('userButtonsContainer');
            if (container) {
                container.innerHTML = `
                    <div class="alert alert-warning w-100 text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Impossible de charger les utilisateurs<br>
                        <small class="text-muted">Vous pouvez continuer sans assigner la tâche</small>
                    </div>`;
            }
        }
    }
}

/**
 * Essayer de récupérer les utilisateurs depuis les données de la page
 */
function tryGetUsersFromPage() {
    try {
        // Chercher les données utilisateurs dans les selects existants
        const userSelects = document.querySelectorAll('select[name="employe_id"], select[name="user_id"], select[id*="user"], select[id*="employe"]');
        const users = [];
        
        userSelects.forEach(select => {
            Array.from(select.options).forEach(option => {
                if (option.value && option.value !== '' && option.text && option.text !== 'Sélectionner un utilisateur') {
                    // Éviter les doublons
                    if (!users.find(u => u.id === option.value)) {
                        users.push({
                            id: option.value,
                            full_name: option.text,
                            role: 'Utilisateur'
                        });
                    }
                }
            });
        });
        
        // Si on n'a pas trouvé d'utilisateurs dans les selects, chercher dans les boutons existants
        if (users.length === 0) {
            const userButtons = document.querySelectorAll('.user-btn[data-value]');
            userButtons.forEach(button => {
                if (button.dataset.value && button.dataset.value !== '') {
                    const name = button.textContent.replace(/^\s*\w+\s*/, '').trim(); // Enlever l'icône
                    if (name && !users.find(u => u.id === button.dataset.value)) {
                        users.push({
                            id: button.dataset.value,
                            full_name: name,
                            role: 'Utilisateur'
                        });
                    }
                }
            });
        }
        
        console.log('Utilisateurs trouvés sur la page:', users);
        return users;
    } catch (error) {
        console.error('Erreur lors de la récupération des utilisateurs depuis la page:', error);
        return [];
    }
}

/**
 * Afficher les utilisateurs dans le modal
 */
function displayTaskModalUsers(users) {
    const container = document.getElementById('userButtonsContainer');
    if (!container) return;
    
    let html = `
        <button type="button" class="btn btn-outline-secondary btn-lg user-btn modern-btn" data-value="">
            <i class="fas fa-user-slash me-2"></i>Non assigné
        </button>
    `;
    
    // Afficher les 3 premiers utilisateurs
    users.slice(0, 3).forEach(user => {
        html += `
            <button type="button" class="btn btn-outline-primary btn-lg user-btn modern-btn" data-value="${user.id}">
                <i class="fas fa-user me-2"></i>${user.full_name}
            </button>
        `;
    });
    
    // Bouton "Voir tous" si plus de 3 utilisateurs
    if (users.length > 3) {
        html += `
            <button type="button" class="btn btn-outline-secondary btn-lg modern-btn" id="modalShowAllUsersBtn">
                <i class="fas fa-users me-2"></i>Voir tous (${users.length})
            </button>
        `;
    }
    
    // Liste complète (masquée par défaut)
    if (users.length > 3) {
        html += `
            <div id="modalAllUsersList" class="w-100 mt-3" style="display: none;">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
        `;
        
        users.forEach(user => {
            html += `
                <div class="col">
                    <button type="button" class="btn btn-outline-primary w-100 text-start user-btn modern-btn py-2" data-value="${user.id}">
                        <i class="fas fa-user me-2"></i>${user.full_name}
                        <small class="d-block text-muted ms-4">${user.role || 'Utilisateur'}</small>
                    </button>
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    }
    
    container.innerHTML = html;
    
    // Ajouter les événements
    initTaskModalUserButtons();
}

/**
 * Initialiser les boutons utilisateurs du modal
 */
function initTaskModalUserButtons() {
    const userButtons = document.querySelectorAll('#userButtonsContainer .user-btn');
    const employeInput = document.getElementById('modal_employe_id');
    const showAllBtn = document.getElementById('modalShowAllUsersBtn');
    const allUsersList = document.getElementById('modalAllUsersList');
    
    // Événements sur les boutons utilisateurs
    userButtons.forEach(button => {
        button.addEventListener('click', function() {
            userButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            if (employeInput) {
                employeInput.value = this.dataset.value;
            }
        });
    });
    
    // Bouton "Voir tous"
    if (showAllBtn && allUsersList) {
        showAllBtn.addEventListener('click', function() {
            if (allUsersList.style.display === 'none') {
                allUsersList.style.display = 'block';
                this.innerHTML = '<i class="fas fa-users-slash me-2"></i>Masquer';
            } else {
                allUsersList.style.display = 'none';
                this.innerHTML = '<i class="fas fa-users me-2"></i>Voir tous';
            }
        });
    }
}

/**
 * Initialiser les boutons priorité
 */
function initTaskModalButtons() {
    // Priorité
    const priorityButtons = document.querySelectorAll('#ajouterTacheModal .btn-priority');
    const priorityInput = document.getElementById('modal_priorite');
    
    priorityButtons.forEach(button => {
        button.addEventListener('click', function() {
            priorityButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            if (priorityInput) {
                priorityInput.value = this.dataset.value;
            }
        });
    });
    
    // Sélectionner la priorité par défaut (moyenne)
    setTimeout(() => {
        const defaultPriority = document.querySelector('#ajouterTacheModal .btn-priority[data-value="moyenne"]');
        if (defaultPriority) defaultPriority.click();
    }, 100);
}

/**
 * Gestion des fichiers pour le modal tâche
 */
function initTaskModalFiles() {
    const fileInput = document.getElementById('modalFileInput');
    const fileDropZone = document.getElementById('modalFileDropZone');
    const selectFilesBtn = document.getElementById('modalSelectFilesBtn');
    const selectedFiles = document.getElementById('modalSelectedFiles');
    const filesList = document.getElementById('modalFilesList');
    
    if (!fileInput || !fileDropZone || !selectFilesBtn) return;
    
    // Fonction pour formater la taille des fichiers
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Fonction pour obtenir l'icône selon le type de fichier
    function getFileIcon(fileType) {
        const imageTypes = ['jpg', 'jpeg', 'png', 'gif'];
        const documentTypes = ['pdf', 'doc', 'docx', 'txt'];
        const archiveTypes = ['zip', 'rar'];
        
        if (imageTypes.includes(fileType.toLowerCase())) {
            return { icon: 'fas fa-image', class: 'image' };
        } else if (documentTypes.includes(fileType.toLowerCase())) {
            return { icon: 'fas fa-file-alt', class: 'document' };
        } else if (archiveTypes.includes(fileType.toLowerCase())) {
            return { icon: 'fas fa-file-archive', class: 'archive' };
        } else {
            return { icon: 'fas fa-file', class: 'other' };
        }
    }
    
    // Fonction pour valider un fichier
    function validateFile(file) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'xlsx', 'xls', 'zip', 'rar'];
        const fileType = file.name.split('.').pop().toLowerCase();
        
        if (file.size > maxSize) {
            return { valid: false, error: `Le fichier "${file.name}" est trop volumineux (max 10MB)` };
        }
        
        if (!allowedTypes.includes(fileType)) {
            return { valid: false, error: `Le type de fichier "${fileType}" n'est pas autorisé pour "${file.name}"` };
        }
        
        return { valid: true };
    }
    
    // Fonction pour afficher les fichiers sélectionnés
    function displayFiles() {
        if (!filesList || !selectedFiles) return;
        
        if (taskModalFilesArray.length === 0) {
            selectedFiles.style.display = 'none';
            return;
        }
        
        selectedFiles.style.display = 'block';
        filesList.innerHTML = '';
        
        taskModalFilesArray.forEach((file, index) => {
            const fileIcon = getFileIcon(file.name.split('.').pop());
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item d-flex align-items-center';
            fileItem.innerHTML = `
                <div class="file-icon ${fileIcon.class} me-3">
                    <i class="${fileIcon.icon}"></i>
                </div>
                <div class="file-info flex-grow-1">
                    <div class="file-name fw-medium">${file.name}</div>
                    <div class="file-size text-muted small">${formatFileSize(file.size)}</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger file-remove" data-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            filesList.appendChild(fileItem);
        });
        
        // Ajouter les événements de suppression
        document.querySelectorAll('#modalFilesList .file-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                taskModalFilesArray.splice(index, 1);
                updateFileInput();
                displayFiles();
            });
        });
    }
    
    // Fonction pour mettre à jour l'input file
    function updateFileInput() {
        const dt = new DataTransfer();
        taskModalFilesArray.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }
    
    // Fonction pour ajouter des fichiers
    function addFiles(files) {
        const newFiles = Array.from(files);
        let hasErrors = false;
        
        newFiles.forEach(file => {
            const validation = validateFile(file);
            if (!validation.valid) {
                showToast(validation.error, 'error');
                hasErrors = true;
                return;
            }
            
            // Vérifier si le fichier n'est pas déjà ajouté
            const exists = taskModalFilesArray.some(existingFile => 
                existingFile.name === file.name && existingFile.size === file.size
            );
            
            if (!exists) {
                taskModalFilesArray.push(file);
            }
        });
        
        if (!hasErrors) {
            updateFileInput();
            displayFiles();
        }
    }
    
    // Événement pour le bouton de sélection
    selectFilesBtn.addEventListener('click', function() {
        fileInput.click();
    });
    
    // Événement pour le changement de fichier
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            addFiles(this.files);
        }
    });
    
    // Événements de drag & drop
    fileDropZone.addEventListener('click', function() {
        fileInput.click();
    });
    
    fileDropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    fileDropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    fileDropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            addFiles(files);
        }
    });
}

/**
 * Soumettre le formulaire de tâche via AJAX
 */
async function submitTaskModal() {
    const form = document.getElementById('taskModalForm');
    const saveBtn = document.getElementById('saveTaskBtn');
    const btnText = saveBtn.querySelector('.btn-text');
    const btnLoading = saveBtn.querySelector('.btn-loading');
    const errorsDiv = document.getElementById('taskModalErrors');
    const errorsList = document.getElementById('taskErrorsList');
    const successDiv = document.getElementById('taskModalSuccess');
    const successMessage = document.getElementById('taskSuccessMessage');
    
    if (!form || !saveBtn) return;
    
    // Masquer les messages précédents
    errorsDiv.classList.add('d-none');
    successDiv.classList.add('d-none');
    
    // Afficher le loading
    btnText.classList.add('d-none');
    btnLoading.classList.remove('d-none');
    saveBtn.disabled = true;
    
    try {
        const formData = new FormData(form);
        
        // Utiliser l'API sans authentification
        const response = await fetch('ajax_simple_no_auth.php?shop_id=63', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Succès
            successMessage.textContent = data.message || 'Tâche ajoutée avec succès !';
            successDiv.classList.remove('d-none');
            
            // Réinitialiser le formulaire après un délai
            setTimeout(() => {
                resetTaskModal();
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('ajouterTacheModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Afficher un toast de succès
                showToast('✅ Tâche ajoutée avec succès !', 'success');
                
                // Recharger la page si on est sur la page des tâches
                if (window.location.href.includes('page=taches') || window.location.href.includes('page=accueil')) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            }, 1500);
            
        } else {
            // Erreurs de validation
            if (data.errors && Array.isArray(data.errors)) {
                errorsList.innerHTML = '';
                data.errors.forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error;
                    errorsList.appendChild(li);
                });
                errorsDiv.classList.remove('d-none');
            } else {
                showToast('❌ ' + (data.message || 'Erreur lors de l\'ajout de la tâche'), 'error');
            }
        }
        
    } catch (error) {
        console.error('Erreur soumission tâche:', error);
        showToast('❌ Erreur de connexion: ' + error.message, 'error');
    } finally {
        // Restaurer le bouton
        btnText.classList.remove('d-none');
        btnLoading.classList.add('d-none');
        saveBtn.disabled = false;
    }
}

/**
 * Réinitialiser le modal de tâche
 */
function resetTaskModal() {
    const form = document.getElementById('taskModalForm');
    if (form) {
        form.reset();
    }
    
    // Réinitialiser les boutons
    document.querySelectorAll('#ajouterTacheModal .btn-priority, #ajouterTacheModal .user-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Réinitialiser les champs cachés (le statut reste à "a_faire")
    document.getElementById('modal_priorite').value = '';
    document.getElementById('modal_employe_id').value = '';
    
    // Réinitialiser les fichiers
    taskModalFilesArray = [];
    const selectedFiles = document.getElementById('modalSelectedFiles');
    if (selectedFiles) {
        selectedFiles.style.display = 'none';
    }
    
    // Masquer les messages
    document.getElementById('taskModalErrors').classList.add('d-none');
    document.getElementById('taskModalSuccess').classList.add('d-none');
    
    // Remettre la priorité par défaut
    setTimeout(() => {
        const defaultPriority = document.querySelector('#ajouterTacheModal .btn-priority[data-value="moyenne"]');
        if (defaultPriority) defaultPriority.click();
    }, 100);
}

/**
 * Initialiser le modal de tâche
 */
function initTaskModal() {
    const modal = document.getElementById('ajouterTacheModal');
    const saveBtn = document.getElementById('saveTaskBtn');
    
    if (!modal) return;
    
    // Événement à l'ouverture du modal
    modal.addEventListener('show.bs.modal', function() {
        console.log('🔄 Ouverture modal tâche - Initialisation...');
        
        // Charger les utilisateurs
        loadTaskModalUsers();
        
        // Initialiser les boutons
        setTimeout(() => {
            initTaskModalButtons();
            initTaskModalFiles();
        }, 100);
    });
    
    // Événement à la fermeture du modal
    modal.addEventListener('hidden.bs.modal', function() {
        console.log('🔄 Fermeture modal tâche - Nettoyage...');
        resetTaskModal();
    });
    
    // Événement sur le bouton de sauvegarde
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            submitTaskModal();
        });
    }
    
    console.log('✅ Modal tâche initialisé avec succès');
}

// Initialisation des modals au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser tous les modals Bootstrap
    const modalElements = document.querySelectorAll('.modal');
    modalElements.forEach(modalElement => {
        new bootstrap.Modal(modalElement);
    });
    
    // Ajouter un événement pour mettre à jour le bouton de pointage à chaque ouverture du modal
    const nouvellesActionsModal = document.getElementById('nouvelles_actions_modal');
    if (nouvellesActionsModal) {
        nouvellesActionsModal.addEventListener('show.bs.modal', function () {
            console.log('🔄 Ouverture modal nouvelles_actions - Mise à jour bouton pointage...');
            updateTimeTrackingButton();
        });
    }
    
    // Initialiser le modal de tâche
    initTaskModal();
    
    // Initialiser le scanner universel
    initUniversalScanner();
    
    console.log('✅ Modals Bootstrap initialisés avec succès');
    console.log('🕐 Système de pointage dynamique prêt');
    console.log('📝 Modal de tâche prêt');
    console.log('📱 Scanner universel prêt');
});

// ========================================= //
// SCANNER UNIVERSEL - QR + CODES-BARRES    //
// ========================================= //

let universalScannerStream = null;
let universalScannerAnimation = null;
let currentCamera = 'environment';
let flashEnabled = false;
let quaggaInitialized = false;
let currentScanMode = 'auto';
let lastDetectedCode = null;
let detectionCount = 0;
let isProcessingDetection = false;

/**
 * Initialiser le scanner universel
 */
function initUniversalScanner() {
    console.log('🔧 Initialisation du scanner universel...');
    
    // Vérifier que les bibliothèques sont disponibles
    if (typeof jsQR === 'undefined' && (typeof Quagga === 'undefined' || !window.Quagga)) {
        console.warn('⏳ Bibliothèques de scan non encore chargées, retry...');
        setTimeout(() => {
            initUniversalScanner();
        }, 200);
        return;
    }
    
    console.log('✅ Bibliothèques disponibles:', {
        jsQR: typeof jsQR !== 'undefined',
        Quagga: typeof Quagga !== 'undefined' && !!window.Quagga
    });
    
    // Bouton d'ouverture du scanner
    const openScannerBtn = document.getElementById('openUniversalScanner');
    if (openScannerBtn) {
        openScannerBtn.addEventListener('click', function() {
            // Fermer le modal nouvelles actions
            const nouvellesActionsModal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal'));
            if (nouvellesActionsModal) {
                nouvellesActionsModal.hide();
            }
            
            // Ouvrir le scanner après un délai
            setTimeout(() => {
                openUniversalScanner();
            }, 300);
        });
    }
    
    // Événements des modes de scan
    const scanModes = document.querySelectorAll('input[name="scanMode"]');
    scanModes.forEach(mode => {
        mode.addEventListener('change', function() {
            updateScannerMode(this.value);
        });
    });
    
    // NOUVEAU : Écouter l'ouverture du modal depuis la barre de dock mobile
    const scannerModal = document.getElementById('universal_scanner_modal');
    if (scannerModal) {
        console.log('🔧 [SCANNER] Installation des événements du modal...');
        
        scannerModal.addEventListener('shown.bs.modal', function() {
            console.log('🚀 [SCANNER] Modal ouvert depuis dock mobile, démarrage automatique...');
            setTimeout(() => {
                startUniversalScanner();
            }, 500);
        });
        
        scannerModal.addEventListener('hidden.bs.modal', function() {
            console.log('🚀 [SCANNER] Modal fermé, arrêt du scanner...');
            stopUniversalScanner();
        });
    } else {
        console.warn('⚠️ [SCANNER] Modal scanner non trouvé pour les événements');
    }
    
    console.log('📱 Scanner universel initialisé');
}

/**
 * Ouvrir le scanner universel
 */
function openUniversalScanner() {
    console.log('🚀 [SCANNER] Fonction openUniversalScanner() appelée');
    console.log('🚀 [SCANNER] User Agent:', navigator.userAgent);
    console.log('🚀 [SCANNER] Taille écran:', window.innerWidth + 'x' + window.innerHeight);
    
    // Vérifier que les bibliothèques sont chargées
    if (typeof jsQR === 'undefined' && (typeof Quagga === 'undefined' || !window.Quagga)) {
        console.warn('⏳ [SCANNER] Bibliothèques de scan en cours de chargement...');
        setTimeout(() => {
            openUniversalScanner();
        }, 200);
        return;
    }
    
    console.log('✅ [SCANNER] Bibliothèques disponibles, ouverture du modal...');
    
    const modal = new bootstrap.Modal(document.getElementById('universal_scanner_modal'));
    modal.show();
    
    // Démarrer le scanner après l'ouverture du modal
    setTimeout(() => {
        startUniversalScanner();
    }, 500);
}

/**
 * Démarrer le scanner
 */
async function startUniversalScanner() {
    console.log('🎬 [SCANNER] Fonction startUniversalScanner() appelée');
    
    const video = document.getElementById('universal_scanner_video');
    const status = document.getElementById('universal_scanner_status');
    
    console.log('🎬 [SCANNER] Éléments DOM:', {
        video: !!video,
        status: !!status,
        videoId: video?.id,
        statusId: status?.id
    });
    
    if (!video) {
        console.error('❌ [SCANNER] Élément vidéo non trouvé !');
        return;
    }
    
    if (!status) {
        console.error('❌ [SCANNER] Élément status non trouvé !');
        return;
    }
    
    try {
        console.log('🎬 [SCANNER] Mise à jour du statut...');
        status.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Démarrage de la caméra...';
        status.className = 'scanner-status';
        
        // Arrêter le stream précédent s'il existe
        if (universalScannerStream) {
            universalScannerStream.getTracks().forEach(track => track.stop());
        }
        
        // Détecter si on est sur mobile/tablette
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                         window.innerWidth <= 768;
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        const isSafari = /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent);
        const isChromeMobileEmulation = window.innerWidth <= 768 && /Chrome/.test(navigator.userAgent) && !/Mobile/.test(navigator.userAgent);
        
        console.log('📱 [SCANNER] Détection appareil détaillée:', { 
            isMobile, 
            isIOS, 
            isSafari,
            isChromeMobileEmulation,
            userAgent: navigator.userAgent, 
            width: window.innerWidth,
            height: window.innerHeight,
            devicePixelRatio: window.devicePixelRatio
        });
        
        // Vérifier les permissions caméra
        if (navigator.permissions) {
            try {
                const permission = await navigator.permissions.query({ name: 'camera' });
                console.log('🔐 Permission caméra:', permission.state);
            } catch (e) {
                console.log('🔐 Impossible de vérifier les permissions:', e);
            }
        }
        
        // Vérifier la disponibilité de getUserMedia
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.error('❌ getUserMedia non disponible');
            status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Caméra non supportée sur cet appareil';
            status.className = 'scanner-status error';
            return;
        }
        
        console.log('✅ getUserMedia disponible');
        
        // Configuration de la caméra adaptée selon l'appareil
        let constraints;
        
        if (isChromeMobileEmulation) {
            // Configuration spéciale pour émulation mobile Chrome
            constraints = {
                video: true  // Contraintes minimales pour émulation
            };
            console.log('🖥️ [SCANNER] Configuration Chrome émulation mobile');
        } else if (isIOS) {
            // Configuration ultra-simple pour iOS
            constraints = {
                video: {
                    facingMode: currentCamera
                }
            };
            console.log('📱 [SCANNER] Configuration iOS ultra-simple');
        } else if (isMobile) {
            // Configuration simplifiée pour autres mobiles
            constraints = {
                video: {
                    facingMode: currentCamera,
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };
            console.log('📱 [SCANNER] Configuration mobile standard');
        } else {
            // Configuration avancée pour desktop
            constraints = {
                video: {
                    facingMode: currentCamera,
                    width: { ideal: 1920, min: 800 },
                    height: { ideal: 1080, min: 600 },
                    focusMode: "continuous",
                    zoom: 1.0,
                    frameRate: { ideal: 30, min: 15 }
                }
            };
            console.log('💻 [SCANNER] Configuration desktop avancée');
        }
        
        console.log('📷 Contraintes caméra:', constraints);
        
        console.log('🎬 Tentative d\'accès à la caméra...');
        
        try {
            universalScannerStream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = universalScannerStream;
            console.log('✅ Caméra démarrée avec succès');
            console.log('📊 Stream info:', {
                active: universalScannerStream.active,
                tracks: universalScannerStream.getTracks().length,
                videoTracks: universalScannerStream.getVideoTracks().length
            });
        } catch (error) {
            console.error('❌ Erreur caméra (tentative 1):', {
                name: error.name,
                message: error.message,
                constraint: error.constraint
            });
            
            // Fallback spécial pour iOS
            if (isIOS) {
                console.log('🍎 Fallback iOS - Tentative avec contraintes vides...');
                try {
                    const iosConstraints = { video: true };
                    console.log('🍎 Contraintes iOS fallback:', iosConstraints);
                    universalScannerStream = await navigator.mediaDevices.getUserMedia(iosConstraints);
                    video.srcObject = universalScannerStream;
                    console.log('✅ Caméra iOS démarrée en mode fallback');
                } catch (iosError) {
                    console.error('❌ Échec iOS total:', {
                        name: iosError.name,
                        message: iosError.message
                    });
                    
                    // Dernier fallback iOS - sans facingMode
                    console.log('🍎 Dernier fallback iOS - contraintes absolument minimales...');
                    try {
                        const minimalConstraints = { video: {} };
                        universalScannerStream = await navigator.mediaDevices.getUserMedia(minimalConstraints);
                        video.srcObject = universalScannerStream;
                        console.log('✅ Caméra iOS démarrée en mode minimal');
                    } catch (minimalError) {
                        console.error('❌ Échec iOS définitif:', minimalError);
                        status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Caméra non accessible sur iOS: ' + minimalError.message;
                        status.className = 'scanner-status error';
                        return;
                    }
                }
            } else if (isMobile) {
                console.log('📱 Fallback mobile - Tentative avec contraintes minimales...');
                try {
                    const fallbackConstraints = { video: { facingMode: currentCamera } };
                    universalScannerStream = await navigator.mediaDevices.getUserMedia(fallbackConstraints);
                    video.srcObject = universalScannerStream;
                    console.log('✅ Caméra mobile démarrée en mode fallback');
                } catch (fallbackError) {
                    console.error('❌ Échec mobile total:', fallbackError);
                    status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erreur caméra mobile: ' + fallbackError.message;
                    status.className = 'scanner-status error';
                    return;
                }
            } else {
                status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erreur caméra: ' + error.message;
                status.className = 'scanner-status error';
                return;
            }
        }
        
        // Attendre que la vidéo soit prête
        video.onloadedmetadata = () => {
            console.log('📹 Métadonnées vidéo chargées:', {
                videoWidth: video.videoWidth,
                videoHeight: video.videoHeight,
                readyState: video.readyState
            });
            
            video.play().then(() => {
                console.log('▶️ Vidéo en cours de lecture');
            }).catch(playError => {
                console.error('❌ Erreur lecture vidéo:', playError);
            });
            
            // Initialiser selon le mode sélectionné
            const selectedMode = document.querySelector('input[name="scanMode"]:checked').value;
            currentScanMode = selectedMode;
            
            if (selectedMode === 'barcode' || selectedMode === 'auto') {
                initQuaggaForBarcodes();
            }
            
            if (selectedMode === 'qr' || selectedMode === 'auto') {
                startScanning();
            }
            
        status.innerHTML = '<i class="fas fa-camera me-2"></i>Positionnez le code dans le cadre';
        status.className = 'scanner-status';
        
        // Timeout de sécurité pour détecter si la caméra ne se lance pas
        const cameraTimeout = setTimeout(() => {
            if (!video.videoWidth || video.readyState < 2) {
                console.warn('⏰ Timeout caméra - La caméra ne semble pas se lancer');
                status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>La caméra met du temps à se lancer...';
                status.className = 'scanner-status error';
            }
        }, 5000);
        
        // Annuler le timeout si la vidéo se charge
        video.addEventListener('loadeddata', () => {
            clearTimeout(cameraTimeout);
            console.log('✅ Timeout caméra annulé - vidéo chargée');
        });
        };
        
    } catch (error) {
        console.error('Erreur caméra:', error);
        status.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erreur: Impossible d\'accéder à la caméra';
        status.className = 'scanner-status error';
    }
}

/**
 * Démarrer le processus de scan
 */
function startScanning() {
    const video = document.getElementById('universal_scanner_video');
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    
    function scanFrame() {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            
            // Obtenir le mode de scan sélectionné
            const selectedMode = document.querySelector('input[name="scanMode"]:checked').value;
            
            // Scanner selon le mode
            if (selectedMode === 'auto' || selectedMode === 'qr') {
                scanQRCode(imageData);
            }
            
            // Ne pas utiliser scanBarcode si Quagga est initialisé
            if ((selectedMode === 'auto' || selectedMode === 'barcode') && !quaggaInitialized) {
                scanBarcode(imageData);
            }
        }
        
        // Continuer le scan
        if (universalScannerAnimation) {
            universalScannerAnimation = requestAnimationFrame(scanFrame);
        }
    }
    
    universalScannerAnimation = requestAnimationFrame(scanFrame);
}

/**
 * Scanner QR code avec jsQR
 */
function scanQRCode(imageData) {
    if (typeof jsQR !== 'undefined') {
        try {
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: "attemptBoth",
            });
            
            if (code && code.data) {
                console.log('✅ QR Code détecté:', code.data);
                handleQRCodeDetected(code.data);
            }
        } catch (error) {
            console.error('Erreur jsQR:', error);
        }
    }
}

/**
 * Initialiser Quagga pour les codes-barres
 */
function initQuaggaForBarcodes() {
    // Vérifier si Quagga est disponible avec retry
    if (typeof Quagga === 'undefined' || !window.Quagga) {
        console.warn('⏳ Quagga.js en cours de chargement, retry dans 100ms...');
        setTimeout(() => {
            initQuaggaForBarcodes();
        }, 100);
        return;
    }
    
    console.log('✅ Quagga.js disponible, initialisation...');
    
    if (quaggaInitialized) {
        try {
            Quagga.stop();
        } catch (e) {
            console.log('Quagga déjà arrêté');
        }
    }
    
    const video = document.getElementById('universal_scanner_video');
    
    console.log('🔧 Initialisation Quagga pour codes-barres...');
    
    // Détecter si on est sur mobile
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                     window.innerWidth <= 768;
    
    console.log('📱 Configuration Quagga pour:', isMobile ? 'Mobile' : 'Desktop');
    
    Quagga.init({
        inputStream: {
            type: 'LiveStream',
            target: video,
            constraints: isMobile ? {
                // Configuration mobile simplifiée
                facingMode: currentCamera,
                width: { ideal: 1280 },
                height: { ideal: 720 }
            } : {
                // Configuration desktop avancée
                width: { ideal: 1920, min: 800 },
                height: { ideal: 1080, min: 600 },
                facingMode: currentCamera,
                focusMode: "continuous",
                zoom: 1.0
            }
        },
        locator: {
            patchSize: isMobile ? "medium" : "large",
            halfSample: isMobile ? true : false
        },
        numOfWorkers: isMobile ? 2 : 4,
        frequency: isMobile ? 15 : 25,
        decoder: {
            readers: [
                "ean_reader",        // EAN-13 (le plus courant)
                "ean_8_reader",      // EAN-8
                "code_128_reader",   // Code 128 (très utilisé)
                "code_39_reader",    // Code 39
                "code_93_reader",    // Code 93
                "codabar_reader",    // Codabar
                "i2of5_reader"       // Interleaved 2 of 5
            ]
        },
        locate: true,
        debug: {
            drawBoundingBox: false,
            showFrequency: false,
            drawScanline: false,
            showPattern: false
        }
    }, function(err) {
        if (err) {
            console.error('❌ Erreur initialisation Quagga:', err);
            
            // Fallback pour mobile avec configuration encore plus simple
            if (isMobile && (err.name === 'NotReadableError' || err.name === 'OverconstrainedError')) {
                console.log('🔄 Tentative Quagga avec configuration minimale mobile...');
                
                setTimeout(() => {
                    Quagga.init({
                        inputStream: {
                            type: 'LiveStream',
                            target: video,
                            constraints: {
                                facingMode: currentCamera
                            }
                        },
                        locator: {
                            patchSize: "small",
                            halfSample: true
                        },
                        numOfWorkers: 1,
                        frequency: 10,
                        decoder: {
                            readers: ["ean_reader", "code_128_reader"]
                        },
                        locate: true,
                        debug: false
                    }, function(fallbackErr) {
                        if (fallbackErr) {
                            console.error('❌ Échec total Quagga mobile:', fallbackErr);
                            return;
                        }
                        
                        console.log('✅ Quagga initialisé en mode minimal mobile');
                        quaggaInitialized = true;
                        Quagga.start();
                        
                        // Logique de détection simplifiée pour mobile
                        Quagga.onDetected(function(result) {
                            const code = result.codeResult.code;
                            const confidence = result.codeResult.confidence || 0;
                            
                            console.log('📊 Code-barres détecté (mobile minimal):', code, 'Confiance:', confidence);
                            
                            if (isProcessingDetection || code.length < 2) {
                                return;
                            }
                            
                            // Validation immédiate en mode mobile
                            console.log('✅ Code validé (mode mobile minimal):', code);
                            isProcessingDetection = true;
                            handleBarcodeDetected(code);
                        });
                    });
                }, 500);
            }
            return;
        }
        
        console.log('✅ Quagga initialisé avec succès');
        Quagga.start();
        quaggaInitialized = true;
    });
    
    // Écouter les détections Quagga
    Quagga.onDetected(function(result) {
        const code = result.codeResult.code.trim();
        const confidence = result.codeResult.confidence || 0;
        
        console.log('📊 Code-barres détecté:', code, 'Confiance:', confidence);
        
        // Éviter le traitement multiple
        if (isProcessingDetection) {
            console.log('⏳ Traitement en cours, détection ignorée');
            return;
        }
        
        // Filtrer les codes trop courts
        // Accepter même les codes très courts pour améliorer la détection
        if (code.length < 2) {
            console.log('Code rejeté - trop court:', code);
            return;
        }
        
        // Boost de détection : accepter immédiatement si le code semble valide
        if (code.length >= 8 && /^[0-9]+$/.test(code)) {
            console.log('🚀 Code validé par boost (numérique long):', code);
            isProcessingDetection = true;
            handleBarcodeDetected(code);
            return;
        }
        
        // Logique de détection immédiate ou rapide
        if (lastDetectedCode === code) {
            detectionCount++;
            console.log(`🔄 Code confirmé (${detectionCount}/1):`, code);
            
            // Validation immédiate dès la première répétition
            console.log('✅ Code validé par répétition immédiate:', code);
            isProcessingDetection = true;
            handleBarcodeDetected(code);
        } else {
            // Nouveau code détecté
            lastDetectedCode = code;
            detectionCount = 1;
            
            // Seuil de confiance très permissif pour meilleure détection
            if (confidence >= 15) {
                console.log('✅ Code validé par confiance:', code, 'Confiance:', confidence);
                isProcessingDetection = true;
                handleBarcodeDetected(code);
            } else {
                console.log('⏳ Code en attente de confirmation:', code, 'Confiance:', confidence);
                
                // Timeout très rapide : accepter après 300ms
                setTimeout(() => {
                    if (lastDetectedCode === code && !isProcessingDetection) {
                        console.log('✅ Code validé par timeout (confiance faible):', code);
                        isProcessingDetection = true;
                        handleBarcodeDetected(code);
                    }
                }, 300);
            }
        }
    });
}

/**
 * Scanner code-barres avec Quagga (méthode alternative)
 */
function scanBarcode(imageData) {
    // Cette fonction est maintenant un fallback
    // La détection principale se fait via initQuaggaForBarcodes
    console.log('🔍 Scan code-barres via imageData (fallback)');
}

/**
 * Gérer la détection d'un QR code
 */
function handleQRCodeDetected(data) {
    const status = document.getElementById('universal_scanner_status');
    
    // Arrêter le scanner
    stopUniversalScanner();
    
    status.innerHTML = '<i class="fas fa-check me-2"></i>QR Code détecté !';
    status.className = 'scanner-status success';
    
    // Vérifier si c'est une URL
    if (data.startsWith('http://') || data.startsWith('https://')) {
        // C'est une URL - rediriger dans le même onglet
        setTimeout(() => {
            console.log('🔗 [SCANNER] Redirection vers:', data);
            window.location.href = data;
        }, 1000);
    } else {
        // Traiter comme un code produit
        setTimeout(() => {
            handleProductCode(data);
        }, 1000);
    }
}

/**
 * Gérer la détection d'un code-barres
 */
function handleBarcodeDetected(code) {
    const status = document.getElementById('universal_scanner_status');
    
    console.log('🎯 Traitement code-barres:', code);
    
    // Arrêter le scanner
    stopUniversalScanner();
    
    status.innerHTML = '<i class="fas fa-check me-2"></i>Code-barres détecté !';
    status.className = 'scanner-status success';
    
    // Les codes-barres sont généralement des codes produits
    setTimeout(() => {
        handleProductCode(code);
    }, 1000);
}

/**
 * Gérer un code produit
 */
function handleProductCode(code) {
    console.log('🔍 [SCANNER] Code produit détecté:', code);
    console.log('🔍 [SCANNER] Page actuelle:', window.location.href);
    console.log('🔍 [SCANNER] Fonction gbOpenAdjust disponible:', typeof gbOpenAdjust === 'function');
    
    // Fermer le scanner
    closeUniversalScanner();
    
    // Vérifier si le produit existe
    const url = `ajax/verifier_produit.php?code=${encodeURIComponent(code)}`;
    console.log('🔍 [SCANNER] URL de vérification:', url);
    
    fetch(url)
        .then(response => {
            console.log('🔍 [SCANNER] Réponse HTTP:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('🔍 [SCANNER] Données reçues:', data);
            
            if (data.existe && data.id) {
                console.log('✅ [SCANNER] Produit trouvé - ID:', data.id, 'Nom:', data.nom);
                
                // Utiliser la fonction existante de l'inventaire si disponible
                if (typeof gbOpenAdjust === 'function') {
                    console.log('🎯 [SCANNER] Ouverture du modal d\'ajustement avec gbOpenAdjust');
                    gbOpenAdjust(data.id);
                } else if (typeof openScanStockModal === 'function') {
                    console.log('🎯 [SCANNER] Ouverture du modal d\'ajustement avec openScanStockModal');
                    openScanStockModal(data);
                } else {
                    console.log('⚠️ [SCANNER] Aucune fonction d\'ajustement disponible, affichage d\'informations produit');
                    
                    // Au lieu de rediriger, afficher les informations du produit
                    showProductInfo(data);
                }
            } else if (data.error) {
                console.error('❌ [SCANNER] Erreur serveur:', data.error);
                alert(`Erreur serveur: ${data.error}`);
            } else {
                console.warn('⚠️ [SCANNER] Produit non trouvé:', code);
                
                // Demander confirmation pour ajouter le produit
                const confirmation = confirm(`Produit non trouvé: ${code}\n\nSouhaitez-vous ajouter ce produit au stock ?`);
                
                if (confirmation) {
                    console.log('✅ [SCANNER] Utilisateur confirme l\'ajout du produit');
                    openAddProductModal(code);
                } else {
                    console.log('❌ [SCANNER] Utilisateur annule l\'ajout du produit');
                }
            }
        })
        .catch(error => {
            console.error('❌ [SCANNER] Erreur fetch:', error);
            alert('Erreur lors de la vérification du produit');
        });
}

/**
 * Afficher les informations d'un produit trouvé
 */
function showProductInfo(productData) {
    console.log('📋 [SCANNER] Affichage des informations produit:', productData);
    
    // NETTOYAGE COMPLET AVANT CRÉATION
    console.log('🧹 [MODAL] Nettoyage complet des modals existants...');
    
    // 1. Fermer et supprimer tous les modals productInfoModal existants
    const existingModals = document.querySelectorAll('#productInfoModal');
    existingModals.forEach((modal, index) => {
        console.log(`🗑️ [MODAL] Suppression modal existant ${index + 1}`);
        try {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
                bootstrapModal.dispose();
            }
        } catch (e) {
            console.log('⚠️ [MODAL] Erreur lors de la fermeture Bootstrap:', e);
        }
        modal.remove();
    });
    
    // 2. Supprimer tous les backdrops orphelins
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach((backdrop, index) => {
        console.log(`🗑️ [BACKDROP] Suppression backdrop ${index + 1}`);
        backdrop.remove();
    });
    
    // 3. Nettoyer les classes du body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // 4. Nettoyer les fonctions globales existantes
    if (window.decreaseProductQuantity) delete window.decreaseProductQuantity;
    if (window.increaseProductQuantity) delete window.increaseProductQuantity;
    if (window.saveProductQuantity) delete window.saveProductQuantity;
    
    console.log('✅ [MODAL] Nettoyage terminé');
    
    // Créer un modal d'information produit
    const modalHtml = `
        <div id="productInfoModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-box me-2"></i>Produit Trouvé
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <h6 class="fw-bold text-primary">${productData.nom}</h6>
                                <p class="text-muted mb-2">Référence: <code>${productData.reference}</code></p>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-boxes text-info me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">Quantité en stock</small>
                                        <span class="fw-bold fs-5 ${productData.quantite > 0 ? 'text-success' : 'text-danger'}">${productData.quantite}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-tag text-warning me-2"></i>
                                    <div>
                                        <small class="text-muted d-block">ID Produit</small>
                                        <span class="fw-bold">#${productData.id}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Fermer
                        </button>
                        <button type="button" class="btn btn-primary" onclick="goToInventoryPage(${productData.id})">
                            <i class="fas fa-edit me-1"></i>Ajuster Stock
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Supprimer le modal existant s'il y en a un
    const existingModal = document.getElementById('productInfoModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Ajouter le modal au DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Ouvrir le modal
    const modal = new bootstrap.Modal(document.getElementById('productInfoModal'));
    modal.show();
    
    // Nettoyer le modal quand il se ferme
    document.getElementById('productInfoModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

/**
 * Aller à la page inventaire pour ajuster le stock
 */
function goToInventoryPage(productId) {
    console.log('🔗 [SCANNER] Redirection vers inventaire pour ajustement, ID:', productId);
    window.location.href = `index.php?page=inventaire#product-${productId}`;
}

/**
 * Ouvrir le modal d'ajout de produit avec code pré-rempli
 */
function openAddProductModal(code) {
    console.log('📦 [SCANNER] Ouverture du modal d\'ajout de produit avec code:', code);
    
    // Vérifier si on est sur la page inventaire
    if (typeof gbOpen === 'function' && document.getElementById('gbAddModal')) {
        console.log('✅ [SCANNER] Modal d\'ajout disponible sur cette page');
        
        // Ouvrir le modal d'ajout de produit
        gbOpen('gbAddModal');
        
        // Pré-remplir le champ référence avec le code scanné
        setTimeout(() => {
            const referenceField = document.querySelector('input[name="reference"]') || document.getElementById('gb_reference');
            if (referenceField) {
                referenceField.value = code;
                referenceField.focus();
                console.log('✅ [SCANNER] Champ référence pré-rempli avec:', code);
            } else {
                console.warn('⚠️ [SCANNER] Champ référence non trouvé dans le modal');
                console.log('🔍 [SCANNER] Champs disponibles:', document.querySelectorAll('input').length);
            }
        }, 300);
        
    } else {
        console.log('⚠️ [SCANNER] Modal d\'ajout non disponible, redirection vers inventaire');
        
        // Rediriger vers l'inventaire avec le code en paramètre
        window.location.href = `index.php?page=inventaire&add_product=1&reference=${encodeURIComponent(code)}`;
    }
}

/**
 * Arrêter le scanner
 */
function stopUniversalScanner() {
    console.log('🛑 Arrêt du scanner universel...');
    
    // Arrêter l'animation jsQR
    if (universalScannerAnimation) {
        cancelAnimationFrame(universalScannerAnimation);
        universalScannerAnimation = null;
    }
    
    // Arrêter Quagga
    if (quaggaInitialized && typeof Quagga !== 'undefined') {
        try {
            Quagga.stop();
            console.log('✅ Quagga arrêté');
        } catch (e) {
            console.log('⚠️ Erreur arrêt Quagga:', e);
        }
        quaggaInitialized = false;
    }
    
    // Arrêter le stream vidéo
    if (universalScannerStream) {
        universalScannerStream.getTracks().forEach(track => track.stop());
        universalScannerStream = null;
        console.log('✅ Stream vidéo arrêté');
    }
    
    // Réinitialiser les variables de détection
    lastDetectedCode = null;
    detectionCount = 0;
    isProcessingDetection = false;
}

/**
 * Fermer le scanner
 */
function closeUniversalScanner() {
    stopUniversalScanner();
    const modal = bootstrap.Modal.getInstance(document.getElementById('universal_scanner_modal'));
    if (modal) {
        modal.hide();
    }
}

/**
 * Changer de caméra
 */
function switchCamera() {
    currentCamera = currentCamera === 'environment' ? 'user' : 'environment';
    startUniversalScanner();
}

/**
 * Activer/désactiver le flash
 */
function toggleScannerFlash() {
    if (universalScannerStream) {
        const track = universalScannerStream.getVideoTracks()[0];
        const capabilities = track.getCapabilities();
        
        if (capabilities.torch) {
            flashEnabled = !flashEnabled;
            track.applyConstraints({
                advanced: [{ torch: flashEnabled }]
            });
            
            const flashIcon = document.getElementById('flashIcon');
            flashIcon.className = flashEnabled ? 'fas fa-flashlight-on' : 'fas fa-flashlight';
        } else {
            alert('Flash non disponible sur cette caméra');
        }
    }
}

/**
 * Saisie manuelle de code
 */
function manualCodeEntry() {
    const code = prompt('Entrez le code manuellement:');
    if (code && code.trim()) {
        if (code.startsWith('http://') || code.startsWith('https://')) {
            console.log('🔗 [SCANNER] Redirection manuelle vers:', code);
            window.location.href = code;
        } else {
            handleProductCode(code.trim());
        }
    }
}

/**
 * Mettre à jour le mode de scanner
 */
function updateScannerMode(mode) {
    console.log('🔄 Mode de scan changé:', mode);
    currentScanMode = mode;
    
    // Redémarrer le scanner avec le nouveau mode
    if (universalScannerStream) {
        // Arrêter les scanners actuels
        if (universalScannerAnimation) {
            cancelAnimationFrame(universalScannerAnimation);
            universalScannerAnimation = null;
        }
        
        if (quaggaInitialized && typeof Quagga !== 'undefined') {
            try {
                Quagga.stop();
                quaggaInitialized = false;
            } catch (e) {
                console.log('Quagga déjà arrêté');
            }
        }
        
        // Redémarrer selon le nouveau mode
        setTimeout(() => {
            if (mode === 'barcode' || mode === 'auto') {
                initQuaggaForBarcodes();
            }
            
            if (mode === 'qr' || mode === 'auto') {
                startScanning();
            }
        }, 100);
    }
}

</script>