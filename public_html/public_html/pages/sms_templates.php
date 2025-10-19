<?php
// Vérification des droits de base
// Permettre l'accès sans authentification si on vient de template_sms
$allow_no_auth = (isset($_GET['page']) && $_GET['page'] === 'template_sms') || 
                 (isset($_SESSION['allow_template_sms_access']) && $_SESSION['allow_template_sms_access'] === true);

error_log("SMS_TEMPLATES: user_id=" . ($_SESSION['user_id'] ?? 'non défini') . 
          ", page=" . ($_GET['page'] ?? 'non défini') . 
          ", allow_template_sms_access=" . ($_SESSION['allow_template_sms_access'] ?? 'non défini') . 
          ", allow_no_auth=" . ($allow_no_auth ? 'true' : 'false'));

if (!isset($_SESSION['user_id']) && !$allow_no_auth) {
    error_log("SMS_TEMPLATES: Accès refusé - redirection vers page vide");
    set_message("Vous devez être connecté pour accéder à cette page.", "danger");
    redirect("");
    exit;
} else {
    error_log("SMS_TEMPLATES: Accès autorisé");
}

// Variable pour déterminer le niveau d'accès
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Traitement des actions (réservé aux administrateurs)
if (isset($_POST['action'])) {
    // Vérifier que l'utilisateur est admin pour les actions de modification
    if (!$is_admin) {
        set_message("Vous n'avez pas les droits nécessaires pour modifier les modèles de SMS.", "danger");
        redirect("sms_templates");
        exit;
    }
    
    $action = $_POST['action'];
    
    // Traitement de l'ajout ou modification de template
    if ($action === 'save_template') {
        $template_id = isset($_POST['template_id']) ? (int)$_POST['template_id'] : 0;
        $nom = clean_input($_POST['nom']);
        $contenu = $_POST['contenu']; // Pas de nettoyage pour préserver les variables
        $statut_id = !empty($_POST['statut_id']) ? (int)$_POST['statut_id'] : null;
        $est_actif = isset($_POST['est_actif']) ? 1 : 0;
        
        // Validation
        if (empty($nom) || empty($contenu)) {
            set_message("Tous les champs obligatoires doivent être remplis.", "danger");
        } else {
            try {
                // Vérifier si un autre template est associé au même statut (sauf celui en cours d'édition)
                if ($statut_id) {
                    $shop_pdo = getShopDBConnection();
$check_stmt = $shop_pdo->prepare("SELECT id FROM sms_templates WHERE statut_id = ? AND id != ?");
                    $check_stmt->execute([$statut_id, $template_id]);
                    if ($check_stmt->rowCount() > 0) {
                        set_message("Un autre modèle est déjà associé à ce statut. Veuillez choisir un statut différent.", "danger");
                        redirect("sms_templates");
                        exit;
                    }
                }
                
                // Ajout ou modification
                if ($template_id > 0) {
                    // Modification
                    $stmt = $shop_pdo->prepare("UPDATE sms_templates SET nom = ?, contenu = ?, statut_id = ?, est_actif = ? WHERE id = ?");
                    $stmt->execute([$nom, $contenu, $statut_id, $est_actif, $template_id]);
                    set_message("Modèle de SMS mis à jour avec succès.", "success");
                } else {
                    // Ajout
                    $stmt = $shop_pdo->prepare("INSERT INTO sms_templates (nom, contenu, statut_id, est_actif) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nom, $contenu, $statut_id, $est_actif]);
                    set_message("Modèle de SMS ajouté avec succès.", "success");
                }
            } catch (PDOException $e) {
                set_message("Erreur lors de l'enregistrement du modèle : " . $e->getMessage(), "danger");
            }
        }
        redirect("sms_templates");
        exit;
    }
    
    // Traitement de la suppression
    if ($action === 'delete_template' && isset($_POST['template_id'])) {
        $template_id = (int)$_POST['template_id'];
        try {
            $stmt = $shop_pdo->prepare("DELETE FROM sms_templates WHERE id = ?");
            $stmt->execute([$template_id]);
            set_message("Modèle de SMS supprimé avec succès.", "success");
        } catch (PDOException $e) {
            set_message("Erreur lors de la suppression du modèle : " . $e->getMessage(), "danger");
        }
        redirect("sms_templates");
        exit;
    }
    
    // Traitement de l'activation/désactivation
    if ($action === 'toggle_active' && isset($_POST['template_id'])) {
        $template_id = (int)$_POST['template_id'];
        $est_actif = isset($_POST['est_actif']) ? (int)$_POST['est_actif'] : 0;
        
        // Ajout de logs détaillés pour débogage
        error_log("Toggle SMS template - Request data: " . print_r($_POST, true));
        error_log("Template ID: " . $template_id . ", État actuel dans la BDD avant mise à jour: " . getTemplateCurrentState($shop_pdo, $template_id));
        error_log("Nouvel état demandé: " . $est_actif);
        
        try {
            $stmt = $shop_pdo->prepare("UPDATE sms_templates SET est_actif = ? WHERE id = ?");
            $stmt->execute([$est_actif, $template_id]);
            $rowCount = $stmt->rowCount();
            error_log("Nombre de lignes affectées par la mise à jour: " . $rowCount);
            
            // Vérifier l'état après la mise à jour
            error_log("État après mise à jour: " . getTemplateCurrentState($shop_pdo, $template_id));
            
            set_message("Statut du modèle mis à jour avec succès.", "success");
        } catch (PDOException $e) {
            error_log("Erreur SQL lors de la mise à jour du statut: " . $e->getMessage());
            set_message("Erreur lors de la mise à jour du statut : " . $e->getMessage(), "danger");
        }
        redirect("sms_templates");
        exit;
    }
}

// Récupération des modèles de SMS
try {
    $stmt = $shop_pdo->query("
        SELECT t.*, s.nom as statut_nom 
        FROM sms_templates t
        LEFT JOIN statuts s ON t.statut_id = s.id
        ORDER BY t.est_actif DESC, t.nom ASC
    ");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $templates = [];
    set_message("Erreur lors de la récupération des modèles : " . $e->getMessage(), "danger");
}

// Récupération des statuts disponibles
try {
    $stmt = $shop_pdo->query("
        SELECT s.id, s.nom, s.code, c.nom as categorie_nom
        FROM statuts s
        JOIN statut_categories c ON s.categorie_id = c.id
        WHERE s.est_actif = 1
        ORDER BY c.ordre, s.ordre
    ");
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $statuts = [];
    set_message("Erreur lors de la récupération des statuts : " . $e->getMessage(), "danger");
}

// Récupération des variables disponibles
try {
    $stmt = $shop_pdo->query("SELECT * FROM sms_template_variables ORDER BY nom");
    $variables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $variables = [];
    set_message("Erreur lors de la récupération des variables : " . $e->getMessage(), "danger");
}

// Récupération des paramètres d'entreprise pour l'aperçu
$company_settings = [
    'company_name' => 'Maison du Geek',
    'company_phone' => '08 95 79 59 33'
];

try {
    $stmt = $shop_pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone')");
    $stmt->execute();
    $params = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (isset($params['company_name']) && !empty($params['company_name'])) {
        $company_settings['company_name'] = $params['company_name'];
    }
    if (isset($params['company_phone']) && !empty($params['company_phone'])) {
        $company_settings['company_phone'] = $params['company_phone'];
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des paramètres d'entreprise: " . $e->getMessage());
}

// Template à éditer
$template_to_edit = null;
if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    $template_id = (int)$_GET['edit'];
    try {
        $stmt = $shop_pdo->prepare("SELECT * FROM sms_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $template_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        set_message("Erreur lors de la récupération du modèle : " . $e->getMessage(), "danger");
    }
}

// Fonction d'aide pour obtenir l'état actuel d'un template
function getTemplateCurrentState($shop_pdo, $template_id) {
    try {
        $stmt = $shop_pdo->prepare("SELECT est_actif FROM sms_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['est_actif'] : 'non trouvé';
    } catch (PDOException $e) {
        return 'erreur: ' . $e->getMessage();
    }
}
?>

<style>
:root {
    --primary-color: #2563eb;
    --success-color: #16a34a;
    --warning-color: #ea580c;
    --danger-color: #dc2626;
    --secondary-color: #64748b;
    --info-color: #0ea5e9;
    --light-color: #f8fafc;
    --dark-color: #1e293b;
    --border-color: #e2e8f0;
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --border-radius: 8px;
    --transition: all 0.2s ease-in-out;
    
    /* Variables pour le mode clair */
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --bg-tertiary: #fefefe;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --border-light: #e2e8f0;
    --table-hover: #f8fafc;
    --header-bg: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%);
    --table-header-bg: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

/* Mode sombre */
[data-theme="dark"], 
body.dark-theme,
.dark-mode {
    --primary-color: #3b82f6;
    --success-color: #22c55e;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --secondary-color: #6b7280;
    --info-color: #06b6d4;
    
    /* Variables pour le mode sombre */
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --bg-tertiary: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #94a3b8;
    --border-light: #334155;
    --table-hover: #1e293b;
    --header-bg: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
    --table-header-bg: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
}

.modern-container {
    padding: 0.5rem;
    width: 100%;
    max-width: 100vw;
    margin: 0;
    background: var(--bg-secondary);
    min-height: 100vh;
    box-sizing: border-box;
}

.modern-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    margin: 0.5rem;
    border: 1px solid var(--border-light);
    width: calc(100% - 1rem);
    box-sizing: border-box;
}

.modern-header {
    background: var(--header-bg);
    color: white;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.modern-header h5 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.25rem;
    font-weight: 600;
}

.modern-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
}

.modern-btn:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow);
}

.modern-btn-primary {
    background: var(--primary-color);
    color: white;
}

.modern-btn-primary:hover {
    background: #1d4ed8;
    color: white;
}

.modern-btn-success {
    background: var(--success-color);
    color: white;
}

.modern-btn-success:hover {
    background: #15803d;
    color: white;
}

.modern-btn-warning {
    background: var(--warning-color);
    color: white;
}

.modern-btn-warning:hover {
    background: #c2410c;
    color: white;
}

.modern-btn-danger {
    background: var(--danger-color);
    color: white;
}

.modern-btn-danger:hover {
    background: #b91c1c;
    color: white;
}

.modern-btn-secondary {
    background: var(--secondary-color);
    color: white;
}

.modern-btn-secondary:hover {
    background: #475569;
    color: white;
}

.modern-btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.modern-table-container {
    overflow-x: auto;
    background: var(--bg-primary);
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    width: 100%;
    box-sizing: border-box;
}

.modern-table {
    width: 100%;
    min-width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
    background: var(--bg-primary);
    color: var(--text-primary);
    table-layout: auto;
}

.modern-table thead {
    background: var(--table-header-bg);
    position: sticky;
    top: 0;
    z-index: 10;
}

.modern-table th {
    padding: 0.5rem 0.75rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 2px solid var(--border-light);
    white-space: nowrap;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    box-sizing: border-box;
}

.modern-table td {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid var(--border-light);
    vertical-align: middle;
    color: var(--text-primary);
    box-sizing: border-box;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Colonnes flexibles avec largeurs adaptatives */
.modern-table th:nth-child(1), /* Nom */
.modern-table td:nth-child(1) {
    width: 20%;
    min-width: 120px;
}

.modern-table th:nth-child(2), /* Contenu */
.modern-table td:nth-child(2) {
    width: 40%;
    min-width: 200px;
    max-width: 350px;
}

.modern-table th:nth-child(3), /* Statut */
.modern-table td:nth-child(3) {
    width: 15%;
    min-width: 100px;
    text-align: center;
}

.modern-table th:nth-child(4), /* État */
.modern-table td:nth-child(4) {
    width: 12%;
    min-width: 80px;
    text-align: center;
}

.modern-table th:nth-child(5), /* Actions */
.modern-table td:nth-child(5) {
    width: 13%;
    min-width: 100px;
    text-align: center;
}

.modern-table tbody tr {
    transition: var(--transition);
    background: var(--bg-primary);
}

.modern-table tbody tr:hover {
    background-color: var(--table-hover);
}

.modern-table tbody tr:nth-child(even) {
    background-color: var(--bg-tertiary);
}

.modern-table tbody tr:nth-child(even):hover {
    background-color: var(--table-hover);
}

.modern-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.modern-badge-info {
    background-color: #dbeafe;
    color: #1e40af;
}

.modern-badge-secondary {
    background-color: #f1f5f9;
    color: #475569;
}

.modern-badge-success {
    background-color: #dcfce7;
    color: #166534;
}

.modern-actions-cell {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.modern-content-preview {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--text-secondary);
    line-height: 1.4;
    width: 100%;
    display: block;
}

.modern-empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--text-secondary);
    background: var(--bg-primary);
}

.modern-empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
    color: var(--text-muted);
}

.modern-empty-state h6 {
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.modern-empty-state p {
    color: var(--text-secondary);
}

.modern-toggle-form {
    display: inline-block;
}

/* Amélioration responsive */
@media (max-width: 1600px) {
    .modern-table th:nth-child(2),
    .modern-table td:nth-child(2) {
        width: 35%;
        min-width: 180px;
        max-width: 300px;
    }
}

@media (max-width: 1200px) {
    .modern-container {
        padding: 0.5rem;
    }
    
    .modern-card {
        margin: 0.25rem;
        width: calc(100% - 0.5rem);
    }
    
    .modern-table {
        font-size: 0.8rem;
    }
    
    .modern-table th,
    .modern-table td {
        padding: 0.4rem 0.6rem;
    }
    
    .modern-table th {
        font-size: 0.7rem;
    }
    
    .modern-table th:nth-child(2),
    .modern-table td:nth-child(2) {
        width: 30%;
        min-width: 150px;
        max-width: 250px;
    }
    
    .modern-table th:nth-child(3),
    .modern-table td:nth-child(3) {
        width: 18%;
        min-width: 90px;
    }
}

@media (max-width: 992px) {
    .modern-header {
        padding: 0.75rem;
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    
    .modern-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .modern-table th:nth-child(1),
    .modern-table td:nth-child(1) {
        width: 25%;
        min-width: 100px;
    }
    
    .modern-table th:nth-child(2),
    .modern-table td:nth-child(2) {
        width: 35%;
        min-width: 120px;
        max-width: 200px;
    }
}

@media (max-width: 768px) {
    .modern-container {
        padding: 0.25rem;
    }
    
    .modern-card {
        margin: 0.25rem;
        width: calc(100% - 0.5rem);
    }
    
    .modern-header {
        padding: 0.75rem;
    }
    
    .modern-table {
        font-size: 0.75rem;
    }
    
    .modern-table th,
    .modern-table td {
        padding: 0.3rem 0.5rem;
    }
    
    .modern-table th {
        font-size: 0.65rem;
    }
    
    .modern-btn {
        padding: 0.4rem 0.6rem;
        font-size: 0.75rem;
    }
    
    .modern-btn-sm {
        padding: 0.2rem 0.4rem;
        font-size: 0.65rem;
    }
    
    /* Ajustement des colonnes pour tablettes */
    .modern-table th:nth-child(1),
    .modern-table td:nth-child(1) {
        width: 30%;
        min-width: 80px;
    }
    
    .modern-table th:nth-child(2),
    .modern-table td:nth-child(2) {
        width: 40%;
        min-width: 100px;
    }
    
    .modern-table th:nth-child(4),
    .modern-table td:nth-child(4) {
        width: 15%;
        min-width: 60px;
    }
    
    .modern-table th:nth-child(5),
    .modern-table td:nth-child(5) {
        width: 15%;
        min-width: 80px;
    }
}

@media (max-width: 640px) {
    .modern-container {
        padding: 0;
    }
    
    .modern-card {
        margin: 0.25rem;
        width: calc(100% - 0.5rem);
        border-radius: var(--border-radius);
    }
    
    .modern-table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .modern-table {
        min-width: 600px; /* Largeur minimale pour éviter l'écrasement */
        font-size: 0.7rem;
    }
    
    .modern-table th,
    .modern-table td {
        padding: 0.3rem 0.4rem;
        white-space: nowrap;
    }
    
    .modern-table th {
        font-size: 0.6rem;
    }
    
    .modern-actions-cell {
        flex-direction: row;
        gap: 0.25rem;
        justify-content: center;
    }
    
    .modern-badge {
        font-size: 0.6rem;
        padding: 0.15rem 0.4rem;
    }
    
    .modern-btn-sm {
        padding: 0.15rem 0.3rem;
        font-size: 0.6rem;
    }
    
    .modern-btn-sm i {
        font-size: 0.7rem;
    }
}

@media (max-width: 480px) {
    .modern-header {
        padding: 0.5rem;
    }
    
    .modern-header h5 {
        font-size: 0.9rem;
    }
    
    .modern-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .modern-btn {
        width: 100%;
        justify-content: center;
        padding: 0.5rem;
        font-size: 0.8rem;
    }
    
    .modern-table {
        min-width: 550px;
        font-size: 0.65rem;
    }
    
    .modern-table th,
    .modern-table td {
        padding: 0.25rem 0.3rem;
    }
    
    .modern-table th {
        font-size: 0.55rem;
    }
    
    .modern-btn-sm {
        padding: 0.1rem 0.25rem;
        font-size: 0.55rem;
    }
    
    /* Optimisation pour très petits écrans */
    .modern-table th:nth-child(3),
    .modern-table td:nth-child(3) {
        display: none; /* Masquer la colonne statut sur très petits écrans */
    }
}

/* Mode sombre pour les éléments spécifiques */
[data-theme="dark"] .modern-badge-info,
body.dark-theme .modern-badge-info,
.dark-mode .modern-badge-info {
    background-color: #1e3a8a;
    color: #93c5fd;
}

[data-theme="dark"] .modern-badge-secondary,
body.dark-theme .modern-badge-secondary,
.dark-mode .modern-badge-secondary {
    background-color: #374151;
    color: #d1d5db;
}

[data-theme="dark"] .modern-badge-success,
body.dark-theme .modern-badge-success,
.dark-mode .modern-badge-success {
    background-color: #14532d;
    color: #86efac;
}

/* Styles pour les cartes mobiles */
.mobile-cards {
    display: none;
    gap: 1rem;
    padding: 1rem;
}

.mobile-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-light);
    border-radius: var(--border-radius);
    padding: 1rem;
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.mobile-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.mobile-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 1rem;
}

.mobile-card-header h6 {
    margin: 0;
    color: var(--text-primary);
    font-weight: 600;
    font-size: 1rem;
    flex: 1;
}

.mobile-card-status {
    flex-shrink: 0;
}

.mobile-card-content {
    margin-bottom: 1rem;
}

.mobile-field {
    margin-bottom: 0.75rem;
}

.mobile-field:last-child {
    margin-bottom: 0;
}

.mobile-field label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}

.mobile-field p {
    margin: 0;
    color: var(--text-primary);
    font-size: 0.875rem;
    line-height: 1.4;
}

.mobile-card-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    flex-wrap: wrap;
}

/* Responsive breakpoints */
@media (max-width: 768px) {
    .desktop-table {
        display: table;
    }
    
    .mobile-cards {
        display: none;
    }
}

@media (max-width: 640px) {
    .desktop-table {
        display: none;
    }
    
    .mobile-cards {
        display: flex;
        flex-direction: column;
    }
    
    .mobile-card-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    
    .mobile-card-status {
        align-self: flex-start;
    }
    
    .mobile-card-actions {
        justify-content: stretch;
    }
    
    .mobile-card-actions .modern-btn {
        flex: 1;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .mobile-cards {
        padding: 0.5rem;
        gap: 0.75rem;
    }
    
    .mobile-card {
        padding: 0.75rem;
    }
    
    .mobile-card-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .mobile-field label {
        font-size: 0.7rem;
    }
    
    .mobile-field p {
        font-size: 0.8rem;
    }
}

/* Styles pour les lignes cliquables */
.clickable-row {
    cursor: pointer;
    transition: var(--transition);
}

.clickable-row:hover {
    background-color: var(--table-hover) !important;
    transform: scale(1.01);
    box-shadow: var(--shadow);
}

.clickable-card {
    cursor: pointer;
}

.clickable-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

/* Styles pour l'adaptation complète à la largeur */
@media (min-width: 1600px) {
    .modern-container {
        padding: 1rem;
    }
    
    .modern-card {
        margin: 1rem;
        width: calc(100% - 2rem);
    }
    
    .modern-table th,
    .modern-table td {
        padding: 0.75rem 1rem;
    }
    
    .modern-table {
        font-size: 0.9rem;
    }
}

@media (min-width: 2000px) {
    .modern-container {
        padding: 1.5rem;
    }
    
    .modern-card {
        margin: 1.5rem;
        width: calc(100% - 3rem);
    }
    
    .modern-table th,
    .modern-table td {
        padding: 1rem 1.25rem;
    }
    
    .modern-table {
        font-size: 1rem;
    }
    
    .modern-table th {
        font-size: 0.85rem;
    }
}

/* Styles pour le modal d'édition rapide */
.quick-view-field {
    background: var(--bg-secondary) !important;
    color: var(--text-primary);
    border: 1px solid var(--border-light);
}

.quick-edit-field {
    background: var(--bg-primary) !important;
    color: var(--text-primary) !important;
    border: 2px solid var(--primary-color) !important;
    transition: var(--transition);
}

.quick-edit-field:focus {
    background: var(--bg-primary) !important;
    color: var(--text-primary) !important;
    border-color: var(--primary-color) !important;
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
}

/* Adaptation des boutons pour les modes clair/sombre */
.modern-btn {
    border: none;
    transition: var(--transition);
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
}

.modern-btn:focus {
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
}

/* Mode clair */
.modern-btn-primary {
    background: var(--primary-color);
    color: white;
}

.modern-btn-primary:hover {
    background: #1d4ed8;
    color: white;
    transform: translateY(-1px);
    box-shadow: var(--shadow);
}

.modern-btn-warning {
    background: var(--warning-color);
    color: white;
}

.modern-btn-warning:hover {
    background: #c2410c;
    color: white;
    transform: translateY(-1px);
    box-shadow: var(--shadow);
}

.modern-btn-success {
    background: var(--success-color);
    color: white;
}

.modern-btn-success:hover {
    background: #15803d;
    color: white;
    transform: translateY(-1px);
    box-shadow: var(--shadow);
}

.modern-btn-danger {
    background: var(--danger-color);
    color: white;
}

.modern-btn-danger:hover {
    background: #b91c1c;
    color: white;
    transform: translateY(-1px);
    box-shadow: var(--shadow);
}

.modern-btn-secondary {
    background: var(--secondary-color);
    color: white;
}

.modern-btn-secondary:hover {
    background: #475569;
    color: white;
    transform: translateY(-1px);
    box-shadow: var(--shadow);
}

/* Mode sombre - Ajustements pour les boutons */
[data-theme="dark"] .modern-btn-primary,
body.dark-theme .modern-btn-primary,
.dark-mode .modern-btn-primary {
    background: #3b82f6;
    color: white;
}

[data-theme="dark"] .modern-btn-primary:hover,
body.dark-theme .modern-btn-primary:hover,
.dark-mode .modern-btn-primary:hover {
    background: #2563eb;
    color: white;
}

[data-theme="dark"] .modern-btn-warning,
body.dark-theme .modern-btn-warning,
.dark-mode .modern-btn-warning {
    background: #f59e0b;
    color: #1f2937;
}

[data-theme="dark"] .modern-btn-warning:hover,
body.dark-theme .modern-btn-warning:hover,
.dark-mode .modern-btn-warning:hover {
    background: #d97706;
    color: #1f2937;
}

[data-theme="dark"] .modern-btn-success,
body.dark-theme .modern-btn-success,
.dark-mode .modern-btn-success {
    background: #22c55e;
    color: white;
}

[data-theme="dark"] .modern-btn-success:hover,
body.dark-theme .modern-btn-success:hover,
.dark-mode .modern-btn-success:hover {
    background: #16a34a;
    color: white;
}

[data-theme="dark"] .modern-btn-danger,
body.dark-theme .modern-btn-danger,
.dark-mode .modern-btn-danger {
    background: #ef4444;
    color: white;
}

[data-theme="dark"] .modern-btn-danger:hover,
body.dark-theme .modern-btn-danger:hover,
.dark-mode .modern-btn-danger:hover {
    background: #dc2626;
    color: white;
}

[data-theme="dark"] .modern-btn-secondary,
body.dark-theme .modern-btn-secondary,
.dark-mode .modern-btn-secondary {
    background: #6b7280;
    color: white;
}

[data-theme="dark"] .modern-btn-secondary:hover,
body.dark-theme .modern-btn-secondary:hover,
.dark-mode .modern-btn-secondary:hover {
    background: #4b5563;
    color: white;
}

/* === STYLES POUR LES TOGGLE SWITCHES === */
.modern-toggle-switch {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    user-select: none;
    cursor: pointer;
}

.toggle-checkbox {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
    margin: 0;
    transition: var(--transition);
}

.toggle-slider {
    position: relative;
    width: 60px;
    height: 28px;
    background: linear-gradient(145deg, #e2e8f0, #cbd5e0);
    border-radius: 28px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
    overflow: hidden;
}

.toggle-slider:before {
    content: "";
    position: absolute;
    height: 24px;
    width: 24px;
    left: 2px;
    top: 2px;
    background: linear-gradient(145deg, #ffffff, #f8fafc);
    border-radius: 50%;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2), 0 1px 3px rgba(0, 0, 0, 0.1);
    z-index: 2;
}

.toggle-slider:after {
    content: "OFF";
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.65rem;
    font-weight: 700;
    color: #64748b;
    transition: all 0.3s ease;
    z-index: 1;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
}

/* État activé */
.toggle-checkbox:checked + .toggle-label .toggle-slider {
    background: linear-gradient(145deg, #22c55e, #16a34a);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2), 0 1px 3px rgba(34, 197, 94, 0.3);
}

.toggle-checkbox:checked + .toggle-label .toggle-slider:before {
    transform: translateX(32px);
    background: linear-gradient(145deg, #ffffff, #f0fdf4);
}

.toggle-checkbox:checked + .toggle-label .toggle-slider:after {
    content: "ON";
    left: 8px;
    right: auto;
    color: #ffffff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.toggle-checkbox:checked + .toggle-label .toggle-text {
    color: var(--success-color);
    font-weight: 600;
}

/* État désactivé */
.toggle-checkbox:not(:checked) + .toggle-label .toggle-text {
    color: var(--text-secondary);
}

/* Effet hover */
.toggle-label:hover .toggle-slider {
    transform: scale(1.02);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.15), 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.toggle-checkbox:checked + .toggle-label:hover .toggle-slider {
    background: linear-gradient(145deg, #16a34a, #15803d);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.25), 0 2px 8px rgba(34, 197, 94, 0.4), 0 0 0 3px rgba(34, 197, 94, 0.2);
}

/* Focus visible pour accessibilité */
.toggle-checkbox:focus + .toggle-label .toggle-slider {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Styles pour mode sombre */
[data-theme="dark"] .toggle-slider,
body.dark-theme .toggle-slider,
.dark-mode .toggle-slider {
    background: linear-gradient(145deg, #374151, #1f2937);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4), 0 1px 3px rgba(0, 0, 0, 0.3);
}

[data-theme="dark"] .toggle-slider:before,
body.dark-theme .toggle-slider:before,
.dark-mode .toggle-slider:before {
    background: linear-gradient(145deg, #e5e7eb, #d1d5db);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4), 0 1px 3px rgba(0, 0, 0, 0.2);
}

[data-theme="dark"] .toggle-slider:after,
body.dark-theme .toggle-slider:after,
.dark-mode .toggle-slider:after {
    color: #9ca3af;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}

[data-theme="dark"] .toggle-checkbox:checked + .toggle-label .toggle-slider,
body.dark-theme .toggle-checkbox:checked + .toggle-label .toggle-slider,
.dark-mode .toggle-checkbox:checked + .toggle-label .toggle-slider {
    background: linear-gradient(145deg, #22c55e, #16a34a);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3), 0 1px 3px rgba(34, 197, 94, 0.4);
}

[data-theme="dark"] .toggle-checkbox:checked + .toggle-label .toggle-slider:after,
body.dark-theme .toggle-checkbox:checked + .toggle-label .toggle-slider:after,
.dark-mode .toggle-checkbox:checked + .toggle-label .toggle-slider:after {
    color: #ffffff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

[data-theme="dark"] .toggle-checkbox:checked + .toggle-label:hover .toggle-slider,
body.dark-theme .toggle-checkbox:checked + .toggle-label:hover .toggle-slider,
.dark-mode .toggle-checkbox:checked + .toggle-label:hover .toggle-slider {
    background: linear-gradient(145deg, #16a34a, #15803d);
}

/* Styles responsives pour toggles */
@media (max-width: 768px) {
    .toggle-slider {
        width: 50px;
        height: 24px;
    }
    
    .toggle-slider:before {
        height: 20px;
        width: 20px;
        left: 2px;
        top: 2px;
    }
    
    .toggle-slider:after {
        font-size: 0.55rem;
        right: 4px;
    }
    
    .toggle-checkbox:checked + .toggle-label .toggle-slider:before {
        transform: translateX(26px);
    }
    
    .toggle-checkbox:checked + .toggle-label .toggle-slider:after {
        left: 6px;
    }
    
    .toggle-text {
        font-size: 0.75rem;
    }
}

@media (max-width: 480px) {
    .toggle-text {
        display: none; /* Masquer le texte sur très petits écrans */
    }
    
    .modern-toggle-switch {
        justify-content: center;
    }
    
    .toggle-slider {
        width: 45px;
        height: 22px;
    }
    
    .toggle-slider:before {
        height: 18px;
        width: 18px;
    }
    
    .toggle-checkbox:checked + .toggle-label .toggle-slider:before {
        transform: translateX(23px);
    }
}

/* Style spécial pour le toggle du modal */
.modern-toggle-switch.w-100 {
    justify-content: center;
    padding: 8px;
    background: var(--bg-secondary);
    border-radius: 6px;
    border: 1px solid var(--border-light);
}

.modern-toggle-switch.w-100 .toggle-label {
    width: 100%;
    justify-content: center;
}
</style>

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

<div class="modern-container" id="mainContent" style="display: none;">
    <div class="modern-card">
        <div class="modern-header">
            <h5><i class="fas fa-list"></i>Modèles de SMS disponibles</h5>
            <div class="modern-actions">
                <a href="index.php?page=campagne_sms" class="modern-btn modern-btn-success">
                    <i class="fas fa-paper-plane"></i>Campagnes SMS
                    </a>
                    <?php if ($is_admin): ?>
                <button class="modern-btn modern-btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal">
                    <i class="fas fa-plus"></i>Nouveau modèle
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        
        <div class="modern-table-container">
            <?php if (empty($templates)): ?>
                <div class="modern-empty-state">
                    <i class="fas fa-inbox"></i>
                    <h6>Aucun modèle de SMS trouvé</h6>
                    <p>Commencez par créer votre premier modèle de SMS</p>
        </div>
            <?php else: ?>
                <!-- Vue tableau pour desktop/tablet -->
                <table class="modern-table desktop-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Contenu</th>
                            <th>Statut associé</th>
                            <th>État</th>
                            <?php if ($is_admin): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                            <?php foreach ($templates as $template): ?>
                        <tr class="clickable-row" data-template-id="<?php echo $template['id']; ?>" data-bs-toggle="modal" data-bs-target="#quickViewModal">
                                <td>
                                <strong><?php echo htmlspecialchars($template['nom']); ?></strong>
                            </td>
                            <td>
                                <div class="modern-content-preview" title="<?php echo htmlspecialchars($template['contenu']); ?>">
                                    <?php 
                                    $contenu = htmlspecialchars($template['contenu']);
                                    echo strlen($contenu) > 50 ? substr($contenu, 0, 50) . '...' : $contenu;
                                    ?>
                                </div>
                                </td>
                                <td>
                                    <?php if ($template['statut_nom']): ?>
                                    <span class="modern-badge modern-badge-info"><?php echo htmlspecialchars($template['statut_nom']); ?></span>
                                    <?php else: ?>
                                    <span class="modern-badge modern-badge-secondary">Non associé</span>
                                    <?php endif; ?>
                                </td>
                            <td onclick="event.stopPropagation();">
                                    <?php if ($is_admin): ?>
                                <form method="post" class="modern-toggle-form">
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                        <input type="hidden" name="est_actif" value="<?php echo $template['est_actif'] ? 0 : 1; ?>">
                                        <div class="modern-toggle-switch" onclick="event.stopPropagation();">
                                            <input type="checkbox" 
                                                   id="toggle_<?php echo $template['id']; ?>" 
                                                   class="toggle-checkbox" 
                                                   <?php echo $template['est_actif'] ? 'checked' : ''; ?>
                                                   onchange="event.stopPropagation(); toggleTemplate(<?php echo $template['id']; ?>, this.checked ? 1 : 0);">
                                            <label for="toggle_<?php echo $template['id']; ?>" class="toggle-label" onclick="event.stopPropagation();">
                                                <span class="toggle-slider"></span>
                                                <span class="toggle-text"><?php echo $template['est_actif'] ? 'Actif' : 'Inactif'; ?></span>
                                            </label>
                                        </div>
                                    </form>
                                    <?php else: ?>
                                <span class="modern-badge <?php echo $template['est_actif'] ? 'modern-badge-success' : 'modern-badge-secondary'; ?>">
                                        <?php echo $template['est_actif'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($is_admin): ?>
                            <td onclick="event.stopPropagation();">
                                <div class="modern-actions-cell">
                                    <a href="index.php?page=sms_templates&edit=<?php echo $template['id']; ?>" class="modern-btn modern-btn-sm modern-btn-warning" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="modern-btn modern-btn-sm modern-btn-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal"
                                        data-id="<?php echo $template['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($template['nom']); ?>"
                                        title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Vue cartes pour mobile -->
                <div class="mobile-cards">
                    <?php foreach ($templates as $template): ?>
                    <div class="mobile-card clickable-card" data-template-id="<?php echo $template['id']; ?>" data-bs-toggle="modal" data-bs-target="#quickViewModal">
                        <div class="mobile-card-header">
                            <h6><?php echo htmlspecialchars($template['nom']); ?></h6>
                            <div class="mobile-card-status" onclick="event.stopPropagation();">
                                <?php if ($is_admin): ?>
                                <form method="post" class="modern-toggle-form">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                    <input type="hidden" name="est_actif" value="<?php echo $template['est_actif'] ? 0 : 1; ?>">
                                    <div class="modern-toggle-switch" onclick="event.stopPropagation();">
                                        <input type="checkbox" 
                                               id="toggle_mobile_<?php echo $template['id']; ?>" 
                                               class="toggle-checkbox" 
                                               <?php echo $template['est_actif'] ? 'checked' : ''; ?>
                                               onchange="event.stopPropagation(); toggleTemplate(<?php echo $template['id']; ?>, this.checked ? 1 : 0);">
                                        <label for="toggle_mobile_<?php echo $template['id']; ?>" class="toggle-label" onclick="event.stopPropagation();">
                                            <span class="toggle-slider"></span>
                                            <span class="toggle-text"><?php echo $template['est_actif'] ? 'Actif' : 'Inactif'; ?></span>
                                        </label>
                                    </div>
                                </form>
                                <?php else: ?>
                                <span class="modern-badge <?php echo $template['est_actif'] ? 'modern-badge-success' : 'modern-badge-secondary'; ?>">
                                    <?php echo $template['est_actif'] ? 'Actif' : 'Inactif'; ?>
                                </span>
                                <?php endif; ?>
            </div>
        </div>
                        
                        <div class="mobile-card-content">
                            <div class="mobile-field">
                                <label>Contenu:</label>
                                <p><?php 
                                    $contenu = htmlspecialchars($template['contenu']);
                                    echo strlen($contenu) > 80 ? substr($contenu, 0, 80) . '...' : $contenu;
                                ?></p>
                            </div>
                            
                            <div class="mobile-field">
                                <label>Statut associé:</label>
                                <?php if ($template['statut_nom']): ?>
                                    <span class="modern-badge modern-badge-info"><?php echo htmlspecialchars($template['statut_nom']); ?></span>
                                <?php else: ?>
                                    <span class="modern-badge modern-badge-secondary">Non associé</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($is_admin): ?>
                        <div class="mobile-card-actions" onclick="event.stopPropagation();">
                            <a href="index.php?page=sms_templates&edit=<?php echo $template['id']; ?>" class="modern-btn modern-btn-sm modern-btn-warning">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <button type="button" class="modern-btn modern-btn-sm modern-btn-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteModal"
                                data-id="<?php echo $template['id']; ?>"
                                data-name="<?php echo htmlspecialchars($template['nom']); ?>">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal pour ajouter/éditer un modèle -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="templateModalLabel">
                    <?php echo $template_to_edit ? 'Modifier le modèle' : 'Nouveau modèle de SMS'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_template">
                    <input type="hidden" name="template_id" value="<?php echo $template_to_edit ? $template_to_edit['id'] : 0; ?>">
                    
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom du modèle *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required
                            value="<?php echo $template_to_edit ? htmlspecialchars($template_to_edit['nom']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="contenu" class="form-label">Contenu du SMS *</label>
                        <textarea class="form-control" id="contenu" name="contenu" rows="5" required
                            maxlength="320"><?php echo $template_to_edit ? htmlspecialchars($template_to_edit['contenu']) : ''; ?></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <div id="charCount" class="form-text">0/320 caractères</div>
                            <div id="smsCount" class="form-text">1 SMS</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="statut_id" class="form-label">Associer à un statut de réparation</label>
                        <select class="form-select" id="statut_id" name="statut_id">
                            <option value="">-- Aucun statut --</option>
                            <?php foreach ($statuts as $statut): ?>
                            <option value="<?php echo $statut['id']; ?>" 
                                <?php echo ($template_to_edit && $template_to_edit['statut_id'] == $statut['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($statut['categorie_nom'] . ' - ' . $statut['nom']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Si sélectionné, ce modèle sera envoyé automatiquement lorsqu'une réparation passe à ce statut.</div>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="est_actif" name="est_actif"
                            <?php echo (!$template_to_edit || $template_to_edit['est_actif']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="est_actif">Activer ce modèle</label>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Variables disponibles</h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($variables as $variable): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary mb-1 me-1 variable-btn" 
                                data-variable="[<?php echo $variable['nom']; ?>]"
                                title="<?php echo htmlspecialchars($variable['description']); ?>">
                                [<?php echo $variable['nom']; ?>]
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Ajout: Bouton de test et aperçu -->
                    <div class="mt-3">
                        <button type="button" class="btn btn-info text-white" id="btnPreviewSMS">
                            <i class="fas fa-eye me-2"></i>Tester le remplacement des variables
                        </button>
                        <div id="testResultContainer" class="mt-3 d-none">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Résultat du test</h6>
                                </div>
                                <div class="card-body">
                                    <h6>Message avec variables:</h6>
                                    <pre id="preTemplateContent" class="border p-2 mb-3 bg-light" style="white-space: pre-wrap;"></pre>
                                    
                                    <h6>Message après remplacement:</h6>
                                    <pre id="preReplacedContent" class="border p-2 mb-3" style="white-space: pre-wrap;"></pre>
                                    
                                    <h6>Détails du remplacement:</h6>
                                    <div id="replacementDetails" class="border p-2 small" style="max-height: 200px; overflow-y: auto;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de visualisation rapide -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="quickViewModalLabel">
                    <i class="fas fa-eye me-2"></i>Aperçu du modèle SMS
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom du modèle</label>
                            <div id="quickViewNom" class="form-control-plaintext quick-view-field p-2 rounded"></div>
                            <input type="text" id="quickEditNom" class="form-control quick-edit-field" style="display: none;">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Contenu du SMS</label>
                            <div id="quickViewContenu" class="form-control-plaintext quick-view-field p-3 rounded" style="min-height: 120px; white-space: pre-wrap; font-family: monospace;"></div>
                            <textarea id="quickEditContenu" class="form-control quick-edit-field" rows="6" style="display: none; font-family: monospace;" maxlength="320"></textarea>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <span id="quickViewCharCount">0</span> caractères • 
                                    <span id="quickViewSmsCount">1</span> SMS
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Statut associé</label>
                            <div id="quickViewStatut"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">État</label>
                            <div id="quickViewEtat"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Actions rapides</label>
                            <div class="d-grid gap-2">
                                <!-- Bouton Modifier visible pour tous -->
                                <button type="button" class="modern-btn modern-btn-warning w-100" id="quickEditToggleBtn">
                                    <i class="fas fa-edit me-2"></i><span id="quickEditToggleText">Modifier</span>
                                </button>
                                
                                <?php if ($is_admin): ?>
                                <!-- Bouton Toggle actif/inactif pour admin seulement -->
                                <form method="post" id="quickToggleForm" class="d-inline">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="template_id" id="quickToggleTemplateId">
                                    <input type="hidden" name="est_actif" id="quickToggleEstActif">
                                    <button type="submit" class="modern-btn modern-btn-secondary w-100" id="quickToggleBtn">
                                        <i class="fas fa-power-off me-2"></i><span id="quickToggleText">Activer/Désactiver</span>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aperçu avec variables remplacées -->
                <div class="mt-4">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-magic me-2"></i>Aperçu avec variables d'exemple
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="bg-light p-3 rounded">
                                <div id="quickViewPreview" style="white-space: pre-wrap; font-family: monospace;"></div>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-info-circle me-1"></i>
                                Cet aperçu utilise des données d'exemple pour remplacer les variables
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="modern-btn modern-btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <!-- Bouton Modifier dans le footer visible pour tous -->
                <button type="button" class="modern-btn modern-btn-primary" id="quickEditFooterBtn">
                    <i class="fas fa-edit me-2"></i><span id="quickEditFooterText">Modifier</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer le modèle "<span id="templateName"></span>" ?
            </div>
            <div class="modal-footer">
                <form method="post">
                    <input type="hidden" name="action" value="delete_template">
                    <input type="hidden" name="template_id" id="deleteTemplateId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ouvrir automatiquement le modal d'édition si édition demandée
    <?php if ($template_to_edit): ?>
    var templateModal = new bootstrap.Modal(document.getElementById('templateModal'));
    templateModal.show();
    <?php endif; ?>
    
    // Compteur de caractères pour le SMS
    const contenuTextarea = document.getElementById('contenu');
    const charCount = document.getElementById('charCount');
    const smsCount = document.getElementById('smsCount');
    
    function updateCounter() {
        const length = contenuTextarea.value.length;
        charCount.textContent = length + "/320 caractères";
        
        // Calcul du nombre de SMS
        if (length <= 160) {
            smsCount.textContent = "1 SMS";
        } else {
            // 153 caractères par SMS pour les messages concaténés
            const count = Math.ceil(length / 153);
            smsCount.textContent = count + " SMS";
        }
    }
    
    contenuTextarea.addEventListener('input', updateCounter);
    
    // Initialiser le compteur au chargement
    updateCounter();
    
    // Insérer les variables dans le texte
    const variableBtns = document.querySelectorAll('.variable-btn');
    variableBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const variable = this.getAttribute('data-variable');
            const cursorPos = contenuTextarea.selectionStart;
            const textBefore = contenuTextarea.value.substring(0, cursorPos);
            const textAfter = contenuTextarea.value.substring(cursorPos);
            
            contenuTextarea.value = textBefore + variable + textAfter;
            
            // Replacer le curseur après la variable insérée
            const newCursorPos = cursorPos + variable.length;
            contenuTextarea.focus();
            contenuTextarea.setSelectionRange(newCursorPos, newCursorPos);
            
            // Mettre à jour le compteur
            updateCounter();
        });
    });
    
    // Configuration du modal de suppression
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        
        document.getElementById('deleteTemplateId').value = id;
        document.getElementById('templateName').textContent = name;
    });
    
    // Ajout: Fonctionnalité de test des variables
    const btnPreviewSMS = document.getElementById('btnPreviewSMS');
    if (btnPreviewSMS) {
        btnPreviewSMS.addEventListener('click', function() {
            const testResultContainer = document.getElementById('testResultContainer');
            const templateContent = contenuTextarea.value;
            const preTemplateContent = document.getElementById('preTemplateContent');
            const preReplacedContent = document.getElementById('preReplacedContent');
            const replacementDetails = document.getElementById('replacementDetails');
            
            // Afficher le contenu du template
            preTemplateContent.textContent = templateContent;
            
            // Générer l'URL de suivi dynamique pour les tests
            const currentHost = window.location.hostname;
            const protocol = window.location.protocol;
            const suiviUrl = protocol + '//' + currentHost + '/suivi.php?id=12345';
            
            // Valeurs d'exemple pour le test
            const testValues = {
                '[CLIENT_NOM]': 'Dupont',
                '[CLIENT_PRENOM]': 'Jean',
                '[CLIENT_TELEPHONE]': '+33612345678',
                '[REPARATION_ID]': '12345',
                '[APPAREIL_TYPE]': 'Smartphone',
                '[APPAREIL_MARQUE]': 'Samsung',
                '[APPAREIL_MODELE]': 'Galaxy S21',
                '[DATE_RECEPTION]': '01/01/2023',
                '[DATE_FIN_PREVUE]': '15/01/2023',
                '[PRIX]': '89,90 €',
                '[MONTANT]': '89,90€',
                '[JOURS_RESTANTS]': '3',
                '[COMPANY_NAME]': '<?php echo addslashes($company_settings['company_name']); ?>',
                '[COMPANY_PHONE]': '<?php echo addslashes($company_settings['company_phone']); ?>',
                '[JOURS_EXPIRES]': '2',
                '[PRIX_GARDIENNAGE]': '5,00',
                '[URL_SUIVI]': suiviUrl,
                '[URL_DEVIS]': protocol + '//' + currentHost + '/pages/devis_client.php?lien=abc123xyz',
                '[DOMAINE]': currentHost
            };
            
            // Effectuer les remplacements
            let replacedContent = templateContent;
            let detailsHTML = '<ul class="list-group">';
            
            for (const [variable, value] of Object.entries(testValues)) {
                const oldContent = replacedContent;
                replacedContent = replacedContent.replace(new RegExp(escapeRegExp(variable), 'g'), value);
                
                if (oldContent !== replacedContent) {
                    detailsHTML += `<li class="list-group-item list-group-item-success">${variable} → <strong>${value}</strong> (Remplacé avec succès)</li>`;
                } else {
                    if (templateContent.includes(variable)) {
                        detailsHTML += `<li class="list-group-item list-group-item-warning">${variable} → <strong>${value}</strong> (Variable présente mais non remplacée)</li>`;
                    } else {
                        detailsHTML += `<li class="list-group-item list-group-item-secondary">${variable} → <strong>${value}</strong> (Variable non trouvée dans le template)</li>`;
                    }
                }
            }
            
            detailsHTML += '</ul>';
            
            // Afficher le résultat
            preReplacedContent.textContent = replacedContent;
            replacementDetails.innerHTML = detailsHTML;
            testResultContainer.classList.remove('d-none');
        });
    }
    
    // Fonction pour échapper les caractères spéciaux dans les expressions régulières
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    // Gestion du modal de visualisation rapide
    const quickViewModal = document.getElementById('quickViewModal');
    if (quickViewModal) {
        // Données des templates pour JavaScript
        const templatesData = <?php echo json_encode($templates); ?>;
        
        quickViewModal.addEventListener('show.bs.modal', function(event) {
            const trigger = event.relatedTarget;
            const templateId = trigger.getAttribute('data-template-id');
            
            // Trouver le template correspondant
            const template = templatesData.find(t => t.id == templateId);
            if (!template) return;
            
            // Remplir les informations du modal
            document.getElementById('quickViewNom').textContent = template.nom;
            document.getElementById('quickViewContenu').textContent = template.contenu;
            
            // Calculer le nombre de caractères et de SMS
            const length = template.contenu.length;
            document.getElementById('quickViewCharCount').textContent = length;
            
            let smsCount;
            if (length <= 160) {
                smsCount = 1;
            } else {
                smsCount = Math.ceil(length / 153);
            }
            document.getElementById('quickViewSmsCount').textContent = smsCount;
            
            // Statut associé
            const statutDiv = document.getElementById('quickViewStatut');
            if (template.statut_nom) {
                statutDiv.innerHTML = '<span class="badge bg-info">' + template.statut_nom + '</span>';
            } else {
                statutDiv.innerHTML = '<span class="badge bg-secondary">Non associé</span>';
            }
            
            // État
            const etatDiv = document.getElementById('quickViewEtat');
            if (template.est_actif == '1') {
                etatDiv.innerHTML = '<span class="badge bg-success">Actif</span>';
            } else {
                etatDiv.innerHTML = '<span class="badge bg-secondary">Inactif</span>';
            }
            
            // Configuration des boutons d'édition (pour tous les utilisateurs)
            const quickEditToggleBtn = document.getElementById('quickEditToggleBtn');
            const quickEditToggleText = document.getElementById('quickEditToggleText');
            const quickEditFooterBtn = document.getElementById('quickEditFooterBtn');
            const quickEditFooterText = document.getElementById('quickEditFooterText');
            
            // Configuration des boutons d'action admin (si admin)
            <?php if ($is_admin): ?>
            const quickToggleTemplateId = document.getElementById('quickToggleTemplateId');
            const quickToggleEstActif = document.getElementById('quickToggleEstActif');
            const quickToggleBtn = document.getElementById('quickToggleBtn');
            const quickToggleText = document.getElementById('quickToggleText');
            
            <?php endif; ?>
            
            // Variables pour le mode édition (pour tous les utilisateurs)
            let isEditMode = false;
            let currentTemplateId = templateId;
            
            // Fonction pour basculer en mode édition
            function toggleEditMode() {
                isEditMode = !isEditMode;
                
                const viewFields = document.querySelectorAll('.quick-view-field');
                const editFields = document.querySelectorAll('.quick-edit-field');
                
                if (isEditMode) {
                    // Passer en mode édition
                    viewFields.forEach(field => field.style.display = 'none');
                    editFields.forEach(field => field.style.display = 'block');
                    
                    // Remplir les champs d'édition
                    document.getElementById('quickEditNom').value = template.nom;
                    document.getElementById('quickEditContenu').value = template.contenu;
                    
                    // Changer les boutons
                    quickEditToggleBtn.className = 'modern-btn modern-btn-success w-100';
                    quickEditToggleBtn.innerHTML = '<i class="fas fa-save me-2"></i><span>Sauvegarder</span>';
                    quickEditToggleText.textContent = 'Sauvegarder';
                    
                    quickEditFooterBtn.className = 'modern-btn modern-btn-success';
                    quickEditFooterBtn.innerHTML = '<i class="fas fa-save me-2"></i><span>Sauvegarder</span>';
                    quickEditFooterText.textContent = 'Sauvegarder';
                    
                    // Mettre le focus sur le premier champ
                    document.getElementById('quickEditNom').focus();
                    
                    // Ajouter l'écoute du compteur de caractères
                    const contenuField = document.getElementById('quickEditContenu');
                    contenuField.addEventListener('input', updateCharacterCount);
                    updateCharacterCount();
                    
                } else {
                    // Sauvegarder les modifications
                    saveTemplate();
                }
            }
            
            // Fonction pour mettre à jour le compteur de caractères
            function updateCharacterCount() {
                const contenuField = document.getElementById('quickEditContenu');
                const length = contenuField.value.length;
                document.getElementById('quickViewCharCount').textContent = length;
                
                let smsCount;
                if (length <= 160) {
                    smsCount = 1;
                } else {
                    smsCount = Math.ceil(length / 153);
                }
                document.getElementById('quickViewSmsCount').textContent = smsCount;
            }
            
            // Fonction pour sauvegarder le template
            function saveTemplate() {
                const nom = document.getElementById('quickEditNom').value.trim();
                const contenu = document.getElementById('quickEditContenu').value.trim();
                
                if (!nom || !contenu) {
                    alert('Le nom et le contenu sont obligatoires.');
                    return;
                }
                
                // Créer un formulaire pour soumettre les données
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'save_template';
                form.appendChild(actionInput);
                
                const templateIdInput = document.createElement('input');
                templateIdInput.type = 'hidden';
                templateIdInput.name = 'template_id';
                templateIdInput.value = currentTemplateId;
                form.appendChild(templateIdInput);
                
                const nomInput = document.createElement('input');
                nomInput.type = 'hidden';
                nomInput.name = 'nom';
                nomInput.value = nom;
                form.appendChild(nomInput);
                
                const contenuInput = document.createElement('input');
                contenuInput.type = 'hidden';
                contenuInput.name = 'contenu';
                contenuInput.value = contenu;
                form.appendChild(contenuInput);
                
                const statutInput = document.createElement('input');
                statutInput.type = 'hidden';
                statutInput.name = 'statut_id';
                statutInput.value = template.statut_id || '';
                form.appendChild(statutInput);
                
                const actifInput = document.createElement('input');
                actifInput.type = 'hidden';
                actifInput.name = 'est_actif';
                actifInput.value = template.est_actif;
                form.appendChild(actifInput);
                
                document.body.appendChild(form);
                form.submit();
            }
            
            // Associer les événements aux boutons (pour tous les utilisateurs)
            if (quickEditToggleBtn) {
                quickEditToggleBtn.onclick = toggleEditMode;
            }
            
            if (quickEditFooterBtn) {
                quickEditFooterBtn.onclick = toggleEditMode;
            }
            
            <?php if ($is_admin): ?>
            // Configuration du bouton toggle actif/inactif (admin seulement)
            if (quickToggleTemplateId) {
                quickToggleTemplateId.value = templateId;
                quickToggleEstActif.value = template.est_actif == '1' ? '0' : '1';
                
                if (template.est_actif == '1') {
                    quickToggleBtn.className = 'modern-btn modern-btn-danger w-100';
                    quickToggleText.textContent = 'Désactiver';
                } else {
                    quickToggleBtn.className = 'modern-btn modern-btn-success w-100';
                    quickToggleText.textContent = 'Activer';
                }
            }
            <?php endif; ?>
            
            // Générer l'aperçu avec variables remplacées
            generatePreview(template.contenu);
        });
        
        // Fonction pour générer l'aperçu avec variables d'exemple
        function generatePreview(contenu) {
            // Générer l'URL de suivi dynamique basée sur le domaine actuel
            const currentHost = window.location.hostname;
            const protocol = window.location.protocol;
            const suiviUrl = protocol + '//' + currentHost + '/suivi.php?id=12345';
            
            const testValues = {
                '[CLIENT_NOM]': 'Dupont',
                '[CLIENT_PRENOM]': 'Jean',
                '[CLIENT_TELEPHONE]': '+33612345678',
                '[REPARATION_ID]': '12345',
                '[APPAREIL_TYPE]': 'Smartphone',
                '[APPAREIL_MARQUE]': 'Samsung',
                '[APPAREIL_MODELE]': 'Galaxy S21',
                '[DATE_RECEPTION]': '01/01/2023',
                '[DATE_FIN_PREVUE]': '15/01/2023',
                '[PRIX]': '89,90 €',
                '[MONTANT]': '89,90€',
                '[JOURS_RESTANTS]': '3',
                '[COMPANY_NAME]': '<?php echo addslashes($company_settings['company_name']); ?>',
                '[COMPANY_PHONE]': '<?php echo addslashes($company_settings['company_phone']); ?>',
                '[JOURS_EXPIRES]': '2',
                '[PRIX_GARDIENNAGE]': '5,00',
                '[URL_SUIVI]': suiviUrl,
                '[URL_DEVIS]': protocol + '//' + currentHost + '/pages/devis_client.php?lien=abc123xyz',
                '[DOMAINE]': currentHost
            };
            
            let preview = contenu;
            for (const [variable, value] of Object.entries(testValues)) {
                preview = preview.replace(new RegExp(escapeRegExp(variable), 'g'), value);
            }
            
            document.getElementById('quickViewPreview').textContent = preview;
        }
    }
});

// Fonction pour basculer l'état d'un template via AJAX
function toggleTemplate(templateId, newState) {
    console.log('Toggle template:', templateId, 'New state:', newState);
    
    // Créer une requête AJAX
    const formData = new FormData();
    formData.append('action', 'toggle_active');
    formData.append('template_id', templateId);
    formData.append('est_actif', newState);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            // Recharger la page pour voir les changements
            window.location.reload();
        } else {
            console.error('Erreur lors du toggle:', response.status);
            // Remettre le toggle dans l'état précédent en cas d'erreur
            const checkbox = document.getElementById('toggle_' + templateId);
            const mobileCheckbox = document.getElementById('toggle_mobile_' + templateId);
            if (checkbox) checkbox.checked = !checkbox.checked;
            if (mobileCheckbox) mobileCheckbox.checked = !mobileCheckbox.checked;
        }
    })
    .catch(error => {
        console.error('Erreur de réseau:', error);
        // Remettre le toggle dans l'état précédent en cas d'erreur
        const checkbox = document.getElementById('toggle_' + templateId);
        const mobileCheckbox = document.getElementById('toggle_mobile_' + templateId);
        if (checkbox) checkbox.checked = !checkbox.checked;
        if (mobileCheckbox) mobileCheckbox.checked = !mobileCheckbox.checked;
    });
}
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

.modern-container,
.modern-container * {
  background: transparent !important;
}

.modern-card {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .modern-card {
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