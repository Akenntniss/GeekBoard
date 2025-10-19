<?php
// Script d'export pour affichage HTML dans un nouvel onglet

// Définir le chemin de base
define('BASE_PATH', dirname(__DIR__));

// Inclure la configuration de session et la base de données
require_once BASE_PATH . '/config/session_config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Vérifier la session utilisateur
if (!isset($_SESSION['user_id'])) {
    echo '<html><body><h1>Erreur d\'authentification</h1><p>Vous devez être connecté pour accéder à cette page.</p></body></html>';
    exit;
}

// Récupérer le paramètre de filtre
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Utiliser la connexion à la base de données du système
try {
    $pdo = getShopDBConnection();
    if (!$pdo) {
        throw new Exception("Impossible de se connecter à la base de données du magasin");
    }
} catch(Exception $e) {
    echo '<html><body><h1>Erreur de base de données</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
    exit;
}

// Construire la requête SQL selon le filtre
$sql = "SELECT 
    p.id,
    p.reference,
    p.nom,
    p.description,
    p.prix_achat,
    p.prix_vente,
    p.quantite,
    p.seuil_alerte,
    p.status,
    p.created_at
FROM produits p
WHERE 1=1";

$params = [];

// Appliquer les filtres
switch ($filter) {
    case 'stock':
        $sql .= " AND p.quantite > 0";
        break;
    case 'alert':
        $sql .= " AND p.quantite <= p.seuil_alerte AND p.quantite > 0";
        break;
    case 'out':
        $sql .= " AND p.quantite = 0";
        break;
    case 'all':
    default:
        // Pas de filtre supplémentaire
        break;
}

$sql .= " ORDER BY p.nom ASC";

// Exécuter la requête
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<html><body><h1>Erreur SQL</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
    exit;
}

// Déterminer le label du filtre
$filterLabel = '';
switch ($filter) {
    case 'stock':
        $filterLabel = 'Produits en stock';
        break;
    case 'alert':
        $filterLabel = 'Produits en alerte';
        break;
    case 'out':
        $filterLabel = 'Produits épuisés';
        break;
    default:
        $filterLabel = 'Inventaire complet';
        break;
}

// Calculer les statistiques
$total_produits = count($produits);
$total_stock = 0;
$total_valeur_achat = 0;
$total_valeur_vente = 0;
$produits_en_alerte = 0;
$produits_epuises = 0;

foreach ($produits as $produit) {
    $total_stock += $produit['quantite'];
    $total_valeur_achat += $produit['prix_achat'] * $produit['quantite'];
    $total_valeur_vente += $produit['prix_vente'] * $produit['quantite'];
    
    if ($produit['quantite'] == 0) {
        $produits_epuises++;
    } elseif ($produit['quantite'] <= $produit['seuil_alerte']) {
        $produits_en_alerte++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaire - <?php echo htmlspecialchars($filterLabel); ?></title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }
            .no-print {
                display: none !important;
            }
            body {
                font-size: 12px;
                line-height: 1.3;
            }
            table {
                page-break-inside: avoid;
            }
            tr {
                page-break-inside: avoid;
            }
            .header-info {
                margin-bottom: 20px;
            }
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        
        .header p {
            color: #7f8c8d;
            margin: 10px 0 0 0;
            font-size: 1.1em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }
        
        .stat-card.danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: #e3f2fd;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-align: center;
            min-width: 60px;
            display: inline-block;
        }
        
        .status-stock {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .status-alert {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .status-out {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .prix {
            text-align: right;
            font-weight: 600;
        }
        
        .quantite {
            text-align: center;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .print-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin: 20px 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }
        
        .print-btn:hover {
            background: #45a049;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            table, th, td {
                font-size: 0.8em;
            }
            
            th, td {
                padding: 8px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <h1>📋 Inventaire GeekBoard</h1>
            <p><?php echo htmlspecialchars($filterLabel); ?> - <?php echo date('d/m/Y à H:i'); ?></p>
        </div>

        <!-- Bouton d'impression -->
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button class="print-btn" onclick="window.print()">
                🖨️ Imprimer cette page
            </button>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card info">
                <div class="stat-value"><?php echo $total_produits; ?></div>
                <div class="stat-label">Références</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?php echo $total_stock; ?></div>
                <div class="stat-label">Unités en stock</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?php echo $produits_en_alerte; ?></div>
                <div class="stat-label">En alerte</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-value"><?php echo $produits_epuises; ?></div>
                <div class="stat-label">Épuisés</div>
            </div>
        </div>

        <!-- Valeurs -->
        <div class="stats-grid">
            <div class="stat-card info">
                <div class="stat-value"><?php echo number_format($total_valeur_achat, 0, ',', ' '); ?> €</div>
                <div class="stat-label">Valeur d'achat</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?php echo number_format($total_valeur_vente, 0, ',', ' '); ?> €</div>
                <div class="stat-label">Valeur de vente</div>
            </div>
        </div>

        <!-- Tableau -->
        <?php if (empty($produits)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <h3>Aucun produit trouvé</h3>
                <p>Aucun produit ne correspond aux critères de filtre sélectionnés.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Nom du produit</th>
                        <th>Prix d'achat</th>
                        <th>Prix de vente</th>
                        <th>Stock</th>
                        <th>Seuil</th>
                        <th>Statut</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produits as $produit): ?>
                        <?php
                        // Déterminer le statut et la classe CSS
                        if ($produit['quantite'] == 0) {
                            $statutStock = 'Épuisé';
                            $cssClass = 'status-out';
                        } elseif ($produit['quantite'] <= $produit['seuil_alerte']) {
                            $statutStock = 'En alerte';
                            $cssClass = 'status-alert';
                        } else {
                            $statutStock = 'En stock';
                            $cssClass = 'status-stock';
                        }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($produit['reference'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($produit['nom']); ?></td>
                            <td class="prix"><?php echo number_format($produit['prix_achat'], 2, ',', ' '); ?> €</td>
                            <td class="prix"><?php echo number_format($produit['prix_vente'], 2, ',', ' '); ?> €</td>
                            <td class="quantite"><?php echo $produit['quantite']; ?></td>
                            <td class="quantite"><?php echo $produit['seuil_alerte']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $cssClass; ?>">
                                    <?php echo $statutStock; ?>
                                </span>
                            </td>
                            <td><?php 
                                $statusIcon = '';
                                $statusText = '';
                                switch($produit['status']) {
                                    case 'temporaire':
                                        $statusIcon = '⏳';
                                        $statusText = 'Temporaire';
                                        break;
                                    case 'a_retourner':
                                        $statusIcon = '↩️';
                                        $statusText = 'À retourner';
                                        break;
                                    default:
                                        $statusIcon = '🔒';
                                        $statusText = 'Permanent';
                                        break;
                                }
                                echo $statusIcon . ' ' . $statusText;
                            ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Pied de page -->
        <div class="footer">
            <p>Document généré par GeekBoard le <?php echo date('d/m/Y à H:i:s'); ?></p>
            <p>Système de gestion d'inventaire - mkmkmk.mdgeek.top</p>
        </div>
    </div>

    <script>
        // Auto-focus sur l'impression si demandé
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autoprint') === '1') {
            window.onload = function() {
                setTimeout(() => window.print(), 500);
            };
        }
    </script>
</body>
</html>
