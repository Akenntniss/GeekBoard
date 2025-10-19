<?php
// Démarrer la session
session_start();

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier que l'ID de la commande est fourni
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de commande manquant']);
    exit;
}

$id = intval($_POST['id']);

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Connexion à la base de données du magasin non disponible');
    }
    
    // Récupérer les détails de la commande
    $sql = "
        SELECT cp.*, 
               c.nom as client_nom, 
               c.prenom as client_prenom, 
               c.telephone as client_telephone,
               f.nom as fournisseur_nom,
               DATE_FORMAT(cp.date_creation, '%d/%m/%Y à %H:%i') as date_creation_formatted,
               DATE_FORMAT(cp.date_commande, '%d/%m/%Y à %H:%i') as date_commande_formatted,
               DATE_FORMAT(cp.date_reception, '%d/%m/%Y à %H:%i') as date_reception_formatted
        FROM commandes_pieces cp
        LEFT JOIN clients c ON cp.client_id = c.id
        LEFT JOIN fournisseurs f ON cp.fournisseur_id = f.id
        WHERE cp.id = :id
    ";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$commande) {
        echo json_encode(['success' => false, 'message' => 'Commande non trouvée']);
        exit;
    }
    
    // Formatage des données pour JSON
    $commande = array_map(function($value) {
        // Éviter les problèmes d'encodage
        return is_string($value) ? $value : $value;
    }, $commande);
    
    // Renvoyer les résultats
    echo json_encode([
        'success' => true,
        'commande' => $commande
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération de la commande: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération de la commande: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception lors de la récupération de la commande: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?> 