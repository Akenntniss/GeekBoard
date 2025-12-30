<?php
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

// Récupérer les catégories et les tags
$categories = get_kb_categories();
$tags = get_kb_tags();

// Traitement du formulaire d'ajout d'article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_article') {
    $title = cleanInput($_POST['title']);
    $content = $_POST['content'];
    $category_id = intval($_POST['category_id']);
    $tag_ids = isset($_POST['tag_ids']) ? $_POST['tag_ids'] : [];
    $new_tags = isset($_POST['new_tags']) ? $_POST['new_tags'] : '';
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Le titre de l'article est requis.";
    }
    
    if (empty($content)) {
        $errors[] = "Le contenu de l'article est requis.";
    }
    
    if ($category_id <= 0) {
        $errors[] = "Veuillez sélectionner une catégorie.";
    }
    
    if (empty($errors)) {
        try {
            $shop_pdo = getShopDBConnection();
            $shop_pdo->beginTransaction();
            
            $query = "INSERT INTO kb_articles (title, content, category_id, created_at, updated_at, views) 
                      VALUES (?, ?, ?, NOW(), NOW(), 0)";
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$title, $content, $category_id]);
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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - GeekBoard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #667eea;
            --primary-dark: #5a6fd8;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --border-color: #e5e7eb;
            --shadow-light: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-heavy: 0 10px 25px rgba(0, 0, 0, 0.15);
            --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --gradient-accent: linear-gradient(135deg, var(--accent-color) 0%, var(--primary-color) 100%);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-heavy);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-title h1 {
            font-size: 2rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-icon {
            width: 3rem;
            height: 3rem;
            background: var(--gradient-primary);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            color: var(--dark-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-heavy);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 0;
            min-height: 80vh;
        }

        .editor-section {
            padding: 2rem;
            background: #ffffff;
        }

        .sidebar {
            background: linear-gradient(180deg, #f8fafc 0%, #e5e7eb 100%);
            padding: 2rem;
            border-left: 1px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #ffffff;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
            padding-right: 3rem;
        }

        .editor-help {
            background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
            border: 1px solid #0284c7;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .editor-help h3 {
            color: #0284c7;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .editor-help ul {
            list-style: none;
            space-y: 0.5rem;
        }

        .editor-help li {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .editor-help li::before {
            content: "✨";
            flex-shrink: 0;
        }

        .rich-editor-container {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .rich-editor-container:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .tags-section {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .tag-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .tag-item:last-child {
            border-bottom: none;
        }

        .tag-checkbox {
            width: 1.2rem;
            height: 1.2rem;
            accent-color: var(--primary-color);
        }

        .tag-label {
            font-size: 0.875rem;
            color: var(--dark-color);
            cursor: pointer;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 3rem;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-medium);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-heavy);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            color: var(--dark-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .status-indicator {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: var(--gradient-primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-heavy);
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .status-indicator.show {
            transform: translateX(0);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .form-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                border-left: none;
                border-top: 1px solid var(--border-color);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header {
                padding: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .editor-section,
            .sidebar {
                padding: 1.5rem;
            }
        }

        /* TinyMCE Custom Styles */
        .tox-tinymce {
            border: none !important;
            border-radius: 0 !important;
        }

        .tox-toolbar-overlord {
            background: #f8fafc !important;
            border-bottom: 1px solid var(--border-color) !important;
        }

        .tox .tox-toolbar__primary {
            background: transparent !important;
        }

        .tox .tox-tbtn {
            border-radius: 8px !important;
            margin: 2px !important;
        }

        .tox .tox-tbtn:hover {
            background: rgba(102, 126, 234, 0.1) !important;
            border-color: var(--primary-color) !important;
        }

        .tox .tox-tbtn--enabled {
            background: var(--primary-color) !important;
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-title">
                    <div class="header-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h1>Créer un Article</h1>
                </div>
                <a href="index.php?page=base_connaissances" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Retour à la liste
                </a>
            </div>
        </div>

        <!-- Messages d'erreur -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="list-style: none; margin: 0;">
                <?php foreach ($errors as $error): ?>
                <li><i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Contenu principal -->
        <div class="main-content">
            <form action="index.php?page=ajouter_article_kb" method="POST" id="add-article-form">
                <input type="hidden" name="action" value="add_article">
                
                <div class="form-container">
                    <!-- Section éditeur -->
                    <div class="editor-section">
                        <!-- Titre -->
                        <div class="form-group">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading" style="margin-right: 0.5rem;"></i>
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

                        <!-- Guide d'aide -->
                        <div class="editor-help">
                            <h3>
                                <i class="fas fa-lightbulb"></i>
                                Guide de l'éditeur professionnel
                            </h3>
                            <ul>
                                <li><strong>Fichiers :</strong> Bouton "Fichier" pour ajouter des documents téléchargeables</li>
                                <li><strong>Vidéos :</strong> Bouton média pour intégrer YouTube, Vimeo, etc.</li>
                                <li><strong>Images :</strong> Glissez-déposez ou utilisez le bouton image</li>
                                <li><strong>Code :</strong> Bouton "&lt;/&gt;" pour la coloration syntaxique</li>
                                <li><strong>Tableaux :</strong> Menu tableau pour des données structurées</li>
                                <li><strong>Styles :</strong> Formats prédéfinis dans le menu déroulant</li>
                            </ul>
                        </div>

                        <!-- Éditeur -->
                        <div class="form-group">
                            <label for="content" class="form-label">
                                <i class="fas fa-edit" style="margin-right: 0.5rem;"></i>
                                Contenu de l'article
                            </label>
                            <div class="rich-editor-container">
                                <textarea class="rich-editor" 
                                          id="content" 
                                          name="content" 
                                          required><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="sidebar">
                        <!-- Catégorie -->
                        <div class="form-group">
                            <label for="category_id" class="form-label">
                                <i class="fas fa-folder" style="margin-right: 0.5rem;"></i>
                                Catégorie
                            </label>
                            <select class="form-input form-select" id="category_id" name="category_id" required>
                                <option value="">Choisir une catégorie</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" 
                                        <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tags existants -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-tags" style="margin-right: 0.5rem;"></i>
                                Tags existants
                            </label>
                            <div class="tags-section">
                                <?php if (empty($tags)): ?>
                                <div style="text-align: center; color: #6b7280; font-style: italic;">
                                    Aucun tag disponible
                                </div>
                                <?php else: ?>
                                    <?php foreach ($tags as $tag): ?>
                                    <div class="tag-item">
                                        <input type="checkbox" 
                                               class="tag-checkbox" 
                                               name="tag_ids[]" 
                                               value="<?= $tag['id'] ?>" 
                                               id="tag-<?= $tag['id'] ?>"
                                               <?= (isset($_POST['tag_ids']) && in_array($tag['id'], $_POST['tag_ids'])) ? 'checked' : '' ?>>
                                        <label class="tag-label" for="tag-<?= $tag['id'] ?>">
                                            <?= htmlspecialchars($tag['name']) ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Nouveaux tags -->
                        <div class="form-group">
                            <label for="new_tags" class="form-label">
                                <i class="fas fa-plus" style="margin-right: 0.5rem;"></i>
                                Nouveaux tags
                            </label>
                            <input type="text" 
                                   class="form-input" 
                                   id="new_tags" 
                                   name="new_tags" 
                                   placeholder="tag1, tag2, tag3"
                                   value="<?= isset($_POST['new_tags']) ? htmlspecialchars($_POST['new_tags']) : '' ?>">
                            <small style="color: #6b7280; font-size: 0.75rem; margin-top: 0.5rem; display: block;">
                                Séparez les tags par des virgules
                            </small>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Publier l'article
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

    <!-- Status indicator -->
    <div class="status-indicator" id="status-indicator">
        <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i>
        <span id="status-text">Prêt</span>
    </div>

    <!-- TinyMCE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Status indicator
            const statusIndicator = document.getElementById('status-indicator');
            const statusText = document.getElementById('status-text');
            
            function showStatus(text, type = 'info') {
                statusText.textContent = text;
                statusIndicator.classList.add('show');
                setTimeout(() => {
                    statusIndicator.classList.remove('show');
                }, 3000);
            }

            // Initialize TinyMCE
            tinymce.init({
                selector: '.rich-editor',
                height: 600,
                menubar: 'file edit view insert format tools table help',
                
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons',
                    'codesample', 'hr', 'nonbreaking'
                ],
                
                toolbar1: 'undo redo | formatselect fontsize | bold italic underline strikethrough | forecolor backcolor',
                toolbar2: 'alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist | link image media',
                toolbar3: 'table tabledelete | upload_file codesample emoticons | preview fullscreen code | help',
                
                content_style: `
                    body { 
                        font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                        font-size: 16px; 
                        line-height: 1.6;
                        color: #1f2937;
                        padding: 20px;
                    }
                    h1, h2, h3, h4, h5, h6 { 
                        color: #111827; 
                        margin-top: 1.5em; 
                        margin-bottom: 0.5em; 
                    }
                    p { margin-bottom: 1em; }
                    blockquote { 
                        border-left: 4px solid #667eea; 
                        padding-left: 20px; 
                        margin: 20px 0; 
                        font-style: italic; 
                        background: #f8fafc; 
                        padding: 15px 15px 15px 25px;
                        border-radius: 8px;
                    }
                    code { 
                        background: #f1f5f9; 
                        padding: 2px 4px; 
                        border-radius: 4px; 
                        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace; 
                    }
                    .file-download {
                        margin: 15px 0;
                        padding: 15px;
                        border: 1px solid #e5e7eb;
                        border-radius: 8px;
                        background: #f8fafc;
                        transition: all 0.3s ease;
                    }
                    .file-download:hover {
                        background: #f1f5f9;
                        transform: translateY(-1px);
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    }
                `,
                
                language: 'fr_FR',
                
                // File upload functionality
                setup: function (editor) {
                    editor.ui.registry.addButton('upload_file', {
                        text: 'Fichier',
                        icon: 'upload',
                        tooltip: 'Télécharger un fichier',
                        onAction: function () {
                            const input = document.createElement('input');
                            input.type = 'file';
                            input.accept = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.jpg,.jpeg,.png,.gif,.mp4,.avi,.mov';
                            input.onchange = function() {
                                if (this.files && this.files[0]) {
                                    uploadFile(this.files[0], editor);
                                }
                            };
                            input.click();
                        }
                    });
                    
                    editor.on('init', function() {
                        showStatus('Éditeur initialisé', 'success');
                    });
                },
                
                // Media URLs
                media_live_embeds: true,
                media_url_resolver: function (data, resolve) {
                    if (data.url.indexOf('youtube.com') !== -1 || data.url.indexOf('youtu.be') !== -1) {
                        let videoId = data.url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
                        if (videoId) {
                            resolve({ 
                                html: `<iframe width="560" height="315" src="https://www.youtube.com/embed/${videoId[1]}" frameborder="0" allowfullscreen></iframe>` 
                            });
                        }
                    } else if (data.url.indexOf('vimeo.com') !== -1) {
                        const videoId = data.url.split('/').pop();
                        resolve({ 
                            html: `<iframe src="https://player.vimeo.com/video/${videoId}" width="560" height="315" frameborder="0" allowfullscreen></iframe>` 
                        });
                    } else {
                        resolve({ html: '' });
                    }
                }
            });

            // File upload function
            function uploadFile(file, editor) {
                const formData = new FormData();
                formData.append('file', file);
                
                showStatus('Téléchargement en cours...', 'info');
                editor.setProgressState(true);
                
                fetch('ajax/upload_file.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    editor.setProgressState(false);
                    if (data.success) {
                        const html = `<div class="file-download">
                            <i class="fas fa-file" style="margin-right: 8px; color: #667eea;"></i>
                            <a href="${data.url}" download="${data.original_name}" style="text-decoration: none; color: #667eea; font-weight: 600;">
                                ${data.original_name}
                            </a>
                            <span style="color: #6b7280; font-size: 0.9em; margin-left: 8px;">(${data.size})</span>
                        </div>`;
                        editor.insertContent(html);
                        showStatus('Fichier ajouté avec succès', 'success');
                    } else {
                        showStatus('Erreur: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    editor.setProgressState(false);
                    showStatus('Erreur de téléchargement', 'error');
                });
            }

            // Form submission
            document.getElementById('add-article-form').addEventListener('submit', function() {
                showStatus('Publication en cours...', 'info');
            });
        });
    </script>
</body>
</html>
