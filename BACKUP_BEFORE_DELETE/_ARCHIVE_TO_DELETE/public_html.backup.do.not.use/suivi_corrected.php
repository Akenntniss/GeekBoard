<?php
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure les fichiers nécessaires
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Démarrer la session pour la détection du shop
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Détecter le shop_id depuis le sous-domaine si pas encore défini
if (!isset($_SESSION['shop_id'])) {
    require_once __DIR__ . '/config/subdomain_config.php';
}

// Initialiser la connexion à la base de données boutique
$shop_pdo = getShopDBConnection();

// Initialiser les variables
$resultats = [];
$recherche_effectuee = false;
$message_erreur = '';
$reparation_id = '';
$client_email = '';

// Vérifier que nous avons une connexion valide
if ($shop_pdo === null) {
    $message_erreur = "Service temporairement indisponible. Veuillez réessayer plus tard.";
}

// Vérifier si un ID est fourni dans l'URL - Déplacé en haut pour traitement prioritaire
if (isset($_GET['id']) && !empty($_GET['id']) && $shop_pdo !== null) {
    $recherche_effectuee = true;
    $reparation_id = cleanInput($_GET['id']);
    
    try {
        $stmt = $shop_pdo->prepare("
            SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email,
                   s.nom as statut_nom, sc.nom as statut_categorie_nom, sc.couleur as statut_couleur,
                   (SELECT COUNT(*) FROM photos_reparation WHERE reparation_id = r.id) as nb_photos,
                   (SELECT COUNT(*) FROM reparation_logs WHERE reparation_id = r.id) as nb_logs
            FROM reparations r
            JOIN clients c ON r.client_id = c.id
            LEFT JOIN statuts s ON r.statut = s.code
            LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
            WHERE r.id = ?
        ");
        $stmt->execute([$reparation_id]);
        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($resultats)) {
            $message_erreur = "Aucune réparation trouvée avec cet identifiant.";
        }
    } catch (PDOException $e) {
        $message_erreur = "Erreur lors de la recherche: " . $e->getMessage();
    }
}

// Traiter le formulaire de recherche
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $shop_pdo !== null) {
    $recherche_effectuee = true;
    
    if (isset($_POST['reparation_id']) && !empty($_POST['reparation_id'])) {
        // Recherche par ID de réparation
        $reparation_id = cleanInput($_POST['reparation_id']);
        
        try {
            $stmt = $shop_pdo->prepare("
                SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email,
                       s.nom as statut_nom, sc.nom as statut_categorie_nom, sc.couleur as statut_couleur,
                       (SELECT COUNT(*) FROM photos_reparation WHERE reparation_id = r.id) as nb_photos,
                       (SELECT COUNT(*) FROM reparation_logs WHERE reparation_id = r.id) as nb_logs
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                LEFT JOIN statuts s ON r.statut = s.code
                LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
                WHERE r.id = ?
            ");
            $stmt->execute([$reparation_id]);
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($resultats)) {
                $message_erreur = "Aucune réparation trouvée avec cet identifiant.";
            }
        } catch (PDOException $e) {
            $message_erreur = "Erreur lors de la recherche: " . $e->getMessage();
        }
    } elseif (isset($_POST['client_email']) && !empty($_POST['client_email'])) {
        // Recherche par email du client
        $client_email = cleanInput($_POST['client_email']);
        
        try {
            $stmt = $shop_pdo->prepare("
                SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email,
                       s.nom as statut_nom, sc.nom as statut_categorie_nom, sc.couleur as statut_couleur,
                       (SELECT COUNT(*) FROM photos_reparation WHERE reparation_id = r.id) as nb_photos,
                       (SELECT COUNT(*) FROM reparation_logs WHERE reparation_id = r.id) as nb_logs
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                LEFT JOIN statuts s ON r.statut = s.code
                LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
                WHERE c.email = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$client_email]);
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($resultats)) {
                $message_erreur = "Aucune réparation trouvée pour cet email.";
            }
        } catch (PDOException $e) {
            $message_erreur = "Erreur lors de la recherche: " . $e->getMessage();
        }
    } else {
        $message_erreur = "Veuillez fournir un ID de réparation ou un email.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de Réparation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .search-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .reparation-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            background: white;
        }
        .reparation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .reparation-id {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .statut-badge {
            padding: 5px 15px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .detail-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }
        .detail-value {
            margin-top: 5px;
            font-size: 16px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Suivi de Réparation</h1>
        
        <div class="search-form">
            <form method="POST">
                <div class="form-group">
                    <label for="reparation_id">ID de Réparation:</label>
                    <input type="text" id="reparation_id" name="reparation_id" value="<?php echo htmlspecialchars($reparation_id); ?>" placeholder="Entrez l'ID de la réparation">
                </div>
                
                <div style="text-align: center; margin: 15px 0; color: #666;">
                    <strong>OU</strong>
                </div>
                
                <div class="form-group">
                    <label for="client_email">Email du Client:</label>
                    <input type="email" id="client_email" name="client_email" value="<?php echo htmlspecialchars($client_email); ?>" placeholder="Entrez votre email">
                </div>
                
                <button type="submit">🔍 Rechercher</button>
            </form>
        </div>

        <?php if (!empty($message_erreur)): ?>
            <div class="error">
                ❌ <?php echo htmlspecialchars($message_erreur); ?>
            </div>
        <?php endif; ?>

        <?php if ($recherche_effectuee && !empty($resultats)): ?>
            <div class="success">
                ✅ <?php echo count($resultats); ?> réparation(s) trouvée(s)
            </div>

            <?php foreach ($resultats as $reparation): ?>
                <div class="reparation-card">
                    <div class="reparation-header">
                        <div class="reparation-id">Réparation #<?php echo htmlspecialchars($reparation['id']); ?></div>
                        <div class="statut-badge" style="background-color: <?php echo htmlspecialchars($reparation['statut_couleur'] ?? '#6c757d'); ?>">
                            <?php echo htmlspecialchars($reparation['statut_nom'] ?? $reparation['statut']); ?>
                        </div>
                    </div>
                    
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Client</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($reparation['client_prenom'] . ' ' . $reparation['client_nom']); ?>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Téléphone</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($reparation['client_telephone']); ?>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($reparation['client_email']); ?>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Appareil</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($reparation['appareil'] ?? 'Non spécifié'); ?>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Problème</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($reparation['probleme'] ?? 'Non spécifié'); ?>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Date de Création</div>
                            <div class="detail-value">
                                <?php echo date('d/m/Y H:i', strtotime($reparation['created_at'])); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($reparation['date_prevue'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">Date Prévue</div>
                            <div class="detail-value">
                                <?php echo date('d/m/Y', strtotime($reparation['date_prevue'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($reparation['prix_estime'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">Prix Estimé</div>
                            <div class="detail-value">
                                <?php echo number_format($reparation['prix_estime'], 2); ?>€
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-item">
                            <div class="detail-label">Photos</div>
                            <div class="detail-value">
                                <?php echo $reparation['nb_photos']; ?> photo(s)
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Historique</div>
                            <div class="detail-value">
                                <?php echo $reparation['nb_logs']; ?> entrée(s)
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($reparation['description'])): ?>
                    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                        <div class="detail-label">Description</div>
                        <div class="detail-value" style="margin-top: 10px;">
                            <?php echo nl2br(htmlspecialchars($reparation['description'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
