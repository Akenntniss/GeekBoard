<?php
// Callback OAuth pour Google Shopping Content API

require_once 'oauth_config.php';
require_once '../config/database.php';

// Initialiser la session pour récupérer le shop_id
initializeShopSession();

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Échanger le code contre un token
    $token_data = getAccessToken($code, $oauth_config);
    
    if (isset($token_data['access_token'])) {
        // Sauvegarder le token dans la base de données
        $access_token = $token_data['access_token'];
        $refresh_token = $token_data['refresh_token'] ?? null;
        $expires_in = $token_data['expires_in'] ?? 3600;
        $expires_at = date('Y-m-d H:i:s', time() + $expires_in);
        
        try {
            $pdo = getShopDBConnection();
            
            // Vérifier si un token existe déjà pour ce shop
            $stmt = $pdo->prepare("SELECT id FROM oauth_tokens WHERE shop_id = ?");
            $stmt->execute([$_SESSION['shop_id']]);
            
            if ($stmt->fetch()) {
                // Mettre à jour le token existant
                $stmt = $pdo->prepare("UPDATE oauth_tokens SET access_token = ?, refresh_token = ?, expires_at = ?, updated_at = NOW() WHERE shop_id = ?");
                $stmt->execute([$access_token, $refresh_token, $expires_at, $_SESSION['shop_id']]);
            } else {
                // Insérer un nouveau token
                $stmt = $pdo->prepare("INSERT INTO oauth_tokens (shop_id, access_token, refresh_token, expires_at) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_SESSION['shop_id'], $access_token, $refresh_token, $expires_at]);
            }
            
            echo "<h2>✅ OAuth configuré avec succès !</h2>";
            echo "<p><strong>Access Token :</strong> " . substr($access_token, 0, 20) . "...</p>";
            echo "<p><strong>Refresh Token :</strong> " . ($refresh_token ? substr($refresh_token, 0, 20) . "..." : "Non fourni") . "</p>";
            echo "<p><strong>Expires at :</strong> " . $expires_at . "</p>";
            echo "<p><strong>Shop ID :</strong> " . $_SESSION['shop_id'] . "</p>";
            
            // Rediriger vers la page de test
            echo "<br><a href='test_oauth.php'>Tester l'API Google Shopping</a>";
            
        } catch (Exception $e) {
            echo "<h2>❌ Erreur de base de données</h2>";
            echo "<p>Erreur : " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<h2>❌ Erreur OAuth</h2>";
        echo "<pre>" . print_r($token_data, true) . "</pre>";
    }
} else {
    echo "<h2>❌ Code d'autorisation manquant</h2>";
}
?>
