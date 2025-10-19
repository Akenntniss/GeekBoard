<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la configuration des sous-domaines pour la détection automatique du magasin
require_once __DIR__ . '/../config/subdomain_config.php';

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Vérifier la session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Non autorisé');
}

// Récupérer les paramètres (POST ou GET pour compatibilité)
$format = isset($_POST['format']) ? $_POST['format'] : (isset($_GET['format']) ? $_GET['format'] : 'csv');
$filter = isset($_POST['filter']) ? $_POST['filter'] : (isset($_GET['filter']) ? $_GET['filter'] : 'all'); // all, stock, alert, out

// Obtenir la connexion à la base de données
try {
    $shop_pdo = getShopDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    exit('Erreur de connexion à la base de données');
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
    p.is_temporaire,
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
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($params);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur SQL: " . $e->getMessage());
    http_response_code(500);
    exit('Erreur lors de la récupération des données');
}

// Si aucun produit trouvé
if (empty($produits)) {
    http_response_code(404);
    exit('Aucun produit trouvé');
}

// Générer le nom du fichier
$timestamp = date('YmdHis');
$filterLabel = '';
switch ($filter) {
    case 'stock':
        $filterLabel = 'en_stock';
        break;
    case 'alert':
        $filterLabel = 'en_alerte';
        break;
    case 'out':
        $filterLabel = 'epuises';
        break;
    default:
        $filterLabel = 'complet';
        break;
}

$filename = "Inventaire_{$filterLabel}_{$timestamp}";

// Exporter selon le format demandé
if ($format === 'csv') {
    exportCSV($produits, $filename);
} else {
    exportHTML($produits, $filename, $filter);
}

/**
 * Fonction pour exporter l'inventaire en format CSV (compatible Excel)
 */
function exportCSV($produits, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // Créer un gestionnaire de fichier pour écrire dans php://output
    $output = fopen('php://output', 'w');
    
    // Ajouter le BOM UTF-8 pour Excel
    fputs($output, "\xEF\xBB\xBF");
    
    // Définir les en-têtes de colonnes
    $headers = [
        'ID',
        'Référence',
        'Nom du produit',
        'Description',
        'Prix d\'achat (€)',
        'Prix de vente (€)',
        'Stock actuel',
        'Seuil d\'alerte',
        'Statut stock',
        'Type',
        'Date de création'
    ];
    
    // Écrire les en-têtes
    fputcsv($output, $headers, ';');
    
    // Écrire les données
    foreach ($produits as $produit) {
        // Déterminer le statut du stock
        $statutStock = '';
        if ($produit['quantite'] == 0) {
            $statutStock = 'Épuisé';
        } elseif ($produit['quantite'] <= $produit['seuil_alerte']) {
            $statutStock = 'En alerte';
        } else {
            $statutStock = 'En stock';
        }
        
        $row = [
            $produit['id'],
            $produit['reference'] ?: '-',
            $produit['nom'],
            $produit['description'] ?: '-',
            number_format($produit['prix_achat'], 2, ',', '') . ' €',
            number_format($produit['prix_vente'], 2, ',', '') . ' €',
            $produit['quantite'],
            $produit['seuil_alerte'],
            $statutStock,
            $produit['is_temporaire'] ? 'Temporaire' : 'Permanent',
            date('d/m/Y H:i', strtotime($produit['created_at']))
        ];
        
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
}

/**
 * Fonction pour exporter l'inventaire en format HTML (visualisable dans le navigateur)
 */
function exportHTML($produits, $filename, $filter) {
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
    
    // En-têtes pour affichage HTML
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $filename . '.html"');
    
    echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Inventaire - ' . htmlspecialchars($filterLabel) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .meta-info { background: #f5f5f5; padding: 10px; border-radius: 5px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .status-epuise { color: #d32f2f; font-weight: bold; }
        .status-alerte { color: #f57c00; font-weight: bold; }
        .status-stock { color: #388e3c; font-weight: bold; }
        .prix { text-align: right; }
        .quantite { text-align: center; font-weight: bold; }
        @media print { 
            body { margin: 0; } 
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <h1>Export Inventaire - ' . htmlspecialchars($filterLabel) . '</h1>
    
    <div class="meta-info">
        <strong>Date d\'export:</strong> ' . date('d/m/Y à H:i:s') . '<br>
        <strong>Nombre de produits:</strong> ' . count($produits) . '<br>
        <strong>Filtre appliqué:</strong> ' . htmlspecialchars($filterLabel) . '
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Référence</th>
                <th>Nom du produit</th>
                <th>Prix d\'achat</th>
                <th>Prix de vente</th>
                <th>Stock</th>
                <th>Seuil</th>
                <th>Statut</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($produits as $produit) {
        // Déterminer le statut et la classe CSS
        $statutStock = '';
        $cssClass = '';
        if ($produit['quantite'] == 0) {
            $statutStock = 'Épuisé';
            $cssClass = 'status-epuise';
        } elseif ($produit['quantite'] <= $produit['seuil_alerte']) {
            $statutStock = 'En alerte';
            $cssClass = 'status-alerte';
        } else {
            $statutStock = 'En stock';
            $cssClass = 'status-stock';
        }
        
        echo '<tr>
            <td>' . htmlspecialchars($produit['reference'] ?: '-') . '</td>
            <td>' . htmlspecialchars($produit['nom']) . '</td>
            <td class="prix">' . number_format($produit['prix_achat'], 2, ',', ' ') . ' €</td>
            <td class="prix">' . number_format($produit['prix_vente'], 2, ',', ' ') . ' €</td>
            <td class="quantite">' . $produit['quantite'] . '</td>
            <td class="quantite">' . $produit['seuil_alerte'] . '</td>
            <td class="' . $cssClass . '">' . $statutStock . '</td>
            <td>' . ($produit['is_temporaire'] ? 'Temporaire' : 'Permanent') . '</td>
        </tr>';
    }
    
    echo '</tbody>
    </table>
    
    <div class="meta-info no-print" style="margin-top: 30px;">
        <small>Export généré par GeekBoard - Système de gestion d\'inventaire</small>
    </div>
    
</body>
</html>';
    
    exit;
}
?>



