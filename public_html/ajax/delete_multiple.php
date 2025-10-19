<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la configuration des sous-domaines pour la détection automatique du magasin
require_once __DIR__ . '/../config/subdomain_config.php';

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';

// Vérifier l'accès au magasin (pas besoin d'utilisateur connecté pour cette page)
if (!isset($_SESSION['shop_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Accès non autorisé - Magasin non détecté']);
    exit();
}

header('Content-Type: application/json');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée");
    }

    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['ids']) || !is_array($input['ids'])) {
        throw new Exception("IDs invalides");
    }

    $ids = array_filter($input['ids'], 'is_numeric');
    if (empty($ids)) {
        throw new Exception("Aucun ID valide fourni");
    }

    // Obtenir la connexion à la base de données
    $pdo = getShopDBConnection();
    if ($pdo === null) {
        throw new Exception("La connexion à la base de données n'est pas disponible");
    }

    // Commencer une transaction
    $pdo->beginTransaction();

    try {
        $deletedCount = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                // Vérifier que le rachat existe et récupérer les chemins des images
                $stmt = $pdo->prepare("SELECT id, photo_appareil, photo_identite, client_photo FROM rachat_appareils WHERE id = ?");
                $stmt->execute([$id]);
                $rachat = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$rachat) {
                    $errors[] = "Rachat ID $id non trouvé";
                    continue;
                }

                // Supprimer les fichiers d'images associés
                $imagesToDelete = [
                    $rachat['photo_appareil'],
                    $rachat['photo_identite'],
                    $rachat['client_photo']
                ];

                foreach ($imagesToDelete as $imagePath) {
                    if (!empty($imagePath) && strpos($imagePath, 'data:') !== 0) {
                        // Si ce n'est pas une image base64, c'est un fichier à supprimer
                        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/rachat/' . $imagePath;
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                }

                // Supprimer le rachat de la base de données
                $stmt = $pdo->prepare("DELETE FROM rachat_appareils WHERE id = ?");
                $stmt->execute([$id]);

                if ($stmt->rowCount() > 0) {
                    $deletedCount++;
                }

            } catch (Exception $e) {
                $errors[] = "Erreur lors de la suppression du rachat ID $id: " . $e->getMessage();
            }
        }

        // Valider la transaction
        $pdo->commit();

        $response = [
            'success' => true,
            'deleted_count' => $deletedCount,
            'total_requested' => count($ids),
            'message' => "$deletedCount rachat(s) supprimé(s) avec succès"
        ];

        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }

        echo json_encode($response);

    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Erreur dans delete_multiple.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 