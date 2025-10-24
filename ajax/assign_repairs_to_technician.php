<?php
session_start();
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Démarrer le buffer de sortie pour capturer les sorties indésirables
ob_start();

try {
    // Utiliser le système de configuration multi-magasin
    $config_path = realpath(__DIR__ . '/../config/database.php');
    
    if (!file_exists($config_path)) {
        throw new Exception('Fichier de configuration introuvable');
    }
    
    require_once $config_path;

    // Initialiser la session du magasin
    initializeShopSession();

    // Vérifier que la requête est en POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupérer les données JSON du POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Données invalides');
    }

    // Valider les données requises
    if (empty($data['technician_id']) || empty($data['repair_ids']) || !is_array($data['repair_ids'])) {
        throw new Exception('Technicien ou réparations non spécifiés');
    }

    $technician_id = (int)$data['technician_id'];
    $repair_ids = array_map('intval', $data['repair_ids']);

    // Obtenir la connexion à la base de données du magasin de l'utilisateur
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }

    // Commencer une transaction
    $shop_pdo->beginTransaction();

    $assigned_count = 0;
    $errors = [];

    try {
        // Vérifier que l'utilisateur existe
        $check_tech_query = "SELECT id, username, full_name, role FROM users WHERE id = ?";
        $check_tech_stmt = $shop_pdo->prepare($check_tech_query);
        $check_tech_stmt->execute([$technician_id]);
        $technician = $check_tech_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$technician) {
            throw new Exception('Utilisateur non trouvé');
        }

        // Préparer les requêtes pour l'attribution
        $update_repair_query = "
            UPDATE reparations 
            SET employe_id = ?, 
                date_modification = CURRENT_TIMESTAMP
            WHERE id = ? 
            AND archive = 'NON'
        ";
        $update_repair_stmt = $shop_pdo->prepare($update_repair_query);

        // Préparer la requête pour l'historique d'attribution (si la table existe)
        $check_attribution_table = "SHOW TABLES LIKE 'reparation_attributions'";
        $table_exists = $shop_pdo->query($check_attribution_table)->fetch();
        
        $insert_attribution_stmt = null;
        if ($table_exists) {
            $insert_attribution_query = "
                INSERT INTO reparation_attributions (
                    reparation_id,
                    employe_id,
                    date_debut,
                    statut_avant,
                    statut_apres,
                    est_principal
                ) VALUES (?, ?, NOW(), ?, ?, 1)
            ";
            $insert_attribution_stmt = $shop_pdo->prepare($insert_attribution_query);
        }

        // Traiter chaque réparation
        foreach ($repair_ids as $repair_id) {
            try {
                // Récupérer les informations actuelles de la réparation
                $get_repair_query = "SELECT id, statut, employe_id FROM reparations WHERE id = ? AND archive = 'NON'";
                $get_repair_stmt = $shop_pdo->prepare($get_repair_query);
                $get_repair_stmt->execute([$repair_id]);
                $current_repair = $get_repair_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$current_repair) {
                    $errors[] = "Réparation #$repair_id non trouvée";
                    continue;
                }

                $statut_avant = $current_repair['statut'];
                
                // Mettre à jour la réparation
                $update_repair_stmt->execute([$technician_id, $repair_id]);
                
                if ($update_repair_stmt->rowCount() > 0) {
                    $assigned_count++;
                    
                    // Enregistrer dans l'historique d'attribution si la table existe
                    if ($insert_attribution_stmt) {
                        $insert_attribution_stmt->execute([
                            $repair_id,
                            $technician_id,
                            $statut_avant,
                            $statut_avant // Le statut reste le même lors de l'attribution
                        ]);
                    }
                } else {
                    $errors[] = "Impossible d'attribuer la réparation #$repair_id";
                }

            } catch (Exception $e) {
                $errors[] = "Erreur avec la réparation #$repair_id : " . $e->getMessage();
            }
        }

        // Valider la transaction si au moins une attribution a réussi
        if ($assigned_count > 0) {
            $shop_pdo->commit();
        } else {
            $shop_pdo->rollback();
            throw new Exception('Aucune réparation n\'a pu être attribuée');
        }

        // Nettoyer le buffer avant d'envoyer la réponse JSON
        ob_clean();
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'assigned_count' => $assigned_count,
            'total_requested' => count($repair_ids),
            'technician' => $technician,
            'message' => "$assigned_count réparation(s) attribuée(s) avec succès"
        ];

        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }

        echo json_encode($response);

    } catch (Exception $e) {
        $shop_pdo->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Nettoyer le buffer en cas d'erreur
    ob_clean();
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'attribution : ' . $e->getMessage(),
        'assigned_count' => 0
    ]);
}
?>
