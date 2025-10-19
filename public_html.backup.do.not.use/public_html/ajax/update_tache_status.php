<?php
// Désactiver l'affichage des erreurs PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Activer la journalisation des erreurs
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Définir l'en-tête JSON
header('Content-Type: application/json');

// Fichier de journalisation pour déboguer
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/update_tache_status.log';

try {
    // Vérifier si la méthode est POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode invalide, utilisez POST');
    }
    
    // Vérifier les paramètres requis
    if (!isset($_POST['id']) || !isset($_POST['statut'])) {
        throw new Exception('Paramètres manquants: id et statut sont requis');
    }
    
    // Inclure les fichiers nécessaires
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    
    // Démarrer la session pour accéder aux informations du magasin
    session_start();
    
    // Journaliser les informations de session pour le debug
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Session shop_id: " . ($_SESSION['shop_id'] ?? 'non défini') . "\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
    
    // Vérifier et définir le shop_id si nécessaire
    if (!isset($_SESSION['shop_id'])) {
        // Si pas de shop_id en session, essayer de récupérer le premier magasin disponible
        try {
            $main_pdo = getMainDBConnection();
            $stmt = $main_pdo->query("SELECT id FROM shops LIMIT 1");
            $shop = $stmt->fetch();
            if ($shop) {
                $_SESSION['shop_id'] = $shop['id'];
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Shop_id défini automatiquement: " . $shop['id'] . "\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Erreur lors de la récupération automatique du shop_id: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
    
    // Obtenir la connexion à la base de données du magasin
    try {
        $shop_pdo = getShopDBConnection();
        if (!$shop_pdo) {
            throw new Exception('Impossible de se connecter à la base de données du magasin');
        }
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Connexion à la base de données réussie\n", FILE_APPEND);
    } catch (Exception $db_error) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Erreur de connexion: " . $db_error->getMessage() . "\n", FILE_APPEND);
        throw new Exception('Erreur de connexion à la base de données: ' . $db_error->getMessage());
    }
    
    // Récupérer et valider les données
    $tache_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nouveau_statut = filter_input(INPUT_POST, 'statut', FILTER_SANITIZE_STRING);
    
    // Valider l'ID de la tâche
    if (!$tache_id) {
        throw new Exception('ID de tâche invalide');
    }
    
    // Valider le statut
    $statuts_valides = ['a_faire', 'en_cours', 'termine'];
    if (!in_array($nouveau_statut, $statuts_valides)) {
        throw new Exception('Statut invalide. Les valeurs acceptées sont: ' . implode(', ', $statuts_valides));
    }
    
    // Journaliser l'action
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Mise à jour tâche ID: $tache_id, nouveau statut: $nouveau_statut\n", FILE_APPEND);
    
    // Récupérer l'ancien statut pour les logs
    $stmt = $shop_pdo->prepare("SELECT statut FROM taches WHERE id = ?");
    $stmt->execute([$tache_id]);
    $tache = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tache) {
        throw new Exception('Tâche non trouvée');
    }
    
    $ancien_statut = $tache['statut'];
    
    // Mettre à jour le statut de la tâche
    $stmt = $shop_pdo->prepare("UPDATE taches SET statut = ? WHERE id = ?");
    $result = $stmt->execute([$nouveau_statut, $tache_id]);
    
    if (!$result) {
        throw new Exception('Erreur lors de la mise à jour du statut');
    }
    
    // Obtenir l'ID de l'utilisateur connecté
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    // Journaliser le succès
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Succès: Tâche ID: $tache_id mise à jour. Ancien statut: $ancien_statut, Nouveau statut: $nouveau_statut\n", FILE_APPEND);
    
    // Retourner une réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Statut de la tâche mis à jour avec succès',
        'data' => [
            'id' => $tache_id,
            'old_status' => $ancien_statut,
            'new_status' => $nouveau_statut
        ]
    ]);
    
} catch (Exception $e) {
    // Journaliser l'erreur
    if (isset($logFile)) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Erreur: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    // Retourner un message d'erreur
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 