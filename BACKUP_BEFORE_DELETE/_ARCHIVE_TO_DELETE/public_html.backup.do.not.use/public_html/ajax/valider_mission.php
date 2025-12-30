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

if (!isset($input['validation_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$validation_id = (int)$input['validation_id'];
$action = $input['action']; // 'approuver' ou 'rejeter'
$admin_id = $_SESSION["user_id"];

if (!in_array($action, ['approuver', 'rejeter'])) {
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    // Récupérer les informations de la validation
    $stmt = $shop_pdo->prepare("
        SELECT mv.*, 
               m.recompense_euros, m.recompense_points, m.titre as mission_titre,
               um.progres as progression_actuelle, um.mission_id, um.user_id,
               m.objectif_quantite
        FROM mission_validations mv
        LEFT JOIN user_missions um ON mv.user_mission_id = um.id
        LEFT JOIN missions m ON um.mission_id = m.id
        WHERE mv.id = ? AND mv.statut = 'en_attente'
    ");
    $stmt->execute([$validation_id]);
    $validation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$validation) {
        echo json_encode(['success' => false, 'message' => 'Validation non trouvée ou déjà traitée']);
        exit;
    }
    
    $shop_pdo->beginTransaction();
    
    if ($action === 'approuver') {
        // Mettre à jour le statut de la validation
        $stmt = $shop_pdo->prepare("
            UPDATE mission_validations 
            SET statut = 'validee', 
                date_validation = NOW(), 
                validee_par = ?,
                commentaire_admin = 'Validation approuvée par admin'
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $validation_id]);
        
        // Mettre à jour la progression de l'utilisateur dans la mission
        $stmt = $shop_pdo->prepare("
            UPDATE user_missions 
            SET progres = progres + 1
            WHERE id = ?
        ");
        $stmt->execute([$validation['user_mission_id']]);
        
        // Vérifier si l'utilisateur a atteint l'objectif
        $nouvelle_progression = $validation['progression_actuelle'] + 1;
        
        if ($nouvelle_progression >= $validation['objectif_quantite']) {
            // Marquer la mission comme terminée pour cet utilisateur
            $stmt = $shop_pdo->prepare("
                UPDATE user_missions 
                SET statut = 'terminee', 
                    date_completee = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$validation['user_mission_id']]);
        }
        
        $shop_pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Validation approuvée avec succès !',
            'recompense_euros' => $validation['recompense_euros'],
            'recompense_points' => $validation['recompense_points'],
            'nouvelle_progression' => $nouvelle_progression,
            'mission_terminee' => $nouvelle_progression >= $validation['objectif_quantite']
        ]);
        
    } else { // rejeter
        // Mettre à jour le statut de la validation
        $stmt = $shop_pdo->prepare("
            UPDATE mission_validations 
            SET statut = 'refusee', 
                date_validation = NOW(), 
                validee_par = ?,
                commentaire_admin = 'Validation rejetée par admin'
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $validation_id]);
        
        $shop_pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Validation rejetée'
        ]);
    }
    
} catch (Exception $e) {
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollback();
    }
    error_log("Erreur valider_mission: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la validation: ' . $e->getMessage()]);
}
?> 