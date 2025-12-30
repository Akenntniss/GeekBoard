<?php
/**
 * AJAX Endpoint: Load More Repairs
 * Returns HTML for additional repairs (infinite scroll)
 */

// Session et sécurité
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Include dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/status_functions.php';
require_once __DIR__ . '/../classes/RepairController.php';

try {
    // Get database connection
    $pdo = getShopDBConnection();
    
    // Get filter parameters (same as main page)
    $statut_ids = isset($_GET['statut_ids']) ? cleanInput($_GET['statut_ids']) : '1,2,3,4,5,19,20';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 2; // Page 2+ for infinite scroll
    $per_page = 50;
    
    // Use RepairController to get repairs (reuse logic)
    $controller = new RepairController($pdo, $_SESSION['user_id'], $_SESSION['user_role']);
    
    // Build filter params
    $statut = isset($_GET['statut']) ? cleanInput($_GET['statut']) : '';
    $type_appareil = isset($_GET['type_appareil']) ? cleanInput($_GET['type_appareil']) : '';
    $date_debut = isset($_GET['date_debut']) ? cleanInput($_GET['date_debut']) : '';
    $date_fin = isset($_GET['date_fin']) ? cleanInput($_GET['date_fin']) : '';
    $search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
    
    // Get repairs using reflection to call private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getRepairs');
    $method->setAccessible(true);
    $reparations = $method->invoke($controller, $statut, $statut_ids, $type_appareil, $date_debut, $date_fin, $search, $page, $per_page);
    
    // Build HTML response
    $html = '';
    
    foreach ($reparations as $reparation) {
        ob_start();
        ?>
        <div class="modern-card draggable-card" data-id="<?php echo $reparation['id']; ?>" data-repair-id="<?php echo $reparation['id']; ?>" data-status="<?php echo $reparation['statut']; ?>" draggable="true">
            <div class="card-header">
                <div class="status-indicator">
                    <?php echo get_enum_status_badge($reparation['statut'], $reparation['id']); ?>
                </div>
                <div class="repair-id">
                    <span>ID: <?php echo $reparation['id']; ?></span>
                </div>
            </div>
            
            <div class="card-content">
                <div class="client-info">
                    <div class="client-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="client-details">
                        <div class="client-name"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
                        <?php if (!empty($reparation['client_telephone'])): ?>
                            <div class="client-contact">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($reparation['client_telephone']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="device-info">
                    <div class="device-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="device-details">
                        <div class="device-model"><?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['modele']); ?></div>
                        <div class="device-problem"><?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 80)); ?><?php echo strlen($reparation['description_probleme']) > 80 ? '...' : ''; ?></div>
                    </div>
                </div>
                
                <?php if ($reparation['urgent'] || $reparation['commande_requise'] || !empty($reparation['notes_techniques'])): ?>
                <div class="special-indicators">
                    <?php if ($reparation['urgent']): ?>
                        <div class="indicator indicator-urgent">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Urgent</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($reparation['commande_requise']): ?>
                        <div class="indicator indicator-order">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Commande</span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="price-section">
                    <?php if (!empty($reparation['cout_reparation'])): ?>
                        <div class="price">
                            <i class="fas fa-euro-sign"></i>
                            <?php echo number_format($reparation['cout_reparation'], 2); ?> €
                        </div>
                    <?php endif; ?>
                    <div class="reception-date">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('d/m/Y', strtotime($reparation['date_reception'])); ?>
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                <button class="action-btn btn-call" title="Appeler">
                    <i class="fas fa-phone"></i>
                </button>
                <button class="action-btn btn-details" title="Détails" onclick="RepairModal.loadRepairDetails(<?php echo $reparation['id']; ?>)">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="action-btn btn-message" title="SMS">
                    <i class="fas fa-sms"></i>
                </button>
            </div>
        </div>
        <?php
        $html .= ob_get_clean();
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'count' => count($reparations),
        'has_more' => count($reparations) === $per_page,
        'next_page' => $page + 1
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error loading more repairs: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
