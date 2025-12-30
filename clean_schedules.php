<?php
// Script de nettoyage des créneaux de planning
// Connexion à la base de données

$host = 'localhost';
$dbname = 'geekboard_mdg';
$user = 'gb_mdg';
$pass = 'Admin123!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Compter avant suppression
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employee_schedules");
    $before = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Nombre de créneaux avant suppression : $before\n";
    
    // Supprimer tous les créneaux
    $deleted = $pdo->exec("DELETE FROM employee_schedules");
    
    echo "✅ Créneaux supprimés : $deleted\n";
    
    // Vérifier après suppression
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employee_schedules");
    $after = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Nombre de créneaux restants : $after\n";
    echo "\n🎉 Nettoyage terminé !\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
