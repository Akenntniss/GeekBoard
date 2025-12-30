<?php
// Démarrer la session
session_start();

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier que les paramètres sont fournis
if (!isset($_POST['id']) || !isset($_POST['statut']) || empty($_POST['id']) || empty($_POST['statut'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$commandeId = intval($_POST['id']);
$nouveauStatut = $_POST['statut'];

// Valider le statut
$statutsValides = ['en_attente', 'commande', 'recue', 'annulee', 'urgent', 'termine', 'utilise', 'a_retourner'];
if (!in_array($nouveauStatut, $statutsValides)) {
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Connexion à la base de données du magasin non disponible');
    }
    
    // Vérifier que la commande existe
    $checkStmt = $shop_pdo->prepare("SELECT id, statut FROM commandes_pieces WHERE id = :id");
    $checkStmt->bindParam(':id', $commandeId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    $commande = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$commande) {
        echo json_encode(['success' => false, 'message' => 'Commande non trouvée']);
        exit;
    }
    
    // Mettre à jour le statut
    $updateStmt = $shop_pdo->prepare("
        UPDATE commandes_pieces 
        SET statut = :statut, 
            date_modification = NOW(),
            date_commande = CASE WHEN :statut2 = 'commande' AND date_commande IS NULL THEN NOW() ELSE date_commande END,
            date_reception = CASE WHEN :statut3 = 'recue' AND date_reception IS NULL THEN NOW() ELSE date_reception END
        WHERE id = :id
    ");
    
    $updateStmt->bindParam(':statut', $nouveauStatut, PDO::PARAM_STR);
    $updateStmt->bindParam(':statut2', $nouveauStatut, PDO::PARAM_STR);
    $updateStmt->bindParam(':statut3', $nouveauStatut, PDO::PARAM_STR);
    $updateStmt->bindParam(':id', $commandeId, PDO::PARAM_INT);
    
    if ($updateStmt->execute()) {
        // Log de l'action si nécessaire
        error_log("Statut de la commande {$commandeId} mis à jour vers {$nouveauStatut} par l'utilisateur " . ($_SESSION['user_id'] ?? 'inconnu'));
        
        echo json_encode([
            'success' => true, 
            'message' => 'Statut mis à jour avec succès',
            'ancien_statut' => $commande['statut'],
            'nouveau_statut' => $nouveauStatut
        ]);
    } else {
        throw new Exception('Erreur lors de la mise à jour du statut');
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la mise à jour du statut: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception lors de la mise à jour du statut: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
