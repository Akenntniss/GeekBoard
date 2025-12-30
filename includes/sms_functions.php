<?php
/**
 * Fonctions SMS unifiées pour GeekBoard
 * Migration vers nouvelle API SMS Gateway (http://168.231.85.4:3001/api)
 * 
 * Ce fichier remplace progressivement les anciens appels à l'API sms-gate.app
 */

// Inclure la nouvelle classe SMS
require_once __DIR__ . '/../classes/NewSmsService.php';

// Inclure les fonctions de facturation SMS
require_once __DIR__ . '/sms_billing_functions.php';

/**
 * Fonction principale d'envoi de SMS
 * Compatible avec l'ancienne fonction mais utilise la nouvelle API
 * 
 * @param string $phoneNumber Numéro de téléphone du destinataire
 * @param string $message Message à envoyer
 * @param string $reference_type Type de référence (optionnel): 'status', 'devis', 'relance', 'manual', etc.
 * @param int $reference_id ID de référence (optionnel)
 * @param int $user_id ID utilisateur (optionnel)
 * @return array Résultat avec success (bool) et message (string)
 */
function send_sms($phoneNumber, $message, $reference_type = null, $reference_id = null, $user_id = null) {
    try {
        // Log de l'appel pour débogage
        log_sms_call($phoneNumber, $message, $reference_type, $reference_id);
        
        // Validation des paramètres
        if (empty($phoneNumber) || empty($message)) {
            return [
                'success' => false,
                'message' => 'Numéro de téléphone et message obligatoires'
            ];
        }
        
        // ============================================
        // VÉRIFICATION DU QUOTA SMS
        // ============================================
        $shopId = $_SESSION['shop_id'] ?? null;
        $quotaCheck = null;
        
        if ($shopId) {
            $quotaCheck = checkSMSQuota($shopId);
            
            if (!$quotaCheck['allowed']) {
                error_log("SMS BLOQUÉ - Shop $shopId: " . $quotaCheck['reason']);
                return [
                    'success' => false,
                    'message' => $quotaCheck['reason'],
                    'quota_blocked' => true
                ];
            }
        }
        // ============================================
        
        // Protection contre les doublons
        require_once __DIR__ . '/../classes/SmsDeduplication.php';
        $deduplication = new SmsDeduplication();
        
        $statusId = ($reference_type === 'repair_status') ? $reference_id : null;
        $repairId = ($reference_type === 'repair') ? $reference_id : null;
        
        if (!$deduplication->canSendSms($phoneNumber, $message, $statusId, $repairId)) {
            return [
                'success' => false,
                'message' => 'SMS identique envoyé récemment - Doublon bloqué',
                'duplicate_blocked' => true
            ];
        }
        
        // Limiter la longueur du message (16000 caractères max pour SMS longs)
        if (strlen($message) > 16000) {
            $message = substr($message, 0, 15997) . '...';
        }
        
        // Créer une instance du service SMS
        $smsService = new NewSmsService();
        
        // Envoyer le SMS
        $result = $smsService->sendSms($phoneNumber, $message);
        
        // ============================================
        // INCRÉMENTER LE COMPTEUR SI ENVOI RÉUSSI
        // ============================================
        if ($result['success'] && $shopId && $quotaCheck) {
            // Déterminer le type de SMS pour les statistiques
            $smsType = determineSmsType($reference_type);
            incrementSMSUsage($shopId, $quotaCheck['source'], $smsType);
        }
        // ============================================
        
        // Enregistrer le résultat dans la base de données si possible
        if (function_exists('log_sms_to_database')) {
            log_sms_to_database($phoneNumber, $message, $result, $reference_type, $reference_id, $user_id);
        }
        
        // Retourner le résultat dans le format attendu par l'ancien code
        return [
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'http_code' => $result['http_code'] ?? null
        ];
        
    } catch (Exception $e) {
        error_log("Erreur dans send_sms: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur technique lors de l\'envoi du SMS: ' . $e->getMessage()
        ];
    }
}

/**
 * Détermine le type de SMS basé sur le reference_type
 */
function determineSmsType($reference_type) {
    if (!$reference_type) return 'other';
    
    $typeMap = [
        'repair_status' => 'status',
        'status' => 'status',
        'devis' => 'devis',
        'envoi_devis' => 'devis',
        'relance_devis' => 'relance',
        'relance_reparation' => 'relance',
        'relance' => 'relance',
        'manual' => 'manual',
        'manual_sms' => 'manual',
        'campaign' => 'campaign',
        'campagne' => 'campaign'
    ];
    
    return $typeMap[$reference_type] ?? 'other';
}

/**
 * Fonction d'envoi SMS directe (pour compatibilité avec certains fichiers)
 * 
 * @param string $telephone Numéro de téléphone
 * @param string $message Message à envoyer
 * @return array Résultat de l'envoi
 */
function send_sms_direct($telephone, $message) {
    return send_sms($telephone, $message);
}

/**
 * Log des appels SMS pour débogage
 * 
 * @param string $phoneNumber Numéro de téléphone
 * @param string $message Message
 * @param string $reference_type Type de référence
 * @param int $reference_id ID de référence
 */
function log_sms_call($phoneNumber, $message, $reference_type = null, $reference_id = null) {
    $logFile = __DIR__ . '/../logs/sms_calls_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] APPEL SMS - Dest: $phoneNumber, Ref: $reference_type:$reference_id, Message: " . substr($message, 0, 50) . "...\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Enregistre le SMS dans la base de données
 * 
 * @param string $phoneNumber Numéro de téléphone
 * @param string $message Message
 * @param array $result Résultat de l'envoi
 * @param string $reference_type Type de référence
 * @param int $reference_id ID de référence  
 * @param int $user_id ID utilisateur
 */
function log_sms_to_database($phoneNumber, $message, $result, $reference_type = null, $reference_id = null, $user_id = null) {
    try {
        // Utiliser directement le SubdomainDatabaseDetector pour être sûr d'avoir la bonne base
        require_once(__DIR__ . '/../config/subdomain_database_detector.php');
        $detector = new SubdomainDatabaseDetector();
        $shop_pdo = $detector->getConnection();
        
        if (!$shop_pdo) {
            error_log("Impossible d'obtenir la connexion database pour log SMS via SubdomainDetector");
            return;
        }
        
        // Log pour debug - quelle base de données est utilisée
        $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
        $db_result = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("SMS: Enregistrement dans la base: " . ($db_result['db_name'] ?? 'Inconnue'));
        
        // Vérifier la structure de la table sms_logs existante
        $checkColumns = $shop_pdo->query("SHOW COLUMNS FROM sms_logs");
        $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
        
        // Adapter à la structure existante de la table
        if (in_array('reference_type', $columns)) {
            // Nouvelle structure (celle que nous voulons)
            $status = $result['success'] ? 'sent' : 'failed';
            $response_data = json_encode($result);
            
            $stmt = $shop_pdo->prepare("
                INSERT INTO sms_logs (
                    recipient, message, status, response, 
                    reference_type, reference_id, user_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $phoneNumber, $message, $status, $response_data,
                $reference_type, $reference_id, $user_id
            ]);
        } else {
            // Structure existante (ancienne)
            $status_int = $result['success'] ? 1 : 0; // 1 = envoyé, 0 = échoué
            $response_data = json_encode($result);
            
            $stmt = $shop_pdo->prepare("
                INSERT INTO sms_logs (
                    recipient, message, status, response, 
                    reparation_id, date_envoi
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $phoneNumber, $message, $status_int, $response_data,
                $reference_id
            ]);
        }
        
        error_log("SMS enregistré en base de données avec succès");
        
        // NOUVEAU : Enregistrer aussi dans reparation_sms pour la page d'historique
        // Seulement si c'est lié à une réparation/devis
        if ($reference_type && $reference_id) {
            try {
                $actual_reparation_id = null;
                
                // Récupérer le reparation_id selon le type de référence
                if ($reference_type === 'envoi_devis' || str_contains($reference_type, 'relance_devis')) {
                    // Pour les devis, récupérer le reparation_id à partir du devis_id
                    $stmt_devis = $shop_pdo->prepare("SELECT reparation_id FROM devis WHERE id = ?");
                    $stmt_devis->execute([$reference_id]);
                    $devis_result = $stmt_devis->fetch(PDO::FETCH_ASSOC);
                    $actual_reparation_id = $devis_result['reparation_id'] ?? null;
                } elseif (str_contains($reference_type, 'relance_reparation') || $reference_type === 'manual_sms' || $reference_type === 'changement_statut') {
                    // Pour les relances de réparation, SMS manuels ou changements de statut, utiliser directement l'ID
                    $actual_reparation_id = $reference_id;
                }
                
                if ($actual_reparation_id) {
                    // Déterminer le statut_id (1 = envoyé, 0 = échoué)
                    $statut_envoi = $result['success'] ? 1 : 0;
                    
                    // Template par défaut (ou récupérer un template existant)
                    $template_id = 1; // Template par défaut
                    
                    // Enregistrer dans reparation_sms
                    $stmt_reparation = $shop_pdo->prepare("
                        INSERT INTO reparation_sms (
                            reparation_id, template_id, telephone, message, statut_id, date_envoi
                        ) VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $stmt_reparation->execute([
                        $actual_reparation_id, $template_id, $phoneNumber, $message, $statut_envoi
                    ]);
                    
                    error_log("SMS également enregistré dans reparation_sms (reparation_id: $actual_reparation_id) pour la page d'historique");
                }
                
            } catch (Exception $e_reparation) {
                error_log("Erreur lors de l'enregistrement dans reparation_sms: " . $e_reparation->getMessage());
                // Ne pas faire échouer l'ensemble, juste log l'erreur
            }
        }
        
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement SMS en BDD: " . $e->getMessage());
    }
}

/**
 * Crée la table sms_logs si elle n'existe pas
 * 
 * @param PDO $shop_pdo Connexion à la base de données de la boutique
 */
function create_sms_logs_table($shop_pdo) {
    try {
        $sql = "
            CREATE TABLE IF NOT EXISTS sms_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipient VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
                response TEXT,
                reference_type VARCHAR(50),
                reference_id INT,
                user_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_recipient (recipient),
                INDEX idx_reference (reference_type, reference_id),
                INDEX idx_status (status),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $shop_pdo->exec($sql);
        error_log("Table sms_logs créée avec succès");
        
    } catch (Exception $e) {
        error_log("Erreur lors de la création de la table sms_logs: " . $e->getMessage());
    }
}

/**
 * Test de connectivité avec la nouvelle API
 * 
 * @return array Résultat du test
 */
function test_new_sms_api() {
    try {
        $smsService = new NewSmsService();
        return $smsService->testConnection();
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erreur lors du test: ' . $e->getMessage()
        ];
    }
}

/**
 * Récupère l'historique des SMS
 * 
 * @param int $limit Nombre de SMS à récupérer
 * @param string $status Filtre par statut (optionnel)
 * @return array Liste des SMS
 */
function get_sms_history($limit = 50, $status = null) {
    try {
        $shop_pdo = getShopDBConnection();
        if (!$shop_pdo) {
            return [];
        }
        
        $sql = "SELECT * FROM sms_logs";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération de l'historique SMS: " . $e->getMessage());
        return [];
    }
}

/**
 * Formatage d'un numéro de téléphone pour affichage
 * 
 * @param string $phoneNumber Numéro à formater
 * @return string Numéro formaté
 */
function format_phone_display($phoneNumber) {
    // Supprimer le +33 et le remplacer par 0 pour l'affichage
    if (strpos($phoneNumber, '+33') === 0) {
        return '0' . substr($phoneNumber, 3);
    }
    return $phoneNumber;
}

/**
 * Envoyer la réponse HTTP au client immédiatement, puis exécuter du code en arrière-plan
 * Utilise fastcgi_finish_request() pour PHP-FPM
 * 
 * @param mixed $response La réponse à envoyer (sera encodée en JSON si array)
 */
function flush_response_and_continue($response) {
    // Encoder en JSON si nécessaire
    if (is_array($response)) {
        $response = json_encode($response);
    }
    
    // Supprimer les buffers de sortie existants
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Définir les headers pour fermer la connexion
    header('Content-Type: application/json');
    header('Connection: close');
    header('Content-Length: ' . strlen($response));
    
    // Envoyer la réponse
    echo $response;
    
    // Forcer l'envoi immédiat
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        ob_end_flush();
        flush();
    }
    
    // Configurer pour l'exécution en arrière-plan
    ignore_user_abort(true);
    set_time_limit(30);
}

/**
 * Envoyer un SMS en arrière-plan (après avoir répondu au client)
 * Cette fonction doit être appelée APRÈS flush_response_and_continue()
 * 
 * @param string $phoneNumber Numéro de téléphone
 * @param string $message Message à envoyer
 * @param string $reference_type Type de référence
 * @param int $reference_id ID de référence
 * @param int $user_id ID utilisateur
 * @return array Résultat de l'envoi
 */
function send_sms_background($phoneNumber, $message, $reference_type = null, $reference_id = null, $user_id = null) {
    try {
        error_log("SMS BACKGROUND: Début envoi vers $phoneNumber");
        $result = send_sms($phoneNumber, $message, $reference_type, $reference_id, $user_id);
        error_log("SMS BACKGROUND: Résultat = " . ($result['success'] ? 'OK' : 'ERREUR: ' . ($result['message'] ?? '')));
        return $result;
    } catch (Exception $e) {
        error_log("SMS BACKGROUND ERROR: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Envoyer un SMS de manière asynchrone via une requête HTTP en arrière-plan
 * Utilise cURL pour lancer une requête non-bloquante vers send_sms_async.php
 * 
 * @param string $phoneNumber Numéro de téléphone
 * @param string $message Message à envoyer
 * @param string $reference_type Type de référence
 * @param int $reference_id ID de référence
 * @param int $shop_id ID du magasin
 * @return bool True si la requête a été envoyée
 */
function queue_sms_async($phoneNumber, $message, $reference_type = null, $reference_id = null, $shop_id = null) {
    try {
        // Construire l'URL vers le endpoint async
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $url = $protocol . '://' . $host . '/ajax/send_sms_async.php';
        
        // Préparer les données
        $postData = http_build_query([
            'telephone' => $phoneNumber,
            'message' => $message,
            'reference_type' => $reference_type,
            'reference_id' => $reference_id,
            'shop_id' => $shop_id ?? ($_SESSION['shop_id'] ?? null)
        ]);
        
        // Créer un contexte de stream non-bloquant
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                           "Content-Length: " . strlen($postData) . "\r\n" .
                           "Connection: close\r\n",
                'content' => $postData,
                'timeout' => 1 // Timeout très court car on ne veut pas attendre
            ]
        ]);
        
        // Lancer la requête de manière non-bloquante
        @file_get_contents($url, false, $context);
        
        error_log("SMS ASYNC QUEUED: $phoneNumber via $url");
        return true;
        
    } catch (Exception $e) {
        error_log("SMS ASYNC QUEUE ERROR: " . $e->getMessage());
        return false;
    }
} 