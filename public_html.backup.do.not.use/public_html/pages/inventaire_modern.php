<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Récupérer les produits avec gestion d'erreur
try {
    $shop_pdo = getShopDBConnection();
    
    // Vérifier si la colonne suivre_stock existe
    $stmt = $shop_pdo->query("SHOW COLUMNS FROM produits LIKE 'suivre_stock'");
    $has_suivre_stock = $stmt->rowCount() > 0;
    
    $sql = "SELECT p.* ";
    if ($has_suivre_stock) {
        $sql .= ", p.suivre_stock ";
    }
    $sql .= "FROM produits p ORDER BY p.nom ASC";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute();
    $produits = $stmt->fetchAll();
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des produits: " . $e->getMessage(), 'danger');
    $produits = [];
    $has_suivre_stock = false;
}

// Calculer les statistiques
$total_produits = count($produits);
$produits_en_alerte = array_filter($produits, function($p) { return $p['quantite'] <= $p['seuil_alerte'] && $p['quantite'] > 0; });
$produits_epuises = array_filter($produits, function($p) { return $p['quantite'] == 0; });
$produits_suivis = $has_suivre_stock ? array_filter($produits, function($p) { return isset($p['suivre_stock']) && $p['suivre_stock'] == 1; }) : [];

$stats = [
    'total' => $total_produits,
    'alerte' => count($produits_en_alerte),
    'epuises' => count($produits_epuises),
    'stock' => $total_produits - count($produits_epuises),
    'suivis' => count($produits_suivis)
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaire - GeekBoard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --light: #f8fafc;
            --dark: #1e293b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --radius: 0.5rem;
            --radius-lg: 1rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--dark);
        }

        .title i {
            color: var(--primary);
        }

        .actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .btn-info:hover {
            background: #0891b2;
            transform: translateY(-1px);
        }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .stat-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
        }

        .stat-icon.primary { background: rgba(79, 70, 229, 0.1); color: var(--primary); }
        .stat-icon.success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .stat-icon.warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .stat-icon.danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .stat-icon.info { background: rgba(6, 182, 212, 0.1); color: var(--info); }

        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
        }

        /* Search & Filters */
        .controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.875rem;
            background: white;
            position: relative;
        }

        .search-wrapper {
            position: relative;
            flex: 1;
            min-width: 300px;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }

        .filter-select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.875rem;
            background: white;
            cursor: pointer;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: var(--light);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 1px solid var(--border);
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: var(--light);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .badge-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); }

        .actions-cell {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem;
            font-size: 0.75rem;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
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
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            max-width: 90vw;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
        }

        .modal-close:hover {
            background: var(--light);
            color: var(--dark);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.875rem;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        /* Loading */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #64748b;
        }

        .spinner {
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }

        /* Stock cards */
        .stock-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .stock-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .stock-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .stock-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .stock-card-title {
            font-weight: 600;
            color: var(--dark);
        }

        .stock-card-ref {
            font-size: 0.75rem;
            color: #64748b;
            background: var(--light);
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius);
        }

        .stock-card-quantity {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .controls {
                flex-direction: column;
            }

            .search-wrapper {
                min-width: auto;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }

            .actions {
                justify-content: stretch;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 class="title">
                <i class="fas fa-boxes"></i>
                Inventaire
            </h1>
            <div class="actions">
                <button class="btn btn-success" onclick="openScanner()">
                    <i class="fas fa-barcode"></i>
                    Scanner
                </button>
                <button class="btn btn-info" onclick="openStockCheck()">
                    <i class="fas fa-eye"></i>
                    Vérifier Stock
                </button>
                <button class="btn btn-primary" onclick="openAddProduct()">
                    <i class="fas fa-plus"></i>
                    Nouveau Produit
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon primary">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Produits</div>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">En Stock</div>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['stock']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="stat-label">En Alerte</div>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['alerte']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Épuisés</div>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['epuises']; ?></div>
            </div>

            <?php if ($has_suivre_stock): ?>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon info">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div>
                        <div class="stat-label">Suivis</div>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['suivis']; ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Controls -->
        <div class="controls">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="Rechercher par nom ou référence...">
            </div>
            <select class="filter-select" id="filterSelect">
                <option value="all">Tous les produits</option>
                <option value="stock">En stock</option>
                <option value="alerte">En alerte</option>
                <option value="epuise">Épuisés</option>
                <?php if ($has_suivre_stock): ?>
                <option value="suivis">Suivis</option>
                <?php endif; ?>
            </select>
            <button class="btn btn-info" onclick="exportInventory()">
                <i class="fas fa-download"></i>
                Exporter
            </button>
        </div>

        <!-- Products Table -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Nom</th>
                        <th>Prix Achat</th>
                        <th>Prix Vente</th>
                        <th>Stock</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productsTable">
                    <?php foreach ($produits as $produit): ?>
                    <tr data-product-id="<?php echo $produit['id']; ?>" 
                        data-nom="<?php echo strtolower($produit['nom']); ?>" 
                        data-reference="<?php echo strtolower($produit['reference']); ?>"
                        data-quantite="<?php echo $produit['quantite']; ?>"
                        data-seuil="<?php echo $produit['seuil_alerte']; ?>"
                        data-suivi="<?php echo $has_suivre_stock && isset($produit['suivre_stock']) ? $produit['suivre_stock'] : '0'; ?>">
                        <td>
                            <code><?php echo htmlspecialchars($produit['reference']); ?></code>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($produit['nom']); ?></strong>
                            <?php if (!empty($produit['description'])): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($produit['description'], 0, 50)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($produit['prix_achat'], 2); ?>€</td>
                        <td><?php echo number_format($produit['prix_vente'], 2); ?>€</td>
                        <td>
                            <strong><?php echo $produit['quantite']; ?></strong>
                            <?php if ($has_suivre_stock && isset($produit['suivre_stock']) && $produit['suivre_stock']): ?>
                            <i class="fas fa-eye" title="Produit suivi" style="color: var(--info); margin-left: 0.5rem;"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($produit['quantite'] == 0): ?>
                                <span class="badge badge-danger">Épuisé</span>
                            <?php elseif ($produit['quantite'] <= $produit['seuil_alerte']): ?>
                                <span class="badge badge-warning">Alerte</span>
                            <?php else: ?>
                                <span class="badge badge-success">En stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn btn-primary btn-sm" onclick="adjustStock(<?php echo $produit['id']; ?>)" title="Ajuster stock">
                                    <i class="fas fa-boxes"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="editProduct(<?php echo $produit['id']; ?>)" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?php echo $produit['id']; ?>)" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($produits)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>Aucun produit</h3>
                <p>Commencez par ajouter votre premier produit à l'inventaire.</p>
                <button class="btn btn-primary" onclick="openAddProduct()" style="margin-top: 1rem;">
                    <i class="fas fa-plus"></i>
                    Ajouter un produit
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Add Product -->
    <div class="modal" id="addProductModal">
        <div class="modal-content" style="width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-plus"></i>
                    Nouveau Produit
                </h3>
                <button class="modal-close" onclick="closeModal('addProductModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="addProductForm" method="POST" action="?page=inventaire_actions&action=ajouter">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Référence *</label>
                            <input type="text" class="form-input" name="reference" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-input" name="nom" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-input" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Prix d'achat *</label>
                            <input type="number" class="form-input" name="prix_achat" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prix de vente *</label>
                            <input type="number" class="form-input" name="prix_vente" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Quantité initiale *</label>
                            <input type="number" class="form-input" name="quantite" value="0" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Seuil d'alerte *</label>
                            <input type="number" class="form-input" name="seuil_alerte" value="5" required>
                        </div>
                    </div>
                    
                    <?php if ($has_suivre_stock): ?>
                    <div class="form-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="suivre_stock" name="suivre_stock" value="1">
                            <label for="suivre_stock">Suivre ce produit dans la vérification des stocks</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                        <button type="button" class="btn" onclick="closeModal('addProductModal')" style="background: #64748b; color: white;">
                            Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Ajouter le produit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Stock Check -->
    <?php if ($has_suivre_stock): ?>
    <div class="modal" id="stockCheckModal">
        <div class="modal-content" style="width: 1000px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-eye"></i>
                    Vérification des Stocks
                </h3>
                <button class="modal-close" onclick="closeModal('stockCheckModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="controls" style="margin-bottom: 1rem;">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" id="stockSearchInput" placeholder="Rechercher dans les produits suivis...">
                    </div>
                </div>
                <div id="stockCardsContainer">
                    <div class="loading">
                        <i class="fas fa-spinner spinner"></i>
                        Chargement des produits suivis...
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Stock Adjustment -->
    <div class="modal" id="stockModal">
        <div class="modal-content" style="width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-boxes"></i>
                    Ajustement du Stock
                </h3>
                <button class="modal-close" onclick="closeModal('stockModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="stockForm" method="POST" action="?page=inventaire_actions&action=mouvement">
                    <input type="hidden" name="produit_id" id="stock_produit_id">
                    
                    <div style="text-align: center; margin-bottom: 2rem; padding: 1rem; background: var(--light); border-radius: var(--radius);">
                        <h4 id="stock_product_name">-</h4>
                        <p style="color: #64748b; margin: 0.5rem 0;">
                            <code id="stock_product_ref">-</code>
                        </p>
                        <p style="font-weight: 600;">Stock actuel: <span id="stock_current">-</span></p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Type de mouvement</label>
                            <select class="form-input" name="type_mouvement" required>
                                <option value="entree">Entrée (+)</option>
                                <option value="sortie">Sortie (-)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Quantité</label>
                            <input type="number" class="form-input" name="quantite" min="1" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Motif</label>
                        <input type="text" class="form-input" name="motif" placeholder="Ex: Réception commande, Vente, Inventaire...">
                    </div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                        <button type="button" class="btn" onclick="closeModal('stockModal')" style="background: #64748b; color: white;">
                            Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i>
                            Valider le mouvement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scanner Modal -->
    <div class="modal" id="scannerModal">
        <div class="modal-content" style="width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-barcode"></i>
                    Scanner Code-Barres
                </h3>
                <button class="modal-close" onclick="closeScanner()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="scanner-container" style="width: 100%; height: 400px; background: #000; border-radius: var(--radius); overflow: hidden; position: relative;">
                    <video id="scanner-video" style="width: 100%; height: 100%; object-fit: cover;"></video>
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 200px; height: 2px; background: red; box-shadow: 0 0 10px red;"></div>
                </div>
                <div id="scanner-status" style="margin-top: 1rem; padding: 1rem; border-radius: var(--radius); background: var(--light); text-align: center;">
                    Initialisation du scanner...
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
    <script>
        // Variables globales
        let currentStream = null;
        let currentProduct = null;

        // Fonctions de modal
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        // Fermer modal en cliquant sur le fond
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });

        // Fonctions principales
        function openAddProduct() {
            openModal('addProductModal');
        }

        function openStockCheck() {
            openModal('stockCheckModal');
            loadTrackedProducts();
        }

        function openScanner() {
            openModal('scannerModal');
            startCamera();
        }

        function closeScanner() {
            stopCamera();
            closeModal('scannerModal');
        }

        function adjustStock(productId) {
            // Récupérer les données du produit depuis la ligne
            const row = document.querySelector(`tr[data-product-id="${productId}"]`);
            if (row) {
                const cells = row.querySelectorAll('td');
                document.getElementById('stock_produit_id').value = productId;
                document.getElementById('stock_product_name').textContent = cells[1].querySelector('strong').textContent;
                document.getElementById('stock_product_ref').textContent = cells[0].textContent;
                document.getElementById('stock_current').textContent = cells[4].querySelector('strong').textContent;
                openModal('stockModal');
            }
        }

        function editProduct(productId) {
            alert('Fonction de modification à implémenter');
        }

        function deleteProduct(productId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?page=inventaire_actions&action=supprimer';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = productId;
                form.appendChild(input);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        function exportInventory() {
            const filter = document.getElementById('filterSelect').value;
            window.open(`ajax/export_print.php?filter=${filter}`, '_blank');
        }

        // Recherche et filtres
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#productsTable tr');
            
            rows.forEach(row => {
                const nom = row.dataset.nom || '';
                const reference = row.dataset.reference || '';
                const visible = nom.includes(searchTerm) || reference.includes(searchTerm);
                row.style.display = visible ? '' : 'none';
            });
        });

        document.getElementById('filterSelect').addEventListener('change', function() {
            const filter = this.value;
            const rows = document.querySelectorAll('#productsTable tr');
            
            rows.forEach(row => {
                const quantite = parseInt(row.dataset.quantite || 0);
                const seuil = parseInt(row.dataset.seuil || 0);
                const suivi = row.dataset.suivi === '1';
                let visible = true;
                
                switch (filter) {
                    case 'stock':
                        visible = quantite > 0;
                        break;
                    case 'alerte':
                        visible = quantite > 0 && quantite <= seuil;
                        break;
                    case 'epuise':
                        visible = quantite === 0;
                        break;
                    case 'suivis':
                        visible = suivi;
                        break;
                    default:
                        visible = true;
                }
                
                row.style.display = visible ? '' : 'none';
            });
        });

        // Scanner
        function startCamera() {
            const status = document.getElementById('scanner-status');
            status.innerHTML = '<i class="fas fa-spinner spinner"></i> Démarrage de la caméra...';
            
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                .then(stream => {
                    currentStream = stream;
                    const video = document.getElementById('scanner-video');
                    video.srcObject = stream;
                    video.play();
                    
                    status.innerHTML = '<i class="fas fa-camera"></i> Caméra active - Positionnez le code-barres';
                    initQuagga(stream);
                })
                .catch(err => {
                    console.error('Erreur caméra:', err);
                    status.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Impossible d\'accéder à la caméra';
                });
        }

        function stopCamera() {
            if (currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
                currentStream = null;
            }
            if (typeof Quagga !== 'undefined') {
                Quagga.stop();
            }
        }

        function initQuagga(stream) {
            const config = {
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#scanner-video')
                },
                decoder: {
                    readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader"]
                }
            };

            Quagga.init(config, function(err) {
                if (err) {
                    console.error("Erreur Quagga:", err);
                    return;
                }
                Quagga.start();
            });

            Quagga.onDetected(function(result) {
                if (result && result.codeResult && result.codeResult.code) {
                    const code = result.codeResult.code;
                    console.log("Code détecté:", code);
                    checkProduct(code);
                }
            });
        }

        function checkProduct(code) {
            fetch(`ajax/verifier_produit.php?code=${encodeURIComponent(code)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.produit) {
                        closeScanner();
                        adjustStock(data.produit.id);
                    } else {
                        document.getElementById('scanner-status').innerHTML = 
                            '<i class="fas fa-exclamation-triangle"></i> Produit non trouvé: ' + code;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('scanner-status').innerHTML = 
                        '<i class="fas fa-exclamation-triangle"></i> Erreur lors de la vérification';
                });
        }

        // Produits suivis
        function loadTrackedProducts() {
            const container = document.getElementById('stockCardsContainer');
            container.innerHTML = '<div class="loading"><i class="fas fa-spinner spinner"></i> Chargement des produits suivis...</div>';
            
            fetch('ajax/get_tracked_products.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayStockCards(data.products);
                    } else {
                        container.innerHTML = '<div class="empty-state"><i class="fas fa-info-circle"></i><h3>Erreur</h3><p>' + (data.error || 'Erreur inconnue') + '</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Erreur de chargement</h3><p>Impossible de charger les produits suivis.</p></div>';
                });
        }

        function displayStockCards(products) {
            const container = document.getElementById('stockCardsContainer');
            
            if (products.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-eye-slash"></i>
                        <h3>Aucun produit suivi</h3>
                        <p>Aucun produit n'est configuré pour le suivi de stock.</p>
                        <p style="font-size: 0.9rem; color: #64748b; margin-top: 1rem;">
                            💡 Pour configurer le suivi :<br>
                            1. Cliquez sur "Nouveau Produit"<br>
                            2. Cochez "Suivre ce produit"<br>
                            3. Sauvegardez le produit
                        </p>
                    </div>
                `;
                return;
            }

            const grid = document.createElement('div');
            grid.className = 'stock-grid';
            
            products.forEach(product => {
                const quantityClass = product.quantite <= 0 ? 'danger' : 
                                    product.quantite <= product.seuil_alerte ? 'warning' : 'success';
                
                const card = document.createElement('div');
                card.className = 'stock-card';
                card.onclick = () => adjustStock(product.id);
                
                card.innerHTML = `
                    <div class="stock-card-header">
                        <div>
                            <div class="stock-card-title">${product.nom}</div>
                            <div class="stock-card-ref">${product.reference}</div>
                        </div>
                        <span class="badge badge-${quantityClass}">${product.quantite}</span>
                    </div>
                    <div class="stock-card-quantity" style="color: var(--${quantityClass})">
                        ${product.quantite} unités
                    </div>
                    <div style="font-size: 0.875rem; color: #64748b;">
                        Seuil: ${product.seuil_alerte} | 
                        Prix: ${parseFloat(product.prix_vente).toFixed(2)}€
                    </div>
                `;
                
                grid.appendChild(card);
            });
            
            container.innerHTML = '';
            container.appendChild(grid);
        }

        // Recherche dans les produits suivis
        document.addEventListener('DOMContentLoaded', function() {
            const stockSearchInput = document.getElementById('stockSearchInput');
            if (stockSearchInput) {
                stockSearchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const cards = document.querySelectorAll('.stock-card');
                    
                    cards.forEach(card => {
                        const title = card.querySelector('.stock-card-title').textContent.toLowerCase();
                        const ref = card.querySelector('.stock-card-ref').textContent.toLowerCase();
                        const visible = title.includes(searchTerm) || ref.includes(searchTerm);
                        card.style.display = visible ? '' : 'none';
                    });
                });
            }
        });

        console.log('✅ Page inventaire moderne chargée');
    </script>
</body>
</html>
