<?php
/* ====================================================================
   📝 AJAX - AJOUTER CLIENT (VERSION NETTOYÉE)
   Gère l'ajout de nouveaux clients - Version sans pollution JSON
==================================================================== */

// Capturer toute sortie non désirée
ob_start();

// Démarrer la session et inclure la configuration
session_start();
require_once '../config/database.php';

// Initialiser la session shop pour la détection automatique de la base
initializeShopSession();

// Nettoyer le buffer de sortie pour éviter la pollution JSON
ob_clean();

// Headers pour JSON
header('Content-Type: application/json');

try {
    // Vérifier la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Vérifier l'action
    if (!isset($_POST['action']) || $_POST['action'] !== 'ajouter_client') {
        throw new Exception('Action non valide');
    }
    
    // Récupérer et valider les données
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    
    // Validation des champs obligatoires
    if (empty($nom)) {
        throw new Exception('Le nom est obligatoire');
    }
    
    if (empty($prenom)) {
        throw new Exception('Le prénom est obligatoire');
    }
    
    if (empty($telephone)) {
        throw new Exception('Le téléphone est obligatoire');
    }
    
    // Validation format téléphone (11 chiffres)
    if (!preg_match('/^[0-9]{11}$/', $telephone)) {
        throw new Exception('Le téléphone doit contenir exactement 11 chiffres');
    }
    
    // Obtenir la connexion à la base de données du shop
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Vérifier si le client existe déjà (par téléphone)
    $checkStmt = $pdo->prepare("
        SELECT id, nom, prenom 
        FROM clients 
        WHERE telephone = ?
    ");
    $checkStmt->execute([$telephone]);
    $existingClient = $checkStmt->fetch();
    
    if ($existingClient) {
        // Client existe déjà
        echo json_encode([
            'success' => false,
            'message' => "Un client avec ce téléphone existe déjà :\n{$existingClient['prenom']} {$existingClient['nom']}",
            'existing_client' => [
                'id' => $existingClient['id'],
                'nom' => $existingClient['nom'],
                'prenom' => $existingClient['prenom']
            ]
        ]);
        exit;
    }
    
    // Insérer le nouveau client
    $insertStmt = $pdo->prepare("
        INSERT INTO clients (nom, prenom, telephone, date_creation) 
        VALUES (:nom, :prenom, :telephone, NOW())
    ");
    
    $insertStmt->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':telephone' => $telephone
    ]);
    
    // Récupérer l'ID du client créé
    $clientId = $pdo->lastInsertId();
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Client créé avec succès',
        'client_id' => $clientId,
        'client_info' => [
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone
        ]
    ]);
    
} catch (PDOException $e) {
    // Retourner une erreur de base de données
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Retourner une erreur générale
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}

// Nettoyer le buffer final
ob_end_flush();
?>

