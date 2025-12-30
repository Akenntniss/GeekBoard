<?php
// Obtenir les informations nécessaires
$navbar_assets_path = '/assets/';
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Compter les tâches en attente (si disponible)
$tasks_count = 0;
try {
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM taches WHERE statut = 'en_attente'");
        $stmt->execute();
        $tasks_count = $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $tasks_count = 0;
}
?>

<!-- Chargement du CSS optimisé pour performance -->
<link rel="stylesheet" href="/assets/css/futuristic-menu-optimized.css">

<!-- MENU FUTURISTE/CORPORATE MODAL -->
<div class="modal fade" id="futuristicMenuModal" tabindex="-1" aria-labelledby="futuristicMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content futuristic-menu-content">
            <!-- Header du menu -->
            <div class="futuristic-menu-header">
                <div class="menu-header-left">
                    <div class="logo-container">
                        <img src="<?php echo $navbar_assets_path; ?>images/logo/logoservo.png" alt="SERVO" class="menu-logo">
                        <div class="logo-text">
                            <h5 class="menu-title" id="futuristicMenuModalLabel">SERVO</h5>
                            <span class="menu-subtitle">Command Center</span>
                        </div>
                    </div>
                </div>
                <div class="menu-header-right">
                    <button type="button" class="futuristic-close-btn" data-bs-dismiss="modal" aria-label="Fermer">
                        <span class="close-line"></span>
                        <span class="close-line"></span>
                    </button>
                </div>
            </div>

            <!-- Corps du menu -->
            <div class="futuristic-menu-body">
                <style>
                /* Grille responsive: 2x2 mobile, 4x4 tablette, auto pour desktop */
                #futuristicMenuModal .menu-grid {
                    display: grid;
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                    gap: 12px;
                }
                #futuristicMenuModal .menu-card { min-height: 110px; border-radius: 16px; }
                
                /* Couleurs des icônes par catégorie */
                /* Actions Principales - Bleu cyan */
                #futuristicMenuModal .menu-section:nth-child(1) .card-icon i { color: #00d4ff !important; }
                #futuristicMenuModal .menu-section:nth-child(1) .card-icon { background: rgba(0, 212, 255, 0.15) !important; }
                
                /* Missions - Vert émeraude */
                #futuristicMenuModal .menu-section:nth-child(2) .card-icon i { color: #10b981 !important; }
                #futuristicMenuModal .menu-section:nth-child(2) .card-icon { background: rgba(16, 185, 129, 0.15) !important; }
                
                /* Gestion - Violet/Mauve */
                #futuristicMenuModal .menu-section:nth-child(3) .card-icon i { color: #a855f7 !important; }
                #futuristicMenuModal .menu-section:nth-child(3) .card-icon { background: rgba(168, 85, 247, 0.15) !important; }
                
                /* Communication - Orange */
                #futuristicMenuModal .menu-section:nth-child(4) .card-icon i { color: #f59e0b !important; }
                #futuristicMenuModal .menu-section:nth-child(4) .card-icon { background: rgba(245, 158, 11, 0.15) !important; }
                
                /* Administration - Rouge/Rose */
                #futuristicMenuModal .menu-section:nth-child(5) .card-icon i { color: #ef4444 !important; }
                #futuristicMenuModal .menu-section:nth-child(5) .card-icon { background: rgba(239, 68, 68, 0.15) !important; }
                
                /* Effets hover harmonieux */
                #futuristicMenuModal .menu-card:hover .card-icon i {
                    text-shadow: 0 0 15px currentColor !important;
                    transform: scale(1.1) !important;
                    transition: all 0.3s ease !important;
                }
                
                /* Mode nuit - couleurs plus vives */
                body.night-mode #futuristicMenuModal .menu-section:nth-child(1) .card-icon i,
                .night-mode #futuristicMenuModal .menu-section:nth-child(1) .card-icon i { 
                    color: #00f5ff !important; 
                    text-shadow: 0 0 10px rgba(0, 245, 255, 0.6) !important;
                }
                body.night-mode #futuristicMenuModal .menu-section:nth-child(2) .card-icon i,
                .night-mode #futuristicMenuModal .menu-section:nth-child(2) .card-icon i { 
                    color: #34d399 !important; 
                    text-shadow: 0 0 10px rgba(52, 211, 153, 0.6) !important;
                }
                body.night-mode #futuristicMenuModal .menu-section:nth-child(3) .card-icon i,
                .night-mode #futuristicMenuModal .menu-section:nth-child(3) .card-icon i { 
                    color: #c084fc !important; 
                    text-shadow: 0 0 10px rgba(192, 132, 252, 0.6) !important;
                }
                body.night-mode #futuristicMenuModal .menu-section:nth-child(4) .card-icon i,
                .night-mode #futuristicMenuModal .menu-section:nth-child(4) .card-icon i { 
                    color: #fbbf24 !important; 
                    text-shadow: 0 0 10px rgba(251, 191, 36, 0.6) !important;
                }
                body.night-mode #futuristicMenuModal .menu-section:nth-child(5) .card-icon i,
                .night-mode #futuristicMenuModal .menu-section:nth-child(5) .card-icon i { 
                    color: #f87171 !important; 
                    text-shadow: 0 0 10px rgba(248, 113, 113, 0.6) !important;
                }
                
                /* Mode sombre (dark-mode) - Icônes blanches pour toutes les sections */
                body.dark-mode #futuristicMenuModal .card-icon i,
                .dark-mode #futuristicMenuModal .card-icon i {
                    color: #ffffff !important;
                    text-shadow: 0 0 10px rgba(255, 255, 255, 0.3) !important;
                }
                
                body.dark-mode #futuristicMenuModal .menu-card:hover .card-icon i,
                .dark-mode #futuristicMenuModal .menu-card:hover .card-icon i {
                    color: #ffffff !important;
                    text-shadow: 0 0 20px rgba(255, 255, 255, 0.5) !important;
                }
                
                /* Tablette (iPad) 4 colonnes, lignes plus compactes */
                @media (max-width: 1024px) and (min-width: 768px) {
                    #futuristicMenuModal .menu-grid {
                        grid-template-columns: repeat(4, minmax(0, 1fr));
                        gap: 10px;
                    }
                    #futuristicMenuModal .menu-card { min-height: 100px; }
                }
                
                /* Mobile 2 colonnes - Modal plein écran */
                @media (max-width: 767px) {
                    #futuristicMenuModal .modal-dialog { 
                        margin: 0.25rem !important; 
                        height: 98vh !important;
                        max-height: 98vh !important;
                    }
                    #futuristicMenuModal .modal-content {
                        height: 98vh !important;
                        max-height: 98vh !important;
                        display: flex !important;
                        flex-direction: column !important;
                        margin: 0 !important;
                        padding: 0 !important;
                    }
                    #futuristicMenuModal .futuristic-menu-header {
                        padding: 0.35rem 0.75rem !important;
                        flex-shrink: 0 !important;
                    }
                    #futuristicMenuModal .futuristic-menu-body {
                        flex: 1 !important;
                        overflow-y: auto !important;
                        padding: 0.75rem !important;
                        max-height: none !important;
                    }
                    #futuristicMenuModal .futuristic-menu-footer {
                        padding: 0.25rem 0.5rem !important;
                        flex-shrink: 0 !important;
                        min-height: 32px !important;
                        max-height: 32px !important;
                    }
                    #futuristicMenuModal .footer-brand {
                        font-size: 0.75rem !important;
                    }
                    #futuristicMenuModal .btn-footer-close {
                        padding: 0.15rem 0.4rem !important;
                        font-size: 0.7rem !important;
                    }
                    #futuristicMenuModal .menu-grid {
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                        gap: 10px;
                    }
                    #futuristicMenuModal .menu-card { min-height: 96px; }
                    #futuristicMenuModal .card-title { font-size: 0.95rem; }
                    #futuristicMenuModal .card-subtitle { display: none; }
                    #futuristicMenuModal .card-icon i { font-size: 1.3rem; }
                    #futuristicMenuModal .menu-logo {
                        height: 28px !important;
                        width: 28px !important;
                    }
                    #futuristicMenuModal .menu-title {
                        font-size: 1rem !important;
                    }
                    #futuristicMenuModal .menu-subtitle {
                        font-size: 0.65rem !important;
                    }
                }
                </style>
                
                <script>
                // Gestion de la navigation dans le menu futuriste
                document.addEventListener('DOMContentLoaded', function() {
                    const menuCards = document.querySelectorAll('#futuristicMenuModal .menu-card[href]');
                    const modal = document.getElementById('futuristicMenuModal');
                    
                    console.log('🔧 [FUTURISTIC-MENU] Initialisation navigation:', {
                        menuCards: menuCards.length,
                        modal: !!modal
                    });
                    
                    menuCards.forEach(card => {
                        card.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const href = this.getAttribute('href');
                            console.log('🔗 [FUTURISTIC-MENU] Clic détecté sur:', href);
                            
                            if (href && href !== '#') {
                                // Navigation immédiate avec délai minimal pour l'animation
                                console.log('🚀 [FUTURISTIC-MENU] Navigation immédiate vers:', href);
                                
                                // Fermer le modal manuellement
                                if (modal) {
                                    modal.classList.remove('show');
                                    modal.style.display = 'none';
                                    modal.setAttribute('aria-hidden', 'true');
                                    modal.removeAttribute('aria-modal');
                                    
                                    // Supprimer le backdrop
                                    const backdrops = document.querySelectorAll('.modal-backdrop');
                                    backdrops.forEach(backdrop => backdrop.remove());
                                    
                                    // Restaurer le body
                                    document.body.classList.remove('modal-open');
                                    document.body.style.overflow = '';
                                    document.body.style.paddingRight = '';
                                }
                                
                                // Navigation immédiate
                                setTimeout(() => {
                                    window.location.href = href;
                                }, 50); // Délai minimal pour permettre la fermeture visuelle
                            }
                        });
                    });
                });
                </script>
                <!-- Section Actions Principales -->
                <div class="menu-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h6 class="section-title">Actions Principales</h6>
                        <div class="section-line"></div>
                    </div>
                    
                    <div class="menu-grid">
                        <!-- Accueil -->
                        <a href="index.php" class="menu-card <?php echo empty($_GET['page']) || $currentPage == 'accueil' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-home"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Accueil</h6>
                                    <p class="card-subtitle">Tableau de bord</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Réparations -->
                        <a href="index.php?page=reparations" class="menu-card <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-tools"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Réparations</h6>
                                    <p class="card-subtitle">Gérer les réparations</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Tâches -->
                        <a href="index.php?page=taches" class="menu-card <?php echo $currentPage == 'taches' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-tasks"></i>
                                    <?php if ($tasks_count > 0): ?>
                                        <span class="notification-badge"><?php echo $tasks_count; ?></span>
                                    <?php endif; ?>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Tâches</h6>
                                    <p class="card-subtitle">Gérer les tâches</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Commandes -->
                        <a href="index.php?page=commandes_pieces" class="menu-card <?php echo $currentPage == 'commandes_pieces' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Commandes</h6>
                                    <p class="card-subtitle">Pièces & fournitures</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Catalogue -->
                        <a href="index.php?page=catalogue_fournisseur" class="menu-card <?php echo $currentPage == 'catalogue_fournisseur' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-book-open"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Catalogue</h6>
                                    <p class="card-subtitle">Catalogue Fournisseur</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Rachat -->
                        <a href="index.php?page=rachat_appareils" class="menu-card <?php echo $currentPage == 'rachat_appareils' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-exchange-alt"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Rachat</h6>
                                    <p class="card-subtitle">Appareils d'occasion</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Base de connaissance -->
                        <a href="index.php?page=base_connaissances" class="menu-card <?php echo $currentPage == 'base_connaissances' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-book"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Base de connaissance</h6>
                                    <p class="card-subtitle">Documentation</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Inventaire -->
                        <a href="index.php?page=inventaire" class="menu-card <?php echo $currentPage == 'inventaire' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-boxes"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Inventaire</h6>
                                    <p class="card-subtitle">Stock & produits</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>
                    </div>
                </div>

                <!-- Section Utilitaires -->
                <div class="menu-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h6 class="section-title">Utilitaires</h6>
                        <div class="section-line"></div>
                    </div>
                    
                    <div class="menu-grid">
                        <!-- Missions -->
                        <a href="index.php?page=mes_missions" class="menu-card <?php echo $currentPage == 'mes_missions' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-clipboard-check"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Missions</h6>
                                    <p class="card-subtitle">Tâches assignées</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Absences & Retards -->
                        <a href="index.php?page=presence_gestion" class="menu-card <?php echo in_array($currentPage, ['presence_gestion', 'presence_ajouter', 'presence_calendrier', 'presence_export', 'presence_modifier']) ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-user-clock"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Absences & Retards</h6>
                                    <p class="card-subtitle">Présences</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Appels -->
                        <a href="index.php?page=appels" class="menu-card <?php echo $currentPage == 'appels' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-phone-alt"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Appels</h6>
                                    <p class="card-subtitle">Audio & Vidéo</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Messagerie Chat -->
                        <a href="index.php?page=messagerie" class="menu-card <?php echo $currentPage == 'messagerie' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-comments"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Messagerie Chat</h6>
                                    <p class="card-subtitle">Chat & Email</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                    </div>
                </div>



                <!-- Section Administration (visible aux admins uniquement) -->
                <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                <div class="menu-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h6 class="section-title">Administration</h6>
                        <div class="section-line"></div>
                    </div>
                    
                    <div class="menu-grid">
                        <!-- Admin missions -->
                        <a href="index.php?page=admin_missions" class="menu-card <?php echo $currentPage == 'admin_missions' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-tasks"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Admin missions</h6>
                                    <p class="card-subtitle">Gestion missions</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Employés -->
                        <a href="index.php?page=employes" class="menu-card <?php echo $currentPage == 'employes' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-user-tie"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Employés</h6>
                                    <p class="card-subtitle">Gestion équipe</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Pointage Admin -->
                        <a href="index.php?page=admin_timetracking" class="menu-card <?php echo (strpos($_SERVER['REQUEST_URI'], 'admin_timetracking') !== false) ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-clock"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Pointage Admin</h6>
                                    <p class="card-subtitle">Temps de travail</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Log Réparation -->
                        <a href="index.php?page=reparation_logs" class="menu-card <?php echo $currentPage == 'reparation_logs' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Log Réparation</h6>
                                    <p class="card-subtitle">Logs réparations</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>


                        <!-- KPI Dashboard -->
                        <a href="/kpi_dashboard_standalone.php" class="menu-card <?php echo $currentPage == 'kpi_dashboard' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-chart-line"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">KPI Dashboard</h6>
                                    <p class="card-subtitle">Indicateurs clés</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Signalements bugs -->
                        <a href="index.php?page=bug-reports" class="menu-card <?php echo $currentPage == 'bug-reports' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-bug"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Signalements bugs</h6>
                                    <p class="card-subtitle">Bugs & feedback</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Parametre -->
                        <a href="index.php?page=parametre" class="menu-card <?php echo $currentPage == 'parametre' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-cog"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Parametre</h6>
                                    <p class="card-subtitle">Configuration</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Template SMS -->
                        <a href="index.php?page=template_sms" class="menu-card <?php echo $currentPage == 'template_sms' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-comment-dots"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Template SMS</h6>
                                    <p class="card-subtitle">Modèles</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                    </div>
                </div>
                <?php endif; ?>

                <!-- Section Gestion -->
                <div class="menu-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <h6 class="section-title">Gestion</h6>
                        <div class="section-line"></div>
                    </div>
                    
                    <div class="menu-grid">
                        <!-- Clients -->
                        <a href="index.php?page=clients" class="menu-card <?php echo $currentPage == 'clients' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-users"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Clients</h6>
                                    <p class="card-subtitle">Base clients</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- SMS Historique -->
                        <a href="index.php?page=sms_historique" class="menu-card <?php echo $currentPage == 'sms_historique' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-history"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">SMS Historique</h6>
                                    <p class="card-subtitle">Messages envoyés</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Fournisseurs -->
                        <a href="index.php?page=fournisseurs" class="menu-card <?php echo $currentPage == 'fournisseurs' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-truck"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Fournisseurs</h6>
                                    <p class="card-subtitle">Approvisionnement</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Partenaires -->
                        <a href="index.php?page=comptes_partenaires" class="menu-card <?php echo $currentPage == 'comptes_partenaires' ? 'active' : ''; ?>" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-handshake"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Partenaires</h6>
                                    <p class="card-subtitle">Comptes externes</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                    </div>
                </div>

                <!-- Section Déconnexion -->
                <div class="menu-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-power-off"></i>
                        </div>
                        <h6 class="section-title">Déconnexion</h6>
                        <div class="section-line"></div>
                    </div>
                    
                    <div class="menu-grid">
                        <!-- Déconnexion -->
                        <a href="index.php?page=logout" class="menu-card logout-card" >
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Se déconnecter</h6>
                                    <p class="card-subtitle">Fermer la session</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Footer minimaliste -->
            <div class="futuristic-menu-footer">
                <div class="footer-brand">
                    <span>SERVO</span>
                </div>
                <button type="button" class="btn-footer-close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                    <span>Fermer</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Particules de fond pour le thème futuriste -->
<div class="futuristic-particles" id="futuristicParticles"></div>
