<?php
/**
 * AJAX endpoint pour récupérer les infos rapides d'une réparation
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_config.php';

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Non connecté');
    }
    
    $repair_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($repair_id <= 0) {
        throw new Exception('ID réparation invalide');
    }
    
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Requête pour récupérer les infos de la réparation
    $stmt = $shop_pdo->prepare("
        SELECT 
            r.id,
            r.modele,
            r.description_probleme,
            r.statut,
            r.notes_techniques,
            r.photo_appareil,
            c.nom as client_nom,
            c.prenom as client_prenom
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    
    $stmt->execute([$repair_id]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        throw new Exception('Réparation non trouvée');
    }
    
    // Formatter le statut
    $status_labels = [
        'nouvelle_intervention' => 'Nouvelle intervention',
        'en_cours' => 'En cours',
        'diagnostic' => 'Diagnostic',
        'attente_piece' => 'Attente pièce',
        'reparation_en_cours' => 'Réparation en cours',
        'reparation_effectue' => 'Réparation effectuée',
        'restitue' => 'Restitué'
    ];
    
    $status_colors = [
        'nouvelle_intervention' => 'primary',
        'en_cours' => 'info',
        'diagnostic' => 'warning',
        'attente_piece' => 'secondary',
        'reparation_en_cours' => 'primary',
        'reparation_effectue' => 'success',
        'restitue' => 'success'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $repair['id'],
            'client_name' => trim(($repair['client_nom'] ?? '') . ' ' . ($repair['client_prenom'] ?? '')),
            'model' => $repair['modele'],
            'problem' => $repair['description_probleme'],
            'status' => $repair['statut'],
            'status_label' => $status_labels[$repair['statut']] ?? $repair['statut'],
            'status_color' => $status_colors[$repair['statut']] ?? 'secondary',
            'note' => $repair['notes_techniques'],
            'photo' => $repair['photo_appareil']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
