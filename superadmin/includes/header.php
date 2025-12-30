<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Variables par défaut pour la personnalisation des pages
$page_title = $page_title ?? 'GeekBoard SuperAdmin';
$page_heading = $page_heading ?? 'Administration GeekBoard';
$page_subtitle = $page_subtitle ?? 'Plateforme de gestion centralisée';
$page_icon = $page_icon ?? 'fas fa-cogs';
$extra_head_html = $extra_head_html ?? '';
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Interface d'administration GeekBoard - Gestion centralisée des boutiques">
    <meta name="author" content="GeekBoard">
    <meta name="theme-color" content="#7c3aed">
    
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/modern-design-system.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/visibility-fix.css">
    <link rel="stylesheet" href="assets/css/visibility-fix.css">
    
    <?php echo $extra_head_html; ?>
</head>
<body>
    <?php if (isset($_SESSION['superadmin_id'])): ?>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <!-- Logo -->
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-tools"></i>
                <span>GeekBoard</span>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="sidebar-nav">
            <a href="index.php" class="sidebar-link <?php echo $current_page === 'index' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="shops.php" class="sidebar-link <?php echo $current_page === 'shops' ? 'active' : ''; ?>">
                <i class="fas fa-store"></i>
                <span>Magasins</span>
            </a>
            
            <a href="kpi.php" class="sidebar-link <?php echo $current_page === 'kpi' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>KPI</span>
            </a>
            
            <a href="create_shop.php" class="sidebar-link <?php echo $current_page === 'create_shop' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Nouveau Magasin</span>
            </a>
            
            <a href="subscriptions.php" class="sidebar-link <?php echo $current_page === 'subscriptions' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i>
                <span>Abonnements</span>
            </a>
            
            <a href="sms_billing.php" class="sidebar-link <?php echo $current_page === 'sms_billing' ? 'active' : ''; ?>">
                <i class="fas fa-comment-dollar"></i>
                <span>SMS Billing</span>
            </a>
            
            <a href="api_manager.php" class="sidebar-link <?php echo $current_page === 'api_manager' ? 'active' : ''; ?>">
                <i class="fas fa-sms"></i>
                <span>API SMS</span>
            </a>
            
            <a href="database_manager.php" class="sidebar-link <?php echo $current_page === 'database_manager' ? 'active' : ''; ?>">
                <i class="fas fa-database"></i>
                <span>Base de Données</span>
            </a>
            
            <div class="sidebar-divider"></div>
            
            <a href="settings_email.php" class="sidebar-link <?php echo $current_page === 'settings_email' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i>
                <span>Configuration Email</span>
            </a>
            
            <a href="configure_domains.php" class="sidebar-link <?php echo $current_page === 'configure_domains' ? 'active' : ''; ?>">
                <i class="fas fa-globe"></i>
                <span>Domaines</span>
            </a>
        </nav>
        
        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo htmlspecialchars(substr($_SESSION['superadmin_name'] ?? $_SESSION['superadmin_username'] ?? 'Admin', 0, 20)); ?></div>
                    <div class="sidebar-user-role">Super Admin</div>
                </div>
            </div>
            <a href="logout.php" class="sidebar-logout" title="Déconnexion">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </aside>
    
    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Breadcrumb -->
                <nav class="breadcrumb-nav">
                    <i class="<?php echo htmlspecialchars($page_icon); ?> text-gray-400"></i>
                    <span class="text-gray-500">/</span>
                    <span class="text-gray-900 font-semibold"><?php echo htmlspecialchars($page_heading); ?></span>
                </nav>
            </div>
            
            <div class="topbar-right">
                <!-- Search (optional, can be activated later) -->
                <!--
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher..." class="search-input">
                </div>
                -->
                
                <!-- Notifications -->
                <button class="topbar-icon-btn" id="notificationsBtn">
                    <i class="fas fa-bell"></i>
                    <span class="topbar-badge">3</span>
                </button>
                
                <!-- Profile Dropdown -->
                <div class="dropdown">
                    <button class="topbar-profile" id="profileDropdown">
                        <div class="topbar-profile-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <span class="topbar-profile-name"><?php echo htmlspecialchars(substr($_SESSION['superadmin_name'] ?? 'Admin', 0, 15)); ?></span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    
                    <div class="dropdown-menu" id="profileMenu">
                        <div class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>Mon Profil</span>
                        </div>
                        <div class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Paramètres</span>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Déconnexion</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <main class="page-content">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title"><?php echo htmlspecialchars($page_heading); ?></h1>
                    <?php if (!empty($page_subtitle)): ?>
                    <p class="page-subtitle"><?php echo htmlspecialchars($page_subtitle); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="content-area">
    
    <?php else: ?>
    <!-- Login Page Layout (no sidebar) -->
    <div class="login-layout">
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
