<?php
// Version de debug pour tester admin_missions
session_start();

// Forcer les variables de session pour admin (temporaire pour debug)
$_SESSION["shop_id"] = "mkmkmk";
$_SESSION["user_id"] = 6; 
$_SESSION["user_role"] = "admin";
$_SESSION["role"] = "admin";  
$_SESSION["full_name"] = "Administrateur Test";
$_SESSION["username"] = "admin";
$_SESSION["is_logged_in"] = true;

echo "Session debug:<br>";
echo "user_role: " . ($_SESSION['user_role'] ?? 'non défini') . "<br>";
echo "shop_id: " . ($_SESSION['shop_id'] ?? 'non défini') . "<br>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'non défini') . "<br>";
echo "<br>";

// Inclure la page admin_missions
if (file_exists('/var/www/mdgeek.top/pages/admin_missions.php')) {
    echo "Fichier admin_missions.php trouvé. Inclusion...<br><br>";
    
    // Simuler l'environnement nécessaire
    define('BASE_PATH', '/var/www/mdgeek.top');
    require_once '/var/www/mdgeek.top/includes/config.php';
    require_once '/var/www/mdgeek.top/includes/functions.php';
    
    include '/var/www/mdgeek.top/pages/admin_missions.php';
} else {
    echo "ERREUR: Fichier admin_missions.php non trouvé !";
}
?>
