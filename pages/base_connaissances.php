<?php
// Page principale de la base de connaissances
$page_title = "Base de Connaissances";
require_once 'includes/header.php';

// Récupération de la catégorie sélectionnée (si présente)
$categorie_id = isset($_GET['categorie']) ? intval($_GET['categorie']) : 0;

// Récupération du terme de recherche (si présent)
$recherche = isset($_GET['recherche']) ? cleanInput($_GET['recherche']) : '';

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

// Récupération des articles
function get_kb_articles($categorie_id = 0, $recherche = '', $limit = 50) {
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
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

// Récupération des catégories et des articles
$categories = get_kb_categories();
$articles = get_kb_articles($categorie_id, $recherche);
?>

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <div class="loader-wrapper dark-loader">
        <div class="loader-circle"></div>
        <div class="loader-text">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
    <div class="loader-wrapper light-loader">
        <div class="loader-circle-light"></div>
        <div class="loader-text-light">
            <span class="loader-letter">S</span>
            <span class="loader-letter">E</span>
            <span class="loader-letter">R</span>
            <span class="loader-letter">V</span>
            <span class="loader-letter">O</span>
        </div>
    </div>
</div>

<div class="container-fluid pt-4" id="mainContent" style="display: none;">
    <div class="row">
        <div class="col-lg-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-book me-2"></i> Base de Connaissances
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Barre de recherche -->
                    <div class="row mb-4">
                        <div class="col-md-6 offset-md-3">
                            <form action="index.php" method="GET" class="mb-0">
                                <input type="hidden" name="page" value="base_connaissances">
                                <?php if ($categorie_id > 0): ?>
                                <input type="hidden" name="categorie" value="<?= $categorie_id ?>">
                                <?php endif; ?>
                                <div class="input-group">
                                    <input type="text" name="recherche" class="form-control" 
                                           placeholder="Rechercher dans la base de connaissances..." 
                                           value="<?= htmlspecialchars($recherche) ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i> Rechercher
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Sidebar (Catégories) -->
                        <div class="col-md-3">
                            <div class="card border-light mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Catégories</h5>
                                </div>
                                <div class="list-group list-group-flush">
                                    <a href="index.php?page=base_connaissances<?= !empty($recherche) ? '&recherche='.urlencode($recherche) : '' ?>" 
                                       class="list-group-item list-group-item-action <?= $categorie_id === 0 ? 'active' : '' ?>">
                                        <i class="fas fa-folder me-2"></i> Toutes les catégories
                                    </a>
                                    
                                    <?php foreach ($categories as $categorie): ?>
                                    <a href="index.php?page=base_connaissances&categorie=<?= $categorie['id'] ?><?= !empty($recherche) ? '&recherche='.urlencode($recherche) : '' ?>" 
                                       class="list-group-item list-group-item-action <?= $categorie_id === (int)$categorie['id'] ? 'active' : '' ?>">
                                        <i class="<?= htmlspecialchars($categorie['icon']) ?> me-2"></i> 
                                        <?= htmlspecialchars($categorie['name']) ?>
                                    </a>
                                    <?php endforeach; ?>
                                    
                                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')): ?>
                                    <a href="index.php?page=gestion_kb" class="list-group-item list-group-item-action text-primary">
                                        <i class="fas fa-cog me-2"></i> Gérer les catégories
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <a href="index.php?page=ajouter_article_kb" class="btn btn-success">
                                    <i class="fas fa-plus-circle me-2"></i> Créer un article
                                </a>
                            </div>
                        </div>
                        
                        <!-- Liste d'articles -->
                        <div class="col-md-9">
                            <?php if (!empty($recherche)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-search me-2"></i>
                                Résultats de recherche pour : <strong><?= htmlspecialchars($recherche) ?></strong>
                                <a href="index.php?page=base_connaissances<?= $categorie_id > 0 ? '&categorie='.$categorie_id : '' ?>" class="float-end">
                                    <i class="fas fa-times"></i> Effacer la recherche
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (empty($articles)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Aucun article trouvé dans la base de connaissances.
                                <?php if (!empty($recherche)): ?>
                                <div class="mt-2">
                                    Essayez avec d'autres termes de recherche ou
                                    <a href="index.php?page=base_connaissances<?= $categorie_id > 0 ? '&categorie='.$categorie_id : '' ?>">
                                        consultez tous les articles
                                    </a>.
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                                
                            <div class="row">
                                <?php foreach ($articles as $article): 
                                    // Récupérer les tags pour cet article
                                    $tags = get_article_tags($article['id']);
                                    
                                    // Calculer le taux d'utilité si des évaluations existent
                                    $utilite = 0;
                                    if ($article['rating_count'] > 0) {
                                        $utilite = round(($article['helpful_count'] / $article['rating_count']) * 100);
                                    }
                                ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 border-light hover-shadow">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="<?= htmlspecialchars($article['category_icon']) ?> me-2 text-primary"></i>
                                                <span class="text-muted small"><?= htmlspecialchars($article['category_name']) ?></span>
                                            </span>
                                            <?php if ($article['rating_count'] > 0): ?>
                                            <span class="badge bg-<?= $utilite >= 70 ? 'success' : ($utilite >= 40 ? 'warning' : 'danger') ?>" 
                                                  title="<?= $article['helpful_count'] ?> sur <?= $article['rating_count'] ?> utilisateurs ont trouvé cet article utile">
                                                <i class="fas fa-thumbs-up me-1"></i> <?= $utilite ?>%
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="index.php?page=article_kb&id=<?= $article['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($article['title']) ?>
                                                </a>
                                            </h5>
                                            <p class="card-text text-muted">
                                                <?= nl2br(htmlspecialchars(mb_substr(strip_tags($article['content']), 0, 150))) ?>...
                                            </p>
                                            
                                            <?php if (!empty($tags)): ?>
                                            <div class="mt-2">
                                                <?php foreach ($tags as $tag): ?>
                                                <a href="index.php?page=base_connaissances&recherche=<?= urlencode($tag['name']) ?>" 
                                                   class="badge bg-light text-dark text-decoration-none me-1">
                                                    <i class="fas fa-tag me-1 text-secondary"></i>
                                                    <?= htmlspecialchars($tag['name']) ?>
                                                </a>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer bg-white">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-eye me-1"></i> <?= $article['views'] ?> vues
                                                </small>
                                                <small class="text-muted">
                                                    Mis à jour le <?= date('d/m/Y', strtotime($article['updated_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles spécifiques -->
<style>
.hover-shadow:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: box-shadow 0.3s ease-in-out;
}
</style>

</div> <!-- Fermeture de mainContent -->

<style>
.loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000);
}

.loader-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 180px;
  height: 180px;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: white;
  border-radius: 50%;
  background-color: transparent;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

.loader-circle {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined 2.3s linear infinite;
  z-index: 0;
}
@keyframes loader-combined {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #38bdf8 inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #0099ff inset,
      0 12px 18px 0 #38bdf8 inset,
      0 36px 36px 0 #005dff inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #60a5fa inset,
      0 12px 6px 0 #0284c7 inset,
      0 24px 36px 0 #005dff inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #0ea5e9 inset,
      0 36px 36px 0 #2563eb inset,
      0 0 6px 2.4px rgba(56, 189, 248, 0.3),
      0 0 12px 3.6px rgba(0, 93, 255, 0.2),
      0 0 18px 6px rgba(30, 64, 175, 0.15);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #4dc8fd inset,
      0 12px 18px 0 #005dff inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(56, 189, 248, 0.3),
      0 0 6px 1.8px rgba(0, 93, 255, 0.2);
  }
}

.loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim 2.4s infinite;
  z-index: 1;
  border-radius: 50ch;
  border: none;
}

.loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #f8fcff 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

#mainContent.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
  display: flex;
}

.loader-circle-light {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background-color: transparent;
  animation: loader-combined-light 2.3s linear infinite;
  z-index: 0;
}

@keyframes loader-combined-light {
  0% {
    transform: rotate(90deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #3b82f6 inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  25% {
    transform: rotate(180deg);
    box-shadow:
      0 6px 12px 0 #2563eb inset,
      0 12px 18px 0 #1e40af inset,
      0 36px 36px 0 #3b82f6 inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  50% {
    transform: rotate(270deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 6px 0 #1d4ed8 inset,
      0 24px 36px 0 #2563eb inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
  75% {
    transform: rotate(360deg);
    box-shadow:
      0 6px 12px 0 #1e40af inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #60a5fa inset,
      0 0 6px 2.4px rgba(30, 64, 175, 0.4),
      0 0 12px 3.6px rgba(59, 130, 246, 0.3),
      0 0 18px 6px rgba(96, 165, 250, 0.2);
  }
  100% {
    transform: rotate(450deg);
    box-shadow:
      0 6px 12px 0 #3b82f6 inset,
      0 12px 18px 0 #2563eb inset,
      0 36px 36px 0 #1e40af inset,
      0 0 3px 1.2px rgba(30, 64, 175, 0.4),
      0 0 6px 1.8px rgba(59, 130, 246, 0.3);
  }
}

.loader-text-light {
  display: flex;
  gap: 2px;
  z-index: 1;
}

.loader-text-light .loader-letter {
  display: inline-block;
  opacity: 0.4;
  transform: translateY(0);
  animation: loader-letter-anim-light 2.4s infinite;
  z-index: 1;
  font-family: "Inter", sans-serif;
  font-size: 1.1em;
  font-weight: 300;
  color: #1f2937;
  border-radius: 50ch;
  border: none;
}

.loader-text-light .loader-letter:nth-child(1) {
  animation-delay: 0s;
}
.loader-text-light .loader-letter:nth-child(2) {
  animation-delay: 0.1s;
}
.loader-text-light .loader-letter:nth-child(3) {
  animation-delay: 0.2s;
}
.loader-text-light .loader-letter:nth-child(4) {
  animation-delay: 0.3s;
}
.loader-text-light .loader-letter:nth-child(5) {
  animation-delay: 0.4s;
}

@keyframes loader-letter-anim-light {
  0%,
  100% {
    opacity: 0.4;
    transform: translateY(0);
  }
  20% {
    opacity: 1;
    text-shadow: #1e40af 0 0 5px;
  }
  40% {
    opacity: 0.7;
    transform: translateY(0);
  }
}

body,
body.dark-mode,
body.light-mode,
html {
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

.container-fluid,
.container-fluid * {
  background: transparent !important;
}

.card,
.modal-content,
.kb-card {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .card,
.dark-mode .modal-content,
.dark-mode .kb-card {
  background: rgba(30, 41, 59, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    setTimeout(function() {
        loader.classList.add('fade-out');
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
        }, 500);
    }, 300);
});
</script>

<?php require_once 'includes/footer.php'; ?> 