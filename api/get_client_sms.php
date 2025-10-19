<?php
/**
 * API pour récupérer les SMS envoyés à un client spécifique
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Initialiser la session pour le système multi-magasin
initializeShopSession();

try {
    // Vérifier que l'ID du client est fourni
    if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID du client requis'
        ]);
        exit;
    }

    $client_id = intval($_GET['client_id']);
    $shop_pdo = getShopDBConnection();

    // Récupérer les informations du client
    $client_stmt = $shop_pdo->prepare("SELECT nom, prenom, telephone FROM clients WHERE id = :client_id");
    $client_stmt->execute(['client_id' => $client_id]);
    $client = $client_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Client non trouvé'
        ]);
        exit;
    }

    // Récupérer les SMS envoyés à ce numéro de téléphone
    $sms_query = "
        SELECT 
            id,
            recipient,
            message,
            status,
            reparation_id,
            date_envoi,
            response
        FROM sms_logs 
        WHERE recipient = :telephone 
        ORDER BY date_envoi DESC
    ";

    $sms_stmt = $shop_pdo->prepare($sms_query);
    $sms_stmt->execute(['telephone' => $client['telephone']]);
    $sms_list = $sms_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les données pour l'affichage
    $formatted_sms = [];
    foreach ($sms_list as $sms) {
        $formatted_sms[] = [
            'id' => $sms['id'],
            'recipient' => $sms['recipient'],
            'message' => $sms['message'],
            'status' => $sms['status'],
            'status_text' => getSmsStatusText($sms['status']),
            'reparation_id' => $sms['reparation_id'],
            'date_envoi' => $sms['date_envoi'],
            'date_formatted' => date('d/m/Y H:i:s', strtotime($sms['date_envoi'])),
            'response' => $sms['response']
        ];
    }

    echo json_encode([
        'success' => true,
        'client' => [
            'nom' => $client['nom'],
            'prenom' => $client['prenom'],
            'telephone' => $client['telephone']
        ],
        'sms' => $formatted_sms,
        'total' => count($formatted_sms)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur : ' . $e->getMessage()
    ]);
}

/**
 * Convertit le statut numérique en texte lisible
 */
function getSmsStatusText($status) {
    switch ($status) {
        case 0:
            return 'En attente';
        case 1:
            return 'Envoyé';
        case 2:
            return 'Échec';
        case 200:
            return 'Livré';
        default:
            return 'Inconnu';
    }
}
?>
