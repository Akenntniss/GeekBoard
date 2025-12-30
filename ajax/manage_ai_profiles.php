<?php
/**
 * API de gestion des profils IA (CRUD)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser la session magasin
initializeShopSession();

header('Content-Type: application/json');

$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;

if ($user_role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin uniquement']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getShopDBConnection();
    
    switch ($action) {
        case 'create':
            $result = createProfile($pdo, $_POST);
            break;
            
        case 'update':
            $result = updateProfile($pdo, $_POST);
            break;
            
        case 'delete':
            $result = deleteProfile($pdo, $_POST['id']);
            break;
            
        case 'toggle_active':
            $result = toggleActive($pdo, $_POST['id']);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function createProfile($pdo, $data) {
    $sql = "INSERT INTO kpi_ai_profiles (name, description, icon, system_prompt, active, created_by) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['name'],
        $data['description'] ?? '',
        $data['icon'] ?? 'fas fa-user',
        $data['system_prompt'],
        $data['active'] ?? 1,
        $_SESSION['user_id'] ?? $_SESSION['id']
    ]);
    
    return ['id' => $pdo->lastInsertId()];
}

function updateProfile($pdo, $data) {
    $sql = "UPDATE kpi_ai_profiles SET name = ?, description = ?, icon = ?, system_prompt = ?, active = ? WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['name'],
        $data['description'] ?? '',
        $data['icon'] ?? 'fas fa-user',
        $data['system_prompt'],
        $data['active'] ?? 1,
        $data['id']
    ]);
    
    return ['message' => 'Profil modifié'];
}

function deleteProfile($pdo, $id) {
    // Vérifier que ce n'est pas un profil par défaut
    $stmt = $pdo->prepare("SELECT is_default FROM kpi_ai_profiles WHERE id = ?");
    $stmt->execute([$id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($profile && $profile['is_default'] == 1) {
        throw new Exception('Les profils par défaut ne peuvent pas être supprimés');
    }
    
    $stmt = $pdo->prepare("DELETE FROM kpi_ai_profiles WHERE id = ?");
    $stmt->execute([$id]);
    
    return ['message' => 'Profil supprimé'];
}

function toggleActive($pdo, $id) {
    $sql = "UPDATE kpi_ai_profiles SET active = NOT active WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    return ['message' => 'Statut modifié'];
}
