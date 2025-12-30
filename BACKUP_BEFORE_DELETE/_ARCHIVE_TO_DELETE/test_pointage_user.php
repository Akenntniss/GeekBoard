<?php
/**
 * Test de l'API de pointage avec l'utilisateur test1
 */

session_start();

// Forcer la session pour test1 sur geekboard_mdg
$_SESSION['shop_id'] = 162;  // ID du magasin mdg
$_SESSION['user_id'] = 8;    // ID de l'utilisateur test1
$_SESSION['user_role'] = 'technicien';
$_SESSION['user_name'] = 'test123';
$_SESSION['user_username'] = 'test1';

echo "<h1>🧪 Test API Pointage - Utilisateur test1</h1>";
echo "<p><strong>Session configurée :</strong></p>";
echo "<ul>";
echo "<li>Shop ID: " . $_SESSION['shop_id'] . "</li>";
echo "<li>User ID: " . $_SESSION['user_id'] . "</li>";
echo "<li>User Role: " . $_SESSION['user_role'] . "</li>";
echo "<li>User Name: " . $_SESSION['user_name'] . "</li>";
echo "<li>Username: " . $_SESSION['user_username'] . "</li>";
echo "</ul>";

// Inclure la configuration de base de données
require_once __DIR__ . '/config/database.php';

try {
    // Initialiser la session magasin
    initializeShopSession();
    
    // Obtenir la connexion
    $pdo = getShopDBConnection();
    
    echo "<p><strong>✅ Connexion à la base de données réussie</strong></p>";
    
    // Vérifier la base de données actuelle
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $db = $stmt->fetch();
    echo "<p><strong>Base de données actuelle :</strong> " . $db['current_db'] . "</p>";
    
    // Vérifier l'utilisateur
    $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p><strong>✅ Utilisateur trouvé :</strong></p>";
        echo "<ul>";
        echo "<li>ID: " . $user['id'] . "</li>";
        echo "<li>Username: " . $user['username'] . "</li>";
        echo "<li>Full Name: " . $user['full_name'] . "</li>";
        echo "<li>Role: " . $user['role'] . "</li>";
        echo "</ul>";
    } else {
        echo "<p><strong>❌ Utilisateur non trouvé dans la base</strong></p>";
    }
    
    // Vérifier les pointages existants
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM time_tracking WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $count = $stmt->fetch();
    
    echo "<p><strong>Pointages existants pour cet utilisateur :</strong> " . $count['total'] . "</p>";
    
    // Vérifier s'il y a un pointage actif
    $stmt = $pdo->prepare("SELECT id, clock_in, status FROM time_tracking WHERE user_id = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $active = $stmt->fetch();
    
    if ($active) {
        echo "<p><strong>⚠️ Pointage actif trouvé :</strong></p>";
        echo "<ul>";
        echo "<li>ID: " . $active['id'] . "</li>";
        echo "<li>Clock In: " . $active['clock_in'] . "</li>";
        echo "<li>Status: " . $active['status'] . "</li>";
        echo "</ul>";
    } else {
        echo "<p><strong>✅ Aucun pointage actif - Prêt pour pointer</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Erreur :</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🔗 Actions de Test</h2>";
echo "<p><a href='time_tracking_api.php?action=get_status' target='_blank'>Tester l'API - Get Status</a></p>";
echo "<p><a href='ajax/get_timetracking_status.php' target='_blank'>Tester l'AJAX - Get Status</a></p>";
?>
