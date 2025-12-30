<?php
// Configuration de la page
$page_title = 'Magasins - GeekBoard SuperAdmin';
$page_heading = 'Magasins';
$page_subtitle = 'Gestion et suivi de vos boutiques';
$page_icon = 'fas fa-store';

// Inclure le header
require_once('includes/header.php');
require_once('../config/database.php');

// Récupérer les magasins
$pdo = getMainDBConnection();
$stmt = $pdo->query("SELECT * FROM shops ORDER BY created_at DESC");
$shops = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les statistiques
$total_shops = count($shops);
$active_shops = count(array_filter($shops, fn($s) => $s['active'] == 1));
$inactive_shops = $total_shops - $active_shops;
$trial_shops = count(array_filter($shops, fn($s) => $s['subscription_status'] == 'trial'));
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Shops -->
    <div class="stat-card animate-slideInUp">
        <div class="stat-card-icon primary">
            <i class="fas fa-store"></i>
        </div>
        <div class="stat-card-value"><?php echo $total_shops; ?></div>
        <div class="stat-card-label">Total Magasins</div>
    </div>
    
    <!-- Active Shops -->
    <div class="stat-card animate-slideInUp" style="animation-delay: 0.1s;">
        <div class="stat-card-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-card-value"><?php echo $active_shops; ?></div>
        <div class="stat-card-label">Actifs</div>
        <?php if ($active_shops > 0): ?>
        <div class="stat-card-change positive">
            <i class="fas fa-arrow-up"></i>
            <?php echo round(($active_shops / $total_shops) * 100); ?>%
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Inactive Shops -->
    <div class="stat-card animate-slideInUp" style="animation-delay: 0.2s;">
        <div class="stat-card-icon danger">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-card-value"><?php echo $inactive_shops; ?></div>
        <div class="stat-card-label">Inactifs</div>
    </div>
    
    <!-- Trial Shops -->
    <div class="stat-card animate-slideInUp" style="animation-delay: 0.3s;">
        <div class="stat-card-icon warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-card-value"><?php echo $trial_shops; ?></div>
        <div class="stat-card-label">En Essai</div>
    </div>
</div>

<!-- Actions Bar -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Liste des Magasins</h2>
        <p class="text-sm text-gray-600 mt-1">Gérez vos boutiques en ligne</p>
    </div>
    
    <div class="flex gap-3">
        <div class="search-input">
            <i class="fas fa-search search-icon"></i>
            <input type="text" 
                   id="searchShops" 
                   placeholder="Rechercher un magasin..."
                   class="form-control"
                   data-search-target=".shop-card"
                   style="padding-left: 2.5rem;">
        </div>
        
        <a href="create_shop.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Nouveau Magasin
        </a>
    </div>
</div>

<!-- Shops Grid -->
<?php if (empty($shops)): ?>
<div class="empty-state">
    <div class="empty-state-icon">
        <i class="fas fa-store"></i>
    </div>
    <h3 class="empty-state-title">Aucun magasin</h3>
    <p class="empty-state-description">
        Commencez par créer votre premier magasin pour démarrer.
    </p>
    <a href="create_shop.php" class="btn btn-primary mt-4">
        <i class="fas fa-plus me-2"></i>
        Créer un Magasin
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($shops as $index => $shop): ?>
    <div class="card shop-card animate-slideInUp" 
         style="animation-delay: <?php echo ($index * 0.05); ?>s;"
         data-shop-name="<?php echo strtolower($shop['name']); ?>"
         data-shop-subdomain="<?php echo strtolower($shop['subdomain']); ?>">
        
        <!-- Card Header -->
        <div class="card-header">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <?php if (!empty($shop['logo'])): ?>
                    <img src="<?php echo htmlspecialchars($shop['logo']); ?>" 
                         alt="Logo" 
                         class="w-10 h-10 rounded-lg">
                    <?php else: ?>
                    <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                        <i class="fas fa-store text-primary-600"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <h3 class="font-semibold text-gray-900">
                            <?php echo htmlspecialchars($shop['name']); ?>
                        </h3>
                        <p class="text-xs text-gray-500">
                            <?php echo htmlspecialchars($shop['subdomain']); ?>.mdgeek.top
                        </p>
                    </div>
                </div>
                
                <!-- Status Badge -->
                <?php if ($shop['active']): ?>
                <span class="status-badge status-<?php echo $shop['subscription_status']; ?>">
                    <?php 
                    echo match($shop['subscription_status']) {
                        'trial' => 'Essai',
                        'active' => 'Actif',
                        'expired' => 'Expiré',
                        'cancelled' => 'Annulé',
                        default => 'Actif'
                    };
                    ?>
                </span>
                <?php else: ?>
                <span class="badge-gray">Inactif</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Card Body -->
        <div class="card-body">
            <?php if (!empty($shop['description'])): ?>
            <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars(substr($shop['description'], 0, 100)); ?><?php echo strlen($shop['description']) > 100 ? '...' : ''; ?></p>
            <?php endif; ?>
            
            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-3 text-xs">
                <?php if (!empty($shop['city'])): ?>
                <div class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-map-marker-alt text-gray-400"></i>
                    <span><?php echo htmlspecialchars($shop['city']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($shop['phone'])): ?>
                <div class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-phone text-gray-400"></i>
                    <span><?php echo htmlspecialchars($shop['phone']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-database text-gray-400"></i>
                    <span><?php echo htmlspecialchars($shop['db_name']); ?></span>
                </div>
                
                <div class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-calendar text-gray-400"></i>
                    <span><?php echo date('d/m/Y', strtotime($shop['created_at'])); ?></span>
                </div>
            </div>
            
            <!-- Trial Info -->
            <?php if ($shop['subscription_status'] == 'trial' && !empty($shop['trial_ends_at'])): ?>
            <div class="mt-4 p-3 bg-warning-50 rounded-lg">
                <div class="flex items-center gap-2 text-warning-700 text-xs">
                    <i class="fas fa-clock"></i>
                    <span>
                        Essai expire le <?php echo date('d/m/Y', strtotime($shop['trial_ends_at'])); ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Card Footer -->
        <div class="card-footer">
            <div class="flex gap-2">
                <a href="https://<?php echo htmlspecialchars($shop['subdomain']); ?>.mdgeek.top" 
                   target="_blank" 
                   class="btn btn-secondary btn-sm flex-1">
                    <i class="fas fa-external-link-alt"></i>
                    Visiter
                </a>
                
                <a href="subscriptions.php?shop_id=<?php echo $shop['id']; ?>" 
                   class="btn btn-ghost btn-sm">
                    <i class="fas fa-credit-card"></i>
                </a>
                
                <a href="phpmyadmin_connect.php?shop_id=<?php echo $shop['id']; ?>" 
                   class="btn btn-ghost btn-sm">
                    <i class="fas fa-database"></i>
                </a>
                
                <div class="dropdown">
                    <button class="btn btn-ghost btn-sm" onclick="this.parentElement.classList.toggle('show')">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="edit_shop.php?id=<?php echo $shop['id']; ?>" class="dropdown-item">
                            <i class="fas fa-edit"></i>
                            <span>Modifier</span>
                        </a>
                        <a href="database_manager.php?shop_id=<?php echo $shop['id']; ?>" class="dropdown-item">
                            <i class="fas fa-database"></i>
                            <span>Base de données</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="delete_shop.php?id=<?php echo $shop['id']; ?>" 
                           class="dropdown-item"
                           data-confirm="Êtes-vous sûr de vouloir supprimer ce magasin ?">
                            <i class="fas fa-trash text-danger-600"></i>
                            <span class="text-danger-600">Supprimer</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
// Search functionality
document.getElementById('searchShops')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.shop-card');
    
    cards.forEach(card => {
        const name = card.dataset.shopName || '';
        const subdomain = card.dataset.shopSubdomain || '';
        const matches = name.includes(searchTerm) || subdomain.includes(searchTerm);
        card.style.display = matches ? '' : 'none';
    });
});
</script>

<?php require_once('includes/footer.php'); ?>
