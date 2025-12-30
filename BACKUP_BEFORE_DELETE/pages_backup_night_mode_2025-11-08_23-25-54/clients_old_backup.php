<?php
/**
 * Page de gestion des clients - Version SANS Bootstrap
 * Interface moderne avec CSS pur et toutes les fonctionnalités existantes
 */

// Configuration de la pagination
$items_per_page = 20;
$current_page = max(1, intval($_GET['p'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

// Paramètres de recherche et tri
$search = trim($_GET['search'] ?? '');
$sort_by = $_GET['sort'] ?? 'nom';
$sort_order = ($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

// Validation des paramètres de tri
$allowed_sort_fields = ['nom', 'prenom', 'telephone', 'email', 'date_creation', 'nombre_reparations'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'nom';
}

try {
    $shop_pdo = getShopDBConnection();
    
    // Construction de la requête avec recherche
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(c.nom LIKE :search OR c.prenom LIKE :search OR c.telephone LIKE :search OR c.email LIKE :search OR CONCAT(c.prenom, ' ', c.nom) LIKE :search)";
        $params['search'] = "%{$search}%";
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Requête pour compter le total
    $count_sql = "
        SELECT COUNT(DISTINCT c.id) as total 
        FROM clients c 
        LEFT JOIN reparations r ON c.id = r.client_id 
        {$where_clause}
    ";
    $count_stmt = $shop_pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_clients = $count_stmt->fetchColumn();
    $total_pages = ceil($total_clients / $items_per_page);
    
    // Requête principale optimisée avec pagination
    $sql = "
        SELECT 
            c.id,
            c.nom,
            c.prenom,
            c.telephone,
            c.email,
            c.date_creation,
            COUNT(r.id) as nombre_reparations,
            SUM(CASE WHEN r.statut IN ('en_cours_diagnostique', 'en_cours_intervention', 'en_attente_accord_client') THEN 1 ELSE 0 END) as reparations_en_cours
        FROM clients c 
        LEFT JOIN reparations r ON c.id = r.client_id 
        {$where_clause}
        GROUP BY c.id 
        ORDER BY {$sort_by} {$sort_order}
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $shop_pdo->prepare($sql);
    $params['limit'] = $items_per_page;
    $params['offset'] = $offset;
    $stmt->execute($params);
    $clients = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des clients : " . $e->getMessage());
    $clients = [];
    $total_clients = 0;
    $total_pages = 0;
    $error_message = "Erreur lors du chargement des clients. Veuillez réessayer.";
}

// Traitement de la suppression (avec vérification CSRF)
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['client_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        set_message("Action non autorisée.", "danger");
    } else {
        $client_id = (int)$_POST['client_id'];
        
        try {
            $shop_pdo = getShopDBConnection();
            
            $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM reparations WHERE client_id = :id");
            $stmt->execute(['id' => $client_id]);
            $repair_count = $stmt->fetchColumn();
            
            if ($repair_count > 0) {
                set_message("Impossible de supprimer ce client car il a {$repair_count} réparation(s) associée(s).", "warning");
            } else {
                $stmt = $shop_pdo->prepare("DELETE FROM clients WHERE id = :id");
                $stmt->execute(['id' => $client_id]);
                set_message("Client supprimé avec succès.", "success");
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du client: " . $e->getMessage());
            set_message("Erreur lors de la suppression du client.", "danger");
        }
        
        // Redirection pour éviter la re-soumission
        $redirect_params = [];
        if (!empty($search)) $redirect_params['search'] = $search;
        if ($current_page > 1) $redirect_params['p'] = $current_page;
        if ($sort_by !== 'nom') $redirect_params['sort'] = $sort_by;
        if ($sort_order !== 'ASC') $redirect_params['order'] = $sort_order;
        
        $redirect_url = 'index.php?page=clients';
        if (!empty($redirect_params)) {
            $redirect_url .= '&' . http_build_query($redirect_params);
        }
        
        header("Location: {$redirect_url}");
        exit;
    }
}

// Génération du token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Fonctions utilitaires
 */
function getSortUrl($field, $order, $search) {
    $params = [
        'page' => 'clients',
        'sort' => $field,
        'order' => strtoupper($order)
    ];
    
    if (!empty($search)) {
        $params['search'] = $search;
    }
    
    return 'index.php?' . http_build_query($params);
}

function getSortLink($field, $label, $current_sort, $current_order, $search, $current_page) {
    $new_order = ($current_sort === $field && $current_order === 'ASC') ? 'DESC' : 'ASC'; 
    $params = ['sort' => $field, 'order' => $new_order];
    if (!empty($search)) $params['search'] = $search;
    if ($current_page > 1) $params['p'] = $current_page;
    
    $url = 'index.php?page=clients&' . http_build_query($params);
    $icon = '';
    if ($current_sort === $field) {
        $icon = $current_order === 'ASC' ? ' ▲' : ' ▼';
    }
    
    return "<a href=\"{$url}\" class=\"sort-link\">{$label}{$icon}</a>";
}
?>

<!DOCTYPE html>
<style>
/* STYLES CSS MODERNES SANS BOOTSTRAP */
* {
    box-sizing: border-box;
}

.clients-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 70px 20px 20px 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

/* En-tête */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.page-title {
    margin: 0;
    color: #1f2937;
    font-size: 2rem;
    font-weight: 700;
}

.page-subtitle {
    color: #6b7280;
    margin: 5px 0 0 0;
    font-size: 0.9rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-info {
    background: #06b6d4;
    color: white;
}

.btn-success {
    background: #10b981;
    color: white;
}

.icon {
    margin-right: 8px;
    width: 16px;
    height: 16px;
}

/* Barre de recherche */
.search-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.search-row {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.search-group {
    flex: 1;
    min-width: 300px;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 12px;
    color: #9ca3af;
    z-index: 2;
}

.search-input {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-actions {
    display: flex;
    gap: 10px;
}

.search-results {
    margin-top: 10px;
    font-size: 0.9rem;
    color: #6b7280;
}

.search-clear {
    margin-left: 10px;
    color: #ef4444;
    text-decoration: none;
}

/* Tableau */
.table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.table th {
    background: #f9fafb;
    padding: 16px 12px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
}

.table td {
    padding: 16px 12px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.table tr:hover {
    background: #f9fafb;
}

.sort-link {
    color: #374151;
    text-decoration: none;
    display: flex;
    align-items: center;
    font-weight: 600;
}

.sort-link:hover {
    color: #3b82f6;
}

.client-id {
    background: #e5e7eb;
    color: #374151;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.8rem;
}

.client-name {
    font-weight: 600;
    color: #1f2937;
}

.contact-link {
    color: #3b82f6;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
}

.contact-link:hover {
    text-decoration: underline;
}

.repairs-badge {
    background: #3b82f6;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
    position: relative;
}

.repairs-badge-active {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #f59e0b;
    color: #000;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: bold;
}

.action-buttons {
    display: flex;
    gap: 6px;
    justify-content: center;
}

.action-btn {
    padding: 8px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
    min-width: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s ease;
}

.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Pagination */
.pagination-container {
    padding: 20px;
    background: #f9fafb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.pagination {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 4px;
}

.pagination-item {
    display: flex;
}

.pagination-link {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    background: white;
    color: #374151;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.2s ease;
    min-width: 40px;
}

.pagination-link:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.pagination-item.active .pagination-link {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.pagination-item.disabled .pagination-link {
    background: #f9fafb;
    color: #9ca3af;
    cursor: not-allowed;
}

.pagination-info {
    font-size: 0.9rem;
    color: #6b7280;
}

/* Modals */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-dialog {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90%;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-dialog-lg {
    max-width: 800px;
}

.modal-dialog-xl {
    max-width: 1200px;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #9ca3af;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: #374151;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Alerts */
.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.alert-danger {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-warning {
    background: #fffbeb;
    color: #92400e;
    border: 1px solid #fed7aa;
}

.alert-success {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-info {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

/* État vide */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 20px;
}

.empty-title {
    font-size: 1.5rem;
    color: #6b7280;
    margin-bottom: 10px;
}

.empty-description {
    color: #9ca3af;
    margin-bottom: 30px;
}

/* Formulaires */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-text {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 4px;
}

/* Options de tri */
.sort-dropdown {
    position: relative;
    display: inline-block;
}

.sort-dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background: white;
    min-width: 200px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    border-radius: 6px;
    z-index: 100;
    border: 1px solid #e5e7eb;
}

.sort-dropdown.active .sort-dropdown-content {
    display: block;
}

.sort-option {
    display: block;
    padding: 12px 16px;
    color: #374151;
    text-decoration: none;
    transition: background-color 0.2s ease;
}

.sort-option:hover {
    background: #f3f4f6;
}

.sort-divider {
    height: 1px;
    background: #e5e7eb;
    margin: 4px 0;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-row {
        flex-direction: column;
    }
    
    .search-group {
        min-width: auto;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .table {
        min-width: 800px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .modal-dialog {
        margin: 10px;
        width: calc(100% - 20px);
    }
}

/* Animation fade-in */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.clients-container {
    animation: fadeIn 0.3s ease-out;
}

/* Spinner de chargement */
.spinner {
    border: 2px solid #f3f4f6;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    animation: spin 1s linear infinite;
    display: inline-block;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* SMS Button styling */
.sms-btn {
    background: #10b981;
    color: white;
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
    margin-left: 8px;
    min-width: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.contact-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Styles pour les champs de formulaire dans les modals */
.radio-group {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.radio-option {
    flex: 1;
}

.radio-option input[type="radio"] {
    display: none;
}

.radio-option label {
    display: block;
    padding: 10px 15px;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s ease;
}

.radio-option input[type="radio"]:checked + label {
    border-color: #3b82f6;
    background: #eff6ff;
    color: #1e40af;
}

.message-preview {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 15px;
    margin-top: 10px;
    font-style: italic;
    color: #6b7280;
}

.char-counter {
    text-align: right;
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 5px;
}

.char-counter.over-limit {
    color: #ef4444;
}
</style>

<div class="clients-container">
<!-- Messages d'alerte -->
<?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            ⚠️ <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

    <!-- En-tête -->
    <div class="page-header">
            <div>
            <h1 class="page-title">
                👥 Gestion des Clients
                </h1>
            <p class="page-subtitle">
                    <?php 
                    if (!empty($search)) {
                        echo "Recherche : \"" . htmlspecialchars($search) . "\" - ";
                    }
                    echo number_format($total_clients) . " client" . ($total_clients > 1 ? 's' : '') . " trouvé" . ($total_clients > 1 ? 's' : '');
                    ?>
                </p>
            </div>
        <div>
                <a href="index.php?page=ajouter_client" class="btn btn-primary">
                <span class="icon">➕</span>
                Nouveau Client
                </a>
    </div>
</div>

    <!-- Barre de recherche -->
    <div class="search-section">
        <div class="search-row">
            <div class="search-group">
                <div class="search-input-wrapper">
                    <span class="search-icon">🔍</span>
                        <input 
                            type="text" 
                        class="search-input" 
                            id="searchInput" 
                            value="<?php echo htmlspecialchars($search); ?>" 
                            placeholder="Rechercher un client (nom, prénom, téléphone, email)..."
                        >
                    </div>
                    
                    <?php if (!empty($search)): ?>
                    <div class="search-results">
                        🔎 Recherche active : "<strong><?php echo htmlspecialchars($search); ?></strong>" 
                            - <?php echo number_format($total_clients); ?> résultat<?php echo $total_clients > 1 ? 's' : ''; ?>
                        <a href="index.php?page=clients" class="search-clear">❌ Effacer</a>
                    </div>
                    <?php endif; ?>
            </div>
            
            <div class="search-actions">
                    <!-- Tri rapide -->
                <div class="sort-dropdown" id="sortDropdown">
                    <button class="btn btn-secondary" onclick="toggleSortDropdown()">
                        <span class="icon">↕️</span>
                        Trier
                        </button>
                    <div class="sort-dropdown-content">
                        <a class="sort-option" href="<?php echo getSortUrl('nom', 'ASC', $search); ?>">
                            📝 Nom A-Z
                        </a>
                        <a class="sort-option" href="<?php echo getSortUrl('nom', 'DESC', $search); ?>">
                            📝 Nom Z-A
                        </a>
                        <div class="sort-divider"></div>
                        <a class="sort-option" href="<?php echo getSortUrl('date_creation', 'DESC', $search); ?>">
                            🕒 Plus récents
                        </a>
                        <a class="sort-option" href="<?php echo getSortUrl('nombre_reparations', 'DESC', $search); ?>">
                            🔧 Plus de réparations
                        </a>
                    </div>
                    </div>
                    
                    <!-- Export -->
                <button type="button" class="btn btn-success" title="Exporter la liste">
                    <span class="icon">📥</span>
                    Export
                    </button>
        </div>
    </div>
</div>

<!-- Tableau des clients -->
    <div class="table-container">
        <?php if (!empty($clients)): ?>
            <table class="table">
                    <thead>
                    <tr>
                        <th style="width: 80px;">
                                <?php echo getSortLink('id', 'ID', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                        <th style="width: 150px;">
                                <?php echo getSortLink('nom', 'Nom', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                        <th style="width: 150px;">
                                <?php echo getSortLink('prenom', 'Prénom', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                        <th style="width: 180px;">
                                <?php echo getSortLink('telephone', 'Téléphone', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                        <th style="width: 200px;">
                                <?php echo getSortLink('email', 'Email', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                        <th style="width: 100px; text-align: center;">
                                <?php echo getSortLink('nombre_reparations', 'Réparations', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                        <th style="width: 120px; text-align: center;">
                                <?php echo getSortLink('date_creation', 'Inscrit le', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                        <th style="width: 120px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>
                                <span class="client-id">#<?php echo $client['id']; ?></span>
                                </td>
                            <td>
                                <span class="client-name"><?php echo htmlspecialchars($client['nom']); ?></span>
                                </td>
                            <td>
                                <?php echo htmlspecialchars($client['prenom']); ?>
                                </td>
                            <td>
                                        <?php if (!empty($client['telephone'])): ?>
                                    <div class="contact-group">
                                        <a href="tel:<?php echo htmlspecialchars($client['telephone']); ?>" class="contact-link">
                                            📞 <?php echo htmlspecialchars($client['telephone']); ?>
                                            </a>
                                            <button type="button" 
                                                class="sms-btn"
                                                onclick="openSmsModal(<?php echo $client['id']; ?>, '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>', '<?php echo htmlspecialchars($client['telephone']); ?>')"
                                                    title="Envoyer un SMS">
                                            💬
                                            </button>
                                    </div>
                                    <?php else: ?>
                                    <span style="color: #9ca3af; font-style: italic;">Non renseigné</span>
                                    <?php endif; ?>
                                </td>
                            <td>
                                    <?php if (!empty($client['email'])): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>" class="contact-link">
                                        ✉️ <?php echo htmlspecialchars($client['email']); ?>
                                        </a>
                                    <?php else: ?>
                                    <span style="color: #9ca3af; font-style: italic;">Non renseigné</span>
                                    <?php endif; ?>
                                </td>
                            <td style="text-align: center;">
                                    <?php if ($client['nombre_reparations'] > 0): ?>
                                    <span class="repairs-badge">
                                            <?php echo $client['nombre_reparations']; ?>
                                            <?php if ($client['reparations_en_cours'] > 0): ?>
                                            <span class="repairs-badge-active">
                                                    <?php echo $client['reparations_en_cours']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                    <span style="color: #9ca3af;">0</span>
                                    <?php endif; ?>
                                </td>
                            <td style="text-align: center;">
                                <span style="color: #6b7280; font-size: 0.9rem;">
                                        <?php echo date('d/m/Y', strtotime($client['date_creation'])); ?>
                                    </span>
                                </td>
                            <td>
                                <div class="action-buttons">
                                        <?php if ($client['nombre_reparations'] > 0): ?>
                                            <button type="button" 
                                                class="action-btn btn-info"
                                                onclick="openHistoryModal(<?php echo $client['id']; ?>, '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>')"
                                                    title="Voir l'historique">
                                            📜
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="index.php?page=modifier_client&id=<?php echo $client['id']; ?>" 
                                       class="action-btn btn-warning"
                                           title="Modifier le client">
                                        ✏️
                                        </a>
                                        
                                        <button type="button" 
                                            class="action-btn btn-danger"
                                            onclick="openDeleteModal(<?php echo $client['id']; ?>, '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>', <?php echo $client['nombre_reparations']; ?>)"
                                                title="Supprimer le client">
                                        🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            
            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                        Page <?php echo $current_page; ?> sur <?php echo $total_pages; ?> 
                        (<?php echo number_format($total_clients); ?> client<?php echo $total_clients > 1 ? 's' : ''; ?> au total)
                    </div>
                <?php if ($total_pages > 1): ?>
                    <ul class="pagination">
                        <!-- Bouton précédent -->
                        <?php if ($current_page > 1): ?>
                            <?php 
                            $prev_params = [];
                            if (!empty($search)) $prev_params['search'] = $search;
                            if ($sort_by !== 'nom') $prev_params['sort'] = $sort_by;
                            if ($sort_order !== 'ASC') $prev_params['order'] = $sort_order;
                            $prev_params['p'] = $current_page - 1;
                            $prev_url = 'index.php?page=clients&' . http_build_query($prev_params);
                            ?>
                            <li class="pagination-item">
                                <a class="pagination-link" href="<?php echo $prev_url; ?>">◀</a>
                            </li>
                        <?php else: ?>
                            <li class="pagination-item disabled">
                                <span class="pagination-link">◀</span>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Pages -->
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                            if ($i == $current_page): ?>
                                <li class="pagination-item active">
                                    <span class="pagination-link"><?php echo $i; ?></span>
                                </li>
                            <?php else:
                                $page_params = [];
                                if (!empty($search)) $page_params['search'] = $search;
                                if ($sort_by !== 'nom') $page_params['sort'] = $sort_by;
                                if ($sort_order !== 'ASC') $page_params['order'] = $sort_order;
                                $page_params['p'] = $i;
                                $page_url = 'index.php?page=clients&' . http_build_query($page_params);
                                ?>
                                <li class="pagination-item">
                                    <a class="pagination-link" href="<?php echo $page_url; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif;
                        endfor; ?>
                        
                        <!-- Bouton suivant -->
                        <?php if ($current_page < $total_pages): ?>
                            <?php 
                            $next_params = [];
                            if (!empty($search)) $next_params['search'] = $search;
                            if ($sort_by !== 'nom') $next_params['sort'] = $sort_by;
                            if ($sort_order !== 'ASC') $next_params['order'] = $sort_order;
                            $next_params['p'] = $current_page + 1;
                            $next_url = 'index.php?page=clients&' . http_build_query($next_params);
                            ?>
                            <li class="pagination-item">
                                <a class="pagination-link" href="<?php echo $next_url; ?>">▶</a>
                            </li>
        <?php else: ?>
                            <li class="pagination-item disabled">
                                <span class="pagination-link">▶</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                </div>
            
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3 class="empty-title">
                    <?php echo !empty($search) ? 'Aucun résultat trouvé' : 'Aucun client enregistré'; ?>
                </h3>
                <p class="empty-description">
                    <?php echo !empty($search) 
                        ? 'Essayez avec d\'autres termes de recherche ou' 
                        : 'Vous pouvez'; ?> 
                    ajouter votre premier client.
                </p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <?php if (!empty($search)): ?>
                        <a href="index.php?page=clients" class="btn btn-secondary">
                            ❌ Effacer la recherche
                        </a>
                    <?php endif; ?>
                    <a href="index.php?page=ajouter_client" class="btn btn-primary">
                        ➕ Ajouter un client
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal" id="confirmDeleteModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h5 class="modal-title">
                ⚠️ Confirmer la suppression
                </h5>
            <button type="button" class="modal-close" onclick="closeModal('confirmDeleteModal')">×</button>
            </div>
            <div class="modal-body">
            <p>Êtes-vous sûr de vouloir supprimer le client <strong id="clientNameToDelete"></strong> ?</p>
            <div class="alert alert-warning" id="deleteWarning" style="display: none;">
                ⚠️ <strong>Attention :</strong> Ce client a <span id="repairCount"></span> réparation(s) associée(s). 
                    Il ne pourra pas être supprimé.
                </div>
            <p style="color: #9ca3af; font-size: 0.9rem;">Cette action est irréversible.</p>
            </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('confirmDeleteModal')">
                ❌ Annuler
                </button>
            <form method="POST" action="index.php?page=clients" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="client_id" id="clientIdToDelete">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">
                    🗑️ Supprimer définitivement
                    </button>
                </form>
        </div>
    </div>
</div>

<!-- Modal pour l'historique des réparations -->
<div class="modal" id="historiqueModal">
    <div class="modal-dialog modal-dialog-xl">
            <div class="modal-header">
            <h5 class="modal-title">
                📜 Historique des réparations - <span id="clientNameHistory"></span>
                </h5>
            <button type="button" class="modal-close" onclick="closeModal('historiqueModal')">×</button>
            </div>
            <div class="modal-body">
                <div id="historyContent">
                <div style="text-align: center; padding: 40px;">
                    <div class="spinner"></div>
                    <p style="margin-top: 10px; color: #6b7280;">Chargement de l'historique...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('historiqueModal')">
                ❌ Fermer
                </button>
        </div>
    </div>
</div>

<!-- Modal pour envoyer un SMS -->
<div class="modal" id="smsModal">
    <div class="modal-dialog modal-dialog-lg">
            <div class="modal-header">
            <h5 class="modal-title">
                💬 Envoyer un SMS - <span id="clientNameSms"></span>
                </h5>
            <button type="button" class="modal-close" onclick="closeModal('smsModal')">×</button>
            </div>
            <div class="modal-body">
                    <!-- Informations du client -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label class="form-label">Client</label>
                    <p id="smsClientInfo" style="margin: 0; padding: 10px; background: #f3f4f6; border-radius: 4px;"></p>
                        </div>
                <div>
                    <label class="form-label">Téléphone</label>
                    <p id="smsClientPhone" style="margin: 0; padding: 10px; background: #f3f4f6; border-radius: 4px;"></p>
                        </div>
                    </div>
                    
                    <!-- Type de message -->
            <div class="form-group">
                <label class="form-label">Type de message</label>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" name="messageType" id="templateType" value="template" checked>
                        <label for="templateType">📝 Template prédéfini</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" name="messageType" id="customType" value="custom">
                        <label for="customType">✏️ Message personnalisé</label>
                    </div>
                        </div>
                    </div>
                    
                    <!-- Sélection du template -->
            <div class="form-group" id="templateSection">
                <label for="smsTemplate" class="form-label">Choisir un template</label>
                <select class="form-control" id="smsTemplate" name="template_id">
                            <option value="">Chargement des templates...</option>
                        </select>
                        <div class="form-text">
                    💡 Les variables {CLIENT_NOM}, {CLIENT_PRENOM}, {DATE} seront automatiquement remplacées.
                        </div>
                    </div>
                    
                    <!-- Message personnalisé -->
            <div class="form-group" id="customSection" style="display: none;">
                <label for="customMessage" class="form-label">Message personnalisé</label>
                        <textarea class="form-control" id="customMessage" name="custom_message" rows="4" 
                                  placeholder="Tapez votre message ici..." maxlength="160"></textarea>
                <div style="display: flex; justify-content: between; margin-top: 5px;">
                    <div class="form-text">💡 Maximum 160 caractères pour un SMS</div>
                    <div class="char-counter" id="charCount">0/160</div>
                        </div>
                    </div>
                    
                    <!-- Aperçu du message -->
            <div class="form-group">
                <label class="form-label">Aperçu du message</label>
                <div class="message-preview">
                    <div id="messagePreview">Sélectionnez un template ou tapez un message pour voir l'aperçu...</div>
                    <div style="margin-top: 10px; font-size: 0.8rem; color: #6b7280;">
                                        Longueur : <span id="previewLength">0</span> caractères
                            </div>
                        </div>
                    </div>
                    
                    <!-- Messages d'alerte -->
            <div id="smsAlert" class="alert" style="display: none;"></div>
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('smsModal')">
                ❌ Annuler
                </button>
            <button type="button" class="btn btn-success" id="sendSmsBtn" onclick="sendSms()">
                📤 Envoyer le SMS
                </button>
        </div>
    </div>
</div>

<script>
// Variables globales
let currentClientData = {};
let smsTemplates = [];
let searchTimeout;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeSearch();
    loadSmsTemplates();
    setupMessageTypeHandlers();
    
    // Désactiver les modals SMS automatiques immédiatement et périodiquement
    disableAutoSmsModal();
    setTimeout(function() {
        disableAutoSmsModal();
    }, 1000);
    setTimeout(function() {
        disableAutoSmsModal();
    }, 3000);
    setTimeout(function() {
        disableAutoSmsModal();
    }, 5000);
    
    // Surveiller et supprimer automatiquement tous les modals SMS non demandés
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.id && node.id.includes('smsModal')) {
                    if (!node.dataset.userInitiated) {
                        console.log('🚫 Modal SMS automatique détecté et supprimé:', node.id);
                        node.remove();
                        disableAutoSmsModal();
                    }
                }
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
    
    // Vérification périodique toutes les 2 secondes
    setInterval(function() {
        disableAutoSmsModal();
    }, 2000);
});

// === FONCTIONS DE RECHERCHE ===
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 800);
        });
        
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                performSearch();
            }
        });
    }
}

        function performSearch() {
    const query = document.getElementById('searchInput').value.trim();
            
            if (query.length === 0) {
                window.location.href = 'index.php?page=clients';
                return;
            }
            
    if (query.length < 2) return;
            
                const params = new URLSearchParams();
                params.append('page', 'clients');
                params.append('search', query);
                
                window.location.href = 'index.php?' + params.toString();
}

// === FONCTIONS MODALS ===
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

function openDeleteModal(clientId, clientName, repairCount) {
            document.getElementById('clientNameToDelete').textContent = clientName;
            document.getElementById('clientIdToDelete').value = clientId;
            
            const warningAlert = document.getElementById('deleteWarning');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            if (repairCount > 0) {
                document.getElementById('repairCount').textContent = repairCount;
        warningAlert.style.display = 'block';
                confirmBtn.disabled = true;
        confirmBtn.innerHTML = '🚫 Suppression impossible';
        confirmBtn.className = 'btn btn-secondary';
            } else {
        warningAlert.style.display = 'none';
                confirmBtn.disabled = false;
        confirmBtn.innerHTML = '🗑️ Supprimer définitivement';
        confirmBtn.className = 'btn btn-danger';
    }
    
    openModal('confirmDeleteModal');
}

function openHistoryModal(clientId, clientName) {
            document.getElementById('clientNameHistory').textContent = clientName;
            
            const historyContent = document.getElementById('historyContent');
            historyContent.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div class="spinner"></div>
            <p style="margin-top: 10px; color: #6b7280;">Chargement de l'historique...</p>
                </div>
            `;
            
    openModal('historiqueModal');
    
    // Charger l'historique via AJAX
            const shopId = '<?php echo $_SESSION["shop_id"] ?? ""; ?>';
            const ajaxUrl = shopId ? 
                `ajax/get_client_history.php?client_id=${clientId}&shop_id=${shopId}` :
                `ajax/get_client_history.php?client_id=${clientId}`;
            
            fetch(ajaxUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP: ${response.status}`);
                    }
                    return response.text();
                })
                .then(data => {
                    historyContent.innerHTML = data;
                })
                .catch(error => {
                    console.error('Erreur lors du chargement de l\'historique:', error);
                    historyContent.innerHTML = `
                        <div class="alert alert-danger">
                    ⚠️ Erreur lors du chargement de l'historique: ${error.message}
                    <br><small>Vérifiez que vous êtes connecté et réessayez.</small>
                        </div>
                    `;
        });
}

function openSmsModal(clientId, clientName, clientPhone) {
    // Marquer comme initié par l'utilisateur
    const smsModal = document.getElementById('smsModal');
    if (smsModal) {
        smsModal.dataset.userInitiated = 'true';
    }
    
                    currentClientData = {
                        id: clientId,
                        name: clientName,
                        phone: clientPhone
                    };
    
    document.getElementById('clientNameSms').textContent = clientName;
    document.getElementById('smsClientInfo').textContent = clientName;
    document.getElementById('smsClientPhone').textContent = clientPhone;
    
            resetSmsForm();
    openModal('smsModal');
}

// Désactiver le modal SMS automatique
function disableAutoSmsModal() {
    // Supprimer tous les modals SMS automatiques
    const autoModals = document.querySelectorAll('#smsModal[data-emergency-modal="true"]');
    autoModals.forEach(modal => modal.remove());
    
    // Supprimer TOUS les modals SMS qui s'affichent automatiquement
    const allSmsModals = document.querySelectorAll('#smsModal');
    allSmsModals.forEach(modal => {
        if (modal.classList.contains('show') && !modal.dataset.userInitiated) {
            console.log('🚫 Suppression modal SMS automatique détecté');
            modal.remove();
        }
    });
    
    // Nettoyer tous les backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Restaurer l'interaction avec la page
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // Désactiver la fonction createEmergencySmsModal globalement
    if (window.createEmergencySmsModal) {
        window.createEmergencySmsModal = function() {
            console.log('🚫 createEmergencySmsModal désactivé pour éviter l\'ouverture automatique');
            return null;
        };
    }
}

// === FONCTIONS SMS ===
    function loadSmsTemplates() {
        fetch('ajax/get_sms_templates.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    smsTemplates = data.templates;
                    populateTemplateSelect();
                } else {
                    console.error('Erreur lors du chargement des templates:', data.error);
                }
            })
            .catch(error => {
                console.error('Erreur AJAX:', error);
            });
    }
    
    function populateTemplateSelect() {
        const select = document.getElementById('smsTemplate');
        select.innerHTML = '<option value="">-- Choisir un template --</option>';
        
        smsTemplates.forEach(template => {
            const option = document.createElement('option');
            option.value = template.id;
            option.textContent = template.nom;
            option.setAttribute('data-content', template.contenu);
            select.appendChild(option);
        });
    }

function setupMessageTypeHandlers() {
    document.querySelectorAll('input[name="messageType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            toggleMessageType(this.value);
            updatePreview();
        });
    });
    
    const templateSelect = document.getElementById('smsTemplate');
    if (templateSelect) {
        templateSelect.addEventListener('change', updatePreview);
    }
    
    const customMessage = document.getElementById('customMessage');
    if (customMessage) {
        customMessage.addEventListener('input', function() {
            updateCharCount();
            updatePreview();
        });
    }
    }
    
    function toggleMessageType(type) {
        const templateSection = document.getElementById('templateSection');
        const customSection = document.getElementById('customSection');
        
        if (type === 'template') {
        templateSection.style.display = 'block';
        customSection.style.display = 'none';
        } else {
        templateSection.style.display = 'none';
        customSection.style.display = 'block';
        }
    }
    
    function updateCharCount() {
        const textarea = document.getElementById('customMessage');
        const charCount = document.getElementById('charCount');
        const length = textarea.value.length;
        
        charCount.textContent = `${length}/160`;
        
        if (length > 160) {
        charCount.classList.add('over-limit');
        textarea.style.borderColor = '#ef4444';
        } else {
        charCount.classList.remove('over-limit');
        textarea.style.borderColor = '#d1d5db';
        }
    }
    
    function updatePreview() {
        const messageType = document.querySelector('input[name="messageType"]:checked').value;
        const preview = document.getElementById('messagePreview');
        const previewLength = document.getElementById('previewLength');
        let message = '';
        
        if (messageType === 'template') {
            const templateSelect = document.getElementById('smsTemplate');
            const selectedOption = templateSelect.options[templateSelect.selectedIndex];
            
            if (selectedOption && selectedOption.value) {
                message = selectedOption.getAttribute('data-content') || '';
                
                // Remplacer les variables
            if (currentClientData.name) {
                const nameParts = currentClientData.name.split(' ');
                message = message.replace(/{CLIENT_NOM}/g, nameParts[0] || '');
                message = message.replace(/{CLIENT_PRENOM}/g, nameParts[1] || '');
            }
                message = message.replace(/{DATE}/g, new Date().toLocaleDateString('fr-FR'));
            }
        } else {
            message = document.getElementById('customMessage').value;
        }
        
        if (message.trim()) {
            preview.textContent = message;
        preview.style.fontStyle = 'normal';
        preview.style.color = '#374151';
        } else {
            preview.textContent = 'Sélectionnez un template ou tapez un message pour voir l\'aperçu...';
        preview.style.fontStyle = 'italic';
        preview.style.color = '#6b7280';
        }
        
        previewLength.textContent = message.length;
        
        if (message.length > 160) {
        previewLength.style.color = '#ef4444';
        } else {
        previewLength.style.color = '#6b7280';
        }
    }
    
    function resetSmsForm() {
        document.getElementById('templateType').checked = true;
        document.getElementById('customType').checked = false;
        
        toggleMessageType('template');
        
        document.getElementById('smsTemplate').value = '';
        document.getElementById('customMessage').value = '';
        
        updatePreview();
        updateCharCount();
        
    const alert = document.getElementById('smsAlert');
    alert.style.display = 'none';
    }
    
    function sendSms() {
        const messageType = document.querySelector('input[name="messageType"]:checked').value;
        const sendBtn = document.getElementById('sendSmsBtn');
        
        // Validation
        if (messageType === 'template') {
            const templateId = document.getElementById('smsTemplate').value;
            if (!templateId) {
                showSmsAlert('Veuillez sélectionner un template', 'warning');
                return;
            }
        } else {
            const customMessage = document.getElementById('customMessage').value.trim();
            if (!customMessage) {
                showSmsAlert('Veuillez saisir un message', 'warning');
                return;
            }
            if (customMessage.length > 160) {
                showSmsAlert('Le message est trop long (maximum 160 caractères)', 'warning');
                return;
            }
        }
        
        // Désactiver le bouton et afficher le spinner
        sendBtn.disabled = true;
    sendBtn.innerHTML = '<span class="spinner"></span> Envoi en cours...';
        
        // Préparer les données
        const data = {
            client_id: currentClientData.id,
            telephone: currentClientData.phone,
            message_type: messageType
        };
        
        if (messageType === 'template') {
            data.template_id = document.getElementById('smsTemplate').value;
        } else {
            data.custom_message = document.getElementById('customMessage').value.trim();
        }
        
        // Envoyer la requête
        fetch('ajax/send_client_sms.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showSmsAlert(`SMS envoyé avec succès à ${currentClientData.phone}`, 'success');
                
                setTimeout(() => {
                closeModal('smsModal');
                }, 2000);
            } else {
                showSmsAlert(`Erreur: ${result.error}`, 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'envoi du SMS:', error);
            showSmsAlert('Erreur de connexion lors de l\'envoi du SMS', 'danger');
        })
        .finally(() => {
            sendBtn.disabled = false;
        sendBtn.innerHTML = '📤 Envoyer le SMS';
        });
    }
    
    function showSmsAlert(message, type) {
        const alert = document.getElementById('smsAlert');
    const icons = {
        success: '✅',
        warning: '⚠️',
        danger: '❌',
        info: 'ℹ️'
    };
    
        alert.className = `alert alert-${type}`;
    alert.innerHTML = `${icons[type]} ${message}`;
    alert.style.display = 'block';
}

// === FONCTIONS UTILITAIRES ===
function toggleSortDropdown() {
    const dropdown = document.getElementById('sortDropdown');
    dropdown.classList.toggle('active');
}

// Fermer le dropdown si on clique ailleurs
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('sortDropdown');
    if (dropdown && !dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

// Fermer les modals en cliquant en dehors
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
});

// Raccourci clavier pour la recherche
document.addEventListener('keydown', function(event) {
    if (event.ctrlKey && event.key === 'f') {
        event.preventDefault();
        const searchField = document.getElementById('searchInput');
        if (searchField) {
            searchField.focus();
            searchField.select();
        }
    }
    
    if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
            closeModal(openModal.id);
            }
        }
});
</script>
