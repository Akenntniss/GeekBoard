<?php
/**
 * API pour associer une pièce détachée à une réparation
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Initialiser la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialiser la connexion à la base de données du magasin
initializeShopSession();

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et valider les données
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$reparation_id = filter_input(INPUT_POST, 'reparation_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if ($product_id === null || $product_id === false ||
    $reparation_id === null || $reparation_id === false ||
    $quantity === null || $quantity === false || $quantity < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $pdo = getShopDBConnection();
    $pdo->beginTransaction();

    // Vérifier que la réparation existe
    $stmt = $pdo->prepare("SELECT id, client_id, type_appareil, modele FROM reparations WHERE id = ?");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reparation) {
        $pdo->rollBack();
        throw new Exception('Réparation non trouvée');
    }

    // Vérifier que le produit existe et qu'il y a assez de stock
    // Essayer d'abord la table stock
    $stmt = $pdo->prepare("SELECT id, name, quantity FROM stock WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    $table_used = 'stock';
    $name_field = 'name';
    $quantity_field = 'quantity';

    if (!$product) {
        // Essayer la table produits
        $stmt = $pdo->prepare("SELECT id, nom, reference, quantite FROM produits WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $table_used = 'produits';
            $name_field = 'nom';
            $quantity_field = 'quantite';
            $product['name'] = $product['nom'];
            $product['quantity'] = $product['quantite'];
        }
    }

    if (!$product) {
        $pdo->rollBack();
        throw new Exception('Produit non trouvé');
    }

    if ($product['quantity'] < $quantity) {
        $pdo->rollBack();
        throw new Exception('Stock insuffisant. Disponible: ' . $product['quantity']);
    }

    // Vérifier si cette pièce n'est pas déjà associée à cette réparation
    $stmt = $pdo->prepare("
        SELECT id, quantite_utilisee 
        FROM pieces_utilisees_reparations 
        WHERE reparation_id = ? AND produit_id = ?
    ");
    $stmt->execute([$reparation_id, $product_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Mettre à jour la quantité existante
        $new_quantity = $existing['quantite_utilisee'] + $quantity;
        
        $stmt = $pdo->prepare("
            UPDATE pieces_utilisees_reparations 
            SET quantite_utilisee = ?, date_utilisation = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$new_quantity, $existing['id']]);
        
        $action_message = "Quantité mise à jour: {$existing['quantite_utilisee']} → {$new_quantity}";
    } else {
        // Créer une nouvelle association
        $stmt = $pdo->prepare("
            INSERT INTO pieces_utilisees_reparations 
            (reparation_id, produit_id, quantite_utilisee, date_utilisation, user_id, notes) 
            VALUES (?, ?, ?, NOW(), ?, ?)
        ");
        
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $notes = "Pièce associée via scanner QR";
        
        $stmt->execute([$reparation_id, $product_id, $quantity, $user_id, $notes]);
        
        $action_message = "Nouvelle pièce associée";
    }

    // Déduire du stock
    $new_stock = $product['quantity'] - $quantity;
    $stmt = $pdo->prepare("UPDATE {$table_used} SET {$quantity_field} = ? WHERE id = ?");
    $stmt->execute([$new_stock, $product_id]);

    // Enregistrer le mouvement de stock si la table existe
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'stock_movements'");
        $stmt->execute();
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO stock_movements 
                (product_id, quantity_change, new_quantity, old_quantity, reason, user_id, timestamp)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            $reason = "Utilisation pour réparation #{$reparation_id}";
            $stmt->execute([
                $product_id, 
                -$quantity, 
                $new_stock, 
                $product['quantity'], 
                $reason, 
                $userId
            ]);
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de l'enregistrement du mouvement de stock: " . $e->getMessage());
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pièce associée avec succès à la réparation',
        'data' => [
            'product_id' => $product_id,
            'product_name' => $product['name'],
            'reparation_id' => $reparation_id,
            'reparation_info' => $reparation['type_appareil'] . ' ' . $reparation['modele'],
            'quantity_used' => $quantity,
            'old_stock' => $product['quantity'],
            'new_stock' => $new_stock,
            'action' => $action_message,
            'table_used' => $table_used
        ]
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur API associate_piece_repair: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
