<?php
/**
 * API pour récupérer la liste des profils IA actifs
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser la session magasin
initializeShopSession();

header('Content-Type: application/json');

// Vérification authentification (TEMPORAIREMENT DÉSACTIVÉ POUR DEBUG)
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1;
/*
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié', 'data' => []]);
    exit;
}
*/

try {
    $pdo = getShopDBConnection();
    
    // Si un ID est fourni, récupérer un profil spécifique
    if (isset($_GET['id'])) {
        $profileId = intval($_GET['id']);
        $sql = "SELECT id, name, description, icon, system_prompt FROM kpi_ai_profiles WHERE id = ? AND active = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$profileId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            echo json_encode(['success' => true, 'data' => $profile]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Profil non trouvé']);
        }
    } else {
        // Sinon, récupérer tous les profils actifs
        $sql = "SELECT id, name, description, icon FROM kpi_ai_profiles WHERE active = 1 ORDER BY is_default DESC, name ASC";
        $stmt = $pdo->query($sql);
        $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $profiles]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
