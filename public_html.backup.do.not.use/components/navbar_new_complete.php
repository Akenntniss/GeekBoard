<?php
// Barre de navigation desktop avec menu complet - Version restaurée
// Détection du type d'appareil
$isMobile = preg_match('/(iPhone|iPod|Android|BlackBerry|IEMobile|Opera Mini)/i', $_SERVER['HTTP_USER_AGENT']);
$isIPad = preg_match('/(iPad)/i', $_SERVER['HTTP_USER_AGENT']) || 
          (preg_match('/(Macintosh)/i', $_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']));

// Récupérer la page courante
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Compter les tâches actives
$tasks_count = 0;
try {
    if (function_exists('getShopDBConnection')) {
        $shop_pdo = getShopDBConnection();
        if ($shop_pdo) {
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM taches WHERE statut IN ('en_cours', 'nouveau')");
            $result = $stmt->fetch();
            $tasks_count = $result['count'] ?? 0;
        }
    }
} catch (Exception $e) {
    // Ignorer les erreurs de comptage
}

// Récupérer le nom de la base de données actuelle pour affichage
$db_name = '';
try {
    if (function_exists('getShopDBConnection')) {
        $shop_pdo = getShopDBConnection();
        if ($shop_pdo) {
            $db_name = $shop_pdo->query("SELECT DATABASE()")->fetchColumn();
        }
    }
} catch (Exception $e) {
    // Ignorer les erreurs
}

// Déterminer le bon chemin selon l'emplacement du fichier
$navbar_assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
?>

<!-- NAVBAR DESKTOP UNIQUEMENT -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-2" id="desktop-navbar">
    <div class="container-fluid px-3">
        <!-- Logo et nom -->
        <a class="navbar-brand me-0 me-lg-4 d-flex align-items-center" href="index.php">
            <img src="assets/images/logo/logoservo.png" alt="GeekBoard" height="40">
        </a>

        <!-- Barre de recherche universelle (desktop uniquement) -->
        <div class="d-none d-lg-flex flex-grow-1 me-3">
            <div class="search-container position-relative w-100" style="max-width: 500px;">
                <input type="text" id="universal-search" class="form-control pe-5" placeholder="Rechercher clients, réparations, tâches..." autocomplete="off">
                <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent" style="z-index: 10;">
                    <i class="fas fa-search text-muted"></i>
                </button>
                <div id="search-results" class="search-results position-absolute w-100 bg-white border rounded shadow-lg mt-1 d-none" style="z-index: 1000; max-height: 400px; overflow-y: auto;"></div>
            </div>
        </div>

        <!-- Informations utilisateur et magasin (desktop) -->
        <?php if (isset($_SESSION['full_name'])): ?>
        <div class="d-none d-lg-flex align-items-center me-3">
            <span class="fw-medium text-dark">
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                <?php if (isset($_SESSION['shop_name'])): ?>
                <span class="badge bg-info ms-1">
                    <?php echo htmlspecialchars($_SESSION['shop_name']); ?>
                    <?php if (!empty($db_name)): ?>
                    <small class="ms-1">(<?php echo htmlspecialchars($db_name); ?>)</small>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
        
        <!-- Boutons de navigation à droite -->
        <div class="d-none d-lg-flex align-items-center ms-auto">
            <!-- Bouton hamburger pour menu principal -->
            <button class="btn btn-outline-secondary ms-2 main-menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenuOffcanvas" aria-controls="mainMenuOffcanvas">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Version mobile du bouton hamburger -->
        <button class="navbar-toggler d-lg-none ms-auto main-menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenuOffcanvas" aria-controls="mainMenuOffcanvas">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>

<!-- MENU OFFCANVAS COMPLET (utilisé par mobile et desktop) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="mainMenuOffcanvas" aria-labelledby="mainMenuOffcanvasLabel">
    <div class="offcanvas-header p-0">
        <div class="sidebar-header w-100">
            <img src="<?php echo $navbar_assets_path; ?>images/logo/logoservo.png" alt="GeekBoard" height="40">
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
    </div>
    <div class="offcanvas-body p-3">
        <div class="dashboard-container">
            <!-- Section Actions Principales -->
            <div class="dashboard-section mb-4">
                <h6 class="dashboard-section-title">🏠 Actions Principales</h6>
                <div class="dashboard-grid">
                    <a class="dashboard-card <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>" href="index.php?page=reparations">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Réparations</h6>
                            <p class="dashboard-card-subtitle">Gérer les réparations</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'taches' ? 'active' : ''; ?>" href="index.php?page=taches">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-tasks"></i>
                            <?php if ($tasks_count > 0): ?>
                                <span class="dashboard-badge"><?php echo $tasks_count; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Tâches</h6>
                            <p class="dashboard-card-subtitle">Gérer les tâches</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'commandes_pieces' ? 'active' : ''; ?>" href="index.php?page=commandes_pieces">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Commandes</h6>
                            <p class="dashboard-card-subtitle">Pièces & fournitures</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'inventaire' ? 'active' : ''; ?>" href="index.php?page=inventaire">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Inventaire</h6>
                            <p class="dashboard-card-subtitle">Stock disponible</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'rachat_appareils' ? 'active' : ''; ?>" href="index.php?page=rachat_appareils">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Rachats</h6>
                            <p class="dashboard-card-subtitle">Appareils d'occasion</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'base_connaissances' ? 'active' : ''; ?>" href="index.php?page=base_connaissances">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Base de connaissance</h6>
                            <p class="dashboard-card-subtitle">Base documentaire</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Section Outils & Qualité -->
            <div class="dashboard-section mb-4">
                <h6 class="dashboard-section-title">🔧 Outils & Qualité</h6>
                <div class="dashboard-grid">
                    <a class="dashboard-card <?php echo $currentPage == 'clients' ? 'active' : ''; ?>" href="index.php?page=clients">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Clients</h6>
                            <p class="dashboard-card-subtitle">Base clients</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'comptes_partenaires' ? 'active' : ''; ?>" href="index.php?page=comptes_partenaires">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Partenaires</h6>
                            <p class="dashboard-card-subtitle">Comptes partenaires</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'sms_historique' ? 'active' : ''; ?>" href="index.php?page=sms_historique">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fas fa-sms"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Historique SMS</h6>
                            <p class="dashboard-card-subtitle">Messages envoyés</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo (strpos($_SERVER['REQUEST_URI'], 'presence') !== false) ? 'active' : ''; ?>" href="index.php?page=presence_gestion">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Présences</h6>
                            <p class="dashboard-card-subtitle">Gestion absences</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'mes_missions' ? 'active' : ''; ?>" href="index.php?page=mes_missions">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Mes Missions</h6>
                            <p class="dashboard-card-subtitle">Missions assignées</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'bug-reports' ? 'active' : ''; ?>" href="index.php?page=bug-reports">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <i class="fas fa-bug"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Bug Report</h6>
                            <p class="dashboard-card-subtitle">Signaler problème</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Section Administration - Visible uniquement aux admins -->
            <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
            <div class="dashboard-section mb-4">
                <h6 class="dashboard-section-title">⚙️ Administration</h6>
                <div class="dashboard-grid">
                    <a class="dashboard-card <?php echo $currentPage == 'employes' ? 'active' : ''; ?>" href="index.php?page=employes">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Employés</h6>
                            <p class="dashboard-card-subtitle">Gestion équipe</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo (strpos($_SERVER['REQUEST_URI'], 'admin_timetracking') !== false) ? 'active' : ''; ?>" href="index.php?page=admin_timetracking">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Pointage</h6>
                            <p class="dashboard-card-subtitle">Temps de travail</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'reparation_logs' ? 'active' : ''; ?>" href="index.php?page=reparation_logs">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Log Réparation</h6>
                            <p class="dashboard-card-subtitle">Logs réparations</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'admin_missions' ? 'active' : ''; ?>" href="index.php?page=admin_missions">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Admin Mission</h6>
                            <p class="dashboard-card-subtitle">Gestion missions</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'campagne_sms' ? 'active' : ''; ?>" href="index.php?page=campagne_sms">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <i class="fas fa-sms"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">SMS Campagne</h6>
                            <p class="dashboard-card-subtitle">Campagnes SMS</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'template_sms' ? 'active' : ''; ?>" href="index.php?page=template_sms">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Template SMS</h6>
                            <p class="dashboard-card-subtitle">Modèles SMS</p>
                        </div>
                    </a>

                    <a class="dashboard-card <?php echo $currentPage == 'parametre' ? 'active' : ''; ?>" href="index.php?page=parametre">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Paramètres</h6>
                            <p class="dashboard-card-subtitle">Configuration</p>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Section Déconnexion -->
            <div class="dashboard-section">
                <div class="dashboard-grid">
                    <a class="dashboard-card dashboard-card-logout" href="index.php?page=deconnexion">
                        <div class="dashboard-card-icon" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <div class="dashboard-card-content">
                            <h6 class="dashboard-card-title">Déconnexion</h6>
                            <p class="dashboard-card-subtitle">Se déconnecter</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

