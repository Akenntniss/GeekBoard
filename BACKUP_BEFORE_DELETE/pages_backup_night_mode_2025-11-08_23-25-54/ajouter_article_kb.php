<?php
// Page d'ajout d'un article à la base de connaissances - Version CKEditor 5
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
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --secondary: #06b6d4;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --dark: #1f2937;
            --light: #f9fafb;
            --border: #e5e7eb;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--gray-800);
            line-height: 1.6;
        }

        .page-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .title-icon {
            width: 4rem;
            height: 4rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .title-text h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .title-text p {
            color: var(--gray-600);
            font-size: 1.1rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 15px;
            color: var(--gray-700);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .main-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            min-height: 85vh;
        }

        .editor-panel {
            padding: 3rem;
            background: #ffffff;
        }

        .sidebar-panel {
            background: linear-gradient(180deg, var(--gray-50) 0%, var(--gray-100) 100%);
            padding: 3rem;
            border-left: 1px solid var(--border);
        }

        .form-section {
            margin-bottom: 2.5rem;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--gray-800);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            padding: 1.25rem 1.5rem;
            border: 2px solid var(--gray-200);
            border-radius: 15px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            transform: translateY(-1px);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234f46e5' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1.5rem center;
            background-size: 1.25rem;
            padding-right: 4rem;
        }


        #editor-container {
            border: 3px solid var(--gray-200);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }

        #editor-container.focused {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .tags-container {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 15px;
            padding: 1.5rem;
            max-height: 250px;
            overflow-y: auto;
        }

        .tag-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .tag-row:last-child {
            border-bottom: none;
        }

        .tag-checkbox {
            width: 1.5rem;
            height: 1.5rem;
            accent-color: var(--primary);
            border-radius: 6px;
        }

        .tag-name {
            font-size: 0.95rem;
            color: var(--gray-700);
            font-weight: 500;
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
            gap: 0.75rem;
            padding: 1.25rem 2rem;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            min-height: 3.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: var(--gray-700);
            border: 2px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: white;
            border-color: var(--gray-400);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            font-weight: 600;
            border: 2px solid;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        .notification {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: linear-gradient(135deg, var(--success) 0%, #047857 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 15px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            font-weight: 600;
        }

        .notification.show {
            transform: translateX(0);
        }

        /* CKEditor 5 Custom Styles */
        .ck-editor__editable {
            min-height: 500px !important;
            font-size: 16px !important;
            line-height: 1.6 !important;
            font-family: 'Inter', sans-serif !important;
        }

        .ck.ck-toolbar {
            border-radius: 15px 15px 0 0 !important;
            background: var(--gray-50) !important;
            border: none !important;
            padding: 1rem !important;
        }

        .ck.ck-content {
            border-radius: 0 0 15px 15px !important;
            border: none !important;
            padding: 2rem !important;
        }

        .ck.ck-button {
            border-radius: 10px !important;
            margin: 2px !important;
        }

        .ck.ck-button:hover {
            background: rgba(79, 70, 229, 0.1) !important;
        }

        .ck.ck-button.ck-on {
            background: var(--primary) !important;
            color: white !important;
        }

        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            :root {
                --dark: #f9fafb;
                --light: #111827;
                --border: #374151;
                --gray-50: #111827;
                --gray-100: #1f2937;
                --gray-200: #374151;
                --gray-300: #4b5563;
                --gray-400: #6b7280;
                --gray-500: #9ca3af;
                --gray-600: #d1d5db;
                --gray-700: #e5e7eb;
                --gray-800: #f3f4f6;
                --gray-900: #f9fafb;
            }

            body {
                background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
                color: var(--gray-100);
            }

            .page-header {
                background: rgba(31, 41, 55, 0.95);
                border: 1px solid rgba(75, 85, 99, 0.3);
            }

            .back-btn {
                background: rgba(31, 41, 55, 0.5);
                border: 1px solid rgba(75, 85, 99, 0.4);
                color: var(--gray-300);
            }

            .back-btn:hover {
                background: rgba(31, 41, 55, 0.7);
            }

            .main-card {
                background: rgba(31, 41, 55, 0.95);
                border: 1px solid rgba(75, 85, 99, 0.3);
            }

            .editor-panel {
                background: #1f2937;
            }

            .sidebar-panel {
                background: linear-gradient(180deg, #111827 0%, #0f172a 100%);
                border-left: 1px solid var(--border);
            }

            .form-input {
                background: #374151;
                border-color: #4b5563;
                color: var(--gray-100);
            }

            .form-input:focus {
                border-color: var(--primary);
                background: #4b5563;
            }

            .tags-container {
                background: #374151;
                border-color: #4b5563;
            }

            .tag-row {
                border-bottom-color: #4b5563;
            }

            .tag-name {
                color: var(--gray-300);
            }

            .alert-danger {
                background: #7f1d1d;
                color: #fca5a5;
                border-color: #dc2626;
            }

            /* CKEditor Dark Mode */
            .ck.ck-toolbar {
                background: #374151 !important;
                border-bottom: 1px solid #4b5563 !important;
            }

            .ck.ck-content {
                background: #1f2937 !important;
                color: #f3f4f6 !important;
            }

            .ck.ck-button {
                color: #f3f4f6 !important;
                border-color: #6b7280 !important;
            }

            .ck.ck-button:not(.ck-disabled):hover {
                background: rgba(79, 70, 229, 0.3) !important;
                color: #ffffff !important;
                border-color: var(--primary) !important;
            }

            .ck.ck-button.ck-on {
                background: var(--primary) !important;
                color: #ffffff !important;
                border-color: var(--primary) !important;
            }

            .ck.ck-button .ck-button__label {
                color: #f3f4f6 !important;
            }

            .ck.ck-dropdown__button {
                color: #f3f4f6 !important;
            }

            .ck.ck-dropdown__button:not(.ck-disabled):hover {
                background: rgba(79, 70, 229, 0.3) !important;
                color: #ffffff !important;
            }

            .ck.ck-splitbutton__arrow {
                color: #f3f4f6 !important;
            }

            .ck.ck-icon {
                color: #f3f4f6 !important;
            }

            #editor-container {
                border-color: #4b5563;
            }

            #editor-container.focused {
                border-color: var(--primary);
                box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.2);
            }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .form-layout {
                grid-template-columns: 1fr;
            }
            
            .sidebar-panel {
                border-left: none;
                border-top: 1px solid var(--border);
            }
        }

        @media (max-width: 768px) {
            .page-container {
                padding: 1rem;
            }
            
            .page-header {
                padding: 1.5rem;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .title-text h1 {
                font-size: 2rem;
            }
            
            .editor-panel,
            .sidebar-panel {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="page-title">
                    <div class="title-icon">
                        <i class="fas fa-pen-fancy"></i>
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
                <li><i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
        <!-- Main Content -->
        <div class="main-card">
            <form action="index.php?page=ajouter_article_kb" method="POST" id="article-form">
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
                                

                        <!-- Content Editor -->
                        <div class="form-section">
                            <label for="content" class="form-label">
                                <i class="fas fa-edit"></i>
                                Contenu de l'article
                            </label>
                            <div id="editor-container">
                                <div id="editor"><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></div>
                            </div>
                            <textarea id="content" name="content" style="display: none;" required></textarea>
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
                                    </select>
                                </div>
                                
                        <!-- Existing Tags -->
                        <div class="form-section">
                            <label class="form-label">
                                <i class="fas fa-tags"></i>
                                Tags Disponibles
                            </label>
                            <div class="tags-container">
                                        <?php if (empty($tags)): ?>
                                <div style="text-align: center; color: var(--gray-500); font-style: italic; padding: 2rem;">
                                    <i class="fas fa-tag" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i><br>
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
                            <small style="color: var(--gray-500); font-size: 0.85rem; margin-top: 0.75rem; display: block;">
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
        <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i>
        <span id="notification-text">Prêt</span>
</div>

    <!-- CKEditor 5 Scripts -->
    <script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
            // Notification system
            const notification = document.getElementById('notification');
            const notificationText = document.getElementById('notification-text');
            
            function showNotification(text, duration = 3000) {
                notificationText.textContent = text;
                notification.classList.add('show');
                setTimeout(() => {
                    notification.classList.remove('show');
                }, duration);
            }

            // Initialize CKEditor 5
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
                        options: [
                            9, 11, 13, 'default', 17, 19, 21, 25, 29, 33, 37
                        ]
                    },
                    fontColor: {
                        colors: [
                            {
                                color: 'hsl(0, 0%, 0%)',
                                label: 'Noir'
                            },
                            {
                                color: 'hsl(0, 0%, 30%)',
                                label: 'Gris foncé'
                            },
                            {
                                color: 'hsl(0, 0%, 60%)',
                                label: 'Gris'
                            },
                            {
                                color: 'hsl(0, 0%, 90%)',
                                label: 'Gris clair'
                            },
                            {
                                color: 'hsl(0, 0%, 100%)',
                                label: 'Blanc',
                                hasBorder: true
                            },
                            {
                                color: 'hsl(0, 75%, 60%)',
                                label: 'Rouge'
                            },
                            {
                                color: 'hsl(30, 75%, 60%)',
                                label: 'Orange'
                            },
                            {
                                color: 'hsl(60, 75%, 60%)',
                                label: 'Jaune'
                            },
                            {
                                color: 'hsl(90, 75%, 60%)',
                                label: 'Vert clair'
                            },
                            {
                                color: 'hsl(120, 75%, 60%)',
                                label: 'Vert'
                            },
                            {
                                color: 'hsl(150, 75%, 60%)',
                                label: 'Turquoise'
                            },
                            {
                                color: 'hsl(180, 75%, 60%)',
                                label: 'Cyan'
                            },
                            {
                                color: 'hsl(210, 75%, 60%)',
                                label: 'Bleu clair'
                            },
                            {
                                color: 'hsl(240, 75%, 60%)',
                                label: 'Bleu'
                            },
                            {
                                color: 'hsl(270, 75%, 60%)',
                                label: 'Violet'
                            }
                        ]
                    },
                    image: {
                        toolbar: [
                            'imageStyle:alignLeft',
                            'imageStyle:alignCenter',
                            'imageStyle:alignRight',
                            '|',
                            'imageTextAlternative',
                            '|',
                            'resizeImage'
                        ],
                        resizeOptions: [
                            {
                                name: 'resizeImage:original',
                                label: 'Taille originale',
                                value: null
                            },
                            {
                                name: 'resizeImage:50',
                                label: '50%',
                                value: '50'
                            },
                            {
                                name: 'resizeImage:75',
                                label: '75%',
                                value: '75'
                            }
                        ]
                    },
                    table: {
                        contentToolbar: [
                            'tableColumn',
                            'tableRow',
                            'mergeTableCells',
                            'tableProperties',
                            'tableCellProperties'
                        ]
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
                    
                    // Show notification when ready
                    showNotification('✨ Éditeur CKEditor 5 initialisé');
                    
                    // Focus effect
                    const editorContainer = document.getElementById('editor-container');
                    
                    editor.editing.view.document.on('focus', () => {
                        editorContainer.classList.add('focused');
                    });
                    
                    editor.editing.view.document.on('blur', () => {
                        editorContainer.classList.remove('focused');
                    });
                    
                    // Update hidden textarea on change
                    editor.model.document.on('change:data', () => {
                        document.getElementById('content').value = editor.getData();
                    });
                    
                    // File upload functionality
                    editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
                        return new CustomUploadAdapter(loader);
                    };
                })
                .catch(error => {
                    console.error('Erreur lors de l\'initialisation de CKEditor:', error);
                    showNotification('❌ Erreur lors de l\'initialisation');
                });

            // Custom upload adapter
            class CustomUploadAdapter {
                constructor(loader) {
                    this.loader = loader;
                }

                upload() {
                    return this.loader.file
                        .then(file => new Promise((resolve, reject) => {
                            const formData = new FormData();
                            formData.append('file', file);

                            showNotification('📤 Upload en cours...');

                            fetch('ajax/upload_file.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showNotification('✅ Fichier uploadé');
                                    resolve({
                                        default: data.url
                                    });
                                } else {
                                    showNotification('❌ Erreur: ' + data.error);
                                    reject(data.error);
                                }
                            })
                            .catch(error => {
                                showNotification('❌ Erreur d\'upload');
                                reject(error);
                            });
                        }));
                }

                abort() {
                    // Abort upload if needed
                }
            }

            // Form submission
            document.getElementById('article-form').addEventListener('submit', function(e) {
                // Update content before submit
                if (window.editor) {
                    document.getElementById('content').value = window.editor.getData();
                }
                
                showNotification('🚀 Publication en cours...');
                
                // Disable submit button
                const submitBtn = document.getElementById('submit-btn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publication...';
            });

            // Auto-save functionality (optional)
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
</body>
</html>
