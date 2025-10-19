<?php
// Correctif pour le bouton des paramètres d'entreprise
// Ce fichier corrige les problèmes potentiels avec le bouton "Enregistrer les paramètres d'entreprise"

session_start();

// Inclure la configuration de base de données
require_once 'config/database.php';
initializeShopSession();

// Vérifications préliminaires
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

if (!$user_id) {
    die("❌ Erreur: Utilisateur non connecté");
}

try {
    $shop_pdo = getShopDBConnection();
    
    // 1. Vérifier le rôle de l'utilisateur dans la base de données
    $stmt = $shop_pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("❌ Erreur: Utilisateur non trouvé dans la base de données");
    }
    
    // 2. Mettre à jour la session si nécessaire
    if ($user['role'] !== $user_role) {
        $_SESSION['user_role'] = $user['role'];
        echo "🔄 Rôle utilisateur mis à jour: " . $user['role'] . "<br>";
    }
    
    // 3. Vérifier si l'utilisateur est admin
    $is_admin = ($user['role'] === 'admin');
    
    echo "<h2>🔍 Diagnostic du problème</h2>";
    echo "<p><strong>Utilisateur:</strong> " . htmlspecialchars($user['username']) . "</p>";
    echo "<p><strong>Rôle:</strong> " . htmlspecialchars($user['role']) . "</p>";
    echo "<p><strong>Est admin:</strong> " . ($is_admin ? "✅ OUI" : "❌ NON") . "</p>";
    
    if (!$is_admin) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>⚠️ Problème identifié</h3>";
        echo "<p>Votre compte n'a pas les droits administrateur nécessaires pour accéder aux paramètres d'entreprise.</p>";
        echo "<p>Pour résoudre ce problème, votre compte doit être mis à niveau au rôle 'admin'.</p>";
        echo "</div>";
        
        // Option pour mettre à niveau automatiquement (à des fins de test)
        echo "<form method='POST' style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>🔧 Solution rapide (Test uniquement)</h4>";
        echo "<p>Voulez-vous mettre à niveau votre compte au rôle administrateur ?</p>";
        echo "<input type='hidden' name='upgrade_to_admin' value='1'>";
        echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
        echo "Mettre à niveau vers Admin";
        echo "</button>";
        echo "</form>";
    } else {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>✅ Droits administrateur confirmés</h3>";
        echo "<p>Votre compte a bien les droits administrateur. Le bouton devrait être visible et fonctionnel.</p>";
        echo "</div>";
        
        // Vérifier s'il y a des problèmes avec les paramètres d'entreprise
        echo "<h3>🔍 Vérification des paramètres d'entreprise</h3>";
        
        // Récupérer les paramètres existants
        $stmt = $shop_pdo->query("SELECT * FROM parametres WHERE cle LIKE 'company_%'");
        $company_params = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Paramètres d'entreprise trouvés: " . count($company_params) . "</p>";
        
        if (empty($company_params)) {
            echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p>⚠️ Aucun paramètre d'entreprise trouvé. Initialisation...</p>";
            echo "</div>";
            
            // Initialiser les paramètres par défaut
            $default_params = [
                'company_name' => '',
                'company_phone' => '',
                'company_email' => '',
                'company_address' => '',
                'company_logo' => ''
            ];
            
            $descriptions = [
                'company_name' => 'Nom de l\'entreprise',
                'company_phone' => 'Numéro de téléphone de l\'entreprise',
                'company_email' => 'Adresse email de l\'entreprise',
                'company_address' => 'Adresse de l\'entreprise',
                'company_logo' => 'Chemin vers le logo de l\'entreprise'
            ];
            
            foreach ($default_params as $cle => $valeur) {
                $stmt = $shop_pdo->prepare("INSERT IGNORE INTO parametres (cle, valeur, description) VALUES (?, ?, ?)");
                $stmt->execute([$cle, $valeur, $descriptions[$cle]]);
            }
            
            echo "<p>✅ Paramètres d'entreprise initialisés</p>";
        }
    }
    
    // Traitement de la mise à niveau vers admin
    if (isset($_POST['upgrade_to_admin'])) {
        $stmt = $shop_pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $_SESSION['user_role'] = 'admin';
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>✅ Mise à niveau réussie</h3>";
            echo "<p>Votre compte a été mis à niveau vers le rôle administrateur.</p>";
            echo "<p><a href='index.php?page=parametre' style='color: #155724; font-weight: bold;'>Retourner aux paramètres</a></p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>❌ Erreur lors de la mise à niveau</h3>";
            echo "<p>Impossible de mettre à niveau votre compte. Contactez un administrateur.</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Erreur de base de données</h3>";
    echo "<p>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔧 Actions recommandées</h3>";
echo "<ul>";
echo "<li><a href='index.php?page=parametre'>Retourner à la page des paramètres</a></li>";
echo "<li><a href='debug_parametre_button.php'>Exécuter le diagnostic complet</a></li>";
echo "<li><a href='index.php'>Retourner à l'accueil</a></li>";
echo "</ul>";
?>
