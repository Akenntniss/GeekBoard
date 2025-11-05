<?php
/**
 * GroqSmartSearch - Recherche intelligente avec IA Groq
 * Intégration pour GeekBoard Knowledge Base
 */

class GroqSmartSearch {
    private $groq_api_key;
    private $groq_endpoint = "https://api.groq.com/openai/v1/chat/completions";
    private $model = "llama-3.1-8b-instant"; // Modèle optimal pour l'analyse de texte (Nov 2024)
    
    public function __construct() {
        $this->groq_api_key = "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";
    }
    
    /**
     * Point d'entrée principal pour la recherche
     */
    public function search($query, $search_type = 'auto') {
        try {
            // Détecter le type de recherche automatiquement
            if ($search_type === 'auto') {
                $search_type = $this->detectSearchType($query);
            }
            
            error_log("GroqSmartSearch: Recherche '$query' (type: $search_type)");
            
            switch($search_type) {
                case 'standard':
                    return $this->standardSearch($query);
                case 'intelligent':
                    return $this->intelligentSearch($query);
                case 'hybrid':
                    return $this->hybridSearch($query);
                default:
                    return $this->standardSearch($query);
            }
        } catch (Exception $e) {
            error_log("GroqSmartSearch Error: " . $e->getMessage());
            // Fallback vers recherche standard en cas d'erreur
            return $this->standardSearch($query);
        }
    }
    
    /**
     * Détecte automatiquement le type de recherche à utiliser
     */
    private function detectSearchType($query) {
        $query_lower = strtolower(trim($query));
        
        // Détection de questions (recherche intelligente)
        $question_patterns = [
            '/^(comment|pourquoi|que|quel|où|quand|qui|combien)/',
            '/\?$/',
            '/(problème|erreur|bug|ne marche|fonctionne pas|aide)/',
            '/(étape|procédure|tutorial|tuto|guide)/'
        ];
        
        foreach ($question_patterns as $pattern) {
            if (preg_match($pattern, $query_lower)) {
                return 'intelligent';
            }
        }
        
        // Détection de références/modèles (recherche standard)
        if (preg_match('/^[A-Z0-9\-\s]{3,20}$/i', $query) && !preg_match('/\s+(comment|pourquoi|que)/i', $query)) {
            return 'standard';
        }
        
        // Si plus de 5 mots, probablement une question complexe
        if (str_word_count($query) > 5) {
            return 'intelligent';
        }
        
        // Par défaut, recherche hybride
        return 'hybrid';
    }
    
    /**
     * Recherche standard (système existant)
     */
    private function standardSearch($query) {
        try {
            // Initialiser la session shop si nécessaire
            $this->initializeShopSession();
            
            $shop_pdo = getShopDBConnection();
            
            if (!$shop_pdo) {
                error_log("GroqSmartSearch: Connexion DB échouée pour recherche standard");
                return ['type' => 'standard', 'articles' => [], 'total' => 0, 'explanation' => 'Erreur de connexion à la base de données'];
            }
            
            $sql = "SELECT a.*, c.name as category_name, c.icon as category_icon,
                           COUNT(r.id) as rating_count,
                           SUM(CASE WHEN r.is_helpful = 1 THEN 1 ELSE 0 END) as helpful_count
                    FROM kb_articles a
                    LEFT JOIN kb_categories c ON a.category_id = c.id
                    LEFT JOIN kb_article_ratings r ON a.id = r.article_id
                    WHERE (a.title LIKE ? OR a.content LIKE ?)
                    GROUP BY a.id
                    ORDER BY a.title ASC
                    LIMIT 20";
            
            $stmt = $shop_pdo->prepare($sql);
            $search_param = "%$query%";
            $stmt->execute([$search_param, $search_param]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("GroqSmartSearch: Recherche standard - " . count($results) . " résultats pour '$query'");
            
            return [
                'type' => 'standard',
                'query' => $query,
                'articles' => $results,
                'total' => count($results),
                'explanation' => "Recherche standard dans les titres et contenus"
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur recherche standard PDO: " . $e->getMessage());
            return ['type' => 'standard', 'articles' => [], 'total' => 0, 'explanation' => 'Erreur base de données'];
        } catch (Exception $e) {
            error_log("Erreur recherche standard: " . $e->getMessage());
            return ['type' => 'standard', 'articles' => [], 'total' => 0, 'explanation' => 'Erreur technique'];
        }
    }
    
    /**
     * Recherche intelligente avec Groq AI
     */
    private function intelligentSearch($query) {
        try {
            // 1. Récupérer tous les articles pour analyse
            $all_articles = $this->getAllArticles();
            
            if (empty($all_articles)) {
                return $this->standardSearch($query); // Fallback
            }
            
            // 2. Créer le contexte pour Groq
            $context = $this->buildContextForGroq($all_articles);
            
            // 3. Construire le prompt pour Groq
            $groq_prompt = $this->buildGroqPrompt($query, $context);
            
            // 4. Interroger Groq
            $groq_response = $this->queryGroq($groq_prompt);
            
            // 5. Parser la réponse et récupérer les articles correspondants
            $ai_results = $this->parseGroqResponse($groq_response, $all_articles);
            
            return [
                'type' => 'intelligent',
                'query' => $query,
                'articles' => $ai_results['articles'],
                'total' => count($ai_results['articles']),
                'explanation' => $ai_results['explanation'],
                'ai_analysis' => $ai_results['analysis']
            ];
            
        } catch (Exception $e) {
            error_log("Erreur recherche intelligente: " . $e->getMessage());
            // Fallback vers recherche standard
            return $this->standardSearch($query);
        }
    }
    
    /**
     * Recherche hybride (standard + IA)
     */
    private function hybridSearch($query) {
        try {
            // Effectuer les deux recherches
            $standard_results = $this->standardSearch($query);
            $ai_results = $this->intelligentSearch($query);
            
            // Fusionner et dédupliquer les résultats
            $combined_articles = [];
            $seen_ids = [];
            
            // Ajouter les résultats IA en premier (plus pertinents)
            if (!empty($ai_results['articles'])) {
                foreach ($ai_results['articles'] as $article) {
                    if (!in_array($article['id'], $seen_ids)) {
                        $article['source'] = 'ai';
                        $combined_articles[] = $article;
                        $seen_ids[] = $article['id'];
                    }
                }
            }
            
            // Ajouter les résultats standard non-dupliqués
            if (!empty($standard_results['articles'])) {
                foreach ($standard_results['articles'] as $article) {
                    if (!in_array($article['id'], $seen_ids)) {
                        $article['source'] = 'standard';
                        $combined_articles[] = $article;
                        $seen_ids[] = $article['id'];
                    }
                }
            }
            
            return [
                'type' => 'hybrid',
                'query' => $query,
                'articles' => $combined_articles,
                'total' => count($combined_articles),
                'explanation' => isset($ai_results['explanation']) ? $ai_results['explanation'] : "Recherche combinée standard + IA",
                'ai_analysis' => isset($ai_results['ai_analysis']) ? $ai_results['ai_analysis'] : null
            ];
            
        } catch (Exception $e) {
            error_log("Erreur recherche hybride: " . $e->getMessage());
            return $this->standardSearch($query);
        }
    }
    
    /**
     * Récupère tous les articles de la base
     */
    private function getAllArticles($limit = 100) {
        try {
            // Initialiser la session shop si nécessaire
            $this->initializeShopSession();
            
            $shop_pdo = getShopDBConnection();
            
            if (!$shop_pdo) {
                error_log("GroqSmartSearch: Connexion DB échouée - shop_pdo est null");
                return [];
            }
            
            $sql = "SELECT a.id, a.title, a.content, a.views, a.created_at, a.updated_at,
                           c.name as category_name, c.icon as category_icon
                    FROM kb_articles a
                    LEFT JOIN kb_categories c ON a.category_id = c.id
                    ORDER BY a.updated_at DESC, a.views DESC
                    LIMIT ?";
            
            $stmt = $shop_pdo->prepare($sql);
            $stmt->execute([$limit]);
            
            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("GroqSmartSearch: " . count($articles) . " articles récupérés de la DB");
            
            return $articles;
            
        } catch (PDOException $e) {
            error_log("Erreur getAllArticles PDO: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            error_log("Erreur getAllArticles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Initialise la session shop si nécessaire
     */
    private function initializeShopSession() {
        try {
            // Vérifier si la session est déjà démarrée
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Si pas de shop_id en session, essayer de le détecter
            if (!isset($_SESSION['shop_id'])) {
                // Fonction de détection basée sur le sous-domaine
                if (function_exists('detectShopFromSubdomain')) {
                    detectShopFromSubdomain();
                } else {
                    // Fallback : essayer de détecter manuellement
                    $host = $_SERVER['HTTP_HOST'] ?? '';
                    if (strpos($host, 'mkmkmk') !== false) {
                        $_SESSION['shop_id'] = 1; // ID pour mkmkmk
                        error_log("GroqSmartSearch: Shop ID défini manuellement pour mkmkmk");
                    }
                }
            }
            
            error_log("GroqSmartSearch: Session shop_id = " . ($_SESSION['shop_id'] ?? 'NON DÉFINI'));
            
        } catch (Exception $e) {
            error_log("Erreur initializeShopSession: " . $e->getMessage());
        }
    }
    
    /**
     * Construit le contexte pour Groq (optimisé pour Mixtral)
     */
    private function buildContextForGroq($articles) {
        $context = "";
        $token_count = 0;
        $max_tokens = 25000; // Laisser de la place pour la réponse
        
        foreach ($articles as $article) {
            // Nettoyer le contenu
            $clean_content = strip_tags($article['content']);
            $clean_content = preg_replace('/\s+/', ' ', $clean_content);
            $excerpt = substr($clean_content, 0, 400);
            
            $article_context = "ID: {$article['id']}\n";
            $article_context .= "TITRE: {$article['title']}\n";
            $article_context .= "CATÉGORIE: {$article['category_name']}\n";
            $article_context .= "CONTENU: $excerpt...\n";
            $article_context .= "VUES: {$article['views']}\n";
            $article_context .= "---\n";
            
            // Estimation approximative des tokens (1 token ≈ 4 caractères)
            $article_tokens = strlen($article_context) / 4;
            
            if ($token_count + $article_tokens > $max_tokens) {
                break;
            }
            
            $context .= $article_context;
            $token_count += $article_tokens;
        }
        
        return $context;
    }
    
    /**
     * Construit le prompt pour Groq
     */
    private function buildGroqPrompt($query, $context) {
        return "Tu es un assistant expert spécialisé dans la recherche d'articles techniques pour un système de gestion de réparations et SAV.

MISSION: Analyser les articles fournis et identifier ceux qui répondent le mieux à la question de l'utilisateur, même si les mots exacts ne sont pas dans le titre.

QUESTION DE L'UTILISATEUR: \"$query\"

ARTICLES DISPONIBLES:
$context

INSTRUCTIONS:
1. Analyse le contenu de chaque article pour comprendre son sujet
2. Identifie les articles qui répondent à la question, même indirectement
3. Classe les résultats par pertinence (score 0-100)
4. Explique pourquoi chaque article est pertinent

RÉPONSE ATTENDUE (FORMAT JSON STRICT):
{
    \"articles_pertinents\": [
        {\"id\": 123, \"score\": 95, \"raison\": \"Contient la procédure exacte demandée à l'étape 4\"},
        {\"id\": 456, \"score\": 70, \"raison\": \"Procédure similaire applicable au cas\"}
    ],
    \"explication\": \"J'ai trouvé X articles pertinents car...\",
    \"analyse\": \"Résumé de l'analyse effectuée\"
}

Réponds UNIQUEMENT en JSON valide, sans autre texte.";
    }
    
    /**
     * Interroge l'API Groq
     */
    private function queryGroq($prompt) {
        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.1,
            'max_tokens' => 1000,
            'top_p' => 0.9
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
            throw new Exception("Erreur API Groq: HTTP $http_code - $response");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Réponse JSON invalide de Groq');
        }
        
        error_log("Groq API Response: " . substr($response, 0, 500));
        
        return $decoded;
    }
    
    /**
     * Parse la réponse de Groq et récupère les articles correspondants
     */
    private function parseGroqResponse($groq_response, $all_articles) {
        try {
            if (!isset($groq_response['choices'][0]['message']['content'])) {
                throw new Exception('Format de réponse Groq invalide');
            }
            
            $content = $groq_response['choices'][0]['message']['content'];
            
            // Nettoyer la réponse (enlever les balises markdown potentielles)
            $content = preg_replace('/```json\s*/', '', $content);
            $content = preg_replace('/```\s*$/', '', $content);
            $content = trim($content);
            
            $parsed = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Erreur parsing JSON Groq: " . json_last_error_msg() . " - Content: $content");
                throw new Exception('Réponse JSON invalide de Groq');
            }
            
            $relevant_articles = [];
            
            if (isset($parsed['articles_pertinents']) && is_array($parsed['articles_pertinents'])) {
                // Créer un index des articles par ID
                $articles_by_id = [];
                foreach ($all_articles as $article) {
                    $articles_by_id[$article['id']] = $article;
                }
                
                // Récupérer les articles pertinents avec leur score
                foreach ($parsed['articles_pertinents'] as $pertinent) {
                    if (isset($pertinent['id']) && isset($articles_by_id[$pertinent['id']])) {
                        $article = $articles_by_id[$pertinent['id']];
                        $article['ai_score'] = $pertinent['score'] ?? 50;
                        $article['ai_reason'] = $pertinent['raison'] ?? 'Article pertinent';
                        $relevant_articles[] = $article;
                    }
                }
                
                // Trier par score décroissant
                usort($relevant_articles, function($a, $b) {
                    return ($b['ai_score'] ?? 0) - ($a['ai_score'] ?? 0);
                });
            }
            
            return [
                'articles' => $relevant_articles,
                'explanation' => $parsed['explication'] ?? 'Recherche IA effectuée',
                'analysis' => $parsed['analyse'] ?? 'Analyse IA'
            ];
            
        } catch (Exception $e) {
            error_log("Erreur parseGroqResponse: " . $e->getMessage());
            return [
                'articles' => [],
                'explanation' => 'Erreur lors de l\'analyse IA',
                'analysis' => 'Erreur technique'
            ];
        }
    }
}

// Fonction helper pour l'utilisation dans les pages
function performSmartSearch($query, $search_type = 'auto') {
    static $groq_search = null;
    
    if ($groq_search === null) {
        $groq_search = new GroqSmartSearch();
    }
    
    return $groq_search->search($query, $search_type);
}
?>
