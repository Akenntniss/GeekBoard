<?php
/**
 * Login de debug pour diagnostiquer le problème de session
 */

require_once __DIR__ . '/config/database.php';

// Démarrer la session
session_start();

// Initialiser la session magasin
initializeShopSession();

echo "<h1>🔍 Debug Login</h1>";

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    echo "<h2>📋 Tentative de Connexion</h2>";
    echo "<p><strong>Username :</strong> " . htmlspecialchars($username) . "</p>";
    echo "<p><strong>Password :</strong> " . (strlen($password) > 0 ? str_repeat('*', strlen($password)) : 'vide') . "</p>";
    
    if ($username && $password) {
        try {
            // Connexion à la base du magasin
            $pdo = getShopDBConnection();
            $db_name = $pdo->query("SELECT DATABASE()")->fetchColumn();
            echo "<p><strong>Base connectée :</strong> $db_name</p>";
            
            // Chercher l'utilisateur
            $stmt = $pdo->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            echo "<h3>👤 Utilisateur trouvé :</h3>";
            if ($user) {
                echo "<pre>";
                print_r([
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role'],
                    'password_hash' => substr($user['password'], 0, 10) . '...'
                ]);
                echo "</pre>";
                
                // Vérifier le mot de passe
                $password_ok = false;
                if (password_get_info($user['password'])['algo'] !== null) {
                    // Bcrypt
                    $password_ok = password_verify($password, $user['password']);
                    echo "<p><strong>Vérification bcrypt :</strong> " . ($password_ok ? '✅ OK' : '❌ ÉCHEC') . "</p>";
                } else {
                    // MD5
                    $password_ok = ($user['password'] === md5($password));
                    echo "<p><strong>Vérification MD5 :</strong> " . ($password_ok ? '✅ OK' : '❌ ÉCHEC') . "</p>";
                    echo "<p><strong>MD5 attendu :</strong> " . md5($password) . "</p>";
                    echo "<p><strong>MD5 en base :</strong> " . $user['password'] . "</p>";
                }
                
                if ($password_ok) {
                    // Définir les sessions
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    echo "<h3>✅ Connexion Réussie !</h3>";
                    echo "<p><strong>Sessions définies :</strong></p>";
                    echo "<pre>";
                    print_r($_SESSION);
                    echo "</pre>";
                    
                    echo "<p><a href='test_api_session.php'>🧪 Tester l'API maintenant</a></p>";
                    echo "<p><a href='debug_pointage.php'>🔍 Voir le debug pointage</a></p>";
                } else {
                    echo "<h3>❌ Mot de passe incorrect</h3>";
                }
            } else {
                echo "<p>❌ Utilisateur non trouvé</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ Veuillez remplir tous les champs</p>";
    }
    
    echo "<hr>";
}

echo "<h2>📋 Session Actuelle</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>🔑 Formulaire de Connexion</h2>";
?>

<form method="POST" style="max-width: 400px;">
    <div style="margin: 10px 0;">
        <label>Username :</label><br>
        <input type="text" name="username" value="test1" style="width: 100%; padding: 5px;">
    </div>
    <div style="margin: 10px 0;">
        <label>Password :</label><br>
        <input type="password" name="password" style="width: 100%; padding: 5px;">
    </div>
    <div style="margin: 10px 0;">
        <button type="submit" style="padding: 10px 20px; background: #007cba; color: white; border: none;">Se Connecter</button>
    </div>
</form>

<h2>👥 Utilisateurs Disponibles</h2>
<?php
try {
    $pdo = getShopDBConnection();
    $stmt = $pdo->query("SELECT id, username, full_name, role FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Nom</th><th>Rôle</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p>Erreur : " . $e->getMessage() . "</p>";
}
?>
