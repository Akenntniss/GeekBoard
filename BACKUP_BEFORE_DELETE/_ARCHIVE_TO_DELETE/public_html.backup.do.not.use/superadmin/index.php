<?php
// Page d'accueil du super administrateur
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    // Rediriger vers la page de connexion si non connecté
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

// Récupérer la liste des magasins
$pdo = getMainDBConnection();
$shops = $pdo->query("SELECT * FROM shops ORDER BY created_at DESC")->fetchAll();

// Récupérer les infos du super administrateur connecté
$stmt = $pdo->prepare("SELECT * FROM superadmins WHERE id = ?");
$stmt->execute([$_SESSION['superadmin_id']]);
$superadmin = $stmt->fetch();

// Statistiques rapides
$total_shops = count($shops);
$active_shops = count(array_filter($shops, function($shop) { return $shop['active']; }));
$inactive_shops = $total_shops - $active_shops;

// Message de succès ou d'erreur
$message = '';
$message_type = 'success';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['message_type'])) {
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message_type']);
}
// Configuration de la page
$page_title = 'GeekBoard SuperAdmin - Dashboard';
$page_heading = 'Centre de Contrôle GeekBoard';
$page_subtitle = 'Gestion centralisée de vos boutiques';
$page_icon = 'fas fa-tachometer-alt';

include __DIR__ . '/includes/header.php';
?>
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php if ($message_type === 'success'): ?>
                        <i class="fas fa-check-circle me-2"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle me-2"></i>
                    <?php endif; ?>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="stats-row">
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="stat-number"><?php echo $total_shops; ?></div>
                            <div class="stat-label">Total Magasins</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number"><?php echo $active_shops; ?></div>
                            <div class="stat-label">Magasins Actifs</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-pause-circle"></i>
                            </div>
                            <div class="stat-number"><?php echo $inactive_shops; ?></div>
                            <div class="stat-label">Magasins Inactifs</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <a href="create_shop.php" class="btn-action">
                    <i class="fas fa-plus-circle"></i>Nouveau magasin
                </a>
                <a href="subscriptions.php" class="btn-action">
                    <i class="fas fa-credit-card"></i>Abonnements
                </a>
                <button type="button" class="btn-action" data-bs-toggle="modal" data-bs-target="#phpmyadminModal">
                    <i class="fas fa-database"></i>PhpMyAdmin
                </button>
                <a href="database_manager.php" class="btn-action btn-secondary">
                    <i class="fas fa-database"></i>Base de données
                </a>
                <a href="configure_domains.php" class="btn-action btn-secondary">
                    <i class="fas fa-globe"></i>Configuration domaines
                </a>
            </div>

            <div class="shops-section">
                <h2>Mes Magasins</h2>
                
                <?php if (count($shops) > 0): ?>
                    <!-- Barre de recherche -->
                    <div class="search-section">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="shop-search" class="search-input" placeholder="Rechercher un magasin par nom ou sous-domaine...">
                        </div>
                    </div>
                    <div class="shop-grid">
                        <?php foreach ($shops as $shop): ?>
                            <div class="shop-card <?php echo $shop['active'] ? '' : 'inactive'; ?>">
                                <div class="shop-status <?php echo $shop['active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $shop['active'] ? 'Actif' : 'Inactif'; ?>
                                </div>
                                
                                <div class="shop-header">
                                    <div class="shop-logo">
                                        <?php if (!empty($shop['logo'])): ?>
                                            <img src="<?php echo htmlspecialchars('../uploads/logos/' . $shop['logo']); ?>" 
                                                 style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;" alt="Logo">
                                        <?php else: ?>
                                            <i class="fas fa-store"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="shop-info">
                                        <h3><?php echo htmlspecialchars($shop['name']); ?></h3>
                                        <?php if (!empty($shop['subdomain'])): ?>
                                            <div class="subdomain"><?php echo htmlspecialchars($shop['subdomain']); ?>.servo.tools</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($shop['description'])): ?>
                                    <p style="color: #666; margin-bottom: 15px;">
                                        <?php echo htmlspecialchars(substr($shop['description'], 0, 100) . (strlen($shop['description']) > 100 ? '...' : '')); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($shop['city']) || !empty($shop['phone'])): ?>
                                    <div style="margin-bottom: 20px; font-size: 0.9rem; color: #666;">
                                        <?php if (!empty($shop['city'])): ?>
                                            <div><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($shop['city']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($shop['phone'])): ?>
                                            <div><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($shop['phone']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="shop-actions">
                                    <a href="edit_shop.php?id=<?php echo $shop['id']; ?>" class="btn-shop btn-shop-primary">
                                        <i class="fas fa-edit me-1"></i>Modifier
                                    </a>
                                    <a href="view_shop.php?id=<?php echo $shop['id']; ?>" class="btn-shop btn-shop-outline">
                                        <i class="fas fa-eye me-1"></i>Détails
                                    </a>
                                    <?php if (!empty($shop['subdomain'])): ?>
                                        <a href="https://<?php echo htmlspecialchars($shop['subdomain']); ?>.servo.tools" target="_blank" class="btn-shop btn-shop-outline">
                                            <i class="fas fa-external-link-alt me-1"></i>Visiter
                                        </a>
                                    <?php endif; ?>
                                    <a href="delete_shop.php?id=<?php echo $shop['id']; ?>" class="btn-shop btn-shop-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer le magasin \"<?php echo htmlspecialchars($shop['name']); ?>\" ? Cette action est irréversible.');">
                                        <i class="fas fa-trash-alt me-1"></i>Supprimer
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Message aucun résultat -->
                    <div id="no-results" class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>Aucun magasin trouvé</h3>
                        <p>Aucun magasin ne correspond à votre recherche.</p>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-store"></i>
                        <h3>Aucun magasin créé</h3>
                        <p>Commencez par créer votre premier magasin pour débuter avec GeekBoard.</p>
                        <a href="create_shop.php" class="btn-action" style="margin-top: 20px;">
                            <i class="fas fa-plus-circle"></i>Créer mon premier magasin
                        </a>
                    </div>
                <?php endif; ?>
    
    <!-- Modal PhpMyAdmin: Choisir un magasin -->
    <div class="modal fade" id="phpmyadminModal" tabindex="-1" aria-labelledby="phpmyadminModalLabel" aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="phpmyadminModalLabel"><i class="fas fa-database me-2"></i>Ouvrir PhpMyAdmin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="phpmyadminForm" action="phpmyadmin_connect.php" method="get" target="_blank" style="margin: 0;">
                        <div class="mb-3">
                            <label for="phpmyadminShopSelect" class="form-label">Sélectionnez un magasin</label>
                            <select id="phpmyadminShopSelect" name="shop_id" class="form-select" required>
                                <option value="">-- Choisir un magasin --</option>
<?php foreach ($shops as $shop): ?>
<?php if (!empty($shop['active'])): ?>
                                <option value="<?php echo (int)$shop['id']; ?>"><?php echo htmlspecialchars($shop['name']); ?><?php echo !empty($shop['subdomain']) ? ' — ' . htmlspecialchars($shop['subdomain']) . '.servo.tools' : ''; ?></option>
<?php endif; ?>
<?php endforeach; ?>
                            </select>
                        </div>
                        <div class="text-muted" style="font-size: 0.9rem;">Cette action ouvrira une page intermédiaire sécurisée avec les identifiants du magasin choisi et un bouton vers PhpMyAdmin.</div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="openPhpMyAdminBtn" form="phpmyadminForm"><i class="fas fa-external-link-alt me-2"></i>Ouvrir</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Animation d'entrée
        document.addEventListener('DOMContentLoaded', function() {
            // handled by global animation as well; keep page-specific animations for cards
            // Animation des cartes de magasins
            const shopCards = document.querySelectorAll('.shop-card');
            shopCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 200 + (index * 100));
            });
            
            // Fonctionnalité de recherche des magasins
            const searchInput = document.getElementById('shop-search');
            const shopCards = document.querySelectorAll('.shop-card');
            const noResults = document.getElementById('no-results');
            
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase().trim();
                    let visibleCards = 0;
                    
                    shopCards.forEach(function(card) {
                        // Récupérer le nom du magasin et le sous-domaine
                        const shopName = card.querySelector('.shop-info h3')?.textContent.toLowerCase() || '';
                        const shopSubdomain = card.querySelector('.shop-info .subdomain')?.textContent.toLowerCase() || '';
                        
                        // Vérifier si le terme de recherche correspond
                        if (searchTerm === '' || 
                            shopName.includes(searchTerm) || 
                            shopSubdomain.includes(searchTerm)) {
                            card.classList.remove('hidden');
                            visibleCards++;
                        } else {
                            card.classList.add('hidden');
                        }
                    });
                    
                    // Afficher le message "aucun résultat" si nécessaire
                    if (visibleCards === 0 && searchTerm !== '') {
                        noResults.style.display = 'block';
                    } else {
                        noResults.style.display = 'none';
                    }
                });
            }

            // Fermer le modal après soumission du formulaire PhpMyAdmin
            const formPhpMyAdmin = document.getElementById('phpmyadminForm');
            if (formPhpMyAdmin) {
                formPhpMyAdmin.addEventListener('submit', function() {
                    // Fermer le modal après un court délai
                    setTimeout(function() {
                        const modalEl = document.getElementById('phpmyadminModal');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            modal.hide();
                        }
                    }, 100);
                });
            }
        });
    </script>
<?php include __DIR__ . '/includes/footer.php'; ?>