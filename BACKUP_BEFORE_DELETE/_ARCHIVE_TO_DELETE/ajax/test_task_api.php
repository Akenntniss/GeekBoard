<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test 1: Includes\n";
require_once '../includes/functions.php';
echo "Functions OK\n";

require_once '../includes/config.php';
echo "Config OK\n";

echo "\nTest 2: Database connection\n";
$db_pdo = getShopDBConnection();
if ($db_pdo) {
    echo "DB Connection OK\n";
} else {
    echo "DB Connection FAILED\n";
}

echo "\nTest 3: Insert test\n";
$_POST['titre'] = 'Test Task';
$_POST['description'] = 'Test Description';
$_POST['priorite'] = 'moyenne';

$titre = $_POST['titre'];
$description = $_POST['description'];
$priorite = $_POST['priorite'];
$statut = 'a_faire';

$sql = "INSERT INTO taches (titre, description, priorite, statut, date_creation) 
        VALUES (:titre, :description, :priorite, :statut, NOW())";

$stmt = $db_pdo->prepare($sql);
$result = $stmt->execute([
    ':titre' => $titre,
    ':description' => $description,
    ':priorite' => $priorite,
    ':statut' => $statut
]);

if ($result) {
    $task_id = $db_pdo->lastInsertId();
    echo "Task created with ID: $task_id\n";
} else {
    echo "Insert FAILED\n";
}
