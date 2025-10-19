<?php
// Inclure la configuration de session
require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config/subdomain_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
if (!$shop_pdo) {
    die('Erreur de connexion à la base de données du magasin');
}

// Récupérer les statistiques
$stmt = $shop_pdo->prepare("SELECT COUNT(*) as total FROM partenaires WHERE actif = 1");
$stmt->execute();
$nombre_partenaires_actifs = $stmt->fetchColumn();

$stmt = $shop_pdo->prepare("SELECT SUM(solde_actuel) as total FROM soldes_partenaires");
$stmt->execute();
$solde_total = $stmt->fetchColumn() ?: 0;

// Récupérer tous les partenaires pour le tableau
$stmt = $shop_pdo->prepare("
    SELECT 
        p.id,
        p.nom,
        p.email,
        p.telephone,
        p.actif,
        COALESCE(s.solde_actuel, 0) as solde_actuel
        FROM partenaires p 
        LEFT JOIN soldes_partenaires s ON p.id = s.partenaire_id
    ORDER BY p.nom ASC
");
$stmt->execute();
    $partenaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Partenaires - <?php echo $_SESSION['shop_name'] ?? 'GeekBoard'; ?></title>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Header Section */
        .hero-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .hero-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .hero-subtitle {
            color: #666;
            font-size: 1.1rem;
        }
        
        .hero-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        /* Modern Buttons */
        .modern-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }
        
        .modern-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .modern-btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #56ab2f, #a8e6cf);
            color: white;
            box-shadow: 0 4px 15px rgba(86, 171, 47, 0.4);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(86, 171, 47, 0.6);
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 8px;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
            box-shadow: 0 4px 15px rgba(116, 185, 255, 0.4);
        }
        
        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #666;
            font-size: 1rem;
            font-weight: 500;
        }
        
        /* Modern Table */
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .table-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .modern-table thead {
            background: linear-gradient(135deg, #2d3748, #4a5568);
            color: white;
        }
        
        .modern-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: none;
        }
        
        .modern-table th:first-child {
            border-top-left-radius: 12px;
        }
        
        .modern-table th:last-child {
            border-top-right-radius: 12px;
        }
        
        .modern-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f7fafc;
        }
        
        .modern-table tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc, #edf2f7);
            transform: scale(1.01);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .modern-table td {
            padding: 1.25rem 1.5rem;
            border: none;
            vertical-align: middle;
        }
        
        .partner-name {
            font-weight: 700;
            color: #2d3748;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        
        .partner-id {
            font-size: 0.875rem;
            color: #718096;
        }
        
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #4a5568;
        }
        
        .contact-item i {
            width: 16px;
            text-align: center;
            color: #718096;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-active {
            background: linear-gradient(135deg, #68d391, #38a169);
            color: white;
            box-shadow: 0 2px 10px rgba(104, 211, 145, 0.4);
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #fc8181, #e53e3e);
            color: white;
            box-shadow: 0 2px 10px rgba(252, 129, 129, 0.4);
        }
        
        /* Balance Display */
        .balance {
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        .balance-positive {
            color: #38a169;
        }
        
        .balance-negative {
            color: #e53e3e;
        }
        
        .balance-zero {
            color: #718096;
        }
        
        /* Action Buttons */
        .actions-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        /* Modern Modals */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease;
            padding: 20px;
            box-sizing: border-box;
        }
        
        .modal-overlay.active {
            display: flex !important;
            opacity: 1 !important;
        }
        
        .modal {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 90vw;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            transform: scale(0.9);
            transition: transform 0.3s ease;
            position: relative;
            z-index: 10001;
            display: flex;
            flex-direction: column;
            min-height: 200px;
            min-width: 300px;
        }
        
        .modal-overlay.active .modal {
            transform: scale(1) !important;
        }

        /* Transaction Modal Styles */
        .transaction-modal {
            max-width: 700px !important;
        }

        /* Partner Selector */
        .partner-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .partner-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .partner-card:hover {
            border-color: #007bff;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.2);
        }

        .partner-card.selected {
            border-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .partner-info {
            display: flex;
            flex-direction: column;
        }

        .partner-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .partner-balance {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .partner-balance.positive {
            color: #28a745;
        }

        .partner-balance.negative {
            color: #dc3545;
        }

        .partner-check {
            color: #28a745;
            font-size: 1.2rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .partner-card.selected .partner-check {
            opacity: 1;
        }

        /* Transaction Type Selector */
        .transaction-type-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .type-btn {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .type-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .type-btn i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .type-btn span {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .type-btn small {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .credit-btn:hover,
        .credit-btn.selected {
            border-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .debit-btn:hover,
        .debit-btn.selected {
            border-color: #dc3545;
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        /* Amount Input Container */
        .amount-input-container {
            margin-top: 1rem;
        }

        .amount-display {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .currency-symbol {
            font-size: 1.2rem;
            font-weight: 600;
            color: #28a745;
            margin-right: 0.5rem;
        }

        .amount-input {
            border: none;
            background: transparent;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            flex: 1;
            outline: none;
            text-align: center;
        }

        .keyboard-toggle-btn {
            background: #007bff;
            border: none;
            border-radius: 8px;
            padding: 0.5rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 0.5rem;
        }

        .keyboard-toggle-btn:hover {
            background: #0056b3;
            transform: scale(1.05);
        }

        /* Numeric Keyboard */
        .numeric-keyboard {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            display: none;
            animation: slideDown 0.3s ease;
        }

        .numeric-keyboard.active {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .keyboard-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .keyboard-row:last-child {
            grid-template-columns: 1fr;
        }

        .key-btn {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 50px;
        }

        .key-btn:hover {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-color: #007bff;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0, 123, 255, 0.2);
        }

        .key-btn:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(0, 123, 255, 0.3);
        }

        .key-zero {
            grid-column: 1;
        }

        .key-decimal {
            grid-column: 2;
        }

        .key-delete {
            grid-column: 3;
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-color: #ffc107;
            color: #856404;
        }

        .key-delete:hover {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            border-color: #e0a800;
        }

        .key-clear {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-color: #dc3545;
            color: #721c24;
        }

        .key-clear:hover {
            background: linear-gradient(135deg, #f5c6cb 0%, #f1b0b7 100%);
            border-color: #c82333;
        }

        /* Modern Textarea */
        .modern-textarea {
            width: 100%;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            font-family: inherit;
            font-size: 1rem;
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            transition: all 0.3s ease;
            resize: vertical;
            min-height: 100px;
        }

        .modern-textarea:focus {
            outline: none;
            border-color: #007bff;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        /* Form Labels */
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: #007bff;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
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
            background: rgba(255, 255, 255, 0.2);
        }
        
        .modal-body {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .form-check-input {
            width: 1.2rem;
            height: 1.2rem;
        }
        
        /* Loading and Empty States */
        .loading-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            color: #718096;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #718096;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .hero-content {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .modern-table {
                font-size: 0.875rem;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 0.75rem;
            }
            
            .actions-group {
                flex-direction: column;
            }
            
            .modal {
                margin: 1rem;
                max-width: calc(100vw - 2rem);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="hero-content">
        <div>
                    <h1 class="hero-title">
                        <i class="fas fa-handshake"></i>
                        Gestion des Partenaires
                    </h1>
                    <p class="hero-subtitle">Gérez vos relations commerciales, transactions et services partenaires</p>
                </div>
                <div class="hero-actions">
                    <button class="modern-btn btn-primary" onclick="openModal('gererPartenairesModal')">
                        <i class="fas fa-users-cog"></i>
                        Gérer
            </button>
                    <button class="modern-btn btn-success" onclick="openModal('ajouterTransactionModal')">
                        <i class="fas fa-plus"></i>
                        Transaction
            </button>
                </div>
        </div>
    </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stats-card">
                <div class="stats-number"><?php echo $nombre_partenaires_actifs; ?></div>
                <div class="stats-label">Partenaires Actifs</div>
                </div>
            <div class="stats-card">
                <div class="stats-number"><?php echo number_format($solde_total, 2); ?> €</div>
                <div class="stats-label">Solde Global</div>
            </div>
            <div class="stats-card">
                <div class="stats-number"><?php echo count($partenaires); ?></div>
                <div class="stats-label">Total Partenaires</div>
        </div>
            <div class="stats-card">
                <div class="stats-number"><?php echo date('m/Y'); ?></div>
                <div class="stats-label">Période</div>
        </div>
    </div>

        <!-- Main Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="fas fa-list"></i>
                    Liste des Partenaires
                </h2>
                <button class="modern-btn btn-primary" onclick="openModal('ajouterPartenaireModal')">
                    <i class="fas fa-user-plus"></i>
                    Nouveau Partenaire
                </button>
                </div>
            
            <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Partenaire</th>
                        <th>Contact</th>
                        <th>Statut</th>
                        <th>Solde</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                            <?php foreach ($partenaires as $partenaire): ?>
                                <tr>
                                    <td>
                            <div class="partner-name"><?php echo htmlspecialchars($partenaire['nom']); ?></div>
                            <div class="partner-id">ID: <?php echo $partenaire['id']; ?></div>
                        </td>
                        <td>
                            <div class="contact-info">
                                <?php if ($partenaire['email']): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo htmlspecialchars($partenaire['email']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($partenaire['telephone']): ?>
                                    <div class="contact-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($partenaire['telephone']); ?></span>
                                    </div>
                                <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                            <?php if ($partenaire['actif']): ?>
                                <span class="status-badge status-active">
                                    <i class="fas fa-check-circle"></i>
                                    Actif
                                        </span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">
                                    <i class="fas fa-times-circle"></i>
                                    Inactif
                                </span>
                            <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                            $solde = (float)$partenaire['solde_actuel'];
                            $class = $solde > 0 ? 'balance-positive' : ($solde < 0 ? 'balance-negative' : 'balance-zero');
                            $prefix = $solde > 0 ? '+' : '';
                            ?>
                            <span class="balance <?php echo $class; ?>">
                                <?php echo $prefix . number_format($solde, 2); ?> €
                            </span>
                                    </td>
                                    <td>
                            <div class="actions-group">
                                <button class="modern-btn btn-secondary btn-small" 
                                        onclick="afficherHistoriqueTransactions(<?php echo $partenaire['id']; ?>, '<?php echo htmlspecialchars($partenaire['nom'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-history"></i> Historique
                                </button>
                                <button class="modern-btn btn-success btn-small" 
                                        onclick="envoyerLien(<?php echo $partenaire['id']; ?>, '<?php echo htmlspecialchars($partenaire['nom'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-paper-plane"></i> Lien
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Gérer les Partenaires -->
    <div class="modal-overlay" id="gererPartenairesModal">
        <div class="modal" style="width: 1000px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-users-cog"></i>
                    Gérer les Partenaires
                </h3>
                <button class="modal-close" onclick="closeModal('gererPartenairesModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 1.5rem;">
                    <button class="modern-btn btn-primary" onclick="openModal('ajouterPartenaireModal')">
                        <i class="fas fa-user-plus"></i>
                        Ajouter un Partenaire
                    </button>
                </div>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partenaires as $partenaire): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($partenaire['nom']); ?></td>
                            <td><?php echo htmlspecialchars($partenaire['email'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($partenaire['telephone'] ?: 'N/A'); ?></td>
                            <td>
                                <?php if ($partenaire['actif']): ?>
                                    <span class="status-badge status-active">
                                        <i class="fas fa-check-circle"></i>
                                        Actif
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">
                                        <i class="fas fa-times-circle"></i>
                                        Inactif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions-group">
                                    <button class="modern-btn btn-secondary btn-small" 
                                            onclick="afficherHistoriqueTransactions(<?php echo $partenaire['id']; ?>, '<?php echo htmlspecialchars($partenaire['nom'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    <button class="modern-btn btn-success btn-small" 
                                            onclick="envoyerLien(<?php echo $partenaire['id']; ?>, '<?php echo htmlspecialchars($partenaire['nom'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-link"></i>
                                    </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
        </div>
    </div>
</div>

    <!-- Modal Ajouter Partenaire -->
    <div class="modal-overlay" id="ajouterPartenaireModal">
        <div class="modal" style="width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-plus"></i>
                    Ajouter un Partenaire
                </h3>
                <button class="modal-close" onclick="closeModal('ajouterPartenaireModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="ajouterPartenaireForm">
                    <div class="form-group">
                        <label class="form-label">Nom du partenaire *</label>
                        <input type="text" class="form-control" name="nom" required>
                            </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                                </div>
                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" name="telephone">
                            </div>
                    <div class="form-group">
                        <label class="form-label">Adresse</label>
                        <textarea class="form-control" name="adresse" rows="2"></textarea>
                        </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="actif" checked>
                        <label class="form-label">Partenaire actif</label>
                                </div>
                </form>
                                    </div>
            <div class="modal-footer">
                <button class="modern-btn btn-secondary" onclick="closeModal('ajouterPartenaireModal')">Annuler</button>
                <button class="modern-btn btn-primary" onclick="ajouterPartenaire()">Enregistrer</button>
                                </div>
                            </div>
                        </div>

    <!-- Modal Ajouter Transaction -->
    <div class="modal-overlay" id="ajouterTransactionModal">
        <div class="modal transaction-modal" style="width: 700px; max-height: 95vh;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-plus-circle"></i>
                    Nouvelle Transaction
                </h3>
                <button class="modal-close" onclick="closeModal('ajouterTransactionModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding: 2rem; overflow-y: auto;">
                <form id="ajouterTransactionForm">
                    <!-- Sélection du partenaire avec cards -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Partenaire *
                                </label>
                        <div class="partner-selector">
                            <?php foreach ($partenaires as $partenaire): ?>
                                <div class="partner-card" onclick="selectPartner(<?php echo $partenaire['id']; ?>, '<?php echo htmlspecialchars($partenaire['nom']); ?>')" data-partner-id="<?php echo $partenaire['id']; ?>">
                                    <div class="partner-info">
                                        <span class="partner-name"><?php echo htmlspecialchars($partenaire['nom']); ?></span>
                                        <span class="partner-balance <?php echo $partenaire['solde_actuel'] >= 0 ? 'positive' : 'negative'; ?>">
                                            <?php echo number_format($partenaire['solde_actuel'], 2); ?> €
                                        </span>
                            </div>
                                    <i class="fas fa-check partner-check"></i>
                        </div>
                            <?php endforeach; ?>
                    </div>
                        <input type="hidden" name="partenaire_id" id="selectedPartnerId" required>
            </div>

                    <!-- Type de transaction avec boutons -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-exchange-alt"></i> Type de transaction *
                        </label>
                        <div class="transaction-type-selector">
                            <button type="button" class="type-btn credit-btn" onclick="selectTransactionType('credit')" data-type="credit">
                                <i class="fas fa-arrow-up"></i>
                                <span>Crédit</span>
                                <small>Ce que nous devons</small>
                        </button>
                            <button type="button" class="type-btn debit-btn" onclick="selectTransactionType('debit')" data-type="debit">
                                <i class="fas fa-arrow-down"></i>
                                <span>Débit</span>
                                <small>Ce qu'on nous doit</small>
                        </button>
                    </div>
                        <input type="hidden" name="type" id="selectedTransactionType" required>
                </div>

                    <!-- Montant avec clavier numérique -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-euro-sign"></i> Montant (€) *
                        </label>
                        <div class="amount-input-container">
                            <div class="amount-display">
                                <span class="currency-symbol">€</span>
                                <input type="text" name="montant" id="transactionAmount" 
                                       class="amount-input" placeholder="0,00" readonly>
                                <button type="button" class="keyboard-toggle-btn" onclick="toggleTransactionKeyboard()" title="Basculer clavier/saisie">
                                    <i class="fas fa-keyboard"></i>
                                </button>
            </div>
                            
                            <!-- Clavier numérique visuel -->
                            <div class="numeric-keyboard active" id="transactionKeyboard">
                                <div class="keyboard-row">
                                    <button type="button" class="key-btn" onclick="addTransactionDigit('7')">7</button>
                                    <button type="button" class="key-btn" onclick="addTransactionDigit('8')">8</button>
                                    <button type="button" class="key-btn" onclick="addTransactionDigit('9')">9</button>
        </div>
                                <div class="keyboard-row">
                                    <button type="button" class="key-btn" onclick="addTransactionDigit('4')">4</button>
                                    <button type="button" class="key-btn" onclick="addTransactionDigit('5')">5</button>
                                    <button type="button" class="key-btn" onclick="addTransactionDigit('6')">6</button>
    </div>
                                <div class="keyboard-row">
                                    <button type="button" class="key-btn" onclick="addTransactionDigit('1')">1</button>
                                    <button type="button" class="key-btn" onclick="addTransactionDigit('2')">2</button>
                                    <button type="button" class="key-btn" onclick="addTransactionDigit('3')">3</button>
</div>
                                <div class="keyboard-row">
                                    <button type="button" class="key-btn key-zero" onclick="addTransactionDigit('0')">0</button>
                                    <button type="button" class="key-btn key-decimal" onclick="addTransactionDecimal()">,</button>
                                    <button type="button" class="key-btn key-delete" onclick="deleteTransactionDigit()">
                                        <i class="fas fa-backspace"></i>
                                    </button>
            </div>
                                <div class="keyboard-row">
                                    <button type="button" class="key-btn key-clear" onclick="clearTransactionAmount()">
                                        <i class="fas fa-trash"></i> Effacer
                                    </button>
                    </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label class="form-label" for="transactionDescription">
                            <i class="fas fa-comment"></i> Description *
                        </label>
                        <textarea name="description" id="transactionDescription" 
                                  class="modern-textarea" rows="3" 
                                  placeholder="Description de la transaction..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="modern-btn btn-secondary" onclick="closeModal('ajouterTransactionModal')">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="modern-btn btn-success" onclick="ajouterTransaction()">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
        </div>
    </div>
</div>

<!-- Modal Historique des Transactions -->
    <div class="modal-overlay" id="historiqueTransactionsModal">
        <div class="modal" style="width: 1200px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-history"></i>
                    Historique des Transactions - <span id="partenaireNom"></span>
                </h3>
                <button class="modal-close" onclick="closeModal('historiqueTransactionsModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="historiqueTransactions">
                    <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
</div>

    <!-- Modal Envoyer un Lien -->
    <div class="modal-overlay" id="envoyerLienModal">
        <div class="modal" style="width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-paper-plane"></i>
                    Envoyer un lien - <span id="partenaireNomLien"></span>
                </h3>
                <button class="modal-close" onclick="closeModal('envoyerLienModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Lien d'accès partenaire :</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" class="form-control" id="lienPartenaire" readonly style="flex: 1;">
                        <button class="modern-btn btn-secondary" onclick="copierLien()">
                            <i class="fas fa-copy"></i>
                    </button>
                </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Numéro de téléphone :</label>
                    <input type="tel" class="form-control" id="numeroTelephone" placeholder="Ex: 06 12 34 56 78">
            </div>
            </div>
            <div class="modal-footer">
                <button class="modern-btn btn-secondary" onclick="closeModal('envoyerLienModal')">Fermer</button>
                <button class="modern-btn btn-success" onclick="envoyerSMS()">
                    <i class="fas fa-sms"></i> Envoyer par SMS
                </button>
        </div>
    </div>
</div>

    <script>
        // Variables globales
        let currentPartenaireId = null;
        let currentPartenaireNom = '';

        // Fonctions pour gérer les modals
        function openModal(modalId) {
            console.log('Ouverture du modal:', modalId);
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // Initialiser le modal de transaction si c'est celui-ci
                if (modalId === 'ajouterTransactionModal') {
                    initTransactionModal();
                }
                
                console.log('Modal ouvert avec succès');
            } else {
                console.error('Modal non trouvé:', modalId);
            }
        }

        function closeModal(modalId) {
            console.log('Fermeture du modal:', modalId);
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
                document.body.style.overflow = 'auto';
            }
        }

        // Fermer le modal en cliquant sur l'overlay
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });

        // Fonction pour ajouter un partenaire
        function ajouterPartenaire() {
            const form = document.getElementById('ajouterPartenaireForm');
            const formData = new FormData(form);
            
            fetch('ajax/add_partenaire.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Partenaire ajouté avec succès !');
                    location.reload();
                } else {
                    alert('Erreur : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            });
        }

        // Fonction pour ajouter une transaction
        function ajouterTransaction() {
            // Récupérer les valeurs des champs
            const partnerId = document.getElementById('selectedPartnerId').value;
            const transactionType = document.getElementById('selectedTransactionType').value;
            const amount = keyboardMode ? 
                transactionAmountValue.replace(',', '.') : 
                document.getElementById('transactionAmount').value.replace(',', '.');
            const description = document.getElementById('transactionDescription').value;
            
            // Validation
            if (!partnerId) {
                alert('Veuillez sélectionner un partenaire');
                return;
            }
            if (!transactionType) {
                alert('Veuillez sélectionner le type de transaction');
                return;
            }
            if (!amount || parseFloat(amount) <= 0) {
                alert('Veuillez saisir un montant valide');
                return;
            }
            if (!description.trim()) {
                alert('Veuillez saisir une description');
                return;
            }
            
            // Préparer les données
            const formData = new FormData();
            formData.append('partenaire_id', partnerId);
            formData.append('type', transactionType);
            formData.append('montant', amount);
            formData.append('description', description);
            
            fetch('ajax/add_transaction_partenaire.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaction ajoutée avec succès !');
                    closeModal('ajouterTransactionModal');
                    location.reload();
                } else {
                    alert('Erreur : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            });
        }

        // Fonction pour afficher l'historique des transactions
        function afficherHistoriqueTransactions(partenaireId, partenaireNom) {
            currentPartenaireId = partenaireId;
            currentPartenaireNom = partenaireNom;
            
            document.getElementById('partenaireNom').textContent = partenaireNom;
            openModal('historiqueTransactionsModal');
            
            // Charger les transactions
            chargerTransactionsPartenaire(partenaireId);
        }

        // Fonction pour charger les transactions d'un partenaire
        function chargerTransactionsPartenaire(partenaireId) {
            const historiqueDiv = document.getElementById('historiqueTransactions');
            historiqueDiv.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div><p>Chargement...</p></div>';
            
            fetch(`ajax/get_transactions_partenaire.php?partenaire_id=${partenaireId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        afficherTransactions(data);
                    } else {
                        historiqueDiv.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Erreur de chargement</h3><p>' + data.message + '</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    historiqueDiv.innerHTML = '<div class="empty-state"><i class="fas fa-wifi-slash"></i><h3>Erreur de connexion</h3></div>';
                });
        }

        // Fonction pour afficher les transactions
        function afficherTransactions(data) {
                        const historiqueDiv = document.getElementById('historiqueTransactions');
            const solde = parseFloat(data.solde);
            const soldeClass = solde >= 0 ? 'balance-positive' : 'balance-negative';
            const soldePrefix = solde >= 0 ? '+' : '';
            
                        let html = `
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="stats-card">
                        <div class="stats-label">Solde Actuel</div>
                        <div class="stats-number ${soldeClass}">${soldePrefix}${Math.abs(solde).toFixed(2)} €</div>
                    </div>
                    <div class="stats-card">
                        <div class="stats-label">Transactions</div>
                        <div class="stats-number">${data.transactions.length}</div>
                    </div>
                </div>
                        `;
                        
                        if (data.transactions && data.transactions.length > 0) {
                html += '<table class="modern-table"><thead><tr><th>Date</th><th>Type</th><th>Montant</th><th>Description</th><th>Statut</th></tr></thead><tbody>';
                
                            data.transactions.forEach(transaction => {
                    const typeClass = transaction.type === 'credit' ? 'balance-positive' : 'balance-negative';
                    const typeIcon = transaction.type === 'credit' ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
                    const statusClass = transaction.transaction_status === 'pending' ? 'status-inactive' : 'status-active';
                    const statusText = transaction.transaction_status === 'pending' ? 'En attente' : 'Validée';
                    
                    // Boutons de validation pour les transactions en attente
                    let actionsHtml = '';
                    if (transaction.transaction_status === 'pending' && transaction.pending_id) {
                        actionsHtml = `
                            <div class="actions-group" style="margin-top: 0.5rem;">
                                <button class="modern-btn btn-success btn-small" 
                                        onclick="validerTransaction(${transaction.pending_id}, 'approve')"
                                        title="Accepter la transaction">
                                    <i class="fas fa-check"></i> Accepter
                                </button>
                                <button class="modern-btn btn-danger btn-small" 
                                        onclick="validerTransaction(${transaction.pending_id}, 'reject')"
                                        title="Refuser la transaction">
                                    <i class="fas fa-times"></i> Refuser
                                </button>
                            </div>
                        `;
                    }
                    
                            html += `
                                <tr>
                            <td>${new Date(transaction.date_transaction).toLocaleDateString('fr-FR')}</td>
                            <td><span class="${typeClass}"><i class="${typeIcon}"></i> ${transaction.type === 'credit' ? 'Crédit' : 'Débit'}</span></td>
                            <td><span class="${typeClass}">${parseFloat(transaction.montant).toFixed(2)} €</span></td>
                            <td>
                                ${transaction.description}
                                ${actionsHtml}
                                    </td>
                            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                                </tr>
                            `;
                });
                
                html += '</tbody></table>';
            } else {
                html += '<div class="empty-state"><i class="fas fa-info-circle"></i><h3>Aucune transaction</h3><p>Aucune transaction trouvée pour ce partenaire</p></div>';
                        }
                        
                        historiqueDiv.innerHTML = html;
        }

        // Fonction pour envoyer un lien
        function envoyerLien(partenaireId, partenaireNom) {
            currentPartenaireId = partenaireId;
            currentPartenaireNom = partenaireNom;
            
            document.getElementById('partenaireNomLien').textContent = partenaireNom;
            document.getElementById('lienPartenaire').value = `${window.location.origin}/partner_transaction.php?pid=${partenaireId}`;
            
            openModal('envoyerLienModal');
        }

        // Fonction pour copier le lien
        function copierLien() {
            const lienInput = document.getElementById('lienPartenaire');
            lienInput.select();
            document.execCommand('copy');
            alert('Lien copié dans le presse-papiers !');
        }

        // Fonction pour envoyer un SMS
        function envoyerSMS() {
            const telephone = document.getElementById('numeroTelephone').value;
            if (!telephone.trim()) {
                alert('Veuillez saisir un numéro de téléphone');
                return;
            }
            
            const lien = document.getElementById('lienPartenaire').value;
            
            fetch('ajax/send_partner_sms.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `partenaire_id=${currentPartenaireId}&telephone=${encodeURIComponent(telephone)}&lien=${encodeURIComponent(lien)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('SMS envoyé avec succès !');
                    closeModal('envoyerLienModal');
                    } else {
                    alert('Erreur : ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'envoi du SMS');
            });
        }

        // Fonction pour valider ou rejeter une transaction en attente
        function validerTransaction(pendingId, action) {
            console.log('Validation transaction:', pendingId, action);
            
            if (!confirm(`Êtes-vous sûr de vouloir ${action === 'approve' ? 'accepter' : 'refuser'} cette transaction ?`)) {
                return;
            }
            
            fetch('ajax/validate_partner_transaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `pending_id=${pendingId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Réponse validation:', data);
                if (data.success) {
                    alert(`Transaction ${action === 'approve' ? 'acceptée' : 'refusée'} avec succès !`);
                    // Recharger l'historique
                    if (currentPartenaireId) {
                        chargerTransactionsPartenaire(currentPartenaireId);
                    }
                } else {
                    alert('Erreur : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur validation:', error);
                alert('Une erreur est survenue lors de la validation');
            });
        }

        // Variables pour le clavier numérique
        let transactionAmountValue = '';
        let keyboardMode = true; // true = clavier visuel, false = saisie manuelle

        // Fonction pour sélectionner un partenaire
        function selectPartner(partnerId, partnerName) {
            // Désélectionner tous les partenaires
            document.querySelectorAll('.partner-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Sélectionner le partenaire cliqué
            const selectedCard = document.querySelector(`[data-partner-id="${partnerId}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            // Mettre à jour le champ caché
            document.getElementById('selectedPartnerId').value = partnerId;
            
            console.log('Partenaire sélectionné:', partnerId, partnerName);
        }

        // Fonction pour sélectionner le type de transaction
        function selectTransactionType(type) {
            // Désélectionner tous les types
            document.querySelectorAll('.type-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            
            // Sélectionner le type cliqué
            const selectedBtn = document.querySelector(`[data-type="${type}"]`);
            if (selectedBtn) {
                selectedBtn.classList.add('selected');
            }
            
            // Mettre à jour le champ caché
            document.getElementById('selectedTransactionType').value = type;
            
            console.log('Type de transaction sélectionné:', type);
        }

        // Fonction pour basculer entre clavier visuel et saisie manuelle
        function toggleTransactionKeyboard() {
            const keyboard = document.getElementById('transactionKeyboard');
            const input = document.getElementById('transactionAmount');
            const toggleBtn = document.querySelector('.keyboard-toggle-btn');
            
            keyboardMode = !keyboardMode;
            
            if (keyboardMode) {
                // Mode clavier visuel
                keyboard.classList.add('active');
                input.readOnly = true;
                toggleBtn.innerHTML = '<i class="fas fa-keyboard"></i>';
                toggleBtn.title = 'Passer en saisie manuelle';
            } else {
                // Mode saisie manuelle
                keyboard.classList.remove('active');
                input.readOnly = false;
                input.focus();
                toggleBtn.innerHTML = '<i class="fas fa-edit"></i>';
                toggleBtn.title = 'Passer au clavier visuel';
            }
        }

        // Fonctions du clavier numérique
        function addTransactionDigit(digit) {
            if (!keyboardMode) return;
            
            // Limiter à 2 décimales
            if (transactionAmountValue.includes(',')) {
                const parts = transactionAmountValue.split(',');
                if (parts[1] && parts[1].length >= 2) return;
            }
            
            // Limiter la longueur totale
            if (transactionAmountValue.replace(',', '').length >= 10) return;
            
            transactionAmountValue += digit;
            updateTransactionDisplay();
        }

        function addTransactionDecimal() {
            if (!keyboardMode) return;
            
            // Éviter les virgules multiples
            if (transactionAmountValue.includes(',')) return;
            
            // Ajouter 0 si vide
            if (transactionAmountValue === '') {
                transactionAmountValue = '0';
            }
            
            transactionAmountValue += ',';
            updateTransactionDisplay();
        }

        function deleteTransactionDigit() {
            if (!keyboardMode) return;
            
            transactionAmountValue = transactionAmountValue.slice(0, -1);
            updateTransactionDisplay();
        }

        function clearTransactionAmount() {
            if (!keyboardMode) return;
            
            transactionAmountValue = '';
            updateTransactionDisplay();
        }

        function updateTransactionDisplay() {
            const input = document.getElementById('transactionAmount');
            if (transactionAmountValue === '') {
                input.value = '0,00';
            } else {
                // Formater l'affichage
                let displayValue = transactionAmountValue;
                if (!displayValue.includes(',')) {
                    displayValue += ',00';
                } else {
                    const parts = displayValue.split(',');
                    if (parts[1].length === 1) {
                        displayValue += '0';
                    }
                }
                input.value = displayValue;
            }
        }

        // Initialiser l'affichage du montant au chargement du modal
        function initTransactionModal() {
            transactionAmountValue = '';
            updateTransactionDisplay();
            
            // Réinitialiser les sélections
            document.querySelectorAll('.partner-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelectorAll('.type-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            
            // Vider les champs
            document.getElementById('selectedPartnerId').value = '';
            document.getElementById('selectedTransactionType').value = '';
            document.getElementById('transactionDescription').value = '';
        }

        // Fermer les modals avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                    closeModal(modal.id);
                });
            }
});
</script> 
</body>
</html>
