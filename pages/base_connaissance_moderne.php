<?php
<?php include_once 'includes/night-mode-system.php'; ?>
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'base_connaissance_moderne.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=base_connaissance_moderne');
    exit();
}

// ⭐ VÉRIFICATION AUTOMATIQUE DE L'ABONNEMENT
require_once __DIR__ . '/../includes/subscription_redirect_middleware.php';

// Vérifier l'accès - redirection automatique si expiré
if (!checkSubscriptionAccess()) {
    exit;
}

// 🧠 INTÉGRATION GROQ AI SEARCH
require_once __DIR__ . '/../includes/groq_search.php';

// Récupération de la catégorie sélectionnée (si présente)
$categorie_id = isset($_GET['categorie']) ? intval($_GET['categorie']) : 0;

// Récupération du terme de recherche (si présent)
$recherche = isset($_GET['recherche']) ? cleanInput($_GET['recherche']) : '';

// 🧠 NOUVEAU: Récupération du type de recherche (standard/intelligent/auto)
$search_type = isset($_GET['search_type']) ? cleanInput($_GET['search_type']) : 'auto';

// Récupération des catégories
function get_kb_categories() {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "SELECT * FROM kb_categories ORDER BY name ASC";
        $stmt = $shop_pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des catégories KB: " . $e->getMessage());
        return [];
    }
}

// 🧠 NOUVELLE FONCTION DE RÉCUPÉRATION DES ARTICLES AVEC IA
function get_kb_articles($categorie_id = 0, $recherche = '', $limit = 50, $search_type = 'auto') {
    // Si pas de recherche, utiliser la logique classique
    if (empty($recherche)) {
        return get_kb_articles_classic($categorie_id, $recherche, $limit);
    }
    
    try {
        // Utiliser la recherche IA Groq
        $groq_search = new GroqSmartSearch();
        $search_results = $groq_search->search($recherche, $search_type);
        
        // Si aucun résultat IA ou erreur, fallback vers recherche classique
        if (empty($search_results['articles'])) {
            error_log("Groq Search: Aucun résultat, fallback vers recherche classique");
            return get_kb_articles_classic($categorie_id, $recherche, $limit);
        }
        
        // Filtrer par catégorie si spécifiée
        if ($categorie_id > 0) {
            $filtered_articles = [];
            foreach ($search_results['articles'] as $article) {
                if ($article['category_id'] == $categorie_id) {
                    $filtered_articles[] = $article;
                }
            }
            $search_results['articles'] = $filtered_articles;
        }
        
        // Limiter les résultats
        if (count($search_results['articles']) > $limit) {
            $search_results['articles'] = array_slice($search_results['articles'], 0, $limit);
        }
        
        // Ajouter les métadonnées de recherche IA aux articles
        foreach ($search_results['articles'] as &$article) {
            $article['search_metadata'] = [
                'type' => $search_results['type'],
                'ai_score' => $article['ai_score'] ?? null,
                'ai_reason' => $article['ai_reason'] ?? null,
                'source' => $article['source'] ?? 'ai'
            ];
        }
        
        // Stocker les métadonnées de recherche pour l'affichage
        global $search_metadata;
        $search_metadata = [
            'type' => $search_results['type'],
            'explanation' => $search_results['explanation'] ?? '',
            'ai_analysis' => $search_results['ai_analysis'] ?? '',
            'total' => $search_results['total'] ?? count($search_results['articles'])
        ];
        
        error_log("Groq Search Success: " . count($search_results['articles']) . " articles trouvés (type: {$search_results['type']})");
        
        return $search_results['articles'];
        
    } catch (Exception $e) {
        error_log("Erreur Groq Search: " . $e->getMessage() . " - Fallback vers recherche classique");
        return get_kb_articles_classic($categorie_id, $recherche, $limit);
    }
}

// 🔧 FONCTION CLASSIQUE DE RÉCUPÉRATION (Backup)
function get_kb_articles_classic($categorie_id = 0, $recherche = '', $limit = 50) {
    $shop_pdo = getShopDBConnection();
    try {
        $params = [];
        $where_clauses = [];
        
        // Si une catégorie est spécifiée
        if ($categorie_id > 0) {
            $where_clauses[] = "a.category_id = ?";
            $params[] = $categorie_id;
        }
        
        // Si un terme de recherche est spécifié
        if (!empty($recherche)) {
            $where_clauses[] = "(a.title LIKE ? OR a.content LIKE ?)";
            $params[] = "%$recherche%";
            $params[] = "%$recherche%";
        }
        
        // Construction de la clause WHERE
        $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        
        $query = "
            SELECT a.*, c.name as category_name, c.icon as category_icon,
                   COUNT(r.id) as rating_count,
                   SUM(CASE WHEN r.is_helpful = 1 THEN 1 ELSE 0 END) as helpful_count
            FROM kb_articles a
            LEFT JOIN kb_categories c ON a.category_id = c.id
            LEFT JOIN kb_article_ratings r ON a.id = r.article_id
            $where_sql
            GROUP BY a.id
            ORDER BY a.title ASC
            LIMIT ?
        ";
        
        $params[] = $limit;
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute($params);
        
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Marquer comme recherche classique
        foreach ($articles as &$article) {
            $article['search_metadata'] = [
                'type' => 'standard',
                'source' => 'standard'
            ];
        }
        
        return $articles;
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des articles KB: " . $e->getMessage());
        return [];
    }
}

// Récupération des tags d'un article
function get_article_tags($article_id) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "
            SELECT t.* 
            FROM kb_tags t
            JOIN kb_article_tags at ON t.id = at.tag_id
            WHERE at.article_id = ?
            ORDER BY t.name ASC
        ";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$article_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des tags: " . $e->getMessage());
        return [];
    }
}

// Récupération des statistiques pour la base de connaissances
function get_kb_stats() {
    $shop_pdo = getShopDBConnection();
    try {
        // Nombre total d'articles
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM kb_articles");
        $total_articles = $stmt->fetchColumn();
        
        // Nombre de catégories
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM kb_categories");
        $total_categories = $stmt->fetchColumn();
        
        // Nombre total de vues
        $stmt = $shop_pdo->query("SELECT SUM(views) as total FROM kb_articles");
        $total_views = $stmt->fetchColumn() ?: 0;
        
        // Articles les plus consultés
        $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM kb_articles WHERE views > 50");
        $popular_articles = $stmt->fetchColumn();
        
        return [
            'total_articles' => $total_articles,
            'total_categories' => $total_categories,
            'total_views' => $total_views,
            'popular_articles' => $popular_articles
        ];
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques KB: " . $e->getMessage());
        return [
            'total_articles' => 0,
            'total_categories' => 0,
            'total_views' => 0,
            'popular_articles' => 0
        ];
    }
}

// Récupération des données
$categories = get_kb_categories();
$articles = get_kb_articles($categorie_id, $recherche, 50, $search_type);
$kb_stats = get_kb_stats();
?>

<style>
/* ========================================
   VARIABLES CSS OPTIMISÉES POUR MODE JOUR
======================================== */
:root {
    /* Mode Jour - Couleurs Claires et Lumineuses */
    --primary: #3b82f6;
    --primary-light: #60a5fa;
    --primary-dark: #2563eb;
    --secondary: #8b5cf6;
    --accent: #06b6d4;
    
    /* Fonds - Mode Jour Clair */
    --bg: #ffffff;
    --bg-secondary: #f8fafc;
    --card-bg: #ffffff;
    --card-bg-alt: #f1f5f9;
    
    /* Textes - Mode Jour */
    --text: #1e293b;
    --text-light: #64748b;
    --text-muted: #94a3b8;
    
    /* Bordures et Ombres - Mode Jour */
    --border: #e0e7ff;
    --border-light: #f1f5f9;
    --shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    --shadow-hover: 0 4px 12px rgba(0, 0, 0, 0.12);
    
    /* Gradients - Mode Jour */
    --gradient-primary: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
    --gradient-bg: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    
    /* Espacements adaptatifs */
    --container-padding: 2rem;
    --card-padding: 1.5rem;
    --section-margin: 3rem;
    --grid-gap: 1.5rem;
}

/* ========================================
   MODE NUIT - Variables Spécifiques
======================================== */
body.night-mode {
    --primary: #60a5fa;
    --primary-light: #93c5fd;
    --primary-dark: #3b82f6;
    --secondary: #a78bfa;
    --accent: #22d3ee;
    
    /* Fonds - Mode Nuit */
    --bg: #0f172a;
    --bg-secondary: #1e293b;
    --card-bg: #1e293b;
    --card-bg-alt: #334155;
    
    /* Textes - Mode Nuit */
    --text: #f1f5f9;
    --text-light: #cbd5e1;
    --text-muted: #94a3b8;
    
    /* Bordures et Ombres - Mode Nuit */
    --border: #334155;
    --border-light: #475569;
    --shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    --shadow-hover: 0 4px 12px rgba(0, 0, 0, 0.4);
    
    /* Gradients - Mode Nuit */
    --gradient-primary: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
    --gradient-bg: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
}

/* ========================================
   STYLES DE BASE RESPONSIVE
======================================== */
* {
    box-sizing: border-box;
}

body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    line-height: 1.6;
    margin: 0;
    padding: 0;
    transition: background-color 0.3s ease, color 0.3s ease;
    overflow-x: hidden;
    min-height: 100vh;
}

.kb-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: var(--container-padding);
    width: 100%;
    background: var(--bg);
    min-height: calc(100vh - 80px);
    transition: background-color 0.3s ease;
}

/* ========================================
   HEADER RESPONSIVE
======================================== */
.kb-header {
    text-align: center;
    margin-bottom: var(--section-margin);
    padding: 2rem 1rem;
    background: var(--gradient-bg);
    border-radius: 16px;
    border: 1px solid var(--border-light);
    transition: all 0.3s ease;
}

.kb-title {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 700;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1rem;
    line-height: 1.2;
    word-break: break-word;
}

.kb-title i {
    font-size: 0.8em;
    margin-right: 0.5rem;
    color: var(--primary);
    -webkit-text-fill-color: var(--primary);
}

.kb-subtitle {
    font-size: clamp(1rem, 2.5vw, 1.2rem);
    color: var(--text-light);
    margin-bottom: 0;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* ========================================
   CARTES RESPONSIVE - Mode Jour Clair
======================================== */
.card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: var(--card-padding);
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    transition: all 0.2s ease;
    width: 100%;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
    border-color: var(--primary-light);
}

/* ========================================
   STATISTIQUES ULTRA-RESPONSIVE - Mode Jour
======================================== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--grid-gap);
    margin-bottom: var(--section-margin);
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: var(--card-padding);
    text-align: center;
    box-shadow: var(--shadow);
    transition: all 0.2s ease;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--gradient-primary);
    opacity: 0.8;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
    border-color: var(--primary-light);
}

.stat-number {
    font-size: clamp(1.8rem, 4vw, 2.5rem);
    font-weight: 700;
    color: var(--primary);
    display: block;
    margin-bottom: 0.5rem;
    line-height: 1;
}

.stat-label {
    color: var(--text-light);
    font-size: clamp(0.8rem, 2vw, 0.9rem);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

/* ========================================
   BOUTONS RESPONSIVE TACTILES - Mode Jour
======================================== */
.btn {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    min-height: 48px;
    min-width: 120px;
    text-align: center;
    white-space: nowrap;
}

.btn-primary {
    background: var(--gradient-primary);
    color: white;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.btn-primary:hover {
    background: var(--primary-dark);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.4);
}

.btn-secondary {
    background: var(--card-bg);
    color: var(--text);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.btn-secondary:hover {
    background: var(--card-bg-alt);
    color: var(--text);
    transform: translateY(-2px);
    border-color: var(--primary-light);
    box-shadow: var(--shadow-hover);
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
    align-items: center;
    margin-bottom: var(--section-margin);
    padding: 0 1rem;
}

/* ========================================
   FORMULAIRES TACTILES RESPONSIFS - Mode Jour
======================================== */
.form-group {
    margin-bottom: 1rem;
}

.form-control {
    width: 100%;
    padding: 1rem;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--card-bg);
    color: var(--text);
    font-size: 1rem;
    min-height: 48px;
    transition: all 0.2s ease;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), inset 0 1px 3px rgba(0, 0, 0, 0.05);
    background: white;
}

/* ========================================
   NOUVELLE SECTION ACTIONS ET RECHERCHE
======================================== */

.search-action-section {
    margin: 2rem 0;
    padding: 1.5rem;
}

.search-action-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: space-between;
}

.create-article-btn {
    flex-shrink: 0;
    white-space: nowrap;
}

.search-form-inline {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    max-width: 500px;
    min-width: 300px;
}

.search-input-inline {
    flex: 1;
    min-width: 0;
}

.search-btn {
    flex-shrink: 0;
    white-space: nowrap;
}

.admin-btn {
    flex-shrink: 0;
    white-space: nowrap;
}

/* ========================================
   TOGGLE TYPE DE RECHERCHE IA
======================================== */

.search-type-toggle {
    flex-shrink: 0;
    margin-right: 0.75rem;
}

.search-type-toggle .btn-group {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-radius: 6px;
    overflow: hidden;
}

.search-type-toggle .btn {
    font-size: 0.8rem;
    padding: 0.375rem 0.5rem;
    border: none;
    transition: all 0.2s ease;
}

.search-type-toggle .btn-outline-primary {
    background: var(--surface);
    color: var(--text-light);
    border: 1px solid var(--border);
}

.search-type-toggle .btn-outline-primary:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-1px);
}

.search-type-toggle .btn-check:checked + .btn-outline-primary {
    background: var(--primary);
    color: white;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
}

.search-type-toggle i {
    margin-right: 0.25rem;
}

/* ========================================
   AFFICHAGE RÉSULTATS IA
======================================== */

.ai-search-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin: 1.5rem 0;
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.ai-search-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.ai-search-badge i {
    color: #ffd700;
    font-size: 1.1rem;
}

.ai-explanation {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.9rem;
    opacity: 0.9;
    line-height: 1.4;
}

.ai-explanation i {
    color: #b8d4ff;
    margin-top: 0.1rem;
    flex-shrink: 0;
}

/* Articles avec résultats IA */
.article-card.ai-result {
    border: 2px solid #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    position: relative;
}

.article-card.ai-result::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px 12px 0 0;
}

.article-badges {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}

.ai-score-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.ai-score-badge i {
    color: #ffd700;
    font-size: 0.8rem;
}

.ai-reason {
    background: rgba(102, 126, 234, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 8px;
    padding: 0.75rem;
    margin: 0 1.25rem;
    font-size: 0.85rem;
    color: var(--text-light);
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    line-height: 1.4;
}

.ai-reason i {
    color: #667eea;
    margin-top: 0.1rem;
    flex-shrink: 0;
}

/* ========================================
   ANCIEN SYSTÈME DE RECHERCHE (Fallback)
======================================== */

.search-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-width: 800px;
    margin: 0 auto;
}

.search-input {
    flex: 1;
    min-width: 0;
}

/* ========================================
   CATÉGORIES RESPONSIVE - Mode Jour
======================================== */
.categories-section {
    margin-bottom: 2rem;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: var(--card-padding);
    box-shadow: var(--shadow);
}

.categories-title {
    margin-bottom: 1.5rem;
    color: var(--text);
    font-size: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.categories-title i {
    color: var(--primary);
}

.categories-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.category-link {
    padding: 0.75rem 1.25rem;
    background: var(--card-bg-alt);
    border: 1px solid var(--border-light);
    border-radius: 25px;
    color: var(--text);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    min-height: 44px;
    white-space: nowrap;
}

.category-link:hover {
    background: var(--primary-light);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
}

.category-link.active {
    background: var(--gradient-primary);
    color: white;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.category-link i {
    font-size: 0.9rem;
    flex-shrink: 0;
}

/* ========================================
   ARTICLES GRID ULTRA-RESPONSIVE - Mode Jour
======================================== */
.articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 2rem;
    align-items: start;
}

.article-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: all 0.2s ease;
    height: fit-content;
    display: flex;
    flex-direction: column;
}

.article-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
    border-color: var(--primary-light);
}

.article-header {
    padding: 1.25rem;
    border-bottom: 1px solid var(--border-light);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
    background: var(--bg-secondary);
}

.article-category {
    color: var(--text-light);
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 120px;
}

.article-rating {
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    flex-shrink: 0;
}

.rating-excellent {
    background: #dcfce7;
    color: #16a34a;
}

.rating-good {
    background: #fef3c7;
    color: #d97706;
}

.rating-poor {
    background: #fee2e2;
    color: #dc2626;
}

body.night-mode .rating-excellent {
    background: rgba(22, 163, 74, 0.2);
    color: #4ade80;
}

body.night-mode .rating-good {
    background: rgba(217, 119, 6, 0.2);
    color: #fbbf24;
}

body.night-mode .rating-poor {
    background: rgba(220, 38, 38, 0.2);
    color: #f87171;
}

/* ========================================
   CORRECTIONS SPÉCIFIQUES MODE JOUR
======================================== */

/* Forcer le mode jour pour tous les éléments qui posent problème */
body:not(.night-mode) {
    /* Variables mode jour forcées */
    --bg: #ffffff !important;
    --card-bg: #ffffff !important;
    --surface: #f8fafc !important;
    --border: #e2e8f0 !important;
    --text: #1e293b !important;
    --text-light: #64748b !important;
    --shadow: rgba(0, 0, 0, 0.1) !important;
}

/* Forcer le fond blanc pour le conteneur principal en mode jour */
body:not(.night-mode) .kb-container {
    background: #ffffff !important;
    color: #1e293b !important;
}

/* Forcer le fond blanc pour les cartes de statistiques en mode jour */
body:not(.night-mode) .stat-card {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
    color: #1e293b !important;
}

/* Forcer le fond blanc pour la section de recherche en mode jour */
body:not(.night-mode) .card {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
    color: #1e293b !important;
}

/* Forcer tous les éléments de formulaire en mode jour */
body:not(.night-mode) .form-control,
body:not(.night-mode) .search-input {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
    color: #1e293b !important;
}

/* Forcer les boutons en mode jour */
body:not(.night-mode) .btn-primary {
    background: #3b82f6 !important;
    border-color: #3b82f6 !important;
    color: #ffffff !important;
}

/* Forcer les éléments de catégories en mode jour */
body:not(.night-mode) .categories-section {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
    color: #1e293b !important;
}

/* Forcer les liens de catégorie en mode jour */
body:not(.night-mode) .category-link {
    background: #f8fafc !important;
    border-color: #e2e8f0 !important;
    color: #1e293b !important;
}

body:not(.night-mode) .category-link:hover {
    background: #e2e8f0 !important;
    color: #1e293b !important;
}

/* Forcer la nouvelle section actions et recherche en mode jour */
body:not(.night-mode) .search-action-section {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
    color: #1e293b !important;
}

body:not(.night-mode) .search-input-inline {
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
    color: #1e293b !important;
}

/* ========================================
   CORRECTIONS SPÉCIFIQUES MODE NUIT
======================================== */
body.night-mode {
    background: var(--bg) !important;
}

body.night-mode .kb-container {
    background: var(--bg) !important;
}

body.night-mode .kb-header {
    background: var(--gradient-bg) !important;
    border-color: var(--border) !important;
}

body.night-mode .card,
body.night-mode .stat-card,
body.night-mode .article-card,
body.night-mode .categories-section,
body.night-mode .search-action-section {
    background: var(--card-bg) !important;
    border-color: var(--border) !important;
}

body.night-mode .search-input-inline {
    background: var(--card-bg) !important;
    border-color: var(--border) !important;
    color: var(--text) !important;
}

body.night-mode .article-header,
body.night-mode .article-footer {
    background: var(--card-bg-alt) !important;
    border-color: var(--border) !important;
}

body.night-mode .form-control {
    background: var(--card-bg) !important;
    border-color: var(--border) !important;
    color: var(--text) !important;
}

body.night-mode .form-control:focus {
    background: var(--card-bg) !important;
}

body.night-mode .category-link {
    background: var(--card-bg-alt) !important;
    border-color: var(--border) !important;
    color: var(--text) !important;
}

body.night-mode .empty-state {
    background: var(--card-bg) !important;
    border-color: var(--border) !important;
}

body.night-mode .clear-search {
    background: var(--card-bg-alt) !important;
    border-color: var(--border) !important;
    color: var(--text-light) !important;
}

.article-body {
    padding: 1.25rem;
    flex: 1;
    background: var(--card-bg);
}

.article-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 1rem;
    text-decoration: none;
    display: block;
    line-height: 1.3;
    word-break: break-word;
}

.article-title:hover {
    color: var(--primary);
}

.article-excerpt {
    color: var(--text-light);
    line-height: 1.6;
    margin-bottom: 1rem;
    overflow-wrap: break-word;
}

.article-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.article-tag {
    padding: 0.4rem 0.8rem;
    background: rgba(59, 130, 246, 0.1);
    color: var(--primary);
    border-radius: 12px;
    font-size: 0.75rem;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.article-tag:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-1px);
}

.article-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    color: var(--text-muted);
    gap: 1rem;
    flex-wrap: wrap;
    background: var(--bg-secondary);
}

.article-footer > div {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    white-space: nowrap;
}

/* ========================================
   ALERTES RESPONSIVE - Mode Jour
======================================== */
.alert {
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    background: var(--card-bg);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.alert-info {
    background: #eff6ff;
    color: #1e40af;
    border-color: #bfdbfe;
}

body.night-mode .alert-info {
    background: rgba(30, 64, 175, 0.15);
    color: #93c5fd;
    border-color: rgba(147, 197, 253, 0.3);
}

.alert-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 200px;
}

.clear-search {
    color: var(--text-light);
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
    white-space: nowrap;
    transition: all 0.2s ease;
    background: var(--card-bg-alt);
    border: 1px solid var(--border-light);
}

.clear-search:hover {
    background: var(--primary-light);
    color: white;
    transform: translateY(-1px);
}

/* ========================================
   ÉTATS VIDES RESPONSIVE
======================================== */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--text-light);
    background: var(--card-bg);
    border-radius: 12px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.empty-state i {
    font-size: clamp(2.5rem, 6vw, 4rem);
    margin-bottom: 1rem;
    opacity: 0.5;
    color: var(--text-muted);
}

.empty-state h3 {
    color: var(--text);
    margin-bottom: 1rem;
    font-size: clamp(1.2rem, 3vw, 1.5rem);
}

.empty-state p {
    max-width: 500px;
    margin: 0 auto;
    line-height: 1.6;
}

.empty-state a {
    color: var(--primary);
    text-decoration: none;
}

.empty-state a:hover {
    text-decoration: underline;
}

/* ========================================
   FIX NAVBAR - Obligatoire pour affichage correct avec alignement parfait
======================================== */
/* Masquer dock mobile sur desktop */
@media (min-width: 992px) {
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    /* Forcer navbar desktop visible */
    #desktop-navbar, nav#desktop-navbar, .navbar, nav.navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 10000 !important;
        height: 60px !important;
        width: 100% !important;
    }
    /* Surcharger navbar-servo-fix.css */
    body #desktop-navbar, html body #desktop-navbar {
        height: 60px !important;
        min-height: 60px !important;
        max-height: 60px !important;
    }
    /* Éléments navbar visibles */
    #desktop-navbar * {
        visibility: visible !important;
        opacity: 1 !important;
    }
    /* Container navbar avec centrage vertical parfait */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.75rem 1rem !important; /* Augmenté à 0.75rem pour plus de centrage */
        min-height: 60px !important;
    }
    /* Logo avec centrage vertical parfait */
    #desktop-navbar .navbar-brand {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        line-height: 1 !important;
    }
    #desktop-navbar .navbar-brand img {
        height: 32px !important; /* Encore réduit pour plus d'espace vertical */
        width: auto !important;
        vertical-align: middle !important;
    }
    /* Boutons avec centrage vertical parfait */
    #desktop-navbar .btn,
    #desktop-navbar .navbar-nav .nav-link,
    #desktop-navbar .dropdown-toggle {
        display: flex !important;
        align-items: center !important;
        height: auto !important;
        padding: 0.375rem 0.75rem !important; /* Padding encore plus réduit */
        margin: 0.125rem 0.25rem !important; /* Marges ajustées */
        line-height: 1.2 !important;
        vertical-align: middle !important;
    }
    /* Correction spécifique pour les icônes dans les boutons */
    #desktop-navbar .btn i,
    #desktop-navbar .navbar-nav .nav-link i,
    #desktop-navbar .dropdown-toggle i {
        vertical-align: middle !important;
        line-height: 1 !important;
    }
    /* Messages de bienvenue centrés */
    #desktop-navbar .d-none.d-md-flex {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
        line-height: 1.2 !important;
    }
    /* Correction pour tous les textes dans la navbar */
    #desktop-navbar .navbar-text,
    #desktop-navbar .text-muted,
    #desktop-navbar span,
    #desktop-navbar small {
        line-height: 1.2 !important;
        vertical-align: middle !important;
    }
    /* Forcer l'alignement vertical pour tous les éléments flex */
    #desktop-navbar .d-flex {
        align-items: center !important;
    }
    /* Animation SERVO centrée parfaitement */
    body .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 10001 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        height: auto !important;
        width: auto !important;
    }
    
    /* Correction spécifique pour l'animation SERVO dans la navbar */
    #desktop-navbar .servo-logo-container {
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
    }
    
    /* Réserver espace navbar */
    body {
        padding-top: 80px !important;
    }
}

/* Styles généraux navbar (mobile + desktop) */
#desktop-navbar, nav#desktop-navbar {
    display: block !important;
    visibility: visible !important;
    position: fixed !important;
    top: 0 !important;
    z-index: 10000 !important;
}

/* Masquer navbar sur mobile */
@media (max-width: 767px) {
    #desktop-navbar, nav#desktop-navbar {
        display: none !important;
    }
}

/* ========================================
   BREAKPOINTS RESPONSIVE DÉTAILLÉS
======================================== */

/* Large Desktop (1400px+) */
@media (min-width: 1400px) {
    .kb-container {
        padding: 3rem;
    }
    
    .articles-grid {
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: 2.5rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
    }
}

/* Desktop (1200px - 1399px) */
@media (min-width: 1200px) and (max-width: 1399px) {
    .articles-grid {
        grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Large Tablet (992px - 1199px) */
@media (min-width: 992px) and (max-width: 1199px) {
    :root {
        --container-padding: 2rem;
        --section-margin: 2.5rem;
    }
    
    .articles-grid {
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.5rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .search-form {
        flex-direction: row;
        max-width: 700px;
    }
    
    /* Nouvelle section actions et recherche - Large Desktop */
    .search-action-container {
        flex-wrap: nowrap;
        gap: 1.5rem;
    }
    
    .search-form-inline {
        max-width: 600px;
        min-width: 400px;
    }
}

/* Tablet (768px - 991px) */
@media (min-width: 768px) and (max-width: 991px) {
    :root {
        --container-padding: 2rem;
        --card-padding: 1.25rem;
        --section-margin: 2rem;
        --grid-gap: 1.25rem;
    }
    
    .kb-title {
        font-size: 2.5rem;
    }
    
    .search-form {
        flex-direction: row;
        max-width: 600px;
    }
    
    /* Nouvelle section actions et recherche - Desktop */
    .search-action-container {
        flex-wrap: nowrap;
        gap: 1.25rem;
    }
    
    .search-form-inline {
        max-width: 450px;
        min-width: 300px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }
    
    .articles-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
    
    .action-buttons {
        flex-direction: row;
        justify-content: center;
    }
    
    .categories-grid {
        justify-content: center;
    }
    
    .article-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .article-category {
        width: 100%;
        justify-content: flex-start;
    }
    
    .article-rating {
        align-self: flex-end;
    }
}

/* Large Mobile (576px - 767px) */
@media (min-width: 576px) and (max-width: 767px) {
    :root {
        --container-padding: 1.5rem;
        --card-padding: 1.25rem;
        --section-margin: 2rem;
        --grid-gap: 1rem;
    }
    
    .kb-title {
        font-size: 2.2rem;
    }
    
    .search-form {
        flex-direction: column;
        max-width: 100%;
    }
    
    /* Nouvelle section actions et recherche - Large Tablet */
    .search-action-container {
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
    }
    
    .search-form-inline {
        flex-direction: row;
        max-width: 100%;
        min-width: unset;
        order: 1;
        width: 100%;
    }
    
    .create-article-btn {
        order: 2;
    }
    
    .admin-btn {
        order: 3;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .articles-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
    }
    
    .categories-grid {
        flex-direction: column;
        align-items: stretch;
    }
    
    .category-link {
        justify-content: center;
        text-align: center;
    }
    
    .article-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .article-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .alert {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .alert-content {
        width: 100%;
    }
    
    .clear-search {
        align-self: flex-end;
    }
}

/* Small Mobile (0 - 575px) */
@media (max-width: 575px) {
    :root {
        --container-padding: 1rem;
        --card-padding: 1rem;
        --section-margin: 1.5rem;
        --grid-gap: 0.75rem;
    }
    
    .kb-title {
        font-size: 1.8rem;
        margin-bottom: 0.75rem;
    }
    
    .kb-subtitle {
        font-size: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .kb-header {
        padding: 1.5rem 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .stat-card {
        min-height: 100px;
        padding: 1rem;
    }
    
    .articles-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .article-card {
        border-radius: 8px;
    }
    
    .article-header,
    .article-body,
    .article-footer {
        padding: 1rem;
    }
    
    .article-title {
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.75rem;
        padding: 0;
    }
    
    .btn {
        width: 100%;
        padding: 0.875rem 1rem;
        font-size: 0.95rem;
    }
    
    .search-form {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    /* Nouvelle section actions et recherche - Small Mobile */
    .search-action-container {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .search-form-inline {
        flex-direction: column;
        gap: 0.75rem;
        max-width: 100%;
        min-width: unset;
        width: 100%;
    }
    
    .create-article-btn,
    .admin-btn,
    .search-btn {
        width: 100%;
        justify-content: center;
    }
    
    .search-input-inline {
        width: 100%;
    }
    
    .search-action-section {
        padding: 1rem;
        margin: 1rem 0;
    }
    
    /* Responsive pour éléments IA - Mobile */
    .search-type-toggle {
        margin-right: 0;
        margin-bottom: 0.75rem;
        width: 100%;
    }
    
    .search-type-toggle .btn-group {
        width: 100%;
        display: flex;
    }
    
    .search-type-toggle .btn {
        flex: 1;
        text-align: center;
    }
    
    .ai-search-info {
        margin: 1rem 0;
        padding: 1rem;
    }
    
    .ai-search-badge {
        font-size: 0.9rem;
    }
    
    .ai-explanation {
        font-size: 0.8rem;
    }
    
    .article-badges {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .ai-reason {
        margin: 0 1rem;
        padding: 0.5rem;
        font-size: 0.8rem;
    }
    
    .form-control {
        padding: 0.875rem;
        font-size: 16px; /* Empêche le zoom sur iOS */
    }
    
    .categories-grid {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .category-link {
        justify-content: center;
        padding: 0.875rem 1rem;
        border-radius: 8px;
    }
    
    .categories-title {
        font-size: 1.1rem;
        margin-bottom: 1rem;
    }
    
    .article-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }
    
    .article-category {
        margin-bottom: 0.5rem;
    }
    
    .article-rating {
        align-self: flex-start;
    }
    
    .article-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .alert {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
        padding: 1rem;
    }
    
    .alert-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .clear-search {
        align-self: flex-end;
        margin-top: 0.5rem;
    }
    
    .empty-state {
        padding: 2rem 1rem;
    }
}

/* ========================================
   AMÉLIORATION TOUCH/TACTILE
======================================== */
@media (hover: none) and (pointer: coarse) {
    /* Appareils tactiles */
    .btn,
    .category-link,
    .article-tag,
    .clear-search {
        min-height: 44px;
        padding: 0.75rem 1rem;
    }
    
    .form-control {
        min-height: 48px;
        font-size: 16px; /* Empêche le zoom automatique */
    }
    
    .article-card:hover {
        transform: none; /* Pas d'effet hover sur tactile */
    }
    
    .stat-card:hover,
    .card:hover {
        transform: none;
    }
}

/* ========================================
   MODE PAYSAGE MOBILE
======================================== */
@media (max-height: 500px) and (orientation: landscape) {
    .kb-header {
        margin-bottom: 1.5rem;
        padding: 1rem;
    }
    
    .kb-title {
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
    }
    
    .kb-subtitle {
        font-size: 0.95rem;
        margin-bottom: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
        margin-bottom: 1.5rem;
    }
    
    .stat-card {
        min-height: 80px;
        padding: 0.75rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        font-size: 0.75rem;
    }
}

/* ========================================
   OPTIMISATION PRINT
======================================== */
@media print {
    .action-buttons,
    .search-form,
    .categories-nav,
    .alert {
        display: none;
    }
    
    .article-card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
    
    .kb-container {
        max-width: none;
        padding: 0;
    }
    
    .articles-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    body {
        background: white;
        color: black;
    }
    
    .stat-card,
    .card,
    .article-card {
        background: white;
        border: 1px solid #ddd;
        box-shadow: none;
    }
}
</style>

<div class="kb-container">
    <!-- Header -->
    <div class="kb-header">
        <h1 class="kb-title">
            <i class="fas fa-book-open"></i>
            Base de Connaissances
        </h1>
        <p class="kb-subtitle">
            Votre centre de ressources et de documentation technique
        </p>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-number"><?= number_format($kb_stats['total_articles']) ?></span>
            <span class="stat-label">Articles</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?= number_format($kb_stats['total_categories']) ?></span>
            <span class="stat-label">Catégories</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?= number_format($kb_stats['total_views']) ?></span>
            <span class="stat-label">Consultations</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?= number_format($kb_stats['popular_articles']) ?></span>
            <span class="stat-label">Articles populaires</span>
        </div>
    </div>

    <!-- Section unifiée : Actions et Recherche -->
    <div class="card search-action-section">
        <div class="search-action-container">
            <!-- Bouton Créer un article -->
            <a href="index.php?page=ajouter_article_kb" class="btn btn-primary create-article-btn">
                <i class="fas fa-plus-circle"></i>
                Créer un article
            </a>
            
            <!-- Formulaire de recherche -->
            <form action="index.php" method="GET" class="search-form-inline">
                <input type="hidden" name="page" value="base_connaissance_moderne">
                <?php if ($categorie_id > 0): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                <input type="hidden" name="categorie" value="<?= $categorie_id ?>">
                <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                
                <!-- 🧠 Toggle type de recherche -->
                <div class="search-type-toggle">
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="search_type" id="search_auto" value="auto" <?= $search_type === 'auto' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="search_auto" title="Détection automatique du type de recherche">
                            <i class="fas fa-magic"></i> Auto
                        </label>
                        
                        <input type="radio" class="btn-check" name="search_type" id="search_standard" value="standard" <?= $search_type === 'standard' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="search_standard" title="Recherche classique par mots-clés">
                            <i class="fas fa-search"></i> Standard
                        </label>
                        
                        <input type="radio" class="btn-check" name="search_type" id="search_intelligent" value="intelligent" <?= $search_type === 'intelligent' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="search_intelligent" title="Recherche intelligente avec IA - Posez des questions !">
                            <i class="fas fa-brain"></i> IA
                        </label>
                    </div>
                </div>
                
                <input type="text" name="recherche" class="form-control search-input-inline" 
                       placeholder="Ex: iPhone 12 OU Comment générer un code Maxer ?" 
                       value="<?= htmlspecialchars($recherche) ?>">
                <button class="btn btn-primary search-btn" type="submit">
                    <i class="fas fa-search"></i>
                    Rechercher
                </button>
            </form>
            
            <!-- Boutons d'administration (si nécessaire) -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
            <a href="index.php?page=gestion_kb" class="btn btn-secondary admin-btn">
                <i class="fas fa-cog"></i>
                Gérer les catégories
            </a>
            <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
        </div>
    </div>

    <!-- Navigation par catégories -->
    <div class="categories-section">
        <h3 class="categories-title">
            <i class="fas fa-folder-open"></i>
            Catégories
        </h3>
        <div class="categories-grid">
            <a href="index.php?page=base_connaissance_moderne<?= !empty($recherche) ? '&recherche='.urlencode($recherche) : '' ?>" 
               class="category-link <?= $categorie_id === 0 ? 'active' : '' ?>">
                <i class="fas fa-folder"></i>
                Toutes les catégories
            </a>
            
            <?php foreach ($categories as $categorie): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
            <a href="index.php?page=base_connaissance_moderne&categorie=<?= $categorie['id'] ?><?= !empty($recherche) ? '&recherche='.urlencode($recherche) : '' ?>" 
               class="category-link <?= $categorie_id === (int)$categorie['id'] ? 'active' : '' ?>">
                <i class="<?= htmlspecialchars($categorie['icon']) ?>"></i>
                <?= htmlspecialchars($categorie['name']) ?>
            </a>
            <?php endforeach; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
        </div>
    </div>

    <!-- Alerte de recherche -->
    <?php if (!empty($recherche)): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
    <div class="alert alert-info">
        <div class="alert-content">
            <i class="fas fa-search"></i>
            <span>Résultats de recherche pour : <strong><?= htmlspecialchars($recherche) ?></strong></span>
        </div>
        <a href="index.php?page=base_connaissance_moderne<?= $categorie_id > 0 ? '&categorie='.$categorie_id : '' ?>" 
           class="clear-search">
            <i class="fas fa-times"></i>
            Effacer
        </a>
    </div>
    <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>

    <!-- 🧠 INFORMATIONS DE RECHERCHE IA -->
    <?php if (!empty($recherche) && isset($search_metadata) && $search_metadata['type'] !== 'standard'): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
    <div class="ai-search-info">
        <div class="ai-search-badge">
            <i class="fas fa-brain"></i>
            <span>Recherche <?= $search_metadata['type'] === 'intelligent' ? 'IA' : ($search_metadata['type'] === 'hybrid' ? 'Hybride' : 'Auto') ?></span>
        </div>
        <?php if (!empty($search_metadata['explanation'])): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
        <div class="ai-explanation">
            <i class="fas fa-info-circle"></i>
            <span><?= htmlspecialchars($search_metadata['explanation']) ?></span>
        </div>
        <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
    </div>
    <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>

    <!-- Articles -->
    <?php if (empty($articles)): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
    <div class="empty-state">
        <i class="fas fa-search"></i>
        <h3>Aucun article trouvé</h3>
        <p>
            <?php if (!empty($recherche)): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                Aucun résultat pour votre recherche. Essayez avec d'autres termes ou 
                <a href="index.php?page=base_connaissance_moderne<?= $categorie_id > 0 ? '&categorie='.$categorie_id : '' ?>">
                    consultez tous les articles
                </a>.
            <?php else: ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                La base de connaissances est vide pour le moment.
            <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
        </p>
    </div>
    <?php else: ?>
<?php include_once 'includes/night-mode-system.php'; ?>
    <div class="articles-grid">
        <?php foreach ($articles as $article): 
<?php include_once 'includes/night-mode-system.php'; ?>
            // Récupérer les tags pour cet article
            $tags = get_article_tags($article['id']);
            
            // Calculer le taux d'utilité si des évaluations existent
            $utilite = 0;
            $rating_class = 'rating-poor';
            if ($article['rating_count'] > 0) {
                $utilite = round(($article['helpful_count'] / $article['rating_count']) * 100);
                if ($utilite >= 70) {
                    $rating_class = 'rating-excellent';
                } elseif ($utilite >= 40) {
                    $rating_class = 'rating-good';
                }
            }
        ?>
            <div class="article-card <?= isset($article['search_metadata']) && $article['search_metadata']['source'] === 'ai' ? 'ai-result' : '' ?>">
                <div class="article-header">
                    <div class="article-category">
                        <i class="<?= htmlspecialchars($article['category_icon']) ?>"></i>
                        <span><?= htmlspecialchars($article['category_name']) ?></span>
                    </div>
                    
                    <div class="article-badges">
                        <!-- 🧠 Badge IA Score -->
                        <?php if (isset($article['search_metadata']['ai_score']) && $article['search_metadata']['ai_score'] !== null): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                        <div class="ai-score-badge" title="Score de pertinence IA">
                            <i class="fas fa-brain"></i>
                            <?= $article['search_metadata']['ai_score'] ?>%
                        </div>
                        <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                        
                        <!-- 👍 Badge Rating -->
                        <?php if ($article['rating_count'] > 0): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                        <div class="article-rating <?= $rating_class ?>" 
                             title="<?= $article['helpful_count'] ?> sur <?= $article['rating_count'] ?> utilisateurs ont trouvé cet article utile">
                            <i class="fas fa-thumbs-up"></i>
                            <?= $utilite ?>%
                        </div>
                        <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    </div>
                </div>
                
                <!-- 🧠 Raison IA si disponible -->
                <?php if (isset($article['search_metadata']['ai_reason']) && !empty($article['search_metadata']['ai_reason'])): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                <div class="ai-reason">
                    <i class="fas fa-lightbulb"></i>
                    <span><?= htmlspecialchars($article['search_metadata']['ai_reason']) ?></span>
                </div>
                <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                
                <div class="article-body">
                    <a href="index.php?page=article_kb&id=<?= $article['id'] ?>" class="article-title">
                        <?= htmlspecialchars($article['title']) ?>
                    </a>
                    
                    <div class="article-excerpt">
                        <?= nl2br(htmlspecialchars(mb_substr(strip_tags($article['content']), 0, 150))) ?>...
                    </div>
                    
                    <?php if (!empty($tags)): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    <div class="article-tags">
                        <?php foreach ($tags as $tag): ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                        <a href="index.php?page=base_connaissance_moderne&recherche=<?= urlencode($tag['name']) ?>" 
                           class="article-tag">
                            <i class="fas fa-tag"></i>
                            <?= htmlspecialchars($tag['name']) ?>
                        </a>
                        <?php endforeach; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                    </div>
                    <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
                </div>
                
                <div class="article-footer">
                    <div>
                        <i class="fas fa-eye"></i>
                        <span><?= number_format($article['views']) ?> vues</span>
                    </div>
                    <div>
                        <i class="fas fa-calendar-alt"></i>
                        <span><?= date('d/m/Y', strtotime($article['updated_at'])) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
    </div>
    <?php endif; ?>
<?php include_once 'includes/night-mode-system.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    </script>