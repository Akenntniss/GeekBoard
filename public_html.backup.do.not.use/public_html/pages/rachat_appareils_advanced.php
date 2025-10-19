<?php
// Vérifier si la session est déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la configuration des sous-domaines pour la détection automatique du magasin
require_once __DIR__ . '/../config/subdomain_config.php';

// Aucune restriction d'accès - tous les utilisateurs peuvent accéder à cette page
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

// Utiliser la fonction système pour respecter le multi-magasin
$shop_pdo = getShopDBConnection();

// Vérifier la connexion à la base de données
if (!$shop_pdo) {
    // Si aucun shop_id n'est défini, rediriger vers la page de connexion
    if (!isset($_SESSION['shop_id'])) {
        echo '<div class="container-fluid mt-4">
            <div class="alert alert-warning">
                <h4><i class="fas fa-exclamation-triangle me-2"></i>Accès requis</h4>
                <p>Vous devez être connecté à un magasin pour accéder à cette page.</p>
                <a href="../index.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                </a>
            </div>
        </div>';
        exit;
    }
    
    die("Erreur de connexion à la base de données du magasin");
}

// Récupérer les statistiques
$stats = [];
try {
    // Total des rachats
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM rachat_appareils");
    $stats['total'] = $stmt->fetch()['total'];
    
    // Rachats ce mois
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM rachat_appareils WHERE MONTH(date_rachat) = MONTH(CURRENT_DATE()) AND YEAR(date_rachat) = YEAR(CURRENT_DATE())");
    $stats['mois'] = $stmt->fetch()['total'];
    
    // Montant total
    $stmt = $shop_pdo->query("SELECT SUM(prix) as total FROM rachat_appareils");
    $stats['montant'] = $stmt->fetch()['total'] ?? 0;
    
    // Appareils fonctionnels
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM rachat_appareils WHERE fonctionnel = 1");
    $stats['fonctionnels'] = $stmt->fetch()['total'];
    
    // Appareils non fonctionnels
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM rachat_appareils WHERE fonctionnel = 0");
    $stats['non_fonctionnels'] = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    $stats = ['total' => 0, 'mois' => 0, 'montant' => 0, 'fonctionnels' => 0, 'non_fonctionnels' => 0];
}

// Récupérer les clients pour les filtres
$clients = [];
try {
    $stmt = $shop_pdo->query("SELECT DISTINCT c.nom, c.prenom FROM rachat_appareils r JOIN clients c ON r.client_id = c.id ORDER BY c.nom");
    $clients_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($clients_data as $client) {
        $clients[] = $client['nom'] . ' ' . $client['prenom'];
    }
} catch (Exception $e) {
    $clients = [];
}

// Récupérer les modèles pour les filtres
$modeles = [];
try {
    $stmt = $shop_pdo->query("SELECT DISTINCT modele FROM rachat_appareils ORDER BY modele");
    $modeles = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $modeles = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Avancée des Rachats - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stats-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stats-card.info {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        }
        .stats-card.danger {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        .filters-panel {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .sortable {
            cursor: pointer;
            user-select: none;
        }
        .sortable:hover {
            background-color: #f8f9fa;
        }
        .bulk-actions {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
        }
        .bulk-actions.show {
            display: block;
        }
        .loader {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .image-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="fas fa-mobile-alt me-2"></i>
                    Gestion Avancée des Rachats
                </h2>
                
                <!-- Statistiques -->
                <div class="row">
                    <div class="col-md-2">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= number_format($stats['total']) ?></h3>
                                    <small>Total Rachats</small>
                                </div>
                                <i class="fas fa-chart-line fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= number_format($stats['mois']) ?></h3>
                                    <small>Ce Mois</small>
                                </div>
                                <i class="fas fa-calendar-alt fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stats-card warning">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= number_format($stats['montant'], 2) ?>€</h3>
                                    <small>Montant Total</small>
                                </div>
                                <i class="fas fa-euro-sign fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= number_format($stats['fonctionnels']) ?></h3>
                                    <small>Fonctionnels</small>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card danger">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= number_format($stats['non_fonctionnels']) ?></h3>
                                    <small>Non Fonctionnels</small>
                                </div>
                                <i class="fas fa-times-circle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="filters-panel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-filter me-2"></i>
                            Filtres Avancés
                        </h5>
                        <button class="btn btn-outline-primary btn-sm" id="toggleFilters">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    
                    <div id="filtersContent">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Client</label>
                                <select class="form-select" id="filterClient">
                                    <option value="">Tous les clients</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= htmlspecialchars($client) ?>"><?= htmlspecialchars($client) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Modèle</label>
                                <select class="form-select" id="filterModele">
                                    <option value="">Tous les modèles</option>
                                    <?php foreach ($modeles as $modele): ?>
                                        <option value="<?= htmlspecialchars($modele) ?>"><?= htmlspecialchars($modele) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">État</label>
                                <select class="form-select" id="filterEtat">
                                    <option value="">Tous les états</option>
                                    <option value="fonctionnel">Fonctionnel</option>
                                    <option value="non_fonctionnel">Non fonctionnel</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date début</label>
                                <input type="date" class="form-control" id="filterDateDebut">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date fin</label>
                                <input type="date" class="form-control" id="filterDateFin">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label class="form-label">Prix min</label>
                                <input type="number" class="form-control" id="filterPrixMin" placeholder="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Prix max</label>
                                <input type="number" class="form-control" id="filterPrixMax" placeholder="1000">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button class="btn btn-primary me-2" id="applyFilters">
                                    <i class="fas fa-search"></i> Appliquer
                                </button>
                                <button class="btn btn-outline-secondary" id="clearFilters">
                                    <i class="fas fa-times"></i> Effacer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recherche rapide -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="searchRachat" placeholder="Recherche rapide...">
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRachatModal">
                            <i class="fas fa-plus me-2"></i>
                            Nouveau Rachat
                        </button>
                    </div>
                </div>

                <!-- Actions en lot -->
                <div class="bulk-actions" id="bulkActions">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-check-square me-2"></i>
                            <span id="selectedCount">0</span> élément(s) sélectionné(s)
                        </span>
                        <div>
                            <button class="btn btn-primary btn-sm" id="exportSelected">
                                <i class="fas fa-file-pdf"></i> Exporter PDF
                            </button>
                            <button class="btn btn-danger btn-sm" id="deleteSelected">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loader -->
                <div class="loader" id="loader">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>

                <!-- Tableau -->
                <div class="table-container">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th class="sortable" data-sort="date_rachat">
                                    Date <i class="fas fa-sort ms-1"></i>
                                </th>
                                <th class="sortable" data-sort="nom_client">
                                    Client <i class="fas fa-sort ms-1"></i>
                                </th>
                                <th class="sortable" data-sort="modele">
                                    Modèle <i class="fas fa-sort ms-1"></i>
                                </th>
                                <th class="sortable" data-sort="etat">
                                    État <i class="fas fa-sort ms-1"></i>
                                </th>
                                <th class="sortable" data-sort="prix_rachat">
                                    Prix <i class="fas fa-sort ms-1"></i>
                                </th>
                                <th>Image</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rachatsList">
                            <!-- Contenu chargé par AJAX -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <span class="text-muted" id="paginationInfo">Chargement...</span>
                    </div>
                    <nav>
                        <ul class="pagination mb-0" id="pagination">
                            <!-- Pagination générée par JavaScript -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour les détails -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails du Rachat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Contenu chargé par AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour nouveau rachat -->
    <div class="modal fade" id="newRachatModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau Rachat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Pour créer un nouveau rachat, veuillez utiliser la page dédiée à la saisie des rachats.
                    </div>
                    <div class="text-center">
                        <a href="../pages/rachat_appareils.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Aller à la page de saisie
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Librairies pour la génération de PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script>
        // Variables globales
        let currentPage = 1;
        let itemsPerPage = 10;
        let currentSort = 'date_rachat';
        let sortDirection = 'desc';
        let currentFilters = {};

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadRachats();
            initializeEventListeners();
        });

        // Initialiser les écouteurs d'événements
        function initializeEventListeners() {
            // Toggle des filtres
            document.getElementById('toggleFilters').addEventListener('click', function() {
                const content = document.getElementById('filtersContent');
                const icon = this.querySelector('i');
                
                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                } else {
                    content.style.display = 'none';
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
            });

            // Appliquer les filtres
            document.getElementById('applyFilters').addEventListener('click', function() {
                applyFilters();
            });

            // Effacer les filtres
            document.getElementById('clearFilters').addEventListener('click', function() {
                clearFilters();
            });

            // Recherche rapide
            let searchTimeout;
            document.getElementById('searchRachat').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentFilters.search = this.value;
                    currentPage = 1;
                    loadRachats();
                }, 500);
            });

            // Tri des colonnes
            document.querySelectorAll('.sortable').forEach(header => {
                header.addEventListener('click', function() {
                    const sortField = this.dataset.sort;
                    
                    if (currentSort === sortField) {
                        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSort = sortField;
                        sortDirection = 'asc';
                    }
                    
                    updateSortIndicators();
                    loadRachats();
                });
            });

            // Sélection multiple
            document.getElementById('selectAll').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBulkActions();
            });

            // Actions en lot
            document.getElementById('exportSelected').addEventListener('click', () => exportSelected());
            document.getElementById('deleteSelected').addEventListener('click', () => deleteSelected());
        }

        // Appliquer les filtres
        function applyFilters() {
            currentFilters = {
                client: document.getElementById('filterClient').value,
                modele: document.getElementById('filterModele').value,
                etat: document.getElementById('filterEtat').value,
                dateDebut: document.getElementById('filterDateDebut').value,
                dateFin: document.getElementById('filterDateFin').value,
                prixMin: document.getElementById('filterPrixMin').value,
                prixMax: document.getElementById('filterPrixMax').value
            };
            
            currentPage = 1;
            loadRachats();
        }

        // Effacer les filtres
        function clearFilters() {
            document.getElementById('filterClient').value = '';
            document.getElementById('filterModele').value = '';
            document.getElementById('filterEtat').value = '';
            document.getElementById('filterDateDebut').value = '';
            document.getElementById('filterDateFin').value = '';
            document.getElementById('filterPrixMin').value = '';
            document.getElementById('filterPrixMax').value = '';
            
            currentFilters = {};
            currentPage = 1;
            loadRachats();
        }

        // Charger les rachats avec AJAX
        function loadRachats() {
            document.getElementById('loader').style.display = 'block';
            
            const formData = new FormData();
            formData.append('page', currentPage);
            formData.append('limit', itemsPerPage);
            formData.append('sort', currentSort);
            formData.append('direction', sortDirection);
            
            // Ajouter les filtres
            Object.keys(currentFilters).forEach(key => {
                if (currentFilters[key]) {
                    formData.append(key, currentFilters[key]);
                }
            });

            fetch('/ajax/recherche_rachat_advanced.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loader').style.display = 'none';
                
                if (data.success) {
                    updateTable(data.data);
                    updatePagination(data.pagination);
                    updatePaginationInfo(data.pagination);
                } else {
                    showError(data.error || 'Erreur lors du chargement des données');
                }
            })
            .catch(error => {
                document.getElementById('loader').style.display = 'none';
                console.error('Erreur:', error);
            });
        }

        // Mettre à jour le tableau
        function updateTable(data) {
            const tbody = document.getElementById('rachatsList');
            
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Aucun résultat trouvé</td></tr>';
                return;
            }

            tbody.innerHTML = data.map(rachat => `
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input row-checkbox" value="${rachat.id}" onchange="updateBulkActions()">
                    </td>
                    <td>${formatDate(rachat.date_rachat)}</td>
                    <td>${rachat.nom_client}</td>
                    <td>${rachat.modele || 'N/A'}</td>
                    <td>
                        <span class="badge ${rachat.etat === 'fonctionnel' ? 'bg-success' : 'bg-warning'}">
                            ${rachat.etat === 'fonctionnel' ? 'Fonctionnel' : 'Non fonctionnel'}
                        </span>
                    </td>
                    <td>${formatPrice(rachat.prix || 0)}</td>
                    <td>
                        ${rachat.photo_appareil ? `<img src="${getImageSrc(rachat.photo_appareil)}" class="image-thumbnail" onclick="viewImage('${getImageSrc(rachat.photo_appareil)}', '${rachat.modele || 'Appareil'}')" alt="Image" style="max-width: 50px; max-height: 50px; cursor: pointer;">` : '<span class="text-muted">Aucune</span>'}
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-info btn-sm" onclick="viewDetails(${rachat.id})" title="Voir les détails">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-success btn-sm" onclick="exportAttestation(${rachat.id})" title="Exporter l'attestation">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteRachat(${rachat.id})" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        // Mettre à jour la pagination
        function updatePagination(pagination) {
            const paginationEl = document.getElementById('pagination');
            let html = '';
            
            // Bouton précédent
            if (pagination.current_page > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})">Précédent</a></li>`;
            }
            
            // Pages
            for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.total_pages, pagination.current_page + 2); i++) {
                html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                </li>`;
            }
            
            // Bouton suivant
            if (pagination.current_page < pagination.total_pages) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})">Suivant</a></li>`;
            }
            
            paginationEl.innerHTML = html;
        }

        // Mettre à jour les informations de pagination
        function updatePaginationInfo(pagination) {
            const info = document.getElementById('paginationInfo');
            const start = (pagination.current_page - 1) * itemsPerPage + 1;
            const end = Math.min(start + itemsPerPage - 1, pagination.total_results);
            
            info.textContent = `Affichage de ${start} à ${end} sur ${pagination.total_results} éléments`;
        }

        // Changer de page
        function changePage(page) {
            currentPage = page;
            loadRachats();
        }

        // Mettre à jour les indicateurs de tri
        function updateSortIndicators() {
            document.querySelectorAll('.sortable').forEach(header => {
                const icon = header.querySelector('i');
                icon.className = 'fas fa-sort ms-1';
                
                if (header.dataset.sort === currentSort) {
                    icon.className = `fas fa-sort-${sortDirection === 'asc' ? 'up' : 'down'} ms-1`;
                }
            });
        }

        // Mettre à jour les actions en lot
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.row-checkbox:checked');
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            
            if (checkboxes.length > 0) {
                selectedCount.textContent = checkboxes.length;
                bulkActions.classList.add('show');
            } else {
                bulkActions.classList.remove('show');
            }
        }

        // Fonctions utilitaires
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR');
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        }

        function getImageSrc(imagePath) {
            if (!imagePath) return '';
            
            // Si c'est déjà une image base64, la retourner telle quelle
            if (imagePath.startsWith('data:')) {
                return imagePath;
            }
            
            // Sinon, construire le chemin vers l'image
            return '/assets/images/rachat/' + imagePath;
        }

        function showError(message) {
            alert('Erreur: ' + message);
        }

        // Fonctions d'export
        function exportData(format) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/ajax/export_multiple.php';
            
            const formatInput = document.createElement('input');
            formatInput.type = 'hidden';
            formatInput.name = 'format';
            formatInput.value = format;
            form.appendChild(formatInput);
            
            // Ajouter les filtres actuels
            Object.keys(currentFilters).forEach(key => {
                if (currentFilters[key]) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = currentFilters[key];
                    form.appendChild(input);
                }
            });
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function exportSelected() {
            const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            
            if (selected.length === 0) {
                showToast('Veuillez sélectionner au moins un élément', 'warning');
                return;
            }

            // Afficher un indicateur de chargement
            const button = document.getElementById('exportSelected');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';
            button.disabled = true;

            // Envoyer la requête AJAX pour générer les PDFs
            fetch('/ajax/export_multiple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    ids: JSON.stringify(selected)
                })
            })
            .then(response => response.json())
            .then(data => {
                // Restaurer le bouton
                button.innerHTML = originalText;
                button.disabled = false;

                if (data.success) {
                    // Générer les PDFs côté client avec html2canvas + jsPDF
                    if (data.type === 'single') {
                        // Un seul PDF
                        generatePDFFromHTML(data.files[0].html, data.files[0].filename);
                        showToast('Attestation PDF générée avec succès !', 'success');
                    } else {
                        // Plusieurs PDFs
                        generateMultiplePDFs(data.files);
                        showToast(`${data.count} attestations PDF générées avec succès !`, 'success');
                    }
                    
                    // Afficher les avertissements s'il y en a
                    if (data.warnings && data.warnings.length > 0) {
                        showToast('Avertissements: ' + data.warnings.join(', '), 'warning');
                    }
                } else {
                    showToast('Erreur lors de la génération des PDFs: ' + data.error, 'error');
                }
            })
            .catch(error => {
                // Restaurer le bouton
                button.innerHTML = originalText;
                button.disabled = false;
                
                console.error('Erreur:', error);
                showToast('Erreur lors de la génération des PDFs: ' + error.message, 'error');
            });
        }

        function downloadFile(url, filename) {
            // Créer un élément <a> invisible pour déclencher le téléchargement
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            link.style.display = 'none';
            
            // Ajouter l'élément au DOM, cliquer dessus, puis le supprimer
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function generatePDFFromHTML(html, filename) {
            // Créer un élément temporaire pour le HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            tempDiv.style.top = '-9999px';
            document.body.appendChild(tempDiv);
            
            // Utiliser html2canvas pour convertir en image puis en PDF
            html2canvas(tempDiv, {
                scale: 2,
                useCORS: true,
                allowTaint: true
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                
                // Créer le PDF avec jsPDF
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');
                
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();
                const imgWidth = canvas.width;
                const imgHeight = canvas.height;
                
                const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
                const finalWidth = imgWidth * ratio;
                const finalHeight = imgHeight * ratio;
                
                pdf.addImage(imgData, 'PNG', 0, 0, finalWidth, finalHeight);
                pdf.save(filename);
                
                // Nettoyer
                document.body.removeChild(tempDiv);
            }).catch(error => {
                console.error('Erreur lors de la génération du PDF:', error);
                showToast('Erreur lors de la génération du PDF', 'error');
                document.body.removeChild(tempDiv);
            });
        }

        function generateMultiplePDFs(files) {
            if (files.length === 1) {
                generatePDFFromHTML(files[0].html, files[0].filename);
                return;
            }

            // Pour plusieurs fichiers, créer un ZIP contenant tous les PDFs
            const zip = new JSZip();
            let processedCount = 0;

            files.forEach((file, index) => {
                // Créer un élément temporaire pour le HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = file.html;
                tempDiv.style.position = 'absolute';
                tempDiv.style.left = '-9999px';
                tempDiv.style.top = '-9999px';
                document.body.appendChild(tempDiv);
                
                // Utiliser html2canvas pour convertir en image puis en PDF
                html2canvas(tempDiv, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true
                }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    
                    // Créer le PDF avec jsPDF
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = pdf.internal.pageSize.getHeight();
                    const imgWidth = canvas.width;
                    const imgHeight = canvas.height;
                    
                    const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
                    const finalWidth = imgWidth * ratio;
                    const finalHeight = imgHeight * ratio;
                    
                    pdf.addImage(imgData, 'PNG', 0, 0, finalWidth, finalHeight);
                    
                    // Ajouter le PDF au ZIP
                    const pdfBlob = pdf.output('blob');
                    zip.file(file.filename, pdfBlob);
                    
                    // Nettoyer
                    document.body.removeChild(tempDiv);
                    
                    processedCount++;
                    
                    // Si tous les PDFs ont été traités, télécharger le ZIP
                    if (processedCount === files.length) {
                        zip.generateAsync({type: 'blob'}).then(function(content) {
                            const link = document.createElement('a');
                            link.href = URL.createObjectURL(content);
                            link.download = `attestations_rachat_${new Date().toISOString().split('T')[0]}.zip`;
                            link.style.display = 'none';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        });
                    }
                }).catch(error => {
                    console.error('Erreur lors de la génération du PDF:', error);
                    showToast('Erreur lors de la génération du PDF ' + file.filename, 'error');
                    document.body.removeChild(tempDiv);
                });
            });
        }

        function deleteSelected() {
            const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            
            if (selected.length === 0) {
                showToast('Veuillez sélectionner au moins un élément', 'warning');
                return;
            }

            if (confirm(`Êtes-vous sûr de vouloir supprimer ${selected.length} élément(s) ?`)) {
                fetch('/ajax/delete_multiple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ids: selected})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadRachats();
                        document.getElementById('selectAll').checked = false;
                        updateBulkActions();
                    } else {
                        alert('Erreur lors de la suppression: ' + data.error);
                    }
                });
            }
        }

        // Fonctions pour les actions individuelles
        function viewDetails(id) {
            fetch(`/ajax/details_rachat.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Erreur: ' + data.error);
                        return;
                    }
                    
                    document.getElementById('detailsContent').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Informations client</h6>
                                <p><strong>Nom:</strong> ${data.nom_client}</p>
                                <p><strong>Téléphone:</strong> ${data.telephone_client}</p>
                                <p><strong>Email:</strong> ${data.email_client}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Informations appareil</h6>
                                <p><strong>Modèle:</strong> ${data.modele}</p>
                                <p><strong>État:</strong> ${data.etat}</p>
                                <p><strong>Prix:</strong> ${formatPrice(data.prix_rachat)}</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6>Commentaires</h6>
                                <p>${data.commentaires || 'Aucun commentaire'}</p>
                            </div>
                        </div>
                    `;
                    
                    new bootstrap.Modal(document.getElementById('detailsModal')).show();
                });
        }

        function viewImage(imagePath, title) {
            if (!imagePath) {
                alert('Image non disponible');
                return;
            }
            
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${imagePath}" class="img-fluid" alt="${title}">
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modal);
            });
        }

        // Fonction simple pour afficher des messages
        function showToast(message, type = 'info') {
            // Créer un toast Bootstrap simple
            const toastContainer = document.getElementById('toastContainer') || createToastContainer();
            const toastId = 'toast-' + Date.now();
            
            const toastClass = {
                'success': 'text-bg-success',
                'error': 'text-bg-danger',
                'info': 'text-bg-info',
                'warning': 'text-bg-warning'
            }[type] || 'text-bg-info';
            
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center ${toastClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Supprimer le toast après 5 secondes
            setTimeout(() => {
                if (toastElement) {
                    toastElement.remove();
                }
            }, 5000);
        }
        
        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }

        function exportAttestation(id) {
            // Afficher un indicateur de chargement
            showToast('Génération de l\'attestation en cours...', 'info');
            
            // Faire l'appel AJAX pour récupérer le HTML
            fetch(`/ajax/export_attestation.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Créer un élément temporaire pour le HTML
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = data.html;
                        tempDiv.style.position = 'absolute';
                        tempDiv.style.left = '-9999px';
                        tempDiv.style.top = '-9999px';
                        document.body.appendChild(tempDiv);
                        
                        // Utiliser html2canvas pour convertir en image puis en PDF
                        html2canvas(tempDiv, {
                            scale: 2,
                            useCORS: true,
                            allowTaint: true
                        }).then(canvas => {
                            const imgData = canvas.toDataURL('image/png');
                            
                            // Créer le PDF avec jsPDF
                            const { jsPDF } = window.jspdf;
                            const pdf = new jsPDF('p', 'mm', 'a4');
                            
                            const pdfWidth = pdf.internal.pageSize.getWidth();
                            const pdfHeight = pdf.internal.pageSize.getHeight();
                            const imgWidth = canvas.width;
                            const imgHeight = canvas.height;
                            
                            const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
                            const finalWidth = imgWidth * ratio;
                            const finalHeight = imgHeight * ratio;
                            
                            pdf.addImage(imgData, 'PNG', 0, 0, finalWidth, finalHeight);
                            pdf.save(`attestation_rachat_${id}.pdf`);
                            
                            // Nettoyer
                            document.body.removeChild(tempDiv);
                            showToast('Attestation générée avec succès!', 'success');
                        }).catch(error => {
                            console.error('Erreur lors de la génération du PDF:', error);
                            document.body.removeChild(tempDiv);
                            showToast('Erreur lors de la génération du PDF', 'error');
                        });
                    } else {
                        showToast(data.error || 'Erreur lors de la récupération des données', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showToast('Erreur lors de la génération de l\'attestation', 'error');
                });
        }

        function deleteRachat(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce rachat ?')) {
                fetch('/ajax/delete_rachat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({id: id})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadRachats();
                    } else {
                        alert('Erreur lors de la suppression: ' + data.error);
                    }
                });
            }
        }
    </script>
</body>
</html>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 