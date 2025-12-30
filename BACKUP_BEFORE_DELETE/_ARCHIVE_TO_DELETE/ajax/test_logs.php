<?php
// Test simple pour vérifier les logs
error_log("TEST LOG - " . date('Y-m-d H:i:s') . " - Fichier de test créé");

// Afficher la configuration des logs
echo "Configuration des logs PHP:\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "error_log: " . ini_get('error_log') . "\n";

// Tester différents types de logs
error_log("TEST LOG - Message de test simple");
error_log("TEST LOG - Données JSON: " . json_encode(['test' => 'value', 'number' => 123]));

echo "Test terminé. Vérifiez les logs.\n";
?>