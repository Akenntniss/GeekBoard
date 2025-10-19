<?php
/**
 * Récupérer les transactions d'un partenaire
 * Version simplifiée sans authentification requise
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

// Récupérer l'ID du partenaire
$partenaire_id = filter_input(INPUT_GET, 'partenaire_id', FILTER_VALIDATE_INT);

if (!$partenaire_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID du partenaire invalide']);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Impossible de se connecter à la base de données du magasin");
    }

    // Vérifier que le partenaire existe
    $stmt = $shop_pdo->prepare("SELECT id, nom FROM partenaires WHERE id = ?");
    $stmt->execute([$partenaire_id]);
    $partenaire = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$partenaire) {
        throw new Exception('Partenaire introuvable');
    }

    // S'assurer que la colonne reject_reason existe sur partner_transactions_pending
    try {
        $colStmt = $shop_pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'partner_transactions_pending' AND COLUMN_NAME = 'reject_reason'");
        $colStmt->execute();
        $hasRejectReason = (int)$colStmt->fetchColumn() > 0;
        if (!$hasRejectReason) {
            // Tenter d'ajouter la colonne si elle n'existe pas encore
            try {
                $shop_pdo->exec("ALTER TABLE partner_transactions_pending ADD COLUMN reject_reason TEXT NULL AFTER description");
            } catch (Exception $e) {
                // Ignorer si concurrent/permissions; l'appel suivant échouera gracieusement
            }
        }
    } catch (Exception $e) {
        // Ne pas bloquer l'API si l'INFORMATION_SCHEMA est restreint
    }

    // Récupérer le solde actuel
    $stmt = $shop_pdo->prepare("
        SELECT solde_actuel 
        FROM soldes_partenaires 
        WHERE partenaire_id = ?
    ");
    $stmt->execute([$partenaire_id]);
    $solde_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $solde = $solde_data ? $solde_data['solde_actuel'] : 0;

    // Récupérer les transactions validées
    $stmt = $shop_pdo->prepare("
        SELECT 
            id,
            type,
            montant,
            description,
            date_transaction,
            'approved' as transaction_status,
            NULL as pending_id
        FROM transactions_partenaires 
        WHERE partenaire_id = ?
        ORDER BY date_transaction DESC
    ");
    $stmt->execute([$partenaire_id]);
    $validated_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir les types de la base vers l'affichage
    foreach ($validated_transactions as &$transaction) {
        if ($transaction['type'] === 'AVANCE') {
            $transaction['type'] = 'credit';
        } elseif ($transaction['type'] === 'REMBOURSEMENT') {
            $transaction['type'] = 'debit';
        }
    }
    
    // Récupérer les transactions en attente ET rejetées (inclure le motif)
    $stmt = $shop_pdo->prepare("
        SELECT 
            id as pending_id,
            type,
            montant,
            description,
            created_at as date_transaction,
            status as transaction_status,
            reject_reason,
            id as pending_id
        FROM partner_transactions_pending 
        WHERE partenaire_id = ? AND status IN ('pending', 'rejected')
        ORDER BY created_at DESC
    ");
    $stmt->execute([$partenaire_id]);
    $pending_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir les types de la base vers l'affichage pour les transactions en attente aussi
    foreach ($pending_transactions as &$transaction) {
        if ($transaction['type'] === 'AVANCE') {
            $transaction['type'] = 'credit';
        } elseif ($transaction['type'] === 'REMBOURSEMENT') {
            $transaction['type'] = 'debit';
        }
    }
    
    // Fusionner les transactions
    $transactions = array_merge($validated_transactions, $pending_transactions);
    
    // Trier par date
    usort($transactions, function($a, $b) {
        return strtotime($b['date_transaction']) - strtotime($a['date_transaction']);
    });
    
    // Compter les transactions en attente
    $pending_count = count($pending_transactions);

    // Préparer la réponse
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'partenaire' => $partenaire,
        'solde' => $solde,
        'transactions' => $transactions,
        'pending_count' => $pending_count
    ]);

} catch (Exception $e) {
    error_log("Erreur get_transactions_partenaire.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la récupération des transactions: ' . $e->getMessage()
    ]);
}
?>