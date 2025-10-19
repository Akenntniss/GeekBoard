<?php
/**
 * Page d'administration des rapports de bugs
 * Permet de visualiser, trier et gérer les signalements des utilisateurs
 */

// Vérifier si l'utilisateur est connecté et a les droits d'admin
// Note : La session est déjà démarrée par index.php
if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Accès non autorisé. Veuillez vous connecter.</div>';
    return;
}

// Vérifier les droits administrateur (plusieurs variantes possibles)
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
            (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

if (!$is_admin) {
    echo '<div class="alert alert-warning">Accès réservé aux administrateurs.</div>';
    return;
}

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Connexion à la base de données
try {
    $shop_pdo = getShopDBConnection();
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Traitement des actions
if (isset($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    // Vérification de l'ID
    if ($id <= 0) {
        $error = "ID de rapport invalide";
    } else {
        switch ($_POST['action']) {
            case 'update_status':
                // Mise à jour du statut
                $status = isset($_POST['statut']) ? $_POST['statut'] : '';
                $valid_statuses = ['nouveau', 'en_cours', 'resolu', 'invalide'];
                
                if (in_array($status, $valid_statuses)) {
                    $query = "UPDATE bug_reports SET status = :status WHERE id = :id";
                    $stmt = $shop_pdo->prepare($query);
                    $stmt->execute([':status' => $status, ':id' => $id]);
                    $success = "Statut mis à jour avec succès";
                } else {
                    $error = "Statut invalide";
                }
                break;
                
            case 'update_priority':
                // Mise à jour de la priorité
                $priority = isset($_POST['priorite']) ? $_POST['priorite'] : '';
                $valid_priorities = ['basse', 'moyenne', 'haute', 'critique'];
                
                if (in_array($priority, $valid_priorities)) {
                    $query = "UPDATE bug_reports SET priorite = :priorite WHERE id = :id";
                    $stmt = $shop_pdo->prepare($query);
                    $stmt->execute([':priorite' => $priority, ':id' => $id]);
                    $success = "Priorité mise à jour avec succès";
                } else {
                    $error = "Priorité invalide";
                }
                break;
                
            case 'add_note':
                // Ajout d'une note
                $note = isset($_POST['note']) ? trim($_POST['note']) : '';
                
                if (!empty($note)) {
                    $query = "UPDATE bug_reports SET notes_admin = :note WHERE id = :id";
                    $stmt = $shop_pdo->prepare($query);
                    $stmt->execute([':note' => $note, ':id' => $id]);
                    $success = "Note ajoutée avec succès";
                } else {
                    $error = "Note vide";
                }
                break;
                
            case 'delete':
                // Suppression d'un rapport
                $query = "DELETE FROM bug_reports WHERE id = :id";
                $stmt = $shop_pdo->prepare($query);
                $stmt->execute([':id' => $id]);
                $success = "Rapport supprimé avec succès";
                break;
                
            default:
                $error = "Action non reconnue";
        }
    }
}

// Filtrage des rapports
$statut_filter = isset($_GET['statut']) ? $_GET['statut'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Construction de la requête avec filtres
$query = "SELECT * FROM bug_reports WHERE 1=1";
$params = [];

if (!empty($statut_filter)) {
    $query .= " AND status = :statut";
    $params[':statut'] = $statut_filter;
}

if (!empty($date_filter)) {
    $query .= " AND DATE(date_creation) = :date";
    $params[':date'] = $date_filter;
}

// Tri par défaut : les plus récents en premier
$query .= " ORDER BY date_creation DESC";

// Exécution de la requête
$stmt = $shop_pdo->prepare($query);
$stmt->execute($params);
$bug_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Note : Le header est inclus par index.php
?>

<!-- Loader Screen -->
<div id="pageLoader" class="loader">
    <!-- Loader Mode Sombre (par défaut) -->
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
    
    <!-- Loader Mode Clair -->
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

<!-- Interface moderne de gestion des bugs -->
<div class="bugs-container" id="mainContent" style="display: none;">
    <div class="bugs-header">
        <h1 class="bugs-title">
            <i class="fas fa-bug"></i>
            Gestion des rapports de bugs
        </h1>
        <div class="bugs-count"><?php echo count($bug_reports); ?> signalement(s)</div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="bugs-alert bugs-alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="bugs-alert bugs-alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <!-- Filtres modernes -->
    <div class="bugs-filters">
        <form method="GET" class="filters-form">
            <!-- Paramètre caché pour maintenir la page -->
            <input type="hidden" name="page" value="bug-reports">
            
            <div class="filter-group">
                <label for="statut">Statut</label>
                <select name="statut" id="statut" class="filter-select">
                        <option value="">Tous</option>
                        <option value="nouveau" <?php echo $statut_filter === 'nouveau' ? 'selected' : ''; ?>>Nouveau</option>
                        <option value="en_cours" <?php echo $statut_filter === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="resolu" <?php echo $statut_filter === 'resolu' ? 'selected' : ''; ?>>Résolu</option>
                        <option value="invalide" <?php echo $statut_filter === 'invalide' ? 'selected' : ''; ?>>Invalide</option>
                    </select>
                </div>
            
            <div class="filter-group">
                <label for="date">Date</label>
                <input type="date" name="date" id="date" class="filter-input" value="<?php echo $date_filter; ?>">
                </div>
            
            <div class="filter-actions">
                <button type="submit" class="filter-btn filter-btn-primary">
                    <i class="fas fa-filter"></i>
                    Filtrer
                </button>
                <a href="?page=bug-reports" class="filter-btn filter-btn-secondary">
                    <i class="fas fa-undo"></i>
                    Réinitialiser
                </a>
                </div>
            </form>
    </div>
    
    <!-- Liste moderne des bugs -->
    <div class="bugs-list">
            <?php if (empty($bug_reports)): ?>
            <div class="bugs-empty">
                <i class="fas fa-inbox"></i>
                <h3>Aucun rapport de bug trouvé</h3>
                <p>Il n'y a actuellement aucun signalement correspondant à vos critères.</p>
        </div>
            <?php else: ?>
                            <?php foreach ($bug_reports as $report): ?>
                <div class="bug-card" data-status="<?php echo $report['status']; ?>">
                    <div class="bug-card-header">
                        <div class="bug-id">#<?php echo $report['id']; ?></div>
                        <div class="bug-date">
                            <i class="fas fa-calendar-alt"></i>
                            <?php echo date('d/m/Y H:i', strtotime($report['date_creation'])); ?>
                                        </div>
                        <div class="bug-status bug-status-<?php echo $report['status']; ?>">
                            <?php 
                            $status_labels = [
                                'nouveau' => 'Nouveau',
                                'en_cours' => 'En cours', 
                                'resolu' => 'Résolu',
                                'invalide' => 'Invalide'
                            ];
                            echo $status_labels[$report['status']] ?? $report['status'];
                            ?>
                                                    </div>
                                                </div>
                                                
                    <div class="bug-card-content">
                        <div class="bug-description">
                            <?php echo nl2br(htmlspecialchars(substr($report['description'], 0, 200))); ?>
                            <?php if (strlen($report['description']) > 200): ?>
                                <span class="read-more">... <strong>Lire plus</strong></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="bug-page">
                            <i class="fas fa-link"></i>
                            <a href="<?php echo $report['page_url']; ?>" target="_blank" class="bug-page-link">
                                <?php echo parse_url($report['page_url'], PHP_URL_PATH) ?: $report['page_url']; ?>
                                            </a>
                                        </div>
                                                </div>
                                                
                    <div class="bug-card-actions">
                                        <button type="button" 
                            class="action-btn validation-btn <?php echo ($report['status'] === 'resolu') ? 'validated' : ''; ?>" 
                                            data-bug-id="<?php echo $report['id']; ?>" 
                            data-status="<?php echo $report['status']; ?>"
                            title="<?php echo ($report['status'] === 'resolu') ? 'Résolu' : 'Marquer comme résolu'; ?>">
                            <i class="fas fa-check"></i>
                                        </button>
                        
                        <button type="button" 
                            class="action-btn details-btn" 
                            onclick="openBugDetails(<?php echo $report['id']; ?>)"
                            title="Voir les détails">
                            <i class="fas fa-eye"></i>
                                            Détails
                                        </button>
                                            </div>
                                                    </div>
                            <?php endforeach; ?>
        <?php endif; ?>
                                                            </div>

    <!-- Modal moderne pour les détails -->
    <div id="bugDetailsModal" class="bugs-modal" style="display: none;">
        <div class="bugs-modal-overlay" onclick="closeBugDetails()"></div>
        <div class="bugs-modal-content">
            <div class="bugs-modal-header">
                <h3 id="modalTitle">Détails du rapport</h3>
                <button class="bugs-modal-close" onclick="closeBugDetails()">
                    <i class="fas fa-times"></i>
                </button>
                                                            </div>
            <div class="bugs-modal-body" id="modalBody">
                <!-- Contenu chargé dynamiquement -->
                                                    </div>
        </div>
    </div>
</div>

<?php 
// Note : Le footer est inclus par index.php, pas ici
?>

<style>
/* Interface moderne de gestion des bugs */
.bugs-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.bugs-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.bugs-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.bugs-title i {
    color: #e74c3c;
    font-size: 1.8rem;
}

.bugs-count {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Alertes modernes */
.bugs-alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
}

.bugs-alert-error {
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    color: white;
}

.bugs-alert-success {
    background: linear-gradient(135deg, #51cf66, #40c057);
    color: white;
}

/* Filtres modernes */
.bugs-filters {
    background: white;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
}

.filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 180px;
}

.filter-group label {
    font-weight: 600;
    color: #34495e;
    font-size: 0.9rem;
}

.filter-select, .filter-input {
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: white;
}

.filter-select:focus, .filter-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-actions {
    display: flex;
    gap: 12px;
}

/* Styles filter-btn commentés pour permettre les couleurs modern-filter personnalisées */
/*
.filter-btn {
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.filter-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.filter-btn-secondary {
    background: #f8f9fa;
    color: #6c757d;
    border: 2px solid #e9ecef;
}

.filter-btn-secondary:hover {
    background: #e9ecef;
    transform: translateY(-1px);
}
*/

/* Liste moderne des bugs */
.bugs-list {
    display: grid;
    gap: 20px;
}

.bugs-empty {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.bugs-empty i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.bugs-empty h3 {
    margin-bottom: 10px;
    color: #495057;
}

/* Cartes de bugs */
.bug-card {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.bug-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.bug-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.bug-id {
    font-weight: 700;
    font-size: 1.1rem;
    color: #2c3e50;
    background: #f8f9fa;
    padding: 6px 12px;
    border-radius: 8px;
}

.bug-date {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6c757d;
    font-size: 0.9rem;
}

.bug-status {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.bug-status-nouveau {
    background: linear-gradient(135deg, #ff9a9e, #fecfef);
    color: #d63384;
}

.bug-status-en_cours {
    background: linear-gradient(135deg, #a8edea, #fed6e3);
    color: #0dcaf0;
}

.bug-status-resolu {
    background: linear-gradient(135deg, #d4e157, #7cb342);
    color: #2e7d32;
}

.bug-status-invalide {
    background: linear-gradient(135deg, #ffcc02, #ff6b00);
    color: #f57c00;
}

.bug-card-content {
    margin-bottom: 20px;
}

.bug-description {
    color: #495057;
    line-height: 1.6;
    margin-bottom: 15px;
}

.read-more {
    color: #667eea;
    cursor: pointer;
}

.bug-page {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 0.9rem;
}

.bug-page i {
    color: #667eea;
}

.bug-page-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    word-break: break-all;
}

.bug-page-link:hover {
    text-decoration: underline;
}

.bug-card-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.action-btn {
    padding: 10px 16px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.validation-btn {
    background: #f8f9fa;
    color: #6c757d;
    border: 2px solid #e9ecef;
    min-width: 44px;
    justify-content: center;
}

.validation-btn:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.validation-btn.validated {
    background: linear-gradient(135deg, #51cf66, #40c057);
    color: white;
    border-color: #40c057;
}

.details-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.details-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

/* Modal moderne */
.bugs-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bugs-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(5px);
}

.bugs-modal-content {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow: hidden;
    position: relative;
    z-index: 1001;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
    opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.bugs-modal-header {
    padding: 25px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bugs-modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.bugs-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6c757d;
    cursor: pointer;
    padding: 5px;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.bugs-modal-close:hover {
    background: #f8f9fa;
    color: #495057;
}

.bugs-modal-body {
    padding: 25px;
    max-height: 60vh;
    overflow-y: auto;
}

/* Mode sombre pour les modaux */
body.dark-mode .bugs-modal-content {
    background: var(--dark-card-bg, #1f2937);
    color: var(--dark-text-primary, #f9fafb);
    border: 1px solid var(--dark-border-color, #374151);
}

body.dark-mode .bugs-modal-header {
    border-bottom-color: var(--dark-border-color, #374151);
}

body.dark-mode .bugs-modal-header h3 {
    color: var(--dark-text-primary, #f9fafb);
}

body.dark-mode .bugs-modal-close {
    color: var(--dark-text-secondary, #e5e7eb);
}

body.dark-mode .bugs-modal-close:hover {
    background: var(--dark-hover-bg, rgba(255, 255, 255, 0.05));
    color: var(--dark-text-primary, #f9fafb);
}

body.dark-mode .bugs-modal-body {
    color: var(--dark-text-primary, #f9fafb);
}

/* Mode sombre pour le contenu du modal */
body.dark-mode .bug-modal-info {
    background: var(--dark-bg-tertiary, #1e293b);
    border-color: var(--dark-border-color, #374151);
    color: var(--dark-text-primary, #f9fafb);
}

body.dark-mode .bug-modal-label {
    color: var(--dark-text-secondary, #e5e7eb);
}

body.dark-mode .bug-modal-value {
    color: var(--dark-text-primary, #f9fafb);
}

/* Mode sombre pour le contenu généré dynamiquement du modal de détails */
body.dark-mode .bugs-modal-body [style*="background: #f8f9fa"] {
    background: var(--dark-bg-tertiary, #1e293b) !important;
}

body.dark-mode .bugs-modal-body [style*="color: #2c3e50"] {
    color: var(--dark-text-primary, #f9fafb) !important;
}

body.dark-mode .bugs-modal-body [style*="color: #6c757d"] {
    color: var(--dark-text-secondary, #e5e7eb) !important;
}

body.dark-mode .bugs-modal-body [style*="background: #fff3cd"] {
    background: var(--dark-warning-bg, rgba(251, 191, 36, 0.15)) !important;
    border-left-color: var(--warning, #fbbf24) !important;
}

body.dark-mode .bugs-modal-body [style*="border-top: 1px solid #f0f0f0"] {
    border-top-color: var(--dark-border-color, #374151) !important;
}

body.dark-mode .bugs-modal-body h5 {
    color: var(--dark-text-primary, #f9fafb) !important;
}

body.dark-mode .bugs-modal-body p,
body.dark-mode .bugs-modal-body strong {
    color: var(--dark-text-primary, #f9fafb) !important;
}

body.dark-mode .bugs-modal-body small {
    color: var(--dark-text-secondary, #e5e7eb) !important;
}

body.dark-mode .bugs-modal-body a {
    color: var(--primary, #6282ff) !important;
}

body.dark-mode .bugs-modal-body a:hover {
    color: var(--primary-hover, #4361ee) !important;
}

/* Styles pour les boutons d'action dans le modal */
body.dark-mode .bugs-modal-body button[style*="background: #667eea"] {
    background: var(--primary, #6282ff) !important;
}

body.dark-mode .bugs-modal-body button[style*="background: #6c757d"] {
    background: var(--dark-hover-bg, rgba(255, 255, 255, 0.1)) !important;
    border: 1px solid var(--dark-border-color, #374151) !important;
}

body.dark-mode .bugs-modal-body button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Style pour le message d'erreur en mode sombre */
body.dark-mode .bugs-modal-body [style*="color: #dc3545"] {
    color: var(--danger, #f87171) !important;
}

body.dark-mode .bugs-modal-body h4 {
    color: var(--dark-text-primary, #f9fafb) !important;
}

/* Styles pour le message de chargement */
.modal-loading {
    text-align: center;
    padding: 40px;
    color: var(--text-muted, #6c757d);
}

.modal-loading i {
    font-size: 2rem;
    color: var(--primary, #667eea);
    margin-bottom: 15px;
}

body.dark-mode .modal-loading {
    color: var(--dark-text-secondary, #e5e7eb);
}

body.dark-mode .modal-loading i {
    color: var(--primary, #6282ff);
}

/* Responsive Design Avancé */
/* Tablettes et petits écrans */
@media (max-width: 1024px) {
    .bugs-container {
        padding: 20px;
        max-width: 100%;
    }
    
    .bugs-header {
        gap: 20px;
    }
    
    .bugs-title {
        font-size: 1.8rem;
    }
    
    .filters-form {
        gap: 15px;
    }
    
    .filter-group {
        min-width: 160px;
    }
    
    .bug-card {
        padding: 20px;
    }
}

/* Tablettes en mode portrait */
@media (max-width: 768px) {
    .bugs-container {
        padding: 15px;
    }
    
    .bugs-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
        margin-bottom: 25px;
    }
    
    .bugs-title {
        font-size: 1.6rem;
        flex-direction: column;
        gap: 10px;
    }
    
    .bugs-count {
        font-size: 0.85rem;
        padding: 6px 14px;
    }
    
    .bugs-filters {
        padding: 20px;
        margin-bottom: 25px;
    }
    
    .filters-form {
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-group {
        min-width: 100%;
    }
    
    .filter-actions {
        width: 100%;
        justify-content: center;
        gap: 10px;
    }
    
    .filter-btn {
        flex: 1;
        justify-content: center;
        min-width: 120px;
    }
    
    .bugs-list {
        gap: 15px;
    }
    
    .bug-card {
        padding: 18px;
    }
    
    .bug-card-header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
    
    .bug-card-actions {
        justify-content: center;
        gap: 10px;
    }
    
    .action-btn {
        padding: 8px 14px;
        font-size: 0.85rem;
    }
    
    .validation-btn {
        min-width: 40px;
    }
}

/* Mobiles en mode portrait */
@media (max-width: 480px) {
    .bugs-container {
        padding: 10px;
    }
    
    .bugs-header {
        margin-bottom: 20px;
        padding-bottom: 15px;
    }
    
    .bugs-title {
        font-size: 1.4rem;
    }
    
    .bugs-count {
        font-size: 0.8rem;
        padding: 5px 12px;
    }
    
    .bugs-filters {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 12px;
    }
    
    .filter-group label {
        font-size: 0.85rem;
    }
    
    .filter-select, .filter-input {
        padding: 10px 14px;
        font-size: 0.9rem;
    }
    
    .filter-btn {
        padding: 10px 16px;
        font-size: 0.85rem;
        min-width: 100px;
    }
    
    .bugs-list {
        gap: 12px;
    }
    
    .bug-card {
        padding: 15px;
        border-radius: 12px;
    }
    
    .bug-id {
        font-size: 1rem;
        padding: 5px 10px;
    }
    
    .bug-date {
        font-size: 0.8rem;
    }
    
    .bug-status {
        font-size: 0.75rem;
        padding: 4px 10px;
    }
    
    .bug-description {
        font-size: 0.9rem;
        line-height: 1.5;
    }
    
    .bug-page {
        padding: 10px;
        font-size: 0.8rem;
    }
    
    .bug-card-actions {
        flex-direction: column;
        gap: 8px;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
        padding: 10px;
    }
}

/* Très petits écrans (smartphones compacts) */
@media (max-width: 360px) {
    .bugs-container {
        padding: 8px;
    }
    
    .bugs-title {
        font-size: 1.3rem;
    }
    
    .bugs-filters {
        padding: 12px;
    }
    
    .filter-btn {
        padding: 8px 12px;
        font-size: 0.8rem;
    }
    
    .bug-card {
        padding: 12px;
    }
    
    .bug-card-header {
        gap: 8px;
    }
    
    .bug-id {
        font-size: 0.9rem;
        padding: 4px 8px;
    }
    
    .bug-status {
        font-size: 0.7rem;
        padding: 3px 8px;
    }
}

/* Mode paysage sur mobile */
@media (max-width: 768px) and (orientation: landscape) {
    .bugs-header {
        flex-direction: row;
        text-align: left;
    }
    
    .bugs-title {
        flex-direction: row;
    }
    
    .filters-form {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .filter-group {
        min-width: 140px;
    }
}

/* Écrans très larges */
@media (min-width: 1400px) {
    .bugs-container {
        max-width: 1400px;
        padding: 30px;
    }
    
    .bugs-title {
        font-size: 2.2rem;
    }
    
    .bugs-filters {
        padding: 30px;
    }
    
    .filter-group {
        min-width: 200px;
    }
    
    .bug-card {
        padding: 30px;
    }
    
    .bugs-list {
        gap: 25px;
    }
}

/* Support des écrans haute densité */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .bug-card {
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    }
    
    .bugs-filters {
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    }
}

/* Mode sombre automatique (si supporté) */
@media (prefers-color-scheme: dark) {
    .bugs-container {
        background: #1a1a1a;
        color: #ffffff;
    }
    
    .bug-card {
        background: #2d2d2d;
        border-color: #404040;
    }
    
    .bugs-filters {
        background: #2d2d2d;
        border-color: #404040;
    }
    
    .filter-select, .filter-input {
        background: #3d3d3d;
        border-color: #555;
        color: #ffffff;
    }
    
    .bug-page {
        background: #3d3d3d;
    }
}

/* Mode sombre avec classe (priorité sur le mode automatique) */
body.dark-mode .bugs-container {
    background: transparent;
    color: var(--dark-text-primary, #f9fafb);
}

body.dark-mode .bugs-header {
    border-bottom-color: var(--dark-border-color, #374151);
}

body.dark-mode .bugs-title {
    color: var(--dark-text-primary, #f9fafb);
}

body.dark-mode .bugs-title i {
    color: var(--danger, #f87171);
}

body.dark-mode .bug-card {
    background: var(--dark-card-bg, #1f2937);
    border-color: var(--dark-border-color, #374151);
    color: var(--dark-text-primary, #f9fafb);
}

body.dark-mode .bugs-filters {
    background: var(--dark-card-bg, #1f2937);
    border-color: var(--dark-border-color, #374151);
}

body.dark-mode .filter-select, 
body.dark-mode .filter-input {
    background: var(--dark-input-bg, #1f2937);
    border-color: var(--dark-border-color, #374151);
    color: var(--dark-text-primary, #f9fafb);
}

body.dark-mode .filter-select:focus,
body.dark-mode .filter-input:focus {
    border-color: var(--primary, #6282ff);
    box-shadow: 0 0 0 0.2rem rgba(98, 130, 255, 0.25);
}

body.dark-mode .bug-page {
    background: var(--dark-bg-tertiary, #1e293b);
    color: var(--dark-text-primary, #f9fafb);
}

body.dark-mode .bug-id {
    background: var(--primary, #6282ff);
    color: white;
}

body.dark-mode .bug-date {
    color: var(--dark-text-secondary, #e5e7eb);
}

body.dark-mode .bug-description {
    color: var(--dark-text-primary, #f9fafb);
}

body.dark-mode .filter-btn {
    background: var(--dark-hover-bg, rgba(255, 255, 255, 0.05));
    color: var(--dark-text-primary, #f9fafb);
    border-color: var(--dark-border-color, #374151);
}

body.dark-mode .filter-btn:hover {
    background: var(--dark-active-bg, rgba(255, 255, 255, 0.1));
    border-color: var(--primary, #6282ff);
}

body.dark-mode .validation-btn {
    background: var(--dark-hover-bg, rgba(255, 255, 255, 0.05));
    color: var(--dark-text-secondary, #e5e7eb);
    border-color: var(--dark-border-color, #374151);
}

body.dark-mode .validation-btn:hover {
    background: var(--dark-active-bg, rgba(255, 255, 255, 0.1));
}

/* Amélioration de l'accessibilité */
@media (prefers-reduced-motion: reduce) {
    .bug-card,
    .filter-btn,
    .action-btn {
        transition: none;
    }
    
    .bug-card:hover {
        transform: none;
    }
    
    .filter-btn:hover,
    .action-btn:hover {
        transform: none;
    }
}

/* Support des écrans tactiles */
@media (hover: none) and (pointer: coarse) {
    .bug-card:hover {
        transform: none;
    }
    
    .filter-btn:hover,
    .action-btn:hover {
        transform: none;
    }
    
    .action-btn {
        min-height: 44px; /* Taille minimale recommandée pour le tactile */
    }
    
    .filter-btn {
        min-height: 44px;
    }
}
</style>

<script>
// Interface moderne de gestion des bugs - JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Interface moderne des bugs initialisée');
    
    // Initialiser les boutons de validation
    initValidationButtons();
    
    // Initialiser les fonctions de modal
    initModalFunctions();
});

// Initialisation des boutons de validation
function initValidationButtons() {
    const validationButtons = document.querySelectorAll('.validation-btn');
    
    validationButtons.forEach(button => {
        // Initialiser l'état visuel
        if (button.getAttribute('data-status') === 'resolu') {
            button.classList.add('validated');
        }
        
        // Ajouter l'événement de clic
        button.addEventListener('click', function() {
            const bugId = this.getAttribute('data-bug-id');
            const currentStatus = this.getAttribute('data-status');
            const newStatus = currentStatus === 'resolu' ? 'nouveau' : 'resolu';
            
            // Animation de chargement
            this.style.transform = 'scale(0.95)';
            
            // Appel AJAX pour mettre à jour le statut
            updateBugStatus(bugId, newStatus, this);
        });
    });
}

// Fonction pour mettre à jour le statut d'un bug
async function updateBugStatus(bugId, newStatus, button) {
    try {
            const ajaxUrl = window.location.pathname.includes('/pages/') 
                ? '../ajax/update_bug_status.php' 
                : 'ajax/update_bug_status.php';
            
        const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${bugId}&status=${newStatus}`
        });
        
        const data = await response.json();
        
                if (data.success) {
            // Mettre à jour l'interface
            updateButtonState(button, newStatus);
            showNotification('Statut mis à jour avec succès', 'success');
        } else {
            throw new Error(data.message || 'Erreur lors de la mise à jour');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la mise à jour du statut', 'error');
    } finally {
        // Réinitialiser l'animation
        button.style.transform = '';
    }
}

// Mettre à jour l'état visuel du bouton
function updateButtonState(button, newStatus) {
    button.setAttribute('data-status', newStatus);
    
                    if (newStatus === 'resolu') {
        button.classList.add('validated');
        button.title = 'Résolu';
                    } else {
        button.classList.remove('validated');
        button.title = 'Marquer comme résolu';
    }
    
    // Animation de succès
    button.style.transform = 'scale(1.1)';
    setTimeout(() => {
        button.style.transform = '';
    }, 150);
}

// Initialiser les fonctions de modal
function initModalFunctions() {
    // Fonction globale pour ouvrir les détails
    window.openBugDetails = function(bugId) {
        console.log('📋 Ouverture des détails du bug #' + bugId);
        
        const modal = document.getElementById('bugDetailsModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        
        modalTitle.textContent = `Rapport #${bugId}`;
        modalBody.innerHTML = '<div class="modal-loading"><i class="fas fa-spinner fa-spin"></i><br><br>Chargement des détails...</div>';
        
        modal.style.display = 'flex';
        
        // Charger les détails du bug (ici vous pourriez faire un appel AJAX)
        loadBugDetails(bugId, modalBody);
    };
    
    // Fonction globale pour fermer les détails
    window.closeBugDetails = function() {
        const modal = document.getElementById('bugDetailsModal');
        modal.style.display = 'none';
    };
    
    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('bugDetailsModal');
            if (modal.style.display === 'flex') {
                closeBugDetails();
            }
        }
    });
}

// Charger les détails d'un bug
async function loadBugDetails(bugId, container) {
    try {
        // Appel AJAX pour récupérer les détails du bug
        const ajaxUrl = window.location.pathname.includes('/pages/') 
            ? '../ajax/get_bug_details.php' 
            : 'ajax/get_bug_details.php';
        
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${bugId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            const bug = data.bug;
            container.innerHTML = `
                <div style="line-height: 1.6;">
                    <div style="display: grid; gap: 20px;">
                        <!-- Informations principales -->
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <h5 style="margin: 0 0 10px 0; color: #2c3e50;">📋 Informations</h5>
                            <p><strong>Date:</strong> ${bug.date_creation}</p>
                            <p><strong>Statut:</strong> <span style="color: ${getStatusColor(bug.status)}; font-weight: bold;">${getStatusLabel(bug.status)}</span></p>
                            <p><strong>Priorité:</strong> ${bug.priorite || 'Non définie'}</p>
                            <p><strong>Page:</strong> <a href="${bug.page_url}" target="_blank" style="color: #667eea;">${bug.page_url}</a></p>
                        </div>
                        
                        <!-- Description -->
                        <div>
                            <h5 style="margin: 0 0 10px 0; color: #2c3e50;">📝 Description</h5>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; white-space: pre-wrap;">${bug.description}</div>
                        </div>
                        
                        <!-- Informations techniques -->
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <h5 style="margin: 0 0 10px 0; color: #2c3e50;">🔧 Informations techniques</h5>
                            <p><strong>User Agent:</strong></p>
                            <small style="word-break: break-all; color: #6c757d;">${bug.user_agent || 'Non disponible'}</small>
                        </div>
                        
                        <!-- Notes administratives -->
                        ${bug.notes_admin ? `
                        <div>
                            <h5 style="margin: 0 0 10px 0; color: #2c3e50;">📎 Notes administratives</h5>
                            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">${bug.notes_admin}</div>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Actions -->
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #f0f0f0; display: flex; gap: 10px; justify-content: flex-end;">
                        <button onclick="updateBugStatusModal(${bugId}, '${bug.status}')" style="background: #667eea; color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer;">
                            ${bug.status === 'resolu' ? 'Marquer non résolu' : 'Marquer résolu'}
                        </button>
                        <button onclick="closeBugDetails()" style="background: #6c757d; color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer;">
                            Fermer
                        </button>
                    </div>
                </div>
            `;
                } else {
            throw new Error(data.message || 'Erreur lors du chargement des détails');
        }
    } catch (error) {
        console.error('Erreur lors du chargement des détails:', error);
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #dc3545;">
                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                <h4>Erreur de chargement</h4>
                <p>Impossible de charger les détails du rapport.</p>
                <button onclick="closeBugDetails()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-top: 15px;">
                    Fermer
                </button>
            </div>
        `;
    }
}

// Fonctions utilitaires pour les status
function getStatusColor(status) {
    switch(status) {
        case 'nouveau': return '#d63384';
        case 'en_cours': return '#0dcaf0';
        case 'resolu': return '#2e7d32';
        case 'invalide': return '#f57c00';
        default: return '#6c757d';
    }
}

function getStatusLabel(status) {
    switch(status) {
        case 'nouveau': return 'Nouveau';
        case 'en_cours': return 'En cours';
        case 'resolu': return 'Résolu';
        case 'invalide': return 'Invalide';
        default: return status;
    }
}

// Fonction pour mettre à jour le statut depuis le modal
async function updateBugStatusModal(bugId, currentStatus) {
    const newStatus = currentStatus === 'resolu' ? 'nouveau' : 'resolu';
    
    try {
        const ajaxUrl = window.location.pathname.includes('/pages/') 
            ? '../ajax/update_bug_status.php' 
            : 'ajax/update_bug_status.php';
        
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${bugId}&status=${newStatus}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Statut mis à jour avec succès', 'success');
            closeBugDetails();
            // Recharger la page pour voir les changements
            window.location.reload();
                } else {
            throw new Error(data.message || 'Erreur lors de la mise à jour');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la mise à jour du statut', 'error');
    }
}

// Système de notifications
function showNotification(message, type = 'success') {
    // Supprimer les anciennes notifications
    const existingNotifications = document.querySelectorAll('.bugs-notification');
    existingNotifications.forEach(notif => notif.remove());
    
    // Créer la nouvelle notification
    const notification = document.createElement('div');
    notification.className = `bugs-notification bugs-notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Styles inline pour la notification
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        background: type === 'success' ? '#51cf66' : '#ff6b6b',
        color: 'white',
        padding: '15px 20px',
        borderRadius: '10px',
        display: 'flex',
        alignItems: 'center',
        gap: '10px',
        zIndex: '2000',
        boxShadow: '0 4px 20px rgba(0,0,0,0.15)',
        transform: 'translateX(100%)',
        transition: 'transform 0.3s ease'
    });
    
    document.body.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Suppression automatique
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

console.log('🎯 Scripts modernes pour la gestion des bugs chargés');
</script>

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

/* Masquer le loader quand la page est chargée */
.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

/* Afficher le contenu principal quand chargé */
#mainContent.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

/* Gestion des deux types de loaders */
.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

/* En mode clair, inverser l'affichage */
body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
  display: flex;
}

/* Loader Mode Clair - Cercle avec couleurs sombres */
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

/* Texte du loader mode clair */
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

/* Appliquer le fond du loader à la page - MODE JOUR ET NUIT */
body,
body.dark-mode,
body.light-mode,
html {
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

.bugs-container,
.bugs-container * {
  background: transparent !important;
}

/* Forcer le fond pour tous les éléments principaux */
.main-content,
.container-fluid,
.content-wrapper {
  background: transparent !important;
}

/* S'assurer que les cartes et éléments restent visibles */
.bug-card,
.bugs-modal-content,
.bugs-alert {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .bug-card,
.dark-mode .bugs-modal-content,
.dark-mode .bugs-alert {
  background: rgba(30, 41, 59, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    // Attendre 0,3 seconde puis masquer le loader et afficher le contenu
    setTimeout(function() {
        // Commencer l'animation de disparition du loader
        loader.classList.add('fade-out');
        
        // Après l'animation de disparition, masquer complètement le loader et afficher le contenu
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
        }, 500); // Durée de l'animation de disparition
        
    }, 300); // 0,3 seconde comme demandé
});
</script> 