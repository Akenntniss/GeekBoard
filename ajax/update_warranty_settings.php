<?php
/**
 * Mise à jour des paramètres de garantie
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
// Initialiser la session shop
initializeShopSession();
header('Content-Type: application/json');
try {
    $shop_pdo = getShopDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Données JSON invalides');
        }
        
        // Paramètres de garantie à traiter
        $warranty_params = [
            'garantie_active' => [
                'value' => isset($input['garantie_active']) ? ($input['garantie_active'] ? '1' : '0') : '0',
                'description' => 'Activer/désactiver le système de garantie (1=actif, 0=inactif)'
            ],
            'garantie_duree_defaut' => [
                'value' => isset($input['garantie_duree_defaut']) ? max(1, intval($input['garantie_duree_defaut'])) : 90,
                'description' => 'Durée par défaut de la garantie en jours'
            ],
            'garantie_description_defaut' => [
                'value' => isset($input['garantie_description_defaut']) ? cleanInput($input['garantie_description_defaut']) : 'Garantie pièces et main d\'œuvre',
                'description' => 'Description par défaut de la garantie'
            ],
            'garantie_auto_creation' => [
                'value' => isset($input['garantie_auto_creation']) ? ($input['garantie_auto_creation'] ? '1' : '0') : '1',
                'description' => 'Création automatique de la garantie quand réparation effectuée (1=auto, 0=manuel)'
            ],
            'garantie_notification_expiration' => [
                'value' => isset($input['garantie_notification_expiration']) ? max(0, intval($input['garantie_notification_expiration'])) : 7,
                'description' => 'Nombre de jours avant expiration pour notifier (0=pas de notification)'
            ]
        ];
        
        $shop_pdo->beginTransaction();
        
        foreach ($warranty_params as $cle => $param) {
            // Vérifier si le paramètre existe déjà
            $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM parametres WHERE cle = ?");
            $stmt->execute([$cle]);
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                $stmt = $shop_pdo->prepare("UPDATE parametres SET valeur = ?, description = ? WHERE cle = ?");
                $stmt->execute([$param['value'], $param['description'], $cle]);
            } else {
                $stmt = $shop_pdo->prepare("INSERT INTO parametres (cle, valeur, description) VALUES (?, ?, ?)");
                $stmt->execute([$cle, $param['value'], $param['description']]);
            }
        }
        
        $shop_pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Paramètres de garantie mis à jour avec succès'
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Récupérer les paramètres de garantie actuels
        $stmt = $shop_pdo->prepare("
            SELECT cle, valeur, description 
            FROM parametres 
            WHERE cle IN ('garantie_active', 'garantie_duree_defaut', 'garantie_description_defaut', 'garantie_auto_creation', 'garantie_notification_expiration')
        ");
        $stmt->execute();
        $params = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organiser les résultats
        $warranty_settings = [];
        foreach ($params as $param) {
            $warranty_settings[$param['cle']] = [
                'value' => $param['valeur'],
                'description' => $param['description']
            ];
        }
        
        // Valeurs par défaut si les paramètres n'existent pas encore
        $defaults = [
            'garantie_active' => ['value' => '1', 'description' => 'Activer/désactiver le système de garantie'],
            'garantie_duree_defaut' => ['value' => '90', 'description' => 'Durée par défaut de la garantie en jours'],
            'garantie_description_defaut' => ['value' => 'Garantie pièces et main d\'œuvre', 'description' => 'Description par défaut de la garantie'],
            'garantie_auto_creation' => ['value' => '1', 'description' => 'Création automatique de la garantie'],
            'garantie_notification_expiration' => ['value' => '7', 'description' => 'Jours avant expiration pour notification']
        ];
        
        foreach ($defaults as $key => $default) {
            if (!isset($warranty_settings[$key])) {
                $warranty_settings[$key] = $default;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $warranty_settings
        ]);
        
    } else {
        throw new Exception('Méthode non autorisée');
    }
    
} catch (Exception $e) {
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollback();
    }
    
    error_log("Erreur paramètres garantie: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>
