<?php
/**
 * Page Clients Moderne
 * Version optimisée avec support complet du thème et de la navbar
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

// Fonction pour générer les URLs de tri (identique à l'original)
function getSortUrl($field) {
    global $sort_by, $sort_order, $search, $current_page;
    $new_order = ($sort_by === $field && $sort_order === 'ASC') ? 'DESC' : 'ASC';
    $params = ['page' => 'clients_moderne', 'sort' => $field, 'order' => $new_order]; // Note: page changed to clients_moderne
    if (!empty($search)) $params['search'] = $search;
    return 'index.php?' . http_build_query($params);
}

function getSortIcon($field) {
    global $sort_by, $sort_order;
    if ($sort_by !== $field) return '↕️';
    return $sort_order === 'ASC' ? '⬆️' : '⬇️';
}
?>

<!-- Import des variables CSS modernes -->
<style>
/* Variables CSS pour les thèmes */
:root {
    /* Mode Jour - Moderne Dynamique */
    --day-primary: #3b82f6;
    --day-secondary: #8b5cf6;
    --day-accent: #06b6d4;
    --day-bg: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    --day-card-bg: rgba(255, 255, 255, 0.95);
    --day-text: #1e293b;
    --day-text-light: #64748b;
    --day-shadow: rgba(59, 130, 246, 0.15);
    --day-border: rgba(148, 163, 184, 0.2);

    /* Mode Nuit - Futuriste */
    --night-primary: #00d4ff;
    --night-secondary: #7c3aed;
    --night-accent: #ff00aa;
    --night-bg: #0a0a0a;
    --night-card-bg: rgba(15, 15, 25, 0.95);
    --night-text: #ffffff;
    --night-text-light: #a0aec0;
    --night-shadow: rgba(0, 212, 255, 0.25);
    --night-border: rgba(0, 212, 255, 0.3);
    --night-glow: 0 0 20px rgba(0, 212, 255, 0.5);
}

/* Container principal avec padding pour la navbar */
.page-container {
    padding-top: 85px; /* Espace pour la navbar fixe */
    max-width: 1600px;
    margin: 0 auto;
    padding-left: 20px;
    padding-right: 20px;
}

/* En-tête de page */
.page-header-modern {
    background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%);
    color: white;
    padding: 40px;
    border-radius: 24px;
    margin-bottom: 40px;
    box-shadow: 0 10px 30px var(--day-shadow);
    position: relative;
    overflow: hidden;
}

.page-header-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
    z-index: 1;
}

.page-header-content {
    position: relative;
    z-index: 2;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0 0 10px 0;
    letter-spacing: -1px;
}

.page-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
    font-weight: 500;
}

/* Cartes de statistiques */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
    margin-bottom: 40px;
}

.stat-card-modern {
    background: var(--day-card-bg);
    padding: 25px;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    border: 1px solid var(--day-border);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    backdrop-filter: blur(10px);
}

.stat-card-modern:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--day-primary);
    margin-bottom: 5px;
    line-height: 1;
}

.stat-label {
    color: var(--day-text-light);
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Contrôles et Recherche */
.controls-bar {
    background: var(--day-card-bg);
    padding: 20px;
    border-radius: 16px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    border: 1px solid var(--day-border);
}

.search-wrapper {
    position: relative;
    flex: 1;
    max-width: 500px;
}

.search-input-modern {
    width: 100%;
    padding: 15px 50px 15px 20px;
    border-radius: 12px;
    border: 2px solid var(--day-border);
    background: #f8fafc;
    font-size: 1rem;
    color: var(--day-text);
    transition: all 0.3s ease;
}

.search-input-modern:focus {
    outline: none;
    border-color: var(--day-primary);
    background: white;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.search-icon {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--day-text-light);
    pointer-events: none;
}

/* Tableau Moderne */
.table-modern {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--day-card-bg);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    border: 1px solid var(--day-border);
}

.table-modern th {
    background: #f8fafc;
    padding: 20px;
    text-align: left;
    font-weight: 700;
    color: var(--day-text-light);
    border-bottom: 1px solid var(--day-border);
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-modern td {
    padding: 20px;
    background: transparent;
    border-bottom: 1px solid var(--day-border);
    color: var(--day-text);
    vertical-align: middle;
}

.table-modern tr:last-child td {
    border-bottom: none;
}

.table-modern tr:hover td {
    background: rgba(59, 130, 246, 0.02);
}

/* Badges et Boutons */
.badge-modern {
    padding: 6px 12px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 700;
}

.badge-blue { background: #e0f2fe; color: #0284c7; }
.badge-green { background: #dcfce7; color: #166534; }
.badge-yellow { background: #fef3c7; color: #92400e; }

.btn-modern {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    font-size: 0.95rem;
}

.btn-primary {
    background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
    color: white;
}

.btn-action {
    padding: 8px 16px;
    font-size: 0.9rem;
}

.btn-info { background: #e0f2fe; color: #0284c7; }
.btn-info:hover { background: #bae6fd; }

.btn-success { background: #dcfce7; color: #166534; }
.btn-success:hover { background: #bbf7d0; }

.btn-danger { background: #fee2e2; color: #991b1b; }
.btn-danger:hover { background: #fecaca; }

/* Pagination */
.pagination-modern {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 40px;
    margin-bottom: 40px;
}

.page-link-modern {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: white;
    color: var(--day-text);
    text-decoration: none;
    border: 1px solid var(--day-border);
    transition: all 0.2s ease;
    font-weight: 600;
}

.page-link-modern.active,
.page-link-modern:hover {
    background: var(--day-primary);
    color: white;
    border-color: var(--day-primary);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* === MODE SOMBRE === */
body.night-mode {
    background: var(--night-bg);
    color: var(--night-text);
}

body.night-mode .page-header-modern {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    border: 1px solid var(--night-border);
}

body.night-mode .stat-card-modern,
body.night-mode .controls-bar,
body.night-mode .table-modern,
body.night-mode .page-link-modern,
body.night-mode .search-input-modern {
    background: var(--night-card-bg);
    border-color: var(--night-border);
}

body.night-mode .stat-number { color: var(--night-primary); }
body.night-mode .stat-label { color: var(--night-text-light); }
body.night-mode .table-modern th {
    background: rgba(0, 212, 255, 0.05);
    color: var(--night-primary);
    border-bottom-color: var(--night-border);
}

body.night-mode .table-modern td,
body.night-mode .search-input-modern {
    color: var(--night-text);
    border-bottom-color: var(--night-border);
}

body.night-mode .badge-blue { background: rgba(3, 105, 161, 0.3); color: #7dd3fc; }
body.night-mode .badge-green { background: rgba(21, 128, 61, 0.3); color: #86efac; }
body.night-mode .badge-yellow { background: rgba(180, 83, 9, 0.3); color: #fcd34d; }

body.night-mode .btn-info { background: rgba(3, 105, 161, 0.3); color: #7dd3fc; }
body.night-mode .btn-success { background: rgba(21, 128, 61, 0.3); color: #86efac; }
body.night-mode .btn-danger { background: rgba(153, 27, 27, 0.3); color: #fca5a5; }

/* Responsive */
@media (max-width: 1200px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .page-container { padding-left: 15px; padding-right: 15px; }
    .stats-grid { grid-template-columns: 1fr; }
    .controls-bar { flex-direction: column; gap: 15px; }
    .search-wrapper { width: 100%; max-width: none; }
    .btn-modern { width: 100%; justify-content: center; }
    .table-container { overflow-x: auto; }
}
</style>

<div class="page-container">
    <!-- En-tête -->
    <div class="page-header-modern">
        <div class="page-header-content">
            <h1 class="page-title">👥 Gestion des Clients</h1>
            <p class="page-subtitle">Gérez votre base client et suivez l'historique des réparations</p>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card-modern">
            <div class="stat-number"><?php echo number_format($total_items); ?></div>
            <div class="stat-label">Total Clients</div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-number"><?php echo count(array_filter($clients, function($c) { return $c['nombre_reparations'] > 0; })); ?></div>
            <div class="stat-label">Clients Actifs</div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-number"><?php echo array_sum(array_column($clients, 'nombre_reparations')); ?></div>
            <div class="stat-label">Total Réparations</div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-number"><?php echo count(array_filter($clients, function($c) { return $c['nombre_reparations'] == 0; })); ?></div>
            <div class="stat-label">Nouveaux Clients</div>
        </div>
    </div>

    <!-- Contrôles -->
    <div class="controls-bar">
        <div class="search-wrapper">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="clients_moderne">
                <input type="text" class="search-input-modern" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Rechercher par nom, téléphone, email...">
                <span class="search-icon">🔍</span>
            </form>
        </div>
        <a href="index.php?page=ajouter_client" class="btn-modern btn-primary">
            ➕ Nouveau Client
        </a>
    </div>

    <!-- Tableau -->
    <div class="table-container">
        <?php if (empty($clients)): ?>
            <div style="text-align: center; padding: 60px; color: var(--day-text-light);">
                <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;">🔍</div>
                <h3>Aucun client trouvé</h3>
                <p>Aucun client ne correspond à votre recherche.</p>
            </div>
        <?php else: ?>
            <table class="table-modern">
                <thead>
                    <tr>
                        <th><a href="<?php echo getSortUrl('id'); ?>" style="color: inherit; text-decoration: none;">ID <?php echo getSortIcon('id'); ?></a></th>
                        <th><a href="<?php echo getSortUrl('nom'); ?>" style="color: inherit; text-decoration: none;">Nom <?php echo getSortIcon('nom'); ?></a></th>
                        <th><a href="<?php echo getSortUrl('prenom'); ?>" style="color: inherit; text-decoration: none;">Prénom <?php echo getSortIcon('prenom'); ?></a></th>
                        <th><a href="<?php echo getSortUrl('telephone'); ?>" style="color: inherit; text-decoration: none;">Téléphone <?php echo getSortIcon('telephone'); ?></a></th>
                        <th><a href="<?php echo getSortUrl('date_creation'); ?>" style="color: inherit; text-decoration: none;">Créé le <?php echo getSortIcon('date_creation'); ?></a></th>
                        <th><a href="<?php echo getSortUrl('nombre_reparations'); ?>" style="color: inherit; text-decoration: none;">Réparations <?php echo getSortIcon('nombre_reparations'); ?></a></th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--day-primary);">#<?php echo $client['id']; ?></td>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($client['nom']); ?></td>
                        <td><?php echo htmlspecialchars($client['prenom']); ?></td>
                        <td>
                            <?php if (!empty($client['telephone'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($client['telephone']); ?>" style="text-decoration: none; color: inherit; display: inline-flex; align-items: center; gap: 5px;">
                                    📞 <?php echo htmlspecialchars($client['telephone']); ?>
                                </a>
                            <?php else: ?>
                                <span style="opacity: 0.5;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($client['date_creation'])); ?></td>
                        <td>
                            <?php if ($client['nombre_reparations'] > 0): ?>
                                <span class="badge-modern badge-blue"><?php echo $client['nombre_reparations']; ?></span>
                            <?php else: ?>
                                <span class="badge-modern badge-yellow">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="showClientHistory('<?php echo $client['id']; ?>', '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>')" 
                                        class="btn-modern btn-action btn-info" title="Historique">
                                    📋
                                </button>
                                <button onclick="openSmsModal('<?php echo $client['id']; ?>', '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>', '<?php echo htmlspecialchars($client['telephone']); ?>')" 
                                        class="btn-modern btn-action btn-success" title="SMS">
                                    💬
                                </button>
                                <button onclick="confirmDelete('<?php echo $client['id']; ?>', '<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>')" 
                                        class="btn-modern btn-action btn-danger" title="Supprimer">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-modern">
        <?php if ($current_page > 1): ?>
            <a href="index.php?page=clients_moderne<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&p=<?php echo ($current_page - 1); ?>" class="page-link-modern">←</a>
        <?php endif; ?>

        <?php
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        for ($i = $start_page; $i <= $end_page; $i++):
        ?>
            <a href="index.php?page=clients_moderne<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&p=<?php echo $i; ?>" 
               class="page-link-modern <?php echo $i == $current_page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
            <a href="index.php?page=clients_moderne<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>&p=<?php echo ($current_page + 1); ?>" class="page-link-modern">→</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- MODALS (Integration complète) -->

<!-- Modal SMS -->
<div id="smsModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; padding:30px; border-radius:16px; width:90%; max-width:500px; box-shadow:0 20px 50px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0;">💬 Envoyer un SMS</h3>
        <p>À: <strong id="smsClientName"></strong> (<span id="smsClientPhone"></span>)</p>
        <textarea id="smsMessage" style="width:100%; height:100px; padding:10px; border:2px solid #e2e8f0; border-radius:8px; margin:10px 0;" placeholder="Votre message..."></textarea>
        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
            <button onclick="closeSmsModal()" class="btn-modern btn-danger">Annuler</button>
            <button onclick="sendSms()" class="btn-modern btn-success">Envoyer</button>
        </div>
    </div>
</div>

<!-- Modal Historique (Simplifié pour l'exemple mais fonctionnel) -->
<!-- Vous pouvez ajouter ici la structure complète du modal historique si nécessaire, 
     mais pour cet exemple j'ai gardé le JS qui charge le contenu dynamiquement -->
<div id="historiqueModal" class="modern-modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(5px);">
    <div style="background:white; width:90%; max-width:800px; height:80vh; border-radius:16px; display:flex; flex-direction:column; overflow:hidden;">
        <div style="padding:20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;">📋 Historique Client</h3>
            <button onclick="closeHistoriqueModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div id="historiqueContent" style="flex:1; padding:20px; overflow-y:auto;">
            Chargement...
        </div>
    </div>
</div>


<script>
// Scripts JS essentiels
function openSmsModal(id, name, phone) {
    document.getElementById('smsClientName').textContent = name;
    document.getElementById('smsClientPhone').textContent = phone;
    document.getElementById('smsModal').style.display = 'flex';
    // Stocker les infos pour l'envoi
    window.currentSmsClient = { id, phone };
}

function closeSmsModal() {
    document.getElementById('smsModal').style.display = 'none';
}

function sendSms() {
    const msg = document.getElementById('smsMessage').value;
    if(!msg) return alert('Veuillez écrire un message');
    
    // Simulation d'envoi (à remplacer par votre appel AJAX réel)
    const formData = new FormData();
    formData.append('telephone', window.currentSmsClient.phone);
    formData.append('message', msg);
    formData.append('client_id', window.currentSmsClient.id);
    
    fetch('ajax/send_sms.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(d => {
        if(d.success) {
            alert('SMS envoyé !');
            closeSmsModal();
            document.getElementById('smsMessage').value = '';
        } else {
            alert('Erreur: ' + d.message);
        }
    })
    .catch(e => alert('Erreur lors de l\'envoi'));
}

function showClientHistory(id, name) {
    document.getElementById('historiqueModal').style.display = 'flex';
    document.getElementById('historiqueContent').innerHTML = '<div style="text-align:center; padding:20px;">Chargement...</div>';
    
    fetch(`ajax/get_client_history.php?client_id=${id}`)
    .then(r => r.text())
    .then(html => {
        document.getElementById('historiqueContent').innerHTML = html;
    });
}

function closeHistoriqueModal() {
    document.getElementById('historiqueModal').style.display = 'none';
}

function confirmDelete(id, name) {
    if(confirm('Supprimer le client ' + name + ' ?')) {
        const fd = new FormData();
        fd.append('client_id', id);
        fetch('ajax/delete_client.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if(d.success) location.reload();
            else alert('Erreur: ' + d.message);
        });
    }
}
</script>
