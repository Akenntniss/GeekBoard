<?php
// Page de visualisation d'un article de la base de connaissances - Version Moderne
$page_title = "Article Base de Connaissances";
require_once 'includes/header.php';

// Vérifier si un ID d'article est spécifié
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_message("L'article demandé n'existe pas.", "danger");
    redirect('article_kb_moderne');
}

$article_id = intval($_GET['id']);

// Récupérer l'article spécifié
function get_kb_article($id) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "
            SELECT a.*, c.name as category_name, c.icon as category_icon 
            FROM kb_articles a
            LEFT JOIN kb_categories c ON a.category_id = c.id
            WHERE a.id = ?
        ";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Incrémenter le compteur de vues si l'article existe
        if ($article) {
            $update = "UPDATE kb_articles SET views = views + 1 WHERE id = ?";
            $stmt = $shop_pdo->prepare($update);
            $stmt->execute([$id]);
        }
        
        return $article;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de l'article KB: " . $e->getMessage());
        return false;
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

// Vérifier si l'utilisateur a déjà évalué cet article
function has_user_rated_article($article_id, $user_id) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "SELECT id FROM kb_article_ratings WHERE article_id = ? AND user_id = ?";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$article_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification de l'évaluation: " . $e->getMessage());
        return false;
    }
}

// Récupération des statistiques d'évaluation
function get_rating_stats($article_id) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "
            SELECT COUNT(*) as total_ratings,
                   SUM(CASE WHEN is_helpful = 1 THEN 1 ELSE 0 END) as helpful_count
            FROM kb_article_ratings
            WHERE article_id = ?
        ";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$article_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculer le pourcentage d'utilité
        if ($stats['total_ratings'] > 0) {
            $stats['helpful_percent'] = round(($stats['helpful_count'] / $stats['total_ratings']) * 100);
        } else {
            $stats['helpful_percent'] = 0;
        }
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques d'évaluation: " . $e->getMessage());
        return ['total_ratings' => 0, 'helpful_count' => 0, 'helpful_percent' => 0];
    }
}

// Récupération des articles associés (même catégorie)
function get_related_articles($article_id, $category_id, $limit = 5) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "
            SELECT id, title, views
            FROM kb_articles 
            WHERE id != ? AND category_id = ?
            ORDER BY views DESC
            LIMIT ?
        ";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$article_id, $category_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des articles associés: " . $e->getMessage());
        return [];
    }
}

// Traitement de l'évaluation
if (isset($_POST['action']) && $_POST['action'] === 'rate_article' && isset($_SESSION['user_id'])) {
    $is_helpful = isset($_POST['is_helpful']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    
    try {
        $shop_pdo = getShopDBConnection();
        // Vérifier si l'utilisateur a déjà évalué cet article
        if (!has_user_rated_article($article_id, $user_id)) {
            $query = "INSERT INTO kb_article_ratings (article_id, user_id, is_helpful, rated_at) VALUES (?, ?, ?, NOW())";
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$article_id, $user_id, $is_helpful]);
            
            set_message("Merci pour votre évaluation !", "success");
        } else {
            set_message("Vous avez déjà évalué cet article.", "warning");
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de l'enregistrement de l'évaluation: " . $e->getMessage());
        set_message("Une erreur est survenue lors de l'enregistrement de votre évaluation.", "danger");
    }
    
    // Rediriger pour éviter le rechargement du formulaire
    redirect('visu_article_moderne', ['id' => $article_id]);
}

// Récupérer l'article
$article = get_kb_article($article_id);

// Si l'article n'existe pas, rediriger vers la liste des articles
if (!$article) {
    set_message("L'article demandé n'existe pas.", "danger");
    redirect('article_kb_moderne');
}

// Récupérer les tags de l'article
$tags = get_article_tags($article_id);

// Récupérer les statistiques d'évaluation
$rating_stats = get_rating_stats($article_id);

// Récupérer les articles associés
$related_articles = get_related_articles($article_id, $article['category_id']);

// Déterminer si l'utilisateur a déjà évalué cet article
$user_has_rated = isset($_SESSION['user_id']) ? has_user_rated_article($article_id, $_SESSION['user_id']) : false;

// Mettre à jour le titre de la page avec le titre de l'article
$page_title = $article['title'] . " | Base de Connaissances";
?>

<style>
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
    padding: 1rem;
    transition: all 0.3s ease;
    margin-top: -80px; /* Remonter sous la navbar */
    padding-top: calc(80px + 1rem); /* Compenser avec padding */
    width: 100%;
    max-width: 100vw;
    overflow-x: hidden;
    box-sizing: border-box;
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

.modern-btn--danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.modern-btn--danger:hover {
    box-shadow: 0 10px 30px rgba(239, 68, 68, 0.4);
}

.modern-btn--secondary {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
}

.modern-btn--secondary:hover {
    box-shadow: 0 10px 30px rgba(107, 114, 128, 0.4);
}

/* ========================================
   BREADCRUMB MODERNE
======================================== */
.modern-breadcrumb {
    background: var(--day-card-bg);
    border-radius: 15px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 4px 16px var(--day-shadow);
    animation: slideInUp 0.6s ease-out;
}

.modern-breadcrumb-list {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 0.95rem;
}

.modern-breadcrumb-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modern-breadcrumb-item:not(:last-child)::after {
    content: '›';
    color: var(--day-text-light);
    font-weight: 600;
    margin-left: 0.75rem;
}

.modern-breadcrumb-link {
    color: var(--day-text-light);
    text-decoration: none;
    transition: color 0.3s ease;
    font-weight: 500;
}

.modern-breadcrumb-link:hover {
    color: var(--day-primary);
}

.modern-breadcrumb-current {
    color: var(--day-text);
    font-weight: 600;
}

/* ========================================
   ARTICLE CONTENT
======================================== */
.article-main {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
    width: 100%;
    max-width: 100%;
}

@media (max-width: 1200px) {
    .article-main {
        grid-template-columns: 1fr 250px;
        gap: 1.5rem;
    }
}

@media (max-width: 900px) {
    .article-main {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

.article-content {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 2rem;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    animation: slideInUp 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

.article-content::before {
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

.article-header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--day-border);
}

.article-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--day-text);
    margin-bottom: 1rem;
    line-height: 1.3;
}

.article-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.article-meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(59, 130, 246, 0.1);
    color: var(--day-primary);
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 600;
}

.article-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.article-tag {
    background: rgba(139, 92, 246, 0.1);
    color: var(--day-secondary);
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.article-tag:hover {
    background: var(--day-secondary);
    color: white;
    transform: translateY(-2px);
}

.article-body {
    font-size: 1.1rem;
    line-height: 1.8;
    color: var(--day-text);
}

.article-body h1, .article-body h2, .article-body h3,
.article-body h4, .article-body h5, .article-body h6 {
    margin-top: 2.5rem;
    margin-bottom: 1.5rem;
    font-weight: 700;
    color: var(--day-text);
}

.article-body h1 { font-size: 2rem; }
.article-body h2 { font-size: 1.75rem; }
.article-body h3 { font-size: 1.5rem; }
.article-body h4 { font-size: 1.25rem; }

.article-body p {
    margin-bottom: 1.5rem;
    white-space: pre-wrap;
}

.article-body img {
    max-width: 100%;
    height: auto;
    margin: 2rem 0;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.article-body pre {
    background: #f8fafc;
    border-radius: 15px;
    padding: 1.5rem;
    margin: 2rem 0;
    overflow-x: auto;
    border-left: 4px solid var(--day-primary);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.article-body code {
    background: #f8fafc;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    color: var(--day-primary);
    font-weight: 600;
}

.article-body blockquote {
    border-left: 4px solid var(--day-primary);
    padding: 1.5rem 2rem;
    margin: 2rem 0;
    background: rgba(59, 130, 246, 0.05);
    border-radius: 0 15px 15px 0;
    font-style: italic;
    color: var(--day-text-light);
}

/* ========================================
   SIDEBAR MODERNE
======================================== */
.article-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.sidebar-card {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    animation: slideInUp 0.6s ease-out;
    transition: transform 0.3s ease;
}

.sidebar-card:hover {
    transform: translateY(-2px);
}

.sidebar-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--day-text);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.related-articles {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.related-article {
    padding: 1rem;
    border-radius: 12px;
    background: rgba(59, 130, 246, 0.05);
    border: 1px solid rgba(59, 130, 246, 0.1);
    text-decoration: none;
    transition: all 0.3s ease;
}

.related-article:hover {
    background: rgba(59, 130, 246, 0.1);
    transform: translateX(5px);
}

.related-article-title {
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.related-article-meta {
    color: var(--day-text-light);
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* ========================================
   ÉVALUATION MODERNE
======================================== */
.rating-section {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 2rem;
    margin-top: 2rem;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    animation: slideInUp 0.6s ease-out;
}

.rating-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--day-text);
    margin-bottom: 1.5rem;
    text-align: center;
}

.rating-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}

.rating-stats {
    text-align: center;
    padding: 1.5rem;
    background: rgba(59, 130, 246, 0.05);
    border-radius: 15px;
    margin-top: 1.5rem;
}

.rating-progress {
    background: rgba(148, 163, 184, 0.2);
    height: 12px;
    border-radius: 6px;
    overflow: hidden;
    margin: 1rem 0;
}

.rating-progress-bar {
    height: 100%;
    border-radius: 6px;
    transition: width 0.3s ease;
}

.rating-progress-success {
    background: linear-gradient(135deg, #10b981, #059669);
}

.rating-progress-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.rating-progress-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
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
    
    .article-title {
        font-size: 2rem;
    }
    
    .article-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .rating-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .article-content {
        padding: 1.5rem;
    }
    
    .article-sidebar {
        margin-top: 1rem;
        order: -1;
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
body.night-mode .modern-breadcrumb,
body.night-mode .article-content,
body.night-mode .sidebar-card,
body.night-mode .rating-section {
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

body.night-mode .article-body pre {
    background: rgba(15, 23, 42, 0.8);
    border-left-color: var(--night-primary);
}

body.night-mode .article-body code {
    background: rgba(15, 23, 42, 0.8);
    color: var(--night-primary);
}

body.night-mode .article-body blockquote {
    background: rgba(0, 212, 255, 0.1);
    border-left-color: var(--night-primary);
}
</style>

<div class="modern-dashboard bg-animated" id="dashboard">
    
    <!-- En-tête moderne -->
    <div class="modern-header fade-in">
        <h1 class="modern-title">
            <i class="fas fa-book-open"></i>
            <?= htmlspecialchars($article['title']) ?>
        </h1>
        <div class="modern-actions">
            <?php
            // Construire l'URL de retour avec les paramètres de navigation
            $return_url = 'index.php?page=article_kb_moderne';
            if (isset($_GET['categorie']) && !empty($_GET['categorie'])) {
                $return_url .= '&categorie=' . urlencode($_GET['categorie']);
            }
            if (isset($_GET['recherche']) && !empty($_GET['recherche'])) {
                $return_url .= '&recherche=' . urlencode($_GET['recherche']);
            }
            ?>
            <a href="<?= $return_url ?>" class="modern-btn modern-btn--info">
                <i class="fas fa-arrow-left"></i>
                Retour à la liste
            </a>
            <a href="index.php?page=modifier_article_kb&id=<?= $article_id ?>" class="modern-btn modern-btn--warning">
                <i class="fas fa-edit"></i>
                Modifier
            </a>
            <button onclick="window.print()" class="modern-btn modern-btn--secondary">
                <i class="fas fa-print"></i>
                Imprimer
            </button>
        </div>
    </div>

    <!-- Breadcrumb moderne -->
    <div class="modern-breadcrumb fade-in">
        <ol class="modern-breadcrumb-list">
            <li class="modern-breadcrumb-item">
                <a href="index.php?page=accueil" class="modern-breadcrumb-link">
                    <i class="fas fa-home"></i>
                    Accueil
                </a>
            </li>
            <li class="modern-breadcrumb-item">
                <a href="<?= $return_url ?>" class="modern-breadcrumb-link">
                    Base de Connaissances
                </a>
            </li>
            <li class="modern-breadcrumb-item">
                <a href="index.php?page=article_kb_moderne&categorie=<?= $article['category_id'] ?>" class="modern-breadcrumb-link">
                    <?= htmlspecialchars($article['category_name']) ?>
                </a>
            </li>
            <li class="modern-breadcrumb-item">
                <span class="modern-breadcrumb-current"><?= htmlspecialchars($article['title']) ?></span>
            </li>
        </ol>
    </div>

    <!-- Affichage du message -->
    <?= display_message() ?>

    <!-- Contenu principal -->
    <div class="article-main fade-in">
        <div class="article-content">
            <div class="article-header">
                <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
                
                <div class="article-meta">
                    <div class="article-meta-item">
                        <i class="<?= htmlspecialchars($article['category_icon'] ?? 'fas fa-folder') ?>"></i>
                        <?= htmlspecialchars($article['category_name']) ?>
                    </div>
                    <div class="article-meta-item">
                        <i class="fas fa-eye"></i>
                        <?= $article['views'] ?> vues
                    </div>
                    <div class="article-meta-item">
                        <i class="fas fa-clock"></i>
                        <?= date('d/m/Y', strtotime($article['updated_at'])) ?>
                    </div>
                </div>
                
                <?php if (!empty($tags)): ?>
                <div class="article-tags">
                    <?php foreach ($tags as $tag): ?>
                    <a href="index.php?page=article_kb_moderne&recherche=<?= urlencode($tag['name']) ?>" class="article-tag">
                        <i class="fas fa-tag"></i>
                        <?= htmlspecialchars($tag['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="article-body">
                <?= nl2br(html_entity_decode($article['content'])) ?>
            </div>
        </div>

        <div class="article-sidebar">
            <!-- Actions rapides -->
            <div class="sidebar-card">
                <h3 class="sidebar-title">
                    <i class="fas fa-tools"></i>
                    Actions rapides
                </h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <a href="<?= $return_url ?>" class="modern-btn modern-btn--info" style="width: 100%; justify-content: center;">
                        <i class="fas fa-list"></i>
                        Retour à la liste
                    </a>
                    <a href="index.php?page=article_kb_moderne&categorie=<?= $article['category_id'] ?>" class="modern-btn modern-btn--secondary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-folder"></i>
                        Cette catégorie
                    </a>
                </div>
            </div>

            <!-- Articles similaires -->
            <?php if (!empty($related_articles)): ?>
            <div class="sidebar-card">
                <h3 class="sidebar-title">
                    <i class="fas fa-link"></i>
                    Articles similaires
                </h3>
                <div class="related-articles">
                    <?php foreach ($related_articles as $related): ?>
                    <a href="index.php?page=visu_article_moderne&id=<?= $related['id'] ?>" class="related-article">
                        <div class="related-article-title"><?= htmlspecialchars($related['title']) ?></div>
                        <div class="related-article-meta">
                            <i class="fas fa-eye"></i>
                            <?= $related['views'] ?> vues
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Statistiques de l'article -->
            <?php if ($rating_stats['total_ratings'] > 0): ?>
            <div class="sidebar-card">
                <h3 class="sidebar-title">
                    <i class="fas fa-chart-bar"></i>
                    Statistiques
                </h3>
                <div class="rating-stats">
                    <div style="font-size: 2rem; font-weight: 800; color: var(--day-primary); margin-bottom: 0.5rem;">
                        <?= $rating_stats['helpful_percent'] ?>%
                    </div>
                    <div style="color: var(--day-text-light); margin-bottom: 1rem;">
                        Trouvent cet article utile
                    </div>
                    <div class="rating-progress">
                        <div class="rating-progress-bar rating-progress-<?= $rating_stats['helpful_percent'] >= 70 ? 'success' : ($rating_stats['helpful_percent'] >= 40 ? 'warning' : 'danger') ?>" 
                             style="width: <?= $rating_stats['helpful_percent'] ?>%;"></div>
                    </div>
                    <div style="font-size: 0.875rem; color: var(--day-text-light);">
                        <?= $rating_stats['helpful_count'] ?> / <?= $rating_stats['total_ratings'] ?> évaluations positives
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section d'évaluation -->
    <div class="rating-section fade-in">
        <h2 class="rating-title">Cet article vous a-t-il été utile ?</h2>
        
        <?php if (!$user_has_rated && isset($_SESSION['user_id'])): ?>
        <form action="index.php?page=visu_article_moderne&id=<?= $article_id ?>" method="POST">
            <input type="hidden" name="action" value="rate_article">
            <div class="rating-buttons">
                <button type="submit" name="is_helpful" value="1" class="modern-btn modern-btn--success">
                    <i class="fas fa-thumbs-up"></i>
                    Oui, cet article m'a aidé
                </button>
                <button type="submit" class="modern-btn modern-btn--danger">
                    <i class="fas fa-thumbs-down"></i>
                    Non, je n'ai pas trouvé ce que je cherchais
                </button>
            </div>
        </form>
        <?php elseif ($user_has_rated): ?>
        <div style="text-align: center; padding: 2rem; background: rgba(16, 185, 129, 0.1); border-radius: 15px; border: 1px solid rgba(16, 185, 129, 0.3);">
            <div style="font-size: 3rem; color: #10b981; margin-bottom: 1rem;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div style="font-size: 1.25rem; font-weight: 700; color: var(--day-text); margin-bottom: 0.5rem;">
                Merci pour votre évaluation !
            </div>
            <div style="color: var(--day-text-light);">
                Votre retour nous aide à améliorer notre base de connaissances.
            </div>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 2rem; background: rgba(245, 158, 11, 0.1); border-radius: 15px; border: 1px solid rgba(245, 158, 11, 0.3);">
            <div style="font-size: 3rem; color: #f59e0b; margin-bottom: 1rem;">
                <i class="fas fa-info-circle"></i>
            </div>
            <div style="font-size: 1.25rem; font-weight: 700; color: var(--day-text); margin-bottom: 0.5rem;">
                Connectez-vous pour évaluer cet article
            </div>
            <div style="color: var(--day-text-light);">
                Votre avis nous est précieux pour améliorer notre base de connaissances.
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($rating_stats['total_ratings'] > 0): ?>
        <div class="rating-stats">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--day-text); margin-bottom: 1rem;">
                Note globale de l'article
            </div>
            <div class="rating-progress">
                <div class="rating-progress-bar rating-progress-<?= $rating_stats['helpful_percent'] >= 70 ? 'success' : ($rating_stats['helpful_percent'] >= 40 ? 'warning' : 'danger') ?>" 
                     style="width: <?= $rating_stats['helpful_percent'] ?>%;"></div>
            </div>
            <div style="font-size: 1.1rem; font-weight: 600; color: var(--day-text); margin-top: 1rem;">
                <?= $rating_stats['helpful_percent'] ?>% des utilisateurs trouvent cet article utile
            </div>
            <div style="font-size: 0.95rem; color: var(--day-text-light);">
                Basé sur <?= $rating_stats['total_ratings'] ?> évaluation<?= $rating_stats['total_ratings'] > 1 ? 's' : '' ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Détection IMMÉDIATE du mode nuit (avant DOMContentLoaded)
(function() {
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('theme');
    
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.documentElement.classList.add('night-mode');
        document.body.classList.add('night-mode');
        console.log('🌙 Mode nuit détecté et appliqué immédiatement');
    } else {
        document.documentElement.classList.remove('night-mode');
        document.body.classList.remove('night-mode');
        console.log('☀️ Mode jour détecté et appliqué immédiatement');
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
            console.log('Mode nuit activé');
        } else {
            document.body.classList.remove('night-mode');
            console.log('Mode jour activé');
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
                    console.log('Passage automatique en mode nuit');
                } else {
                    document.body.classList.remove('night-mode');
                    console.log('Passage automatique en mode jour');
                }
            }
        });
    }

    // Fonction pour basculer manuellement le mode (si vous voulez ajouter un bouton plus tard)
    function toggleDarkMode() {
        document.body.classList.toggle('night-mode');
        const isDark = document.body.classList.contains('night-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        console.log('Mode basculé vers:', isDark ? 'nuit' : 'jour');
    }

    // Animation d'entrée
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

    // Observer les éléments pour l'animation
    document.querySelectorAll('.fade-in').forEach(function(element) {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(element);
    });

    // Initialisation
    detectAndApplyDarkMode();
    
    console.log('Article moderne initialisé avec détection automatique du mode nuit');
});
</script>

<?php require_once 'includes/footer.php'; ?>
