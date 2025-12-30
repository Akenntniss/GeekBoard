<?php
/**
 * Page devis simple pour iframe - Version de test
 */

// Initialiser la session si nécessaire
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Détecter le sous-domaine
$host = $_SERVER['HTTP_HOST'] ?? '';
$subdomain = '';

if (strpos($host, 'localhost') !== false) {
    $subdomain = 'mkmkmk'; // Par défaut en local
} else {
    $parts = explode('.', $host);
    if (count($parts) >= 3) {
        $subdomain = $parts[0];
    }
}

// Afficher des informations de debug
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Devis en attente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-bug me-2"></i>Debug - Devis en attente</h5>
            </div>
            <div class="card-body">
                <h6>Informations de session :</h6>
                <ul>
                    <li><strong>Host :</strong> <?php echo htmlspecialchars($host); ?></li>
                    <li><strong>Sous-domaine détecté :</strong> <?php echo htmlspecialchars($subdomain); ?></li>
                    <li><strong>Shop ID en session :</strong> <?php echo isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'Non défini'; ?></li>
                    <li><strong>Shop name en session :</strong> <?php echo isset($_SESSION['shop_name']) ? $_SESSION['shop_name'] : 'Non défini'; ?></li>
                </ul>

                <?php
                // Tenter de se connecter et récupérer des informations
                try {
                    require_once __DIR__ . '/../config/database.php';
                    
                    echo "<h6>Test de connexion :</h6>";
                    
                    // Connexion principale
                    $main_pdo = getMainDBConnection();
                    if ($main_pdo) {
                        echo "<div class='alert alert-success'>✅ Connexion principale réussie</div>";
                        
                        // Récupérer les infos du magasin
                        $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1 LIMIT 1");
                        $stmt->execute([$subdomain]);
                        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($shop) {
                            echo "<div class='alert alert-info'>";
                            echo "<strong>Informations magasin :</strong><br>";
                            echo "ID: " . $shop['id'] . "<br>";
                            echo "Nom: " . $shop['name'] . "<br>";
                            echo "Base: " . $shop['db_name'] . "<br>";
                            echo "Host: " . $shop['db_host'] . "<br>";
                            echo "Port: " . ($shop['db_port'] ?? '3306') . "<br>";
                            echo "</div>";
                            
                            // Tenter connexion directe
                            try {
                                $shop_dsn = "mysql:host={$shop['db_host']};port=" . ($shop['db_port'] ?? '3306') . ";dbname={$shop['db_name']};charset=utf8mb4";
                                $shop_pdo = new PDO($shop_dsn, $shop['db_user'], $shop['db_pass'], [
                                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                                ]);
                                
                                echo "<div class='alert alert-success'>✅ Connexion magasin réussie</div>";
                                
                                // Récupérer quelques devis de test
                                $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM devis WHERE statut = 'envoye'");
                                $count = $stmt->fetch()['total'];
                                
                                echo "<div class='alert alert-info'>";
                                echo "<strong>Devis en attente :</strong> $count";
                                echo "</div>";
                                
                                // Afficher la structure de la table devis
                                $stmt = $shop_pdo->query("DESCRIBE devis");
                                $columns = $stmt->fetchAll();
                                
                                echo "<h6>Structure de la table devis :</h6>";
                                echo "<div class='table-responsive'>";
                                echo "<table class='table table-sm'>";
                                echo "<thead><tr><th>Colonne</th><th>Type</th></tr></thead>";
                                echo "<tbody>";
                                foreach ($columns as $col) {
                                    echo "<tr><td>" . $col['Field'] . "</td><td>" . $col['Type'] . "</td></tr>";
                                }
                                echo "</tbody></table>";
                                echo "</div>";
                                
                                if ($count > 0) {
                                    $stmt = $shop_pdo->query("
                                        SELECT d.id, d.date_creation, d.date_expiration, d.total_ttc, 
                                               c.nom, c.prenom, c.telephone 
                                        FROM devis d
                                        LEFT JOIN clients c ON d.client_id = c.id 
                                        WHERE d.statut = 'envoye' 
                                        ORDER BY d.date_creation DESC 
                                        LIMIT 5
                                    ");
                                    $devis = $stmt->fetchAll();
                                    
                                    echo "<h6>Exemples de devis :</h6>";
                                    echo "<div class='table-responsive'>";
                                    echo "<table class='table table-sm'>";
                                    echo "<thead><tr><th>ID</th><th>Client</th><th>Montant</th><th>Créé</th><th>Expire</th></tr></thead>";
                                    echo "<tbody>";
                                    
                                    foreach ($devis as $d) {
                                        echo "<tr>";
                                        echo "<td>#{$d['id']}</td>";
                                        echo "<td>" . htmlspecialchars($d['nom'] . ' ' . $d['prenom']) . "</td>";
                                        echo "<td>" . number_format($d['total_ttc'], 2, ',', ' ') . " €</td>";
                                        echo "<td>" . date('d/m/Y', strtotime($d['date_creation'])) . "</td>";
                                        echo "<td>" . date('d/m/Y', strtotime($d['date_expiration'])) . "</td>";
                                        echo "</tr>";
                                    }
                                    
                                    echo "</tbody></table>";
                                    echo "</div>";
                                }
                                
                            } catch (PDOException $e) {
                                echo "<div class='alert alert-danger'>❌ Erreur connexion magasin: " . $e->getMessage() . "</div>";
                            }
                            
                        } else {
                            echo "<div class='alert alert-warning'>⚠️ Aucun magasin trouvé pour le sous-domaine: $subdomain</div>";
                        }
                        
                    } else {
                        echo "<div class='alert alert-danger'>❌ Connexion principale échouée</div>";
                    }
                    
                } catch (Exception $e) {
                    echo "<div class='alert alert-danger'>❌ Erreur: " . $e->getMessage() . "</div>";
                }
                ?>
                
                <div class="mt-4">
                    <button class="btn btn-primary" onclick="window.location.reload()">
                        <i class="fas fa-sync me-2"></i>Actualiser
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
