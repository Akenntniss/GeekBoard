<?php
// Désactiver l'affichage des erreurs PHP pour la production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Démarrer la session pour avoir accès à l'ID du magasin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer l'ID du magasin depuis les paramètres POST ou GET
$shop_id_from_request = $_POST['shop_id'] ?? $_GET['shop_id'] ?? null;

// Définir l'ID du magasin en session si fourni dans la requête
if ($shop_id_from_request) {
    $_SESSION['shop_id'] = $shop_id_from_request;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Données invalides');
    }

    // Vérifier les paramètres requis
    if (!isset($input['repair_ids']) || !isset($input['new_status']) || !is_array($input['repair_ids'])) {
        throw new Exception('Paramètres manquants ou invalides');
    }

    $repair_ids = array_map('intval', $input['repair_ids']);
    $new_status = cleanInput($input['new_status']);
    $send_sms = isset($input['send_sms']) ? (bool)$input['send_sms'] : false;

    if (empty($repair_ids)) {
        throw new Exception('Aucune réparation sélectionnée');
    }

    if (empty($new_status)) {
        throw new Exception('Nouveau statut requis');
    }

    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Erreur de connexion à la base de données');
    }

    // Vérifier que le statut existe (schéma réel)
    $status_check = $shop_pdo->prepare("SELECT id, nom FROM statuts WHERE code = ? AND est_actif = 1");
    $status_check->execute([$new_status]);
    $status_info = $status_check->fetch(PDO::FETCH_ASSOC);

    if (!$status_info) {
        throw new Exception('Statut invalide');
    }

    // Commencer une transaction
    try {
        $shop_pdo->beginTransaction();
        error_log("Transaction démarrée avec succès");
    } catch (Exception $tx_e) {
        error_log("Erreur lors du démarrage de la transaction : " . $tx_e->getMessage());
        throw new Exception("Impossible de démarrer la transaction : " . $tx_e->getMessage());
    }

    $updated_count = 0;
    $sms_sent_count = 0;
    $errors = [];
    $sms_queue = []; // Queue pour envoi async

    // Préparer les requêtes
    $update_stmt = $shop_pdo->prepare("
        UPDATE reparations 
        SET statut = ?, date_modification = NOW()
        WHERE id = ? AND (archive IS NULL OR archive = 'NON')
    ");

    $get_client_stmt = $shop_pdo->prepare("
        SELECT c.nom, c.prenom, c.telephone, r.description_probleme, r.type_appareil, r.modele
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");

    // Fonction pour récupérer le template SMS selon le statut
    $get_sms_template = function($status_id) use ($shop_pdo) {
        $template_stmt = $shop_pdo->prepare("
            SELECT contenu, variables 
            FROM sms_templates 
            WHERE statut_id = ? AND est_actif = 1 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $template_stmt->execute([$status_id]);
        return $template_stmt->fetch(PDO::FETCH_ASSOC);
    };

    // Fonction pour remplacer les variables dans le template SMS
    $replace_sms_variables = function($template, $client, $repair_data, $repair_id) {
        $variables = [
            '[CLIENT_NOM]' => $client['nom'] ?? '',
            '[CLIENT_PRENOM]' => $client['prenom'] ?? '',
            '[CLIENT]' => trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '')),
            '[APPAREIL_MODELE]' => $repair_data['modele'] ?? '',
            '[APPAREIL_TYPE]' => $repair_data['type_appareil'] ?? '',
            '[APPAREIL_MARQUE]' => $repair_data['modele'] ?? '', // Utiliser modele à la place de marque
            '[APPAREIL]' => $repair_data['type_appareil'] ?? '',
            '[REPARATION_ID]' => $repair_id,
            '[DESCRIPTION_PROBLEME]' => $repair_data['description_probleme'] ?? ''
        ];
        
        return str_replace(array_keys($variables), array_values($variables), $template);
    };

    $transaction_success = true;

    foreach ($repair_ids as $repair_id) {
        try {
            // Mettre à jour le statut
            $update_stmt->execute([$new_status, $repair_id]);
            
            if ($update_stmt->rowCount() > 0) {
                $updated_count++;

                // Envoyer SMS si demandé
                if ($send_sms) {
                    error_log("SMS demandé pour la réparation #$repair_id");
                    $get_client_stmt->execute([$repair_id]);
                    $client = $get_client_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$client) {
                        error_log("Aucun client trouvé pour la réparation #$repair_id");
                    } elseif (empty($client['telephone'])) {
                        error_log("Pas de numéro de téléphone pour le client de la réparation #$repair_id");
                    } else {
                        error_log("Client trouvé pour réparation #$repair_id: " . $client['prenom'] . " " . $client['nom'] . " - Tel: " . $client['telephone']);
                    }
                    
                    if ($client && !empty($client['telephone'])) {
                        // Récupérer le template SMS pour ce statut
                        error_log("Recherche template SMS pour statut ID: " . $status_info['id']);
                        $sms_template = $get_sms_template($status_info['id']);
                        
                        if ($sms_template) {
                            error_log("Template SMS trouvé: " . substr($sms_template['contenu'], 0, 50) . "...");
                        } else {
                            error_log("Aucun template SMS trouvé pour le statut ID: " . $status_info['id']);
                        }
                        
                        $message = '';
                        if ($sms_template && !empty($sms_template['contenu'])) {
                            // Utiliser le template prédéfini avec substitution des variables
                            $message = $replace_sms_variables(
                                $sms_template['contenu'], 
                                $client, 
                                $client, // Les données de réparation sont dans le même array
                                $repair_id
                            );
                            error_log("Message SMS généré à partir du template: " . substr($message, 0, 100) . "...");
                        } else {
                            // Message par défaut si aucun template n'est trouvé
                            $message = "Bonjour " . $client['prenom'] . " " . $client['nom'] . 
                                     ", votre réparation a été mise à jour. Nouveau statut: " . 
                                     $status_info['nom'] . ". Cordialement.";
                            error_log("Message SMS par défaut généré: " . substr($message, 0, 100) . "...");
                        }
                        
                        // Stocker les données SMS pour envoi async
                        $sms_queue[] = [
                            'telephone' => $client['telephone'],
                            'message' => $message,
                            'repair_id' => $repair_id,
                            'client_name' => $client['prenom'] . ' ' . $client['nom']
                        ];
                        error_log("SMS ajouté à la queue pour réparation #$repair_id");
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Erreur lors du traitement de la réparation #$repair_id: " . $e->getMessage());
            $errors[] = "Erreur pour la réparation #$repair_id: " . $e->getMessage();
            $transaction_success = false;
        }
    }

    // Valider la transaction
    error_log("État de la transaction : success=" . ($transaction_success ? 'true' : 'false'));
    
    if ($transaction_success && $shop_pdo->inTransaction()) {
        $shop_pdo->commit();
        error_log("Transaction commitée avec succès");
        
        // === ENVOI NOTIFICATION PUSH ===
        if ($updated_count > 0) {
            try {
                require_once __DIR__ . '/../includes/PushNotifications.php';
                $pushService = new PushNotifications($shop_pdo);
                
                $title = "MAJ Statut par lot effectuée";
                $body = "$updated_count réparation(s) mise(s) à jour";
                
                // Envoyer la notification à l'utilisateur actuel
                $userId = $_SESSION['user_id'] ?? 1;
                $pushService->sendToUser($userId, $title, $body, [
                    'url' => '/index.php?page=reparations',
                    'tag' => 'batch-update'
                ]);
                error_log("NOTIFICATION: Batch update notification sent");
            } catch (Exception $e) {
                error_log("NOTIFICATION ERROR (batch): " . $e->getMessage());
            }
        }
    } elseif ($shop_pdo->inTransaction()) {
        $shop_pdo->rollback();
        error_log("Transaction annulée à cause d'erreurs");
        throw new Exception("Erreurs lors de la mise à jour des réparations");
    }

    // === RÉPONDRE IMMÉDIATEMENT AU CLIENT ===
    $response = [
        'success' => true,
        'updated_count' => $updated_count,
        'total_requested' => count($repair_ids),
        'new_status_label' => $status_info['nom'],
        'sms_sent' => $send_sms,
        'sms_sent_count' => count($sms_queue),
        'message' => "$updated_count réparation(s) mise(s) à jour avec succès"
    ];

    if (!empty($errors)) {
        $response['warnings'] = $errors;
        $response['message'] .= ". " . count($errors) . " erreur(s) rencontrée(s).";
    }

    if ($send_sms && !empty($sms_queue)) {
        $response['message'] .= ". " . count($sms_queue) . " SMS en cours d'envoi...";
    }

    $json_response = json_encode($response);
    
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    header('Connection: close');
    header('Content-Length: ' . strlen($json_response));
    echo $json_response;
    
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        flush();
    }
    
    // === ENVOI SMS EN ARRIÈRE-PLAN ===
    if (!empty($sms_queue)) {
        ignore_user_abort(true);
        set_time_limit(120);
        
        if (!function_exists('send_sms')) {
            require_once __DIR__ . '/../includes/sms_functions.php';
        }
        
        foreach ($sms_queue as $sms_item) {
            try {
                $sms_result = send_sms(
                    $sms_item['telephone'], 
                    $sms_item['message'], 
                    'repair_status', 
                    $sms_item['repair_id'], 
                    $_SESSION['user_id'] ?? null
                );
                error_log("SMS async envoyé pour réparation #{$sms_item['repair_id']}: " . ($sms_result['success'] ? 'OK' : 'ERREUR'));
            } catch (Exception $e) {
                error_log("Exception SMS async: " . $e->getMessage());
            }
            usleep(100000); // 100ms pause entre les SMS
        }
    }
    
    exit;

} catch (Exception $e) {
    error_log("Erreur dans update_batch_status.php : " . $e->getMessage());
    
    try {
        if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
            $shop_pdo->rollback();
        }
    } catch (Exception $rollback_e) {
        error_log("Erreur rollback : " . $rollback_e->getMessage());
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'updated_count' => isset($updated_count) ? $updated_count : 0,
        'total_requested' => isset($repair_ids) ? count($repair_ids) : 0
    ]);
}
?>