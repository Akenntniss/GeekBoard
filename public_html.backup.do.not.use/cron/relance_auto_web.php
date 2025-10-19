<?php
/**
 * Point d'entrée web pour les relances automatiques
 * Alternative au cron job si le cron système ne fonctionne pas
 */

// Vérification de sécurité
$secret_key = 'GeekBoard2024RelanceAuto!';
$provided_key = $_GET['key'] ?? '';

if ($provided_key !== $secret_key) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Accès refusé - Clé de sécurité invalide'
    ]);
    exit();
}

// Headers pour éviter la mise en cache
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Capturer la sortie du script
ob_start();

try {
    // Inclure et exécuter le script de relance
    include __DIR__ . '/../scripts/relance_automatique.php';
    
    $output = ob_get_contents();
    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Script de relance automatique exécuté avec succès',
        'output' => $output,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'exécution : ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
