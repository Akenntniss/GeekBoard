<?php
/**
 * Page de gestion des garanties
 */

// Définir la page actuelle pour le menu
$current_page = 'garanties';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo '<meta http-equiv="refresh" content="0;url=index.php">';
    exit();
}

// Initialiser la session shop
initializeShopSession();

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

try {
    $shop_pdo = getShopDBConnection();
    
    // Vérifier si le système de garantie est actif
    $stmt = $shop_pdo->prepare("SELECT valeur FROM parametres WHERE cle = 'garantie_active'");
    $stmt->execute();
    $garantie_active = $stmt->fetchColumn() === '1';
    
    if (!$garantie_active) {
        set_message("Le système de garantie n'est pas activé. Contactez l'administrateur.", "warning");
    }
    
} catch (PDOException $e) {
    set_message("Erreur lors de la vérification du système de garantie: " . $e->getMessage(), "danger");
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">
                            <i class="fas fa-shield-alt text-primary me-2"></i>
                            Gestion des Garanties
                        </h1>
                        <p class="page-subtitle">Suivi et gestion des garanties des réparations</p>
                    </div>
                    
                    <?php if ($is_admin): ?>
                    <div class="page-actions">
                        <a href="index.php?page=parametre#warranty" class="btn btn-outline-primary">
                            <i class="fas fa-cog"></i>
                            Paramètres
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$garantie_active): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Système de garantie désactivé</strong><br>
                Le système de garantie n'est actuellement pas actif. Les nouvelles réparations ne généreront pas automatiquement de garantie.
            </div>
            <?php endif; ?>

            <!-- Statistiques rapides -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card bg-success">
                        <div class="stat-icon">
                            <i class="fas fa-shield-check"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="total-active">-</div>
                            <div class="stat-label">Garanties actives</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-warning">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="total-expiring">-</div>
                            <div class="stat-label">Expirent bientôt</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-danger">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="total-expired">-</div>
                            <div class="stat-label">Expirées</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-info">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="total-claims">-</div>
                            <div class="stat-label">Réclamations</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="warranty-filters" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Statut</label>
                            <select class="form-select" id="filter-status">
                                <option value="">Tous les statuts</option>
                                <option value="active">Actives</option>
                                <option value="expiree">Expirées</option>
                                <option value="utilisee">Utilisées</option>
                                <option value="annulee">Annulées</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Période d'expiration</label>
                            <select class="form-select" id="filter-expiration">
                                <option value="">Toutes les périodes</option>
                                <option value="expired">Déjà expirées</option>
                                <option value="7">Expire dans 7 jours</option>
                                <option value="30">Expire dans 30 jours</option>
                                <option value="90">Expire dans 90 jours</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Client</label>
                            <input type="text" class="form-control" id="filter-client" placeholder="Nom du client">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Actions</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" onclick="loadWarranties()">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Liste des garanties -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Liste des garanties
                    </h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success btn-sm" onclick="exportWarranties()">
                            <i class="fas fa-download"></i>
                            Exporter
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="warranties-loading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Chargement des garanties...</p>
                    </div>
                    
                    <div id="warranties-table" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Appareil</th>
                                        <th>Date début</th>
                                        <th>Date fin</th>
                                        <th>Statut</th>
                                        <th>Jours restants</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="warranties-list">
                                    <!-- Les garanties seront chargées ici via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div id="warranties-pagination" class="d-flex justify-content-center mt-3">
                            <!-- Pagination sera générée ici -->
                        </div>
                    </div>
                    
                    <div id="warranties-empty" style="display: none;" class="text-center py-4">
                        <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                        <h5>Aucune garantie trouvée</h5>
                        <p class="text-muted">Il n'y a aucune garantie correspondant aux critères sélectionnés.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal détails garantie -->
<div class="modal fade" id="warrantyDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-shield-alt me-2"></i>
                    Détails de la garantie
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="warranty-details-content">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

<style>
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 600;
    margin: 0;
}

.page-subtitle {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border: none;
    transition: transform 0.2s;
    display: flex;
    align-items: center;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card.bg-success { border-left: 4px solid #10b981; }
.stat-card.bg-warning { border-left: 4px solid #f59e0b; }
.stat-card.bg-danger { border-left: 4px solid #ef4444; }
.stat-card.bg-info { border-left: 4px solid #06b6d4; }

.stat-icon {
    font-size: 2.5rem;
    margin-right: 1rem;
    opacity: 0.8;
}

.stat-card.bg-success .stat-icon { color: #10b981; }
.stat-card.bg-warning .stat-icon { color: #f59e0b; }
.stat-card.bg-danger .stat-icon { color: #ef4444; }
.stat-card.bg-info .stat-icon { color: #06b6d4; }

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
}

.stat-label {
    color: #6b7280;
    font-size: 0.9rem;
    margin: 0;
}

.badge-status {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-active { background-color: #d1fae5; color: #065f46; }
.badge-expiring { background-color: #fef3c7; color: #92400e; }
.badge-expired { background-color: #fee2e2; color: #991b1b; }
.badge-used { background-color: #e0e7ff; color: #3730a3; }
.badge-cancelled { background-color: #f3f4f6; color: #374151; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadWarrantyStats();
    loadWarranties();
});

// Charger les statistiques
function loadWarrantyStats() {
    fetch('../ajax/warranty_stats.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('total-active').textContent = data.data.active || 0;
            document.getElementById('total-expiring').textContent = data.data.expiring || 0;
            document.getElementById('total-expired').textContent = data.data.expired || 0;
            document.getElementById('total-claims').textContent = data.data.claims || 0;
        }
    })
    .catch(error => console.error('Erreur stats:', error));
}

// Charger la liste des garanties
function loadWarranties() {
    const loading = document.getElementById('warranties-loading');
    const table = document.getElementById('warranties-table');
    const empty = document.getElementById('warranties-empty');
    
    loading.style.display = 'block';
    table.style.display = 'none';
    empty.style.display = 'none';
    
    // Récupérer les filtres
    const filters = {
        status: document.getElementById('filter-status').value,
        expiration: document.getElementById('filter-expiration').value,
        client: document.getElementById('filter-client').value
    };
    
    const params = new URLSearchParams(filters);
    
    fetch(`../ajax/warranties_list.php?${params}`)
    .then(response => response.json())
    .then(data => {
        loading.style.display = 'none';
        
        if (data.success && data.data.length > 0) {
            displayWarranties(data.data);
            table.style.display = 'block';
        } else {
            empty.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Erreur chargement garanties:', error);
        loading.style.display = 'none';
        empty.style.display = 'block';
    });
}

// Afficher les garanties dans le tableau
function displayWarranties(warranties) {
    const tbody = document.getElementById('warranties-list');
    tbody.innerHTML = '';
    
    warranties.forEach(warranty => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>#${warranty.garantie_id}</td>
            <td>
                <strong>${warranty.nom} ${warranty.prenom}</strong><br>
                <small class="text-muted">${warranty.telephone}</small>
            </td>
            <td>
                <strong>${warranty.type_appareil}</strong><br>
                <small class="text-muted">${warranty.modele}</small>
            </td>
            <td>${formatDate(warranty.date_debut)}</td>
            <td>${formatDate(warranty.date_fin)}</td>
            <td>
                <span class="badge badge-${getStatusClass(warranty.statut_garantie)}">
                    ${getStatusLabel(warranty.statut_garantie)}
                </span>
            </td>
            <td>
                <span class="badge badge-${getExpirationClass(warranty.jours_restants)}">
                    ${warranty.jours_restants >= 0 ? warranty.jours_restants + ' jours' : 'Expirée'}
                </span>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="viewWarrantyDetails(${warranty.garantie_id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-success" onclick="printWarranty(${warranty.garantie_id})">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Fonctions utilitaires
function getStatusClass(status) {
    const classes = {
        'active': 'active',
        'expiree': 'expired',
        'utilisee': 'used',
        'annulee': 'cancelled'
    };
    return classes[status] || 'active';
}

function getStatusLabel(status) {
    const labels = {
        'active': 'Active',
        'expiree': 'Expirée',
        'utilisee': 'Utilisée',
        'annulee': 'Annulée'
    };
    return labels[status] || status;
}

function getExpirationClass(days) {
    if (days < 0) return 'expired';
    if (days <= 7) return 'expiring';
    return 'active';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR');
}

// Actions
function viewWarrantyDetails(warrantyId) {
    // Charger les détails de la garantie dans le modal
    fetch(`../ajax/warranty_details.php?id=${warrantyId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('warranty-details-content').innerHTML = data.html;
            new bootstrap.Modal(document.getElementById('warrantyDetailsModal')).show();
        }
    })
    .catch(error => console.error('Erreur détails garantie:', error));
}

function printWarranty(warrantyId) {
    window.open(`../print/warranty.php?id=${warrantyId}`, '_blank');
}

function exportWarranties() {
    const filters = {
        status: document.getElementById('filter-status').value,
        expiration: document.getElementById('filter-expiration').value,
        client: document.getElementById('filter-client').value
    };
    
    const params = new URLSearchParams(filters);
    window.open(`../export/warranties.php?${params}`, '_blank');
}

function resetFilters() {
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-expiration').value = '';
    document.getElementById('filter-client').value = '';
    loadWarranties();
}
</script>

