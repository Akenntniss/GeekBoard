<?php
// Menu latéral moderne - Composant séparé
// Détection du type d'appareil et récupération des données

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

// Récupérer le nom de la base de données actuelle
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
$assets_path = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) ? '../assets/' : 'assets/';
?>

<!-- Inclure les styles et scripts du menu latéral -->
<link rel="stylesheet" href="<?php echo $assets_path; ?>css/sidebar-menu-modern.css">
<script src="<?php echo $assets_path; ?>js/sidebar-menu-modern.js" defer></script>

<!-- MENU LATÉRAL MODERNE -->
<div class="modern-menu-container">
    <!-- Checkbox invisible pour contrôler le menu -->
    <input type="checkbox" id="modern-menu-checkbox" class="hamburger-checkbox">
    
    <!-- Icône hamburger animée -->
    <div class="hamburger-icon">
        <label for="modern-menu-checkbox">
            <span></span>
            <span></span>
            <span></span>
        </label>
    </div>
    
    <!-- Overlay sombre -->
    <div class="menu-overlay"></div>
    
    <!-- Panneau du menu -->
    <div class="menu-pane">
        <!-- Header du menu -->
        <div class="menu-header">
            <div class="menu-logo">
                <img src="<?php echo $assets_path; ?>images/logo/logoservo.png" alt="GeekBoard">
                <h2>GeekBoard</h2>
            </div>
            <?php if (isset($_SESSION['full_name'])): ?>
            <div class="menu-user-info">
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                <?php if (isset($_SESSION['shop_name'])): ?>
                <span class="badge">
                    <?php echo htmlspecialchars($_SESSION['shop_name']); ?>
                    <?php if (!empty($db_name)): ?>
                    <small>(<?php echo htmlspecialchars($db_name); ?>)</small>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Navigation du menu -->
        <nav class="menu-nav">
            <!-- Section Actions Principales -->
            <div class="menu-section">
                <div class="menu-section-title">🏠 Actions Principales</div>
                <ul class="menu-links">
                    <li>
                        <a href="index.php?page=accueil" class="<?php echo $currentPage == 'accueil' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i>
                            <span>Accueil</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=reparations" class="<?php echo $currentPage == 'reparations' ? 'active' : ''; ?>">
                            <i class="fas fa-tools"></i>
                            <span>Réparations</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=taches" class="<?php echo $currentPage == 'taches' ? 'active' : ''; ?>">
                            <i class="fas fa-tasks"></i>
                            <span>Tâches</span>
                            <?php if ($tasks_count > 0): ?>
                                <span class="menu-badge"><?php echo $tasks_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=commandes_pieces" class="<?php echo $currentPage == 'commandes_pieces' ? 'active' : ''; ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Commandes</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=inventaire" class="<?php echo $currentPage == 'inventaire' ? 'active' : ''; ?>">
                            <i class="fas fa-boxes"></i>
                            <span>Inventaire</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=rachat_appareils" class="<?php echo $currentPage == 'rachat_appareils' ? 'active' : ''; ?>">
                            <i class="fas fa-exchange-alt"></i>
                            <span>Rachats</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Section Outils & Qualité -->
            <div class="menu-section">
                <div class="menu-section-title">🔧 Outils & Qualité</div>
                <ul class="menu-links">
                    <li>
                        <a href="index.php?page=clients" class="<?php echo $currentPage == 'clients' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Clients</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=comptes_partenaires" class="<?php echo $currentPage == 'comptes_partenaires' ? 'active' : ''; ?>">
                            <i class="fas fa-handshake"></i>
                            <span>Partenaires</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=base_connaissances" class="<?php echo $currentPage == 'base_connaissances' ? 'active' : ''; ?>">
                            <i class="fas fa-book"></i>
                            <span>Base de connaissance</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=sms_historique" class="<?php echo $currentPage == 'sms_historique' ? 'active' : ''; ?>">
                            <i class="fas fa-sms"></i>
                            <span>Historique SMS</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=presence_gestion" class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'presence') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-check"></i>
                            <span>Présences</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=mes_missions" class="<?php echo $currentPage == 'mes_missions' ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Mes Missions</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=bug-reports" class="<?php echo $currentPage == 'bug-reports' ? 'active' : ''; ?>">
                            <i class="fas fa-bug"></i>
                            <span>Bug Report</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Section Administration - Visible uniquement aux admins -->
            <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
            <div class="menu-section">
                <div class="menu-section-title">⚙️ Administration</div>
                <ul class="menu-links">
                    <li>
                        <a href="index.php?page=employes" class="<?php echo $currentPage == 'employes' ? 'active' : ''; ?>">
                            <i class="fas fa-user-tie"></i>
                            <span>Employés</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=admin_timetracking" class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin_timetracking') !== false) ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i>
                            <span>Pointage</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=reparation_logs" class="<?php echo $currentPage == 'reparation_logs' ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Log Réparation</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=admin_missions" class="<?php echo $currentPage == 'admin_missions' ? 'active' : ''; ?>">
                            <i class="fas fa-tasks"></i>
                            <span>Admin Mission</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=campagne_sms" class="<?php echo $currentPage == 'campagne_sms' ? 'active' : ''; ?>">
                            <i class="fas fa-sms"></i>
                            <span>SMS Campagne</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=template_sms" class="<?php echo $currentPage == 'template_sms' ? 'active' : ''; ?>">
                            <i class="fas fa-comment-dots"></i>
                            <span>Template SMS</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=parametre" class="<?php echo $currentPage == 'parametre' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            <span>Paramètres</span>
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Section Déconnexion -->
            <div class="menu-section menu-logout">
                <ul class="menu-links">
                    <li>
                        <a href="index.php?page=deconnexion">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Déconnexion</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</div>
