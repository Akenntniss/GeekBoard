<?php
// Page d'ajout d'un article à la base de connaissances
$page_title = "Ajouter un article à la Base de Connaissances";
require_once 'includes/header.php';

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
        $shop_pdo = getShopDBConnection();
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
        // Vérifier si le tag existe déjà
        $query = "SELECT id FROM kb_tags WHERE name = ?";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([trim($tag_name)]);
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tag) {
            return $tag['id'];
        } else {
            // Créer le tag s'il n'existe pas
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
    $content = $_POST['content']; // Ne pas nettoyer le contenu qui peut contenir du HTML formaté
    $category_id = intval($_POST['category_id']);
    $tag_ids = isset($_POST['tag_ids']) ? $_POST['tag_ids'] : [];
    $new_tags = isset($_POST['new_tags']) ? $_POST['new_tags'] : '';
    
    // Validation basique
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
    
    // Si aucune erreur, ajouter l'article
    if (empty($errors)) {
        try {
            // Début de la transaction
            $shop_pdo->beginTransaction();
            
            // Insérer l'article
            $query = "INSERT INTO kb_articles (title, content, category_id, created_at, updated_at, views) 
                      VALUES (?, ?, ?, NOW(), NOW(), 0)";
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$title, $content, $category_id]);
            $article_id = $shop_pdo->lastInsertId();
            
            // Ajouter les tags existants sélectionnés
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
            
            // Traiter les nouveaux tags
            if (!empty($new_tags)) {
                $tag_names = explode(',', $new_tags);
                
                foreach ($tag_names as $tag_name) {
                    $tag_name = trim($tag_name);
                    if (!empty($tag_name)) {
                        $tag_id = get_or_create_tag($tag_name);
                        
                        if ($tag_id) {
                            // Ajouter l'association entre l'article et le tag
                            $query = "INSERT INTO kb_article_tags (article_id, tag_id) VALUES (?, ?)";
                            $stmt = $shop_pdo->prepare($query);
                            $stmt->execute([$article_id, $tag_id]);
                        }
                    }
                }
            }
            
            // Valider la transaction
            $shop_pdo->commit();
            
            set_message("L'article a été ajouté avec succès à la base de connaissances.", "success");
            redirect('article_kb', ['id' => $article_id]);
            
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $shop_pdo->rollBack();
            error_log("Erreur lors de l'ajout de l'article: " . $e->getMessage());
            set_message("Une erreur est survenue lors de l'ajout de l'article. Veuillez réessayer.", "danger");
        }
    }
}
?>

<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i> Ajouter un article à la Base de Connaissances
                    </h5>
                    <a href="index.php?page=base_connaissances" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                    </a>
                </div>
                <div class="card-body">
                    <!-- Affichage des erreurs -->
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Formulaire d'ajout d'article -->
                    <form action="index.php?page=ajouter_article_kb" method="POST" id="add-article-form">
                        <input type="hidden" name="action" value="add_article">
                        
                        <div class="row mb-3">
                            <div class="col-md-9">
                                <!-- Titre de l'article -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">Titre de l'article <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                                </div>
                                
                                <!-- Contenu de l'article -->
                                <div class="mb-3">
                                    <label for="content" class="form-label">Contenu de l'article <span class="text-danger">*</span></label>
                                    
                                    <!-- Aide pour l'éditeur -->
                                    <div class="editor-help">
                                        <h5><i class="fas fa-info-circle"></i> Guide de l'éditeur avancé</h5>
                                        <ul>
                                            <li><strong>📁 Fichiers :</strong> Cliquez sur "Fichier" pour ajouter des documents téléchargeables (PDF, Word, Excel, etc.)</li>
                                            <li><strong>📹 Vidéos YouTube :</strong> Cliquez sur l'icône vidéo puis collez l'URL YouTube</li>
                                            <li><strong>🖼️ Images :</strong> Cliquez sur l'icône image pour insérer des photos</li>
                                            <li><strong>📊 Tableaux :</strong> Menu "Tableau" pour créer des tableaux formatés</li>
                                            <li><strong>💻 Code :</strong> Cliquez sur "&lt;/&gt;" pour insérer du code avec coloration syntaxique</li>
                                            <li><strong>😊 Émojis :</strong> Cliquez sur l'icône smiley pour ajouter des émojis</li>
                                            <li><strong>🎨 Styles :</strong> Utilisez les menus de formatage pour personnaliser le texte</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="rich-editor-container">
                                    <textarea class="form-control rich-editor" id="content" name="content" rows="15" required><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></textarea>
                                    </div>
                                    <div class="form-text">Éditeur professionnel avec support complet : vidéos, images, tableaux, code, styles personnalisés et bien plus !</div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <!-- Catégorie -->
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Sélectionner une catégorie</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Tags existants -->
                                <div class="mb-3">
                                    <label class="form-label">Tags existants</label>
                                    <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                        <?php if (empty($tags)): ?>
                                        <div class="text-muted small">Aucun tag existant.</div>
                                        <?php else: ?>
                                            <?php foreach ($tags as $tag): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="tag_ids[]" 
                                                       value="<?= $tag['id'] ?>" id="tag-<?= $tag['id'] ?>"
                                                       <?= (isset($_POST['tag_ids']) && in_array($tag['id'], $_POST['tag_ids'])) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="tag-<?= $tag['id'] ?>">
                                                    <?= htmlspecialchars($tag['name']) ?>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Nouveaux tags -->
                                <div class="mb-3">
                                    <label for="new_tags" class="form-label">Nouveaux tags</label>
                                    <input type="text" class="form-control" id="new_tags" name="new_tags" 
                                           placeholder="tag1, tag2, tag3" 
                                           value="<?= isset($_POST['new_tags']) ? htmlspecialchars($_POST['new_tags']) : '' ?>">
                                    <div class="form-text">Séparez les tags par des virgules.</div>
                                </div>
                                
                                <!-- Boutons d'action -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Enregistrer l'article
                                    </button>
                                    <a href="index.php?page=base_connaissances" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Annuler
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inclure TinyMCE pour l'éditeur de texte riche avancé -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" integrity="sha512-VVJgTd0x5rC1lABQqPw5IleOiZ4Nk2xmdDTI9kSGZg/yFZNyZu0y/BaTB3XGqqaTN5ICOH3Kc8m0f+Kiy4FWjQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser TinyMCE avec configuration avancée
    tinymce.init({
        selector: '.rich-editor',
        height: 600,
        menubar: 'file edit view insert format tools table help',
        
        // Plugins complets pour un éditeur professionnel
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons',
            'template', 'codesample', 'hr', 'pagebreak', 'nonbreaking'
        ],
        
        // Barre d'outils complète avec toutes les fonctionnalités
        toolbar1: 'undo redo | bold italic underline strikethrough | formatselect fontsize | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist',
        toolbar2: 'forecolor backcolor | removeformat | charmap emoticons | fullscreen preview | image media link anchor codesample | upload_file',
        toolbar3: 'table tabledelete | tableprops tablerowprops tablecellprops | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
        
        // Configuration des médias avec support YouTube et autres
        media_live_embeds: true,
        media_url_resolver: function (data, resolve) {
            // Support des URLs YouTube, Vimeo, etc.
            if (data.url.indexOf('youtube.com') !== -1 || data.url.indexOf('youtu.be') !== -1) {
                resolve({ html: '<iframe src="' + data.url.replace('watch?v=', 'embed/').replace('youtu.be/', 'youtube.com/embed/') + '" width="560" height="315" frameborder="0" allowfullscreen></iframe>' });
            } else if (data.url.indexOf('vimeo.com') !== -1) {
                var videoId = data.url.split('/').pop();
                resolve({ html: '<iframe src="https://player.vimeo.com/video/' + videoId + '" width="560" height="315" frameborder="0" allowfullscreen></iframe>' });
            } else {
                resolve({ html: '' });
            }
        },
        
        // Configuration des images avec outils avancés
        image_advtab: true,
        image_uploadtab: true,
        image_title: true,
        automatic_uploads: true,
        file_picker_types: 'image',
        
        // Templates prédéfinis
        templates: [
            {
                title: 'Article avec image',
                description: 'Template d\'article avec image et texte',
                content: '<div class="article-template"><h2>Titre de l\'article</h2><img src="https://via.placeholder.com/400x300" alt="Image" style="width: 100%; max-width: 400px; height: auto; margin: 10px 0;"><p>Votre contenu ici...</p></div>'
            },
            {
                title: 'FAQ Question/Réponse',
                description: 'Template pour questions fréquentes',
                content: '<div class="faq-item"><h3 style="color: #2c5aa0;">❓ Question</h3><div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #2c5aa0; margin: 10px 0;"><p><strong>Réponse :</strong> Votre réponse détaillée ici...</p></div></div>'
            },
            {
                title: 'Tutoriel étape par étape',
                description: 'Template pour tutoriels',
                content: '<div class="tutorial"><h2>🎯 Tutoriel : Titre</h2><div class="step"><h3>Étape 1</h3><p>Description de l\'étape...</p></div><div class="step"><h3>Étape 2</h3><p>Description de l\'étape...</p></div></div>'
            },
            {
                title: 'Vidéo YouTube',
                description: 'Intégration vidéo YouTube',
                content: '<div class="video-container" style="text-align: center; margin: 20px 0;"><h3>📹 Vidéo explicative</h3><iframe width="560" height="315" src="https://www.youtube.com/embed/VOTRE_ID_VIDEO" frameborder="0" allowfullscreen></iframe><p><em>Remplacez VOTRE_ID_VIDEO par l\'ID de votre vidéo YouTube</em></p></div>'
            }
        ],
        
        // Configuration des couleurs
        color_map: [
            "000000", "Noir",
            "993300", "Marron foncé",
            "333300", "Olive foncé", 
            "003300", "Vert foncé",
            "003366", "Bleu marine",
            "000080", "Marine",
            "333399", "Indigo",
            "333333", "Gris très foncé",
            "800000", "Marron",
            "FF6600", "Orange",
            "808000", "Olive",
            "008000", "Vert",
            "008080", "Sarcelle",
            "0000FF", "Bleu",
            "666699", "Gris bleu",
            "808080", "Gris",
            "FF0000", "Rouge",
            "FF9900", "Ambre",
            "99CC00", "Jaune vert",
            "339966", "Vert mer",
            "33CCCC", "Turquoise",
            "3366FF", "Bleu royal",
            "800080", "Violet",
            "999999", "Gris moyen",
            "FF00FF", "Magenta",
            "FFCC00", "Or",
            "FFFF00", "Jaune",
            "00FF00", "Lime",
            "00FFFF", "Aqua",
            "00CCFF", "Bleu ciel",
            "993366", "Brun rouge",
            "C0C0C0", "Argent",
            "FF99CC", "Rose",
            "FFCC99", "Pêche",
            "FFFF99", "Jaune clair",
            "CCFFCC", "Vert clair",
            "CCFFFF", "Cyan clair",
            "99CCFF", "Bleu clair",
            "CC99FF", "Lavande",
            "FFFFFF", "Blanc"
        ],
        
        // Styles personnalisés
        style_formats: [
            {
                title: 'Styles de titre',
                items: [
                    { title: 'Titre principal', block: 'h1', styles: { color: '#2c5aa0', 'border-bottom': '2px solid #2c5aa0', 'padding-bottom': '10px' } },
                    { title: 'Sous-titre', block: 'h2', styles: { color: '#495057', 'margin-top': '25px' } },
                    { title: 'Section', block: 'h3', styles: { color: '#6c757d', 'margin-top': '20px' } }
                ]
            },
            {
                title: 'Boîtes d\'information',
                items: [
                    { title: 'Info', block: 'div', classes: 'alert alert-info', wrapper: true },
                    { title: 'Succès', block: 'div', classes: 'alert alert-success', wrapper: true },
                    { title: 'Attention', block: 'div', classes: 'alert alert-warning', wrapper: true },
                    { title: 'Erreur', block: 'div', classes: 'alert alert-danger', wrapper: true }
                ]
            },
            {
                title: 'Éléments spéciaux',
                items: [
                    { title: 'Citation', block: 'blockquote', classes: 'blockquote' },
                    { title: 'Code inline', inline: 'code' },
                    { title: 'Badge', inline: 'span', classes: 'badge badge-primary' }
                ]
            }
        ],
        
        // Configuration avancée
        content_style: `
            body { 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
                font-size: 16px; 
                line-height: 1.6;
                color: #333;
                margin: 20px;
            }
            .alert {
                padding: 15px;
                margin: 15px 0;
                border: 1px solid transparent;
                border-radius: 4px;
            }
            .alert-info { background-color: #d9edf7; border-color: #bce8f1; color: #31708f; }
            .alert-success { background-color: #dff0d8; border-color: #d6e9c6; color: #3c763d; }
            .alert-warning { background-color: #fcf8e3; border-color: #faebcc; color: #8a6d3b; }
            .alert-danger { background-color: #f2dede; border-color: #ebccd1; color: #a94442; }
            .blockquote { 
                border-left: 4px solid #2c5aa0; 
                padding-left: 20px; 
                margin: 20px 0; 
                font-style: italic; 
                background: #f8f9fa; 
                padding: 15px 15px 15px 25px;
            }
            .badge { 
                display: inline-block; 
                padding: 3px 7px; 
                font-size: 12px; 
                font-weight: bold; 
                color: white; 
                background-color: #007bff; 
                border-radius: 3px; 
            }
            code { 
                background: #f1f3f4; 
                padding: 2px 4px; 
                border-radius: 3px; 
                font-family: monospace; 
            }
            .video-container { 
                text-align: center; 
                margin: 20px 0; 
            }
            .video-container iframe { 
                max-width: 100%; 
                height: auto; 
            }
            table { 
                border-collapse: collapse; 
                width: 100%; 
                margin: 15px 0; 
            }
            table td, table th { 
                border: 1px solid #ddd; 
                padding: 8px; 
            }
            table th { 
                background-color: #f2f2f2; 
                font-weight: bold; 
            }
        `,
        
        // Paramètres de langue et localisation
        language: 'fr_FR',
        directionality: 'ltr',
        
        // Paramètres de sécurité et validation
        entity_encoding: 'raw',
        forced_root_block: 'p',
        remove_linebreaks: false,
        convert_newlines_to_brs: false,
        remove_trailing_brs: false,
        
        // Configuration des liens
        link_assume_external_targets: true,
        link_context_toolbar: true,
        
        // Configuration des tableaux
        table_default_attributes: {
            'class': 'table table-striped'
        },
        table_default_styles: {
            'border-collapse': 'collapse'
        },
        
        // Sauvegarde automatique (optionnel)
        autosave_ask_before_unload: true,
        autosave_interval: "30s",
        autosave_prefix: "{path}{query}-{id}-",
        autosave_restore_when_empty: false,
        autosave_retention: "2m",
        
        // Paramètres de performance
        paste_data_images: true,
        paste_as_text: false,
        
        // Configuration pour les mobiles
        mobile: {
            theme: 'mobile',
            plugins: ['autosave', 'lists', 'autolink'],
            toolbar: ['undo', 'bold', 'italic', 'styleselect']
        },
        
        // Bouton personnalisé pour upload de fichiers
        setup: function (editor) {
            editor.ui.registry.addButton('upload_file', {
                text: 'Fichier',
                icon: 'upload',
                tooltip: 'Télécharger un fichier',
                onAction: function () {
                    // Créer un input file
                    var input = document.createElement('input');
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
        }
    });
    
    // Fonction d'upload de fichier
    function uploadFile(file, editor) {
        var formData = new FormData();
        formData.append('file', file);
        
        // Afficher un indicateur de chargement
        editor.setProgressState(true);
        
        fetch('ajax/upload_file.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            editor.setProgressState(false);
            if (data.success) {
                // Insérer le lien de téléchargement dans l'éditeur
                var html = '<div class="file-download" style="margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;">' +
                          '<i class="fas fa-file" style="margin-right: 8px; color: #007bff;"></i>' +
                          '<a href="' + data.url + '" download="' + data.original_name + '" style="text-decoration: none; color: #007bff; font-weight: bold;">' + 
                          data.original_name + '</a> ' +
                          '<span style="color: #6c757d; font-size: 0.9em;">(' + data.size + ')</span>' +
                          '</div>';
                editor.insertContent(html);
            } else {
                alert('Erreur lors du téléchargement: ' + data.error);
            }
        })
        .catch(error => {
            editor.setProgressState(false);
            alert('Erreur lors du téléchargement: ' + error.message);
        });
    }
});
</script>

<!-- Styles CSS personnalisés pour l'éditeur -->
<style>
.tox-editor-container {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}

.tox-toolbar-overlord {
    background: #f8f9fa;
}

.rich-editor-container {
    margin: 15px 0;
}

.editor-help {
    background: #e7f3ff;
    border: 1px solid #b6d7ff;
    border-radius: 5px;
    padding: 15px;
    margin: 10px 0;
}

.editor-help h5 {
    color: #0066cc;
    margin: 0 0 10px 0;
}

.editor-help ul {
    margin: 0;
    padding-left: 20px;
}

.editor-help li {
    margin: 5px 0;
}
</style>

<?php require_once 'includes/footer.php'; ?> 