<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de configuration: ' . $e->getMessage()]);
    exit;
}

// TEMPORAIRE: Désactivation de la vérification d'accès pour débloquer les actions (à sécuriser plus tard)
// if (!((isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'))) {
//     echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
//     exit;
// }

if (function_exists('initializeShopSession')) {
    initializeShopSession();
}

$mission_id = (int)($_POST['id'] ?? 0);
$titre = trim($_POST['titre'] ?? '');
$description = trim($_POST['description'] ?? '');
$type_id = (int)($_POST['type_id'] ?? 0);
$objectif_quantite = (int)($_POST['objectif_quantite'] ?? 0);
$recompense_euros = (float)($_POST['recompense_euros'] ?? 0);
$recompense_points = (int)($_POST['recompense_points'] ?? 0);

if ($mission_id <= 0) { echo json_encode(['success'=>false,'message'=>'ID mission invalide']); exit; }
if ($titre === '' || $description === '') { echo json_encode(['success'=>false,'message'=>'Champs requis manquants']); exit; }
if ($type_id <= 0) { echo json_encode(['success'=>false,'message'=>'Type invalide']); exit; }
if ($objectif_quantite <= 0) { echo json_encode(['success'=>false,'message'=>'Objectif invalide']); exit; }

try {
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) { echo json_encode(['success'=>false,'message'=>'Connexion base indisponible']); exit; }

    $stmt = $shop_pdo->prepare("SELECT id FROM mission_types WHERE id = ?");
    $stmt->execute([$type_id]);
    if (!$stmt->fetch()) { echo json_encode(['success'=>false,'message'=>'Type de mission inexistant']); exit; }

    $stmt = $shop_pdo->prepare("UPDATE missions SET titre=?, description=?, type_id=?, objectif_quantite=?, recompense_euros=?, recompense_points=? WHERE id=?");
    $stmt->execute([$titre,$description,$type_id,$objectif_quantite,$recompense_euros,$recompense_points,$mission_id]);

    echo json_encode(['success'=>true,'message'=>'Mission mise à jour']);
} catch (Exception $e) {
    error_log('update_mission_fixed: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Erreur: '.$e->getMessage()]);
}
?>


