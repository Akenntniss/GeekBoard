<?php
// Définir le type de contenu JSON
header('Content-Type: application/json');

// Inclure la configuration de session
require_once __DIR__ . '/../config/session_config.php';

// Inclure la configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non connecté',
        'redirect' => '/pages/login.php'
    ]);
    exit;
}

// Vérifier que le shop_id est défini
if (!isset($_SESSION['shop_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Shop ID non défini dans la session',
        'redirect' => '/pages/login.php'
    ]);
    exit;
}

try {
    // Log des données reçues
    error_log("POST data reçu: " . print_r($_POST, true));
    
    // Validation des données communes
    $client_id = $_POST['client_id'] ?? null;
    $fournisseur_id = $_POST['fournisseur_id'] ?? null;
    $statut = $_POST['statut'] ?? 'en_attente';
    $notes = $_POST['notes'] ?? '';
    
    error_log("Validation - client_id: " . var_export($client_id, true));
    error_log("Validation - fournisseur_id: " . var_export($fournisseur_id, true));
    
    // Extraire les pièces du formulaire
    $pieces = [];
    if (isset($_POST['pieces']) && is_array($_POST['pieces'])) {
        foreach ($_POST['pieces'] as $index => $piece) {
            if (!empty($piece['nom_piece']) && !empty($piece['quantite'])) {
                $pieces[] = [
                    'nom_piece' => trim($piece['nom_piece']),
                    'code_barre' => trim($piece['code_barre'] ?? ''),
                    'quantite' => intval($piece['quantite'])
                ];
            }
        }
    }
    
    error_log("Pièces extraites: " . print_r($pieces, true));
    error_log("Nombre de pièces: " . count($pieces));
    
    if (empty($pieces)) {
        error_log("Erreur: Aucune pièce valide à commander");
        throw new Exception('Veuillez ajouter au moins une pièce à la commande');
    }
    
    if (empty($fournisseur_id)) {
        error_log("Erreur: Fournisseur requis");
        throw new Exception('Veuillez sélectionner un fournisseur');
    }
    
    if (empty($client_id)) {
        error_log("Erreur: Client requis");
        throw new Exception('Veuillez sélectionner un client');
    }
    
    // Connexion à la base de données du shop
    try {
        $shop_pdo = getShopDBConnection();
        error_log("Connexion à la base de données réussie");
    } catch (Exception $db_error) {
        error_log("Erreur de connexion à la base de données: " . $db_error->getMessage());
        throw new Exception('Erreur de connexion à la base de données: ' . $db_error->getMessage());
    }
    
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    $commandesCreees = 0;
    $commandeIds = [];
    
    // Préparer la requête d'insertion
    $stmt = $shop_pdo->prepare("
        INSERT INTO commandes_pieces (
            reference, client_id, fournisseur_id, 
            nom_piece, code_barre, quantite, 
            statut, notes, date_creation, user_id
        ) VALUES (
            :reference, :client_id, :fournisseur_id, 
            :nom_piece, :code_barre, :quantite, 
            :statut, :notes, NOW(), :user_id
        )
    ");
    
    // Traiter chaque pièce
    foreach ($pieces as $index => $piece) {
        // Générer une référence unique
        $reference = 'CMD-' . date('Ymd') . '-' . uniqid();
        
        // Préparer les données
        $insert_data = [
            'reference' => $reference,
            'client_id' => $client_id,
            'fournisseur_id' => $fournisseur_id,
            'nom_piece' => $piece['nom_piece'],
            'code_barre' => $piece['code_barre'] ?: null,
            'quantite' => $piece['quantite'],
            'statut' => $statut,
            'notes' => $notes,
            'user_id' => $_SESSION['user_id']
        ];
        
        // Log des données à insérer
        error_log("Commande à insérer (pièce " . ($index + 1) . "): " . print_r($insert_data, true));
        
        // Exécuter la requête
        $result = $stmt->execute($insert_data);
        
        if ($result) {
            $commandeId = $shop_pdo->lastInsertId();
            $commandeIds[] = $commandeId;
            $commandesCreees++;
            error_log("Commande créée avec succès - ID: " . $commandeId . " - Pièce: " . $piece['nom_piece']);
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Erreur lors de l'insertion de la pièce " . ($index + 1) . ": " . print_r($errorInfo, true));
            throw new Exception('Erreur lors de l\'insertion de la pièce: ' . $piece['nom_piece']);
        }
    }
    
    // Valider la transaction
    $shop_pdo->commit();
    
    error_log("Transaction validée - " . $commandesCreees . " commande(s) créée(s)");
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => $commandesCreees === 1 ? 
            'Commande créée avec succès' : 
            $commandesCreees . ' commandes créées avec succès',
        'commandes_creees' => $commandesCreees,
        'commande_ids' => $commandeIds
    ]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
        error_log("Transaction annulée suite à l'erreur: " . $e->getMessage());
    }
    
    error_log("Erreur dans add_commande_multiple.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
