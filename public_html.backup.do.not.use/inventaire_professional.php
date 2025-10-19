<?php
// Session factice pour test
session_start();
$_SESSION["shop_id"] = 63;

// Inclure les fichiers nécessaires
require_once(__DIR__ . "/../includes/functions.php");
require_once(__DIR__ . "/../config/database.php");

// Récupérer les produits
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->prepare("SELECT * FROM produits ORDER BY nom ASC");
    $stmt->execute();
    $produits = $stmt->fetchAll();
} catch (PDOException $e) {
    $produits = [];
}

// Calculer les statistiques
$total_produits = count($produits);
$total_stock = array_sum(array_column($produits, 'quantite'));
$valeur_totale = 0;
foreach($produits as $p) {
    $valeur_totale += $p['quantite'] * $p['prix_achat'];
}
$alertes = 0;
foreach($produits as $p) {
    if($p['quantite'] <= $p['seuil_alerte']) $alertes++;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de l'Inventaire - GeekBoard</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --accent-color: #0ea5e9;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-900);
            line-height: 1.6;
        }
        
        /* Animation globales */
        * {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Header Section */
        .header-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            animation: slideInDown 0.6s ease-out;
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
            animation: slideInDown 0.6s ease-out 0.1s both;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover::before {
            transform: scaleY(1);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.products { background: var(--primary-color); }
        .stat-icon.stock { background: var(--accent-color); }
        .stat-icon.value { background: var(--success-color); }
        .stat-icon.alerts { background: var(--danger-color); }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-500);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Main Content */
        .main-content {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        
        .content-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }
        
        .search-section {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
        }
        
        .search-input-group {
            position: relative;
            flex: 1;
            min-width: 300px;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 0.875rem;
            background: white;
            transition: all 0.2s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 0.875rem;
        }
        
        .btn-primary-modern {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .btn-primary-modern:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-primary-modern:active {
            transform: translateY(0);
        }
        
        /* Alerts */
        .alert-modern {
            margin: 1.5rem;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--warning-color);
            background: #fef3c7;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideInRight 0.5s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .alert-icon {
            flex-shrink: 0;
            font-size: 1.25rem;
        }
        
        /* Products Grid */
        .content-body {
            padding: 1.5rem;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        
        .product-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }
        
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-color);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .product-card:hover::before {
            transform: scaleX(1);
        }
        
        .product-header {
            display: flex;
            align-items: start;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .product-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
            line-height: 1.4;
        }
        
        .product-reference {
            font-size: 0.75rem;
            color: var(--gray-500);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .quantity-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .quantity-high {
            background: #d1fae5;
            color: #065f46;
        }
        
        .quantity-medium {
            background: #fef3c7;
            color: #92400e;
        }
        
        .quantity-low {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .product-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .detail-label {
            font-size: 0.75rem;
            color: var(--gray-500);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .detail-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 0.5rem 1rem;
            border: 1px solid;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }
        
        .btn-view {
            background: white;
            border-color: var(--gray-300);
            color: var(--gray-700);
        }
        
        .btn-view:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
        }
        
        .btn-edit {
            background: white;
            border-color: var(--accent-color);
            color: var(--accent-color);
        }
        
        .btn-edit:hover {
            background: var(--accent-color);
            color: white;
        }
        
        .btn-delete {
            background: white;
            border-color: var(--danger-color);
            color: var(--danger-color);
        }
        
        .btn-delete:hover {
            background: var(--danger-color);
            color: white;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-500);
        }
        
        .empty-icon {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }
        
        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        .empty-description {
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }
        
        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            box-shadow: var(--shadow-lg);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .fab:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
            box-shadow: var(--shadow-xl);
        }
        
        .fab:active {
            transform: scale(0.95);
        }
        
        /* Loading State */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            color: var(--gray-500);
        }
        
        .spinner {
            width: 24px;
            height: 24px;
            border: 2px solid var(--gray-200);
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.75rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .search-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input-group {
                min-width: auto;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .product-details {
                grid-template-columns: 1fr;
            }
            
            .fab {
                bottom: 1rem;
                right: 1rem;
            }
        }
        
        /* Animation delays pour les cartes */
        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.2s; }
        .product-card:nth-child(3) { animation-delay: 0.3s; }
        .product-card:nth-child(4) { animation-delay: 0.4s; }
        .product-card:nth-child(5) { animation-delay: 0.5s; }
        .product-card:nth-child(6) { animation-delay: 0.6s; }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header-section">
        <div class="container">
            <div class="header-content">
                <h1 class="page-title">
                    <i class="fas fa-boxes me-3"></i>
                    Gestion de l'Inventaire
                </h1>
                <p class="page-subtitle">
                    Gérez votre stock de produits en temps réel
                </p>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card" onclick="showStatDetails('produits')">
                <div class="stat-header">
                    <div class="stat-icon products">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $total_produits; ?></div>
                <div class="stat-label">Produits</div>
            </div>
            
            <div class="stat-card" onclick="showStatDetails('stock')">
                <div class="stat-header">
                    <div class="stat-icon stock">
                        <i class="fas fa-cubes"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo number_format($total_stock); ?></div>
                <div class="stat-label">Unités en stock</div>
            </div>
            
            <div class="stat-card" onclick="showStatDetails('valeur')">
                <div class="stat-header">
                    <div class="stat-icon value">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo number_format($valeur_totale, 0, ',', ' '); ?>€</div>
                <div class="stat-label">Valeur totale</div>
            </div>
            
            <div class="stat-card" onclick="showStatDetails('alertes')">
                <div class="stat-header">
                    <div class="stat-icon alerts">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $alertes; ?></div>
                <div class="stat-label">Stock faible</div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <div class="search-section">
                    <div class="search-input-group">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" id="searchInput" placeholder="Rechercher un produit par nom, référence...">
                    </div>
                    <button class="btn-primary-modern" onclick="addProduct()">
                        <i class="fas fa-plus"></i>
                        Nouveau produit
                    </button>
                </div>
            </div>
            
            <?php if ($alertes > 0): ?>
            <div class="alert-modern">
                <i class="fas fa-exclamation-triangle alert-icon"></i>
                <div>
                    <strong>Attention !</strong> 
                    <?php echo $alertes; ?> produit(s) ont un stock faible et nécessitent votre attention.
                </div>
            </div>
            <?php endif; ?>
            
            <div class="content-body">
                <?php if (empty($produits)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3 class="empty-title">Aucun produit en stock</h3>
                    <p class="empty-description">
                        Commencez par ajouter votre premier produit à l'inventaire
                    </p>
                    <button class="btn-primary-modern" onclick="addProduct()">
                        <i class="fas fa-plus"></i>
                        Ajouter un produit
                    </button>
                </div>
                <?php else: ?>
                <div class="products-grid" id="productsGrid">
                    <?php foreach($produits as $produit): ?>
                    <div class="product-card" data-name="<?php echo strtolower($produit['nom']); ?>" onclick="showProduct(<?php echo $produit['id']; ?>)">
                        <div class="product-header">
                            <div>
                                <h3 class="product-name"><?php echo htmlspecialchars($produit['nom']); ?></h3>
                                <div class="product-reference">REF: <?php echo htmlspecialchars($produit['reference'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="quantity-badge <?php 
                                echo $produit['quantite'] <= $produit['seuil_alerte'] ? 'quantity-low' : 
                                     ($produit['quantite'] < 50 ? 'quantity-medium' : 'quantity-high'); 
                            ?>">
                                <?php echo $produit['quantite']; ?> unités
                            </div>
                        </div>
                        
                        <div class="product-details">
                            <div class="detail-item">
                                <div class="detail-label">Prix de vente</div>
                                <div class="detail-value"><?php echo number_format($produit['prix_vente'], 2, ',', ' '); ?>€</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Prix d'achat</div>
                                <div class="detail-value"><?php echo number_format($produit['prix_achat'], 2, ',', ' '); ?>€</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Seuil d'alerte</div>
                                <div class="detail-value"><?php echo $produit['seuil_alerte']; ?> unités</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Valeur stock</div>
                                <div class="detail-value"><?php echo number_format($produit['quantite'] * $produit['prix_achat'], 2, ',', ' '); ?>€</div>
                            </div>
                        </div>
                        
                        <div class="product-actions">
                            <button class="btn-action btn-view" onclick="event.stopPropagation(); showProduct(<?php echo $produit['id']; ?>)">
                                <i class="fas fa-eye"></i> Voir
                            </button>
                            <button class="btn-action btn-edit" onclick="event.stopPropagation(); editProduct(<?php echo $produit['id']; ?>)">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <button class="btn-action btn-delete" onclick="event.stopPropagation(); deleteProduct(<?php echo $produit['id']; ?>)">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Floating Action Button -->
    <button class="fab" onclick="addProduct()" title="Ajouter un produit">
        <i class="fas fa-plus"></i>
    </button>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const productCards = document.querySelectorAll('.product-card');
            let visibleCount = 0;
            
            productCards.forEach((card, index) => {
                const productName = card.dataset.name;
                const isVisible = productName.includes(searchTerm);
                
                card.style.display = isVisible ? 'block' : 'none';
                
                if (isVisible) {
                    visibleCount++;
                    card.style.animationDelay = (visibleCount * 0.1) + 's';
                    card.style.animation = 'fadeInUp 0.4s ease-out both';
                }
            });
            
            // Show no results message
            const noResults = document.querySelector('.no-results');
            if (noResults) noResults.remove();
            
            if (visibleCount === 0 && searchTerm.length > 0) {
                const noResultsDiv = document.createElement('div');
                noResultsDiv.className = 'no-results empty-state';
                noResultsDiv.innerHTML = `
                    <div class="empty-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="empty-title">Aucun résultat trouvé</h3>
                    <p class="empty-description">Aucun produit ne correspond à votre recherche "${searchTerm}"</p>
                `;
                document.getElementById('productsGrid').appendChild(noResultsDiv);
            }
        });
        
        // Product management functions
        function addProduct() {
            // TODO: Implement add product modal/page
            alert('Fonctionnalité d\'ajout de produit à implémenter');
        }
        
        function showProduct(id) {
            // TODO: Implement product details modal/page
            console.log('Affichage du produit:', id);
        }
        
        function editProduct(id) {
            // TODO: Implement edit product modal/page
            console.log('Modification du produit:', id);
        }
        
        function deleteProduct(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
                // TODO: Implement delete functionality
                console.log('Suppression du produit:', id);
            }
        }
        
        function showStatDetails(type) {
            let message = '';
            switch(type) {
                case 'produits':
                    message = 'Vous avez <?php echo $total_produits; ?> produits dans votre inventaire.';
                    break;
                case 'stock':
                    message = 'Total de <?php echo number_format($total_stock); ?> unités en stock.';
                    break;
                case 'valeur':
                    message = 'Valeur totale de l\'inventaire: <?php echo number_format($valeur_totale, 2, ",", " "); ?>€';
                    break;
                case 'alertes':
                    message = '<?php echo $alertes; ?> produit(s) avec un stock faible.';
                    break;
            }
            alert(message);
        }
        
        // Smooth animations on scroll
        function animateOnScroll() {
            const cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
                
                if (isVisible) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }
            });
        }
        
        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('load', animateOnScroll);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K pour focus sur la recherche
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
            
            // N pour nouveau produit
            if (e.key === 'n' && !e.target.matches('input, textarea')) {
                addProduct();
            }
        });
        
        // Auto-save search in localStorage
        const searchInput = document.getElementById('searchInput');
        searchInput.value = localStorage.getItem('inventaire_search') || '';
        searchInput.dispatchEvent(new Event('input'));
        
        searchInput.addEventListener('input', function() {
            localStorage.setItem('inventaire_search', this.value);
        });
    </script>
</body>
</html> 