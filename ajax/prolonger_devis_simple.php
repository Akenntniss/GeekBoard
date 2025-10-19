<?php
// Version ultra-simple pour identifier le problème
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

try {
    // Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Includes - utilisation de chemins absolus
    $config_path = realpath(__DIR__ . '/../config/database.php');
    $functions_path = realpath(__DIR__ . '/../includes/functions.php');
    
    if (!$config_path || !$functions_path) {
        throw new Exception('Fichiers non trouvés');
    }
    
    require_once($config_path);
    require_once($functions_path);

    // Auth simple
    $user_id = $_SESSION['shop_id'] ?? $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
    
    if (!$user_id) {
        throw new Exception('Non authentifié');
    }

    // Méthode POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode incorrecte');
    }

    // JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('JSON invalide');
    }

    $devis_id = (int)($data['devis_id'] ?? 0);
    $duree_jours = (int)($data['duree_jours'] ?? 0);

    if ($devis_id <= 0 || $duree_jours <= 0) {
        throw new Exception('Données invalides');
    }

    // Base de données
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Connexion DB échouée');
    }

    // Vérifier le devis
    $stmt = $shop_pdo->prepare("SELECT numero_devis, date_expiration FROM devis WHERE id = ?");
    $stmt->execute([$devis_id]);
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$devis) {
        throw new Exception('Devis non trouvé');
    }

    // Calculer nouvelle date
    $nouvelle_date = new DateTime();
    $nouvelle_date->add(new DateInterval('P' . $duree_jours . 'D'));

    // Mettre à jour
    $update_stmt = $shop_pdo->prepare("UPDATE devis SET date_expiration = ?, statut = 'envoye' WHERE id = ?");
    $success = $update_stmt->execute([
        $nouvelle_date->format('Y-m-d H:i:s'),
        $devis_id
    ]);

    if (!$success) {
        throw new Exception('Erreur UPDATE');
    }

    // Succès
    echo json_encode([
        'success' => true,
        'message' => "Devis {$devis['numero_devis']} prolongé de {$duree_jours} jour(s)",
        'nouvelle_expiration' => $nouvelle_date->format('d/m/Y'),
        'devis_id' => $devis_id,
        'sms_envoye' => false
    ]);

} catch (Exception $e) {
    error_log("ERREUR PROLONGER SIMPLE: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
