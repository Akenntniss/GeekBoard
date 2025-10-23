<?php
// Détection du mode sombre/clair
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true';
?>

<!-- NOUVEAU MENU MODAL FUTURISTE -->
<div class="modal fade" id="futuristicMainMenu" tabindex="-1" aria-labelledby="futuristicMainMenuLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content futuristic-menu-content">
            <!-- Header du menu -->
            <div class="futuristic-menu-header">
                <div class="menu-header-content">
                    <div class="menu-logo-section">
                        <img src="<?php echo $navbar_assets_path; ?>images/logo/logoservo.png" alt="GeekBoard" class="menu-logo">
                        <div class="menu-title-wrapper">
                            <h1 class="menu-title">GeekBoard</h1>
                            <span class="menu-subtitle">Navigation System</span>
                        </div>
                    </div>
                    <button type="button" class="futuristic-close-btn" data-bs-dismiss="modal" aria-label="Close">
                        <span class="close-icon"></span>
                    </button>
                </div>
                <div class="menu-divider"></div>
            </div>

            <!-- Corps du menu -->
            <div class="futuristic-menu-body">
                <div class="menu-grid-container">
                    
                    <!-- Section Gestion Principale -->
                    <div class="menu-section">
                        <h2 class="section-title">
                            <span class="title-icon"><i class="fas fa-th-large"></i></span>
                            Gestion Principale
                        </h2>
                        <div class="menu-items-grid">
                            <a href="index.php" class="menu-item <?php echo empty($_GET['page']) ? 'active' : ''; ?>" data-category="main">
                                <div class="item-icon">
                                    <i class="fas fa-home"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Accueil</span>
                                    <span class="item-description">Dashboard principal</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <a href="index.php?page=reparations" class="menu-item <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>" data-category="main">
                                <div class="item-icon">
                                    <i class="fas fa-tools"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Réparations</span>
                                    <span class="item-description">Gérer les réparations</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <a href="index.php?page=ajouter_reparation" class="menu-item <?php echo $currentPage == 'ajouter_reparation' ? 'active' : ''; ?>" data-category="main">
                                <div class="item-icon">
                                    <i class="fas fa-plus-circle"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Nouvelle Réparation</span>
                                    <span class="item-description">Créer une réparation</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <a href="index.php?page=commandes_pieces" class="menu-item <?php echo $currentPage == 'commandes_pieces' ? 'active' : ''; ?>" data-category="main">
                                <div class="item-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Commandes</span>
                                    <span class="item-description">Gérer les commandes</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <a href="index.php?page=taches" class="menu-item <?php echo $currentPage == 'taches' ? 'active' : ''; ?>" data-category="main">
                                <div class="item-icon">
                                    <i class="fas fa-tasks"></i>
                                    <div class="icon-glow"></div>
                                    <?php if ($tasks_count > 0): ?>
                                        <span class="notification-badge"><?php echo $tasks_count; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Tâches</span>
                                    <span class="item-description">Gérer les tâches</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <a href="index.php?page=rachat_appareils" class="menu-item <?php echo $currentPage == 'rachat_appareils' ? 'active' : ''; ?>" data-category="main">
                                <div class="item-icon">
                                    <i class="fas fa-exchange-alt"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Rachat</span>
                                    <span class="item-description">Rachat d'appareils</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <a href="index.php?page=base_connaissances" class="menu-item <?php echo $currentPage == 'base_connaissances' ? 'active' : ''; ?>" data-category="main">
                                <div class="item-icon">
                                    <i class="fas fa-book"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Base de connaissance</span>
                                    <span class="item-description">Documentation</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <a href="index.php?page=clients" class="menu-item <?php echo $currentPage == 'clients' ? 'active' : ''; ?>" data-category="main">
                                <div class="item-icon">
                                    <i class="fas fa-users"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Clients</span>
                                    <span class="item-description">Gestion clientèle</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>
                        </div>
                    </div>

                    <!-- Section Missions -->
                    <div class="menu-section">
                        <h2 class="section-title">
                            <span class="title-icon"><i class="fas fa-clipboard-check"></i></span>
                            Missions
                        </h2>
                        <div class="menu-items-grid">
                            <a href="index.php?page=mes_missions" class="menu-item" data-category="missions">
                                <div class="item-icon">
                                    <i class="fas fa-clipboard-check"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Mes missions</span>
                                    <span class="item-description">Missions assignées</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <a href="index.php?page=admin_missions" class="menu-item" data-category="missions">
                                <div class="item-icon">
                                    <i class="fas fa-tasks"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Admin missions</span>
                                    <span class="item-description">Gestion des missions</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Section Communication -->
                    <div class="menu-section">
                        <h2 class="section-title">
                            <span class="title-icon"><i class="fas fa-comments"></i></span>
                            Communication
                        </h2>
                        <div class="menu-items-grid">
                            <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                            <a href="index.php?page=campagne_sms" class="menu-item <?php echo $currentPage == 'campagne_sms' ? 'active' : ''; ?>" data-category="communication">
                                <div class="item-icon">
                                    <i class="fas fa-sms"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Campagne SMS</span>
                                    <span class="item-description">Campagnes marketing</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <a href="index.php?page=template_sms" class="menu-item <?php echo $currentPage == 'template_sms' ? 'active' : ''; ?>" data-category="communication">
                                <div class="item-icon">
                                    <i class="fas fa-comment-dots"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Template SMS</span>
                                    <span class="item-description">Modèles de messages</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>
                            <?php endif; ?>

                            <a href="index.php?page=sms_historique" class="menu-item <?php echo $currentPage == 'sms_historique' ? 'active' : ''; ?>" data-category="communication">
                                <div class="item-icon">
                                    <i class="fas fa-history"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Historique SMS</span>
                                    <span class="item-description">Historique des envois</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>
                        </div>
                    </div>

                    <!-- Section Administration -->
                    <div class="menu-section">
                        <h2 class="section-title">
                            <span class="title-icon"><i class="fas fa-cogs"></i></span>
                            Administration
                        </h2>
                        <div class="menu-items-grid">
                            <a href="index.php?page=employes" class="menu-item <?php echo $currentPage == 'employes' ? 'active' : ''; ?>" data-category="admin">
                                <div class="item-icon">
                                    <i class="fas fa-user-tie"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Employés</span>
                                    <span class="item-description">Gestion du personnel</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                            <a href="index.php?page=presence_gestion" class="menu-item <?php echo in_array($currentPage, ['presence_gestion', 'presence_ajouter', 'presence_calendrier', 'presence_export', 'presence_modifier']) ? 'active' : ''; ?>" data-category="admin">
                                <div class="item-icon">
                                    <i class="fas fa-user-clock"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Absences & Retards</span>
                                    <span class="item-description">Gestion des présences</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <a href="index.php?page=admin_timetracking" class="menu-item <?php echo (strpos($_SERVER['REQUEST_URI'], 'admin_timetracking') !== false) ? 'active' : ''; ?>" data-category="admin">
                                <div class="item-icon">
                                    <i class="fas fa-clock"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Pointage Admin</span>
                                    <span class="item-description">Administration du pointage</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <a href="index.php?page=reparation_logs" class="menu-item <?php echo $currentPage == 'reparation_logs' ? 'active' : ''; ?>" data-category="admin">
                                <div class="item-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Journaux de réparation</span>
                                    <span class="item-description">Logs système</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>
                            <?php endif; ?>

                            <a href="index.php?page=kpi_dashboard" class="menu-item <?php echo $currentPage == 'kpi_dashboard' ? 'active' : ''; ?>" data-category="admin">
                                <div class="item-icon">
                                    <i class="fas fa-chart-line"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">KPI Dashboard</span>
                                    <span class="item-description">Tableaux de bord</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <?php if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                            <a href="index.php?page=bug-reports" class="menu-item <?php echo $currentPage == 'bug-reports' ? 'active' : ''; ?>" data-category="admin">
                                <div class="item-icon">
                                    <i class="fas fa-bug"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Signalements bugs</span>
                                    <span class="item-description">Gestion des bugs</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>

                            <a href="index.php?page=parametre" class="menu-item <?php echo $currentPage == 'parametre' ? 'active' : ''; ?>" data-category="admin">
                                <div class="item-icon">
                                    <i class="fas fa-cog"></i>
                                    <div class="icon-glow"></div>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">Paramètres</span>
                                    <span class="item-description">Configuration système</span>
                                </div>
                                <div class="item-arrow"><i class="fas fa-chevron-right"></i></div>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Effet de particules/Background animé -->
                <div class="menu-background-effects">
                    <div class="particle" style="--i: 1;"></div>
                    <div class="particle" style="--i: 2;"></div>
                    <div class="particle" style="--i: 3;"></div>
                    <div class="particle" style="--i: 4;"></div>
                    <div class="particle" style="--i: 5;"></div>
                    <div class="particle" style="--i: 6;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== VARIABLES CSS ===== */
:root {
    --futuristic-bg-day: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    --futuristic-bg-night: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%);
    --corporate-primary: #2563eb;
    --corporate-secondary: #64748b;
    --neon-blue: #00d4ff;
    --neon-purple: #8b5cf6;
    --neon-pink: #f472b6;
    --glass-bg-day: rgba(255, 255, 255, 0.85);
    --glass-bg-night: rgba(15, 15, 26, 0.85);
    --text-primary-day: #1e293b;
    --text-primary-night: #f1f5f9;
    --text-secondary-day: #64748b;
    --text-secondary-night: #94a3b8;
}

/* ===== BASE MODAL STYLES ===== */
#futuristicMainMenu .modal-content {
    border: none;
    border-radius: 0;
    overflow: hidden;
    position: relative;
}

[data-bs-theme="light"] #futuristicMainMenu .futuristic-menu-content {
    background: var(--futuristic-bg-day);
    backdrop-filter: blur(20px);
    color: var(--text-primary-day);
}

[data-bs-theme="dark"] #futuristicMainMenu .futuristic-menu-content {
    background: var(--futuristic-bg-night);
    backdrop-filter: blur(20px);
    color: var(--text-primary-night);
    position: relative;
}

/* ===== HEADER STYLES ===== */
.futuristic-menu-header {
    padding: 2rem;
    position: relative;
    z-index: 10;
}

[data-bs-theme="light"] .futuristic-menu-header {
    background: rgba(255, 255, 255, 0.1);
    border-bottom: 1px solid rgba(37, 99, 235, 0.1);
}

[data-bs-theme="dark"] .futuristic-menu-header {
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid rgba(0, 212, 255, 0.2);
    box-shadow: 0 1px 0 rgba(0, 212, 255, 0.1);
}

.menu-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.menu-logo-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.menu-logo {
    height: 50px;
    border-radius: 12px;
    filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.1));
}

[data-bs-theme="dark"] .menu-logo {
    filter: drop-shadow(0 4px 12px rgba(0, 212, 255, 0.3));
}

.menu-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.02em;
}

[data-bs-theme="light"] .menu-title {
    background: linear-gradient(135deg, var(--corporate-primary), #1d4ed8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

[data-bs-theme="dark"] .menu-title {
    background: linear-gradient(135deg, var(--neon-blue), var(--neon-purple));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 0 0 30px rgba(0, 212, 255, 0.3);
}

.menu-subtitle {
    font-size: 0.9rem;
    opacity: 0.7;
    font-weight: 500;
    display: block;
    margin-top: 0.25rem;
}

/* ===== CLOSE BUTTON ===== */
.futuristic-close-btn {
    width: 50px;
    height: 50px;
    border: none;
    border-radius: 50%;
    position: relative;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: flex;
    align-items: center;
    justify-content: center;
}

[data-bs-theme="light"] .futuristic-close-btn {
    background: rgba(37, 99, 235, 0.1);
    backdrop-filter: blur(10px);
}

[data-bs-theme="dark"] .futuristic-close-btn {
    background: rgba(0, 212, 255, 0.1);
    backdrop-filter: blur(10px);
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.1);
}

.futuristic-close-btn:hover {
    transform: scale(1.1) rotate(90deg);
}

[data-bs-theme="light"] .futuristic-close-btn:hover {
    background: rgba(37, 99, 235, 0.2);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.2);
}

[data-bs-theme="dark"] .futuristic-close-btn:hover {
    background: rgba(0, 212, 255, 0.2);
    box-shadow: 0 0 40px rgba(0, 212, 255, 0.3);
}

.close-icon {
    position: relative;
    width: 20px;
    height: 20px;
}

.close-icon::before,
.close-icon::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 2px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(45deg);
    transition: all 0.3s ease;
}

.close-icon::after {
    transform: translate(-50%, -50%) rotate(-45deg);
}

[data-bs-theme="light"] .close-icon::before,
[data-bs-theme="light"] .close-icon::after {
    background: var(--corporate-primary);
}

[data-bs-theme="dark"] .close-icon::before,
[data-bs-theme="dark"] .close-icon::after {
    background: var(--neon-blue);
    box-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
}

/* ===== DIVIDER ===== */
.menu-divider {
    height: 1px;
    width: 100%;
    position: relative;
    overflow: hidden;
}

[data-bs-theme="light"] .menu-divider {
    background: linear-gradient(90deg, transparent, var(--corporate-primary), transparent);
}

[data-bs-theme="dark"] .menu-divider {
    background: linear-gradient(90deg, transparent, var(--neon-blue), transparent);
}

.menu-divider::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    animation: slideGlow 2s infinite;
}

[data-bs-theme="dark"] .menu-divider::after {
    background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.6), transparent);
}

@keyframes slideGlow {
    0% { left: -100%; }
    100% { left: 100%; }
}

/* ===== MENU BODY ===== */
.futuristic-menu-body {
    flex: 1;
    padding: 2rem;
    overflow-y: auto;
    position: relative;
    z-index: 10;
}

.menu-grid-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 3rem;
}

/* ===== SECTION STYLES ===== */
.menu-section {
    position: relative;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    position: relative;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    height: 2px;
    width: 60px;
    border-radius: 1px;
}

[data-bs-theme="light"] .section-title::after {
    background: var(--corporate-primary);
}

[data-bs-theme="dark"] .section-title::after {
    background: linear-gradient(90deg, var(--neon-blue), var(--neon-purple));
    box-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
}

.title-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

[data-bs-theme="light"] .title-icon {
    background: linear-gradient(135deg, var(--corporate-primary), #1d4ed8);
    color: white;
}

[data-bs-theme="dark"] .title-icon {
    background: linear-gradient(135deg, var(--neon-blue), var(--neon-purple));
    color: white;
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.2);
}

/* ===== MENU ITEMS GRID ===== */
.menu-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
    border: 1px solid transparent;
}

[data-bs-theme="light"] .menu-item {
    background: var(--glass-bg-day);
    color: var(--text-primary-day);
    border-color: rgba(37, 99, 235, 0.1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

[data-bs-theme="dark"] .menu-item {
    background: rgba(15, 15, 26, 0.6);
    color: var(--text-primary-night);
    border-color: rgba(0, 212, 255, 0.1);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.menu-item:hover {
    transform: translateY(-4px) scale(1.02);
    text-decoration: none;
}

[data-bs-theme="light"] .menu-item:hover {
    background: rgba(255, 255, 255, 0.95);
    border-color: var(--corporate-primary);
    box-shadow: 0 10px 40px rgba(37, 99, 235, 0.15);
    color: var(--text-primary-day);
}

[data-bs-theme="dark"] .menu-item:hover {
    background: rgba(15, 15, 26, 0.8);
    border-color: var(--neon-blue);
    box-shadow: 0 0 40px rgba(0, 212, 255, 0.2);
    color: var(--text-primary-night);
}

.menu-item.active {
    transform: translateY(-2px);
}

[data-bs-theme="light"] .menu-item.active {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(29, 78, 216, 0.05));
    border-color: var(--corporate-primary);
    box-shadow: 0 8px 30px rgba(37, 99, 235, 0.2);
}

[data-bs-theme="dark"] .menu-item.active {
    background: linear-gradient(135deg, rgba(0, 212, 255, 0.1), rgba(139, 92, 246, 0.05));
    border-color: var(--neon-blue);
    box-shadow: 0 0 30px rgba(0, 212, 255, 0.3);
}

/* ===== ITEM ICON ===== */
.item-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    position: relative;
    flex-shrink: 0;
}

[data-bs-theme="light"] .item-icon {
    background: linear-gradient(135deg, var(--corporate-primary), #1d4ed8);
    color: white;
}

[data-bs-theme="dark"] .item-icon {
    background: linear-gradient(135deg, rgba(0, 212, 255, 0.2), rgba(139, 92, 246, 0.2));
    color: var(--neon-blue);
    border: 1px solid rgba(0, 212, 255, 0.3);
}

.icon-glow {
    position: absolute;
    inset: 0;
    border-radius: 16px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

[data-bs-theme="dark"] .icon-glow {
    background: linear-gradient(135deg, var(--neon-blue), var(--neon-purple));
    filter: blur(10px);
}

[data-bs-theme="dark"] .menu-item:hover .icon-glow {
    opacity: 0.3;
}

/* ===== NOTIFICATION BADGE ===== */
.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ef4444;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    min-width: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

[data-bs-theme="dark"] .notification-badge {
    box-shadow: 0 0 15px rgba(239, 68, 68, 0.5);
}

/* ===== ITEM CONTENT ===== */
.item-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.item-title {
    font-size: 1.1rem;
    font-weight: 600;
    line-height: 1.3;
}

.item-description {
    font-size: 0.9rem;
    opacity: 0.7;
    line-height: 1.4;
}

/* ===== ITEM ARROW ===== */
.item-arrow {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.5;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.menu-item:hover .item-arrow {
    opacity: 1;
    transform: translateX(4px);
}

[data-bs-theme="dark"] .menu-item:hover .item-arrow {
    color: var(--neon-blue);
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
}

/* ===== QUICK ACTIONS ===== */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.quick-action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem;
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    backdrop-filter: blur(10px);
    border: 1px solid transparent;
}

[data-bs-theme="light"] .quick-action-item {
    background: var(--glass-bg-day);
    color: var(--text-primary-day);
    border-color: rgba(37, 99, 235, 0.1);
}

[data-bs-theme="dark"] .quick-action-item {
    background: rgba(15, 15, 26, 0.6);
    color: var(--text-primary-night);
    border-color: rgba(0, 212, 255, 0.1);
}

.quick-action-item:hover {
    transform: translateY(-4px) scale(1.05);
    text-decoration: none;
}

[data-bs-theme="light"] .quick-action-item:hover {
    background: rgba(255, 255, 255, 0.95);
    border-color: var(--corporate-primary);
    box-shadow: 0 10px 30px rgba(37, 99, 235, 0.15);
    color: var(--text-primary-day);
}

[data-bs-theme="dark"] .quick-action-item:hover {
    background: rgba(15, 15, 26, 0.8);
    border-color: var(--neon-blue);
    box-shadow: 0 0 30px rgba(0, 212, 255, 0.2);
    color: var(--text-primary-night);
}

.quick-action-item.logout {
    border-color: rgba(239, 68, 68, 0.2);
}

[data-bs-theme="light"] .quick-action-item.logout:hover {
    border-color: #ef4444;
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.15);
}

[data-bs-theme="dark"] .quick-action-item.logout:hover {
    border-color: #ef4444;
    box-shadow: 0 0 30px rgba(239, 68, 68, 0.3);
}

.quick-action-item .item-icon {
    width: 50px;
    height: 50px;
    font-size: 1.3rem;
}

.quick-action-item .item-title {
    font-size: 1rem;
    font-weight: 600;
    text-align: center;
}

/* ===== BACKGROUND EFFECTS (Mode Nuit uniquement) ===== */
[data-bs-theme="dark"] .menu-background-effects {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 1;
}

[data-bs-theme="light"] .menu-background-effects {
    display: none;
}

.particle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: var(--neon-blue);
    border-radius: 50%;
    opacity: 0.6;
    animation: float 6s infinite ease-in-out;
    animation-delay: calc(var(--i) * -1s);
    box-shadow: 0 0 10px var(--neon-blue);
}

.particle:nth-child(odd) {
    background: var(--neon-purple);
    box-shadow: 0 0 10px var(--neon-purple);
}

.particle:nth-child(1) { top: 20%; left: 10%; }
.particle:nth-child(2) { top: 60%; left: 80%; }
.particle:nth-child(3) { top: 40%; left: 20%; }
.particle:nth-child(4) { top: 80%; left: 60%; }
.particle:nth-child(5) { top: 15%; left: 70%; }
.particle:nth-child(6) { top: 70%; left: 30%; }

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.6; }
    50% { transform: translateY(-20px) rotate(180deg); opacity: 1; }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .futuristic-menu-header {
        padding: 1.5rem;
    }
    
    .futuristic-menu-body {
        padding: 1.5rem;
    }
    
    .menu-grid-container {
        gap: 2rem;
    }
    
    .menu-items-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .menu-item {
        padding: 1.25rem;
    }
    
    .item-icon {
        width: 50px;
        height: 50px;
        font-size: 1.3rem;
    }
    
    .menu-title {
        font-size: 1.75rem;
    }
    
    .section-title {
        font-size: 1.3rem;
        margin-bottom: 1.5rem;
    }
    
    .quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .futuristic-menu-header {
        padding: 1rem;
    }
    
    .futuristic-menu-body {
        padding: 1rem;
    }
    
    .menu-logo-section {
        gap: 0.75rem;
    }
    
    .menu-logo {
        height: 40px;
    }
    
    .menu-title {
        font-size: 1.5rem;
    }
    
    .menu-subtitle {
        font-size: 0.8rem;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
}

/* ===== ANIMATIONS D'ENTRÉE ===== */
#futuristicMainMenu.show {
    animation: modalFadeIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.menu-section {
    animation: slideInUp 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    animation-fill-mode: both;
}

.menu-section:nth-child(1) { animation-delay: 0.1s; }
.menu-section:nth-child(2) { animation-delay: 0.2s; }
.menu-section:nth-child(3) { animation-delay: 0.3s; }
.menu-section:nth-child(4) { animation-delay: 0.4s; }
.menu-section:nth-child(5) { animation-delay: 0.5s; }

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('futuristicMainMenu');
    
    // Animation d'entrée des éléments
    modal.addEventListener('shown.bs.modal', function() {
        // Animation des particules
        const particles = document.querySelectorAll('.particle');
        particles.forEach((particle, index) => {
            particle.style.animationDelay = `${index * 0.3}s`;
        });
        
        // Animation des items de menu avec délai
        const menuItems = document.querySelectorAll('.menu-item, .quick-action-item');
        menuItems.forEach((item, index) => {
            item.style.animationDelay = `${0.1 + (index * 0.05)}s`;
            item.style.animation = 'slideInUp 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) both';
        });
    });
    
    // Fermeture avec animation
    modal.addEventListener('hide.bs.modal', function() {
        modal.style.animation = 'modalFadeOut 0.3s ease-in-out';
    });
    
    // Effet de parallaxe léger sur les particules
    if (window.matchMedia('(prefers-reduced-motion: no-preference)').matches) {
        document.addEventListener('mousemove', function(e) {
            const particles = document.querySelectorAll('.particle');
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            particles.forEach((particle, index) => {
                const speed = (index + 1) * 0.5;
                const x = (mouseX - 0.5) * speed;
                const y = (mouseY - 0.5) * speed;
                particle.style.transform = `translate(${x}px, ${y}px)`;
            });
        });
    }
    
    // Gestion des clicks sur les items
    document.querySelectorAll('.menu-item, .quick-action-item').forEach(item => {
        item.addEventListener('click', function() {
            // Ajouter un effet de ripple
            const ripple = document.createElement('div');
            ripple.classList.add('ripple-effect');
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
});

// Animation de fermeture
const modalFadeOutKeyframes = `
    @keyframes modalFadeOut {
        from {
            opacity: 1;
            transform: scale(1);
        }
        to {
            opacity: 0;
            transform: scale(0.8);
        }
    }
`;

// Effet ripple
const rippleStyles = `
    .ripple-effect {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        animation: ripple 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;

// Ajouter les styles dynamiques
const styleSheet = document.createElement('style');
styleSheet.textContent = modalFadeOutKeyframes + rippleStyles;
document.head.appendChild(styleSheet);
</script>
