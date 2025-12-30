<?php
/**
 * Page de gestion des clients - Version COMPLÈTEMENT REFAITE
 * Interface moderne sans Bootstrap et sans modals problématiques
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
        $where_conditions[] = "(nom LIKE :search OR prenom LIKE :search OR telephone LIKE :search OR email LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Requête pour compter le total
    $count_sql = "SELECT COUNT(*) as total FROM clients $where_clause";
    $count_stmt = $shop_pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_items / $items_per_page);
    
    // Requête principale avec jointure pour compter les réparations
    $sql = "SELECT c.*, 
            COUNT(r.id) as nombre_reparations
            FROM clients c
            LEFT JOIN reparations r ON c.id = r.client_id
            $where_clause
            GROUP BY c.id
            ORDER BY $sort_by $sort_order
            LIMIT :limit OFFSET :offset";
    
    $stmt = $shop_pdo->prepare($sql);
    
    // Ajouter les paramètres de pagination
    $params['limit'] = $items_per_page;
    $params['offset'] = $offset;
    
    // Bind des paramètres
    foreach ($params as $key => $value) {
        if ($key === 'limit' || $key === 'offset') {
            $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
        }
    }
    
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des clients: " . $e->getMessage());
    $clients = [];
    $total_pages = 0;
    $total_items = 0;
}

// Fonction pour générer les URLs de tri
function getSortUrl($field) {
    global $sort_by, $sort_order, $search, $current_page;
    
    $new_order = ($sort_by === $field && $sort_order === 'ASC') ? 'DESC' : 'ASC';
    $params = ['page' => 'clients', 'sort' => $field, 'order' => $new_order];
    
    if (!empty($search)) {
        $params['search'] = $search;
    }
    
    return 'index.php?' . http_build_query($params);
}

// Fonction pour générer l'icône de tri
function getSortIcon($field) {
    global $sort_by, $sort_order;
    
    if ($sort_by !== $field) {
        return '↕️';
    }
    
    return $sort_order === 'ASC' ? '⬆️' : '⬇️';
}
?>

<style>
/* CSS personnalisé pour la page clients */
.clients-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 70px 20px 20px 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background: #f8fafc;
    min-height: 100vh;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.2);
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 10px 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.page-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    border-left: 4px solid #667eea;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #667eea;
    margin: 0;
}

.stat-label {
    color: #64748b;
    font-size: 0.9rem;
    margin: 5px 0 0 0;
}

.controls-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.controls-grid {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 20px;
    align-items: center;
}

.search-container {
    position: relative;
    max-width: 400px;
}

.search-input {
    width: 100%;
    padding: 12px 45px 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    background: white;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1.2rem;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.95rem;
}

.table th {
    background: #f8fafc;
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

.table td {
    padding: 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.table tr:hover {
    background: #f8fafc;
}

.sort-header {
    cursor: pointer;
    user-select: none;
    transition: color 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sort-header:hover {
    color: #667eea;
}

.client-id {
    font-weight: 600;
    color: #667eea;
    font-size: 0.9rem;
}

.client-name {
    font-weight: 600;
    color: #1e293b;
}

.contact-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.contact-link {
    color: #059669;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.contact-link:hover {
    color: #047857;
}

.sms-btn {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 6px 10px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.2s ease;
}

.sms-btn:hover {
    background: #2563eb;
}

.email-link {
    color: #7c3aed;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.email-link:hover {
    color: #6d28d9;
}

.date-text {
    color: #64748b;
    font-size: 0.9rem;
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-primary {
    background: #dbeafe;
    color: #1d4ed8;
}

.badge-success {
    background: #dcfce7;
    color: #166534;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.85rem;
    border-radius: 6px;
}

.btn-info {
    background: #0ea5e9;
    color: white;
}

.btn-info:hover {
    background: #0284c7;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
}

.pagination a, .pagination span {
    padding: 8px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    text-decoration: none;
    color: #475569;
    transition: all 0.2s ease;
}

.pagination a:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.pagination .current {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

/* Modal SMS Simple */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-overlay.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    padding: 0;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.modal-body {
    padding: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
    resize: vertical;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.client-info {
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #667eea;
}

.modal-footer {
    padding: 20px 25px;
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.btn-success {
    background: #059669;
    color: white;
}

.btn-success:hover {
    background: #047857;
}

@media (max-width: 768px) {
    .clients-container {
        padding: 90px 10px 20px 10px;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .controls-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .table {
        min-width: 600px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 5px;
    }
    
    .modal-content {
        width: 95%;
        margin: 20px;
    }
}
</style>

<div class="clients-container">
    <!-- En-tête de la page -->
    <div class="page-header">
        <h1 class="page-title">👥 Gestion des Clients</h1>
        <p class="page-subtitle">Gérez votre base client et consultez les informations détaillées</p>
    </div>

    <!-- Statistiques -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($total_items); ?></div>
            <div class="stat-label">Total clients</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count(array_filter($clients, function($c) { return $c['nombre_reparations'] > 0; })); ?></div>
            <div class="stat-label">Avec réparations</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo array_sum(array_column($clients, 'nombre_reparations')); ?></div>
            <div class="stat-label">Total réparations</div>
        </div>
    </div>

    <!-- Contrôles -->
    <div class="controls-section">
        <div class="controls-grid">
            <div class="search-container">
                <form method="GET" action="index.php">
                    <input type="hidden" name="page" value="clients">
                    <input type="text" 
                           class="search-input" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Rechercher un client...">
                    <span class="search-icon">🔍</span>
                </form>
            </div>
            <a href="index.php?page=ajouter_client" class="btn btn-primary">
                ➕ Nouveau Client
            </a>
        </div>
    </div>

    <?php if (empty($clients)): ?>
        <div class="table-container">
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3>Aucun client trouvé</h3>
                <p>
                    <?php if (!empty($search)): ?>
                        Aucun client ne correspond à votre recherche "<?php echo htmlspecialchars($search); ?>".
                    <?php else: ?>
                        Vous n'avez pas encore de clients enregistrés.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search)): ?>
                    <a href="index.php?page=clients" class="btn btn-primary">Voir tous les clients</a>
                <?php else: ?>
                    <a href="index.php?page=ajouter_client" class="btn btn-primary">Ajouter le premier client</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Tableau des clients -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <a href="<?php echo getSortUrl('id'); ?>" class="sort-header">
                                ID <?php echo getSortIcon('id'); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo getSortUrl('nom'); ?>" class="sort-header">
                                Nom <?php echo getSortIcon('nom'); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo getSortUrl('prenom'); ?>" class="sort-header">
                                Prénom <?php echo getSortIcon('prenom'); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo getSortUrl('telephone'); ?>" class="sort-header">
                                Téléphone <?php echo getSortIcon('telephone'); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo getSortUrl('email'); ?>" class="sort-header">
                                Email <?php echo getSortIcon('email'); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo getSortUrl('date_creation'); ?>" class="sort-header">
                                Créé le <?php echo getSortIcon('date_creation'); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo getSortUrl('nombre_reparations'); ?>" class="sort-header">
                                Réparations <?php echo getSortIcon('nombre_reparations'); ?>
                            </a>
                        </th>
                        <th>Actions</th>
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
                                            onclick="openSmsModal('<?php echo $client['id']; ?>', '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>', '<?php echo htmlspecialchars($client['telephone']); ?>')"
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
                                <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>" class="email-link">
                                    ✉️ <?php echo htmlspecialchars($client['email']); ?>
                                </a>
                            <?php else: ?>
                                <span style="color: #9ca3af; font-style: italic;">Non renseigné</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="date-text">
                                <?php echo date('d/m/Y', strtotime($client['date_creation'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($client['nombre_reparations'] > 0): ?>
                                <span class="badge badge-primary">
                                    <?php echo $client['nombre_reparations']; ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-warning">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-info btn-sm" onclick="showClientHistory('<?php echo $client['id']; ?>', '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>')">
                                    📋 Historique
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('<?php echo $client['id']; ?>', '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>')">
                                    🗑️ Supprimer
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="index.php?page=clients<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&p=<?php echo ($current_page - 1); ?>">
                        ⬅️ Précédent
                    </a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <?php if ($i == $current_page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="index.php?page=clients<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&p=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="index.php?page=clients<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&p=<?php echo ($current_page + 1); ?>">
                        Suivant ➡️
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal SMS Simple -->
<div id="smsModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">💬 Envoyer un SMS</h5>
            <button type="button" class="modal-close" onclick="closeSmsModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="client-info">
                <strong>Client :</strong> <span id="smsClientName"></span><br>
                <strong>Téléphone :</strong> <span id="smsClientPhone"></span>
            </div>
            <div class="form-group">
                <label for="smsMessage" class="form-label">Message SMS</label>
                <textarea id="smsMessage" class="form-control" rows="4" placeholder="Tapez votre message ici..." maxlength="160"></textarea>
                <small style="color: #6b7280; font-size: 0.85rem; margin-top: 5px; display: block;">
                    <span id="charCount">0</span>/160 caractères
                </small>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeSmsModal()">
                ❌ Annuler
            </button>
            <button type="button" class="btn btn-success" onclick="sendSms()">
                📤 Envoyer SMS
            </button>
        </div>
    </div>
</div>

<script>
// Variables globales
let currentSmsData = {};

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeSearch();
    initializeCharacterCounter();
});

// === FONCTIONS DE RECHERCHE ===
function initializeSearch() {
    const searchInput = document.querySelector('.search-input');
    if (!searchInput) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const form = searchInput.closest('form');
            if (form) {
                form.submit();
            }
        }, 800);
    });
    
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchTimeout);
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        }
    });
}

// === FONCTIONS SMS ===
function openSmsModal(clientId, clientName, clientPhone) {
    currentSmsData = {
        id: clientId,
        name: clientName,
        phone: clientPhone
    };
    
    document.getElementById('smsClientName').textContent = clientName;
    document.getElementById('smsClientPhone').textContent = clientPhone;
    document.getElementById('smsMessage').value = '';
    updateCharacterCount();
    
    const modal = document.getElementById('smsModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Focus sur le textarea
    setTimeout(() => {
        document.getElementById('smsMessage').focus();
    }, 100);
}

function closeSmsModal() {
    const modal = document.getElementById('smsModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
    currentSmsData = {};
}

function initializeCharacterCounter() {
    const textarea = document.getElementById('smsMessage');
    if (!textarea) return;
    
    textarea.addEventListener('input', updateCharacterCount);
}

function updateCharacterCount() {
    const textarea = document.getElementById('smsMessage');
    const counter = document.getElementById('charCount');
    if (!textarea || !counter) return;
    
    const count = textarea.value.length;
    counter.textContent = count;
    
    // Changer la couleur si on approche de la limite
    if (count > 140) {
        counter.style.color = '#ef4444';
    } else if (count > 120) {
        counter.style.color = '#f59e0b';
    } else {
        counter.style.color = '#6b7280';
    }
}

function sendSms() {
    const message = document.getElementById('smsMessage').value.trim();
    
    if (!message) {
        alert('Veuillez saisir un message SMS');
        return;
    }
    
    if (!currentSmsData.phone) {
        alert('Numéro de téléphone manquant');
        return;
    }
    
    const sendButton = document.querySelector('.btn-success');
    const originalText = sendButton.textContent;
    sendButton.textContent = '⏳ Envoi...';
    sendButton.disabled = true;
    
    const formData = new FormData();
    formData.append('telephone', currentSmsData.phone);
    formData.append('message', message);
    formData.append('client_id', currentSmsData.id);
    
    fetch('ajax/send_sms.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ SMS envoyé avec succès !');
            closeSmsModal();
        } else {
            alert('❌ Erreur lors de l\'envoi : ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur envoi SMS:', error);
        alert('❌ Erreur lors de l\'envoi du SMS');
    })
    .finally(() => {
        sendButton.textContent = originalText;
        sendButton.disabled = false;
    });
}

// === AUTRES FONCTIONS ===
function showClientHistory(clientId, clientName) {
    // Redirection vers la page d'historique
    window.location.href = `index.php?page=historique_client&client_id=${clientId}`;
}

function confirmDelete(clientId, clientName) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le client "${clientName}" ?\n\nCette action est irréversible.`)) {
        deleteClient(clientId);
    }
}

function deleteClient(clientId) {
    const formData = new FormData();
    formData.append('client_id', clientId);
    
    fetch('ajax/delete_client.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Client supprimé avec succès');
            location.reload();
        } else {
            alert('❌ Erreur lors de la suppression : ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur suppression client:', error);
        alert('❌ Erreur lors de la suppression du client');
    });
}

// Fermer le modal en cliquant en dehors
document.getElementById('smsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSmsModal();
    }
});

// Fermer le modal avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSmsModal();
    }
});
</script>
