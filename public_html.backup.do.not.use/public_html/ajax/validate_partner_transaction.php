<?php
/**
 * Valider ou rejeter une transaction partenaire en attente
 */

// Configuration des erreurs (ne pas afficher pour préserver JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Inclure la configuration de session
require_once dirname(__DIR__) . '/config/session_config.php';
require_once dirname(__DIR__) . '/config/database.php';

// Réponse JSON par défaut et pas de cache
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Détecter le magasin depuis le sous-domaine
if (!isset($_SESSION['shop_id'])) {
    require_once dirname(__DIR__) . '/config/subdomain_config.php';
    $detected_shop_id = detectShopFromSubdomain();
    if ($detected_shop_id) {
        $_SESSION['shop_id'] = $detected_shop_id;
        error_log("Shop ID détecté et défini: " . $detected_shop_id);
    } else {
        error_log("Impossible de détecter le shop_id depuis le sous-domaine");
    }
}

// Vérifier la session utilisateur (tolérant)
if (!isset($_SESSION['shop_id'])) {
    // Dernière tentative de détection
    if (function_exists('detectShopFromSubdomain')) {
        $detected = detectShopFromSubdomain();
        if ($detected) {
            $_SESSION['shop_id'] = $detected;
        }
    }
    if (!isset($_SESSION['shop_id'])) {
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'message' => 'Magasin introuvable pour cette requête'
        ]);
        exit;
    }
}

// Auth utilisateur facultative
$validatedByUserId = $_SESSION['user_id'] ?? null;

// Log des paramètres reçus pour debug
error_log("VALIDATE_PARTNER_TRANSACTION: Method=" . $_SERVER['REQUEST_METHOD'] . ", POST=" . json_encode($_POST));

try {
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Validation des données POST
    $pending_id = filter_var($_POST['pending_id'] ?? '', FILTER_VALIDATE_INT);
    $action = filter_var($_POST['action'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    error_log("VALIDATE_PARTNER_TRANSACTION: pending_id=$pending_id, action=$action");
    
    if (!$pending_id || !in_array($action, ['approve', 'reject'])) {
        throw new Exception('Données invalides');
    }
    
    // Connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Erreur de connexion à la base de données du magasin');
    }
    
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    // Récupérer la transaction en attente (tolérant aux incohérences d'état)
    $stmt = $shop_pdo->prepare("SELECT * FROM partner_transactions_pending WHERE id = ?");
    $stmt->execute([$pending_id]);
    $pending_transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pending_transaction) {
        throw new Exception('Transaction en attente introuvable');
    }
    if ($pending_transaction['status'] !== 'pending') {
        echo json_encode([
            'success' => true,
            'message' => 'Déjà traitée',
            'action' => $pending_transaction['status']
        ]);
        $shop_pdo->commit();
        exit;
    }
    
    if ($action === 'approve') {
        // Approuver : transférer vers transactions_partenaires
        $stmt = $shop_pdo->prepare("
            INSERT INTO transactions_partenaires 
            (partenaire_id, type, montant, description, date_transaction)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $pending_transaction['partenaire_id'],
            $pending_transaction['type'],
            $pending_transaction['montant'],
            $pending_transaction['description']
        ]);
        
        // Mettre à jour le solde (approche en 2 étapes pour éviter erreurs SQL)
        $delta = ($pending_transaction['type'] === 'AVANCE') 
            ? (float)$pending_transaction['montant'] 
            : -(float)$pending_transaction['montant'];
        $stmt = $shop_pdo->prepare("SELECT 1 FROM soldes_partenaires WHERE partenaire_id = ?");
        $stmt->execute([$pending_transaction['partenaire_id']]);
        if ($stmt->fetchColumn()) {
            $stmt = $shop_pdo->prepare("UPDATE soldes_partenaires SET solde_actuel = solde_actuel + ? WHERE partenaire_id = ?");
            $stmt->execute([$delta, $pending_transaction['partenaire_id']]);
        } else {
            $stmt = $shop_pdo->prepare("INSERT INTO soldes_partenaires (partenaire_id, solde_actuel) VALUES (?, ?)");
            $stmt->execute([$pending_transaction['partenaire_id'], $delta]);
        }
        
        // Marquer comme approuvée
        $stmt = $shop_pdo->prepare("
            UPDATE partner_transactions_pending 
            SET status = 'approved', validated_at = NOW(), validated_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$validatedByUserId, $pending_id]);
        
        $message = 'Transaction validée avec succès';
        
    } else {
        // Rejeter : enregistrer le motif et marquer comme rejetée
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($reason === '') { $reason = null; }

        // S'assurer que la colonne reject_reason existe
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'partner_transactions_pending' AND COLUMN_NAME = 'reject_reason'");
        $stmt->execute();
        $hasColumn = (int)$stmt->fetchColumn() > 0;
        if (!$hasColumn) {
            // MySQL 5.7+ compatible (pas IF NOT EXISTS)
            try {
                $shop_pdo->exec("ALTER TABLE partner_transactions_pending ADD COLUMN reject_reason TEXT NULL AFTER description");
            } catch (Exception $ignore) { /* colonne peut déjà exister */ }
        }

        $stmt = $shop_pdo->prepare("
            UPDATE partner_transactions_pending 
            SET status = 'rejected', validated_at = NOW(), validated_by = ?, reject_reason = ?
            WHERE id = ?
        ");
        $stmt->execute([$validatedByUserId, $reason, $pending_id]);
        
        $message = 'Transaction rejetée avec succès';
    }
    
    // Confirmer la transaction
    $shop_pdo->commit();
    
    // Log de l'action
    error_log("Transaction partenaire {$action}d - Pending ID: $pending_id, User: {$_SESSION['user_id']}");
    
    // Succès (réponse simplifiée)
    $response = [
        'success' => true,
        'message' => $message,
        'action' => $action
    ];
    error_log("VALIDATE_PARTNER_TRANSACTION: Success response=" . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback en cas d'erreur
    if (isset($shop_pdo)) {
        $shop_pdo->rollBack();
    }
    
    error_log("Erreur validate_partner_transaction.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la validation: ' . $e->getMessage()
    ]);
}
?>