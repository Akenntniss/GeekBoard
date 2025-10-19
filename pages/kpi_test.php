<?php
echo "<div class='alert alert-success'>✅ KPI Test Page - Le routing fonctionne !</div>";
echo "<h2>Test KPI</h2>";
echo "<p>Si vous voyez cette page, le système de routing fonctionne correctement.</p>";
echo "<p>Page: " . ($_GET['page'] ?? 'Non définie') . "</p>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
?>

