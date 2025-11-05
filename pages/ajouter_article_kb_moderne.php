<?php
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'ajouter_article_kb_moderne.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=ajouter_article_kb_moderne');
    exit();
}

// Page d'ajout d'un article à la base de connaissances - Version Moderne
$page_title = "Créer un Article";

// Vérifier que l'utilisateur est connecté et a les droits suffisants
if (!isset($_SESSION['user_id']) || 
    (!isset($_SESSION['role']) && !isset($_SESSION['user_role'])) || 
    (
        (isset($_SESSION['role']) && !in_array($_SESSION['role'], ['admin', 'manager'])) &&
        (isset($_SESSION['user_role']) && !in_array($_SESSION['user_role'], ['admin', 'manager']))
    )) {
    set_message("Vous n'avez pas les droits nécessaires pour accéder à cette page.", "danger");
    redirect('base_connaissances');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/subdomain_config.php';
require_once __DIR__ . '/../config/database.php';

$shop_pdo = getShopDBConnection();

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

// Récupération des tags
function get_kb_tags() {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "SELECT * FROM kb_tags ORDER BY name ASC";
        $stmt = $shop_pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des tags KB: " . $e->getMessage());
        return [];
    }
}

// Fonction pour créer un nouveau tag
function create_kb_tag($name) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "INSERT INTO kb_tags (name, created_at) VALUES (?, NOW())";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$name]);
        return $shop_pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erreur lors de la création du tag: " . $e->getMessage());
        return false;
    }
}

// Fonction pour vérifier si un tag existe et le créer s'il n'existe pas
function get_or_create_tag($tag_name) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "SELECT id FROM kb_tags WHERE name = ?";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([trim($tag_name)]);
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tag) {
            return $tag['id'];
        } else {
            return create_kb_tag(trim($tag_name));
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération/création du tag: " . $e->getMessage());
        return false;
    }
}

// Fonction pour créer une nouvelle catégorie
function create_kb_category($name) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "INSERT INTO kb_categories (name, created_at) VALUES (?, NOW())";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([trim($name)]);
        return $shop_pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erreur lors de la création de la catégorie: " . $e->getMessage());
        return false;
    }
}

// Récupérer les catégories et les tags
$categories = get_kb_categories();
$tags = get_kb_tags();

// Traitement du formulaire d'ajout d'article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_article') {
    $title = cleanInput($_POST['title']);
    $type = cleanInput($_POST['type'] ?? 'texte');
    $content = $_POST['content'];
    $url = cleanInput($_POST['url'] ?? '');
    $html_code = $_POST['html_code'] ?? '';
    $category_id = intval($_POST['category_id']);
    $tag_ids = isset($_POST['tag_ids']) ? $_POST['tag_ids'] : [];
    $new_tags = isset($_POST['new_tags']) ? $_POST['new_tags'] : '';
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Le titre de l'article est requis.";
    }
    
    if ($type === 'url' && empty($url)) {
        $errors[] = "L'URL est requise pour ce type d'article.";
    } elseif ($type === 'html' && empty($html_code)) {
        $errors[] = "Le code HTML/CSS est requis pour ce type d'article.";
    } elseif ($type === 'texte' && empty($content)) {
        $errors[] = "Le contenu de l'article est requis.";
    }
    
    if ($category_id <= 0) {
        $errors[] = "Veuillez sélectionner une catégorie.";
    }
    
    if (empty($errors)) {
        try {
            $shop_pdo = getShopDBConnection();
            $shop_pdo->beginTransaction();
            
            // Préparer le contenu selon le type
            $final_content = $content;
            if ($type === 'url') {
                $final_content = '<p><strong>Lien:</strong> <a href="' . htmlspecialchars($url) . '" target="_blank">' . htmlspecialchars($url) . '</a></p>' . $content;
            } elseif ($type === 'youtube') {
                // Extraire l'ID YouTube si c'est une URL complète
                $youtube_id = '';
                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
                    $youtube_id = $matches[1];
                } else {
                    $youtube_id = $url; // Assumons que c'est déjà un ID
                }
                $final_content = '<div class="youtube-video"><iframe width="560" height="315" src="https://www.youtube.com/embed/' . htmlspecialchars($youtube_id) . '" frameborder="0" allowfullscreen></iframe></div>' . $content;
            } elseif ($type === 'html') {
                // Pour le HTML/CSS, on l'utilise tel quel (sans échapper) car c'est du code HTML valide
                // On ajoute juste une wrapper div pour isolation
                $final_content = '<div class="html-content-wrapper">' . $html_code . '</div>' . (!empty($content) ? '<div class="html-description">' . $content . '</div>' : '');
            }
            
            $query = "INSERT INTO kb_articles (title, content, category_id, created_at, updated_at, views) 
                      VALUES (?, ?, ?, NOW(), NOW(), 0)";
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$title, $final_content, $category_id]);
            $article_id = $shop_pdo->lastInsertId();
            
            if (!empty($tag_ids)) {
                $values = [];
                $placeholders = [];
                
                foreach ($tag_ids as $tag_id) {
                    $placeholders[] = "(?, ?)";
                    $values[] = $article_id;
                    $values[] = intval($tag_id);
                }
                
                $query = "INSERT INTO kb_article_tags (article_id, tag_id) VALUES " . implode(', ', $placeholders);
                $stmt = $shop_pdo->prepare($query);
                $stmt->execute($values);
            }
            
            if (!empty($new_tags)) {
                $tag_names = explode(',', $new_tags);
                
                foreach ($tag_names as $tag_name) {
                    $tag_name = trim($tag_name);
                    if (!empty($tag_name)) {
                        $tag_id = get_or_create_tag($tag_name);
                        
                        if ($tag_id) {
                            $query = "INSERT INTO kb_article_tags (article_id, tag_id) VALUES (?, ?)";
                            $stmt = $shop_pdo->prepare($query);
                            $stmt->execute([$article_id, $tag_id]);
                        }
                    }
                }
            }
            
            $shop_pdo->commit();
            
            set_message("L'article a été ajouté avec succès à la base de connaissances.", "success");
            redirect('article_kb', ['id' => $article_id]);
            
        } catch (PDOException $e) {
            $shop_pdo->rollBack();
            error_log("Erreur lors de l'ajout de l'article: " . $e->getMessage());
            set_message("Une erreur est survenue lors de l'ajout de l'article. Veuillez réessayer.", "danger");
        }
    }
}
?>

<style>
/* FIX NAVBAR DESKTOP - ABSOLUMENT NÉCESSAIRE */
@media (min-width: 992px) {
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
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
    body #desktop-navbar, html body #desktop-navbar {
        height: 60px !important;
        min-height: 60px !important;
        max-height: 60px !important;
    }
    #desktop-navbar * {
        visibility: visible !important;
        opacity: 1 !important;
    }
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.3rem 1rem !important;
    }
    body .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        z-index: 10001 !important;
    }
    body {
        padding-top: 80px !important;
    }
}

:root {
    --primary: #4f46e5;
    --primary-dark: #3730a3;
    --secondary: #06b6d4;
    --success: #059669;
    --warning: #d97706;
    --danger: #dc2626;
    --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --card-bg: rgba(255, 255, 255, 0.95);
    --card-border: rgba(255, 255, 255, 0.2);
    --text-primary: #1f2937;
    --text-secondary: #6b7280;
    --input-bg: #ffffff;
    --input-border: #e5e7eb;
    --input-focus: rgba(79, 70, 229, 0.1);
    --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
}

@media (prefers-color-scheme: dark) {
    :root {
        --bg-gradient: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
        --card-bg: rgba(15, 15, 35, 0.95);
        --card-border: rgba(79, 70, 229, 0.3);
        --text-primary: #e2e8f0;
        --text-secondary: #94a3b8;
        --input-bg: #1e293b;
        --input-border: #475569;
        --input-focus: rgba(79, 70, 229, 0.3);
        --shadow: 0 25px 50px -12px rgba(79, 70, 229, 0.2);
    }
    
    /* Forcer le mode sombre pour tous les éléments */
    .editor-panel {
        background: #0f1419 !important;
        background-image: 
            radial-gradient(circle at 25% 25%, rgba(79, 70, 229, 0.15) 0%, transparent 50%),
            radial-gradient(circle at 75% 75%, rgba(6, 182, 212, 0.15) 0%, transparent 50%) !important;
    }
    
    .form-input, .form-select, .form-textarea, 
    .upload-zone, .tags-container {
        background: #1e293b !important;
        color: #e2e8f0 !important;
        border-color: #475569 !important;
    }
    
    .form-input::placeholder, .form-textarea::placeholder {
        color: #64748b !important;
    }
    
    /* Style spécifique pour le textarea de code HTML */
    #html-code {
        background: #0f1419 !important;
        color: #e2e8f0 !important;
        border-color: #475569 !important;
        font-family: 'Courier New', monospace !important;
    }
    
    #html-code::placeholder {
        color: #64748b !important;
    }
    
    .type-option label {
        background: #1e293b !important;
        color: #e2e8f0 !important;
        border-color: #475569 !important;
    }
    
    .type-option input[type="radio"]:checked + label {
        background: linear-gradient(135deg, rgba(79, 70, 229, 0.3), rgba(6, 182, 212, 0.3)) !important;
        border-color: var(--primary) !important;
    }
    
    .type-option label span {
        color: #94a3b8 !important;
    }
    
    /* CKEditor mode sombre - Version complète - ULTRA FORCE */
    .ck-editor,
    #editor-container .ck-editor,
    .ck-editor__main,
    .ck-editor__editable {
        background: #0f1419 !important;
        background-color: #0f1419 !important;
        border: 3px solid #475569 !important;
        border-radius: 25px !important;
        overflow: hidden !important;
    }
    
    .ck.ck-toolbar,
    #editor-container .ck.ck-toolbar {
        background: #1e293b !important;
        background-color: #1e293b !important;
        border: none !important;
        border-bottom: 1px solid #475569 !important;
        color: #e2e8f0 !important;
    }
    
    .ck.ck-content,
    #editor-container .ck.ck-content,
    .ck-editor__editable_inline {
        background: #0f1419 !important;
        background-color: #0f1419 !important;
        color: #e2e8f0 !important;
        border: none !important;
    }
    
    /* Tous les boutons CKEditor */
    .ck.ck-button, .ck.ck-dropdown__button {
        color: #e2e8f0 !important;
        border-color: transparent !important;
        background: transparent !important;
    }
    
    .ck.ck-button:not(.ck-disabled):hover, 
    .ck.ck-dropdown__button:not(.ck-disabled):hover {
        background: rgba(79, 70, 229, 0.3) !important;
        color: #ffffff !important;
        border-color: rgba(79, 70, 229, 0.5) !important;
    }
    
    .ck.ck-button.ck-on {
        background: var(--primary) !important;
        color: #ffffff !important;
        border-color: var(--primary) !important;
    }
    
    /* Icônes et textes */
    .ck.ck-icon {
        color: #e2e8f0 !important;
    }
    
    .ck.ck-button .ck-button__label {
        color: #e2e8f0 !important;
    }
    
    .ck.ck-splitbutton__arrow {
        color: #e2e8f0 !important;
    }
    
    /* Dropdowns et menus */
    .ck.ck-dropdown__panel {
        background: #1e293b !important;
        border: 1px solid #475569 !important;
    }
    
    .ck.ck-list__item {
        background: transparent !important;
        color: #e2e8f0 !important;
    }
    
    .ck.ck-list__item:hover {
        background: rgba(79, 70, 229, 0.3) !important;
    }
    
    .ck.ck-list__item.ck-on {
        background: var(--primary) !important;
        color: #ffffff !important;
    }
    
    /* Séparateurs */
    .ck.ck-toolbar__separator {
        background: #475569 !important;
    }
    
    /* Input et textarea dans les dialogs */
    .ck.ck-input {
        background: #1e293b !important;
        color: #e2e8f0 !important;
        border-color: #475569 !important;
    }
    
    .ck.ck-input:focus {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.3) !important;
    }
    
    /* Labels */
    .ck.ck-label {
        color: #e2e8f0 !important;
    }
    
    /* Dialogs et modals */
    .ck.ck-dialog {
        background: #1e293b !important;
        border: 1px solid #475569 !important;
    }
    
    .ck.ck-dialog .ck-dialog__header {
        background: #0f1419 !important;
        border-bottom: 1px solid #475569 !important;
    }
    
    .ck.ck-dialog .ck-dialog__content {
        background: #1e293b !important;
    }
    
    /* Zone upload mode sombre */
    .upload-zone {
        background: #1e293b !important;
        border-color: #475569 !important;
    }
    
    .upload-zone:hover, .upload-zone.dragover {
        background: rgba(79, 70, 229, 0.2) !important;
        border-color: var(--primary) !important;
    }
    
    .upload-zone p, .upload-zone small {
        color: #94a3b8 !important;
    }
    
    /* Tags container mode sombre */
    .tags-container {
        background: #1e293b !important;
        border-color: #475569 !important;
    }
    
    .tag-row {
        border-bottom-color: #475569 !important;
    }
    
    .tag-row:hover {
        background: rgba(79, 70, 229, 0.2) !important;
    }
    
    .tag-name {
        color: #e2e8f0 !important;
    }
    
    /* Dropdown select mode sombre */
    select option {
        background: #1e293b !important;
        color: #e2e8f0 !important;
    }
    
    /* Container éditeur en mode sombre - FORCE LE FOND NOIR */
    #editor-container {
        border-color: #475569 !important;
        background: #0f1419 !important;
    }
    
    #editor-container.focused {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 6px rgba(79, 70, 229, 0.3) !important;
    }
    
    /* FORCER le fond noir pour tous les éléments de l'éditeur */
    #editor-container .ck-editor {
        background: #0f1419 !important;
        border-color: #475569 !important;
    }
    
    #editor-container .ck-editor__main {
        background: #0f1419 !important;
    }
    
    #editor-container .ck-editor__top {
        background: #1e293b !important;
    }
    
    /* Styles supplémentaires pour CKEditor dark mode */
    .ck.ck-balloon-panel {
        background: #1e293b !important;
        border: 1px solid #475569 !important;
    }
    
    .ck.ck-tooltip {
        background: #1e293b !important;
        color: #e2e8f0 !important;
        border: 1px solid #475569 !important;
    }
    
    .ck.ck-tooltip .ck-tooltip__text {
        color: #e2e8f0 !important;
    }
    
    /* Placeholder text dans l'éditeur */
    .ck.ck-content .ck-placeholder::before {
        color: #64748b !important;
    }
    
    /* Selection et focus dans l'éditeur */
    .ck.ck-content blockquote {
        border-left-color: var(--primary) !important;
        background: rgba(79, 70, 229, 0.1) !important;
    }
    
    .ck.ck-content code {
        background: #1e293b !important;
        color: #e2e8f0 !important;
        border: 1px solid #475569 !important;
    }
    
    .ck.ck-content pre {
        background: #1e293b !important;
        color: #e2e8f0 !important;
        border: 1px solid #475569 !important;
    }
    
    /* Files list mode sombre */
    #file-list > div {
        background: #1e293b !important;
        border-color: #475569 !important;
        color: #e2e8f0 !important;
    }
    
    #file-list a {
        color: var(--primary) !important;
    }
    
    /* Améliorer le contraste pour les icônes */
    .form-label i, .upload-zone i {
        color: var(--primary) !important;
    }
    
    /* Améliorer le style des options sélectionnées */
    .form-select {
        color-scheme: dark;
    }
    
    /* Style pour les messages d'aide */
    small {
        color: #94a3b8 !important;
    }
    
    /* Améliorer les boutons en mode sombre */
    .btn-secondary {
        background: rgba(30, 41, 59, 0.8) !important;
        color: #e2e8f0 !important;
        border-color: #475569 !important;
    }
    
    .btn-secondary:hover {
        background: rgba(30, 41, 59, 1) !important;
        border-color: var(--primary) !important;
    }
    
    /* Modal en mode sombre */
    #new-category-modal > div {
        background: rgba(15, 15, 35, 0.95) !important;
        border: 1px solid rgba(79, 70, 229, 0.3) !important;
    }
    
    #new-category-modal h3 {
        color: #e2e8f0 !important;
    }
    
    #new-category-name {
        background: #1e293b !important;
        color: #e2e8f0 !important;
        border-color: #475569 !important;
    }
    
    #new-category-name::placeholder {
        color: #64748b !important;
    }
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    background: var(--bg-gradient);
    min-height: 100vh;
    color: var(--text-primary);
    line-height: 1.6;
    position: relative;
}

body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 50%, rgba(79, 70, 229, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(236, 72, 153, 0.1) 0%, transparent 50%);
    animation: particleFloat 20s ease-in-out infinite;
    z-index: -1;
}

@keyframes particleFloat {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    33% { transform: translate(30px, -30px) rotate(120deg); }
    66% { transform: translate(-20px, 20px) rotate(240deg); }
}

.page-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 2rem;
    position: relative;
    z-index: 1;
}

.page-header {
    background: var(--card-bg);
    backdrop-filter: blur(20px);
    border-radius: 25px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    border: 1px solid var(--card-border);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--secondary), #ec4899);
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0%, 100% { transform: translateX(-100%); }
    50% { transform: translateX(100%); }
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 2rem;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.title-icon {
    width: 5rem;
    height: 5rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    box-shadow: 0 15px 30px -5px rgba(79, 70, 229, 0.4);
    animation: iconPulse 4s ease-in-out infinite;
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1); box-shadow: 0 15px 30px -5px rgba(79, 70, 229, 0.4); }
    50% { transform: scale(1.05); box-shadow: 0 20px 40px -5px rgba(79, 70, 229, 0.6); }
}

.title-text h1 {
    font-size: 3rem;
    font-weight: 900;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
    text-shadow: 0 0 30px rgba(79, 70, 229, 0.3);
}

.title-text p {
    color: var(--text-secondary);
    font-size: 1.2rem;
    font-weight: 500;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 2.5rem;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    color: var(--text-primary);
    text-decoration: none;
    font-weight: 700;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.back-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-3px);
    box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.2);
    border-color: var(--primary);
}

.main-card {
    background: var(--card-bg);
    backdrop-filter: blur(20px);
    border-radius: 30px;
    overflow: hidden;
    box-shadow: var(--shadow);
    border: 1px solid var(--card-border);
    position: relative;
}

.main-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--primary), var(--secondary), transparent);
    animation: borderMove 4s linear infinite;
}

@keyframes borderMove {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.form-layout {
    display: grid;
    grid-template-columns: 1fr 450px;
    min-height: 90vh;
}

@media (max-width: 1200px) {
    .form-layout {
        grid-template-columns: 1fr;
    }
}

.editor-panel {
    padding: 3rem;
    background: var(--input-bg);
    background-image: 
        radial-gradient(circle at 25% 25%, rgba(79, 70, 229, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 75% 75%, rgba(6, 182, 212, 0.05) 0%, transparent 50%);
}

.sidebar-panel {
    background: linear-gradient(180deg, 
        rgba(79, 70, 229, 0.1) 0%, 
        rgba(6, 182, 212, 0.1) 50%, 
        rgba(236, 72, 153, 0.1) 100%);
    padding: 3rem;
    border-left: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
}

@media (max-width: 1200px) {
    .sidebar-panel {
        border-left: none;
        border-top: 1px solid var(--card-border);
    }
}

.form-section {
    margin-bottom: 3rem;
    position: relative;
}

.form-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: -1rem;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, var(--primary), var(--secondary));
    border-radius: 2px;
    opacity: 0.6;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-weight: 800;
    font-size: 1rem;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.form-label i {
    font-size: 1.25rem;
    color: var(--primary);
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 1.5rem 2rem;
    border: 2px solid var(--input-border);
    border-radius: 20px;
    font-size: 1.1rem;
    font-family: inherit;
    background: var(--input-bg);
    color: var(--text-primary);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 6px var(--input-focus);
    transform: translateY(-2px);
}

.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234f46e5' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 2rem center;
    background-size: 1.5rem;
    padding-right: 5rem;
}

.type-selector {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.type-option {
    position: relative;
}

.type-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.type-option label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem;
    background: var(--input-bg);
    border: 2px solid var(--input-border);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    text-align: center;
}

.type-option input[type="radio"]:checked + label {
    border-color: var(--primary);
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(6, 182, 212, 0.1));
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);
}

.type-option label i {
    font-size: 2rem;
    color: var(--primary);
}

.type-option label span {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.url-section {
    display: none;
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.4s ease;
}

.url-section.visible {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

/* Textarea pour le code HTML/CSS */
.form-textarea {
    resize: vertical;
    min-height: 200px;
    line-height: 1.5;
}

#editor-container {
    border: 3px solid var(--input-border);
    border-radius: 25px;
    overflow: hidden;
    transition: all 0.4s ease;
    background: var(--input-bg);
}

#editor-container.focused {
    border-color: var(--primary);
    box-shadow: 0 0 0 6px var(--input-focus);
    transform: translateY(-2px);
}

.tags-container {
    background: var(--input-bg);
    border: 2px solid var(--input-border);
    border-radius: 20px;
    padding: 2rem;
    max-height: 300px;
    overflow-y: auto;
}

.tag-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--input-border);
    transition: all 0.3s ease;
}

.tag-row:hover {
    background: rgba(79, 70, 229, 0.05);
    border-radius: 10px;
    padding-left: 1rem;
    padding-right: 1rem;
}

.tag-row:last-child {
    border-bottom: none;
}

.tag-checkbox {
    width: 1.5rem;
    height: 1.5rem;
    accent-color: var(--primary);
    transform: scale(1.2);
}

.tag-name {
    font-size: 1rem;
    color: var(--text-primary);
    font-weight: 500;
    cursor: pointer;
}

.upload-zone {
    border: 3px dashed var(--input-border);
    border-radius: 20px;
    padding: 3rem;
    text-align: center;
    background: var(--input-bg);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.upload-zone:hover, .upload-zone.dragover {
    border-color: var(--primary);
    background: var(--input-focus);
    transform: scale(1.02);
}

.upload-zone i {
    font-size: 3rem;
    color: var(--primary);
    margin-bottom: 1rem;
}

.upload-zone p {
    font-size: 1.1rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.upload-zone small {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

#file-input {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.action-section {
    margin-top: 3rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding: 1.5rem 3rem;
    border: none;
    border-radius: 20px;
    font-size: 1.1rem;
    font-weight: 800;
    text-decoration: none;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    min-height: 4rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    box-shadow: 0 15px 30px -5px rgba(79, 70, 229, 0.4);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 25px 40px -5px rgba(79, 70, 229, 0.6);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
    border: 2px solid var(--input-border);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: var(--primary);
    transform: translateY(-2px);
}

.alert {
    padding: 2rem 2.5rem;
    border-radius: 20px;
    margin-bottom: 2rem;
    font-weight: 600;
    border: 2px solid;
    position: relative;
    overflow: hidden;
}

.alert::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: currentColor;
}

.alert-danger {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
    border-color: rgba(220, 38, 38, 0.3);
}

.notification {
    position: fixed;
    top: 2rem;
    right: 2rem;
    background: linear-gradient(135deg, var(--success) 0%, #047857 100%);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 20px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
    z-index: 10001;
    transform: translateX(100%);
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.notification.show {
    transform: translateX(0);
}

.ck-editor__editable {
    min-height: 500px !important;
    font-size: 16px !important;
    line-height: 1.8 !important;
    font-family: 'Inter', sans-serif !important;
    padding: 2.5rem !important;
}

/* CKEditor styles généraux - compatibles jour/nuit */
.ck-editor {
    border: 3px solid var(--input-border) !important;
    border-radius: 25px !important;
    overflow: hidden !important;
    background: var(--input-bg) !important;
}

.ck.ck-toolbar {
    border-radius: 20px 20px 0 0 !important;
    background: var(--input-bg) !important;
    border: none !important;
    border-bottom: 1px solid var(--input-border) !important;
    padding: 1.5rem !important;
    color: var(--text-primary) !important;
}

.ck.ck-content {
    border-radius: 0 0 20px 20px !important;
    border: none !important;
    background: var(--input-bg) !important;
    color: var(--text-primary) !important;
}

.ck.ck-button {
    border-radius: 15px !important;
    margin: 3px !important;
    transition: all 0.3s ease !important;
    color: var(--text-primary) !important;
    border-color: transparent !important;
    background: transparent !important;
}

.ck.ck-button:hover {
    background: var(--input-focus) !important;
    transform: translateY(-1px) !important;
    color: var(--primary) !important;
}

.ck.ck-button.ck-on {
    background: var(--primary) !important;
    color: white !important;
}

/* Icônes et éléments généraux */
.ck.ck-icon {
    color: var(--text-primary) !important;
}

.ck.ck-dropdown__button {
    color: var(--text-primary) !important;
}

@media (max-width: 768px) {
    .page-container {
        padding: 1rem;
    }
    
    .page-header {
        padding: 2rem;
    }
    
    .header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .title-text h1 {
        font-size: 2.5rem;
    }
    
    .editor-panel,
    .sidebar-panel {
        padding: 2rem;
    }
    
    .type-selector {
        grid-template-columns: 1fr;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.page-header {
    animation: fadeInUp 0.6s ease-out;
}

.main-card {
    animation: fadeInUp 0.8s ease-out 0.2s both;
}

.form-section {
    animation: fadeInUp 0.6s ease-out var(--delay, 0s) both;
}

.form-section:nth-child(1) { --delay: 0.1s; }
.form-section:nth-child(2) { --delay: 0.2s; }
.form-section:nth-child(3) { --delay: 0.3s; }
.form-section:nth-child(4) { --delay: 0.4s; }
.form-section:nth-child(5) { --delay: 0.5s; }
</style>

<div class="page-container">
    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="page-title">
                <div class="title-icon">
                    <i class="fas fa-magic"></i>
                </div>
                <div class="title-text">
                    <h1>Créer un Article</h1>
                    <p>Rédigez et publiez un nouvel article dans votre base de connaissances</p>
                </div>
            </div>
            <a href="index.php?page=base_connaissances" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Retour à la liste
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul style="list-style: none; margin: 0;">
            <?php foreach ($errors as $error): ?>
            <li><i class="fas fa-exclamation-triangle" style="margin-right: 0.75rem;"></i><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div class="main-card">
        <form action="index.php?page=ajouter_article_kb_moderne" method="POST" id="article-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_article">
            
            <div class="form-layout">
                <!-- Editor Panel -->
                <div class="editor-panel">
                    <!-- Title -->
                    <div class="form-section">
                        <label for="title" class="form-label">
                            <i class="fas fa-heading"></i>
                            Titre de l'article
                        </label>
                        <input type="text" 
                               class="form-input" 
                               id="title" 
                               name="title" 
                               placeholder="Saisissez le titre de votre article..."
                               required 
                               value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                    </div>

                    <!-- Type d'article -->
                    <div class="form-section">
                        <label class="form-label">
                            <i class="fas fa-layer-group"></i>
                            Type d'article
                        </label>
                        <div class="type-selector">
                            <div class="type-option">
                                <input type="radio" id="type-texte" name="type" value="texte" checked>
                                <label for="type-texte">
                                    <i class="fas fa-align-left"></i>
                                    <span>Article Texte</span>
                                </label>
                            </div>
                            <div class="type-option">
                                <input type="radio" id="type-url" name="type" value="url">
                                <label for="type-url">
                                    <i class="fas fa-link"></i>
                                    <span>Lien URL</span>
                                </label>
                            </div>
                            <div class="type-option">
                                <input type="radio" id="type-youtube" name="type" value="youtube">
                                <label for="type-youtube">
                                    <i class="fab fa-youtube"></i>
                                    <span>Vidéo YouTube</span>
                                </label>
                            </div>
                            <div class="type-option">
                                <input type="radio" id="type-html" name="type" value="html">
                                <label for="type-html">
                                    <i class="fas fa-code"></i>
                                    <span>Code HTML/CSS</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- URL Section (conditional) -->
                    <div class="form-section url-section" id="url-section">
                        <label for="url" class="form-label">
                            <i class="fas fa-external-link-alt"></i>
                            <span id="url-label">URL du lien</span>
                        </label>
                        <input type="url" 
                               class="form-input" 
                               id="url" 
                               name="url" 
                               placeholder="https://exemple.com"
                               value="<?= isset($_POST['url']) ? htmlspecialchars($_POST['url']) : '' ?>">
                        <small style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 1rem; display: block;">
                            <i class="fas fa-info-circle"></i> 
                            <span id="url-help">Saisissez l'URL complète du lien</span>
                        </small>
                    </div>

                    <!-- HTML/CSS Section (conditional) -->
                    <div class="form-section url-section" id="html-section">
                        <label for="html-code" class="form-label">
                            <i class="fas fa-code"></i>
                            Code HTML/CSS
                        </label>
                        <textarea class="form-input form-textarea" 
                                  id="html-code" 
                                  name="html_code" 
                                  rows="12"
                                  placeholder="Collez votre code HTML/CSS généré par ChatGPT ici..."
                                  style="font-family: 'Courier New', monospace; font-size: 14px;"><?= isset($_POST['html_code']) ? htmlspecialchars($_POST['html_code']) : '' ?></textarea>
                        <small style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 1rem; display: block;">
                            <i class="fas fa-info-circle"></i> 
                            Le code sera affiché tel quel dans l'article (HTML brut avec CSS inline)
                        </small>
                    </div>

                    <!-- Content Editor -->
                    <div class="form-section">
                        <label for="content" class="form-label">
                            <i class="fas fa-edit"></i>
                            <span id="content-label">Contenu de l'article</span>
                        </label>
                        <div id="editor-container">
                            <div id="editor"><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></div>
                        </div>
                        <textarea id="content" name="content" style="display: none;"></textarea>
                    </div>

                    <!-- Upload Zone -->
                    <div class="form-section">
                        <label class="form-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            Upload de fichiers
                        </label>
                        <div class="upload-zone" id="upload-zone">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Glissez-déposez vos fichiers ici ou cliquez pour sélectionner</p>
                            <small>Formats acceptés: images, PDF, documents (max 10MB)</small>
                            <input type="file" id="file-input" multiple accept="image/*,.pdf,.doc,.docx,.txt">
                        </div>
                        <div id="file-list" style="margin-top: 1rem;"></div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="sidebar-panel">
                    <!-- Category -->
                    <div class="form-section">
                        <label for="category_id" class="form-label">
                            <i class="fas fa-folder-open"></i>
                            Catégorie
                        </label>
                        <select class="form-input form-select" id="category_id" name="category_id" required>
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                    <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="create_new" style="color: var(--primary); font-weight: bold;">
                                ➕ Créer une catégorie
                            </option>
                        </select>
                        
                        <!-- Modal pour créer une nouvelle catégorie -->
                        <div id="new-category-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; backdrop-filter: blur(5px);">
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--card-bg); border-radius: 20px; padding: 2rem; min-width: 400px; box-shadow: var(--shadow);">
                                <h3 style="margin-bottom: 1.5rem; color: var(--text-primary); text-align: center;">
                                    <i class="fas fa-folder-plus" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                    Créer une nouvelle catégorie
                                </h3>
                                <input type="text" id="new-category-name" class="form-input" placeholder="Nom de la catégorie..." style="margin-bottom: 1.5rem;">
                                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                                    <button type="button" id="cancel-category" class="btn btn-secondary" style="padding: 0.75rem 1.5rem;">
                                        <i class="fas fa-times"></i> Annuler
                                    </button>
                                    <button type="button" id="create-category" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                                        <i class="fas fa-plus"></i> Créer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Existing Tags -->
                    <div class="form-section">
                        <label class="form-label">
                            <i class="fas fa-tags"></i>
                            Tags Disponibles
                        </label>
                        <div class="tags-container">
                            <?php if (empty($tags)): ?>
                            <div style="text-align: center; color: var(--text-secondary); font-style: italic; padding: 3rem;">
                                <i class="fas fa-tag" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i><br>
                                Aucun tag disponible
                            </div>
                            <?php else: ?>
                                <?php foreach ($tags as $tag): ?>
                                <div class="tag-row">
                                    <input type="checkbox" 
                                           class="tag-checkbox" 
                                           name="tag_ids[]" 
                                           value="<?= $tag['id'] ?>" 
                                           id="tag-<?= $tag['id'] ?>"
                                           <?= (isset($_POST['tag_ids']) && in_array($tag['id'], $_POST['tag_ids'])) ? 'checked' : '' ?>>
                                    <label class="tag-name" for="tag-<?= $tag['id'] ?>">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- New Tags -->
                    <div class="form-section">
                        <label for="new_tags" class="form-label">
                            <i class="fas fa-plus-circle"></i>
                            Nouveaux Tags
                        </label>
                        <input type="text" 
                               class="form-input" 
                               id="new_tags" 
                               name="new_tags" 
                               placeholder="tag1, tag2, tag3" 
                               value="<?= isset($_POST['new_tags']) ? htmlspecialchars($_POST['new_tags']) : '' ?>">
                        <small style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 1rem; display: block;">
                            <i class="fas fa-info-circle"></i> Séparez les tags par des virgules
                        </small>
                    </div>
                    
                    <!-- Actions -->
                    <div class="action-section">
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fas fa-rocket"></i>
                            Publier l'Article
                        </button>
                        <a href="index.php?page=base_connaissances" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Annuler
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Notification -->
<div class="notification" id="notification">
    <i class="fas fa-check-circle" style="margin-right: 0.75rem;"></i>
    <span id="notification-text">Prêt</span>
</div>

<!-- CKEditor 5 Scripts -->
<script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notification = document.getElementById('notification');
    const notificationText = document.getElementById('notification-text');
    
    function showNotification(text, duration = 3000) {
        notificationText.textContent = text;
        notification.classList.add('show');
        setTimeout(() => {
            notification.classList.remove('show');
        }, duration);
    }

    // Détecter les changements de thème en temps réel
    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    
    function updateTheme() {
        const isDarkMode = darkModeMediaQuery.matches;
        if (isDarkMode) {
            document.body.classList.add('dark-mode');
            showNotification('🌙 Mode nuit activé', 2000);
        } else {
            document.body.classList.remove('dark-mode');
            showNotification('☀️ Mode jour activé', 2000);
        }
    }
    
    // Écouter les changements de thème (seulement les changements, pas l'état initial)
    darkModeMediaQuery.addListener(updateTheme);

    // Gestion du modal de création de catégorie
    const categorySelect = document.getElementById('category_id');
    const newCategoryModal = document.getElementById('new-category-modal');
    const newCategoryInput = document.getElementById('new-category-name');
    const createCategoryBtn = document.getElementById('create-category');
    const cancelCategoryBtn = document.getElementById('cancel-category');

    categorySelect.addEventListener('change', function() {
        if (this.value === 'create_new') {
            newCategoryModal.style.display = 'block';
            setTimeout(() => newCategoryInput.focus(), 100);
        }
    });

    cancelCategoryBtn.addEventListener('click', function() {
        newCategoryModal.style.display = 'none';
        categorySelect.value = '';
        newCategoryInput.value = '';
    });

    // Fermer le modal en cliquant sur l'overlay
    newCategoryModal.addEventListener('click', function(e) {
        if (e.target === newCategoryModal) {
            cancelCategoryBtn.click();
        }
    });

    // Créer la catégorie
    createCategoryBtn.addEventListener('click', function() {
        const categoryName = newCategoryInput.value.trim();
        
        if (!categoryName) {
            showNotification('❌ Veuillez saisir un nom de catégorie');
            return;
        }

        // Désactiver le bouton
        createCategoryBtn.disabled = true;
        createCategoryBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création...';

        // Envoyer la requête AJAX
        fetch('ajax/create_kb_category.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name: categoryName })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('✅ Catégorie créée avec succès');
                
                // Ajouter la nouvelle catégorie au select
                const option = document.createElement('option');
                option.value = data.id;
                option.textContent = categoryName;
                option.selected = true;
                
                // Insérer avant l'option "Créer une catégorie"
                const createOption = categorySelect.querySelector('option[value="create_new"]');
                categorySelect.insertBefore(option, createOption);
                
                // Fermer le modal
                newCategoryModal.style.display = 'none';
                newCategoryInput.value = '';
            } else {
                showNotification('❌ Erreur: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('❌ Erreur lors de la création');
        })
        .finally(() => {
            // Réactiver le bouton
            createCategoryBtn.disabled = false;
            createCategoryBtn.innerHTML = '<i class="fas fa-plus"></i> Créer';
        });
    });

    // Permettre la création avec Entrée
    newCategoryInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            createCategoryBtn.click();
        }
    });

    const typeRadios = document.querySelectorAll('input[name="type"]');
    const urlSection = document.getElementById('url-section');
    const htmlSection = document.getElementById('html-section');
    const urlLabel = document.getElementById('url-label');
    const urlHelp = document.getElementById('url-help');
    const urlInput = document.getElementById('url');
    const htmlInput = document.getElementById('html-code');
    const contentLabel = document.getElementById('content-label');

    function updateFormByType(type) {
        // Masquer toutes les sections spéciales d'abord
        urlSection.classList.remove('visible');
        htmlSection.classList.remove('visible');
        urlInput.required = false;
        htmlInput.required = false;
        
        if (type === 'url') {
            urlSection.classList.add('visible');
            urlLabel.textContent = 'URL du lien';
            urlHelp.textContent = 'Saisissez l\'URL complète du lien';
            urlInput.placeholder = 'https://exemple.com';
            urlInput.required = true;
            contentLabel.textContent = 'Description (optionnelle)';
        } else if (type === 'youtube') {
            urlSection.classList.add('visible');
            urlLabel.textContent = 'URL ou ID YouTube';
            urlHelp.textContent = 'URL YouTube complète ou juste l\'ID de la vidéo';
            urlInput.placeholder = 'https://youtube.com/watch?v=... ou dQw4w9WgXcQ';
            urlInput.required = true;
            contentLabel.textContent = 'Description de la vidéo (optionnelle)';
        } else if (type === 'html') {
            htmlSection.classList.add('visible');
            htmlInput.required = true;
            contentLabel.textContent = 'Description (optionnelle)';
        } else {
            contentLabel.textContent = 'Contenu de l\'article';
        }
    }

    typeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            updateFormByType(this.value);
        });
    });

    const checkedType = document.querySelector('input[name="type"]:checked').value;
    updateFormByType(checkedType);

    const uploadZone = document.getElementById('upload-zone');
    const fileInput = document.getElementById('file-input');
    const fileList = document.getElementById('file-list');

    uploadZone.addEventListener('click', () => fileInput.click());
    
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });
    
    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('dragover');
    });
    
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });
    
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        Array.from(files).forEach(file => {
            if (file.size > 10 * 1024 * 1024) {
                showNotification('⚠️ Fichier trop volumineux: ' + file.name);
                return;
            }
            uploadFile(file);
        });
    }

    function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        showNotification('📤 Upload: ' + file.name);
        
        fetch('ajax/upload_kb_file.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('✅ Fichier uploadé: ' + file.name);
                addFileToList(file.name, data.url);
                insertFileInEditor(file.name, data.url);
            } else {
                showNotification('❌ Erreur: ' + data.error);
            }
        })
        .catch(error => {
            showNotification('❌ Erreur d\'upload');
            console.error('Upload error:', error);
        });
    }

    function addFileToList(filename, url) {
        const fileItem = document.createElement('div');
        const isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        fileItem.style.cssText = `
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: ${isDarkMode ? '#1e293b' : 'var(--input-bg)'};
            border: 1px solid ${isDarkMode ? '#475569' : 'var(--input-border)'};
            border-radius: 15px;
            margin-bottom: 0.5rem;
            color: ${isDarkMode ? '#e2e8f0' : 'var(--text-primary)'};
        `;
        
        fileItem.innerHTML = `
            <i class="fas fa-file" style="color: var(--primary);"></i>
            <span style="flex: 1; font-weight: 500; color: ${isDarkMode ? '#e2e8f0' : 'inherit'};">${filename}</span>
            <a href="${url}" target="_blank" style="color: var(--primary); text-decoration: none;">
                <i class="fas fa-external-link-alt"></i>
            </a>
        `;
        
        fileList.appendChild(fileItem);
    }

    function insertFileInEditor(filename, url) {
        if (window.editor) {
            const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(filename);
            let content;
            
            if (isImage) {
                content = `<p><img src="${url}" alt="${filename}" style="max-width: 100%; height: auto;"></p>`;
            } else {
                content = `<p><a href="${url}" target="_blank">${filename}</a></p>`;
            }
            
            const viewFragment = window.editor.data.processor.toView(content);
            const modelFragment = window.editor.data.toModel(viewFragment);
            window.editor.model.insertContent(modelFragment);
        }
    }

    ClassicEditor
        .create(document.querySelector('#editor'), {
            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'fontSize', 'fontColor', 'fontBackgroundColor', '|',
                    'alignment', '|',
                    'numberedList', 'bulletedList', '|',
                    'outdent', 'indent', '|',
                    'link', 'insertImage', 'mediaEmbed', 'insertTable', '|',
                    'blockQuote', 'codeBlock', '|',
                    'horizontalLine', 'pageBreak', '|',
                    'undo', 'redo', '|',
                    'sourceEditing'
                ],
                shouldNotGroupWhenFull: true
            },
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraphe', class: 'ck-heading_paragraph' },
                    { model: 'heading1', view: 'h1', title: 'Titre 1', class: 'ck-heading_heading1' },
                    { model: 'heading2', view: 'h2', title: 'Titre 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Titre 3', class: 'ck-heading_heading3' },
                    { model: 'heading4', view: 'h4', title: 'Titre 4', class: 'ck-heading_heading4' }
                ]
            },
            fontSize: {
                options: [9, 11, 13, 'default', 17, 19, 21, 25, 29, 33, 37]
            },
            fontColor: {
                colors: [
                    { color: 'hsl(0, 0%, 0%)', label: 'Noir' },
                    { color: 'hsl(0, 0%, 30%)', label: 'Gris foncé' },
                    { color: 'hsl(0, 0%, 60%)', label: 'Gris' },
                    { color: 'hsl(0, 0%, 90%)', label: 'Gris clair' },
                    { color: 'hsl(0, 0%, 100%)', label: 'Blanc', hasBorder: true },
                    { color: 'hsl(0, 75%, 60%)', label: 'Rouge' },
                    { color: 'hsl(30, 75%, 60%)', label: 'Orange' },
                    { color: 'hsl(60, 75%, 60%)', label: 'Jaune' },
                    { color: 'hsl(90, 75%, 60%)', label: 'Vert clair' },
                    { color: 'hsl(120, 75%, 60%)', label: 'Vert' },
                    { color: 'hsl(150, 75%, 60%)', label: 'Turquoise' },
                    { color: 'hsl(180, 75%, 60%)', label: 'Cyan' },
                    { color: 'hsl(210, 75%, 60%)', label: 'Bleu clair' },
                    { color: 'hsl(240, 75%, 60%)', label: 'Bleu' },
                    { color: 'hsl(270, 75%, 60%)', label: 'Violet' }
                ]
            },
            image: {
                toolbar: [
                    'imageStyle:alignLeft', 'imageStyle:alignCenter', 'imageStyle:alignRight', '|',
                    'imageTextAlternative', '|', 'resizeImage'
                ],
                resizeOptions: [
                    { name: 'resizeImage:original', label: 'Taille originale', value: null },
                    { name: 'resizeImage:50', label: '50%', value: '50' },
                    { name: 'resizeImage:75', label: '75%', value: '75' }
                ]
            },
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
            },
            mediaEmbed: {
                previewsInData: true
            },
            codeBlock: {
                languages: [
                    { language: 'plaintext', label: 'Texte simple' },
                    { language: 'c', label: 'C' },
                    { language: 'cs', label: 'C#' },
                    { language: 'cpp', label: 'C++' },
                    { language: 'css', label: 'CSS' },
                    { language: 'diff', label: 'Diff' },
                    { language: 'html', label: 'HTML' },
                    { language: 'java', label: 'Java' },
                    { language: 'javascript', label: 'JavaScript' },
                    { language: 'php', label: 'PHP' },
                    { language: 'python', label: 'Python' },
                    { language: 'ruby', label: 'Ruby' },
                    { language: 'typescript', label: 'TypeScript' },
                    { language: 'xml', label: 'XML' },
                    { language: 'json', label: 'JSON' },
                    { language: 'sql', label: 'SQL' }
                ]
            },
            language: 'fr'
        })
        .then(editor => {
            window.editor = editor;
            
            showNotification('✨ Éditeur moderne initialisé');
            
            const editorContainer = document.getElementById('editor-container');
            
            // FORCER le mode sombre au niveau JavaScript
            function forceEditorDarkMode() {
                const isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (isDarkMode) {
                    // Forcer les styles sombres directement sur les éléments
                    const ckEditor = editorContainer.querySelector('.ck-editor');
                    const ckToolbar = editorContainer.querySelector('.ck-toolbar');
                    const ckContent = editorContainer.querySelector('.ck-content');
                    const ckEditable = editorContainer.querySelector('.ck-editor__editable');
                    
                    if (ckEditor) {
                        ckEditor.style.setProperty('background', '#0f1419', 'important');
                        ckEditor.style.setProperty('background-color', '#0f1419', 'important');
                        ckEditor.style.setProperty('border-color', '#475569', 'important');
                    }
                    
                    if (ckToolbar) {
                        ckToolbar.style.setProperty('background', '#1e293b', 'important');
                        ckToolbar.style.setProperty('background-color', '#1e293b', 'important');
                        ckToolbar.style.setProperty('color', '#e2e8f0', 'important');
                    }
                    
                    if (ckContent) {
                        ckContent.style.setProperty('background', '#0f1419', 'important');
                        ckContent.style.setProperty('background-color', '#0f1419', 'important');
                        ckContent.style.setProperty('color', '#e2e8f0', 'important');
                    }
                    
                    if (ckEditable) {
                        ckEditable.style.setProperty('background', '#0f1419', 'important');
                        ckEditable.style.setProperty('background-color', '#0f1419', 'important');
                        ckEditable.style.setProperty('color', '#e2e8f0', 'important');
                    }
                }
            }
            
            // Appliquer le mode sombre immédiatement
            setTimeout(forceEditorDarkMode, 100);
            
            // Observer les changements de thème
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', forceEditorDarkMode);
            
            editor.editing.view.document.on('focus', () => {
                editorContainer.classList.add('focused');
                setTimeout(forceEditorDarkMode, 50); // Re-appliquer au focus
            });
            
            editor.editing.view.document.on('blur', () => {
                editorContainer.classList.remove('focused');
            });
            
            editor.model.document.on('change:data', () => {
                document.getElementById('content').value = editor.getData();
            });
            
            editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
                return new CustomUploadAdapter(loader);
            };
        })
        .catch(error => {
            console.error('Erreur lors de l\'initialisation de CKEditor:', error);
            showNotification('❌ Erreur lors de l\'initialisation');
        });

    class CustomUploadAdapter {
        constructor(loader) {
            this.loader = loader;
        }

        upload() {
            return this.loader.file
                .then(file => new Promise((resolve, reject) => {
                    const formData = new FormData();
                    formData.append('file', file);

                    showNotification('📤 Upload CKEditor...');

                    fetch('ajax/upload_kb_file.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('✅ Fichier inséré');
                            resolve({ default: data.url });
                        } else {
                            showNotification('❌ Erreur: ' + data.error);
                            reject(data.error);
                        }
                    })
                    .catch(error => {
                        showNotification('❌ Erreur d\'upload CKEditor');
                        reject(error);
                    });
                }));
        }

        abort() {
        }
    }

    document.getElementById('article-form').addEventListener('submit', function(e) {
        if (window.editor) {
            document.getElementById('content').value = window.editor.getData();
        }
        
        showNotification('🚀 Publication en cours...');
        
        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publication...';
    });

    let autoSaveTimeout;
    if (window.editor) {
        window.editor.model.document.on('change:data', () => {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                showNotification('💾 Sauvegarde auto', 1000);
            }, 5000);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
