<?php
/**
 * Ajouter une transaction partenaire directement
 * Système simplifié sans token
 */

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session immédiatement
session_start();

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/subdomain_config.php';

// Initialiser la session du magasin si nécessaire
if (!isset($_SESSION['shop_id'])) {
    $detected_shop_id = detectShopFromSubdomain();
    if ($detected_shop_id) {
        $_SESSION['shop_id'] = $detected_shop_id;
    }
}

try {
    // Validation des données
    $partenaire_id = filter_input(INPUT_POST, 'partenaire_id', FILTER_VALIDATE_INT);
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $montant = filter_input(INPUT_POST, 'montant', FILTER_VALIDATE_FLOAT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    if (!$partenaire_id || !$type || $montant === false || !$description) {
        throw new Exception('Données manquantes ou invalides');
    }
    
    if ($montant <= 0) {
        throw new Exception('Le montant doit être supérieur à 0');
    }
    
    if (!in_array($type, ['AVANCE', 'REMBOURSEMENT'])) {
        throw new Exception('Type de transaction invalide');
    }
    
    // Connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Vérifier que le partenaire existe
    $stmt = $shop_pdo->prepare("SELECT id, nom FROM partenaires WHERE id = ?");
    $stmt->execute([$partenaire_id]);
    $partenaire = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$partenaire) {
        throw new Exception('Partenaire introuvable');
    }
    
    // Insérer la transaction en attente de validation
    $stmt = $shop_pdo->prepare("
        INSERT INTO partner_transactions_pending 
        (partenaire_id, type, montant, description, shop_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$partenaire_id, $type, $montant, $description, $_SESSION['shop_id']]);
    
    $transaction_id = $shop_pdo->lastInsertId();
    
    // Ne pas mettre à jour le solde tant que la transaction n'est pas validée
    
    // Log de la transaction pour audit
    error_log("Transaction partenaire créée - ID: $transaction_id, Partenaire: {$partenaire['nom']}, Type: $type, Montant: $montant");
    
    // Préparer données SMS pour envoi async
    $sms_data = null;
    if (!empty($partenaire['telephone'])) {
        require_once dirname(__DIR__) . '/includes/sms_functions.php';
        
        $type_sms = ($type === 'AVANCE') ? 'Credit' : 'Debit';
        $message_sms = "Ajout Transaction\nType : " . $type_sms . "\nMontant : " . number_format($montant, 2, ',', ' ') . " €\nDescription : " . $description;
        
        $sms_data = [
            'telephone' => $partenaire['telephone'],
            'message' => $message_sms,
            'transaction_id' => $transaction_id,
            'partenaire_nom' => $partenaire['nom']
        ];
    }
    
    // === RÉPONDRE IMMÉDIATEMENT AU CLIENT ===
    $response = [
        'success' => true,
        'message' => 'Transaction enregistrée avec succès - En attente de validation',
        'transaction_id' => $transaction_id,
        'status' => 'pending'
    ];
    
    $json_response = json_encode($response);
    
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    header('Connection: close');
    header('Content-Length: ' . strlen($json_response));
    echo $json_response;
    
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        flush();
    }
    
    // === ENVOI SMS EN ARRIÈRE-PLAN ===
    if ($sms_data !== null) {
        ignore_user_abort(true);
        set_time_limit(30);
        
        $sms_result = send_sms(
            $sms_data['telephone'], 
            $sms_data['message'], 
            'partner_transaction', 
            $sms_data['transaction_id']
        );
        
        if ($sms_result['success']) {
            error_log("SMS envoyé avec succès au partenaire {$sms_data['partenaire_nom']}");
        } else {
            error_log("Erreur envoi SMS au partenaire: " . $sms_result['message']);
        }
    }
    
    exit;
    
} catch (Exception $e) {
    error_log("Erreur add_partner_transaction.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>