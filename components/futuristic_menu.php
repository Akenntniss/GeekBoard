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
                
                /* Tablette (iPad) 4 colonnes, lignes plus compactes */
                @media (max-width: 1024px) and (min-width: 768px) {
                    #futuristicMenuModal .menu-grid {
                        grid-template-columns: repeat(4, minmax(0, 1fr));
                        gap: 10px;
                    }
                    #futuristicMenuModal .menu-card { min-height: 100px; }
                }
                
                /* Mobile 2 colonnes */
                @media (max-width: 767px) {
                    #futuristicMenuModal .modal-dialog { margin: 0.75rem !important; }
                    #futuristicMenuModal .menu-grid {
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                        gap: 10px;
                    }
                    #futuristicMenuModal .menu-card { min-height: 96px; }
                    #futuristicMenuModal .card-title { font-size: 0.95rem; }
                    #futuristicMenuModal .card-subtitle { display: none; }
                    #futuristicMenuModal .card-icon i { font-size: 1.3rem; }
                }
                </style>
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
                        <a href="index.php" class="menu-card <?php echo empty($_GET['page']) || $currentPage == 'accueil' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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
                        <a href="index.php?page=reparations" class="menu-card <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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

                        <!-- Nouvelle Réparation -->
                        <a href="index.php?page=ajouter_reparation" class="menu-card <?php echo $currentPage == 'ajouter_reparation' ? 'active' : ''; ?>" data-bs-dismiss="modal">
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-plus-circle"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Nouvelle Réparation</h6>
                                    <p class="card-subtitle">Créer une intervention</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Commandes -->
                        <a href="index.php?page=commandes_pieces" class="menu-card <?php echo $currentPage == 'commandes_pieces' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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

                        <!-- Tâches -->
                        <a href="index.php?page=taches" class="menu-card <?php echo $currentPage == 'taches' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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

                        <!-- Rachat -->
                        <a href="index.php?page=rachat_appareils" class="menu-card <?php echo $currentPage == 'rachat_appareils' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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
                        <a href="index.php?page=base_connaissances" class="menu-card <?php echo $currentPage == 'base_connaissances' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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

                        <!-- Clients -->
                        <a href="index.php?page=clients" class="menu-card <?php echo $currentPage == 'clients' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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
                    </div>
                </div>

                <!-- Section Missions -->
                <div class="menu-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h6 class="section-title">Missions</h6>
                        <div class="section-line"></div>
                    </div>
                    
                    <div class="menu-grid">
                        <!-- Missions -->
                        <a href="index.php?page=missions" class="menu-card <?php echo $currentPage == 'missions' ? 'active' : ''; ?>" data-bs-dismiss="modal">
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-list-check"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Missions</h6>
                                    <p class="card-subtitle">Vue d'ensemble</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Mes Missions -->
                        <a href="index.php?page=mes_missions" class="menu-card <?php echo $currentPage == 'mes_missions' ? 'active' : ''; ?>" data-bs-dismiss="modal">
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-clipboard-check"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Mes missions</h6>
                                    <p class="card-subtitle">Tâches assignées</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Admin missions (admin only) -->
                        <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                        <a href="index.php?page=admin_missions" class="menu-card <?php echo $currentPage == 'admin_missions' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section Communication -->
                <div class="menu-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h6 class="section-title">Communication</h6>
                        <div class="section-line"></div>
                    </div>
                    
                    <div class="menu-grid">
                        <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                        <!-- Campagne SMS -->
                        <a href="index.php?page=campagne_sms" class="menu-card <?php echo $currentPage == 'campagne_sms' ? 'active' : ''; ?>" data-bs-dismiss="modal">
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-sms"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Campagne SMS</h6>
                                    <p class="card-subtitle">Diffusions</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>

                        <!-- Template SMS -->
                        <a href="index.php?page=template_sms" class="menu-card <?php echo $currentPage == 'template_sms' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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
                        <?php endif; ?>

                        <!-- Historique SMS -->
                        <a href="index.php?page=sms_historique" class="menu-card <?php echo $currentPage == 'sms_historique' ? 'active' : ''; ?>" data-bs-dismiss="modal">
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-history"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Historique SMS</h6>
                                    <p class="card-subtitle">Messages envoyés</p>
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
                        <!-- Employés -->
                        <a href="index.php?page=employes" class="menu-card <?php echo $currentPage == 'employes' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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

                        <!-- Absences & Retards -->
                        <a href="index.php?page=presence_gestion" class="menu-card <?php echo in_array($currentPage, ['presence_gestion', 'presence_ajouter', 'presence_calendrier', 'presence_export', 'presence_modifier']) ? 'active' : ''; ?>" data-bs-dismiss="modal">
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

                        <!-- Pointage Admin -->
                        <a href="index.php?page=admin_timetracking" class="menu-card <?php echo (strpos($_SERVER['REQUEST_URI'], 'admin_timetracking') !== false) ? 'active' : ''; ?>" data-bs-dismiss="modal">
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
                        <a href="index.php?page=reparation_logs" class="menu-card <?php echo $currentPage == 'reparation_logs' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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
                        <a href="index.php?page=kpi_dashboard" class="menu-card <?php echo $currentPage == 'kpi_dashboard' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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
                        <a href="index.php?page=bug-reports" class="menu-card <?php echo $currentPage == 'bug-reports' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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
                        <a href="index.php?page=parametre" class="menu-card <?php echo $currentPage == 'parametre' ? 'active' : ''; ?>" data-bs-dismiss="modal">
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

                        <!-- Changer de magasin -->
                        <?php if (isset($_SESSION['shop_id'])): ?>
                        <a href="/pages/change_shop.php" class="menu-card" data-bs-dismiss="modal">
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-store"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Changer de magasin</h6>
                                    <p class="card-subtitle">Basculer de shop</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Section Déconnexion -->
                <div class="menu-section logout-section">
                    <div class="menu-grid single-item">
                        <a href="logout.php" class="menu-card logout-card" data-bs-dismiss="modal">
                            <div class="card-glow"></div>
                            <div class="card-content">
                                <div class="card-icon">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <div class="icon-particles"></div>
                                </div>
                                <div class="card-info">
                                    <h6 class="card-title">Déconnexion</h6>
                                    <p class="card-subtitle">Quitter la session</p>
                                </div>
                            </div>
                            <div class="card-overlay"></div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer du menu -->
            <div class="futuristic-menu-footer">
                <div class="footer-info">
                    <span class="user-info">
                        <?php if (isset($_SESSION['full_name'])): ?>
                            Connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['shop_name'])): ?>
                            <span class="shop-badge"><?php echo htmlspecialchars($_SESSION['shop_name']); ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="footer-version">
                    <span>GeekBoard v2.0</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Particules de fond pour le thème futuriste -->
<div class="futuristic-particles" id="futuristicParticles"></div>
