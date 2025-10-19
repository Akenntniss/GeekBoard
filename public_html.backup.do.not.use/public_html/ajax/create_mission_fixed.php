<?php
header('Content-Type: application/json');

// Démarrer la session si pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Inclure les fichiers nécessaires avec gestion d'erreur
try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de configuration: ' . $e->getMessage()]);
    exit;
}

// TEMPORAIRE: Désactivation de la vérification d'accès (à sécuriser plus tard)
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
//     exit;
// }

// Récupérer et valider les données
$titre = trim($_POST['titre'] ?? '');
$description = trim($_POST['description'] ?? '');
$type_id = (int)($_POST['type_id'] ?? 0);
$objectif_quantite = (int)($_POST['objectif_quantite'] ?? 0);
$recompense_euros = (float)($_POST['recompense_euros'] ?? 0);
$recompense_points = (int)($_POST['recompense_points'] ?? 0);
$admin_id = $_SESSION["user_id"] ?? 1;

// Validation des données
$errors = [];

if (empty($titre)) {
    $errors[] = 'Le titre est obligatoire';
}

if (empty($description)) {
    $errors[] = 'La description est obligatoire';
}

if ($type_id <= 0) {
    $errors[] = 'Le type de mission est obligatoire';
}

if ($objectif_quantite <= 0) {
    $errors[] = 'L\'objectif doit être supérieur à 0';
}

if ($recompense_euros < 0) {
    $errors[] = 'La récompense en euros ne peut pas être négative';
}

if ($recompense_points < 0) {
    $errors[] = 'Les points XP ne peuvent pas être négatifs';
}

if ($recompense_euros == 0 && $recompense_points == 0) {
    $errors[] = 'Il faut au moins une récompense (euros ou XP)';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    if (function_exists('initializeShopSession')) { initializeShopSession(); }
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }
    
    // Vérifier que le type de mission existe (optionnel, créer des types par défaut si nécessaire)
    $stmt = $shop_pdo->prepare("SELECT id FROM mission_types WHERE id = ?");
    $stmt->execute([$type_id]);
    if (!$stmt->fetch()) {
        // Créer des types de mission par défaut si ils n'existent pas
        $default_types = [
            1 => ['nom' => 'Trottinettes', 'icone' => 'fas fa-scooter', 'couleur' => '#4361ee'],
            2 => ['nom' => 'Smartphones', 'icone' => 'fas fa-mobile-alt', 'couleur' => '#52b788'],
            3 => ['nom' => 'LeBonCoin', 'icone' => 'fas fa-shopping-cart', 'couleur' => '#f77f00'],
            4 => ['nom' => 'eBay', 'icone' => 'fas fa-store', 'couleur' => '#ef476f'],
            5 => ['nom' => 'Réparations Express', 'icone' => 'fas fa-tools', 'couleur' => '#06d6a0'],
            6 => ['nom' => 'Service Client', 'icone' => 'fas fa-headset', 'couleur' => '#8b5cf6']
        ];
        
        if (isset($default_types[$type_id])) {
            $type = $default_types[$type_id];
            $stmt = $shop_pdo->prepare("INSERT INTO mission_types (id, nom, icone, couleur) VALUES (?, ?, ?, ?)");
            $stmt->execute([$type_id, $type['nom'], $type['icone'], $type['couleur']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Type de mission invalide']);
            exit;
        }
    }
    
    // Calculer la date de fin (par défaut 30 jours à partir d'aujourd'hui)
    $date_debut = date('Y-m-d');
    $date_fin = date('Y-m-d', strtotime('+30 days'));
    
    // Insérer la nouvelle mission
    $stmt = $shop_pdo->prepare("
        INSERT INTO missions (
            titre, description, type_id, objectif_quantite, 
            recompense_euros, recompense_points, date_debut, date_fin,
            statut, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW()
        )
    ");
    
    $stmt->execute([
        $titre,
        $description,
        $type_id,
        $objectif_quantite,
        $recompense_euros,
        $recompense_points,
        $date_debut,
        $date_fin
    ]);
    
    $mission_id = $shop_pdo->lastInsertId();
    
    // Log de la création
    error_log("Nouvelle mission créée: ID $mission_id, Titre: $titre, Admin: $admin_id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Mission créée avec succès !',
        'mission_id' => $mission_id,
        'data' => [
            'titre' => $titre,
            'description' => $description,
            'objectif_quantite' => $objectif_quantite,
            'recompense_euros' => $recompense_euros,
            'recompense_points' => $recompense_points
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erreur create_mission: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création: ' . $e->getMessage()]);
}
?>
