<?php
require_once __DIR__ . '/../config/database.php';
// Inclure les fonctions de notification
$shop_pdo = getShopDBConnection();
require_once 'includes/notification_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$user_id = $_SESSION['user_id'];

// Traitement des actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'mark_read':
            if (isset($_GET['id'])) {
                mark_notification_as_read($_GET['id'], $user_id);
                set_message('success', 'Notification marquée comme lue');
            }
            header('Location: index.php?page=notifications');
            exit;
            break;
            
        case 'mark_all_read':
            $count = mark_all_notifications_as_read($user_id);
            set_message('success', $count . ' notification(s) marquée(s) comme lue(s)');
            header('Location: index.php?page=notifications');
            exit;
            break;

        case 'clean_old':
            // Supprimer les notifications de plus de 30 jours
            $stmt = $shop_pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute([$user_id]);
            set_message('success', 'Anciennes notifications nettoyées');
            header('Location: index.php?page=notifications');
            exit;
            break;
    }
}

// Pagination
$page_num = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 15;
$offset = ($page_num - 1) * $limit;

// Filtre de statut
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
if (!in_array($filter, ['all', 'new', 'read'])) {
    $filter = 'all';
}

// Récupérer les notifications
$notifications = get_user_notifications($user_id, $filter, $limit, $offset);

// Récupérer le nombre total pour la pagination
$total_notifications = 0;
switch ($filter) {
    case 'new':
        $total_notifications = count_unread_notifications($user_id);
        break;
    case 'read':
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'read'");
        $stmt->execute([$user_id]);
        $total_notifications = $stmt->fetchColumn();
        break;
    default:
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total_notifications = $stmt->fetchColumn();
}

$total_pages = ceil($total_notifications / $limit);
$unread_count = count_unread_notifications($user_id);
$stats = get_notification_stats($user_id, 7);
?>

<style>
/* Modern Notifications Page - CSS Variable and Base Styles */
:root {
    --notif-primary: #4361ee;
    --notif-secondary: #00d4ff;
    --notif-accent: #e11d48;
    --notif-bg-glass: rgba(255, 255, 255, 0.9); /* Increased opacity for better visibility */
    --notif-bg-glass-night: rgba(15, 23, 42, 0.7);
    --notif-border: rgba(0, 0, 0, 0.1); /* Darker border for day mode */
    --notif-border-night: rgba(255, 255, 255, 0.1);
    --notif-text-primary: #0f172a; /* Slate 900 - Very Dark */
    --notif-text-secondary: #334155; /* Slate 700 - Dark */
    --notif-text-tertiary: #475569; /* Slate 600 - Medium Dark */
}

.modern-dashboard {
    position: relative;
    z-index: 10;
    min-height: 100vh;
    padding: 2rem 1rem;
    background: transparent;
}

/* Glassmorphism Cards */
.modern-card {
    background: var(--notif-bg-glass) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border: 1px solid var(--notif-border) !important;
    border-radius: 20px !important;
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.1) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    overflow: hidden !important;
    margin-bottom: 2rem !important;
}

body.night-mode .modern-card {
    background: var(--notif-bg-glass-night) !important;
    border: 1px solid var(--notif-border-night) !important;
    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3) !important;
}

.page-header {
    margin-bottom: 2.5rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, var(--notif-primary), var(--notif-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-item {
    padding: 1.5rem;
    text-align: center;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 0.5rem;
    display: block;
    color: var(--notif-text-primary);
    background: linear-gradient(135deg, var(--notif-primary), var(--notif-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 2px 4px rgba(67, 97, 238, 0.2));
}

.stat-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--notif-text-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
}

body.night-mode .stat-label {
    color: var(--night-text-light);
}

/* Filters */
.nav-pills .nav-link {
    border-radius: 12px;
    padding: 0.6rem 1.25rem;
    font-weight: 700;
    color: var(--notif-text-secondary);
    background: rgba(0, 0, 0, 0.05); /* Slightly darker bg for better contrast */
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    margin-right: 0.5rem;
}

body.night-mode .nav-pills .nav-link:not(.active) {
    color: rgba(255, 255, 255, 0.8);
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

body.night-mode .nav-pills .nav-link:not(.active):hover {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
}

.nav-pills .nav-link.active {
    background: var(--notif-primary) !important;
    color: white !important;
    box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
}

/* Notifications List */
.notif-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.notif-card {
    display: flex;
    padding: 1.25rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08); /* Darker border */
    transition: all 0.3s ease;
    position: relative;
    text-decoration: none !important;
    color: inherit !important;
}

body.night-mode .notif-card {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.notif-card:last-child {
    border-bottom: none;
}

.notif-card:hover {
    background: rgba(0, 0, 0, 0.04);
    transform: translateX(5px);
}

body.night-mode .notif-card:hover {
    background: rgba(255, 255, 255, 0.02);
}

.notif-card.unread {
    background: rgba(67, 97, 238, 0.06);
}

body.night-mode .notif-card.unread {
    background: rgba(67, 97, 238, 0.15);
}

.notif-card.unread::before {
    content: '';
    position: absolute;
    left: 0;
    top: 15%;
    bottom: 15%;
    width: 4px;
    background: var(--notif-primary);
    border-radius: 0 4px 4px 0;
}

.notif-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-right: 1.25rem;
    flex-shrink: 0;
}

.notif-info {
    flex-grow: 1;
}

.notif-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.4rem;
}

.notif-time {
    font-size: 0.75rem;
    color: var(--notif-text-tertiary);
    font-weight: 600;
}

body.night-mode .notif-time {
    color: var(--night-text-light);
}

.notif-msg {
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 0.75rem;
    color: var(--notif-text-primary);
    font-weight: 500;
}

body.night-mode .notif-msg {
    color: var(--night-text);
}

.notif-actions {
    display: flex;
    gap: 0.75rem;
}

/* Empty State */
.notif-empty {
    padding: 5rem 2rem;
    text-align: center;
}

.empty-icon {
    font-size: 4rem;
    color: var(--notif-text-tertiary);
    opacity: 0.5;
    margin-bottom: 1.5rem;
}

body.night-mode .empty-icon {
    color: rgba(255, 255, 255, 0.1);
    opacity: 1;
}

/* Stats Table Modernized */
.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 0.5rem;
}

.modern-table th {
    padding: 1rem;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 800;
    color: var(--notif-text-secondary); /* Darker header */
    border: none;
}

body.night-mode .modern-table th {
    color: var(--night-text-light);
}

.modern-table td {
    padding: 1rem;
    background: rgba(0, 0, 0, 0.02);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    vertical-align: middle;
    color: var(--notif-text-primary); /* Darker cell text */
    font-weight: 600;
}

body.night-mode .modern-table td {
    background: rgba(255, 255, 255, 0.02);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--night-text);
}

.modern-table tr td:first-child { border-radius: 12px 0 0 12px; }
.modern-table tr td:last-child { border-radius: 0 12px 12px 0; }

.modern-table tr:hover td {
    background: rgba(0, 0, 0, 0.04);
}

body.night-mode .modern-table tr:hover td {
    background: rgba(255, 255, 255, 0.04);
}

/* Pagination Modern */
.modern-pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: var(--notif-bg-glass);
    border: 1px solid var(--notif-border);
    color: var(--notif-text-primary);
    text-decoration: none;
    font-weight: 700;
    transition: all 0.3s ease;
}

body.night-mode .page-btn {
    color: var(--night-text);
    background: var(--notif-bg-glass-night);
    border: 1px solid var(--notif-border-night);
}

.page-btn.active {
    background: var(--notif-primary);
    color: white;
    border-color: var(--notif-primary);
}

.page-btn:hover:not(.active) {
    background: rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .page-title { font-size: 1.5rem; }
}

/* Night Mode Overrides for Buttons and Links */
body.night-mode .btn-light {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: #fff !important;
}

body.night-mode .btn-light:hover {
    background: rgba(255, 255, 255, 0.2) !important;
}

body.night-mode .btn-outline-primary {
    color: #4cc9f0 !important;
    border-color: #4cc9f0 !important;
}

body.night-mode .btn-outline-primary:hover {
    background-color: #4cc9f0 !important;
    color: #0f172a !important;
}

body.night-mode .btn-outline-danger {
    color: #ff4d6d !important;
    border-color: #ff4d6d !important;
}

body.night-mode .btn-outline-danger:hover {
    background-color: #ff4d6d !important;
    color: #fff !important;
}

body.night-mode .page-title {
    background: linear-gradient(135deg, #4cc9f0, #00d4ff);
    -webkit-background-clip: text;
}
</style>

<div class="modern-dashboard fade-in">
    <div class="container">
        
        <!-- Header Section -->
        <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="page-title">Mes Notifications</h1>
                <p class="text-muted mb-0">Restez informé de l'activité de votre compte</p>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php?page=notification_preferences" class="btn modern-card py-2 px-3 border-0 m-0 d-flex align-items-center gap-2">
                    <i class="fas fa-cog"></i> <span>Réglages</span>
                </a>
                <?php if ($unread_count > 0): ?>
                <a href="index.php?page=notifications&action=mark_all_read" class="btn btn-primary py-2 px-3 rounded-4 d-flex align-items-center gap-2">
                    <i class="fas fa-check-double"></i> <span>Tout lire</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="modern-card stat-item">
                <span class="stat-value text-primary"><?php echo $total_notifications; ?></span>
                <span class="stat-label">Total</span>
            </div>
            <div class="modern-card stat-item">
                <span class="stat-value text-danger"><?php echo $unread_count; ?></span>
                <span class="stat-label">Non lues</span>
            </div>
            <div class="modern-card stat-item">
                <span class="stat-value text-success"><?php echo ($total_notifications - $unread_count); ?></span>
                <span class="stat-label">Lues</span>
            </div>
        </div>

        <div class="row">
            <!-- Notifications List -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="nav nav-pills">
                        <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="index.php?page=notifications&filter=all">Toutes</a>
                        <a class="nav-link <?php echo $filter === 'new' ? 'active' : ''; ?>" href="index.php?page=notifications&filter=new">Non lues</a>
                        <a class="nav-link <?php echo $filter === 'read' ? 'active' : ''; ?>" href="index.php?page=notifications&filter=read">Lues</a>
                    </div>
                </div>

                <div class="modern-card">
                    <?php if (empty($notifications)): ?>
                        <div class="notif-empty">
                            <div class="empty-icon"><i class="fas fa-bell-slash"></i></div>
                            <h3>Rien de nouveau ici</h3>
                            <p class="text-muted">Vous n'avez aucune notification pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="notif-list">
                            <?php foreach ($notifications as $notification): 
                                $icon = $notification['icon'] ?? 'fas fa-bell';
                                $color = $notification['color'] ?? '#4361ee';
                                $is_new = $notification['status'] === 'new';
                                $time_ago = time_elapsed_string($notification['created_at']);
                            ?>
                                <div class="notif-card <?php echo $is_new ? 'unread' : ''; ?>">
                                    <div class="notif-icon-box" style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>;">
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="notif-info">
                                        <div class="notif-meta">
                                            <span class="badge rounded-pill <?php echo $notification['is_important'] ? 'bg-danger' : 'bg-primary'; ?> opacity-75" style="font-size: 0.65rem;">
                                                <?php echo $notification['importance'] ?? 'Notification'; ?>
                                            </span>
                                            <span class="notif-time"><?php echo $time_ago; ?></span>
                                        </div>
                                        <div class="notif-msg"><?php echo htmlspecialchars($notification['message']); ?></div>
                                        <div class="notif-actions">
                                            <?php if ($notification['action_url']): ?>
                                                <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" class="btn btn-sm btn-light rounded-pill px-3">Voir les détails</a>
                                            <?php endif; ?>
                                            <?php if ($is_new): ?>
                                                <a href="index.php?page=notifications&action=mark_read&id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-primary border-0 rounded-pill px-3">Marquer comme lu</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <div class="modern-pagination pb-4">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="index.php?page=notifications&filter=<?php echo $filter; ?>&p=<?php echo $i; ?>" class="page-btn <?php echo $i === $page_num ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats & Admin Panel -->
            <div class="col-lg-4">
                <div class="modern-card p-4">
                    <h5 class="mb-4 d-flex align-items-center gap-2">
                        <i class="fas fa-chart-pie text-primary"></i> 
                        <span>Activité (7j)</span>
                    </h5>
                    <?php if (empty($stats)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle text-muted mb-2"></i>
                            <p class="text-muted small">Aucune donnée disponible</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Événement</th>
                                        <th class="text-center">#</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($stats, 0, 8) as $stat): 
                                        $type_name = str_replace(['reparation_', 'task_', 'system_'], '', $stat['notification_type']);
                                        $type_name = ucfirst(str_replace('_', ' ', $type_name));
                                    ?>
                                        <tr>
                                            <td><span class="small font-weight-bold"><?php echo htmlspecialchars($type_name); ?></span></td>
                                            <td class="text-center"><span class="badge bg-light text-dark rounded-pill"><?php echo $stat['total']; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <div class="modern-card p-4">
                        <h5 class="mb-4 d-flex align-items-center gap-2">
                            <i class="fas fa-user-shield text-danger"></i> 
                            <span>Admin Tools</span>
                        </h5>
                        <div class="d-grid gap-2">
                            <a href="index.php?page=notifications&action=generate_test&type=all" class="btn btn-outline-primary btn-sm rounded-3">Générer Test Notifications</a>
                            <a href="index.php?page=notifications&action=clean_old" class="btn btn-outline-danger btn-sm rounded-3">Nettoyer (+30 jours)</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-update unread count if badge exists in navbar
document.addEventListener('DOMContentLoaded', function() {
    const navbarBadge = document.querySelector('.navbar-badge');
    if (navbarBadge) {
        navbarBadge.textContent = '<?php echo $unread_count; ?>';
        navbarBadge.style.display = <?php echo $unread_count; ?> > 0 ? 'inline-flex' : 'none';
    }
});
</script>