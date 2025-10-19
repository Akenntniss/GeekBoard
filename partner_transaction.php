<?php
/**
 * Interface partenaire pour ajouter des transactions
 * Accès simple via ID partenaire, sans authentification
 */

// Démarrer la session immédiatement
session_start();

// Inclure les configurations nécessaires
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/subdomain_config.php';

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialiser la session du magasin si nécessaire
if (!isset($_SESSION['shop_id'])) {
    $detected_shop_id = detectShopFromSubdomain();
    if ($detected_shop_id) {
        $_SESSION['shop_id'] = $detected_shop_id;
    } else {
        // Fallback pour mkmkmk
        $_SESSION['shop_id'] = 1;
    }
}

try {
    // Récupérer l'ID du partenaire
    $partenaire_id = filter_input(INPUT_GET, 'pid', FILTER_VALIDATE_INT);
    
    if (!$partenaire_id) {
        throw new Exception('ID partenaire manquant');
    }
    
    // Connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Vérifier que le partenaire existe
    $stmt = $shop_pdo->prepare("
        SELECT id, nom, telephone 
        FROM partenaires 
        WHERE id = ?
    ");
    $stmt->execute([$partenaire_id]);
    $partenaire = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$partenaire) {
        http_response_code(404);
        die('Partenaire introuvable');
    }
    
} catch (Exception $e) {
    error_log("Erreur partner_transaction.php: " . $e->getMessage());
    http_response_code(500);
    die('Erreur du serveur');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Partenaire - <?php echo htmlspecialchars($partenaire['nom']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
            z-index: -1;
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(1deg); }
            66% { transform: translate(-20px, 20px) rotate(-1deg); }
        }
        
        .partner-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
            animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .partner-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            box-shadow: 
                0 25px 80px rgba(0,0,0,0.15),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            overflow: hidden;
            border: none;
            position: relative;
        }

        .partner-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { background-position: 200% 0; }
            50% { background-position: -200% 0; }
        }
        
        .partner-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .partner-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 70%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 30%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        .partner-header::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-conic-gradient(from 0deg at 50% 50%, transparent 0deg, rgba(255, 255, 255, 0.03) 1deg, transparent 2deg);
            animation: rotate 20s linear infinite;
            pointer-events: none;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .partner-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            margin: 0 0 0.5rem 0;
            position: relative;
            z-index: 2;
            background: linear-gradient(45deg, #ffffff, #f0f0f0);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.3)); }
            to { filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.6)); }
        }
        
        .partner-subtitle {
            font-size: 1.2rem;
            opacity: 0.95;
            margin: 0;
            position: relative;
            z-index: 2;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .nav-tabs {
            border: none;
            background: linear-gradient(145deg, #f8f9fa, #ffffff);
            padding: 1.5rem;
            margin: 0;
            position: relative;
        }

        .nav-tabs::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #667eea, transparent);
            border-radius: 2px;
        }
        
        .nav-tab {
            background: transparent;
            border: 2px solid transparent;
            border-radius: 16px;
            padding: 1.2rem 2.5rem;
            color: #6b7280;
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            margin-right: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .nav-tab::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s ease;
        }

        .nav-tab:hover::before {
            left: 100%;
        }
        
        .nav-tab.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
            box-shadow: 
                0 8px 25px rgba(102, 126, 234, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .nav-tab:hover:not(.active) {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .tab-content {
            padding: 2rem;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        .balance-summary-partner {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 25px;
            padding: 3rem 2rem;
            margin-bottom: 3rem;
            border: 1px solid rgba(102, 126, 234, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .balance-summary-partner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }
        
        .balance-card-partner {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2.5rem 3rem;
            border-radius: 20px;
            box-shadow: 
                0 15px 35px rgba(102, 126, 234, 0.4),
                0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            transform: perspective(1000px) rotateX(5deg);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .balance-card-partner:hover {
            transform: perspective(1000px) rotateX(0deg) translateY(-5px);
            box-shadow: 
                0 25px 50px rgba(102, 126, 234, 0.5),
                0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .balance-card-partner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(0.8) rotate(0deg); opacity: 0.5; }
            50% { transform: scale(1.2) rotate(180deg); opacity: 0.8; }
        }
        
        .balance-amount {
            font-size: 3.5rem;
            font-weight: 900;
            margin: 1rem 0;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            animation: countUp 1s ease-out;
        }

        @keyframes countUp {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .balance-label {
            font-size: 1rem;
            opacity: 0.95;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            position: relative;
            z-index: 2;
        }
        
        .transactions-section {
            margin-top: 2rem;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .transactions-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .loading-state, .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }
        
        .transaction-item {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(102, 126, 234, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out backwards;
        }

        .transaction-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .transaction-item:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 
                0 15px 40px rgba(102, 126, 234, 0.15),
                0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .transaction-item:hover::before {
            opacity: 1;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 1rem;
            animation: pulse 2s infinite;
        }

        .status-badge.pending {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .status-badge.approved {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            animation: none;
        }

        /* Désactiver l'animation pour les statuts rejetés */
        .status-badge.rejected {
            animation: none;
        }

        .status-badge i {
            font-size: 0.9rem;
        }

        /* Styles pour le formulaire */
        .form-section {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 25px;
            padding: 2.5rem;
            border: 1px solid rgba(102, 126, 234, 0.1);
            position: relative;
            overflow: hidden;
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        .form-group {
            margin-bottom: 2rem;
            position: relative;
        }

        .form-label {
            display: block;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.8rem;
            font-size: 1rem;
            position: relative;
        }

        .form-label.required::after {
            content: '*';
            color: #ef4444;
            margin-left: 0.25rem;
            font-weight: 900;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 1.2rem 1.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 500;
            background: linear-gradient(145deg, #ffffff, #f9fafb);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 1;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 
                0 0 0 4px rgba(102, 126, 234, 0.1),
                0 8px 25px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .radio-group {
            display: flex;
            gap: 1.5rem;
            margin-top: 0.5rem;
        }

        .radio-option {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            background: linear-gradient(145deg, #ffffff, #f9fafb);
            position: relative;
            overflow: hidden;
            min-height: 80px;
        }

        .radio-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .radio-option:hover::before {
            left: 100%;
        }

        .radio-option:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .radio-option.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            color: #667eea;
            font-weight: 700;
        }

        .radio-option input[type="radio"] {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 50%;
            position: relative;
            transition: all 0.3s ease;
        }

        .radio-option input[type="radio"]:checked {
            border-color: #667eea;
            background: #667eea;
        }

        .radio-option input[type="radio"]:checked::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
        }

        .btn-submit {
            width: 100%;
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 15px 35px rgba(102, 126, 234, 0.4),
                0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Fix pour s'assurer que le bouton est cliquable */
        .btn-submit {
            pointer-events: auto !important;
            cursor: pointer !important;
        }

        /* Animation pour les éléments de formulaire */
        .form-group {
            animation: slideInLeft 0.6s ease-out backwards;
        }

        .form-group:nth-child(2) { animation-delay: 0.1s; }
        .form-group:nth-child(3) { animation-delay: 0.2s; }
        .form-group:nth-child(4) { animation-delay: 0.3s; }
        .btn-submit { animation-delay: 0.4s; }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .transaction-type {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .type-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .type-icon.positive {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .type-icon.negative {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .type-info {
            flex: 1;
        }
        
        .type-label {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 0.25rem 0;
        }
        
        .type-date {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .transaction-amount {
            font-size: 1.5rem;
            font-weight: 800;
            padding: 0.5rem 1rem;
            border-radius: 12px;
        }
        
        .transaction-amount.positive {
            color: #10b981;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.05));
        }
        
        .transaction-amount.negative {
            color: #ef4444;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05));
        }
        
        .transaction-description {
            font-size: 0.95rem;
            color: #4b5563;
            line-height: 1.5;
            padding: 1rem;
            background: rgba(0,0,0,0.02);
            border-radius: 12px;
            border-left: 3px solid #e5e7eb;
            margin-top: 1rem;
        }
        
        .transaction-status {
            margin-top: 1rem;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-badge.pending {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .status-badge.approved {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .transaction-time {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .form-section {
            background: linear-gradient(145deg, #f8f9fa, #ffffff);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            padding: 0.875rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-group .btn {
            border-radius: 12px;
            margin-right: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .partner-container {
                margin: 1rem auto;
            }
            
            .nav-tabs {
                flex-direction: column;
            }
            
            .nav-tab {
                margin-right: 0;
                margin-bottom: 0.5rem;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 1rem;
            }
            
            .radio-option {
                min-height: auto;
                padding: 1.25rem;
            }
            
            .transaction-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .transaction-amount {
                text-align: center;
            }
        }

        /* ===== UI Overrides to align with app look (lighter, card-based) ===== */
        :root {
            --gb-bg: #f4f6f8;
            --gb-card: #ffffff;
            --gb-border: #e5e7eb;
            --gb-text: #1f2937;
            --gb-muted: #6b7280;
            --gb-primary: #667eea;
            --gb-primary-2: #764ba2;
            --gb-success: #10b981;
            --gb-danger: #ef4444;
        }

        body { background: var(--gb-bg); color: var(--gb-text); }

        .partner-card { background: var(--gb-card); border: 1px solid var(--gb-border); border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); }
        .partner-header { padding: 28px 24px; }
        .partner-header h1 { font-size: 24px; font-weight: 800; }
        .partner-subtitle { font-weight: 600; opacity: .95; }

        .nav-tabs { display: flex; gap: 8px; padding: 12px 16px; background: #fafafa; border-bottom: 1px solid var(--gb-border); }
        .nav-tab { padding: 10px 14px; border-radius: 12px; border: 1px solid var(--gb-border); color: var(--gb-muted); font-weight: 700; }
        .nav-tab.active { background: linear-gradient(135deg, var(--gb-primary), var(--gb-primary-2)); color: #fff; border-color: transparent; }
        .nav-tab:hover:not(.active) { background: #eef2ff; color: var(--gb-primary); }

        .tab-content { padding: 20px; }

        .balance-summary-partner { background: #fff; border: 1px solid var(--gb-border); border-radius: 16px; padding: 24px; }
        .balance-amount { font-size: 40px; font-weight: 900; text-shadow: none; }
        .balance-label { color: var(--gb-muted); }

        .transactions-list { gap: 12px; }
        .transaction-item { background: #fff; border: 1px solid var(--gb-border); box-shadow: 0 10px 24px rgba(0,0,0,0.05); padding: 16px; border-radius: 14px; }
        .transaction-type { gap: 12px; }
        .type-icon { width: 38px; height: 38px; border-radius: 10px; }
        .type-icon.positive { background: linear-gradient(135deg, var(--gb-success), #059669); }
        .type-icon.negative { background: linear-gradient(135deg, var(--gb-danger), #dc2626); }
        .type-label { font-weight: 800; }
        .type-date { color: var(--gb-muted); font-size: 13px; }
        .transaction-amount { font-weight: 800; }
        .transaction-amount.positive { color: var(--gb-success); background: #ecfdf5; }
        .transaction-amount.negative { color: var(--gb-danger); background: #fee2e2; }
        .transaction-description { background: #fafafa; border: 1px solid var(--gb-border); color: #374151; border-left: none; }
        .transaction-time { color: var(--gb-muted); }
        .status-badge.pending { background: #fef3c7; color: #92400e; box-shadow: none; }
        .status-badge.approved { background: #d1fae5; color: #065f46; box-shadow: none; }

        .form-section { background: #fff; border: 1px solid var(--gb-border); border-radius: 16px; padding: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-label { color: var(--gb-text); font-weight: 800; }
        .form-control, .form-select { border: 1px solid var(--gb-border); border-radius: 12px; padding: 12px 14px; background: #fff; }
        .form-control:focus, .form-select:focus { border-color: var(--gb-primary); box-shadow: 0 0 0 3px rgba(102,126,234,.15); transform: none; }
        .radio-group { gap: 12px; flex-wrap: wrap; }
        .radio-option { border: 1px solid var(--gb-border); border-radius: 12px; padding: 12px; background: #fff; }
        .radio-option.selected { border-color: var(--gb-primary); background: #eef2ff; color: var(--gb-primary); }
        .btn-submit { padding: 14px; border-radius: 12px; background: linear-gradient(135deg, var(--gb-primary), var(--gb-primary-2)); font-weight: 800; text-transform: none; letter-spacing: .3px; }
    </style>
</head>
<body>
    <div class="partner-container">
        <div class="partner-card">
            <div class="partner-header">
                <h1><i class="fas fa-handshake me-3"></i>Espace Partenaire</h1>
                <p class="partner-subtitle"><?php echo htmlspecialchars($partenaire['nom']); ?></p>
            </div>
            
            <div class="nav-tabs">
                <div class="nav-tab active" data-tab="history">
                    <i class="fas fa-history me-2"></i>Mon Historique
                </div>
                <div class="nav-tab" data-tab="new-transaction">
                    <i class="fas fa-plus me-2"></i>Nouvelle Transaction
                </div>
            </div>
            
            <div class="tab-content">
                <!-- Onglet Historique -->
                <div class="tab-pane active" id="history-tab">
                    <div class="balance-summary-partner">
                        <div class="balance-card-partner">
                            <div class="balance-label">Solde Actuel</div>
                            <div class="balance-amount" id="currentBalance">0.00 €</div>
                        </div>
                    </div>
                    
                    <div class="transactions-section">
                        <h3 class="section-title">
                            <i class="fas fa-receipt"></i>
                            Historique des Transactions
                        </h3>
                        <div class="transactions-list" id="transactionsList">
                            <div class="loading-state">
                                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                <p>Chargement de l'historique...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Onglet Nouvelle Transaction -->
                <div class="tab-pane" id="new-transaction-tab">
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-plus-circle"></i>
                            Ajouter une Transaction
                        </h3>
                        
                        <div class="form-section">
                            <form id="partnerTransactionForm">
                                <input type="hidden" name="partenaire_id" value="<?php echo $partenaire['id']; ?>">
                                
                                <div class="form-group">
                                    <label class="form-label required">Type de transaction</label>
                                    <div class="radio-group">
                                        <div class="radio-option" onclick="selectRadio('typeAvance')">
                                            <input type="radio" name="type" id="typeAvance" value="AVANCE" required checked>
                                            <label for="typeAvance">
                                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <i class="fas fa-arrow-up" style="color: #10b981;"></i>
                                                    <div>
                                                        <div style="font-weight: 700; color: #1f2937;">Avance</div>
                                                        <div style="font-size: 0.85rem; color: #6b7280; font-weight: 400;">J'emprunte/achète du matériel</div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                        <div class="radio-option" onclick="selectRadio('typeRemboursement')">
                                            <input type="radio" name="type" id="typeRemboursement" value="REMBOURSEMENT" required>
                                            <label for="typeRemboursement">
                                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <i class="fas fa-arrow-down" style="color: #ef4444;"></i>
                                                    <div>
                                                        <div style="font-weight: 700; color: #1f2937;">Remboursement</div>
                                                        <div style="font-size: 0.85rem; color: #6b7280; font-weight: 400;">Je prête/rends du matériel</div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="montant" class="form-label required">Montant (€)</label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="montant" name="montant" placeholder="0.00" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description" class="form-label required">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" 
                                            placeholder="Décrivez la transaction..." required></textarea>
                                </div>
                                
                                <button type="submit" class="btn-submit">
                                    <i class="fas fa-paper-plane" style="margin-right: 0.5rem;"></i>
                                    Enregistrer la Transaction
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const partenaireId = <?php echo $partenaire['id']; ?>;
        
        // Fonction pour sélectionner un radio button
        window.selectRadio = function(radioId) {
            const radio = document.getElementById(radioId);
            if (radio) {
                radio.checked = true;
                
                // Mettre à jour l'apparence
                document.querySelectorAll('.radio-option').forEach(option => {
                    option.classList.remove('selected');
                });
                radio.closest('.radio-option').classList.add('selected');
            }
        }

        // Initialiser la sélection par défaut
        const defaultRadio = document.querySelector('input[name="type"]:checked');
        if (defaultRadio) {
            defaultRadio.closest('.radio-option').classList.add('selected');
        }

        // Gestion des onglets
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                // Désactiver tous les onglets
                document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                
                // Activer l'onglet cliqué
                this.classList.add('active');
                if (targetTab === 'history') {
                    document.getElementById('history-tab').classList.add('active');
                } else if (targetTab === 'new-transaction') {
                    document.getElementById('new-transaction-tab').classList.add('active');
                }
            });
        });
        
        // Charger l'historique des transactions au chargement
        loadTransactionHistory();
        
        function loadTransactionHistory() {
            fetch(`ajax/get_partner_transactions.php?partenaire_id=${partenaireId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour le solde
                        const balance = parseFloat(data.solde || 0);
                        const balanceElement = document.getElementById('currentBalance');
                        balanceElement.textContent = balance.toFixed(2) + ' €';
                        balanceElement.className = 'balance-amount ' + (balance >= 0 ? 'positive' : 'negative');
                        
                        // Afficher les transactions
                        renderTransactions(data.transactions || []);
                    } else {
                        showError('Erreur lors du chargement de l\'historique');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showError('Erreur de connexion');
                });
        }
        
        function renderTransactions(transactions) {
            const container = document.getElementById('transactionsList');
            
            if (transactions.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-receipt fa-3x mb-3"></i>
                        <p>Aucune transaction enregistrée</p>
                    </div>
                `;
                return;
            }
            
                const transactionsHtml = transactions.map(transaction => {
                const date = new Date(transaction.date_transaction);
                const dateFormatted = date.toLocaleDateString('fr-FR');
                const timeFormatted = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                
                const montant = parseFloat(transaction.montant);
                const isNegative = transaction.type === 'REMBOURSEMENT';
                const typeClass = isNegative ? 'negative' : 'positive';
                const typeIcon = isNegative ? 'fas fa-arrow-down' : 'fas fa-arrow-up';
                const montantPrefix = isNegative ? '-' : '+';
                
                const typeLabels = {
                    'AVANCE': 'Avance',
                    'REMBOURSEMENT': 'Remboursement'
                };
                
                // Affichage du statut
                const isPending = transaction.status === 'pending';
                const isRejected = transaction.status === 'rejected';
                let statusBadge;
                if (isPending) {
                    statusBadge = '<div class="status-badge pending"><i class="fas fa-clock"></i> En attente de validation</div>';
                } else if (isRejected) {
                    const reasonLink = transaction.reject_reason ? `<a href="#" onclick=\"showRejectReason('${encodeURIComponent(transaction.reject_reason)}')\" class=\"ms-2\">Voir motif</a>` : '';
                    statusBadge = '<div class="status-badge rejected" style="background:#fee2e2;color:#991b1b;"><i class="fas fa-times"></i> Rejetée' + reasonLink + '</div>';
                } else {
                    statusBadge = '<div class="status-badge approved"><i class="fas fa-check"></i> Validée</div>';
                }
                
                return `
                    <div class="transaction-item">
                        <div class="transaction-header">
                            <div class="transaction-type">
                                <div class="type-icon ${typeClass}">
                                    <i class="${typeIcon}"></i>
                                </div>
                                <div class="type-info">
                                    <div class="type-label">${typeLabels[transaction.type] || transaction.type}</div>
                                    <div class="type-date">${dateFormatted}</div>
                                </div>
                            </div>
                            <div class="transaction-amount ${typeClass}">
                                ${montantPrefix}${montant.toFixed(2)} €
                            </div>
                        </div>
                        <div class="transaction-description">
                            ${transaction.description || '<em>Aucune description</em>'}
                        </div>
                        <div class="transaction-status">
                            ${statusBadge}
                        </div>
                        <div class="transaction-time">
                            <i class="fas fa-clock"></i>
                            ${timeFormatted}
                        </div>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = transactionsHtml;
        }

        window.showRejectReason = function(encoded){
            try{
                const text = decodeURIComponent(encoded).replace(/\+/g,' ');
                const modal = document.createElement('div');
                modal.className = 'modal fade';
                modal.innerHTML = `
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header"><h5 class="modal-title">Motif du rejet</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                      <div class="modal-body"><pre style="white-space:pre-wrap">${text || 'Aucun motif fourni.'}</pre></div>
                    </div>
                  </div>`;
                document.body.appendChild(modal);
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
                modal.addEventListener('hidden.bs.modal', ()=> modal.remove());
            }catch(e){ alert('Motif non disponible'); }
        }
        
        function showError(message) {
            document.getElementById('transactionsList').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3 text-danger"></i>
                    <p>${message}</p>
                </div>
            `;
        }
        
        // Gestion du formulaire de transaction
        document.getElementById('partnerTransactionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Désactiver le bouton
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...';
            
            fetch('ajax/add_partner_transaction.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Transaction enregistrée avec succès !', 'success');
                    this.reset();
                    // Recharger l'historique et basculer vers l'onglet historique
                    loadTransactionHistory();
                    document.querySelector('[data-tab="history"]').click();
                } else {
                    showNotification(data.message || 'Erreur lors de l\'enregistrement', 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'danger');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Enregistrer la Transaction';
            });
        });
        
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 5000);
        }
    });
    </script>
</body>
</html>