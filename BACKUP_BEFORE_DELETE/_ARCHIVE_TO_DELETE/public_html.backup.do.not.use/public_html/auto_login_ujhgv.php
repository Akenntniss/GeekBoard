<?php
// Connexion automatique pour ujhgv.servo.tools
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';
require_once __DIR__ . '/config/database.php';

// Vérifier que nous sommes sur le bon sous-domaine
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($host !== 'ujhgv.servo.tools') {
    die('Accès non autorisé');
}

try {
    // Forcer la session magasin
    $_SESSION['shop_id'] = 154;
    $_SESSION['shop_name'] = 'Boutique jokdl jodkl';
    
    // Connecter automatiquement l'utilisateur
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->prepare("SELECT id, username, full_name, role FROM users WHERE id = 6");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'] ?? 'employee';
        
        echo "<h1>✅ Connexion automatique réussie</h1>";
        echo "<p><strong>Utilisateur :</strong> " . $user['full_name'] . "</p>";
        echo "<p><strong>Magasin :</strong> " . $_SESSION['shop_name'] . "</p>";
        echo "<p><strong>Shop ID :</strong> " . $_SESSION['shop_id'] . "</p>";
        
        echo '<p><a href="index.php">Aller à l\'accueil</a></p>';
        echo '<p><a href="index.php?page=taches">Aller aux tâches</a></p>';
        
        // Redirection automatique après 3 secondes
        echo '<script>setTimeout(function() { window.location.href = "index.php?page=taches"; }, 3000);</script>';
    } else {
        echo "<h1>❌ Utilisateur non trouvé</h1>";
    }
    
} catch (Exception $e) {
    echo "<h1>❌ Erreur</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

