<?php
// Interface de gestion de base de données pour le super administrateur
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

// Récupérer l'ID du magasin sélectionné
$shop_id = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;
$selected_table = isset($_GET['table']) ? $_GET['table'] : '';
$query_mode = isset($_GET['mode']) ? $_GET['mode'] : 'tables';

// Récupérer les informations du super administrateur
$main_pdo = getMainDBConnection();
$stmt = $main_pdo->prepare("SELECT * FROM superadmins WHERE id = ?");
$stmt->execute([$_SESSION['superadmin_id']]);
$superadmin = $stmt->fetch();

// Récupérer la liste des magasins
$shops = $main_pdo->query("SELECT * FROM shops ORDER BY name")->fetchAll();

// Variables pour stocker les données
$shop_info = null;
$shop_db = null;
$tables = [];
$table_data = [];
$query_result = null;
$error_message = '';
$success_message = '';

// Si un magasin est sélectionné
if ($shop_id > 0) {
    // Récupérer les informations du magasin
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop_info = $stmt->fetch();
    
    if ($shop_info) {
        // Connexion à la base de données du magasin
        $shop_config = [
            'host' => $shop_info['db_host'],
            'port' => $shop_info['db_port'],
            'dbname' => $shop_info['db_name'],
            'user' => $shop_info['db_user'],
            'pass' => $shop_info['db_pass']
        ];
        
        try {
            $shop_db = connectToShopDB($shop_config);
            
            if ($shop_db) {
                // Récupérer la liste des tables
                $result = $shop_db->query("SHOW TABLES");
                $tables = $result->fetchAll(PDO::FETCH_COLUMN);
                
                // Si une table est sélectionnée, récupérer ses données
                if ($selected_table && in_array($selected_table, $tables)) {
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $limit = 50;
                    $offset = ($page - 1) * $limit;
                    
                    // Compter le nombre total de lignes
                    $count_stmt = $shop_db->prepare("SELECT COUNT(*) FROM `$selected_table`");
                    $count_stmt->execute();
                    $total_rows = $count_stmt->fetchColumn();
                    
                    // Récupérer les données paginées
                    $data_stmt = $shop_db->prepare("SELECT * FROM `$selected_table` LIMIT $limit OFFSET $offset");
                    $data_stmt->execute();
                    $table_data = $data_stmt->fetchAll();
                    
                    // Récupérer la structure de la table
                    $structure_stmt = $shop_db->prepare("DESCRIBE `$selected_table`");
                    $structure_stmt->execute();
                    $table_structure = $structure_stmt->fetchAll();
                }
            }
        } catch (PDOException $e) {
            $error_message = "Erreur de connexion : " . $e->getMessage();
        }
    }
}

// Traitement des requêtes SQL personnalisées
if (isset($_POST['execute_query']) && $shop_db) {
    $sql_query = trim($_POST['sql_query']);
    
    if (!empty($sql_query)) {
        try {
            // Vérifier que la requête n'est pas dangereuse
            $dangerous_keywords = ['DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE', 'INSERT', 'UPDATE'];
            $is_dangerous = false;
            
            foreach ($dangerous_keywords as $keyword) {
                if (stripos($sql_query, $keyword) !== false) {
                    $is_dangerous = true;
                    break;
                }
            }
            
            if ($is_dangerous && !isset($_POST['confirm_dangerous'])) {
                $error_message = "Cette requête contient des mots-clés potentiellement dangereux. Cochez la case de confirmation pour l'exécuter.";
            } else {
                $stmt = $shop_db->prepare($sql_query);
                $stmt->execute();
                
                if (stripos($sql_query, 'SELECT') === 0) {
                    $query_result = $stmt->fetchAll();
                    $success_message = "Requête exécutée avec succès. " . count($query_result) . " résultat(s) trouvé(s).";
                } else {
                    $affected_rows = $stmt->rowCount();
                    $success_message = "Requête exécutée avec succès. $affected_rows ligne(s) affectée(s).";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'exécution de la requête : " . $e->getMessage();
        }
    }
}

// Export de données
if (isset($_GET['export']) && $selected_table && $shop_db) {
    $export_format = $_GET['export'];
    
    try {
        $stmt = $shop_db->prepare("SELECT * FROM `$selected_table`");
        $stmt->execute();
        $export_data = $stmt->fetchAll();
        
        if ($export_format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $selected_table . '_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            if (!empty($export_data)) {
                // En-têtes
                fputcsv($output, array_keys($export_data[0]));
                
                // Données
                foreach ($export_data as $row) {
                    fputcsv($output, $row);
                }
            }
            
            fclose($output);
            exit;
        }
    } catch (PDOException $e) {
        $error_message = "Erreur lors de l'export : " . $e->getMessage();
    }
}

$page_title = 'GeekBoard - Gestionnaire de Base de Données';
$page_heading = 'Gestionnaire de Base de Données';
$page_subtitle = 'Administration des données des magasins';
$extra_head_html = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">\n<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">';
include __DIR__ . '/includes/header.php';
?>
            <div class="action-buttons">
                <a href="index.php" class="btn-action btn-secondary">
                    <i class="fas fa-arrow-left"></i>Retour à l'accueil
                </a>
                <a href="create_shop.php" class="btn-action">
                    <i class="fas fa-plus-circle"></i>Nouveau magasin
                </a>
                <a href="configure_domains.php" class="btn-action btn-secondary">
                    <i class="fas fa-globe"></i>Configuration domaines
                </a>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="database-container">
                <div class="sidebar">
                    <div class="shop-selector">
                        <label><i class="fas fa-store me-2"></i>Sélectionner un magasin</label>
                        <form method="get" action="">
                            <select name="shop_id" class="form-select" onchange="this.form.submit()">
                                <option value="0">-- Choisir un magasin --</option>
                                <?php foreach ($shops as $shop): ?>
                                    <option value="<?php echo $shop['id']; ?>" <?php echo $shop_id == $shop['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($shop['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    
                    <?php if ($shop_id > 0 && $shop_info): ?>
                        <div class="shop-info-card" style="background: rgba(102, 126, 234, 0.1); border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                            <h5 style="color: #667eea; margin-bottom: 10px; font-size: 1.1rem;">
                                <i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($shop_info['name']); ?>
                            </h5>
                            <div style="font-size: 0.9rem; color: #666;">
                                <div><strong>Base :</strong> <?php echo htmlspecialchars($shop_info['db_name']); ?></div>
                                <div><strong>Host :</strong> <?php echo htmlspecialchars($shop_info['db_host']); ?></div>
                                <div><strong>Tables :</strong> <?php echo count($tables); ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($tables)): ?>
                            <div class="tables-section">
                                <h3><i class="fas fa-table"></i>Tables (<?php echo count($tables); ?>)</h3>
                                
                                <!-- Recherche dans les tables -->
                                <div class="table-search-container" style="margin-bottom: 15px;">
                                    <div style="position: relative;">
                                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #667eea; font-size: 1rem;"></i>
                                        <input type="text" id="table-search" placeholder="Rechercher une table..." 
                                               style="width: 100%; padding: 10px 15px 10px 35px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; transition: all 0.3s ease; background: white;">
                                    </div>
                                </div>
                                
                                <div class="table-list">
                                    <?php foreach ($tables as $table): ?>
                                        <div class="table-item <?php echo $selected_table === $table ? 'active' : ''; ?>" 
                                             onclick="selectTable('<?php echo htmlspecialchars($table); ?>')">
                                            <i class="fas fa-table"></i>
                                            <span><?php echo htmlspecialchars($table); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="main-content">
                    <?php if ($shop_id == 0): ?>
                        <div class="no-shop-selected">
                            <i class="fas fa-database"></i>
                            <h3>Aucun magasin sélectionné</h3>
                            <p>Veuillez sélectionner un magasin dans la liste de gauche pour accéder à sa base de données.</p>
                        </div>
                    <?php elseif (!$shop_info): ?>
                        <div class="no-shop-selected">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Magasin introuvable</h3>
                            <p>Le magasin sélectionné n'existe pas ou a été supprimé.</p>
                        </div>
                    <?php elseif (empty($tables)): ?>
                        <div class="no-shop-selected">
                            <i class="fas fa-database"></i>
                            <h3>Aucune table trouvée</h3>
                            <p>La base de données de ce magasin ne contient aucune table ou la connexion a échoué.</p>
                        </div>
                    <?php else: ?>
                        <!-- Éditeur SQL -->
                        <div class="sql-editor-section">
                            <h4><i class="fas fa-code"></i>Éditeur SQL</h4>
                            <form method="post">
                                <textarea name="sql_query" class="form-control sql-editor" placeholder="Tapez votre requête SQL ici... (ex: SELECT * FROM users LIMIT 10)"><?php echo htmlspecialchars($_POST['sql_query'] ?? ''); ?></textarea>
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="confirm_dangerous" id="confirm_dangerous">
                                    <label class="form-check-label" for="confirm_dangerous">
                                        <small>J'autorise les requêtes potentiellement dangereuses (INSERT, UPDATE, DELETE, DROP, etc.)</small>
                                    </label>
                                </div>
                                <button type="submit" name="execute_query" class="btn btn-execute mt-3">
                                    <i class="fas fa-play me-2"></i>Exécuter la requête
                                </button>
                            </form>
                        </div>
                        
                        <!-- Résultats de requête personnalisée -->
                        <?php if (isset($query_result)): ?>
                            <div class="table-data-section" id="query-result-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4><i class="fas fa-search"></i>Résultats de la requête (<?php echo count($query_result); ?> lignes)</h4>
                                    <div class="export-buttons">
                                        <button onclick="toggleQueryFullscreen()" class="btn-export me-2" id="query-fullscreen-btn">
                                            <i class="fas fa-expand"></i> Plein écran
                                        </button>
                                    </div>
                                </div>
                                <?php if (!empty($query_result)): ?>
                                    <!-- Recherche dans les résultats -->
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div style="position: relative; max-width: 400px;">
                                            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #667eea; font-size: 1rem;"></i>
                                            <input type="text" id="query-search" placeholder="Rechercher dans les résultats..." 
                                                   style="width: 100%; padding: 10px 15px 10px 35px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; transition: all 0.3s ease; background: white;">
                                        </div>
                                        <div style="font-size: 0.9rem; color: #666;">
                                            <span id="query-visible-rows"><?php echo count($query_result); ?></span> lignes visibles
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive" id="query-table-container">
                                        <table class="table table-striped" id="query-table">
                                            <thead>
                                                <tr>
                                                    <?php foreach (array_keys($query_result[0]) as $index => $column): ?>
                                                        <th data-column="<?php echo $index; ?>" style="cursor: pointer;">
                                                            <?php echo htmlspecialchars($column); ?>
                                                            <i class="fas fa-sort text-muted ms-1"></i>
                                                        </th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($query_result, 0, 100) as $rowIndex => $row): ?>
                                                    <tr data-row="<?php echo $rowIndex; ?>">
                                                        <?php foreach ($row as $column => $value): ?>
                                                            <?php 
                                                            $valueStr = $value ?? '';
                                                            $isLongText = strlen($valueStr) > 50;
                                                            $isShortText = strlen($valueStr) <= 10;
                                                            $cellClass = $isLongText ? 'long-text' : ($isShortText ? 'short-text' : '');
                                                            $displayValue = $isLongText ? substr($valueStr, 0, 100) . '...' : $valueStr;
                                                            ?>
                                                            <td class="<?php echo $cellClass; ?>" 
                                                                <?php if ($isLongText): ?>
                                                                title="<?php echo htmlspecialchars($valueStr); ?>"
                                                                style="cursor: help;"
                                                                <?php endif; ?>>
                                                                <?php echo htmlspecialchars($displayValue); ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="text-muted">
                                            <?php if (count($query_result) > 100): ?>
                                                <small>Affichage des 100 premiers résultats sur <?php echo count($query_result); ?> total.</small>
                                            <?php else: ?>
                                                <small>Total : <?php echo count($query_result); ?> ligne(s)</small>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle"></i> 
                                                Utilisez la molette pour défiler horizontalement • Cliquez sur les colonnes pour trier
                                            </small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Aucun résultat trouvé.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Données de table sélectionnée -->
                        <?php if ($selected_table && !empty($table_data)): ?>
                            <div class="table-data-section" id="table-data-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4><i class="fas fa-table"></i>Table : <?php echo htmlspecialchars($selected_table); ?> (<?php echo count($table_data); ?> lignes)</h4>
                                    <div class="export-buttons">
                                        <button onclick="toggleFullscreen()" class="btn-export me-2" id="fullscreen-btn">
                                            <i class="fas fa-expand"></i> Plein écran
                                        </button>
                                        <a href="?shop_id=<?php echo $shop_id; ?>&table=<?php echo urlencode($selected_table); ?>&export=csv" class="btn-export">
                                            <i class="fas fa-download me-1"></i>Export CSV
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Recherche dans les données -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div style="position: relative; max-width: 400px;">
                                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #667eea; font-size: 1rem;"></i>
                                        <input type="text" id="data-search" placeholder="Rechercher dans les données..." 
                                               style="width: 100%; padding: 10px 15px 10px 35px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; transition: all 0.3s ease; background: white;">
                                    </div>
                                    <div style="font-size: 0.9rem; color: #666;">
                                        <span id="visible-rows"><?php echo count($table_data); ?></span> lignes visibles
                                    </div>
                                </div>
                                
                                <div class="table-responsive" id="table-container">
                                    <table class="table table-striped" id="data-table">
                                        <thead>
                                            <tr>
                                                <?php foreach (array_keys($table_data[0]) as $index => $column): ?>
                                                    <th data-column="<?php echo $index; ?>" style="cursor: pointer;">
                                                        <?php echo htmlspecialchars($column); ?>
                                                        <i class="fas fa-sort text-muted ms-1"></i>
                                                    </th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($table_data as $rowIndex => $row): ?>
                                                <tr data-row="<?php echo $rowIndex; ?>">
                                                    <?php foreach ($row as $column => $value): ?>
                                                        <?php 
                                                        $valueStr = $value ?? '';
                                                        $isLongText = strlen($valueStr) > 50;
                                                        $isShortText = strlen($valueStr) <= 10;
                                                        $cellClass = $isLongText ? 'long-text' : ($isShortText ? 'short-text' : '');
                                                        $displayValue = $isLongText ? substr($valueStr, 0, 100) . '...' : $valueStr;
                                                        ?>
                                                        <td class="<?php echo $cellClass; ?>" 
                                                            <?php if ($isLongText): ?>
                                                            title="<?php echo htmlspecialchars($valueStr); ?>"
                                                            style="cursor: help;"
                                                            <?php endif; ?>>
                                                            <?php echo htmlspecialchars($displayValue); ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted">
                                        <?php if (isset($total_rows) && $total_rows > 50): ?>
                                            <small>Affichage de 50 résultats sur <?php echo $total_rows; ?> total.</small>
                                        <?php else: ?>
                                            <small>Total : <?php echo count($table_data); ?> ligne(s)</small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            Utilisez la molette pour défiler horizontalement • Cliquez sur les colonnes pour trier
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($selected_table): ?>
                            <div class="no-shop-selected">
                                <i class="fas fa-table"></i>
                                <h3>Table vide</h3>
                                <p>La table "<?php echo htmlspecialchars($selected_table); ?>" ne contient aucune donnée.</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php
$extra_footer_html = <<<HTML
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/sql/sql.min.js"></script>
<script>
(function(){
  document.addEventListener("DOMContentLoaded",function(){
    var sqlTextarea=document.querySelector('textarea[name="sql_query"]');
    if(sqlTextarea && window.CodeMirror){
      var editor=CodeMirror.fromTextArea(sqlTextarea,{mode:'sql',theme:'default',lineNumbers:true,lineWrapping:true,indentUnit:2,tabSize:2});
      editor.setSize(null,'150px');
    }
  });
})();
function selectTable(t){var e=new URL(window.location);e.searchParams.set('table',t),window.location.href=e.toString()}
var tableSearch=document.getElementById('table-search');tableSearch&&tableSearch.addEventListener('input',function(t){var e=t.target.value.toLowerCase().trim();document.querySelectorAll('.table-item').forEach(function(t){var a=(t.querySelector('span')||{}).textContent||'';a=a.toLowerCase(),t.style.display=''===e||a.includes(e)?'flex':'none'})});
var dataSearch=document.getElementById('data-search');dataSearch&&dataSearch.addEventListener('input',function(t){var e=t.target.value.toLowerCase().trim(),a=document.querySelectorAll('.table-data-section tbody tr'),n=0;a.forEach(function(t){var a=t.textContent.toLowerCase();''===e||a.includes(e)?(t.style.display='',t.classList.remove('hidden'),n++):(t.style.display='none',t.classList.add('hidden'))});var o=document.getElementById('visible-rows');o&&(o.textContent=n)});
var sortableHeaders=document.querySelectorAll('.table th[data-column]'),currentSort={column:-1,direction:'asc'},currentQuerySort={column:-1,direction:'asc'};sortableHeaders.forEach(function(t){t.addEventListener('click',function(){var e=parseInt(this.dataset.column),a=null!==this.closest('#query-table'),n=a?'query-table':'data-table',o=document.getElementById(n),d=o.querySelector('tbody'),r=Array.from(d.querySelectorAll('tr')),l=a?currentQuerySort:currentSort;l.column===e?l.direction='asc'===l.direction?'desc':'asc':(l.direction='asc',l.column=e),o.querySelectorAll('th[data-column]').forEach(function(t){var e=t.querySelector('i');e&&(e.className='fas fa-sort text-muted ms-1')});var c=this.querySelector('i');c&&(c.className='asc'===l.direction?'fas fa-sort-up ms-1':'fas fa-sort-down ms-1'),r.sort(function(t,a){var n=t.children[e].textContent.trim(),o=a.children[e].textContent.trim(),d=parseFloat(n),r=parseFloat(o);return isNaN(d)||isNaN(r)?'asc'===l.direction?n.localeCompare(o):o.localeCompare(n):'asc'===l.direction?d-r:r-d}),r.forEach(function(t){return d.appendChild(t)})})});
window.toggleFullscreen=function(){var t=document.getElementById('table-data-section'),e=document.getElementById('fullscreen-btn');t&&e&&(t.classList.contains('fullscreen-mode')?(t.classList.remove('fullscreen-mode'),e.innerHTML='<i class="fas fa-expand"></i> Plein écran',document.body.style.overflow=''):(t.classList.add('fullscreen-mode'),e.innerHTML='<i class="fas fa-compress"></i> Quitter',document.body.style.overflow='hidden'))};
window.toggleQueryFullscreen=function(){var t=document.getElementById('query-result-section'),e=document.getElementById('query-fullscreen-btn');t&&e&&(t.classList.contains('fullscreen-mode')?(t.classList.remove('fullscreen-mode'),e.innerHTML='<i class="fas fa-expand"></i> Plein écran',document.body.style.overflow=''):(t.classList.add('fullscreen-mode'),e.innerHTML='<i class="fas fa-compress"></i> Quitter',document.body.style.overflow='hidden'))};
var querySearch=document.getElementById('query-search');querySearch&&querySearch.addEventListener('input',function(t){var e=t.target.value.toLowerCase().trim(),a=document.querySelectorAll('#query-table tbody tr'),n=0;a.forEach(function(t){var a=t.textContent.toLowerCase();''===e||a.includes(e)?(t.style.display='',t.classList.remove('hidden'),n++):(t.style.display='none',t.classList.add('hidden'))});var o=document.getElementById('query-visible-rows');o&&(o.textContent=n)});
document.addEventListener('keydown',function(t){if('Escape'===t.key){var e=document.getElementById('table-data-section'),a=document.getElementById('query-result-section');e&&e.classList.contains('fullscreen-mode')?toggleFullscreen():a&&a.classList.contains('fullscreen-mode')&&toggleQueryFullscreen()}});
document.querySelectorAll('#table-container, #query-table-container').forEach(function(t){t&&t.addEventListener('wheel',function(t){t.shiftKey&&(t.preventDefault(),this.scrollLeft+=t.deltaY)})});
</script>
HTML;
include __DIR__ . '/includes/footer.php';
?>
