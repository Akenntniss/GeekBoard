<?php
/**
 * Extension IA Groq pour les Analyses KPI
 * Génère des analyses expertes basées sur les profils personnalisables
 * Intègre les notes contextuelles (employés + magasin)
 */

require_once __DIR__ . '/groq_search.php';

class KPIAIAnalyzer {
    private $groq_api_key;
    private $groq_endpoint = "https://api.groq.com/openai/v1/chat/completions";
    private $model = "llama-3.1-8b-instant";
    private $pdo;
    
    public function __construct($pdo = null) {
        $this->groq_api_key = "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";
        $this->pdo = $pdo ?? getShopDBConnection();
    }
    
    /**
     * Génère une analyse selon un profil d'expert
     * 
     * @param int $profile_id ID du profil expert
     * @param array $kpi_data Données KPI
     * @param int $employee_id ID employé (optionnel, pour contexte spécifique)
     * @param string $date_start Date début période
     * @param string $date_end Date fin période
     * @param array $selected_kpi_data Données KPI sélectionnées pour filtrage (optionnel)
     * @param array $employee_notes Notes d'employé pré-chargées (optionnel)
     * @return array
     */
    public function generateAnalysis($profile_id, $kpi_data, $employee_id = null, $date_start = null, $date_end = null, $selected_kpi_data = null, $employee_notes = null, $custom_prompt = null) {
        try {
            // Récupérer le profil
            $profile = $this->getProfile($profile_id);
            
            if (!$profile) {
                throw new Exception("Profil introuvable");
            }
            
            // Si un prompt personnalisé est fourni, l'utiliser directement
            if ($custom_prompt) {
                $prompt = $custom_prompt;
                error_log("Utilisation du prompt personnalisé");
            } else {
                // Sinon, construire le prompt normalement
                // Filtrer les KPI si une sélection est fournie
                if ($selected_kpi_data !== null && isset($selected_kpi_data['selected_kpis'])) {
                    $kpi_data = $this->filterSelectedKPIs($kpi_data, $selected_kpi_data);
                }
                
                // Construire le contexte complet
                $context = $this->buildFullContext($kpi_data, $employee_id, $date_start, $date_end, $employee_notes);
                
                // Construire le prompt
                $prompt = $this->buildAnalysisPrompt($profile, $context);
            }
            
            // Appeler Groq
            $response = $this->queryGroq($prompt);
            
            // Parser la réponse
            $analysis = $this->parseGroqResponse($response);
            
            return [
                'profile_name' => $profile['name'],
                'profile_icon' => $profile['icon'],
                'analysis' => $analysis,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("KPI AI Analysis Error: " . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Récupère liste des profils actifs
     */
    public function getActiveProfiles() {
        $sql = "SELECT id, name, description, icon FROM kpi_ai_profiles WHERE active = 1 ORDER BY is_default DESC, name ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère un profil spécifique
     */
    private function getProfile($profile_id) {
        $sql = "SELECT * FROM kpi_ai_profiles WHERE id = ? AND active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$profile_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Filtre les KPI selon les données sélectionnées
     */
    private function filterSelectedKPIs($kpi_data, $selected_kpi_data) {
        // Si c'est une analyse globale avec indices sélectionnés
        if (isset($selected_kpi_data['analysis_type']) && $selected_kpi_data['analysis_type'] === 'global') {
            // Les KPI ont déjà été envoyés filtrés par le frontend
            // On retourne tel quel
            return $kpi_data;
        }
        
        // Pour l'analyse par employé
        return $kpi_data;
    }
    
    /**
     * Construit le contexte complet (KPI + Notes Employé + Notes Magasin)
     */
    private function buildFullContext($kpi_data, $employee_id, $date_start, $date_end, $employee_notes = null) {
        $context = "=== DONNÉES KPI ===\n\n";
        $context .= $this->formatKPIData($kpi_data);
        $context .= "\n\n";
        
        // Contexte employé si spécifié
        if ($employee_id) {
            $employee_context = $this->getEmployeeContextData($employee_id);
            if (!empty($employee_context['context'])) {
                $context .= "=== " . $employee_context['context'] . "\n\n";
            }
        }
        
        // Contexte magasin
        if ($date_start && $date_end) {
            $shop_context = $this->getShopContextData($date_start, $date_end);
            if (!empty($shop_context['context'])) {
                $context .= "=== " . $shop_context['context'] . "\n\n";
            }
        }
        
        return $context;
    }
    
    /**
     * Formate les données KPI en texte lisible
     */
    private function formatKPIData($kpi_data) {
        $text = "";
        
        foreach ($kpi_data as $category => $data) {
            $text .= strtoupper(str_replace('_', ' ', $category)) . ":\n";
            
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        $text .= "  " . ucfirst(str_replace('_', ' ', $key)) . ":\n";
                        foreach ($value as $subkey => $subvalue) {
                            $text .= "    - " . ucfirst(str_replace('_', ' ', $subkey)) . ": " . $this->formatValue($subvalue) . "\n";
                        }
                    } else {
                        $text .= "  - " . ucfirst(str_replace('_', ' ', $key)) . ": " . $this->formatValue($value) . "\n";
                    }
                }
            }
            
            $text .= "\n";
        }
        
        return $text;
    }
    
    /**
     * Formate une valeur (ajout symboles monétaires, etc.)
     */
    private function formatValue($value) {
        if (is_numeric($value)) {
            // Si ça ressemble à un montant (decimales ou > 100)
            if (strpos($value, '.') !== false || $value > 100) {
                return number_format($value, 2, ',', ' ') . ' €';
            }
            return $value;
        }
        return $value;
    }
    
    /**
     * Récupère le contexte employé depuis l'API
     */
    private function getEmployeeContextData($employee_id) {
        $sql = "
            SELECT *
            FROM employee_notes
            WHERE employee_id = ?
            AND include_in_ai_analysis = 1
            ORDER BY severity DESC, date_incident DESC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$employee_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($notes)) {
            return ['context' => ''];
        }
        
        $context = "CONTEXTE MANAGÉRIAL EMPLOYÉ:\n\n";
        
        foreach ($notes as $note) {
            $context .= "[" . strtoupper($note['note_type']) . " - " . strtoupper($note['severity']) . " - " . date('d/m/Y', strtotime($note['date_incident'])) . "]\n";
            $context .= $note['title'] . "\n";
            if (!empty($note['description'])) {
                $context .= $note['description'] . "\n";
            }
            $context .= "\n";
        }
        
        return ['context' => $context];
    }
    
    /**
     * Récupère le contexte magasin
     */
    private function getShopContextData($date_start, $date_end) {
        $sql = "
            SELECT *
            FROM shop_notes
            WHERE include_in_ai_analysis = 1
            AND (
                (date_start BETWEEN ? AND ?)
                OR (date_end BETWEEN ? AND ?)
                OR (date_start <= ? AND (date_end >= ? OR date_end IS NULL))
            )
            ORDER BY impact_level DESC, date_start DESC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$date_start, $date_end, $date_start, $date_end, $date_start, $date_end]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($notes)) {
            return ['context' => ''];
        }
        
        $context = "CONTEXTE MAGASIN:\n\n";
        
        foreach ($notes as $note) {
            $date_str = date('d/m/Y', strtotime($note['date_start']));
            if ($note['date_end']) {
                $date_str .= ' au ' . date('d/m/Y', strtotime($note['date_end']));
            }
            
            $context .= "[" . strtoupper($note['note_type']) . " - " . strtoupper($note['impact_level']) . " - " . $date_str . "]\n";
            $context .= $note['title'] . "\n";
            if (!empty($note['description'])) {
                $context .= $note['description'] . "\n";
            }
            if ($note['affects_kpi']) {
                $context .= "⮕ Affecte directement les KPI\n";
            }
            $context .= "\n";
        }
        
        return ['context' => $context];
    }
    
    /**
     * Construit le prompt pour l'IA
     */
    private function buildAnalysisPrompt($profile, $context) {
        return $profile['system_prompt'] . "\n\n" .
               "DONNÉES ET CONTEXTE À ANALYSER:\n\n" .
               $context . "\n\n" .
               "G\u00e9n\u00e8re une analyse d\u00e9taill\u00e9e et structur\u00e9e selon ton r\u00f4le. " .
               "Prends en compte TOUS les \u00e9l\u00e9ments de contexte fournis (notes mana\u0361g\u00e9riales et \u00e9v\u00e9nements magasin) pour nuancer ton analyse. " .
               "Sois factuel, pr\u00e9cis et constructif. Utilise des \u00e9moji pertinents pour rendre l'analyse plus visuelle.";
    }
    
    /**
     * Appel API Groq
     */
    private function queryGroq($prompt) {
        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.3,
            'max_tokens' => 2000,
            'top_p' => 0.95
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->groq_endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->groq_api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Erreur cURL: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception("Erreur API Groq: HTTP $http_code");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Réponse JSON invalide');
        }
        
        return $decoded;
    }
    
    /**
     * Parse la réponse de Groq
     */
    private function parseGroqResponse($response) {
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('Format de réponse invalide');
        }
        
        return $response['choices'][0]['message']['content'];
    }
}
