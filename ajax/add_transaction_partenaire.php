<?php
// ajax/add_transaction_partenaire.php

// 1. Load session config (handles MDGEEK_SESSION cookie)
require_once '../config/session_config.php';

// 2. Initialize shop session
require_once '../config/database.php';

if (function_exists('initializeShopSession')) {
    initializeShopSession();
}

header('Content-Type: application/json');

// 3. Auth Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// 4. Verify POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // 5. Load additional dependencies
    require_once '../includes/functions.php';

    // 6. Get POST data
    $partenaire_id = filter_input(INPUT_POST, 'partenaire_id', FILTER_VALIDATE_INT);
    $type = trim(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $montant = filter_input(INPUT_POST, 'montant', FILTER_VALIDATE_FLOAT);
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    
    if (!$partenaire_id || !$type || !$montant) {
        throw new Exception('Données invalides');
    }

    // 7. Validate transaction type
    $types_valides = ['credit', 'debit', 'AVANCE', 'REMBOURSEMENT', 'SERVICE'];
    if (!in_array($type, $types_valides)) {
        throw new Exception('Type de transaction invalide');
    }

    // Convert types
    if ($type === 'credit') {
        $type = 'AVANCE';
    } elseif ($type === 'debit') {
        $type = 'REMBOURSEMENT';
    }

    // 8. Get DB connection
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Impossible de se connecter à la base de données");
    }

    // Start transaction
    $shop_pdo->beginTransaction();

    // Insert transaction
    $stmt = $shop_pdo->prepare("
        INSERT INTO transactions_partenaires 
        (partenaire_id, type, montant, description, date_transaction, statut) 
        VALUES (?, ?, ?, ?, NOW(), 'VALIDÉ')
    ");
    $stmt->execute([$partenaire_id, $type, $montant, $description]);
    
    // Update balance
    $signe = ($type === 'AVANCE') ? 1 : -1;
    $stmt = $shop_pdo->prepare("
        UPDATE soldes_partenaires 
        SET solde_actuel = solde_actuel + ?, derniere_mise_a_jour = NOW() 
        WHERE partenaire_id = ?
    ");
    $stmt->execute([$montant * $signe, $partenaire_id]);
    
    // If no row updated, insert initial balance
    if ($stmt->rowCount() === 0) {
        $stmt = $shop_pdo->prepare("
            INSERT INTO soldes_partenaires (partenaire_id, solde_actuel, derniere_mise_a_jour) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$partenaire_id, $montant * $signe]);
    }
    
    $shop_pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Transaction enregistrée avec succès']);

} catch (Exception $e) {
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}