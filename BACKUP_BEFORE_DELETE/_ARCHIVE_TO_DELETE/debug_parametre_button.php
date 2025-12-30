<?php
// Fichier de diagnostic pour le bouton paramètres
session_start();

// Inclure la configuration de base de données
require_once 'config/database.php';

// Initialiser la session magasin
initializeShopSession();

echo "<h1>🔍 Diagnostic du bouton Paramètres</h1>";

// 1. Vérifier la session utilisateur
echo "<h2>1. Session utilisateur</h2>";
echo "<pre>";
echo "user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NON DÉFINI') . "\n";
echo "user_role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'NON DÉFINI') . "\n";
echo "shop_id: " . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'NON DÉFINI') . "\n";
echo "</pre>";

// 2. Vérifier si l'utilisateur est admin
echo "<h2>2. Vérification des droits admin</h2>";
$is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
echo "<p>Est admin: " . ($is_admin ? "✅ OUI" : "❌ NON") . "</p>";

// 3. Vérifier la connexion à la base de données
echo "<h2>3. Connexion base de données</h2>";
try {
    $shop_pdo = getShopDBConnection();
    echo "<p>✅ Connexion réussie à la base de données</p>";
    
    // Vérifier les informations utilisateur
    if (isset($_SESSION['user_id'])) {
        $stmt = $shop_pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p>✅ Utilisateur trouvé: " . htmlspecialchars($user['username']) . " (rôle: " . htmlspecialchars($user['role']) . ")</p>";
        } else {
            echo "<p>❌ Utilisateur non trouvé dans la base de données</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Erreur de connexion: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 4. Test du formulaire
echo "<h2>4. Test du formulaire</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_form'])) {
    echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ Formulaire soumis avec succès !";
    echo "<br>Données reçues: " . print_r($_POST, true);
    echo "</div>";
}

?>

<form method="POST" action="" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;">
    <input type="hidden" name="test_form" value="1">
    
    <h3>Formulaire de test</h3>
    
    <div style="margin: 10px 0;">
        <label for="test_input">Champ de test:</label><br>
        <input type="text" id="test_input" name="test_input" value="Test" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
    
    <div style="margin: 20px 0;">
        <button type="submit" class="btn btn-primary" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
            <i class="fas fa-save"></i> Tester le bouton
        </button>
    </div>
</form>

<script>
console.log('🔍 Diagnostic JavaScript');
console.log('user_role:', '<?php echo isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'undefined'; ?>');
console.log('is_admin:', <?php echo $is_admin ? 'true' : 'false'; ?>);

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM chargé');
    
    // Vérifier si le bouton existe
    const testButton = document.querySelector('button[type="submit"]');
    if (testButton) {
        console.log('✅ Bouton trouvé:', testButton);
        
        testButton.addEventListener('click', function(e) {
            console.log('🖱️ Clic sur le bouton détecté');
        });
    } else {
        console.log('❌ Bouton non trouvé');
    }
    
    // Vérifier les formulaires
    const forms = document.querySelectorAll('form');
    console.log('📋 Nombre de formulaires trouvés:', forms.length);
    
    forms.forEach((form, index) => {
        console.log('Formulaire', index, ':', form);
        
        form.addEventListener('submit', function(e) {
            console.log('📤 Soumission du formulaire', index);
        });
    });
});
</script>

<style>
.btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    user-select: none;
    border: 1px solid transparent;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: 0.25rem;
    transition: all 0.15s ease-in-out;
    cursor: pointer;
}

.btn-primary {
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
}

.btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}
</style>
