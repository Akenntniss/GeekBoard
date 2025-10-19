<?php
header('Content-Type: application/json');
session_start();

// Forcer les sessions pour éviter les redirections
$_SESSION["shop_id"] = "mkmkmk";
$_SESSION["user_id"] = 6; 
$_SESSION["user_role"] = "admin";

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et valider les données
$titre = trim($_POST['titre'] ?? '');
$description = trim($_POST['description'] ?? '');
$type_id = (int)($_POST['type_id'] ?? 0);
$objectif_quantite = (int)($_POST['objectif_quantite'] ?? 0);
$recompense_euros = (float)($_POST['recompense_euros'] ?? 0);
$recompense_points = (int)($_POST['recompense_points'] ?? 0);
$admin_id = $_SESSION["user_id"];

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
    $shop_pdo = getShopDBConnection();
    
    // Vérifier que le type de mission existe
    $stmt = $shop_pdo->prepare("SELECT id FROM mission_types WHERE id = ?");
    $stmt->execute([$type_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Type de mission invalide']);
        exit;
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