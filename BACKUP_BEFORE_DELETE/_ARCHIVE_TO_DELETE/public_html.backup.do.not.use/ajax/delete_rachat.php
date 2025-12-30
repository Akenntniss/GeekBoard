<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier l'accès
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../includes/SubdomainDatabaseDetector.php';
require_once __DIR__ . '/../config/database.php';

// Initialiser le détecteur de sous-domaine
$subdomain_detector = new SubdomainDatabaseDetector();

header('Content-Type: application/json');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée");
    }

    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || !is_numeric($input['id'])) {
        throw new Exception("ID invalide");
    }

    $id = (int)$input['id'];

    // Obtenir la connexion à la base de données
    $pdo = $subdomain_detector->getConnection();
    if ($pdo === null) {
        throw new Exception("La connexion à la base de données n'est pas disponible");
    }

    // Commencer une transaction
    $pdo->beginTransaction();

    try {
        // Vérifier que le rachat existe
        $stmt = $pdo->prepare("SELECT id, photo_appareil, photo_identite, client_photo FROM rachat_appareils WHERE id = ?");
        $stmt->execute([$id]);
        $rachat = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rachat) {
            throw new Exception("Rachat non trouvé");
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

        // Valider la transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Rachat supprimé avec succès'
        ]);

    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Erreur dans delete_rachat.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 