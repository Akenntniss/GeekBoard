<?php
// Test de connexion forcée pour diagnostiquer
session_start();

echo "<h1>🔧 Test Connexion Forcée</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Forcer la session utilisateur
$_SESSION['shop_id'] = 'mkmkmk';
$_SESSION['user_id'] = 6;
$_SESSION['full_name'] = 'Administrateur Mkmkmk';
$_SESSION['role'] = 'admin';
$_SERVER['HTTP_HOST'] = 'mkmkmk.mdgeek.top';

echo "<h2>🔄 Session forcée :</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>🔗 Test de redirection :</h2>";
echo "<p><strong>Cliquez sur ce lien pour tester la page missions avec session forcée :</strong></p>";
echo "<p><a href='index.php?page=mes_missions' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📋 Accéder aux Missions</a></p>";

echo "<hr>";
echo "<h2>🧪 Tests disponibles :</h2>";
echo "<ul>";
echo "<li><a href='debug_sql_missions.php'>🔍 Diagnostic SQL</a></li>";
echo "<li><a href='debug_session_auth.php'>🔐 Debug Session</a></li>";
echo "<li><a href='pages/login_auto.php?shop_id=mkmkmk'>🔑 Login Auto</a></li>";
echo "</ul>";

echo "<hr><p><em>Session forcée créée - " . date('Y-m-d H:i:s') . "</em></p>";
?>
