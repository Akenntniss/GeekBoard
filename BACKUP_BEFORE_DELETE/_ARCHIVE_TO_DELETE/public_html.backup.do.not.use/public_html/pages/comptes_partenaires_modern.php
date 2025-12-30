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
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        
        .modal {
            background: white;
            border-radius: 20px;
            max-width: 90vw;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        .modal-overlay.active .modal {
            transform: scale(1);
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
        <div class="modal" style="width: 600px;">
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
                <form id="ajouterTransactionForm">
                    <div class="form-group">
                        <label class="form-label">Partenaire *</label>
                        <select class="form-control form-select" name="partenaire_id" required>
                            <option value="">Sélectionner un partenaire</option>
                            <?php foreach ($partenaires as $partenaire): ?>
                                <option value="<?php echo $partenaire['id']; ?>">
                                    <?php echo htmlspecialchars($partenaire['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type de transaction *</label>
                        <select class="form-control form-select" name="type" required>
                            <option value="">Sélectionner le type</option>
                            <option value="debit">Débit (ce que le partenaire nous doit)</option>
                            <option value="credit">Crédit (ce que nous devons au partenaire)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Montant (€) *</label>
                        <input type="number" class="form-control" name="montant" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="modern-btn btn-secondary" onclick="closeModal('ajouterTransactionModal')">Annuler</button>
                <button class="modern-btn btn-success" onclick="ajouterTransaction()">Enregistrer</button>
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
            const modal = document.getElementById(modalId);
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('active');
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
        function ajouterTransaction() {
            const form = document.getElementById('ajouterTransactionForm');
            const formData = new FormData(form);
            
            fetch('ajax/add_transaction_partenaires.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaction ajoutée avec succès !');
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
                    
                    html += `
                        <tr>
                            <td>${new Date(transaction.date_transaction).toLocaleDateString('fr-FR')}</td>
                            <td><span class="${typeClass}"><i class="${typeIcon}"></i> ${transaction.type === 'credit' ? 'Crédit' : 'Débit'}</span></td>
                            <td><span class="${typeClass}">${parseFloat(transaction.montant).toFixed(2)} €</span></td>
                            <td>${transaction.description}</td>
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

