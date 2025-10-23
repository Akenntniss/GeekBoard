<?php
// Démarrer la session au début
session_start();

// Désactiver l'affichage des erreurs pour éviter de casser le JSON
ini_set('display_errors', 0);
error_reporting(0);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    $functions_path = realpath(__DIR__ . '/../includes/functions.php');
    
    if (!file_exists($config_path) || !file_exists($functions_path)) {
        throw new Exception('Fichiers de configuration introuvables.');
    }

    // Inclure les fichiers nécessaires
    require_once $config_path;
    require_once $functions_path;

    // Initialiser la détection de sous-domaine comme dans les autres pages
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $subdomain = '';
    
    // Extraire le sous-domaine - support pour mdgeek.top et servo.tools
    if (strpos($host, 'mdgeek.top') !== false || strpos($host, 'servo.tools') !== false) {
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $subdomain = $parts[0];
        }
    }
    
    // DEBUG: Log des informations de débogage
    error_log("DEBUG get_statuts_by_category.php: Host=" . $host . ", Subdomain=" . $subdomain);
    error_log("DEBUG get_statuts_by_category.php: Session shop_id avant=" . ($_SESSION['shop_id'] ?? 'non défini'));
    
    // Si on a un sous-domaine, initialiser la session shop
    if (!empty($subdomain) && $subdomain !== 'www') {
        // Obtenir la connexion principale
        $main_pdo = getMainDBConnection();
        if ($main_pdo) {
            error_log("DEBUG get_statuts_by_category.php: Connexion principale OK");
            $stmt = $main_pdo->prepare("SELECT id, name FROM shops WHERE subdomain = ? AND active = 1");
            $stmt->execute([$subdomain]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($shop) {
                $_SESSION['shop_id'] = $shop['id'];
                $_SESSION['shop_name'] = $shop['name'];
                error_log("DEBUG get_statuts_by_category.php: Shop trouvé ID=" . $shop['id']);
            } else {
                error_log("DEBUG get_statuts_by_category.php: Aucun shop trouvé pour subdomain=" . $subdomain);
            }
        } else {
            error_log("DEBUG get_statuts_by_category.php: Connexion principale ERREUR");
        }
    }
    
    error_log("DEBUG get_statuts_by_category.php: Session shop_id après=" . ($_SESSION['shop_id'] ?? 'non défini'));

    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin. Debug: host=' . $host . ', subdomain=' . $subdomain . ', session_shop_id=' . ($_SESSION['shop_id'] ?? 'non défini'));
    }

    // Récupérer l'ID de catégorie depuis les paramètres GET
    $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    
    if ($category_id <= 0) {
        throw new Exception('ID de catégorie invalide');
    }

    // Récupérer les statuts de cette catégorie
    $stmt = $shop_pdo->prepare("
        SELECT id, nom, code, est_actif, ordre
        FROM statuts
        WHERE categorie_id = ? AND est_actif = 1
        ORDER BY ordre ASC
    ");
    $stmt->execute([$category_id]);
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($statuts)) {
        throw new Exception('Aucun statut trouvé pour cette catégorie');
    }
    
    // Récupérer les informations de la catégorie
    $stmt = $shop_pdo->prepare("
        SELECT nom, code, couleur
        FROM statut_categories
        WHERE id = ?
    ");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        throw new Exception('Catégorie introuvable');
    }
    
    // Renvoyer les résultats
    echo json_encode([
        'success' => true,
        'category' => $category,
        'statuts' => $statuts
    ]);

} catch (Exception $e) {
    // Logger l'erreur sans l'afficher
    error_log("Erreur dans get_statuts_by_category.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'host' => $_SERVER['HTTP_HOST'] ?? 'non défini',
            'subdomain' => $subdomain ?? 'non défini',
            'session_shop_id' => $_SESSION['shop_id'] ?? 'non défini',
            'session_shop_name' => $_SESSION['shop_name'] ?? 'non défini'
        ]
    ]);
} catch (PDOException $e) {
    // Logger l'erreur de base de données
    error_log("Erreur PDO dans get_statuts_by_category.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données'
    ]);
}
?> 