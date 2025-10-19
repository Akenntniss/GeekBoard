<?php
/**
 * API pour traiter les prêts et emprunts avec les partenaires
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
$partner_id = filter_input(INPUT_POST, 'partner_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

if ($product_id === null || $product_id === false ||
    $partner_id === null || $partner_id === false ||
    $quantity === null || $quantity === false || $quantity < 1 ||
    !in_array($action, ['lend', 'borrow'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $pdo = getShopDBConnection();
    $pdo->beginTransaction();

    // Vérifier que le partenaire existe
    $stmt = $pdo->prepare("SELECT id, nom FROM partenaires WHERE id = ? AND actif = 1");
    $stmt->execute([$partner_id]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partner) {
        $pdo->rollBack();
        throw new Exception('Partenaire non trouvé ou inactif');
    }

    // Vérifier que le produit existe et récupérer les informations
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

    // Pour un prêt (lend), vérifier qu'on a assez de stock
    if ($action === 'lend' && $product['quantity'] < $quantity) {
        $pdo->rollBack();
        throw new Exception('Stock insuffisant pour le prêt. Disponible: ' . $product['quantity']);
    }

    // Calculer la nouvelle quantité selon l'action
    if ($action === 'lend') {
        // Je prête = je diminue mon stock
        $new_stock = $product['quantity'] - $quantity;
        $stock_change = -$quantity;
        $transaction_type = 'PRET_SORTANT';
        $description = "Prêt de {$quantity} x {$product['name']} au partenaire {$partner['nom']}";
    } else {
        // J'emprunte = j'augmente mon stock
        $new_stock = $product['quantity'] + $quantity;
        $stock_change = $quantity;
        $transaction_type = 'EMPRUNT_ENTRANT';
        $description = "Emprunt de {$quantity} x {$product['name']} du partenaire {$partner['nom']}";
    }

    // Mettre à jour le stock
    $stmt = $pdo->prepare("UPDATE {$table_used} SET {$quantity_field} = ? WHERE id = ?");
    $stmt->execute([$new_stock, $product_id]);

    // Enregistrer la transaction partenaire
    $stmt = $pdo->prepare("
        INSERT INTO transactions_partenaires 
        (partenaire_id, produit_id, type_transaction, quantite, date_transaction, description, user_id) 
        VALUES (?, ?, ?, ?, NOW(), ?, ?)
    ");
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $stmt->execute([
        $partner_id, 
        $product_id, 
        $transaction_type, 
        $quantity, 
        $description, 
        $user_id
    ]);

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
            $reason = $action === 'lend' ? 
                "Prêt au partenaire {$partner['nom']}" : 
                "Emprunt du partenaire {$partner['nom']}";
            
            $stmt->execute([
                $product_id, 
                $stock_change, 
                $new_stock, 
                $product['quantity'], 
                $reason, 
                $userId
            ]);
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de l'enregistrement du mouvement de stock: " . $e->getMessage());
    }

    // Créer la table transactions_partenaires si elle n'existe pas
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'transactions_partenaires'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $pdo->exec("
                CREATE TABLE transactions_partenaires (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    partenaire_id INT NOT NULL,
                    produit_id INT NOT NULL,
                    type_transaction ENUM('PRET_SORTANT', 'PRET_RETOUR', 'EMPRUNT_ENTRANT', 'EMPRUNT_RETOUR') NOT NULL,
                    quantite INT NOT NULL,
                    date_transaction DATETIME NOT NULL,
                    date_retour_prevue DATE NULL,
                    date_retour_effective DATETIME NULL,
                    description TEXT,
                    statut ENUM('EN_COURS', 'TERMINE', 'ANNULE') DEFAULT 'EN_COURS',
                    user_id INT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_partenaire (partenaire_id),
                    INDEX idx_produit (produit_id),
                    INDEX idx_date (date_transaction)
                )
            ");
            
            // Réinsérer la transaction maintenant que la table existe
            $stmt = $pdo->prepare("
                INSERT INTO transactions_partenaires 
                (partenaire_id, produit_id, type_transaction, quantite, date_transaction, description, user_id) 
                VALUES (?, ?, ?, ?, NOW(), ?, ?)
            ");
            $stmt->execute([
                $partner_id, 
                $product_id, 
                $transaction_type, 
                $quantity, 
                $description, 
                $user_id
            ]);
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la création de la table transactions_partenaires: " . $e->getMessage());
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaction enregistrée avec succès',
        'data' => [
            'product_id' => $product_id,
            'product_name' => $product['name'],
            'partner_id' => $partner_id,
            'partner_name' => $partner['nom'],
            'action' => $action,
            'quantity' => $quantity,
            'old_stock' => $product['quantity'],
            'new_stock' => $new_stock,
            'stock_change' => $stock_change,
            'transaction_type' => $transaction_type,
            'description' => $description,
            'table_used' => $table_used
        ]
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur API process_loan_borrow: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
