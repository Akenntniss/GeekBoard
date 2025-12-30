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
            align-items: center;
        }
        
        .global-theme-toggle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            padding: 0.75rem;
            cursor: pointer;
            transition: all .3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .global-theme-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
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

        .table-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
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
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .partner-selector .show-all-partners {
            grid-column: 1 / -1; /* Prend toute la largeur */
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

        /* Bouton spécial "Afficher les partenaires" */
        .show-all-partners {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
            border-color: #007bff !important;
            color: white !important;
        }

        .show-all-partners:hover {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%) !important;
            border-color: #0056b3 !important;
            transform: translateY(-2px);
        }

        .show-all-partners .partner-name {
            color: white !important;
            font-weight: 600;
        }

        .show-all-partners .partner-count {
            color: rgba(255, 255, 255, 0.8) !important;
            font-size: 0.85rem;
        }

        .show-all-partners i.fa-arrow-right {
            color: white !important;
            font-size: 1.2rem;
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

        /* Conteneur du bouton d'affichage du clavier */
        .keyboard-show-container {
            text-align: center;
            margin: 1rem 0;
        }

        .show-keyboard-btn {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .show-keyboard-btn:hover {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }

        .show-keyboard-btn:active {
            transform: translateY(0);
        }

        .show-keyboard-btn.hidden {
            display: none;
        }

        /* Numeric Keyboard */
        .numeric-keyboard {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            display: none;
            animation: slideDown 0.3s ease;
            margin-top: 1rem;
        }

        .numeric-keyboard.show {
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

        /* Partners Grid Modal */
        .partners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            padding: 1rem 0;
        }

        .partner-card-modal {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border: 2px solid #e9ecef;
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .partner-card-modal:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #007bff;
        }

        .partner-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .partner-card-info h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .partner-card-info .partner-id {
            font-size: 0.9rem;
            color: #666;
            background: #f0f0f0;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            display: inline-block;
        }

        .partner-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #28a745;
        }

        .status-dot.inactive {
            background: #dc3545;
        }

        .partner-contact {
            margin: 1rem 0;
            padding: 1rem;
            background: rgba(0, 123, 255, 0.05);
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .partner-contact i {
            color: #007bff;
            width: 16px;
            margin-right: 0.5rem;
        }

        .partner-balance {
            text-align: center;
            margin: 1rem 0;
            padding: 1rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .partner-balance.positive {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .partner-balance.negative {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .partner-balance.zero {
            background: linear-gradient(135deg, #e2e3e5 0%, #d6d8db 100%);
            color: #383d41;
            border: 1px solid #d6d8db;
        }

        .partner-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .partner-actions .modern-btn {
            flex: 1;
            padding: 0.7rem;
            font-size: 0.85rem;
        }

        /* Loading et Error States */
        .loading-spinner, .error-message, .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }

        .loading-spinner i, .error-message i, .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .loading-spinner i {
            color: #007bff;
        }

        .error-message i {
            color: #dc3545;
        }

        .empty-state i {
            color: #6c757d;
        }

        .empty-state h3 {
            margin: 1rem 0 0.5rem 0;
            color: #333;
        }

        .empty-state p {
            margin: 0;
            font-size: 0.9rem;
        }

        /* Modal Sélection Partenaire */
        .partners-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            padding: 1rem 0;
        }

        .partner-selection-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
        }

        .partner-selection-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.15);
            border-color: #007bff;
        }

        .partner-selection-card.selected {
            border-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }

        .partner-selection-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .partner-selection-balance {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .partner-selection-balance.positive {
            color: #28a745;
        }

        .partner-selection-balance.negative {
            color: #dc3545;
        }

        .partner-selection-check {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #28a745;
            font-size: 1.2rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .partner-selection-card.selected .partner-selection-check {
            opacity: 1;
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

        /* ===== STYLES POUR LE NOUVEAU MODAL SIMPLE ===== */

        /* Modal de transaction simple et fonctionnel */
        .transaction-modal-simple {
            max-width: 600px !important;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-transaction {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .btn-save-transaction {
            background: #10b981;
            color: white;
        }

        .btn-save-transaction:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-cancel-transaction {
            background: #6b7280;
            color: white;
            margin-right: 1rem;
        }

        .btn-cancel-transaction:hover {
            background: #4b5563;
        }

        /* ===== THÈME NUIT GLOBAL ===== */
        body.dark-theme {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e2e8f0;
        }
        
        body.dark-theme .hero-section {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        body.dark-theme .hero-title {
            background: linear-gradient(135deg, #818cf8, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        body.dark-theme .hero-subtitle {
            color: #9ca3af;
        }
        
        body.dark-theme .stats-card {
            background: rgba(31, 31, 47, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        body.dark-theme .stats-card:hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        
        body.dark-theme .stats-number {
            color: #e2e8f0;
        }
        
        body.dark-theme .stats-label {
            color: #9ca3af;
        }
        
        body.dark-theme .section {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        body.dark-theme .section-header {
            background: linear-gradient(135deg, #2d1b69 0%, #1a1a2e 100%);
            color: #e2e8f0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        body.dark-theme .section-title {
            color: #e2e8f0;
        }
        
        body.dark-theme .modern-table {
            background: rgba(31, 31, 47, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        body.dark-theme .modern-table thead {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
        }
        
        body.dark-theme .modern-table th {
            color: #e2e8f0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        body.dark-theme .modern-table td {
            color: #e2e8f0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        body.dark-theme .modern-table tbody tr:hover {
            background: rgba(55, 65, 81, 0.3);
        }
        
        body.dark-theme .partner-name {
            color: #e2e8f0;
        }
        
        body.dark-theme .partner-id {
            color: #9ca3af;
        }
        
        body.dark-theme .contact-info {
            color: #9ca3af;
        }
        
        body.dark-theme .modern-btn {
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        body.dark-theme .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
        
        body.dark-theme .btn-secondary {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
            color: #e2e8f0;
        }
        
        body.dark-theme .btn-success {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        body.dark-theme .global-theme-toggle {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
        }
        
        /* ===== Améliorations UI du modal ===== */
        /* Conteneur du modal transaction: look moderne, verre, ombre douce */
        .transaction-modal-simple {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.1);
            border: 1px solid rgba(0,0,0,0.04);
            backdrop-filter: blur(20px);
            position: relative;
        }
        .transaction-modal-simple::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
            z-index: 1;
        }
        .transaction-modal-simple .modal-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
            color: #ffffff;
            padding: 22px 24px;
            border: none;
            position: relative;
            overflow: hidden;
        }
        .transaction-modal-simple .modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="10" height="10" patternUnits="userSpaceOnUse"><circle cx="5" cy="5" r="0.8" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
            opacity: 0.3;
        }
        .transaction-modal-simple .modal-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            letter-spacing: .3px;
            position: relative;
            z-index: 1;
        }
        .transaction-modal-simple .modal-title i { 
            opacity: .95; 
            font-size: 18px;
            background: rgba(255,255,255,0.2);
            padding: 8px;
            border-radius: 8px;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .theme-toggle {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: #ffffff;
            padding: 8px;
            cursor: pointer;
            transition: all .3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .theme-toggle:hover {
            background: rgba(255,255,255,0.25);
            transform: scale(1.05);
        }
        .modal-close {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: #ffffff;
            padding: 8px;
            cursor: pointer;
            transition: all .3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-close:hover {
            background: rgba(255,255,255,0.25);
            transform: scale(1.05);
        }
        .transaction-modal-simple .modal-body { 
            background: linear-gradient(135deg, #fbfbfd 0%, #f8fafc 100%); 
            padding: 24px 26px; 
            position: relative;
        }
        .transaction-modal-simple .modal-body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 24px;
            right: 24px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0,0,0,0.05), transparent);
        }
        .transaction-modal-simple .modal-footer {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border-top: 1px solid #eef0f4;
            padding: 18px 24px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            box-shadow: 0 -4px 16px rgba(0,0,0,0.04);
        }

        .partner-selection-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .partner-quick-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: stretch;
        }
        .partner-selected {
            margin-top: 4px;
            color: #6b7280;
            font-size: 0.9rem;
            font-style: italic;
        }
        .partner-btn {
            display: inline-flex;
            align-items: center;
            flex-direction: column;
            gap: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 16px 18px;
            cursor: pointer;
            transition: all .3s ease;
            box-shadow: 0 4px 16px rgba(102,126,234,0.25);
            position: relative;
            overflow: hidden;
            min-width: 130px;
            text-align: center;
        }
        .partner-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            opacity: 0;
            transition: opacity .3s ease;
        }
        .partner-btn:hover::before { opacity: 1; }
        .partner-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(102,126,234,0.35); }
        .partner-btn:focus-visible { outline: 3px solid rgba(59,130,246,.35); outline-offset: 2px; }
        .partner-btn.active { 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 6px 20px rgba(16,185,129,0.4);
            transform: translateY(-2px);
        }
        .partner-btn i {
            font-size: 20px;
            opacity: 0.9;
        }
        .partner-btn span {
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .show-all-btn {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 16px 18px;
            cursor: pointer;
            transition: all .3s ease;
            box-shadow: 0 4px 16px rgba(71,85,105,0.12);
            font-weight: 600;
            display: flex;
            align-items: center;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
            min-width: 130px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .show-all-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            opacity: 0;
            transition: opacity .3s ease;
        }
        .show-all-btn:hover::before { opacity: 1; }
        .show-all-btn:hover { 
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            transform: translateY(-2px); 
            box-shadow: 0 8px 24px rgba(71,85,105,0.18);
        }
        .show-all-btn:focus-visible { outline: 3px solid rgba(59,130,246,.35); outline-offset: 2px; }
        .show-all-btn i {
            font-size: 20px;
            opacity: 0.9;
        }
        .show-all-btn span {
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
        }

        .type-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .type-btn {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px 14px;
            cursor: pointer;
            transition: all .3s ease;
            box-shadow: 0 4px 16px rgba(2,6,23,0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .type-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            opacity: 0;
            transition: opacity .3s ease;
        }
        .type-btn:hover::before { opacity: 1; }
        .type-btn.credit { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); color: #065f46; border-color: #a7f3d0; }
        .type-btn.debit  { background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%); color: #9a3412; border-color: #f97316; }
        .type-btn:hover  { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(2,6,23,0.12); }
        .type-btn.active { outline: 3px solid rgba(59,130,246,.25); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(2,6,23,0.15); }
        .type-title {
            font-weight: 800;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .type-desc {
            font-weight: 500;
            font-size: 11px;
            opacity: 0.8;
            line-height: 1.2;
        }

        .keypad { margin-top: 12px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px; box-shadow: 0 8px 24px rgba(2,6,23,0.08); }
        .keypad.hidden { display: none; }
        .keypad-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .keypad-key {
            height: 44px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            font-weight: 700;
            cursor: pointer;
            transition: transform .08s ease;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .keypad-key:hover { background: #f9fafb; }
        .keypad-key:active { transform: scale(0.97); }
        .keypad-key.wide { grid-column: span 3; background: #f8fafc; }

        /* Inputs & labels harmonisés */
        .transaction-modal-simple .form-group { margin-bottom: 18px; }
        .transaction-modal-simple .form-group:last-of-type { margin-bottom: 8px; }
        .transaction-modal-simple .form-label { 
            color: #0f172a; 
            font-weight: 700; 
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .transaction-modal-simple .form-label::before {
            content: '';
            width: 3px;
            height: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }
        .transaction-modal-simple .form-control {
            border: 2px solid #e5e7eb;
            background: #ffffff;
            border-radius: 10px;
            padding: 12px 14px;
            font-weight: 500;
            transition: all .3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .transaction-modal-simple .form-control::placeholder { color: #94a3b8; }
        .transaction-modal-simple .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102,126,234,.15), 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-1px);
        }

        /* Boutons actions harmonisés */
        .btn-transaction { font-weight: 700; }
        .btn-save-transaction {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            box-shadow: 0 8px 24px rgba(16,185,129,0.25);
        }
        .btn-save-transaction:hover { filter: brightness(1.03); transform: translateY(-1px); }
        .btn-cancel-transaction { background: #f3f4f6; color: #111827; border: 1px solid #e5e7eb; }
        .btn-cancel-transaction:hover { background: #e5e7eb; }

        /* ===== THÈME NUIT POUR LE MODAL (HÉRITAGE DU THÈME GLOBAL) ===== */
        body.dark-theme .transaction-modal-simple {
            background: #1a1a1a;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 32px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.05);
        }
        body.dark-theme .transaction-modal-simple::before {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        }
        body.dark-theme .transaction-modal-simple .modal-header {
            background: linear-gradient(135deg, #2d1b69 0%, #1a1a2e 50%, #16213e 100%);
        }
        body.dark-theme .transaction-modal-simple .modal-body {
            background: linear-gradient(135deg, #1f1f1f 0%, #2a2a2a 100%);
        }
        body.dark-theme .transaction-modal-simple .modal-body::before {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
        }
        body.dark-theme .transaction-modal-simple .modal-footer {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-top: 1px solid #333;
        }
        body.dark-theme .transaction-modal-simple .form-label {
            color: #e2e8f0;
        }
        body.dark-theme .transaction-modal-simple .form-label::before {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        body.dark-theme .transaction-modal-simple .form-control {
            background: #2d2d2d;
            border: 2px solid #404040;
            color: #e2e8f0;
        }
        body.dark-theme .transaction-modal-simple .form-control::placeholder {
            color: #6b7280;
        }
        body.dark-theme .transaction-modal-simple .form-control:focus {
            background: #333;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102,126,234,.25), 0 4px 12px rgba(0,0,0,0.3);
        }
        body.dark-theme .transaction-modal-simple .partner-selected {
            color: #9ca3af;
        }
        body.dark-theme .transaction-modal-simple .partner-btn {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
            color: #e5e7eb;
            border: 1px solid #4b5563;
        }
        body.dark-theme .transaction-modal-simple .partner-btn:hover {
            background: linear-gradient(135deg, #4b5563 0%, #6b7280 100%);
        }
        body.dark-theme .transaction-modal-simple .partner-btn.active {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        body.dark-theme .transaction-modal-simple .show-all-btn {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
            color: #e5e7eb;
            border: 1px solid #4b5563;
        }
        body.dark-theme .transaction-modal-simple .show-all-btn:hover {
            background: linear-gradient(135deg, #4b5563 0%, #6b7280 100%);
        }
        body.dark-theme .transaction-modal-simple .type-btn.credit {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
            color: #a7f3d0;
            border-color: #047857;
        }
        body.dark-theme .transaction-modal-simple .type-btn.debit {
            background: linear-gradient(135deg, #7c2d12 0%, #9a3412 100%);
            color: #fed7aa;
            border-color: #ea580c;
        }
        body.dark-theme .transaction-modal-simple .keypad {
            background: #2d2d2d;
            border: 1px solid #404040;
        }
        body.dark-theme .transaction-modal-simple .keypad-key {
            background: #374151;
            color: #e5e7eb;
            border: 1px solid #4b5563;
        }
        body.dark-theme .transaction-modal-simple .keypad-key:hover {
            background: #4b5563;
        }
        body.dark-theme .transaction-modal-simple .keypad-key.wide {
            background: #1f2937;
        }
        body.dark-theme .transaction-modal-simple .btn-save-transaction {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        body.dark-theme .transaction-modal-simple .btn-save-transaction:hover {
            background: linear-gradient(135deg, #047857 0%, #065f46 100%);
        }
        body.dark-theme .transaction-modal-simple .btn-cancel-transaction {
            background: #4b5563;
            color: #e5e7eb;
        }
        body.dark-theme .transaction-modal-simple .btn-cancel-transaction:hover {
            background: #6b7280;
        }

        /* Responsive */
        @media (max-width: 520px) {
            .transaction-modal-simple { border-radius: 12px; }
            .type-toggle { grid-template-columns: 1fr; }
            .partner-quick-actions { gap: 6px; }
            .partner-btn span { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        }

        /* Fin des styles du nouveau modal simple */


        .header-content {
            display: flex;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
            font-size: 1.5rem;
            color: white;
            backdrop-filter: blur(10px);
        }

        .header-text h3 {
            margin: 0;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .modal-subtitle {
            margin: 0.25rem 0 0 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .modern-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .modern-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        /* Corps moderne */
        .modern-transaction-body {
            background: #fafbfc;
            padding: 2.5rem !important;
        }

        /* Groupes de formulaire modernes */
        .modern-form-group {
            margin-bottom: 2rem;
        }

        .modern-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
        }

        /* Sélecteur de partenaire moderne */
        .modern-partner-selector {
            position: relative;
        }

        .modern-select {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 5"><path fill="%23666" d="M2 0L0 2h4zm0 5L0 3h4z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 12px;
        }

        .modern-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .partner-balance-info {
            font-size: 0.875rem;
            color: #718096;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
        }

        /* Types de transaction modernes */
        .modern-transaction-types {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .type-radio {
            display: none;
        }

        .type-card {
            padding: 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .type-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .type-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .type-avance .type-icon {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .type-remboursement .type-icon {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
        }

        .type-title {
            font-weight: 600;
            font-size: 1rem;
            color: #2d3748;
        }

        .type-desc {
            color: #718096;
            font-size: 0.8rem;
        }

        .type-radio:checked + .type-card {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .type-radio:checked + .type-card .type-title,
        .type-radio:checked + .type-card .type-desc {
            color: white;
        }

        .type-radio:checked + .type-card .type-icon {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Conteneur de montant moderne */
        .modern-amount-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 2px solid #f1f3f4;
        }

        .amount-display-modern {
            position: relative;
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .modern-amount-input {
            width: 100%;
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            padding: 1rem 3rem 1rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .modern-amount-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }

        .currency-modern {
            position: absolute;
            right: 1.25rem;
            font-size: 1.5rem;
            font-weight: 600;
            color: #667eea;
            pointer-events: none;
        }

        .balance-preview {
            font-size: 0.875rem;
            color: #718096;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        /* Clavier moderne */
        .modern-keyboard {
            display: grid;
            gap: 0.75rem;
        }

        .keyboard-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        .key-modern {
            height: 50px;
            border: none;
            border-radius: 12px;
            background: #f8f9fa;
            color: #2d3748;
            font-size: 1.25rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            user-select: none;
        }

        .key-modern:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .key-modern:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .key-clear {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
        }

        .key-clear:hover {
            background: linear-gradient(135deg, #e53e3e, #c53030);
        }

        .key-decimal {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .key-decimal:hover {
            background: linear-gradient(135deg, #5a67d8, #6b46c1);
        }

        /* Textarea moderne */
        .modern-textarea {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        .modern-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        /* Pied de modal moderne */
        .modern-transaction-footer {
            background: white;
            border: none;
            padding: 2rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .transaction-summary-modern {
            flex: 1;
            min-width: 200px;
            padding: 1rem;
            background: #f8f9ff;
            border-radius: 12px;
            border: 2px solid #e8f0fe;
            font-size: 0.9rem;
        }

        .transaction-summary-modern.positive {
            background: #f0fff4;
            border-color: #c6f6d5;
            color: #22543d;
        }

        .transaction-summary-modern.negative {
            background: #fff5f5;
            border-color: #fed7d7;
            color: #742a2a;
        }

        .modal-actions-modern {
            display: flex;
            gap: 1rem;
        }

        /* Boutons modernes */
        .btn-modern {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            user-select: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .btn-modern:active {
            transform: translateY(0);
        }

        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            /* Effet de pulsation subtil pour indiquer l'interactivité */
            animation: pulse-ready 3s infinite;
        }

        @keyframes pulse-ready {
            0%, 100% {
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            50% {
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
            }
        }

        .btn-save:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
            /* Effet de brillance au survol */
            background-size: 200% 200%;
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .btn-save:active {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-save:disabled {
            background: #a0aec0 !important;
            cursor: not-allowed !important;
            transform: none !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
            animation: none !important;
            opacity: 0.6;
        }

        .btn-save:disabled:hover {
            background: #a0aec0 !important;
            transform: none !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
        }

        .btn-cancel {
            background: #f8f9fa;
            color: #6c757d;
            border: 2px solid #e9ecef;
            cursor: pointer;
        }

        .btn-cancel:hover {
            background: #e9ecef;
            color: #495057;
            border-color: #dee2e6;
        }

        /* Responsive pour le nouveau modal */
        @media (max-width: 768px) {
            .modern-transaction-header {
                padding: 1.5rem;
            }
            
            .modern-transaction-body {
                padding: 1.5rem !important;
            }
            
            .modern-transaction-footer {
                padding: 1.5rem;
                flex-direction: column;
                align-items: stretch;
            }
            
            .modal-actions-modern {
                width: 100%;
                justify-content: space-between;
            }
            
            .modern-transaction-types {
                grid-template-columns: 1fr;
            }
            
            .keyboard-row {
                gap: 0.5rem;
            }
            
            .key-modern {
                height: 45px;
                font-size: 1.1rem;
            }

            .modern-amount-input {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loader Screen -->
    <div id="pageLoader" class="loader">
        <div class="loader-wrapper dark-loader">
            <div class="loader-circle"></div>
            <div class="loader-text">
                <span class="loader-letter">S</span>
                <span class="loader-letter">E</span>
                <span class="loader-letter">R</span>
                <span class="loader-letter">V</span>
                <span class="loader-letter">O</span>
            </div>
        </div>
        <div class="loader-wrapper light-loader">
            <div class="loader-circle-light"></div>
            <div class="loader-text-light">
                <span class="loader-letter">S</span>
                <span class="loader-letter">E</span>
                <span class="loader-letter">R</span>
                <span class="loader-letter">V</span>
                <span class="loader-letter">O</span>
            </div>
        </div>
    </div>
    
    <div class="container" id="mainContent" style="display: none;">
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
                    <button class="global-theme-toggle" onclick="toggleGlobalTheme()" title="Basculer le thème" id="globalThemeToggle">
                        <i class="fas fa-moon" id="globalThemeIcon"></i>
                    </button>
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
                    Aperçu des Partenaires
                </h2>
                <div class="table-actions">
                    <button class="modern-btn btn-secondary" onclick="openModal('tousPartenairesModal')">
                        <i class="fas fa-users"></i>
                        Afficher les partenaires
                    </button>
                    <button class="modern-btn btn-primary" onclick="openModal('ajouterPartenaireModal')">
                        <i class="fas fa-user-plus"></i>
                        Nouveau Partenaire
                    </button>
                </div>
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
                    <?php 
                    $partenaires_apercu = array_slice($partenaires, 0, 15); // Limiter à 15 partenaires
                    foreach ($partenaires_apercu as $partenaire): ?>
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
                                <button class="modern-btn btn-primary btn-small" 
                                        onclick="ouvrirTransactionAvecPartenaire(<?php echo $partenaire['id']; ?>, '<?php echo htmlspecialchars($partenaire['nom'], ENT_QUOTES); ?>')"
                                        title="Nouvelle transaction">
                                    <i class="fas fa-plus"></i>
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

    <!-- NOUVEAU MODAL SIMPLE ET FONCTIONNEL -->
    <div class="modal-overlay" id="ajouterTransactionModal">
        <div class="modal transaction-modal-simple">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-plus"></i>
                    Nouvelle Transaction
                </h3>
                <button class="modal-close" onclick="closeModal('ajouterTransactionModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="transactionForm">
                    <input type="hidden" id="partenaire_id" name="partenaire_id" required>
                    
                    <div class="form-group">
                        <label class="form-label">Partenaire *</label>
                        <div class="partner-selection-container">
                            <div class="partner-quick-actions">
                                <?php 
                                    $firstTwo = array_slice($partenaires ?? [], 0, 2);
                                    foreach ($firstTwo as $idx => $p):
                                ?>
                                    <button type="button" class="partner-btn" data-id="<?php echo (int)$p['id']; ?>" data-name="<?php echo htmlspecialchars($p['nom'], ENT_QUOTES); ?>">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo htmlspecialchars($p['nom']); ?></span>
                                    </button>
                                <?php endforeach; ?>
                                <button type="button" class="show-all-btn" id="btnShowAllPartners">
                                    <i class="fas fa-users"></i>
                                    <span>Tous</span>
                                </button>
                            </div>
                            <div class="partner-selected" id="partnerSelected">
                                Aucun partenaire sélectionné
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Type de transaction *</label>
                        <input type="hidden" id="type_transaction" name="type" required>
                        <div class="type-toggle">
                            <button type="button" class="type-btn credit" id="btnTypeCredit" data-type="AVANCE">
                                <div class="type-title">CRÉDIT</div>
                                <div class="type-desc">Je prête / Je dépanne</div>
                            </button>
                            <button type="button" class="type-btn debit" id="btnTypeDebit" data-type="REMBOURSEMENT">
                                <div class="type-title">DÉBIT</div>
                                <div class="type-desc">J'emprunte / J'achète</div>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Montant (€) *</label>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <input type="number" class="form-control" id="montant" name="montant" step="0.01" min="0" inputmode="decimal" placeholder="0.00" required>
                            <button type="button" class="btn-transaction" id="btnToggleKeypad">Afficher le clavier</button>
                        </div>
                        <div class="keypad hidden" id="numericKeypad">
                            <div class="keypad-grid">
                                <button type="button" class="keypad-key" data-key="1">1</button>
                                <button type="button" class="keypad-key" data-key="2">2</button>
                                <button type="button" class="keypad-key" data-key="3">3</button>
                                <button type="button" class="keypad-key" data-key="4">4</button>
                                <button type="button" class="keypad-key" data-key="5">5</button>
                                <button type="button" class="keypad-key" data-key="6">6</button>
                                <button type="button" class="keypad-key" data-key="7">7</button>
                                <button type="button" class="keypad-key" data-key="8">8</button>
                                <button type="button" class="keypad-key" data-key="9">9</button>
                                <button type="button" class="keypad-key" data-key=".">.</button>
                                <button type="button" class="keypad-key" data-key="0">0</button>
                                <button type="button" class="keypad-key wide" data-key="back">⌫</button>
                                <button type="button" class="keypad-key wide" data-key="clear">C</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Détaillez la transaction..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-transaction btn-cancel-transaction" onclick="closeModal('ajouterTransactionModal')">
                    Annuler
                </button>
                <button type="button" class="btn-transaction btn-save-transaction" id="btnEnregistrerTransaction">
                    Enregistrer
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

    <!-- Modal Tous les Partenaires -->
    <div class="modal-overlay" id="tousPartenairesModal">
        <div class="modal" style="width: 1200px; max-height: 90vh;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-users"></i>
                    Tous les Partenaires
                </h3>
                <button class="modal-close" onclick="closeModal('tousPartenairesModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding: 2rem; overflow-y: auto;">
                <div class="partners-grid" id="partnersGrid">
                    <!-- Contenu chargé dynamiquement -->
                    </div>
                    </div>
                    </div>
                    </div>

    <!-- Modal Sélection Partenaire pour Transaction -->
    <div class="modal-overlay" id="selectionPartenaireModal">
        <div class="modal" style="width: 900px; max-height: 90vh;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-check"></i>
                    Sélectionner un Partenaire
                </h3>
                <button class="modal-close" onclick="closeModal('selectionPartenaireModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding: 2rem; overflow-y: auto;">
                <div class="partners-selection-grid" id="partnersSelectionGrid">
                    <!-- Contenu chargé dynamiquement -->
            </div>
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
            if (!modal) {
                console.error('Modal non trouvé:', modalId);
                return;
            }

            // 1) Fermer tout modal Bootstrap éventuellement ouvert qui capte le focus/clic
            try {
                if (window.bootstrap && document.querySelector('.modal.show')) {
                    document.querySelectorAll('.modal.show').forEach(m => {
                        const inst = bootstrap.Modal.getInstance(m) || new bootstrap.Modal(m);
                        inst.hide();
                    });
                }
            } catch (e) {
                console.warn('Impossible de fermer les modals Bootstrap existants:', e);
            }

            // 2) Désactiver temporairement les interactions de la navbar qui bloque (pointer-events)
            const desktopNavbar = document.getElementById('desktop-navbar');
            const prevNavbarPointerEvents = desktopNavbar ? desktopNavbar.style.pointerEvents : '';
            if (desktopNavbar) desktopNavbar.style.pointerEvents = 'none';

            // 3) Forcer l'overlay en top layer et prendre la taille
            modal.style.display = 'flex';
            modal.classList.add('active');
            modal.style.zIndex = '2147483647';
            modal.style.position = 'fixed';
            modal.style.inset = '0';
            document.body.style.overflow = 'hidden';

            // Charger les partenaires si c'est le modal "Tous les partenaires"
            if (modalId === 'tousPartenairesModal') {
                chargerTousLesPartenaires();
            }

            console.log('Modal ouvert avec succès');

            // 4) Restaurer la navbar quand on ferme ce modal
            const restoreNavbar = () => {
                if (desktopNavbar) desktopNavbar.style.pointerEvents = prevNavbarPointerEvents;
                modal.removeEventListener('transitionend', restoreNavbar);
            };
            modal.addEventListener('transitionend', restoreNavbar);
        }

        function closeModal(modalId) {
            console.log('Fermeture du modal:', modalId);
            const modal = document.getElementById(modalId);
            if (!modal) return;

            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
                // Restaurer les interactions de la navbar si elles avaient été désactivées
                const desktopNavbar = document.getElementById('desktop-navbar');
                if (desktopNavbar) desktopNavbar.style.pointerEvents = '';
            }, 300);
            document.body.style.overflow = 'auto';
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
        // ANCIENNE FONCTION DÉSACTIVÉE - Le nouveau modal moderne utilise son propre système
        function ajouterTransaction() {
            console.log('=== ANCIENNE FONCTION DÉSACTIVÉE ===');
            console.log('Cette fonction a été remplacée par le nouveau système moderne');
            alert('Erreur: Cette fonction est obsolète. Veuillez utiliser le nouveau modal.');
            return false;
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
        let keyboardVisible = false; // true = clavier affiché, false = clavier masqué

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

        // Fonction pour afficher le clavier numérique
        function showTransactionKeyboard() {
            const keyboard = document.getElementById('transactionKeyboard');
            const showBtn = document.getElementById('showKeyboardBtn');
            const input = document.getElementById('transactionAmount');
            
            // Afficher le clavier
            keyboard.classList.add('show');
            
            // Masquer le bouton "AFFICHER LE CLAVIER"
            showBtn.classList.add('hidden');
            
            // Passer en mode readonly et initialiser le clavier
            input.readOnly = true;
            keyboardVisible = true;
            
            // Initialiser l'affichage si vide
            if (!transactionAmountValue) {
                updateTransactionDisplay();
            }
            
            console.log('Clavier numérique affiché');
        }

        // Fonctions du clavier numérique
        function addTransactionDigit(digit) {
            if (!keyboardVisible) return;
            
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
            if (!keyboardVisible) return;
            
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
            if (!keyboardVisible) return;
            
            transactionAmountValue = transactionAmountValue.slice(0, -1);
            updateTransactionDisplay();
        }

        function clearTransactionAmount() {
            if (!keyboardVisible) return;
            
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
        // ANCIENNE FONCTION DÉSACTIVÉE - Le nouveau modal moderne n'utilise plus cette fonction
        function initTransactionModal() {
            console.log('=== ANCIENNE FONCTION DÉSACTIVÉE ===');
            console.log('initTransactionModal() a été remplacée par le nouveau système moderne');
            // Ne rien faire - cette fonction est obsolète
            return false;
        }

        // Fonction pour charger tous les partenaires dans le modal
        function chargerTousLesPartenaires() {
            const partnersGrid = document.getElementById('partnersGrid');
            partnersGrid.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Chargement des partenaires...</div>';
            
            fetch('ajax/get_partenaires.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Partenaires chargés:', data);
                    if (data.success && data.partenaires) {
                        afficherTousLesPartenaires(data.partenaires);
                    } else {
                        partnersGrid.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i> Erreur: ' + (data.message || 'Impossible de charger les partenaires') + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Erreur chargement partenaires:', error);
                    partnersGrid.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i> Erreur de chargement des partenaires</div>';
                });
        }

        // Fonction pour afficher tous les partenaires
        function afficherTousLesPartenaires(partenaires) {
            const partnersGrid = document.getElementById('partnersGrid');
            
            if (partenaires.length === 0) {
                partnersGrid.innerHTML = '<div class="empty-state"><i class="fas fa-users"></i><h3>Aucun partenaire</h3><p>Aucun partenaire n\'a été trouvé</p></div>';
                return;
            }
            
            let html = '';
            partenaires.forEach(partenaire => {
                const soldeClass = partenaire.solde_actuel > 0 ? 'positive' : (partenaire.solde_actuel < 0 ? 'negative' : 'zero');
                const statusClass = partenaire.actif == 1 ? '' : 'inactive';
                const statusText = partenaire.actif == 1 ? 'Actif' : 'Inactif';
                
                html += `
                    <div class="partner-card-modal">
                        <div class="partner-card-header">
                            <div class="partner-card-info">
                                <h4>${partenaire.nom}</h4>
                                <span class="partner-id">ID: ${partenaire.id}</span>
                            </div>
                            <div class="partner-status">
                                <div class="status-dot ${statusClass}"></div>
                                <span>${statusText}</span>
                            </div>
                        </div>
                        
                        <div class="partner-contact">
                            <div><i class="fas fa-envelope"></i> ${partenaire.email || 'Non renseigné'}</div>
                            <div><i class="fas fa-phone"></i> ${partenaire.telephone || 'Non renseigné'}</div>
                            <div><i class="fas fa-map-marker-alt"></i> ${partenaire.adresse || 'Non renseignée'}</div>
                        </div>
                        
                        <div class="partner-balance ${soldeClass}">
                            <i class="fas fa-euro-sign"></i>
                            ${parseFloat(partenaire.solde_actuel || 0).toFixed(2)} €
                        </div>
                        
                        <div class="partner-actions">
                            <button class="modern-btn btn-info" onclick="afficherHistoriqueTransactions(${partenaire.id}, '${partenaire.nom.replace(/'/g, "\\'")}'); closeModal('tousPartenairesModal');">
                                <i class="fas fa-history"></i>
                                Historique
                            </button>
                            <button class="modern-btn btn-warning" onclick="envoyerLienPartenaire(${partenaire.id}, '${partenaire.nom.replace(/'/g, "\\'")}'); closeModal('tousPartenairesModal');">
                                <i class="fas fa-paper-plane"></i>
                                Envoyer lien
                            </button>
                        </div>
                    </div>
                `;
            });
            
            partnersGrid.innerHTML = html;
        }

        // Fonction pour ouvrir le modal de sélection de partenaire
        function ouvrirModalSelectionPartenaires() {
            openModal('selectionPartenaireModal');
            chargerPartenairesSelection();
        }

        // Fonction pour charger les partenaires pour sélection
        function chargerPartenairesSelection() {
            const partnersGrid = document.getElementById('partnersSelectionGrid');
            partnersGrid.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Chargement des partenaires...</div>';
            
            fetch('ajax/get_partenaires.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Partenaires chargés pour sélection:', data);
                    if (data.success && data.partenaires) {
                        afficherPartenairesSelection(data.partenaires);
                    } else {
                        partnersGrid.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i> Erreur: ' + (data.message || 'Impossible de charger les partenaires') + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Erreur chargement partenaires:', error);
                    partnersGrid.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i> Erreur de chargement des partenaires</div>';
                });
        }

        // Fonction pour afficher les partenaires pour sélection
        function afficherPartenairesSelection(partenaires) {
            const partnersGrid = document.getElementById('partnersSelectionGrid');
            
            if (partenaires.length === 0) {
                partnersGrid.innerHTML = '<div class="empty-state"><i class="fas fa-users"></i><h3>Aucun partenaire</h3><p>Aucun partenaire n\'a été trouvé</p></div>';
                return;
            }
            
            let html = '';
            partenaires.forEach(partenaire => {
                const soldeClass = partenaire.solde_actuel > 0 ? 'positive' : 'negative';
                
                html += `
                    <div class="partner-selection-card" onclick="selectionnerPartenaire(${partenaire.id}, '${partenaire.nom.replace(/'/g, "\\'")}', ${partenaire.solde_actuel})" data-partner-id="${partenaire.id}">
                        <i class="fas fa-check partner-selection-check"></i>
                        <div class="partner-selection-name">${partenaire.nom}</div>
                        <div class="partner-selection-balance ${soldeClass}">
                            ${parseFloat(partenaire.solde_actuel || 0).toFixed(2)} €
                        </div>
                        <div style="font-size: 0.9rem; color: #666;">
                            ID: ${partenaire.id}
                        </div>
                    </div>
                `;
            });
            
            partnersGrid.innerHTML = html;
        }

        // Fonction pour sélectionner un partenaire depuis le modal
        function selectionnerPartenaire(partnerId, partnerName, partnerBalance) {
            // Fermer le modal de sélection
            closeModal('selectionPartenaireModal');
            
            // Sélectionner le partenaire dans le modal de transaction
            selectPartner(partnerId, partnerName);
            
            console.log('Partenaire sélectionné depuis modal:', partnerId, partnerName);
        }

        // Fermer les modals avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                    closeModal(modal.id);
                });
            }
        });

        // ===== JAVASCRIPT POUR LE NOUVEAU MODAL SIMPLE =====
        
        // Variable pour stocker l'état du thème global
        let isGlobalDarkTheme = false;
        
        // Fonction pour basculer le thème global de la page
        function toggleGlobalTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('globalThemeIcon');
            
            if (body && themeIcon) {
                isGlobalDarkTheme = !isGlobalDarkTheme;
                
                if (isGlobalDarkTheme) {
                    body.classList.add('dark-theme');
                    themeIcon.className = 'fas fa-sun';
                    console.log('🌙 Thème nuit global activé');
                    
                    // Sauvegarder la préférence
                    localStorage.setItem('darkTheme', 'true');
                } else {
                    body.classList.remove('dark-theme');
                    themeIcon.className = 'fas fa-moon';
                    console.log('☀️ Thème jour global activé');
                    
                    // Sauvegarder la préférence
                    localStorage.setItem('darkTheme', 'false');
                }
            }
        }
        
        // Initialiser le thème au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('darkTheme');
            const body = document.body;
            const themeIcon = document.getElementById('globalThemeIcon');
            
            if (savedTheme === 'true') {
                isGlobalDarkTheme = true;
                body.classList.add('dark-theme');
                if (themeIcon) themeIcon.className = 'fas fa-sun';
                console.log('🌙 Thème nuit restauré depuis localStorage');
            } else {
                isGlobalDarkTheme = false;
                body.classList.remove('dark-theme');
                if (themeIcon) themeIcon.className = 'fas fa-moon';
                console.log('☀️ Thème jour par défaut');
            }
        });
        
        // Fonction pour ouvrir le modal de transaction avec un partenaire présélectionné
        function ouvrirTransactionAvecPartenaire(partenaireId, partenaireNom) {
            // Ouvrir le modal
            openModal('ajouterTransactionModal');
            
            // Présélectionner le partenaire
            const partenaireInput = document.getElementById('partenaire_id');
            const partnerSelected = document.getElementById('partnerSelected');
            
            if (partenaireInput && partnerSelected) {
                partenaireInput.value = partenaireId;
                partnerSelected.textContent = partenaireNom;
                partnerSelected.style.color = '#10b981';
                partnerSelected.style.fontWeight = '600';
            }
            
            // Désactiver tous les boutons partenaires et activer celui correspondant
            const partnerButtons = document.querySelectorAll('.partner-btn');
            partnerButtons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.id == partenaireId) {
                    btn.classList.add('active');
                }
            });
            
            console.log(`✅ Modal ouvert avec partenaire présélectionné: ${partenaireNom} (ID: ${partenaireId})`);
        }
        
        // Fonction pour ajouter une transaction (nouveau système)
        function ajouterTransaction() {
            const form = document.getElementById('transactionForm');
            const formData = new FormData(form);
            
            // Validation basique
            const partenaireId = document.getElementById('partenaire_id').value;
            const type = document.getElementById('type_transaction').value;
            const montant = document.getElementById('montant').value;
            const description = document.getElementById('description').value;
            
            if (!partenaireId) {
                alert('Veuillez sélectionner un partenaire');
                return;
            }
            
            if (parseFloat(montant) <= 0) {
                alert('Le montant doit être supérieur à 0');
                return;
            }
            
            if (!description || description.trim().length < 2) {
                alert('La description est obligatoire');
                return;
            }
            
            // Désactiver le bouton pendant l'envoi
            const btnEnregistrer = document.getElementById('btnEnregistrerTransaction');
            const originalText = btnEnregistrer.innerHTML;
            btnEnregistrer.disabled = true;
            btnEnregistrer.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
            
            fetch('ajax/add_transaction_partenaire.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaction enregistrée avec succès !');
                    
                    // Envoyer SMS si activé
                    if (data.sms_sent) {
                        console.log('SMS envoyé automatiquement');
                    }
                    
                    closeModal('ajouterTransactionModal');
                    
                    // Recharger la page après un délai
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'enregistrement');
            })
            .finally(() => {
                // Réactiver le bouton
                btnEnregistrer.disabled = false;
                btnEnregistrer.innerHTML = originalText;
            });
        }
        
        // Attacher l'événement au bouton Enregistrer
        document.addEventListener('DOMContentLoaded', function() {
            const btnEnregistrer = document.getElementById('btnEnregistrerTransaction');
            if (btnEnregistrer) {
                btnEnregistrer.addEventListener('click', ajouterTransaction);
                console.log('✅ Événement de clic attaché au bouton Enregistrer');
            } else {
                console.error('❌ Bouton Enregistrer non trouvé');
            }

            // Sélecteurs de partenaire rapides
            document.querySelectorAll('.partner-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    // Retirer la classe active de tous les boutons partenaires
                    document.querySelectorAll('.partner-btn').forEach(b => b.classList.remove('active'));
                    // Ajouter la classe active au bouton cliqué
                    btn.classList.add('active');
                    
                    const id = btn.getAttribute('data-id');
                    const name = btn.getAttribute('data-name');
                    document.getElementById('partenaire_id').value = id;
                    const label = document.getElementById('partnerSelected');
                    if (label) {
                        label.textContent = `Partenaire sélectionné: ${name}`;
                        label.style.color = '#10b981';
                        label.style.fontWeight = '600';
                    }
                });
            });

            // Bouton pour afficher la liste complète des partenaires (ouvre le modal existant)
            const btnShowAll = document.getElementById('btnShowAllPartners');
            if (btnShowAll) {
                btnShowAll.addEventListener('click', () => {
                    openModal('tousPartenairesModal');
                });
            }

            // Toggle type (credit/debit)
            const typeInput = document.getElementById('type_transaction');
            const btnCredit = document.getElementById('btnTypeCredit');
            const btnDebit = document.getElementById('btnTypeDebit');
            function updateType(selected) {
                typeInput.value = selected;
                btnCredit.classList.toggle('active', selected === 'AVANCE');
                btnDebit.classList.toggle('active', selected === 'REMBOURSEMENT');
            }
            if (btnCredit && btnDebit) {
                btnCredit.addEventListener('click', () => updateType('AVANCE'));
                btnDebit.addEventListener('click', () => updateType('REMBOURSEMENT'));
                // Valeur par défaut: AVANCE
                updateType('AVANCE');
            }

            // Clavier numérique
            const btnToggleKeypad = document.getElementById('btnToggleKeypad');
            const keypad = document.getElementById('numericKeypad');
            const montantInput = document.getElementById('montant');
            if (btnToggleKeypad && keypad && montantInput) {
                btnToggleKeypad.addEventListener('click', () => {
                    keypad.classList.toggle('hidden');
                    if (!keypad.classList.contains('hidden')) {
                        montantInput.focus();
                    }
                });
                keypad.addEventListener('click', (e) => {
                    const key = e.target.getAttribute('data-key');
                    if (!key) return;
                    e.preventDefault();
                    let val = montantInput.value || '';
                    if (key === 'back') {
                        montantInput.value = val.slice(0, -1);
                        return;
                    }
                    if (key === 'clear') {
                        montantInput.value = '';
                        return;
                    }
                    if (key === '.') {
                        if (!val.includes('.')) montantInput.value = val + '.';
                        return;
                    }
                    // limiter à 2 décimales
                    const parts = val.split('.');
                    if (parts[1] && parts[1].length >= 2) return;
                    montantInput.value = val + key;
                });
            }
        });
        
    </script>
    
    </div> <!-- Fermeture de mainContent -->

    <style>
    .loader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      background: linear-gradient(0deg, #0f1419, #0a0f1a, #000);
    }

    .loader-wrapper {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 180px;
      height: 180px;
      font-family: "Inter", sans-serif;
      font-size: 1.1em;
      font-weight: 300;
      color: white;
      border-radius: 50%;
      background-color: transparent;
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
    }

    .loader-circle {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      aspect-ratio: 1 / 1;
      border-radius: 50%;
      background-color: transparent;
      animation: loader-combined 2.3s linear infinite;
      z-index: 0;
    }
    @keyframes loader-combined {
      0% {
        transform: rotate(90deg);
        box-shadow:
          0 6px 12px 0 #38bdf8 inset,
          0 12px 18px 0 #005dff inset,
          0 36px 36px 0 #1e40af inset,
          0 0 3px 1.2px rgba(56, 189, 248, 0.3),
          0 0 6px 1.8px rgba(0, 93, 255, 0.2);
      }
      25% {
        transform: rotate(180deg);
        box-shadow:
          0 6px 12px 0 #0099ff inset,
          0 12px 18px 0 #38bdf8 inset,
          0 36px 36px 0 #005dff inset,
          0 0 6px 2.4px rgba(56, 189, 248, 0.3),
          0 0 12px 3.6px rgba(0, 93, 255, 0.2),
          0 0 18px 6px rgba(30, 64, 175, 0.15);
      }
      50% {
        transform: rotate(270deg);
        box-shadow:
          0 6px 12px 0 #60a5fa inset,
          0 12px 6px 0 #0284c7 inset,
          0 24px 36px 0 #005dff inset,
          0 0 3px 1.2px rgba(56, 189, 248, 0.3),
          0 0 6px 1.8px rgba(0, 93, 255, 0.2);
      }
      75% {
        transform: rotate(360deg);
        box-shadow:
          0 6px 12px 0 #3b82f6 inset,
          0 12px 18px 0 #0ea5e9 inset,
          0 36px 36px 0 #2563eb inset,
          0 0 6px 2.4px rgba(56, 189, 248, 0.3),
          0 0 12px 3.6px rgba(0, 93, 255, 0.2),
          0 0 18px 6px rgba(30, 64, 175, 0.15);
      }
      100% {
        transform: rotate(450deg);
        box-shadow:
          0 6px 12px 0 #4dc8fd inset,
          0 12px 18px 0 #005dff inset,
          0 36px 36px 0 #1e40af inset,
          0 0 3px 1.2px rgba(56, 189, 248, 0.3),
          0 0 6px 1.8px rgba(0, 93, 255, 0.2);
      }
    }

    .loader-letter {
      display: inline-block;
      opacity: 0.4;
      transform: translateY(0);
      animation: loader-letter-anim 2.4s infinite;
      z-index: 1;
      border-radius: 50ch;
      border: none;
    }

    .loader-letter:nth-child(1) {
      animation-delay: 0s;
    }
    .loader-letter:nth-child(2) {
      animation-delay: 0.1s;
    }
    .loader-letter:nth-child(3) {
      animation-delay: 0.2s;
    }
    .loader-letter:nth-child(4) {
      animation-delay: 0.3s;
    }
    .loader-letter:nth-child(5) {
      animation-delay: 0.4s;
    }

    @keyframes loader-letter-anim {
      0%,
      100% {
        opacity: 0.4;
        transform: translateY(0);
      }
      20% {
        opacity: 1;
        text-shadow: #f8fcff 0 0 5px;
      }
      40% {
        opacity: 0.7;
        transform: translateY(0);
      }
    }

    .loader.fade-out {
      opacity: 0;
      transition: opacity 0.5s ease-out;
    }

    .loader.hidden {
      display: none;
    }

    #mainContent.fade-in {
      opacity: 1;
      transition: opacity 0.5s ease-in;
    }

    .dark-loader {
      display: flex;
    }

    .light-loader {
      display: none;
      background: #ffffff !important;
    }

    body:not(.dark-mode) #pageLoader {
      background: #ffffff !important;
    }

    body:not(.dark-mode) .dark-loader {
      display: none;
    }

    body:not(.dark-mode) .light-loader {
      display: flex;
    }

    .loader-circle-light {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      aspect-ratio: 1 / 1;
      border-radius: 50%;
      background-color: transparent;
      animation: loader-combined-light 2.3s linear infinite;
      z-index: 0;
    }

    @keyframes loader-combined-light {
      0% {
        transform: rotate(90deg);
        box-shadow:
          0 6px 12px 0 #1e40af inset,
          0 12px 18px 0 #3b82f6 inset,
          0 36px 36px 0 #60a5fa inset,
          0 0 3px 1.2px rgba(30, 64, 175, 0.4),
          0 0 6px 1.8px rgba(59, 130, 246, 0.3);
      }
      25% {
        transform: rotate(180deg);
        box-shadow:
          0 6px 12px 0 #2563eb inset,
          0 12px 18px 0 #1e40af inset,
          0 36px 36px 0 #3b82f6 inset,
          0 0 6px 2.4px rgba(30, 64, 175, 0.4),
          0 0 12px 3.6px rgba(59, 130, 246, 0.3),
          0 0 18px 6px rgba(96, 165, 250, 0.2);
      }
      50% {
        transform: rotate(270deg);
        box-shadow:
          0 6px 12px 0 #3b82f6 inset,
          0 12px 6px 0 #1d4ed8 inset,
          0 24px 36px 0 #2563eb inset,
          0 0 3px 1.2px rgba(30, 64, 175, 0.4),
          0 0 6px 1.8px rgba(59, 130, 246, 0.3);
      }
      75% {
        transform: rotate(360deg);
        box-shadow:
          0 6px 12px 0 #1e40af inset,
          0 12px 18px 0 #2563eb inset,
          0 36px 36px 0 #60a5fa inset,
          0 0 6px 2.4px rgba(30, 64, 175, 0.4),
          0 0 12px 3.6px rgba(59, 130, 246, 0.3),
          0 0 18px 6px rgba(96, 165, 250, 0.2);
      }
      100% {
        transform: rotate(450deg);
        box-shadow:
          0 6px 12px 0 #3b82f6 inset,
          0 12px 18px 0 #2563eb inset,
          0 36px 36px 0 #1e40af inset,
          0 0 3px 1.2px rgba(30, 64, 175, 0.4),
          0 0 6px 1.8px rgba(59, 130, 246, 0.3);
      }
    }

    .loader-text-light {
      display: flex;
      gap: 2px;
      z-index: 1;
    }

    .loader-text-light .loader-letter {
      display: inline-block;
      opacity: 0.4;
      transform: translateY(0);
      animation: loader-letter-anim-light 2.4s infinite;
      z-index: 1;
      font-family: "Inter", sans-serif;
      font-size: 1.1em;
      font-weight: 300;
      color: #1f2937;
      border-radius: 50ch;
      border: none;
    }

    .loader-text-light .loader-letter:nth-child(1) {
      animation-delay: 0s;
    }
    .loader-text-light .loader-letter:nth-child(2) {
      animation-delay: 0.1s;
    }
    .loader-text-light .loader-letter:nth-child(3) {
      animation-delay: 0.2s;
    }
    .loader-text-light .loader-letter:nth-child(4) {
      animation-delay: 0.3s;
    }
    .loader-text-light .loader-letter:nth-child(5) {
      animation-delay: 0.4s;
    }

    @keyframes loader-letter-anim-light {
      0%,
      100% {
        opacity: 0.4;
        transform: translateY(0);
      }
      20% {
        opacity: 1;
        text-shadow: #1e40af 0 0 5px;
      }
      40% {
        opacity: 0.7;
        transform: translateY(0);
      }
    }

    body,
    body.dark-mode,
    body.light-mode,
    html {
      background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
      background-attachment: fixed !important;
      min-height: 100vh !important;
    }

    .container,
    .container * {
      background: transparent !important;
    }

    .hero-section,
    .partner-card,
    .modal-content {
      background: rgba(255, 255, 255, 0.95) !important;
      backdrop-filter: blur(10px) !important;
    }

    .dark-mode .hero-section,
    .dark-mode .partner-card,
    .dark-mode .modal-content {
      background: rgba(30, 41, 59, 0.95) !important;
      backdrop-filter: blur(10px) !important;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const loader = document.getElementById('pageLoader');
        const mainContent = document.getElementById('mainContent');
        
        setTimeout(function() {
            loader.classList.add('fade-out');
            setTimeout(function() {
                loader.classList.add('hidden');
                mainContent.style.display = 'block';
                mainContent.classList.add('fade-in');
            }, 500);
        }, 300);
    });
    </script>
</body>
</html>
