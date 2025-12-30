<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

try {
	$main = getMainDBConnection();
	$host = $_SERVER['HTTP_HOST'] ?? '';
	$stmt = $main->query("SELECT id, subdomain, db_name FROM shops WHERE active=1");
	$found = null;
	foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
		if ($host === $s['subdomain'].'.servo.tools' || $host === $s['subdomain'].'.mdgeek.top') { $found = $s; break; }
	}
	if ($found) { $_SESSION['shop_id'] = (int)$found['id']; }
} catch (Exception $e) {}

$pdo = null;
try { $pdo = getShopDBConnection(); } catch (Exception $e) { echo "getShopDBConnection EX: ".$e->getMessage(); die; }
if (!$pdo) { echo "pdo null"; die; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 2;

try {
	$sql = "SELECT r.id, r.type_appareil, r.marque, r.modele, r.description_probleme, r.date_reception, r.statut,
				c.prenom AS client_prenom, c.nom AS client_nom, c.telephone AS client_telephone, c.email AS client_email
			FROM reparations r
			LEFT JOIN clients c ON r.client_id = c.id
			WHERE r.id = ?";
	$st = $pdo->prepare($sql);
	$st->execute([$id]);
	$row = $st->fetch(PDO::FETCH_ASSOC);
	var_dump($row);
} catch (Throwable $e) {
	echo "SQL ERR: ".$e->getMessage();
}

try {
	$st = $pdo->prepare("SELECT 1 FROM devis WHERE reparation_id = ? LIMIT 1");
	$st->execute([$id]);
	echo "\nDEVIS OK rows: ".$st->rowCount();
} catch (Throwable $e) {
	echo "\nDEVIS ERR: ".$e->getMessage();
}

try {
	$st = $pdo->prepare("SELECT 1 FROM photos_reparation WHERE reparation_id = ? LIMIT 1");
	$st->execute([$id]);
	echo "\nPHOTOS OK rows: ".$st->rowCount();
} catch (Throwable $e) {
	echo "\nPHOTOS ERR: ".$e->getMessage();
}
