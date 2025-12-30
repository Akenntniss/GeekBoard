<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Désactiver l'affichage des erreurs pour éviter la corruption JSON

// Lire les données depuis JSON ou POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Si les données JSON ne sont pas disponibles, utiliser $_POST
if (!$data) {
    $data = $_POST;
}

// Récupérer les données
$repair_id = $data['repair_id'] ?? '';
$status_id = $data['status_id'] ?? '';
$send_sms = $data['send_sms'] ?? false;
$shop_id = $data['shop_id'] ?? $_GET['shop_id'] ?? '';

// Validation des données
if (empty($repair_id) || empty($status_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de réparation et nouveau statut requis'
    ]);
    exit;
}

// Validation de l'ID du magasin
if (empty($shop_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID du magasin requis'
    ]);
    exit;
}

// Validation que les IDs sont numériques
if (!is_numeric($repair_id) || !is_numeric($status_id) || !is_numeric($shop_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'IDs invalides'
    ]);
    exit;
}

try {
    // Utiliser le système de configuration multi-magasin
    $config_path = realpath(__DIR__ . '/../config/database.php');
    $session_config_path = realpath(__DIR__ . '/../config/session_config.php');
    
    if (!file_exists($config_path)) {
        throw new Exception('Fichier de configuration introuvable.');
    }

    // Inclure la configuration de base de données et de session
    require_once $config_path;
    if (file_exists($session_config_path)) {
        require_once $session_config_path;
        // Initialiser la session magasin si possible
        if (function_exists('initializeShopSession')) {
            initializeShopSession();
        }
    }
    
    // Inclure les fonctions des paramètres d'entreprise
    $company_settings_path = realpath(__DIR__ . '/../includes/company_settings.php');
    if (file_exists($company_settings_path)) {
        require_once $company_settings_path;
    }
    
    // Utiliser la connexion à la base de données du magasin spécifique par son ID
    $pdo = getShopDBConnectionById($shop_id);
    
    if (!$pdo) {
        throw new Exception('Erreur de connexion à la base de données du magasin');
    }
    
    // Debug: Vérifier quelle base de données est utilisée
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    error_log("DEBUG SMS: Base de données utilisée: $dbName pour shop_id: $shop_id");
    
    // Vérifier que la réparation existe
    $checkSQL = "SELECT id, statut FROM reparations WHERE id = ?";
    $checkStmt = $pdo->prepare($checkSQL);
    $checkStmt->execute([$repair_id]);
    $reparation = $checkStmt->fetch();

    if (!$reparation) {
        echo json_encode([
            'success' => false,
            'message' => 'Réparation non trouvée'
        ]);
        exit;
    }

    // Récupérer le code du statut
    $statusCodeSQL = "SELECT code FROM statuts WHERE id = ?";
    $statusCodeStmt = $pdo->prepare($statusCodeSQL);
    $statusCodeStmt->execute([$status_id]);
    $status_code = $statusCodeStmt->fetchColumn();
    
    if (!$status_code) {
        echo json_encode([
            'success' => false,
            'message' => 'Code de statut non trouvé'
        ]);
        exit;
    }

    // Traitement spécial pour le statut "Retard de livraison"
    if ($status_code === 'retard_livraison') {
        // Pour "Retard de livraison", on envoie seulement le SMS sans changer le statut
        error_log("Statut 'Retard de livraison' détecté - Envoi SMS uniquement sans changement de statut");
        
        // Si l'envoi SMS est activé, on l'envoie automatiquement
        $sms_sent = false;
        $sms_message = '';
        
        if ($send_sms) {
            // Inclure les fonctions SMS
            $sms_functions_path = realpath(__DIR__ . '/../includes/sms_functions.php');
            if (file_exists($sms_functions_path)) {
                require_once $sms_functions_path;
                
                // Récupérer les données de la réparation et du client
                $repairDataSQL = "SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone 
                                  FROM reparations r 
                                  JOIN clients c ON r.client_id = c.id 
                                  WHERE r.id = ?";
                $repairStmt = $pdo->prepare($repairDataSQL);
                $repairStmt->execute([$repair_id]);
                $repairData = $repairStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($repairData && !empty($repairData['client_telephone'])) {
                    // Récupérer le template SMS pour "Retard de livraison"
                    $templateSQL = "SELECT contenu FROM sms_templates WHERE code = 'retard_livraison' AND est_actif = 1";
                    $templateStmt = $pdo->prepare($templateSQL);
                    $templateStmt->execute();
                    $template = $templateStmt->fetchColumn();
                    
                    if ($template) {
                        // Générer l'URL de suivi dynamique
                        $suivi_url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'servo.tools') . '/suivi.php?id=' . ($repairData['id'] ?? '');
                        
                        // Récupérer les paramètres d'entreprise
                        $company_name = 'Maison du Geek';
                        $company_phone = '08 95 79 59 33';
                        $company_address = '';
                        
                        try {
                            $stmt_company = $pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone', 'company_address')");
                            $stmt_company->execute();
                            $company_params = $stmt_company->fetchAll(PDO::FETCH_KEY_PAIR);
                            
                            if (!empty($company_params['company_name'])) {
                                $company_name = $company_params['company_name'];
                            }
                            if (!empty($company_params['company_phone'])) {
                                $company_phone = $company_params['company_phone'];
                            }
                            if (!empty($company_params['company_address'])) {
                                $company_address = $company_params['company_address'];
                            }
                        } catch (Exception $e) {
                            error_log("Erreur récupération paramètres entreprise: " . $e->getMessage());
                        }
                        
                        // Remplacer les variables dans le template
                        $message = str_replace([
                            // Variables client
                            '[CLIENT_NOM]', '[CLIENT_PRENOM]', '[CLIENT_TELEPHONE]',
                            // Variables réparation
                            '[REPARATION_ID]', '[APPAREIL_TYPE]', '[APPAREIL_MARQUE]', '[APPAREIL_MODELE]',
                            '[DATE_RECEPTION]', '[DATE_FIN_PREVUE]', '[PRIX]', '[NOTES_TECHNIQUES]',
                            // Variables magasin
                            '[LIEN]', '[URL_SUIVI]', '[URL_DEVIS]',
                            '[COMPANY_NAME]', '[COMPANY_PHONE]', '[COMPANY_ADDRESS]',
                            '[COMPANY_NUMBER]', '[COMPANY_HOURS]'
                        ], [
                            // Valeurs client
                            $repairData['client_nom'] ?? '',
                            $repairData['client_prenom'] ?? '',
                            $repairData['client_telephone'] ?? '',
                            // Valeurs réparation
                            $repairData['id'] ?? '',
                            $repairData['type_appareil'] ?? '',
                            $repairData['marque'] ?? '',
                            $repairData['modele'] ?? '',
                            $repairData['date_reception'] ? date('d/m/Y', strtotime($repairData['date_reception'])) : '',
                            $repairData['date_fin_prevue'] ? date('d/m/Y', strtotime($repairData['date_fin_prevue'])) : '',
                            !empty($repairData['prix']) ? number_format((float)$repairData['prix'], 2, ',', ' ') . '€' : '',
                            $repairData['notes_techniques'] ?? '',
                            // Valeurs URLs et magasin
                            $suivi_url,
                            $suivi_url,
                            'https://' . ($_SERVER['HTTP_HOST'] ?? 'servo.tools') . '/devis_client.php?id=' . ($repairData['id'] ?? ''),
                            $company_name,
                            $company_phone,
                            $company_address,
                            $company_params['company_number'] ?? '',
                            $company_params['company_hours'] ?? ''
                        ], $template);
                        
                        // Envoyer le SMS
                        try {
                            $sms_result = send_sms(
                                $repairData['client_telephone'], 
                                $message, 
                                'retard_livraison', 
                                $repairData['client_id'], 
                                $_SESSION['user_id'] ?? 1
                            );
                            
                            if ($sms_result['success']) {
                                $sms_sent = true;
                                $sms_message = 'SMS de notification "Retard de livraison" envoyé avec succès';
                                error_log("SMS Retard de livraison envoyé avec succès pour la réparation $repair_id");
                            } else {
                                $sms_message = 'Erreur lors de l\'envoi du SMS: ' . ($sms_result['message'] ?? 'Erreur inconnue');
                                error_log("Erreur envoi SMS Retard de livraison: " . $sms_message);
                            }
                        } catch (Exception $e) {
                            $sms_message = 'Erreur lors de l\'envoi du SMS: ' . $e->getMessage();
                            error_log("Exception envoi SMS Retard de livraison: " . $e->getMessage());
                        }
                    } else {
                        $sms_message = 'Template SMS "Retard de livraison" non trouvé';
                        error_log($sms_message);
                    }
                } else {
                    $sms_message = 'Données de réparation ou numéro de téléphone client manquants';
                    error_log($sms_message);
                }
            } else {
                $sms_message = 'Fonctions SMS non disponibles';
                error_log($sms_message);
            }
        }
        
        // Retourner le succès sans changement de statut
        echo json_encode([
            'success' => true,
            'message' => 'Notification "Retard de livraison" traitée - Aucun changement de statut effectué',
            'data' => [
                'repair_id' => $repair_id,
                'old_status' => $reparation['statut'],
                'new_status' => $reparation['statut'], // Statut inchangé
                'shop_id' => $shop_id,
                'send_sms' => $send_sms,
                'sms_sent' => $sms_sent,
                'sms_message' => $sms_message,
                'status_changed' => false,
                'notification_type' => 'retard_livraison',
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
        exit;
    }

    // Traitement normal pour tous les autres statuts
    $updateSQL = "UPDATE reparations SET statut = ?, date_modification = NOW() WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSQL);
    $result = $updateStmt->execute([$status_code, $repair_id]);

    if ($result) {
        // Variables pour l'envoi SMS
        $sms_sent = false;
        $sms_message = '';
        $sms_queued = false;
        
        // Préparer les données pour l'envoi SMS en arrière-plan
        $repairData = null;
        $template = null;
        $message = null;
        
        // Si l'envoi SMS est activé, préparer les données
        if ($send_sms) {
            error_log("Envoi SMS demandé pour le statut: " . $status_code);
            
            // Inclure les fonctions SMS
            $sms_functions_path = realpath(__DIR__ . '/../includes/sms_functions.php');
            if (file_exists($sms_functions_path)) {
                require_once $sms_functions_path;
                
                // Récupérer les données de la réparation et du client
                $repairDataSQL = "SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone 
                                  FROM reparations r 
                                  JOIN clients c ON r.client_id = c.id 
                                  WHERE r.id = ?";
                $repairStmt = $pdo->prepare($repairDataSQL);
                $repairStmt->execute([$repair_id]);
                $repairData = $repairStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($repairData && !empty($repairData['client_telephone'])) {
                    // Récupérer le template SMS pour ce statut
                    $templateSQL = "SELECT contenu FROM sms_templates WHERE code = ? AND est_actif = 1";
                    $templateStmt = $pdo->prepare($templateSQL);
                    $templateStmt->execute([$status_code]);
                    $template = $templateStmt->fetchColumn();
                    
                    if (!$template) {
                        $templateSQL = "SELECT contenu FROM sms_templates WHERE statut_id = ? AND est_actif = 1";
                        $templateStmt = $pdo->prepare($templateSQL);
                        $templateStmt->execute([$status_id]);
                        $template = $templateStmt->fetchColumn();
                    }
                    
                    if ($template) {
                        // Préparer le message
                        if (function_exists('replaceSmsVariables')) {
                            $message = replaceSmsVariables($template, $shop_id, $repairData);
                        } else {
                            $host = $_SERVER['HTTP_HOST'] ?? 'servo.tools';
                            $suivi_url = 'https://' . $host . '/suivi.php?id=' . $repairData['id'];
                            
                            // Récupérer les paramètres d'entreprise
                            $company_name = 'Maison du Geek';
                            $company_phone = '08 95 79 59 33';
                            $company_address = '';
                            
                            try {
                                $stmt_company = $pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone', 'company_address')");
                                $stmt_company->execute();
                                $company_params = $stmt_company->fetchAll(PDO::FETCH_KEY_PAIR);
                                
                                if (!empty($company_params['company_name'])) {
                                    $company_name = $company_params['company_name'];
                                }
                                if (!empty($company_params['company_phone'])) {
                                    $company_phone = $company_params['company_phone'];
                                }
                                if (!empty($company_params['company_address'])) {
                                    $company_address = $company_params['company_address'];
                                }
                            } catch (Exception $e) {
                                error_log("Erreur récupération paramètres entreprise: " . $e->getMessage());
                            }
                            
                            $message = str_replace([
                                // Variables client
                                '[CLIENT_NOM]', '[CLIENT_PRENOM]', '[CLIENT_TELEPHONE]',
                                // Variables réparation
                                '[REPARATION_ID]', '[APPAREIL_TYPE]', '[APPAREIL_MARQUE]', '[APPAREIL_MODELE]',
                                '[DATE_RECEPTION]', '[DATE_FIN_PREVUE]', '[PRIX]', '[NOTES_TECHNIQUES]',
                                // Variables magasin
                                '[COMPANY_NAME]', '[COMPANY_PHONE]', '[COMPANY_ADDRESS]',
                                '[COMPANY_NUMBER]', '[COMPANY_HOURS]',
                                // URLs
                                '[URL_SUIVI]', '[URL_DEVIS]', '[LIEN]'
                            ], [
                                // Valeurs client
                                $repairData['client_nom'] ?? '', 
                                $repairData['client_prenom'] ?? '',
                                $repairData['client_telephone'] ?? '',
                                // Valeurs réparation
                                $repairData['id'] ?? '', 
                                $repairData['type_appareil'] ?? '',
                                $repairData['marque'] ?? '', 
                                $repairData['modele'] ?? '',
                                !empty($repairData['date_reception']) ? date('d/m/Y', strtotime($repairData['date_reception'])) : '',
                                !empty($repairData['date_fin_prevue']) ? date('d/m/Y', strtotime($repairData['date_fin_prevue'])) : '',
                                !empty($repairData['prix']) ? number_format((float)$repairData['prix'], 2, ',', ' ') . '€' : '',
                                $repairData['notes_techniques'] ?? '',
                                // Valeurs magasin
                                $company_name, 
                                $company_phone, 
                                $company_address,
                                $company_params['company_number'] ?? '',
                                $company_params['company_hours'] ?? '',
                                // Valeurs URLs
                                $suivi_url, 
                                'https://' . $host . '/devis_client.php?id=' . $repairData['id'],
                                $suivi_url
                            ], $template);
                        }
                        $sms_queued = true;
                        $sms_message = 'SMS en cours d\'envoi...';
                    } else {
                        $sms_message = 'Template SMS non trouvé pour le statut: ' . $status_code;
                    }
                } else {
                    $sms_message = 'Numéro de téléphone client manquant';
                }
            }
        }
        
        // === RÉPONDRE AU CLIENT IMMÉDIATEMENT ===
        $response = json_encode([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'data' => [
                'repair_id' => $repair_id,
                'old_status' => $reparation['statut'],
                'new_status' => $status_code,
                'shop_id' => $shop_id,
                'send_sms' => $send_sms,
                'sms_sent' => $sms_queued, // Indique que le SMS est en cours
                'sms_queued' => $sms_queued,
                'sms_message' => $sms_message,
                'status_changed' => true,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
        
        // Fermer la connexion avec le client AVANT d'envoyer le SMS
        header('Connection: close');
        header('Content-Length: ' . strlen($response));
        echo $response;
        
        // Forcer l'envoi de la réponse
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            ob_end_flush();
            flush();
        }
        
        // === ENVOI SMS EN ARRIÈRE-PLAN ===
        if ($send_sms && $sms_queued && $repairData && $message) {
            ignore_user_abort(true);
            set_time_limit(30);
            
            try {
                if (function_exists('send_sms')) {
                    error_log("SMS ASYNC: Envoi vers " . $repairData['client_telephone']);
                    $sms_result = send_sms(
                        $repairData['client_telephone'], 
                        $message, 
                        'repair_status_change', 
                        $repair_id, 
                        $_SESSION['user_id'] ?? null
                    );
                    error_log("SMS ASYNC: Résultat = " . ($sms_result['success'] ? 'OK' : 'ERREUR: ' . ($sms_result['message'] ?? '')));
                }
            } catch (Exception $e) {
                error_log("SMS ASYNC ERROR: " . $e->getMessage());
            }
        }
        
        // === ENVOI NOTIFICATION PUSH EN ARRIÈRE-PLAN ===
        try {
            require_once __DIR__ . '/../includes/NotificationService.php';
            $employeId = $repairData['employe_id'] ?? null;
            NotificationService::notifyRepairStatusChange($repair_id, $reparation['statut'], $status_code, $employeId);
            error_log("NOTIFICATION: Push notification sent for repair $repair_id status change");
        } catch (Exception $e) {
            error_log("NOTIFICATION ERROR: " . $e->getMessage());
        }
        
        exit; // Important: on a déjà répondu au client
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du statut'
        ]);
    }

} catch (PDOException $e) {
    error_log("Erreur PDO dans update_repair_status.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);

} catch (Exception $e) {
    error_log("Erreur générale dans update_repair_status.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}
?> 