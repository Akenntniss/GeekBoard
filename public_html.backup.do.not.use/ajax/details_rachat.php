<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Forcer l'initialisation du détecteur de sous-domaines
if (!isset($subdomain_detector)) {
    require_once __DIR__ . '/../config/subdomain_database_detector.php';
    $subdomain_detector = new SubdomainDatabaseDetector();
}

// Aucune restriction d'accès - tous les utilisateurs peuvent accéder à ces données
// Si vous souhaitez rétablir la restriction plus tard, décommentez le code ci-dessous
/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}
*/

// Vérifier l'ID du rachat
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'ID invalide']);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin directement via le détecteur
    $pdo = $subdomain_detector->getConnection();
    if ($pdo === null) {
        throw new Exception("La connexion à la base de données n'est pas disponible");
    }
    
    $stmt = $pdo->prepare("SELECT 
        r.id,
        r.date_rachat,
        r.type_appareil,
        r.modele,
        r.sin,
        r.prix,
        r.fonctionnel,
        r.photo_identite,
        r.photo_appareil,
        r.client_photo,
        r.signature,
        c.nom,
        c.prenom
    FROM rachat_appareils r
    JOIN clients c ON r.client_id = c.id
    WHERE r.id = ?");
    
    $stmt->execute([$_GET['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Rachat introuvable']);
        exit;
    }

    // Traiter toutes les images de manière uniforme - convertir en base64
    $image_fields = ['photo_identite', 'photo_appareil', 'client_photo', 'signature'];
    
    foreach ($image_fields as $field) {
        if ($result[$field]) {
            $image_path = __DIR__ . '/../assets/images/rachat/' . $result[$field];
            if (file_exists($image_path)) {
                $image_content = base64_encode(file_get_contents($image_path));
                // Déterminer le type MIME basé sur l'extension
                $extension = strtolower(pathinfo($result[$field], PATHINFO_EXTENSION));
                $mime_type = 'image/jpeg'; // par défaut
                if ($extension === 'png') {
                    $mime_type = 'image/png';
                } elseif ($extension === 'gif') {
                    $mime_type = 'image/gif';
                }
                $result[$field] = 'data:' . $mime_type . ';base64,' . $image_content;
            } else {
                $result[$field] = null;
            }
        } else {
            $result[$field] = null;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($result);

} catch (Exception $e) {
    error_log('Erreur: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
}
?>