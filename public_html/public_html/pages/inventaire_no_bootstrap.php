<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Récupérer les produits
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->prepare("
        SELECT p.* 
        FROM produits p 
        ORDER BY p.nom ASC
    ");
    $stmt->execute();
    $produits = $stmt->fetchAll();
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des produits: " . $e->getMessage(), 'danger');
    $produits = [];
}

// Récupérer les produits en alerte de stock
try {
    $stmt = $shop_pdo->prepare("
        SELECT p.* 
        FROM produits p 
        WHERE p.quantite <= p.seuil_alerte 
        ORDER BY p.quantite ASC
    ");
    $stmt->execute();
    $produits_alerte = $stmt->fetchAll();
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des alertes: " . $e->getMessage(), 'danger');
    $produits_alerte = [];
}

// Calculer les statistiques
$total_produits = count($produits);
$produits_en_alerte = count($produits_alerte);
$produits_epuises = array_filter($produits, function($p) { return $p['quantite'] == 0; });
$total_produits_epuises = count($produits_epuises);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de l'Inventaire - GeekBoard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset et base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2d3748;
            line-height: 1.6;
        }

        /* Container principal */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #1a202c;
            font-size: 2rem;
            font-weight: 700;
        }

        .page-title i {
            color: #667eea;
            font-size: 2.5rem;
        }

        /* Boutons d'action */
        .action-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        /* Statistiques */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.primary { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.warning { background: linear-gradient(135deg, #ed8936, #dd6b20); }
        .stat-icon.danger { background: linear-gradient(135deg, #f56565, #e53e3e); }
        .stat-icon.success { background: linear-gradient(135deg, #48bb78, #38a169); }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
        }

        .stat-info p {
            color: #718096;
            font-weight: 500;
        }

        /* Barre de recherche */
        .search-section {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .search-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input-group {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }

        .filter-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            min-width: 200px;
        }

        /* Tableau simplifié */
        .table-container {
            background: white;
            border-radius: 16px;
            overflow-x: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin: 0;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 800px;
        }

        .table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.8rem 0.6rem;
            text-align: center;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table th:first-child {
            border-radius: 16px 0 0 0;
        }

        .table th:last-child {
            border-radius: 0 16px 0 0;
        }

        .table td {
            padding: 0.8rem 0.6rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table td:first-child {
            text-align: left;
            max-width: 100px;
        }

        .table td:nth-child(2) {
            text-align: left;
            max-width: 150px;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background: #f7fafc;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:last-child td:first-child {
            border-radius: 0 0 0 16px;
        }

        .table tbody tr:last-child td:last-child {
            border-radius: 0 0 16px 0;
        }

        /* Badges */
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            min-width: 60px;
            text-align: center;
            display: inline-block;
        }

        .badge-success { background: #c6f6d5; color: #22543d; }
        .badge-warning { background: #feebc8; color: #744210; }
        .badge-danger { background: #fed7d7; color: #742a2a; }

        /* Boutons d'action du tableau */
        .action-buttons-table {
            display: flex;
            gap: 0.3rem;
            justify-content: center;
            align-items: center;
            flex-wrap: nowrap;
        }

        .btn-sm {
            padding: 0.4rem;
            font-size: 0.75rem;
            border-radius: 6px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            flex-shrink: 0;
        }

        /* Modals */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1 !important;
            visibility: visible !important;
            z-index: 9999 !important;
        }

        /* Style spécifique pour le modal de vérification des stocks */
        #stockVerificationModal {
            z-index: 10000 !important;
        }

        #stockVerificationModal.active {
            opacity: 1 !important;
            visibility: visible !important;
            display: flex !important;
            z-index: 10000 !important;
        }

        .modal {
            background: white;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.8);
            transition: transform 0.3s ease;
            position: relative;
            z-index: 1001;
        }

        .modal-overlay.active .modal {
            transform: scale(1);
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px 20px 0 0;
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
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        /* Formulaires */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .input-group {
            display: flex;
            align-items: center;
        }

        .input-group .form-input {
            border-radius: 12px 0 0 12px;
        }

        .input-group-text {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-left: none;
            padding: 0.75rem 1rem;
            border-radius: 0 12px 12px 0;
            font-weight: 600;
            color: #4a5568;
        }

        /* Checkbox */
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
        }

        /* Scanner styles */
        .scanner-container {
            text-align: center;
            padding: 2rem;
        }

        .scanner-video {
            width: 100%;
            max-width: 500px;
            height: 300px;
            border-radius: 12px;
            background: #000;
        }

        .scanner-controls {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
                margin: 1rem;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .search-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input-group {
                min-width: auto;
            }

            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .table {
                min-width: 700px;
            }

            .table th {
                padding: 0.6rem 0.4rem;
                font-size: 0.7rem;
            }

            .table td {
                padding: 0.6rem 0.4rem;
                font-size: 0.85rem;
            }

            .btn-sm {
                width: 24px;
                height: 24px;
                font-size: 0.7rem;
            }

            .action-buttons-table {
                gap: 0.2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .modal {
                width: 95%;
                margin: 1rem;
            }

            .modal-body {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .table {
                min-width: 600px;
            }

            .table th,
            .table td {
                padding: 0.5rem 0.3rem;
            }

            .btn-sm {
                width: 20px;
                height: 20px;
            }
        }

        /* Animations */
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

        .container {
            animation: fadeInUp 0.6s ease;
        }

        /* Ajustements pour le modal d'ajustement de stock */
        .stock-info {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .stock-info h4 {
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .stock-current {
            color: #718096;
            font-size: 0.9rem;
        }

        .movement-type {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .movement-option {
            position: relative;
        }

        .movement-radio {
            position: absolute;
            opacity: 0;
        }

        .movement-label {
            display: block;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .movement-radio:checked + .movement-label {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .quantity-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .quantity-input {
            width: 80px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .motif-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .motif-btn {
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .motif-btn:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .motif-btn.active {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .alert-info {
            background: #bee3f8;
            color: #2a4365;
            border: 1px solid #90cdf4;
        }

        .alert-danger {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #68d391;
        }

        /* Styles pour la vérification des stocks */
        .stock-search-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .stock-stats {
            display: flex;
            gap: 1rem;
        }

        .stock-stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f7fafc;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stock-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            max-height: 60vh;
            overflow-y: auto;
            padding: 0.5rem;
        }

        .stock-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stock-card:hover {
            border-color: #667eea;
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }

        .stock-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stock-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }

        .stock-card-reference {
            font-size: 0.85rem;
            color: #718096;
            font-family: 'Courier New', monospace;
            background: #f7fafc;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }

        .stock-card-quantity {
            text-align: center;
            margin-top: 1rem;
        }

        .stock-quantity-badge {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            min-width: 80px;
        }

        .stock-quantity-badge.high { background: linear-gradient(135deg, #48bb78, #38a169); }
        .stock-quantity-badge.medium { background: linear-gradient(135deg, #ed8936, #dd6b20); }
        .stock-quantity-badge.low { background: linear-gradient(135deg, #f56565, #e53e3e); }

        .stock-card-footer {
            margin-top: 1rem;
            text-align: center;
            font-size: 0.8rem;
            color: #a0aec0;
        }

        .loading-message {
            text-align: center;
            padding: 3rem;
            color: #718096;
            font-size: 1.1rem;
        }

        .no-products-message {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }

        .no-products-message i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e0;
        }

        /* Responsive pour les cartes */
        @media (max-width: 768px) {
            .stock-search-section {
                flex-direction: column;
                align-items: stretch;
            }

            .stock-cards-grid {
                grid-template-columns: 1fr;
                max-height: 50vh;
            }

            .stock-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-boxes"></i>
                Gestion de l'Inventaire
            </h1>
            <div class="action-buttons">
                <button class="btn btn-success" onclick="openScanner()">
                    <i class="fas fa-barcode"></i>
                    Scanner
                </button>
                <button class="btn btn-warning" onclick="openModal('stockVerificationModal')">
                    <i class="fas fa-eye"></i>
                    Vérifier les Stock
                </button>
                <button class="btn btn-primary" onclick="openModal('addProductModal')">
                    <i class="fas fa-plus"></i>
                    Nouveau Produit
                </button>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon primary">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_produits; ?></h3>
                        <p>Total Produits</p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $produits_en_alerte; ?></h3>
                        <p>En Alerte</p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_produits_epuises; ?></h3>
                        <p>Épuisés</p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_produits - $total_produits_epuises; ?></h3>
                        <p>En Stock</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre de recherche -->
        <div class="search-section">
            <div class="search-controls">
                <div class="search-input-group">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" id="searchInput" placeholder="Rechercher un produit...">
                </div>
                <select class="filter-select" id="filterStatus">
                    <option value="all">Tous les statuts</option>
                    <option value="stock">En stock</option>
                    <option value="alert">En alerte</option>
                    <option value="out">Épuisés</option>
                </select>
                <button class="btn btn-primary" onclick="exportInventory()">
                    <i class="fas fa-download"></i>
                    Exporter
                </button>
            </div>
        </div>

        <!-- Tableau des produits -->
        <div class="table-container">
            <table class="table" id="produitsTable">
                <thead>
                    <tr>
                        <th style="width: 12%;">Référence</th>
                        <th style="width: 20%;">Produit</th>
                        <th style="width: 12%;">Prix Achat</th>
                        <th style="width: 12%;">Prix Vente</th>
                        <th style="width: 10%;">Stock</th>
                        <th style="width: 8%;">Seuil</th>
                        <th style="width: 26%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produits as $produit): ?>
                    <tr data-produit-id="<?php echo $produit['id']; ?>" data-seuil-alerte="<?php echo $produit['seuil_alerte']; ?>">
                        <td title="<?php echo htmlspecialchars($produit['reference']); ?>">
                            <?php echo htmlspecialchars(substr($produit['reference'], 0, 15)); ?>
                            <?php if (strlen($produit['reference']) > 15) echo '...'; ?>
                        </td>
                        <td title="<?php echo htmlspecialchars($produit['nom']); ?>">
                            <?php echo htmlspecialchars(substr($produit['nom'], 0, 20)); ?>
                            <?php if (strlen($produit['nom']) > 20) echo '...'; ?>
                        </td>
                        <td><?php echo number_format($produit['prix_achat'], 2); ?>€</td>
                        <td><?php echo number_format($produit['prix_vente'], 2); ?>€</td>
                        <td>
                            <span class="badge <?php 
                                if ($produit['quantite'] <= 0) echo 'badge-danger';
                                elseif ($produit['quantite'] <= $produit['seuil_alerte']) echo 'badge-warning';
                                else echo 'badge-success';
                            ?>">
                                <?php echo $produit['quantite']; ?>
                            </span>
                        </td>
                        <td><?php echo $produit['seuil_alerte']; ?></td>
                        <td>
                            <div class="action-buttons-table">
                                <button class="btn btn-primary btn-sm" onclick="ajusterStock(<?php echo $produit['id']; ?>)" title="Ajuster le stock">
                                    <i class="fas fa-boxes"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="modifierProduit(<?php echo $produit['id']; ?>)" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="supprimerProduit(<?php echo $produit['id']; ?>)" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Ajout Produit -->
    <div class="modal-overlay" id="addProductModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-plus-circle"></i>
                    Nouveau Produit
                </h3>
                <button class="modal-close" onclick="closeModal('addProductModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="addProductForm" method="POST" action="index.php?page=inventaire_actions">
                    <input type="hidden" name="action" value="ajouter_produit">
                    
                    <div class="form-group">
                        <label class="form-label">Référence</label>
                        <input type="text" class="form-input" name="reference" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-input" name="nom" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-input form-textarea" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Prix d'achat</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-input" name="prix_achat" required>
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prix de vente</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-input" name="prix_vente" required>
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Stock initial</label>
                            <input type="number" class="form-input" name="quantite" value="0" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Seuil d'alerte</label>
                            <input type="number" class="form-input" name="seuil_alerte" value="5" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_temporaire" id="is_temporaire" value="1">
                            <label class="form-check-label" for="is_temporaire">
                                Produit temporaire (susceptible d'être retourné)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="suivre_stock" id="suivre_stock" value="1" checked>
                            <label class="form-check-label" for="suivre_stock">
                                <i class="fas fa-eye text-primary"></i> Suivre le stock (inclure dans la vérification des stocks)
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addProductModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="btn btn-primary" onclick="document.getElementById('addProductForm').submit()">
                    <i class="fas fa-check"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Ajustement Stock -->
    <div class="modal-overlay" id="stockModal">
        <div class="modal">
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
                <form id="stockForm">
                    <input type="hidden" name="produit_id" id="stock_produit_id">
                    <input type="hidden" name="prix_vente" id="stock_prix_vente">
                    
                    <!-- Informations du produit -->
                    <div class="stock-info">
                        <h4 id="stock_product_name">-</h4>
                        <p class="stock-current">
                            <span id="stock_product_ref">-</span> | 
                            <span id="stock_current_stock">Stock actuel: -</span>
                        </p>
                    </div>
                    
                    <!-- Type de mouvement -->
                    <div class="form-group">
                        <label class="form-label">Type de mouvement</label>
                        <div class="movement-type">
                            <div class="movement-option">
                                <input class="movement-radio" type="radio" name="type_mouvement" id="type_entree" value="entree" checked>
                                <label class="movement-label" for="type_entree">
                                    <i class="fas fa-arrow-circle-down"></i> Entrée
                                </label>
                            </div>
                            <div class="movement-option">
                                <input class="movement-radio" type="radio" name="type_mouvement" id="type_sortie" value="sortie">
                                <label class="movement-label" for="type_sortie">
                                    <i class="fas fa-arrow-circle-up"></i> Sortie
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quantité -->
                    <div class="form-group">
                        <label class="form-label">Quantité</label>
                        <div class="quantity-controls">
                            <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="form-input quantity-input" name="quantite" id="stock_quantite" required min="1" value="1">
                            <button type="button" class="quantity-btn" onclick="changeQuantity(1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Motifs -->
                    <div class="form-group">
                        <label class="form-label">Motif du mouvement</label>
                        <input type="hidden" name="motif" id="motif" value="">
                        
                        <!-- Motifs d'entrée -->
                        <div class="motif-buttons" id="motifs-entree">
                            <button type="button" class="motif-btn" data-motif="Commande Reçu">
                                <i class="fas fa-truck-loading"></i><br>Commande Reçu
                            </button>
                            <button type="button" class="motif-btn" data-motif="Retour de prêt">
                                <i class="fas fa-undo"></i><br>Retour de prêt
                            </button>
                        </div>
                        
                        <!-- Motifs de sortie -->
                        <div class="motif-buttons" id="motifs-sortie" style="display: none;">
                            <button type="button" class="motif-btn" data-motif="Utilisé pour une réparation">
                                <i class="fas fa-tools"></i><br>Utilisé pour une réparation
                            </button>
                            <button type="button" class="motif-btn" data-motif="Utilisé pour un SAV">
                                <i class="fas fa-headset"></i><br>Utilisé pour un SAV
                            </button>
                            <button type="button" class="motif-btn" data-motif="Prêté à un partenaire">
                                <i class="fas fa-handshake"></i><br>Prêté à un partenaire
                            </button>
                        </div>
                        
                        <div id="motif-display" class="alert alert-info" style="display: none;"></div>
                    </div>
                    
                    <!-- Sélection partenaire -->
                    <div class="form-group" id="partenaire-selection" style="display: none;">
                        <label class="form-label">Sélectionner un partenaire</label>
                        <select class="form-input" id="partenaire_id" name="partenaire_id">
                            <option value="">Choisir un partenaire...</option>
                        </select>
                    </div>
                    
                    <div id="stock-alert" class="alert" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('stockModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="btn btn-primary" onclick="submitStock()">
                    <i class="fas fa-check"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Vérification des Stocks -->
    <div class="modal-overlay" id="stockVerificationModal">
        <div class="modal" style="max-width: 1200px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-eye"></i>
                    Vérification des Stocks
                </h3>
                <button class="modal-close" onclick="closeModal('stockVerificationModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Barre de recherche -->
                <div class="stock-search-section">
                    <div class="search-input-group">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" id="stockSearchInput" placeholder="Rechercher par nom ou référence...">
                    </div>
                    <div class="stock-stats">
                        <span class="stock-stat-item">
                            <i class="fas fa-eye text-primary"></i>
                            <span id="totalStockItems">0</span> produits suivis
                        </span>
                    </div>
                </div>
                
                <!-- Grille des cartes produits -->
                <div class="stock-cards-grid" id="stockCardsContainer">
                    <div class="loading-message">
                        <i class="fas fa-spinner fa-spin"></i>
                        Chargement des produits...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Mise à jour Quantité Rapide -->
    <div class="modal-overlay" id="quickUpdateModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-edit"></i>
                    Mise à jour Rapide
                </h3>
                <button class="modal-close" onclick="closeModal('quickUpdateModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="quickUpdateForm">
                    <input type="hidden" name="produit_id" id="quick_produit_id">
                    
                    <!-- Informations du produit -->
                    <div class="stock-info">
                        <h4 id="quick_product_name">-</h4>
                        <p class="stock-current">
                            <span id="quick_product_ref">-</span> | 
                            <span id="quick_current_stock">Stock actuel: -</span>
                        </p>
                    </div>
                    
                    <!-- Nouvelle quantité -->
                    <div class="form-group">
                        <label class="form-label">Nouvelle quantité</label>
                        <div class="quantity-controls">
                            <button type="button" class="quantity-btn" onclick="changeQuickQuantity(-1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="form-input quantity-input" name="nouvelle_quantite" id="quick_quantite" required min="0" value="0">
                            <button type="button" class="quantity-btn" onclick="changeQuickQuantity(1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Motif de la modification -->
                    <div class="form-group">
                        <label class="form-label">Motif (optionnel)</label>
                        <select class="form-input" name="motif" id="quick_motif">
                            <option value="Comptage physique">Comptage physique</option>
                            <option value="Correction d'erreur">Correction d'erreur</option>
                            <option value="Mise à jour manuelle">Mise à jour manuelle</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    
                    <div id="quick-alert" class="alert" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('quickUpdateModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="btn btn-primary" onclick="submitQuickUpdate()">
                    <i class="fas fa-check"></i> Mettre à jour
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Scanner -->
    <div class="modal-overlay" id="scannerModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-barcode"></i>
                    Scanner un Code-Barres
                </h3>
                <button class="modal-close" onclick="closeScanner()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="scanner-container">
                    <div id="scanner-status" class="alert alert-info">
                        Initialisation de la caméra...
                    </div>
                    <video id="scanner-video" class="scanner-video" autoplay playsinline></video>
                    <div class="scanner-controls">
                        <select id="camera-select" class="form-input" style="display: none;">
                            <option value="">Sélectionner une caméra...</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
    <script>
        // Variables globales
        let currentStream = null;
        let scannerInitialized = false;
        let currentProductData = null;

        // Fonctions de modal
        function openModal(modalId) {
            console.log('Tentative d\'ouverture du modal:', modalId);
            const modal = document.getElementById(modalId);
            if (modal) {
                console.log('Modal trouvé, ajout de la classe active');
                
                // Debug: vérifier les styles avant
                const computedStyle = window.getComputedStyle(modal);
                console.log('🎨 Styles AVANT:', {
                    display: computedStyle.display,
                    visibility: computedStyle.visibility,
                    opacity: computedStyle.opacity,
                    zIndex: computedStyle.zIndex,
                    position: computedStyle.position
                });
                
                modal.classList.add('active');
                
                // Debug: vérifier les styles après
                setTimeout(() => {
                    const computedStyleAfter = window.getComputedStyle(modal);
                    console.log('🎨 Styles APRÈS:', {
                        display: computedStyleAfter.display,
                        visibility: computedStyleAfter.visibility,
                        opacity: computedStyleAfter.opacity,
                        zIndex: computedStyleAfter.zIndex,
                        position: computedStyleAfter.position,
                        classes: modal.className
                    });
                    
                    // Test: forcer l'affichage
                    if (modalId === 'stockVerificationModal') {
                        modal.style.display = 'flex';
                        modal.style.visibility = 'visible';
                        modal.style.opacity = '1';
                        modal.style.zIndex = '10000';
                        modal.style.position = 'fixed';
                        modal.style.top = '0';
                        modal.style.left = '0';
                        modal.style.right = '0';
                        modal.style.bottom = '0';
                        modal.style.background = 'rgba(0, 0, 0, 0.5)';
                        modal.style.alignItems = 'center';
                        modal.style.justifyContent = 'center';
                        console.log('🔧 Styles forcés pour test - z-index: 10000');
                        
                        // Vérifier après forçage
                        setTimeout(() => {
                            const finalStyle = window.getComputedStyle(modal);
                            console.log('🎨 Styles FINAUX après forçage:', {
                                display: finalStyle.display,
                                visibility: finalStyle.visibility,
                                opacity: finalStyle.opacity,
                                zIndex: finalStyle.zIndex,
                                position: finalStyle.position
                            });
                        }, 200);
                    }
                }, 100);
                
                if (modalId === 'stockVerificationModal') {
                    console.log('Chargement des produits suivis');
                    loadStockVerification();
                }
            } else {
                console.error('Modal non trouvé:', modalId);
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Fonction de test pour forcer l'affichage du modal
        window.forceShowStockModal = function() {
            console.log('🚀 Test de forçage du modal de stock...');
            const modal = document.getElementById('stockVerificationModal');
            if (modal) {
                // Supprimer tous les styles conflictuels
                modal.style.cssText = '';
                
                // Appliquer les styles de force
                modal.style.display = 'flex';
                modal.style.position = 'fixed';
                modal.style.top = '0';
                modal.style.left = '0';
                modal.style.right = '0';
                modal.style.bottom = '0';
                modal.style.background = 'rgba(0, 0, 0, 0.8)';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                modal.style.zIndex = '99999';
                modal.style.opacity = '1';
                modal.style.visibility = 'visible';
                
                console.log('✅ Modal forcé avec z-index 99999');
                loadStockVerification();
            } else {
                console.error('❌ Modal non trouvé');
            }
        };

        // Fonction pour ajuster le stock
        function ajusterStock(produitId, produitData = null) {
            if (produitData) {
                afficherModalStock(produitData);
                return;
            }
            
            // Récupérer les données du produit depuis la ligne du tableau
            const row = document.querySelector(`tr[data-produit-id="${produitId}"]`);
            if (row) {
                const cells = row.querySelectorAll('td');
                const produitData = {
                    id: produitId,
                    reference: cells[0].textContent.trim(),
                    nom: cells[1].textContent.trim(),
                    quantite: parseInt(cells[4].querySelector('.badge').textContent.trim()),
                    prix_achat: parseFloat(cells[2].textContent.replace('€', '').trim())
                };
                afficherModalStock(produitData);
            }
        }

        function afficherModalStock(data) {
            currentProductData = data;
            document.getElementById('stock_produit_id').value = data.id;
            document.getElementById('stock_product_name').textContent = data.nom || 'Produit sans nom';
            document.getElementById('stock_product_ref').textContent = data.reference || 'Sans référence';
            document.getElementById('stock_current_stock').textContent = `Stock actuel: ${data.quantite || 0}`;
            document.getElementById('stock_quantite').value = 1;
            document.getElementById('stock_prix_vente').value = data.prix_achat || 0;
            
            // Réinitialiser le formulaire
            document.getElementById('motif').value = '';
            document.getElementById('motif-display').style.display = 'none';
            document.getElementById('partenaire-selection').style.display = 'none';
            document.getElementById('stock-alert').style.display = 'none';
            document.querySelectorAll('.motif-btn').forEach(btn => btn.classList.remove('active'));
            
            openModal('stockModal');
        }

        function modifierProduit(id) {
            // Récupérer les données du produit et ouvrir le modal d'édition
            alert('Fonction de modification à implémenter');
        }

        function supprimerProduit(produitId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php?page=inventaire_actions';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'supprimer_produit';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'produit_id';
                idInput.value = produitId;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Gestion des contrôles de quantité
        function changeQuantity(delta) {
            const input = document.getElementById('stock_quantite');
            const value = parseInt(input.value) + delta;
            if (value >= 1) {
                input.value = value;
            }
        }

        // Gestion du type de mouvement
        document.querySelectorAll('input[name="type_mouvement"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'entree') {
                    document.getElementById('motifs-entree').style.display = 'grid';
                    document.getElementById('motifs-sortie').style.display = 'none';
                } else {
                    document.getElementById('motifs-entree').style.display = 'none';
                    document.getElementById('motifs-sortie').style.display = 'grid';
                }
                // Réinitialiser le motif
                document.getElementById('motif').value = '';
                document.getElementById('motif-display').style.display = 'none';
                document.querySelectorAll('.motif-btn').forEach(btn => btn.classList.remove('active'));
            });
        });

        // Gestion des boutons de motif
        document.querySelectorAll('.motif-btn').forEach(button => {
            button.addEventListener('click', function() {
                const motif = this.getAttribute('data-motif');
                document.getElementById('motif').value = motif;
                
                // Afficher le motif sélectionné
                const motifDisplay = document.getElementById('motif-display');
                motifDisplay.textContent = 'Motif sélectionné: ' + motif;
                motifDisplay.style.display = 'block';
                
                // Marquer ce bouton comme actif
                document.querySelectorAll('.motif-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Afficher la sélection de partenaire si nécessaire
                if (motif === 'Prêté à un partenaire' || motif === 'Retour de prêt') {
                    document.getElementById('partenaire-selection').style.display = 'block';
                    chargerPartenaires();
                } else {
                    document.getElementById('partenaire-selection').style.display = 'none';
                }
            });
        });

        // Fonction pour charger les partenaires
        function chargerPartenaires() {
            fetch('ajax/get_partenaires.php')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('partenaire_id');
                    select.innerHTML = '<option value="">Choisir un partenaire...</option>';
                    
                    if (data.success && data.partenaires) {
                        data.partenaires.forEach(partenaire => {
                            const option = document.createElement('option');
                            option.value = partenaire.id;
                            option.textContent = partenaire.nom;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des partenaires:', error);
                });
        }

        // Fonction pour soumettre l'ajustement de stock
        function submitStock() {
            const form = document.getElementById('stockForm');
            const stockAlert = document.getElementById('stock-alert');
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const motif = document.getElementById('motif').value;
            const partenaireId = document.getElementById('partenaire_id').value;
            
            if ((motif === 'Prêté à un partenaire' || motif === 'Retour de prêt') && !partenaireId) {
                stockAlert.className = 'alert alert-danger';
                stockAlert.textContent = 'Veuillez sélectionner un partenaire';
                stockAlert.style.display = 'block';
                return;
            }
            
            const formData = {
                produit_id: document.getElementById('stock_produit_id').value,
                type_mouvement: document.querySelector('input[name="type_mouvement"]:checked').value,
                quantite: document.getElementById('stock_quantite').value,
                motif: motif,
                partenaire_id: partenaireId,
                prix_vente: document.getElementById('stock_prix_vente').value
            };
            
            fetch('ajax/update_stock.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    stockAlert.className = 'alert alert-danger';
                    stockAlert.textContent = data.error;
                    stockAlert.style.display = 'block';
                } else if (data.success) {
                    closeModal('stockModal');
                    
                    // Mettre à jour l'affichage
                    const produitRow = document.querySelector(`tr[data-produit-id="${data.produit.id}"]`);
                    if (produitRow) {
                        const quantiteCell = produitRow.querySelector('.badge');
                        if (quantiteCell) {
                            quantiteCell.textContent = data.produit.quantite;
                            
                            const seuilAlerte = parseInt(produitRow.getAttribute('data-seuil-alerte'));
                            if (data.produit.quantite <= 0) {
                                quantiteCell.className = 'badge badge-danger';
                            } else if (data.produit.quantite <= seuilAlerte) {
                                quantiteCell.className = 'badge badge-warning';
                            } else {
                                quantiteCell.className = 'badge badge-success';
                            }
                        }
                    }
                    
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                stockAlert.className = 'alert alert-danger';
                stockAlert.textContent = 'Erreur lors de la communication avec le serveur';
                stockAlert.style.display = 'block';
            });
        }

        // Fonctions de recherche et filtrage
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#produitsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        document.getElementById('filterStatus').addEventListener('change', function() {
            const filter = this.value;
            const rows = document.querySelectorAll('#produitsTable tbody tr');
            
            rows.forEach(row => {
                const badge = row.querySelector('.badge');
                const quantity = parseInt(badge.textContent);
                const seuil = parseInt(row.getAttribute('data-seuil-alerte'));
                
                let show = true;
                switch(filter) {
                    case 'stock':
                        show = quantity > 0;
                        break;
                    case 'alert':
                        show = quantity <= seuil && quantity > 0;
                        break;
                    case 'out':
                        show = quantity === 0;
                        break;
                }
                
                row.style.display = show ? '' : 'none';
            });
        });

        // Scanner functions
        function openScanner() {
            openModal('scannerModal');
            startCamera();
        }

        function closeScanner() {
            stopCamera();
            closeModal('scannerModal');
        }

        function startCamera() {
            const status = document.getElementById('scanner-status');
            status.className = 'alert alert-info';
            status.textContent = 'Initialisation de la caméra...';
            
            navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "environment",
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            })
            .then(stream => {
                currentStream = stream;
                const video = document.getElementById('scanner-video');
                video.srcObject = stream;
                
                initQuagga(stream);
            })
            .catch(err => {
                console.error('Erreur caméra:', err);
                status.className = 'alert alert-danger';
                status.textContent = 'Erreur d\'accès à la caméra: ' + err.message;
            });
        }

        function stopCamera() {
            if (currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
                currentStream = null;
            }
            
            if (scannerInitialized && typeof Quagga !== 'undefined') {
                try {
                    Quagga.stop();
                    scannerInitialized = false;
                } catch (e) {
                    console.error("Erreur lors de l'arrêt de Quagga:", e);
                }
            }
        }

        function initQuagga(stream) {
            const status = document.getElementById('scanner-status');
            
            const config = {
                inputStream: {
                    type: "LiveStream",
                    target: document.getElementById('scanner-video'),
                    constraints: {
                        width: 640,
                        height: 480
                    }
                },
                locator: {
                    patchSize: "large",
                    halfSample: false
                },
                numOfWorkers: 2,
                frequency: 5,
                decoder: {
                    readers: ["ean_reader", "ean_8_reader", "code_128_reader", "code_39_reader"],
                    multiple: false
                },
                locate: true
            };
            
            Quagga.init(config, function(err) {
                if (err) {
                    console.error("Erreur Quagga:", err);
                    status.className = 'alert alert-danger';
                    status.textContent = "Erreur d'initialisation du scanner";
                    return;
                }
                
                scannerInitialized = true;
                status.className = 'alert alert-success';
                status.textContent = "Scanner actif. Placez un code-barres devant la caméra.";
                
                Quagga.onDetected(function(result) {
                    if (result && result.codeResult && result.codeResult.code) {
                        const code = result.codeResult.code;
                        console.log("Code détecté:", code);
                        verifierProduit(code);
                    }
                });
                
                Quagga.start();
            });
        }

        function verifierProduit(code) {
            fetch('ajax/verifier_produit.php?code=' + encodeURIComponent(code))
                .then(response => response.json())
                .then(data => {
                    stopCamera();
                    closeModal('scannerModal');
                    
                    if (data.existe && data.id) {
                        const produitData = {
                            id: data.id,
                            nom: data.nom,
                            reference: data.reference,
                            quantite: data.quantite,
                            prix_achat: data.prix_achat
                        };
                        ajusterStock(data.id, produitData);
                    } else {
                        const referenceInput = document.querySelector('#addProductModal input[name="reference"]');
                        referenceInput.value = code;
                        openModal('addProductModal');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la vérification du produit: ' + error.message);
                });
        }

        // Export function
        function exportInventory() {
            const filter = document.getElementById('filterStatus').value;
            window.open(`ajax/export_print.php?filter=${encodeURIComponent(filter)}`, '_blank');
        }

        // Fermeture des modals en cliquant à l'extérieur
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    if (this.id === 'scannerModal') {
                        stopCamera();
                    }
                }
            });
        });

        // Fonctions pour la vérification des stocks
        function loadStockVerification() {
            const container = document.getElementById('stockCardsContainer');
            container.innerHTML = '<div class="loading-message"><i class="fas fa-spinner fa-spin"></i> Chargement des produits...</div>';
            
            console.log('🔍 Chargement des produits suivis...');
            
            fetch('ajax/get_tracked_products.php')
                .then(response => {
                    console.log('📡 Réponse reçue:', response.status, response.statusText);
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('❌ Erreur serveur:', text);
                            throw new Error(`Erreur ${response.status}: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('📦 Données reçues:', data);
                    if (data.success) {
                        displayStockCards(data.products);
                        document.getElementById('totalStockItems').textContent = data.products.length;
                        
                        if (!data.column_exists) {
                            console.warn('⚠️ La colonne suivre_stock n\'existe pas encore');
                        }
                    } else {
                        console.error('❌ Erreur dans les données:', data.error);
                        container.innerHTML = `<div class="no-products-message"><i class="fas fa-exclamation-circle"></i><h3>Erreur</h3><p>${data.error}</p></div>`;
                    }
                })
                .catch(error => {
                    console.error('💥 Erreur complète:', error);
                    container.innerHTML = `<div class="no-products-message"><i class="fas fa-exclamation-triangle"></i><h3>Erreur de chargement</h3><p>${error.message}</p></div>`;
                });
        }

        function displayStockCards(products) {
            const container = document.getElementById('stockCardsContainer');
            
            console.log('📋 Affichage des cartes produits:', products.length, 'produits');
            
            if (products.length === 0) {
                container.innerHTML = `
                    <div class="no-products-message" style="text-align: center; padding: 3rem; color: #6c757d;">
                        <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem; color: #ffc107;"></i>
                        <h3 style="margin-bottom: 1rem; color: #495057;">Aucun produit suivi</h3>
                        <p style="margin-bottom: 1.5rem;">Aucun produit n'est configuré pour le suivi de stock.</p>
                        <p style="font-size: 0.9rem; color: #6c757d;">
                            💡 <strong>Pour configurer le suivi :</strong><br>
                            1. Cliquez sur "Nouveau Produit"<br>
                            2. Cochez l'option "Suivre le stock"<br>
                            3. Sauvegardez le produit
                        </p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = products.map(product => {
                const quantityClass = product.quantite <= 0 ? 'low' : 
                                    product.quantite <= product.seuil_alerte ? 'medium' : 'high';
                
                return `
                    <div class="stock-card" onclick="openQuickUpdate(${product.id}, '${product.nom.replace(/'/g, "\\'")}', '${product.reference}', ${product.quantite})">
                        <div class="stock-card-header">
                            <div>
                                <div class="stock-card-title">${product.nom}</div>
                                <div class="stock-card-reference">${product.reference}</div>
                            </div>
                        </div>
                        <div class="stock-card-quantity">
                            <div class="stock-quantity-badge ${quantityClass}">
                                ${product.quantite}
                            </div>
                        </div>
                        <div class="stock-card-footer">
                            Seuil d'alerte: ${product.seuil_alerte}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function openQuickUpdate(id, nom, reference, quantite) {
            document.getElementById('quick_produit_id').value = id;
            document.getElementById('quick_product_name').textContent = nom;
            document.getElementById('quick_product_ref').textContent = reference;
            document.getElementById('quick_current_stock').textContent = `Stock actuel: ${quantite}`;
            document.getElementById('quick_quantite').value = quantite;
            document.getElementById('quick-alert').style.display = 'none';
            
            closeModal('stockVerificationModal');
            openModal('quickUpdateModal');
        }

        function changeQuickQuantity(delta) {
            const input = document.getElementById('quick_quantite');
            const value = parseInt(input.value) + delta;
            if (value >= 0) {
                input.value = value;
            }
        }

        function submitQuickUpdate() {
            const form = document.getElementById('quickUpdateForm');
            const alert = document.getElementById('quick-alert');
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = {
                produit_id: document.getElementById('quick_produit_id').value,
                nouvelle_quantite: document.getElementById('quick_quantite').value,
                motif: document.getElementById('quick_motif').value
            };
            
            fetch('ajax/quick_update_stock.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert.className = 'alert alert-danger';
                    alert.textContent = data.error;
                    alert.style.display = 'block';
                } else if (data.success) {
                    closeModal('quickUpdateModal');
                    
                    // Mettre à jour l'affichage dans le tableau principal si visible
                    const produitRow = document.querySelector(`tr[data-produit-id="${data.produit.id}"]`);
                    if (produitRow) {
                        const quantiteCell = produitRow.querySelector('.badge');
                        if (quantiteCell) {
                            quantiteCell.textContent = data.produit.quantite;
                            
                            const seuilAlerte = parseInt(produitRow.getAttribute('data-seuil-alerte'));
                            if (data.produit.quantite <= 0) {
                                quantiteCell.className = 'badge badge-danger';
                            } else if (data.produit.quantite <= seuilAlerte) {
                                quantiteCell.className = 'badge badge-warning';
                            } else {
                                quantiteCell.className = 'badge badge-success';
                            }
                        }
                    }
                    
                    alert('Stock mis à jour avec succès !');
                    
                    // Recharger la vérification des stocks si elle est ouverte
                    if (document.getElementById('stockVerificationModal').classList.contains('active')) {
                        loadStockVerification();
                        openModal('stockVerificationModal');
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert.className = 'alert alert-danger';
                alert.textContent = 'Erreur lors de la communication avec le serveur';
                alert.style.display = 'block';
            });
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
                        const reference = card.querySelector('.stock-card-reference').textContent.toLowerCase();
                        
                        if (title.includes(searchTerm) || reference.includes(searchTerm)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        });

        // Fonction supprimée - déplacée vers le haut pour éviter les conflits

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page inventaire chargée');
        });
    </script>
</body>
</html>
