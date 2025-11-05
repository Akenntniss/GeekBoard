<?php
// Page principale de la base de connaissances - Version Moderne - CACHE BUST v2.0
$page_title = "Base de Connaissances";
require_once 'includes/header.php';

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
            error_log("Groq Search (article_kb_moderne): Aucun résultat, fallback vers recherche classique");
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
        
        error_log("Groq Search (article_kb_moderne) Success: " . count($search_results['articles']) . " articles trouvés (type: {$search_results['type']})");
        
        return $search_results['articles'];
        
    } catch (Exception $e) {
        error_log("Erreur Groq Search (article_kb_moderne): " . $e->getMessage() . " - Fallback vers recherche classique");
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

// Fonction pour extraire un extrait de contenu HTML
function extract_content_preview($html_content, $length = 200) {
    // Détecter si c'est du contenu HTML généré (avec wrapper)
    if (strpos($html_content, 'html-content-wrapper') !== false) {
        return "✨ Contenu HTML/CSS interactif - Cliquez pour voir";
    }
    
    // Pour le contenu normal, extraire le texte
    $text = strip_tags($html_content);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    
    return $text;
}

// Récupération des catégories et des articles
$categories = get_kb_categories();
$articles = get_kb_articles($categorie_id, $recherche, 50, $search_type);

// Calcul des statistiques
$total_articles = count($articles);
$articles_with_tags = array_filter($articles, function($article) {
    $tags = get_article_tags($article['id']);
    return !empty($tags);
});
$articles_populaires = array_filter($articles, function($article) {
    return $article['views'] > 10;
});
$articles_utiles = array_filter($articles, function($article) {
    $rating_count = $article['rating_count'] ?? 0;
    $helpful_count = $article['helpful_count'] ?? 0;
    return $rating_count > 0 && 
           (($helpful_count / $rating_count) * 100) >= 70;
});
?>

<style>
/* ========================================
   FORCE LAYOUT CORRECTION - CACHE BUST v2.0
======================================== */
.kb-layout-custom {
    display: flex !important;
    flex-direction: row !important;
    align-items: flex-start !important;
    width: 100% !important;
    gap: 1.5rem !important;
    margin: 0 !important;
    padding: 0 !important;
    box-sizing: border-box !important;
    clear: both !important;
}

.kb-layout-custom .sidebar {
    flex: 0 0 260px !important;
    width: 260px !important;
    max-width: 260px !important;
    min-width: 260px !important;
    position: relative !important;
    float: none !important;
}

.kb-layout-custom .main-content {
    flex: 1 1 auto !important;
    width: calc(100% - 260px - 1.5rem) !important;
    min-width: 0 !important;
    position: relative !important;
    float: none !important;
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .kb-layout-custom {
        flex-direction: column !important;
    }
    
    .kb-layout-custom .sidebar {
        flex: none !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
    }
    
    .kb-layout-custom .main-content {
        width: 100% !important;
    }
}

/* FIX NAVBAR - Obligatoire pour affichage correct */
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
    /* Container navbar */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.3rem 1rem !important;
    }
    /* Logo centré */
    body .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        z-index: 10001 !important;
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
   VARIABLES CSS POUR LES THÈMES
======================================== */
:root {
    /* Mode Jour - Moderne Dynamique */
    --day-primary: #3b82f6;
    --day-secondary: #8b5cf6;
    --day-accent: #06b6d4;
    --day-bg: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    --day-bg-animated: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff);
    --day-card-bg: rgba(255, 255, 255, 0.95);
    --day-text: #1e293b;
    --day-text-light: #64748b;
    --day-shadow: rgba(59, 130, 246, 0.15);
    --day-border: rgba(148, 163, 184, 0.2);

    /* Mode Nuit - Futuriste */
    --night-primary: #00d4ff;
    --night-secondary: #7c3aed;
    --night-accent: #ff00aa;
    --night-bg: #0a0a0a;
    --night-bg-animated: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483);
    --night-card-bg: rgba(15, 15, 25, 0.95);
    --night-text: #ffffff;
    --night-text-light: #a0aec0;
    --night-shadow: rgba(0, 212, 255, 0.25);
    --night-border: rgba(0, 212, 255, 0.3);
    --night-glow: 0 0 20px rgba(0, 212, 255, 0.5);
}

/* ========================================
   STRUCTURE DE BASE
======================================== */
body {
    margin: 0;
    padding: 0;
    padding-top: 80px; /* Espace pour la navbar fixe */
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow-x: hidden;
}

.modern-dashboard {
    position: relative;
    min-height: 100vh;
    padding: 1rem !important;
    transition: all 0.3s ease;
    margin-top: -80px; /* Remonter sous la navbar */
    padding-top: calc(80px + 1rem) !important; /* Compenser avec padding */
    width: 100% !important;
    max-width: 100vw !important;
    overflow-x: hidden;
    box-sizing: border-box !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
}

/* Surcharge Bootstrap Container */
.modern-dashboard.container,
.modern-dashboard.container-fluid {
    padding-left: 1rem !important;
    padding-right: 1rem !important;
    max-width: none !important;
}

/* ========================================
   ANIMATIONS DE FOND
======================================== */
.bg-animated {
    background: var(--day-bg-animated);
    background-size: 300% 300%;
    animation: gradientFlow 20s ease infinite;
}

.bg-animated.night-mode {
    background: var(--night-bg-animated);
    background-size: 400% 400%;
}

@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* ========================================
   ANIMATIONS MODERNES
======================================== */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.fade-in {
    animation: fadeIn 0.6s ease-out;
}

/* ========================================
   EN-TÊTE MODERNE
======================================== */
.modern-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--day-card-bg);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    animation: slideInUp 0.6s ease-out;
}

.modern-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--day-text);
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0;
}

.modern-title i {
    color: var(--day-primary);
    font-size: 2rem;
}

/* ========================================
   BOUTONS D'ACTION MODERNES
======================================== */
.modern-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%);
    color: white;
    text-decoration: none;
    border-radius: 15px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
    position: relative;
    overflow: hidden;
}

.modern-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.modern-btn:hover::before {
    left: 100%;
}

.modern-btn:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
}

.modern-btn--success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.modern-btn--success:hover {
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
}

.modern-btn--info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.modern-btn--info:hover {
    box-shadow: 0 10px 30px rgba(6, 182, 212, 0.4);
}

.modern-btn--warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.modern-btn--warning:hover {
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
}

/* ========================================
   STATISTIQUES MODERNES
======================================== */
.modern-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.modern-stat-card {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px var(--day-shadow);
    transition: all 0.3s ease;
    animation: slideInUp 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

.modern-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary), var(--day-accent));
    background-size: 200% 100%;
    animation: gradientFlow 3s ease infinite;
}

.modern-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px var(--day-shadow);
}

.stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1e293b !important; /* Noir en mode jour - Priorité forte */
    margin: 0;
    line-height: 1;
}

.stat-label {
    color: var(--day-text-light);
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0.5rem 0 0;
}

/* ========================================
   CONTRÔLES MODERNES
======================================== */
.modern-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    padding: 1.5rem;
    background: var(--day-card-bg);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
}

.modern-search {
    position: relative;
    flex: 1;
    min-width: 300px;
}

.modern-search input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid var(--day-border);
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.8);
    color: var(--day-text);
    font-size: 1rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.modern-search input:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    background: rgba(255, 255, 255, 1);
}

.modern-search i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--day-text-light);
    font-size: 1.1rem;
}

.modern-select {
    padding: 1rem;
    border: 2px solid var(--day-border);
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.8);
    color: var(--day-text);
    font-size: 1rem;
    min-width: 150px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.modern-select:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* ========================================
   GRILLE D'ARTICLES MODERNES
======================================== */
.articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    animation: slideInUp 0.6s ease-out;
    width: 100%;
    max-width: 100%;
}

@media (max-width: 1200px) {
    .articles-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
    }
}

@media (max-width: 900px) {
    .articles-grid {
        grid-template-columns: 1fr;
    }
}

.article-card {
    background: var(--day-card-bg);
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px var(--day-shadow);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    height: fit-content;
}

.article-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary), var(--day-accent));
    background-size: 200% 100%;
    animation: gradientFlow 4s ease infinite;
}

.article-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 50px var(--day-shadow);
}

.article-header {
    padding: 1.5rem 1.5rem 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.article-badges-modern {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
}

.article-category {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(59, 130, 246, 0.1);
    color: var(--day-primary);
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.875rem;
    font-weight: 600;
}

.article-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.875rem;
    font-weight: 600;
}

.article-body {
    padding: 1.5rem;
}

.article-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--day-text);
    margin-bottom: 1rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.article-title a {
    color: inherit;
    text-decoration: none;
    transition: color 0.3s ease;
}

.article-title a:hover {
    color: var(--day-primary);
}

.article-preview {
    color: var(--day-text-light);
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.article-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.article-tag {
    background: rgba(139, 92, 246, 0.1);
    color: var(--day-secondary);
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
}

.article-tag:hover {
    background: var(--day-secondary);
    color: white;
    transform: translateY(-1px);
}

.article-footer {
    padding: 0 1.5rem 1.5rem;
    display: flex;
    justify-content: between;
    align-items: center;
    border-top: 1px solid var(--day-border);
    padding-top: 1rem;
}

.article-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--day-text-light);
    flex: 1;
}

.article-views {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.article-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* ========================================
   MESSAGE VIDE
======================================== */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--day-card-bg);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
}

.empty-icon {
    font-size: 4rem;
    color: var(--day-text-light);
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.empty-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 1rem;
}

.empty-subtitle {
    color: var(--day-text-light);
    font-size: 1rem;
    line-height: 1.6;
}

/* ========================================
   SIDEBAR CATÉGORIES
======================================== */
.sidebar {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    margin-bottom: 2rem;
    height: fit-content;
    width: 260px !important;
    min-width: 220px !important;
    max-width: 260px !important;
    flex: 0 0 260px !important;
    position: relative;
    z-index: 2;
    box-sizing: border-box !important;
    float: none !important;
}

@media (max-width: 1200px) {
    .sidebar {
        width: 240px;
        max-width: 240px;
        flex: 0 0 240px;
    }
}

@media (max-width: 1024px) {
    .sidebar {
        width: 200px;
        max-width: 200px;
        flex: 0 0 200px;
    }
}

.sidebar-title {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--day-text);
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    text-align: center;
}

.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-item {
    margin-bottom: 0.5rem;
}

.category-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    color: var(--day-text);
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.category-link:hover {
    background: rgba(59, 130, 246, 0.1);
    color: var(--day-primary);
    transform: translateX(5px);
}

.category-link.active {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
}

/* ========================================
   CONTENU PRINCIPAL
======================================== */
.main-content {
    flex: 1 1 auto !important;
    min-width: 0 !important;
    overflow: hidden;
    width: 100% !important;
    position: relative;
    z-index: 1;
    box-sizing: border-box !important;
    padding: 0 !important;
    margin: 0 !important;
    float: none !important;
}

/* ========================================
   RESPONSIVE
======================================== */
@media (max-width: 768px) {
    .modern-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .modern-actions {
        width: 100%;
        justify-content: center;
    }
    
    .modern-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .modern-search {
        min-width: unset;
    }
    
    .modern-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .modern-title {
        font-size: 2rem;
    }

    .articles-grid {
        grid-template-columns: 1fr;
    }

    .main-layout {
        flex-direction: column;
        gap: 1rem;
    }

    .sidebar {
        order: -1;
        width: 100%;
        max-width: none;
        min-width: 0;
        flex: 0 0 auto;
        margin-bottom: 1.5rem;
    }
    
    .sidebar-title {
        font-size: 1.5rem;
    }
    
    .category-link {
        padding: 0.75rem;
        font-size: 0.9rem;
    }
}

/* ========================================
   MODE NUIT
======================================== */
body.night-mode {
    --day-primary: var(--night-primary);
    --day-secondary: var(--night-secondary);
    --day-accent: var(--night-accent);
    --day-card-bg: var(--night-card-bg);
    --day-text: var(--night-text);
    --day-text-light: var(--night-text-light);
    --day-shadow: var(--night-shadow);
    --day-border: var(--night-border);
}

body.night-mode .bg-animated {
    background: var(--night-bg-animated);
}

body.night-mode .modern-header,
body.night-mode .modern-stat-card,
body.night-mode .modern-controls,
body.night-mode .article-card,
body.night-mode .sidebar,
body.night-mode .empty-state {
    background: var(--night-card-bg);
    color: var(--night-text);
    border: 1px solid var(--night-border);
    box-shadow: 0 8px 32px var(--night-shadow);
}

body.night-mode .modern-title {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

body.night-mode .modern-search input,
body.night-mode .modern-select {
    background: rgba(15, 23, 42, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .modern-search input:focus,
body.night-mode .modern-select:focus {
    background: rgba(15, 23, 42, 0.9);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

/* ========================================
   TOGGLE TYPE DE RECHERCHE IA - VERSION MODERNE
======================================== */

.search-type-toggle-modern {
    margin-bottom: 1rem;
    display: flex;
    justify-content: center;
}

.search-type-toggle-modern .btn-group {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.search-type-toggle-modern .btn {
    font-size: 0.85rem;
    padding: 0.5rem 0.75rem;
    border: none;
    transition: all 0.3s ease;
    font-weight: 600;
}

.search-type-toggle-modern .btn-outline-primary {
    background: rgba(255, 255, 255, 0.9);
    color: var(--day-text-light);
    border: 1px solid var(--day-border);
}

.search-type-toggle-modern .btn-outline-primary:hover {
    background: var(--day-primary);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.search-type-toggle-modern .btn-check:checked + .btn-outline-primary {
    background: var(--day-primary);
    color: white;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
}

.search-type-toggle-modern i {
    margin-right: 0.35rem;
}

/* Mode nuit pour les toggles */
body.night-mode .search-type-toggle-modern .btn-outline-primary {
    background: rgba(15, 23, 42, 0.8);
    color: var(--night-text-light);
    border-color: var(--night-border);
}

body.night-mode .search-type-toggle-modern .btn-outline-primary:hover {
    background: var(--night-primary);
    color: white;
}

body.night-mode .search-type-toggle-modern .btn-check:checked + .btn-outline-primary {
    background: var(--night-primary);
    color: white;
}

/* ========================================
   AFFICHAGE RÉSULTATS IA - VERSION MODERNE
======================================== */

.ai-search-info-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 1.25rem 1.5rem;
    margin: 1.5rem 0;
    color: white;
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
    backdrop-filter: blur(10px);
}

.ai-search-badge-modern {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 0.75rem;
}

.ai-search-badge-modern i {
    color: #ffd700;
    font-size: 1.25rem;
}

.ai-explanation-modern {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: 0.95rem;
    opacity: 0.95;
    line-height: 1.5;
}

.ai-explanation-modern i {
    color: #b8d4ff;
    margin-top: 0.2rem;
    flex-shrink: 0;
}

/* Articles avec résultats IA - Version moderne */
.article-card.ai-result-modern {
    border: 2px solid #667eea;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.25);
    position: relative;
    background: rgba(102, 126, 234, 0.02);
}

.article-card.ai-result-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px 15px 0 0;
}

.ai-score-badge-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.35rem 0.65rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    box-shadow: 0 3px 8px rgba(102, 126, 234, 0.4);
    margin-left: 0.75rem;
}

.ai-score-badge-modern i {
    color: #ffd700;
    font-size: 0.85rem;
}

.ai-reason-modern {
    background: rgba(102, 126, 234, 0.08);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin: 1rem 1.5rem 0;
    font-size: 0.9rem;
    color: var(--day-text-light);
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    line-height: 1.5;
}

.ai-reason-modern i {
    color: #667eea;
    margin-top: 0.15rem;
    flex-shrink: 0;
    font-size: 1rem;
}

/* Mode nuit pour résultats IA */
body.night-mode .ai-reason-modern {
    background: rgba(102, 126, 234, 0.15);
    border-color: rgba(102, 126, 234, 0.3);
    color: var(--night-text-light);
}

/* ========================================
   CONTAINER ET BOUTON DE RECHERCHE - VERSION ÉTENDUE
======================================== */

.expanded-search-container {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1rem;
    width: 100%;
}

.expanded-search-input {
    flex: 1; /* Prend tout l'espace libre */
    position: relative;
    display: flex;
    align-items: center;
    background: var(--card-bg);
    border: 2px solid var(--border);
    border-radius: 15px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.expanded-search-input:focus-within {
    border-color: var(--primary);
    background: var(--card-bg-hover);
    box-shadow: 0 0 0 3px var(--primary-light);
}

.expanded-search-input i {
    color: var(--text-secondary);
    margin-right: 0.75rem;
    font-size: 1.1rem;
}

.expanded-search-input input {
    flex: 1;
    border: none;
    background: transparent;
    color: var(--text);
    font-size: 1rem;
    font-weight: 500;
    outline: none;
}

.expanded-search-input input::placeholder {
    color: var(--text-secondary);
    font-weight: 400;
}

.search-controls-right {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-shrink: 0; /* Ne rétrécit pas */
}

.modern-select-compact {
    background: var(--card-bg);
    border: 2px solid var(--border);
    border-radius: 12px;
    padding: 0.6rem 1rem;
    color: var(--text);
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 180px;
}

.modern-select-compact:hover {
    border-color: var(--primary);
    background: var(--card-bg-hover);
}

.modern-select-compact:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-light);
    outline: none;
}

.modern-search-btn-compact {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border: none;
    border-radius: 12px;
    padding: 0.6rem 1.2rem;
    color: white;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
}

.modern-search-btn-compact:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--primary-light);
}

.modern-search-btn-compact:active {
    transform: translateY(0);
}

/* Legacy container pour compatibilité */
.modern-search-container {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    margin-bottom: 1rem;
}

.modern-search {
    flex: 1;
}

.modern-search-btn {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    border: none;
    padding: 1rem 1.5rem;
    border-radius: 15px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    backdrop-filter: blur(10px);
    white-space: nowrap;
    min-height: 52px;
}

.modern-search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}

.modern-search-btn:active {
    transform: translateY(0);
}

.modern-search-btn i {
    font-size: 1rem;
}

/* Mode nuit pour bouton recherche */
body.night-mode .modern-search-btn {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    box-shadow: 0 4px 12px var(--night-shadow);
}

body.night-mode .modern-search-btn:hover {
    box-shadow: 0 8px 25px var(--night-glow);
}

/* Responsive pour container recherche étendu */
@media (max-width: 992px) {
    .expanded-search-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .search-controls-right {
        width: 100%;
        justify-content: space-between;
    }
    
    .modern-select-compact {
        flex: 1;
        min-width: auto;
    }
    
    .modern-search-btn-compact {
        min-width: 120px;
    }
}

@media (max-width: 768px) {
    .search-controls-right {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .modern-select-compact,
    .modern-search-btn-compact {
        width: 100%;
        justify-content: center;
    }
}

/* Legacy responsive pour compatibilité */
@media (max-width: 768px) {
    .modern-search-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .modern-search-btn {
        justify-content: center;
    }
}

/* Règle spécifique pour mode jour */
body:not(.night-mode) .stat-value {
    color: #1e293b !important; /* Noir en mode jour */
}

body.night-mode .stat-value {
    color: var(--night-text) !important; /* Blanc en mode nuit - Priorité forte */
}

/* ========================================
   MAIN LAYOUT - SURCHARGE BOOTSTRAP
======================================== */
.main-layout,
.kb-layout-custom {
    display: flex !important;
    flex-direction: row !important;
    gap: 1.5rem;
    align-items: flex-start !important;
    width: 100% !important;
    max-width: 100% !important;
    overflow: hidden;
    box-sizing: border-box !important;
    margin: 0 !important;
    padding: 0 !important;
    clear: both !important;
}

/* Force le layout même avec Bootstrap */
div.kb-layout-custom {
    display: flex !important;
    flex-wrap: nowrap !important;
}

/* Surcharge des classes Bootstrap potentielles */
.main-layout.row {
    margin-left: 0 !important;
    margin-right: 0 !important;
}

.main-layout > * {
    padding-left: 0 !important;
    padding-right: 0 !important;
}

@media (max-width: 1200px) {
    .main-layout {
        gap: 1.25rem;
    }
}

@media (max-width: 1024px) {
    .main-layout {
        gap: 1rem;
    }
}
</style>

<div class="modern-dashboard bg-animated" id="dashboard">
    
    <!-- En-tête moderne (sera déplacé dans main-content) -->
    <div class="modern-header fade-in" style="display:none">
        <h1 class="modern-title">
            <i class="fas fa-book-open"></i>
            Base de Connaissances
        </h1>
        <div class="modern-actions">
            <a href="index.php?page=ajouter_article_kb_moderne" class="modern-btn modern-btn--success">
                <i class="fas fa-plus"></i>
                Créer un article
            </a>
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')): ?>
            <a href="index.php?page=gestion_kb" class="modern-btn modern-btn--info">
                <i class="fas fa-cog"></i>
                Gérer
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistiques modernes (masquées, réinsérées à droite de la sidebar) -->
    <div class="modern-stats-grid fade-in" style="display:none">
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $total_articles; ?></div>
            <div class="stat-label">Total Articles</div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-tags"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo count($articles_with_tags); ?></div>
            <div class="stat-label">Avec Tags</div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-fire"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo count($articles_populaires); ?></div>
            <div class="stat-label">Populaires</div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <i class="fas fa-thumbs-up"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo count($articles_utiles); ?></div>
            <div class="stat-label">Très Utiles</div>
        </div>
    </div>

    <!-- Contrôles modernes (masqués, réinsérés à droite de la sidebar) -->
    <div class="modern-controls fade-in" style="display:none">
        <div class="modern-search">
            <i class="fas fa-search"></i>
            <input id="kbSearchHidden" placeholder="Rechercher dans la base de connaissances..." value="<?= htmlspecialchars($recherche) ?>" />
        </div>
        <select id="kbCategoryFilterHidden" class="modern-select">
            <option value="0">Toutes les catégories</option>
            <?php foreach ($categories as $category): ?>
            <option value="<?= $category['id'] ?>" <?= $categorie_id === (int)$category['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($category['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Layout principal -->
    <div class="main-layout fade-in kb-layout-custom">
        <!-- Sidebar catégories -->
        <div class="sidebar">
            <h3 class="sidebar-title">
                <i class="fas fa-folder-open"></i>
                Catégories
            </h3>
            <ul class="category-list">
                <li class="category-item">
                    <a href="index.php?page=article_kb_moderne<?= !empty($recherche) ? '&recherche='.urlencode($recherche) : '' ?>" 
                       class="category-link <?= $categorie_id === 0 ? 'active' : '' ?>">
                        <i class="fas fa-folder"></i>
                        Toutes les catégories
                    </a>
                </li>
                <?php foreach ($categories as $category): ?>
                <li class="category-item">
                    <a href="index.php?page=article_kb_moderne&categorie=<?= $category['id'] ?><?= !empty($recherche) ? '&recherche='.urlencode($recherche) : '' ?>" 
                       class="category-link <?= $categorie_id === (int)$category['id'] ? 'active' : '' ?>">
                        <i class="<?= htmlspecialchars($category['icon'] ?? 'fas fa-folder') ?>"></i>
                        <?= htmlspecialchars($category['name']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

         <!-- Contenu principal -->
         <div class="main-content">
             <!-- En-tête moderne aligné avec le contenu -->
             <div class="modern-header fade-in">
                 <h1 class="modern-title">
                     <i class="fas fa-book-open"></i>
                     Base de Connaissances
                 </h1>
                 <div class="modern-actions">
                     <a href="index.php?page=ajouter_article_kb_moderne" class="modern-btn modern-btn--success">
                         <i class="fas fa-plus"></i>
                         Créer un article
                     </a>
                     <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')): ?>
                     <a href="index.php?page=gestion_kb" class="modern-btn modern-btn--info">
                         <i class="fas fa-cog"></i>
                         Gérer
                     </a>
                     <?php endif; ?>
                 </div>
             </div>

             <!-- Statistiques modernes (affichées à droite de la sidebar) -->
             <div class="modern-stats-grid fade-in">
                <div class="modern-stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $total_articles; ?></div>
                    <div class="stat-label">Total Articles</div>
                </div>
                
                <div class="modern-stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo count($articles_with_tags); ?></div>
                    <div class="stat-label">Avec Tags</div>
                </div>
                
                <div class="modern-stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-fire"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo count($articles_populaires); ?></div>
                    <div class="stat-label">Populaires</div>
                </div>
                
                <div class="modern-stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <i class="fas fa-thumbs-up"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo count($articles_utiles); ?></div>
                    <div class="stat-label">Très Utiles</div>
                </div>
            </div>

            <!-- Contrôles modernes (affichés à droite de la sidebar) -->
            <div class="modern-controls fade-in" style="margin-top: 0;">
                
                <!-- 🧠 Toggle type de recherche IA -->
                <div class="search-type-toggle-modern">
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="search_type_ui" id="search_auto_ui" value="auto" <?= $search_type === 'auto' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="search_auto_ui" title="Détection automatique du type de recherche">
                            <i class="fas fa-magic"></i> Auto
                        </label>
                        
                        <input type="radio" class="btn-check" name="search_type_ui" id="search_standard_ui" value="standard" <?= $search_type === 'standard' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="search_standard_ui" title="Recherche classique par mots-clés">
                            <i class="fas fa-search"></i> Standard
                        </label>
                        
                        <input type="radio" class="btn-check" name="search_type_ui" id="search_intelligent_ui" value="intelligent" <?= $search_type === 'intelligent' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="search_intelligent_ui" title="Recherche intelligente avec IA - Posez des questions !">
                            <i class="fas fa-brain"></i> IA
                        </label>
                    </div>
                </div>
                
                <div class="expanded-search-container">
                    <div class="expanded-search-input">
                        <i class="fas fa-search"></i>
                        <input id="kbSearch" placeholder="Ex: iPhone 12 OU Comment générer un code Maxer ?" value="<?= htmlspecialchars($recherche) ?>" />
                    </div>
                    <div class="search-controls-right">
                        <select id="kbCategoryFilter" class="modern-select-compact">
                            <option value="0">Toutes les catégories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $categorie_id === (int)$category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button id="searchButton" class="modern-search-btn-compact" title="Lancer la recherche">
                            <i class="fas fa-search"></i>
                            Rechercher
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($recherche)): ?>
            <div style="background: rgba(6, 182, 212, 0.1); color: #0891b2; padding: 1rem 1.5rem; border-radius: 15px; margin-bottom: 2rem; border: 1px solid rgba(6, 182, 212, 0.3);">
                <i class="fas fa-search" style="margin-right: 0.75rem;"></i>
                Résultats de recherche pour : <strong><?= htmlspecialchars($recherche) ?></strong>
                <a href="index.php?page=article_kb_moderne<?= $categorie_id > 0 ? '&categorie='.$categorie_id : '' ?>" 
                   style="float: right; color: #0891b2; text-decoration: none;">
                    <i class="fas fa-times"></i> Effacer
                </a>
            </div>
            <?php endif; ?>

            <!-- 🧠 INFORMATIONS DE RECHERCHE IA -->
            <?php if (!empty($recherche) && isset($search_metadata) && $search_metadata['type'] !== 'standard'): ?>
            <div class="ai-search-info-modern">
                <div class="ai-search-badge-modern">
                    <i class="fas fa-brain"></i>
                    <span>Recherche <?= $search_metadata['type'] === 'intelligent' ? 'IA' : ($search_metadata['type'] === 'hybrid' ? 'Hybride' : 'Auto') ?></span>
                </div>
                <?php if (!empty($search_metadata['explanation'])): ?>
                <div class="ai-explanation-modern">
                    <i class="fas fa-info-circle"></i>
                    <span><?= htmlspecialchars($search_metadata['explanation']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if (empty($articles)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="empty-title">Aucun article trouvé</div>
                <div class="empty-subtitle">
                    <?php if (!empty($recherche)): ?>
                    Essayez avec d'autres termes de recherche ou 
                    <a href="index.php?page=article_kb_moderne<?= $categorie_id > 0 ? '&categorie='.$categorie_id : '' ?>" style="color: var(--day-primary);">
                        consultez tous les articles
                    </a>.
                    <?php else: ?>
                    Commencez par créer votre premier article dans la base de connaissances.
                    <br><br>
                    <a href="index.php?page=ajouter_article_kb_moderne" class="modern-btn modern-btn--success">
                        <i class="fas fa-plus"></i>
                        Créer un article
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            
            <div class="articles-grid">
                <?php foreach ($articles as $article): 
                    // Récupérer les tags pour cet article
                    $tags = get_article_tags($article['id']);
                    
                    // Calculer le taux d'utilité si des évaluations existent
                    $utilite = 0;
                    $rating_count = $article['rating_count'] ?? 0;
                    $helpful_count = $article['helpful_count'] ?? 0;
                    
                    if ($rating_count > 0) {
                        $utilite = round(($helpful_count / $rating_count) * 100);
                    }

                    // Extraire un aperçu du contenu
                    $preview = extract_content_preview($article['content']);
                ?>
                <div class="article-card <?= isset($article['search_metadata']) && $article['search_metadata']['source'] === 'ai' ? 'ai-result-modern' : '' ?>" onclick="window.location.href='index.php?page=visu_article_moderne&id=<?= $article['id'] ?>'" style="cursor: pointer;">
                    <div class="article-header">
                        <?php if (!empty($article['category_name'])): ?>
                        <div class="article-category">
                            <i class="<?= htmlspecialchars($article['category_icon'] ?? 'fas fa-folder') ?>"></i>
                            <?= htmlspecialchars($article['category_name']) ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="article-badges-modern">
                            <!-- 🧠 Badge IA Score -->
                            <?php if (isset($article['search_metadata']['ai_score']) && $article['search_metadata']['ai_score'] !== null): ?>
                            <div class="ai-score-badge-modern" title="Score de pertinence IA">
                                <i class="fas fa-brain"></i>
                                <?= $article['search_metadata']['ai_score'] ?>%
                            </div>
                            <?php endif; ?>
                            
                            <!-- 👍 Badge Rating -->
                            <?php if ($rating_count > 0): ?>
                            <div class="article-rating" style="background: linear-gradient(135deg, <?= $utilite >= 70 ? '#10b981, #059669' : ($utilite >= 40 ? '#f59e0b, #d97706' : '#ef4444, #dc2626') ?>);">
                                <i class="fas fa-thumbs-up"></i>
                                <?= $utilite ?>%
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="article-body">
                        <h3 class="article-title">
                            <a href="index.php?page=visu_article_moderne&id=<?= $article['id'] ?>">
                                <?= htmlspecialchars($article['title']) ?>
                            </a>
                        </h3>
                        
                        <div class="article-preview">
                            <?= $preview ?>
                        </div>
                        
                        <?php if (!empty($tags)): ?>
                        <div class="article-tags">
                            <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                            <a href="index.php?page=article_kb_moderne&recherche=<?= urlencode($tag['name']) ?>" 
                               class="article-tag">
                                <?= htmlspecialchars($tag['name']) ?>
                            </a>
                            <?php endforeach; ?>
                            <?php if (count($tags) > 3): ?>
                            <span class="article-tag" style="background: rgba(156, 163, 175, 0.2); color: var(--day-text-light);">
                                +<?= count($tags) - 3 ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="article-footer">
                        <div class="article-meta">
                            <div class="article-views">
                                <i class="fas fa-eye"></i>
                                <?= $article['views'] ?>
                            </div>
                            <div class="article-date">
                                <i class="fas fa-clock"></i>
                                <?= date('d/m/Y', strtotime($article['updated_at'])) ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 🧠 Raison IA si disponible -->
                    <?php if (isset($article['search_metadata']['ai_reason']) && !empty($article['search_metadata']['ai_reason'])): ?>
                    <div class="ai-reason-modern">
                        <i class="fas fa-lightbulb"></i>
                        <span><?= htmlspecialchars($article['search_metadata']['ai_reason']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 🧠 MODAL CHOIX TYPE DE RECHERCHE -->
<div id="searchTypeModal" class="search-modal-overlay" style="display: none;">
    <div class="search-modal">
        <div class="search-modal-header">
            <h3>
                <i class="fas fa-search"></i>
                Comment voulez-vous rechercher ?
            </h3>
            <button class="search-modal-close" onclick="closeSearchModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="search-modal-body">
            <p class="search-modal-description">
                Choisissez le type de recherche le mieux adapté à votre besoin :
            </p>
            
            <div class="search-options">
                <div class="search-option" onclick="selectSearchType('standard')">
                    <div class="search-option-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="search-option-content">
                        <h4>Recherche Standard</h4>
                        <p>Recherche classique par mots-clés dans les titres et contenus</p>
                        <span class="search-option-example">Ex: "iPhone", "activation", "réparation"</span>
                    </div>
                </div>
                
                <div class="search-option" onclick="selectSearchType('intelligent')">
                    <div class="search-option-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <div class="search-option-content">
                        <h4>Recherche Intelligente (IA)</h4>
                        <p>Analyse contextuelle avancée pour comprendre vos questions</p>
                        <span class="search-option-example">Ex: "Comment générer un code Maxer ?"</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ========================================
   MODAL CHOIX TYPE DE RECHERCHE
======================================== */

.search-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(8px);
    animation: modalFadeIn 0.3s ease;
}

.search-modal {
    background: var(--day-card-bg);
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
    animation: modalSlideIn 0.3s ease;
}

.search-modal-header {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.search-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.search-modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.search-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.search-modal-body {
    padding: 2rem;
}

.search-modal-description {
    color: var(--day-text-light);
    margin-bottom: 2rem;
    text-align: center;
    font-size: 1rem;
}

.search-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.search-option {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    padding: 1.5rem;
    border: 2px solid var(--day-border);
    border-radius: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--day-card-bg);
}

.search-option:hover {
    border-color: var(--day-primary);
    background: rgba(59, 130, 246, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
}

.search-option-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.search-option:first-child .search-option-icon {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.search-option:last-child .search-option-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.search-option-content {
    flex: 1;
}

.search-option-content h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--day-text);
}

.search-option-content p {
    margin: 0 0 0.75rem 0;
    color: var(--day-text-light);
    font-size: 0.9rem;
    line-height: 1.4;
}

.search-option-example {
    display: inline-block;
    background: var(--day-surface);
    color: var(--day-text-light);
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-family: monospace;
    border: 1px solid var(--day-border);
}

/* Mode nuit pour modal */
body.night-mode .search-modal {
    background: var(--night-card-bg);
    border: 1px solid var(--night-border);
}

body.night-mode .search-modal-header {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
}

body.night-mode .search-modal-description {
    color: var(--night-text-light);
}

body.night-mode .search-option {
    background: var(--night-card-bg);
    border-color: var(--night-border);
}

body.night-mode .search-option:hover {
    border-color: var(--night-primary);
    background: rgba(102, 126, 234, 0.1);
}

body.night-mode .search-option-content h4 {
    color: var(--night-text);
}

body.night-mode .search-option-content p {
    color: var(--night-text-light);
}

body.night-mode .search-option-example {
    background: var(--night-surface);
    color: var(--night-text-light);
    border-color: var(--night-border);
}

/* Animations */
@keyframes modalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes modalSlideIn {
    from { 
        opacity: 0;
        transform: translateY(-30px) scale(0.95);
    }
    to { 
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .search-modal {
        width: 95%;
        margin: 1rem;
    }
    
    .search-modal-header {
        padding: 1rem 1.5rem;
    }
    
    .search-modal-body {
        padding: 1.5rem;
    }
    
    .search-option {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .search-options {
        gap: 1.5rem;
    }
}
</style>

<script>
// Détection IMMÉDIATE du mode nuit (avant DOMContentLoaded)
(function() {
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('theme');
    
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.documentElement.classList.add('night-mode');
        document.body.classList.add('night-mode');
    } else {
        document.documentElement.classList.remove('night-mode');
        document.body.classList.remove('night-mode');
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    // Fonction de détection automatique du mode nuit
    function detectAndApplyDarkMode() {
        // Détecter si l'utilisateur préfère le mode sombre
        const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Vérifier s'il y a une préférence stockée en localStorage
        const storedTheme = localStorage.getItem('theme');
        
        // Appliquer le thème
        if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
            document.body.classList.add('night-mode');
        } else {
            document.body.classList.remove('night-mode');
        }
    }

    // Écouter les changements de préférence système
    if (window.matchMedia) {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addListener(function(e) {
            // Si aucune préférence n'est stockée, suivre les préférences système
            if (localStorage.getItem('theme') === null) {
                if (e.matches) {
                    document.body.classList.add('night-mode');
                } else {
                    document.body.classList.remove('night-mode');
                }
            }
        });
    }

    // Fonction pour basculer manuellement le mode (si vous voulez ajouter un bouton plus tard)
    function toggleDarkMode() {
        document.body.classList.toggle('night-mode');
        const isDark = document.body.classList.contains('night-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    }

    // Logique de recherche (sur clic/Entrée uniquement)
    const searchInput = document.getElementById('kbSearch');
    const categoryFilter = document.getElementById('kbCategoryFilter');
    const searchButton = document.getElementById('searchButton');
    
    // Variables globales pour le modal
    let pendingSearchTerm = '';
    
    // Fonction pour vérifier si un type de recherche est sélectionné
    function isSearchTypeSelected() {
        const searchTypeRadio = document.querySelector('input[name="search_type_ui"]:checked');
        return searchTypeRadio !== null;
    }
    
    // Fonction pour effectuer la recherche
    function performSearch(searchType = null) {
        const searchTerm = searchInput.value.trim();
        const categoryId = categoryFilter.value;
        
        if (!searchTerm) {
            return; // Ne rien faire si pas de terme de recherche
        }
        
        // Récupérer le type de recherche
        let selectedSearchType = searchType;
        if (!selectedSearchType) {
            const searchTypeRadio = document.querySelector('input[name="search_type_ui"]:checked');
            selectedSearchType = searchTypeRadio ? searchTypeRadio.value : 'auto';
        }
        
        let url = 'index.php?page=article_kb_moderne';
        
        url += '&recherche=' + encodeURIComponent(searchTerm);
        
        if (categoryId && categoryId !== '0') {
            url += '&categorie=' + categoryId;
        }
        
        // Ajouter le type de recherche
        if (selectedSearchType && selectedSearchType !== 'auto') {
            url += '&search_type=' + selectedSearchType;
        }
        
        window.location.href = url;
    }
    
    // Fonction pour ouvrir le modal de choix
    function showSearchModal() {
        const modal = document.getElementById('searchTypeModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Empêcher le scroll
        }
    }
    
    // Fonction pour fermer le modal
    function closeSearchModal() {
        const modal = document.getElementById('searchTypeModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Restaurer le scroll
            pendingSearchTerm = '';
        }
    }
    
    // Fonction pour sélectionner le type de recherche depuis le modal
    function selectSearchType(type) {
        // Sélectionner automatiquement le radio button correspondant
        const targetRadio = document.querySelector(`input[name="search_type_ui"][value="${type}"]`);
        if (targetRadio) {
            targetRadio.checked = true;
        }
        
        closeSearchModal();
        
        // Effectuer la recherche avec le type sélectionné
        if (pendingSearchTerm) {
            performSearch(type);
        }
    }
    
    // Fonction pour gérer le déclenchement de la recherche
    function handleSearchTrigger() {
        const searchTerm = searchInput.value.trim();
        
        if (!searchTerm) {
            return; // Ne rien faire si pas de terme
        }
        
        // Vérifier si un type de recherche est sélectionné
        if (!isSearchTypeSelected()) {
            // Aucun type sélectionné, montrer le modal
            pendingSearchTerm = searchTerm;
            showSearchModal();
        } else {
            // Type sélectionné, effectuer la recherche directement
            performSearch();
        }
    }
    
    // Event listeners
    
    // Clic sur le bouton de recherche
    if (searchButton) {
        searchButton.addEventListener('click', handleSearchTrigger);
    }
    
    // Touche Entrée dans le champ de recherche
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleSearchTrigger();
            }
        });
    }
    
    // Changement de catégorie (recherche directe si il y a déjà un terme)
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            const searchTerm = searchInput.value.trim();
            if (searchTerm && isSearchTypeSelected()) {
                performSearch();
            }
        });
    }
    
    // Fermer le modal en cliquant à l'extérieur
    const modal = document.getElementById('searchTypeModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSearchModal();
            }
        });
    }
    
    // Fermer le modal avec Echap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSearchModal();
        }
    });
    
    // Exposer les fonctions globalement pour les onclick
    window.closeSearchModal = closeSearchModal;
    window.selectSearchType = selectSearchType;

    // Animation d'entrée des cartes
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observer les cartes d'articles pour l'animation
    document.querySelectorAll('.article-card').forEach(function(card) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });

    // Initialisation
    detectAndApplyDarkMode();
    
});
</script>

<?php require_once 'includes/footer.php'; ?>
