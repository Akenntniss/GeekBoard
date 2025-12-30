<?php
// Inclure la configuration de session avant de démarrer la session
require_once __DIR__ . '/../config/session_config.php';
// La session est déjà démarrée dans session_config.php

// Inclusion de la configuration des sous-domaines pour la détection automatique du shop_id
require_once __DIR__ . '/../config/subdomain_config.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
    exit;
}

// Vérification si l'ID de la tâche est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de tâche manquant']);
    exit;
}

// Inclusion des fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';

// Nettoyage de l'ID
$tache_id = (int)$_GET['id'];

try {
    // Utilisation de getShopDBConnection() pour la connexion dynamique
    $pdo = getShopDBConnection();
    
    // Récupération des détails de la tâche (seulement description et statut pour taches.js)
    $stmt = $pdo->prepare("
        SELECT description, statut
        FROM taches 
        WHERE id = ?
    ");
    $stmt->execute([$tache_id]);
    $tache = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tache) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Tâche non trouvée']);
        exit;
    }
    
    // Récupération des pièces jointes
    $stmt = $pdo->prepare("
        SELECT id, file_name, file_type, file_size, file_path, est_image, date_upload
        FROM tache_attachments 
        WHERE tache_id = ?
        ORDER BY date_upload ASC
    ");
    $stmt->execute([$tache_id]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer la réponse JSON dans le format attendu par taches.js
    $response = [
        'success' => true,
        'description' => $tache['description'] ?: 'Aucune description disponible',
        'status' => $tache['statut'],
        'attachments' => $attachments
    ];
    
    // Renvoyer la réponse
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // En cas d'erreur, renvoyer un message d'erreur
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()
    ]);
    
    // Journaliser l'erreur
    error_log('Erreur dans get_tache_details.php: ' . $e->getMessage());
}
?>