<?php
// Test simple d'export CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="test_export.csv"');
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

// BOM UTF-8 pour Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers
fputcsv($output, ['Utilisateur', 'Type', 'Date', 'Statut'], ';');

// Données de test
fputcsv($output, ['John Doe', 'Retard', '2025-09-03 09:15', 'En attente'], ';');
fputcsv($output, ['Jane Smith', 'Absence', '2025-09-02 08:00', 'Approuvé'], ';');

fclose($output);
exit;
?>
