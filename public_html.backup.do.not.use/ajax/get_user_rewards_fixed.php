<?php
header('Content-Type: application/json');

// Démarrer la session si pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
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

// Vérifier l'authentification
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }
    
    // Récupérer les utilisateurs avec leurs récompenses
    $stmt = $shop_pdo->prepare("
        SELECT 
            u.id,
            u.full_name,
            u.username,
            COALESCE(SUM(CASE WHEN mv.statut = 'validee' THEN m.recompense_euros ELSE 0 END), 0) as total_euros,
            COALESCE(SUM(CASE WHEN mv.statut = 'validee' THEN m.recompense_points ELSE 0 END), 0) as total_points,
            COUNT(CASE WHEN mv.statut = 'validee' THEN 1 END) as missions_completees
        FROM users u
        LEFT JOIN user_missions um ON u.id = um.user_id
        LEFT JOIN missions m ON um.mission_id = m.id
        LEFT JOIN mission_validations mv ON um.id = mv.user_mission_id
        WHERE u.role = 'employe'
        GROUP BY u.id, u.full_name, u.username
        ORDER BY total_euros DESC, total_points DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Générer le HTML
    $html = '';
    if (empty($users)) {
        $html = '<div style="text-align: center; padding: 2rem; color: #6c757d;">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h4>Aucun employé trouvé</h4>
                    <p>Aucune donnée de récompense disponible</p>
                 </div>';
    } else {
        foreach ($users as $user) {
            $html .= '<div style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <div>
                                <h5 style="margin: 0; color: #343a40;">' . htmlspecialchars($user['full_name']) . '</h5>
                                <small style="color: #6c757d;">@' . htmlspecialchars($user['username']) . '</small>
                            </div>
                            <div style="text-align: right;">
                                <div style="color: #52b788; font-weight: 600; font-size: 1.1rem;">' . number_format($user['total_euros'], 2) . '€</div>
                                <div style="color: #4361ee; font-weight: 600;">' . $user['total_points'] . ' XP</div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 1rem; font-size: 0.9rem; color: #6c757d;">
                            <span><i class="fas fa-trophy"></i> ' . $user['missions_completees'] . ' missions</span>
                        </div>
                      </div>';
        }
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'count' => count($users)
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_user_rewards: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors du chargement des données: ' . $e->getMessage()
    ]);
}
?>
