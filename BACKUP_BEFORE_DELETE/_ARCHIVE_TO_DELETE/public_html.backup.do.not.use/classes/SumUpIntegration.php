<?php
/**
 * Classe d'intégration SumUp pour GeekBoard
 * Gère tous les appels API vers SumUp
 */

class SumUpIntegration {
    private $config;
    private $apiKey;
    private $baseUrl;
    private $logFile;
    
    public function __construct() {
        $this->config = include(__DIR__ . '/../config/sumup_config.php');
        
        // Sélection de l'environnement
        if ($this->config['environment'] === 'production' && !empty($this->config['api_key_production'])) {
            $this->apiKey = $this->config['api_key_production'];
            $this->baseUrl = $this->config['base_url_production'];
        } else {
            $this->apiKey = $this->config['api_key_sandbox'];
            $this->baseUrl = $this->config['base_url_sandbox'];
        }
        
        $this->logFile = $this->config['log_file'];
    }
    
    /**
     * Créer un checkout pour un paiement
     */
    public function createCheckout($amount, $description, $reparationId = null, $clientInfo = null) {
        try {
            $checkoutReference = 'GB_' . $reparationId . '_' . time();
            
            $data = [
                'checkout_reference' => $checkoutReference,
                'amount' => (float)$amount,
                'currency' => $this->config['currency'],
                'description' => $this->config['description_prefix'] . $description,
                'return_url' => $this->config['return_url'] . '?ref=' . $checkoutReference
            ];
            
            // Ajouter les infos client si disponibles
            if ($clientInfo && isset($clientInfo['email'])) {
                $data['customer_id'] = $this->getOrCreateCustomer($clientInfo);
            }
            
            $response = $this->makeApiCall('POST', '/v0.1/checkouts', $data);
            
            if ($response && isset($response['id'])) {
                $this->log("Checkout créé: " . $response['id'] . " pour réparation " . $reparationId);
                return $response;
            }
            
            throw new Exception('Réponse API invalide');
            
        } catch (Exception $e) {
            $this->log("Erreur création checkout: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Récupérer le statut d'un checkout
     */
    public function getCheckoutStatus($checkoutId) {
        try {
            $response = $this->makeApiCall('GET', '/v0.1/checkouts/' . $checkoutId);
            return $response;
        } catch (Exception $e) {
            $this->log("Erreur récupération checkout: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    /**
     * Créer ou récupérer un client SumUp
     */
    private function getOrCreateCustomer($clientInfo) {
        $customerId = 'gb_client_' . ($clientInfo['id'] ?? uniqid());
        
        try {
            // Essayer de créer le client (SumUp gère les doublons)
            $data = [
                'customer_id' => $customerId,
                'personal_details' => []
            ];
            
            if (isset($clientInfo['email'])) {
                $data['personal_details']['email'] = $clientInfo['email'];
            }
            if (isset($clientInfo['nom'])) {
                $data['personal_details']['first_name'] = $clientInfo['prenom'] ?? '';
                $data['personal_details']['last_name'] = $clientInfo['nom'];
            }
            if (isset($clientInfo['telephone'])) {
                $data['personal_details']['phone'] = $clientInfo['telephone'];
            }
            
            $this->makeApiCall('POST', '/v0.1/customers', $data);
            return $customerId;
            
        } catch (Exception $e) {
            $this->log("Info client: " . $e->getMessage(), 'INFO');
            return $customerId; // Retourner l'ID même en cas d'erreur
        }
    }
    
    /**
     * Traiter un webhook SumUp
     */
    public function processWebhook($payload) {
        try {
            $this->log("Webhook reçu: " . json_encode($payload), 'INFO');
            
            if (!isset($payload['event_type']) || !isset($payload['resource'])) {
                throw new Exception('Webhook invalide');
            }
            
            $eventType = $payload['event_type'];
            $resource = $payload['resource'];
            
            switch ($eventType) {
                case 'checkout.paid':
                    return $this->handlePaymentSuccess($resource);
                    
                case 'checkout.failed':
                    return $this->handlePaymentFailure($resource);
                    
                default:
                    $this->log("Événement non géré: " . $eventType, 'INFO');
                    return true;
            }
            
        } catch (Exception $e) {
            $this->log("Erreur traitement webhook: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Gérer un paiement réussi
     */
    private function handlePaymentSuccess($resource) {
        try {
            $checkoutId = $resource['id'];
            $checkoutReference = $resource['checkout_reference'] ?? '';
            $transactionCode = $resource['transaction_code'] ?? '';
            
            // Extraire l'ID de réparation depuis la référence
            if (preg_match('/GB_(\d+)_/', $checkoutReference, $matches)) {
                $reparationId = (int)$matches[1];
                
                // Mettre à jour la base de données
                $this->updatePaymentStatus($reparationId, $checkoutId, 'paid', $transactionCode);
                
                $this->log("Paiement réussi pour réparation " . $reparationId);
                return true;
            }
            
            throw new Exception('Référence checkout invalide');
            
        } catch (Exception $e) {
            $this->log("Erreur paiement réussi: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Gérer un paiement échoué
     */
    private function handlePaymentFailure($resource) {
        try {
            $checkoutId = $resource['id'];
            $checkoutReference = $resource['checkout_reference'] ?? '';
            
            if (preg_match('/GB_(\d+)_/', $checkoutReference, $matches)) {
                $reparationId = (int)$matches[1];
                
                // Mettre à jour la base de données
                $this->updatePaymentStatus($reparationId, $checkoutId, 'failed');
                
                $this->log("Paiement échoué pour réparation " . $reparationId);
                return true;
            }
            
            throw new Exception('Référence checkout invalide');
            
        } catch (Exception $e) {
            $this->log("Erreur paiement échoué: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Mettre à jour le statut de paiement en base
     */
    private function updatePaymentStatus($reparationId, $checkoutId, $status, $transactionCode = null) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $shop_pdo = getShopDBConnection();
            
            // Mettre à jour ou insérer dans paiements_sumup
            $stmt = $shop_pdo->prepare("
                INSERT INTO paiements_sumup (
                    reparation_id, checkout_id, statut_paiement, 
                    transaction_code, date_paiement
                ) VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    statut_paiement = VALUES(statut_paiement),
                    transaction_code = VALUES(transaction_code),
                    date_modification = NOW()
            ");
            
            $stmt->execute([$reparationId, $checkoutId, $status, $transactionCode]);
            
            // Si paiement réussi, mettre à jour la réparation
            if ($status === 'paid') {
                $stmt = $shop_pdo->prepare("
                    UPDATE reparations 
                    SET statut = 'paye', date_modification = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$reparationId]);
            }
            
        } catch (Exception $e) {
            $this->log("Erreur mise à jour BDD: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Faire un appel API vers SumUp
     */
    private function makeApiCall($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'GeekBoard/1.0'
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            throw new Exception("Erreur cURL: " . $error);
        }
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = isset($decodedResponse['message']) ? $decodedResponse['message'] : 'Erreur API inconnue';
            throw new Exception("Erreur API ({$httpCode}): " . $errorMsg);
        }
        
        $this->log("API Call: {$method} {$endpoint} - HTTP {$httpCode}");
        
        return $decodedResponse;
    }
    
    /**
     * Logger les événements
     */
    private function log($message, $level = 'INFO') {
        if (!$this->config['log_enabled']) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        // Créer le dossier logs s'il n'existe pas
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Obtenir l'URL de paiement pour un checkout
     */
    public function getPaymentUrl($checkoutId) {
        // Pour SumUp, l'URL de paiement dépend de l'implémentation
        // Ici on utilise une approche simple avec redirection
        return $this->baseUrl . '/checkout/' . $checkoutId;
    }
    
    /**
     * Tester la connexion API
     */
    public function testConnection() {
        try {
            // Tenter de récupérer le profil marchand
            $response = $this->makeApiCall('GET', '/v0.1/me');
            return [
                'success' => true,
                'message' => 'Connexion SumUp OK',
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur connexion SumUp: ' . $e->getMessage()
            ];
        }
    }
} 