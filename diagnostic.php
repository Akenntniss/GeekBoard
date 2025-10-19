<?php
session_start();
require_once __DIR__ . "/config/subdomain_config.php";
echo "<h1>Diagnostic testtest.servo.tools</h1>";
echo "<p>Host: " . ($_SERVER["HTTP_HOST"] ?? "non défini") . "</p>";
echo "<p>Shop ID en session: " . ($_SESSION["shop_id"] ?? "non défini") . "</p>";
echo "<p>Résultat detectShopFromSubdomain(): " . (detectShopFromSubdomain() ?? "NULL") . "</p>";
?>
