<?php
header('Content-Type: application/json');
session_start();

// Forcer les sessions pour éviter les redirections
$_SESSION["shop_id"] = "mkmkmk";
$_SESSION["user_id"] = 6; 
$_SESSION["user_role"] = "admin";

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['mission_id']) || !is_numeric($input['mission_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de mission invalide']);
    exit;
}

$mission_id = (int)$input['mission_id'];
$admin_id = $_SESSION["user_id"];

try {
    $shop_pdo = getShopDBConnection();
    
    // Vérifier que la mission existe et est active
    $stmt = $shop_pdo->prepare("
        SELECT id, titre, statut 
        FROM missions 
        WHERE id = ?
    ");
    $stmt->execute([$mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mission) {
        echo json_encode(['success' => false, 'message' => 'Mission non trouvée']);
        exit;
    }
    
    if ($mission['statut'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Mission déjà désactivée']);
        exit;
    }
    
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    // Désactiver la mission
    $stmt = $shop_pdo->prepare("
        UPDATE missions 
        SET statut = 'inactive', 
            date_fin = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$mission_id]);
    
    // Marquer toutes les missions utilisateur non terminées comme abandonnées
    $stmt = $shop_pdo->prepare("
        UPDATE user_missions 
        SET statut = 'abandonnee'
        WHERE mission_id = ? AND statut NOT IN ('terminee')
    ");
    $stmt->execute([$mission_id]);
    
    // Récupérer le nombre d'utilisateurs affectés
    $stmt = $shop_pdo->prepare("
        SELECT COUNT(*) as nb_users
        FROM user_missions 
        WHERE mission_id = ?
    ");
    $stmt->execute([$mission_id]);
    $affected_users = $stmt->fetchColumn();
    
    // Valider la transaction
    $shop_pdo->commit();
    
    // Log de la désactivation
    error_log("Mission désactivée: ID $mission_id, Titre: " . $mission['titre'] . ", Admin: $admin_id, Utilisateurs affectés: $affected_users");
    
    echo json_encode([
        'success' => true,
        'message' => 'Mission désactivée avec succès !',
        'mission_id' => $mission_id,
        'mission_titre' => $mission['titre'],
        'affected_users' => $affected_users
    ]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollback();
    }
    
    error_log("Erreur deactivate_mission: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la désactivation: ' . $e->getMessage()]);
}
?> 