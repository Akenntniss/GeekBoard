<?php
/**
 * API - Tableau de bord des signatures (Global)
 * Retourne la liste des messages nécessitant une signature avec leur état d'avancement.
 */

require_once __DIR__ . '/../../config/session_config.php';
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../../config/database.php';
$shop_pdo = getShopDBConnection();

// Vérification Admin
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit(json_encode(['success' => false, 'message' => 'Non connecté']));
}

$is_admin = false;
$role = $_SESSION['role'] ?? '';
if (empty($role)) {
    // Fallback BDD
    try {
        $stmt = $shop_pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $role = $stmt->fetchColumn();
    } catch(Exception $e){}
}
if (in_array(strtolower($role), ['admin', 'superadmin'])) {
    $is_admin = true;
}

if (!$is_admin) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'Accès refusé']));
}

try {
    // 1. Récupérer tous les messages avec requires_signature = 1
    // On joint avec conversations pour avoir le titre
    $stmt = $shop_pdo->prepare("
        SELECT m.id, m.contenu, m.date_envoi, m.conversation_id, c.titre as conversation_titre
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        WHERE m.requires_signature = 1
        ORDER BY m.date_envoi DESC
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $dashboard_data = [
        'pending' => [],
        'completed' => []
    ];
    
    foreach ($messages as $msg) {
        // Compter les participants (sauf l'émetteur du message si on veut être strict, 
        // mais souvent l'admin est l'émetteur. Disons count participants totaux).
        // Simplification: On récupère tous les IDs des participants.
        $stmtPart = $shop_pdo->prepare("SELECT user_id FROM conversation_participants WHERE conversation_id = ?");
        $stmtPart->execute([$msg['conversation_id']]);
        $all_particip_ids = $stmtPart->fetchAll(PDO::FETCH_COLUMN);
        
        // Exclure l'admin/current user de la liste des signataires attendus ? 
        // Souvent oui : celui qui demande la signature ne signe pas.
        // On va exclure l'émetteur du message du comptage attendu ??
        // Pour l'instant on suppose que TOUS les participants doivent signer.
        
        $total_required = count($all_particip_ids);
        
        // Compter les signatures reçues
        $stmtSig = $shop_pdo->prepare("SELECT COUNT(*) FROM message_signatures WHERE message_id = ?");
        $stmtSig->execute([$msg['id']]);
        $signed_count = $stmtSig->fetchColumn();
        
        $item = [
            'id' => $msg['id'],
            'excerpt' => substr($msg['contenu'], 0, 50) . (strlen($msg['contenu']) > 50 ? '...' : ''),
            'date' => $msg['date_envoi'],
            'conversation' => $msg['conversation_titre'],
            'stats' => [
                'signed' => $signed_count,
                'total' => $total_required
            ]
        ];
        
        if ($signed_count >= $total_required && $total_required > 0) {
            $dashboard_data['completed'][] = $item;
        } else {
            $dashboard_data['pending'][] = $item;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $dashboard_data]);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
