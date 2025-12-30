<?php
/**
 * Debug du système de pointage
 */

require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/database.php';

// Initialiser la session magasin
initializeShopSession();

echo "<h1>🔍 Debug Système de Pointage</h1>";

echo "<h2>📋 Variables de Session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>🏪 Informations Magasin</h2>";
$pdo = getShopDBConnection();
if ($pdo) {
    $db_name = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<p><strong>Base de données connectée :</strong> $db_name</p>";
    
    echo "<h3>👥 Utilisateurs disponibles :</h3>";
    $stmt = $pdo->query("SELECT id, username, full_name, role FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Nom complet</th><th>Rôle</th></tr>";
    foreach ($users as $user) {
        $highlight = ($user['id'] == ($_SESSION['user_id'] ?? null)) ? ' style="background-color: yellow;"' : '';
        echo "<tr$highlight>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>⏰ Derniers pointages :</h3>";
    $stmt = $pdo->query("
        SELECT t.id, t.user_id, u.username, u.full_name, t.clock_in, t.clock_out, t.status 
        FROM time_tracking t 
        LEFT JOIN users u ON t.user_id = u.id 
        ORDER BY t.id DESC 
        LIMIT 5
    ");
    $pointages = $stmt->fetchAll();
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Username</th><th>Nom</th><th>Entrée</th><th>Sortie</th><th>Statut</th></tr>";
    foreach ($pointages as $p) {
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['user_id']}</td>";
        echo "<td>{$p['username']}</td>";
        echo "<td>{$p['full_name']}</td>";
        echo "<td>{$p['clock_in']}</td>";
        echo "<td>{$p['clock_out']}</td>";
        echo "<td>{$p['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Impossible de se connecter à la base de données</p>";
}

echo "<h2>🌐 Informations Serveur</h2>";
echo "<p><strong>Host :</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Non défini') . "</p>";
echo "<p><strong>User Agent :</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Non défini') . "</p>";
echo "<p><strong>IP :</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'Non défini') . "</p>";
?>
