<?php
/**
 * Service SMS utilisant la nouvelle API SMS Gateway
 * API Documentation: https://sms.maisondugeek.fr/docs
 * Base URL: https://sms.maisondugeek.fr/api
 * 
 * Chaque magasin utilise sa propre clé API stockée dans la table shops
 */
class NewSmsService {
    private $apiUrl;
    private $apiKey;
    private $maxRetries = 2;
    private $timeout = 30;
    
    /**
     * Constructeur - Récupère automatiquement la clé API du magasin actuel
     * 
     * @param string|null $apiKey Clé API spécifique (optionnel, sinon récupérée du magasin)
     */
    public function __construct($apiKey = null) {
        // URL de l'API SMS Gateway officielle
        $this->apiUrl = 'https://sms.maisondugeek.fr/api/send';
        
        if ($apiKey) {
            $this->apiKey = $apiKey;
        } else {
            // Récupérer la clé API du magasin actuel
            $this->apiKey = $this->getShopApiKey();
        }
    }
    
    /**
     * Récupère la clé API SMS du magasin actuel
     * 
     * @return string Clé API du magasin
     */
    private function getShopApiKey() {
        try {
            // Essayer de récupérer via le SubdomainDatabaseDetector
            require_once(__DIR__ . '/../config/subdomain_database_detector.php');
            $detector = new SubdomainDatabaseDetector();
            $shopInfo = $detector->getShopInfo();
            
            if ($shopInfo && !empty($shopInfo['sms_api_key'])) {
                $this->logDebug("Clé API trouvée pour le magasin: " . ($shopInfo['name'] ?? 'Unknown'));
                return $shopInfo['sms_api_key'];
            }
            
            // Fallback: essayer de récupérer directement depuis la base
            $mainPdo = $this->getMainDbConnection();
            if ($mainPdo) {
                $subdomain = $detector->getCurrentSubdomain();
                if ($subdomain) {
                    $stmt = $mainPdo->prepare("SELECT sms_api_key FROM shops WHERE subdomain = ? AND active = 1");
                    $stmt->execute([$subdomain]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result && !empty($result['sms_api_key'])) {
                        return $result['sms_api_key'];
                    }
                }
            }
            
            // Clé par défaut si aucune trouvée
            $this->logError("Aucune clé API SMS trouvée pour ce magasin, utilisation de la clé par défaut");
            return '1234';
            
        } catch (Exception $e) {
            $this->logError("Erreur lors de la récupération de la clé API: " . $e->getMessage());
            return '1234';
        }
    }
    
    /**
     * Connexion à la base principale
     */
    private function getMainDbConnection() {
        try {
            if (function_exists('getMainDBConnection')) {
                return getMainDBConnection();
            }
            
            require_once(__DIR__ . '/../config/database.php');
            return getMainDBConnection();
        } catch (Exception $e) {
            $this->logError("Erreur connexion DB principale: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Envoie un SMS à un numéro spécifié
     * 
     * @param string $phoneNumber Le numéro de téléphone du destinataire
     * @param string $message Le message à envoyer
     * @param string $priority Priorité du message (non utilisée par nouvelle API)
     * @param int $simId ID de la SIM à utiliser (optionnel, non utilisé par nouvelle API)
     * @return array Résultat de l'envoi avec succès/échec et détails
     */
    public function sendSms($phoneNumber, $message, $priority = 'normal', $simId = null) {
        // Formater le numéro de téléphone au format international
        $recipient = $this->formatPhoneNumber($phoneNumber);
        
        // Préparer les données selon la documentation API SMS Gateway
        // La nouvelle API utilise "content" au lieu de "message"
        $smsData = [
            'recipient' => $recipient,
            'content' => $message
        ];
        
        $jsonData = json_encode($smsData);
        
        $this->logDebug("Envoi SMS via nouvelle API - Dest: $recipient, API Key: " . substr($this->apiKey, 0, 4) . "***");
        
        // Tentative d'envoi avec retry et backoff exponentiel
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            $result = $this->attemptSend($jsonData, $attempt);
            
            if ($result['success'] || $attempt == $this->maxRetries) {
                return $result;
            }
            
            // Backoff exponentiel entre les tentatives
            $delay = pow(2, $attempt - 1);
            $this->logError("Tentative $attempt échouée, attente de {$delay}s avant retry");
            sleep($delay);
        }
        
        return $result;
    }
    
    /**
     * Tentative d'envoi SMS
     * 
     * @param string $jsonData Données JSON à envoyer
     * @param int $attempt Numéro de la tentative
     * @return array Résultat de la tentative
     */
    private function attemptSend($jsonData, $attempt) {
        // Configuration de la requête cURL avec authentification X-API-Key
        $curl = curl_init($this->apiUrl);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $this->apiKey,  // Authentification via header
                'Content-Length: ' . strlen($jsonData)
            ],
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'GeekBoard SMS Client v2.0'
        ]);
        
        // Exécution de la requête
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        $requestInfo = curl_getinfo($curl);
        
        curl_close($curl);
        
        // Log de la requête pour debugging
        $this->logDebug("Tentative $attempt - HTTP Code: $httpCode, Time: {$requestInfo['total_time']}s");
        
        // Vérification des erreurs cURL
        if ($response === false) {
            $errorMsg = "Erreur de connexion cURL: $curlError";
            $this->logError("Tentative $attempt - $errorMsg");
            return [
                'success' => false,
                'message' => $errorMsg,
                'attempt' => $attempt,
                'http_code' => 0,
                'curl_error' => $curlError
            ];
        }
        
        // Traitement de la réponse
        $responseData = json_decode($response, true);
        
        // Log de la réponse brute pour debugging
        $this->logDebug("Réponse brute (tentative $attempt): " . substr($response, 0, 500));
        
        if ($httpCode == 200 || $httpCode == 201) {
            if ($responseData && isset($responseData['success']) && $responseData['success']) {
                $statusMsg = $httpCode == 200 ? "SMS envoyé avec succès" : "SMS ajouté à la queue d'envoi";
                $this->logSuccess("$statusMsg (tentative $attempt)");
                return [
                    'success' => true,
                    'message' => $responseData['message'] ?? $statusMsg,
                    'data' => $responseData['data'] ?? null,
                    'attempt' => $attempt,
                    'http_code' => $httpCode,
                    'response_time' => $requestInfo['total_time']
                ];
            } else {
                $errorMsg = $responseData['message'] ?? 'Réponse API invalide';
                $this->logError("Tentative $attempt - Échec: $errorMsg");
                return [
                    'success' => false,
                    'message' => $errorMsg,
                    'response' => $responseData,
                    'attempt' => $attempt,
                    'http_code' => $httpCode
                ];
            }
        } else if ($httpCode == 400) {
            $errorMsg = $responseData['message'] ?? 'Paramètres invalides';
            $details = $responseData['details'] ?? null;
            $this->logError("Tentative $attempt - Erreur 400: $errorMsg");
            return [
                'success' => false,
                'message' => $errorMsg,
                'details' => $details,
                'response' => $responseData,
                'attempt' => $attempt,
                'http_code' => $httpCode
            ];
        } else if ($httpCode == 401) {
            $errorMsg = "Clé API invalide ou expirée";
            $this->logError("Tentative $attempt - Erreur 401: $errorMsg");
            return [
                'success' => false,
                'message' => $errorMsg,
                'response' => $responseData,
                'attempt' => $attempt,
                'http_code' => $httpCode
            ];
        } else if ($httpCode == 402) {
            // Erreur de crédit insuffisant
            $errorMsg = "⚠️ Crédit SMS insuffisant. Veuillez contacter votre administrateur pour recharger votre compte SMS.";
            $this->logError("Tentative $attempt - Erreur 402: Crédit insuffisant");
            return [
                'success' => false,
                'message' => $errorMsg,
                'error_type' => 'insufficient_credit',
                'response' => $responseData,
                'attempt' => $attempt,
                'http_code' => $httpCode
            ];
        } else if ($httpCode == 429) {
            $errorMsg = "Limite de taux dépassée - Trop de requêtes";
            $this->logError("Tentative $attempt - $errorMsg");
            return [
                'success' => false,
                'message' => $errorMsg,
                'response' => $responseData,
                'attempt' => $attempt,
                'http_code' => $httpCode,
                'retry_after' => $responseData['retry_after'] ?? null
            ];
        } else {
            $errorMsg = $responseData['message'] ?? "Erreur HTTP $httpCode";
            $this->logError("Tentative $attempt - $errorMsg");
            return [
                'success' => false,
                'message' => $errorMsg,
                'response' => $responseData,
                'attempt' => $attempt,
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Formate le numéro de téléphone au format international
     * Selon la documentation API, le format requis est +33612345678
     * 
     * @param string $phoneNumber Le numéro à formater
     * @return string Le numéro formaté
     */
    private function formatPhoneNumber($phoneNumber) {
        // Supprimer tous les caractères non numériques sauf +
        $formatted = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Gestion des différents formats français
        if (substr($formatted, 0, 1) !== '+') {
            if (substr($formatted, 0, 1) === '0') {
                // Format français 0612345678 -> +33612345678
                $formatted = '+33' . substr($formatted, 1);
            } else if (substr($formatted, 0, 2) === '33') {
                // Format 33612345678 -> +33612345678
                $formatted = '+' . $formatted;
            } else if (strlen($formatted) === 9) {
                // Format 612345678 -> +33612345678
                $formatted = '+33' . $formatted;
            } else {
                // Autre format, ajouter + par défaut
                $formatted = '+' . $formatted;
            }
        }
        
        return $formatted;
    }
    
    /**
     * Récupère l'historique des messages via l'API
     * Note: Cette fonctionnalité peut ne pas être disponible sur la nouvelle API
     * 
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @param string $status Filtrer par statut
     * @return array Historique des messages
     */
    public function getHistory($page = 1, $limit = 50, $status = null) {
        // L'historique est maintenant géré localement dans la base de données
        return [
            'success' => false,
            'message' => 'Historique disponible via la base de données locale'
        ];
    }
    
    /**
     * Teste la connectivité avec l'API
     * 
     * @return array Résultat du test
     */
    public function testConnection() {
        $curl = curl_init($this->apiUrl);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_CUSTOMREQUEST => 'OPTIONS'
        ]);
        
        curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        
        if ($curlError) {
            return [
                'success' => false,
                'message' => 'Erreur de connexion: ' . $curlError
            ];
        }
        
        // Accepter les codes indiquant que l'API est accessible
        if ($httpCode >= 200 && $httpCode < 500) {
            return [
                'success' => true,
                'message' => 'API SMS Gateway accessible (HTTP ' . $httpCode . ')',
                'http_code' => $httpCode,
                'api_key_status' => ($httpCode == 401) ? 'Clé invalide' : 'OK'
            ];
        }
        
        return [
            'success' => false,
            'message' => "API non accessible (Code: $httpCode)",
            'http_code' => $httpCode
        ];
    }
    
    /**
     * Définit une clé API manuellement
     * 
     * @param string $apiKey Nouvelle clé API
     */
    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Retourne la clé API actuelle (masquée)
     * 
     * @return string Clé API masquée
     */
    public function getApiKeyMasked() {
        if (strlen($this->apiKey) > 8) {
            return substr($this->apiKey, 0, 4) . '****' . substr($this->apiKey, -4);
        }
        return '****';
    }
    
    /**
     * Journalise un message de succès
     */
    private function logSuccess($message) {
        $this->writeLog('SUCCESS', $message);
    }
    
    /**
     * Journalise un message d'erreur
     */
    private function logError($message) {
        $this->writeLog('ERROR', $message);
        error_log("NewSmsService ERROR: $message");
    }
    
    /**
     * Journalise un message de debug
     */
    private function logDebug($message) {
        $this->writeLog('DEBUG', $message);
    }
    
    /**
     * Écrit dans le fichier de log
     */
    private function writeLog($level, $message) {
        $logFile = __DIR__ . '/../logs/sms_gateway_' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}