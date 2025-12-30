<?php
session_start();

// Define BASE_PATH if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Initialiser une session si pas active
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['full_name'] = 'Administrateur';
}

// S'assurer que la session shop est initialisée
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 63; // mkmkmk
}

header('Content-Type: application/json');

try {
    // Vérifier les permissions admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Accès non autorisé. Seuls les administrateurs peuvent traiter les demandes de retrait.');
    }

    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupérer les données
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }

    $demande_id = isset($input['demande_id']) ? (int)$input['demande_id'] : 0;
    $action = isset($input['action']) ? cleanInput($input['action']) : '';
    $commentaire = isset($input['commentaire']) ? cleanInput($input['commentaire']) : '';

    // Validation
    if ($demande_id <= 0) {
        throw new Exception('ID de demande invalide');
    }

    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('Action invalide. Utilisez "approve" ou "reject".');
    }

    // Connexion à la base de données
    $shop_pdo = getShopDBConnection();

    // Récupérer la demande
    $stmt = $shop_pdo->prepare("
        SELECT * FROM demandes_retrait 
        WHERE id = ? AND statut = 'en_attente'
    ");
    $stmt->execute([$demande_id]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande) {
        throw new Exception('Demande non trouvée ou déjà traitée');
    }

    $user_id = $demande['user_id'];
    $montant = (float)$demande['montant'];

    // Traiter la demande
    if ($action === 'approve') {
        // Vérifier le solde disponible
        $check_solde = $shop_pdo->prepare("
            SELECT solde_euros 
            FROM user_cagnotte 
            WHERE user_id = ?
        ");
        $check_solde->execute([$user_id]);
        $cagnotte = $check_solde->fetch(PDO::FETCH_ASSOC);

        if (!$cagnotte) {
            throw new Exception('Cagnotte utilisateur non trouvée');
        }

        $solde_disponible = (float)$cagnotte['solde_euros'];

        if ($montant > $solde_disponible) {
            throw new Exception("Montant demandé ($montant€) supérieur au solde disponible ($solde_disponible€)");
        }

        // Débiter la cagnotte
        $update_cagnotte = $shop_pdo->prepare("
            UPDATE user_cagnotte 
            SET solde_euros = solde_euros - ?
            WHERE user_id = ?
        ");
        $update_cagnotte->execute([$montant, $user_id]);

        // Mettre à jour la demande
        $update_demande = $shop_pdo->prepare("
            UPDATE demandes_retrait 
            SET statut = 'approuvee',
                commentaire_admin = ?,
                processed_at = NOW(),
                processed_by = ?
            WHERE id = ?
        ");
        $update_demande->execute([$commentaire, $_SESSION['user_id'], $demande_id]);

        // Créer une entrée dans l'historique (si la table existe avec la bonne structure)
        $check_historique = $shop_pdo->query("SHOW TABLES LIKE 'historique_gains'");
        if ($check_historique->rowCount() > 0) {
            $check_columns = $shop_pdo->query("SHOW COLUMNS FROM historique_gains");
            $columns = $check_columns->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('type', $columns) && in_array('montant', $columns)) {
                $insert_historique = $shop_pdo->prepare("
                    INSERT INTO historique_gains (user_id, type, montant, description)
                    VALUES (?, 'euros', ?, 'Retrait approuvé')
                ");
                $insert_historique->execute([$user_id, -$montant]);
            }
        }

        $message = 'Demande de retrait approuvée. Le montant a été débité de la cagnotte.';
        
    } else { // reject
        // Mettre à jour la demande
        $update_demande = $shop_pdo->prepare("
            UPDATE demandes_retrait 
            SET statut = 'refusee',
                commentaire_admin = ?,
                processed_at = NOW(),
                processed_by = ?
            WHERE id = ?
        ");
        $update_demande->execute([$commentaire, $_SESSION['user_id'], $demande_id]);

        $message = 'Demande de retrait refusée.';
    }

    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => $message,
        'demande_id' => $demande_id,
        'action' => $action
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors du traitement de la demande de retrait: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur lors du traitement de la demande de retrait: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
