<?php
/**
 * Script AJAX pour créer un paiement SumUp
 * Appelé depuis la page statut_rapide.php
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../classes/SumUpIntegration.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Méthode non autorisée'
    ]);
    exit;
}

try {
    // Récupérer les données POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST; // Fallback pour form-data
    }
    
    // Validation des données requises
    if (!isset($input['reparation_id']) || !isset($input['montant'])) {
        throw new Exception('Données manquantes: reparation_id et montant requis');
    }
    
    $reparationId = (int)$input['reparation_id'];
    $montant = (float)$input['montant'];
    
    if ($reparationId <= 0 || $montant <= 0) {
        throw new Exception('ID réparation et montant doivent être positifs');
    }
    
    // Récupérer les infos de la réparation
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->prepare("
        SELECT r.*, c.nom, c.prenom, c.email, c.telephone
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparationId]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        throw new Exception('Réparation non trouvée');
    }
    
    // Vérifier qu'il n'y a pas déjà un paiement en cours
    $stmt = $shop_pdo->prepare("
        SELECT id FROM paiements_sumup 
        WHERE reparation_id = ? AND statut_paiement IN ('pending', 'paid')
    ");
    $stmt->execute([$reparationId]);
    
    if ($stmt->rowCount() > 0) {
        throw new Exception('Un paiement est déjà en cours ou terminé pour cette réparation');
    }
    
    // Créer l'instance SumUp
    $sumup = new SumUpIntegration();
    
    // Préparer les infos client
    $clientInfo = [
        'id' => $reparation['client_id'],
        'nom' => $reparation['nom'],
        'prenom' => $reparation['prenom'],
        'email' => $reparation['email'],
        'telephone' => $reparation['telephone']
    ];
    
    // Description du paiement
    $description = "Réparation #{$reparationId} - {$reparation['type_appareil']} {$reparation['marque']} {$reparation['modele']}";
    
    // Créer le checkout SumUp
    $checkout = $sumup->createCheckout($montant, $description, $reparationId, $clientInfo);
    
    if (!$checkout || !isset($checkout['id'])) {
        throw new Exception('Erreur lors de la création du checkout SumUp');
    }
    
    // Enregistrer le paiement en base
    $stmt = $shop_pdo->prepare("
        INSERT INTO paiements_sumup (
            reparation_id, 
            checkout_id, 
            checkout_reference, 
            montant, 
            currency,
            statut_paiement, 
            client_info,
            description
        ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
    ");
    
    $stmt->execute([
        $reparationId,
        $checkout['id'],
        $checkout['checkout_reference'] ?? null,
        $montant,
        $checkout['currency'] ?? 'EUR',
        json_encode($clientInfo),
        $description
    ]);
    
    // Mettre à jour la réparation
    $stmt = $shop_pdo->prepare("
        UPDATE reparations 
        SET sumup_checkout_id = ?, sumup_statut = 'pending', date_modification = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$checkout['id'], $reparationId]);
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Paiement SumUp créé avec succès',
        'data' => [
            'checkout_id' => $checkout['id'],
            'checkout_reference' => $checkout['checkout_reference'] ?? '',
            'amount' => $montant,
            'currency' => $checkout['currency'] ?? 'EUR',
            'status' => $checkout['status'] ?? 'PENDING',
            'description' => $description,
            'client_nom' => $reparation['prenom'] . ' ' . $reparation['nom']
        ]
    ]);
    
} catch (Exception $e) {
    // Log de l'erreur
    error_log("Erreur création paiement SumUp: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 