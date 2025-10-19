<?php
// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'accueil.php') {
    // Rediriger vers l'index principal
    header('Location: ../index.php?page=accueil');
    exit();
}

// Fonction pour obtenir la couleur en fonction de la priorité
function get_priority_color($priority) {
    switch(strtolower($priority)) {
        case 'haute':
            return 'danger';
        case 'moyenne':
            return 'warning';
        case 'basse':
            return 'info';
        default:
            return 'secondary';
    }
}

// Récupérer les statistiques pour le tableau de bord
$reparations_stats_categorie = get_reparations_count_by_status_categorie();
$reparations_en_attente = $reparations_stats_categorie['en_attente'];
$reparations_en_cours = $reparations_stats_categorie['en_cours'];
$reparations_nouvelles = $reparations_stats_categorie['nouvelles'];
$reparations_actives = count_active_reparations();

$total_clients = get_total_clients();
$taches_recentes_count = get_taches_recentes_count();
$reparations_recentes = get_recent_reparations(5);
$reparations_recentes_count = count_recent_reparations();
$taches = get_taches_en_cours(5);

// Récupérer les commandes récentes
$commandes_recentes = [];
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->query("
        SELECT c.*, cl.nom as client_nom, cl.prenom as client_prenom, f.nom as fournisseur_nom 
        FROM commandes_pieces c 
        LEFT JOIN clients cl ON c.client_id = cl.id 
        LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id 
        WHERE c.statut IN ('en_attente', 'urgent')
        ORDER BY c.date_creation DESC 
        LIMIT 5
    ");
    $commandes_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur silencieusement
    error_log("Erreur lors de la récupération des commandes récentes: " . $e->getMessage());
}
?>

<!-- Styles spécifiques pour le tableau de bord -->
<link href="assets/css/dashboard-new.css" rel="stylesheet">

<!-- Styles pour le modal de commande -->
<link href="assets/css/modern-theme.css" rel="stylesheet">
<link href="assets/css/order-form.css" rel="stylesheet">

<!-- Correction pour tableaux côte à côte -->
<style>
.dashboard-tables-container {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 1.5rem !important;
    margin-top: 2rem !important;
    margin-bottom: 2rem !important;
    width: 100% !important;
}

.table-section {
    display: flex !important;
    flex-direction: column !important;
    background: var(--bg-card, #fff) !important;
    border-radius: 10px !important;
    box-shadow: var(--shadow, 0 2px 4px rgba(0,0,0,0.05)) !important;
    padding: 1rem !important;
    height: 100% !important;
    border: 1px solid var(--border-color, #e5e7eb) !important;
    color: var(--text-primary, #111827) !important;
    transition: background-color var(--transition-fast, 0.15s), color var(--transition-fast, 0.15s), border-color var(--transition-fast, 0.15s) !important;
}

/* Mode sombre pour les sections de tableau */
body.dark-mode .table-section {
    background: var(--dark-card-bg, #1f2937) !important;
    border-color: var(--dark-border-color, #374151) !important;
    color: var(--dark-text-primary, #f9fafb) !important;
    box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.2)) !important;
}

@media (max-width: 1400px) {
    .dashboard-tables-container {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 992px) {
    .dashboard-tables-container {
        grid-template-columns: 1fr !important;
    }
    
    /* Masquer certaines colonnes sur les écrans moyens et mobiles */
    .hide-md {
        display: none !important;
    }
}

@media (max-width: 768px) {
    /* Masquer les colonnes additionnelles sur mobile */
    .hide-sm {
        display: none !important;
    }
}

.order-date, .order-quantity, .order-price {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-muted, #6b7280);
}

.order-price {
    font-weight: 600;
    color: var(--primary, #4361ee);
}

/* Mode sombre pour les éléments de commande */
body.dark-mode .order-date,
body.dark-mode .order-quantity,
body.dark-mode .order-price {
    color: var(--dark-text-secondary, #e5e7eb);
}

body.dark-mode .order-price {
    color: var(--primary, #6282ff);
}

.tabs-header .badge {
    font-size: 0.75rem;
    padding: 3px 6px;
    border-radius: 10px;
}

/* Style pour les boutons d'onglets */
.tab-button {
    padding: 10px 20px;
    border: none;
    background: none;
    cursor: pointer;
    transition: all 0.3s ease;
    border-bottom: 2px solid transparent;
    color: var(--text-primary, #111827);
}

.tab-button.active {
    color: var(--primary, #4361ee);
    border-bottom: 2px solid var(--primary, #4361ee);
    background-color: rgba(67, 97, 238, 0.1);
}

.tab-button:hover {
    background-color: rgba(67, 97, 238, 0.05);
}

/* Mode sombre pour les boutons d'onglets */
body.dark-mode .tab-button {
    color: var(--dark-text-primary, #f9fafb);
}

body.dark-mode .tab-button.active {
    color: var(--primary, #6282ff);
    border-bottom-color: var(--primary, #6282ff);
    background-color: rgba(98, 130, 255, 0.15);
}

body.dark-mode .tab-button:hover {
    background-color: rgba(98, 130, 255, 0.1);
}

/* Mode sombre pour les tableaux dans les sections */
body.dark-mode .table-section .table {
    background-color: transparent !important;
    color: var(--dark-text-primary, #f9fafb) !important;
}

body.dark-mode .table-section .table th {
    background-color: var(--dark-bg-tertiary, #1e293b) !important;
    color: var(--dark-text-primary, #f9fafb) !important;
    border-color: var(--dark-border-color, #374151) !important;
}

body.dark-mode .table-section .table td {
    color: var(--dark-text-primary, #f9fafb) !important;
    border-color: var(--dark-border-color, #374151) !important;
}

body.dark-mode .table-section .table tbody tr:hover {
    background-color: var(--dark-hover-bg, rgba(255, 255, 255, 0.05)) !important;
}

body.dark-mode .table-section h5,
body.dark-mode .table-section h4,
body.dark-mode .table-section h3 {
    color: var(--dark-text-primary, #f9fafb) !important;
}

/* Mode sombre pour les contenus de tableaux avec styles inline */
body.dark-mode .table-section [style*="background: white"] {
    background: var(--dark-card-bg, #1f2937) !important;
}

body.dark-mode .table-section [style*="background: #f8f9fa"] {
    background: var(--dark-bg-tertiary, #1e293b) !important;
}

body.dark-mode .table-section [style*="background: #ffffff"] {
    background: var(--dark-card-bg, #1f2937) !important;
}

body.dark-mode .table-section [style*="background: #fafbfc"] {
    background: var(--dark-hover-bg, rgba(255, 255, 255, 0.05)) !important;
}

body.dark-mode .table-section [style*="color: #212529"] {
    color: var(--dark-text-primary, #f9fafb) !important;
}

body.dark-mode .table-section [style*="color: #495057"] {
    color: var(--dark-text-secondary, #e5e7eb) !important;
}

body.dark-mode .table-section [style*="color: #6c757d"] {
    color: var(--dark-text-muted, #9ca3af) !important;
}

body.dark-mode .table-section [style*="border-bottom: 1px solid #e9ecef"],
body.dark-mode .table-section [style*="border-bottom: 1px solid #f1f3f4"] {
    border-bottom-color: var(--dark-border-color, #374151) !important;
}

/* En-têtes de tableaux en mode sombre */
body.dark-mode .table-section-header {
    color: var(--dark-text-primary, #f9fafb) !important;
    border-bottom-color: var(--dark-border-color, #374151) !important;
}

body.dark-mode .table-section-title {
    color: var(--dark-text-primary, #f9fafb) !important;
}

body.dark-mode .table-section-title a {
    color: var(--dark-text-primary, #f9fafb) !important;
}

/* Effets de survol en mode sombre */
body.dark-mode .table-section [onmouseover] {
    transition: background-color 0.2s ease !important;
}

body.dark-mode .table-section [onmouseover]:hover {
    background-color: var(--dark-active-bg, rgba(255, 255, 255, 0.1)) !important;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Styles pour les badges de statut */
.status-badge {
    display: inline-block;
    padding: 0.25em 0.5em;
    font-size: 0.7rem;
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 20px;
    color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s;
    letter-spacing: 0.01em;
    text-transform: uppercase;
    background-image: linear-gradient(to bottom, rgba(255,255,255,0.15), rgba(0,0,0,0.05));
}

.status-badge-primary {
    background-color: #0d6efd;
}

.status-badge-success {
    background-color: #28a745;
}

.status-badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.status-badge-danger {
    background-color: #dc3545;
}

.status-badge-info {
    background-color: #17a2b8;
}

.status-badge-secondary {
    background-color: #6c757d;
}
</style>

<div class="modern-dashboard">
    <!-- Actions rapides -->
    <?php include 'components/quick-actions.php'; ?>

    <!-- État des réparations -->
    <div class="statistics-container">
        <h3 class="section-title">État des réparations</h3>
        <div class="statistics-grid">
            <a href="index.php?page=reparations&statut_ids=1,2,3,19,20" class="stat-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $reparations_actives; ?></div>
                    <div class="stat-label">Réparation</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="index.php?page=taches" class="stat-card progress-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $taches_recentes_count; ?></div>
                    <div class="stat-label">Tâche</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="index.php?page=commandes_pieces" class="stat-card waiting-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $reparations_en_attente; ?></div>
                    <div class="stat-label">Commande</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="index.php?page=reparations&urgence=1" class="stat-card clients-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $reparations_en_cours; ?></div>
                    <div class="stat-label">Urgence</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Tableaux côte à côte -->
    <div class="dashboard-tables-container">
        <!-- Tâches en cours avec onglets -->
        <div class="table-section">
            <div class="table-section-header">
                <h4 class="table-section-title">
                    <i class="fas fa-tasks"></i>
                    <a href="index.php?page=taches" style="text-decoration: none; color: inherit;">
                        Tâches en cours
                        <span class="badge bg-primary ms-2"><?php echo $taches_recentes_count; ?></span>
                    </a>
                </h4>
                <div class="tabs">
                    <button class="tab-button active" data-tab="toutes-taches">Toutes les tâches</button>
                    <button class="tab-button" data-tab="mes-taches">Mes tâches</button>
                </div>
            </div>
            <!-- 🎯 TABLEAU TÂCHES PARFAITEMENT ALIGNÉ -->
            <div class="table-container">
                <div class="tab-content active" id="toutes-taches">
                    <!-- 🎨 DESIGN MODERNE SANS BORDURES -->
                    <div style="padding: 0;">
                        <div style="background: #f8f9fa; padding: 12px 20px; border-radius: 8px 8px 0 0; border-bottom: 1px solid #e9ecef;">
                            <div style="display: flex; align-items: center;">
                                <span style="flex: 1; font-weight: 600; color: #495057; font-size: 13px;">TITRE</span>
                                <span style="width: 30%; text-align: center; font-weight: 600; color: #495057; font-size: 13px;">PRIORITÉ</span>
                            </div>
                        </div>
                        <div style="background: white; border-radius: 0 0 8px 8px; overflow: hidden;">
                            <?php
                            $toutes_taches = get_toutes_taches_en_cours(10);
                            if (!empty($toutes_taches)) :
                                foreach ($toutes_taches as $index => $tache) :
                                    $urgence_class = get_urgence_class($tache['urgence']);
                                    $bg_color = $index % 2 === 0 ? '#ffffff' : '#fafbfc';
                            ?>
                                <div data-task-id="<?php echo $tache['id']; ?>" 
                                     style="display: flex; align-items: center; padding: 16px 20px; background: <?php echo $bg_color; ?>; transition: all 0.2s ease; cursor: pointer; border-bottom: 1px solid #f1f3f4;" 
                                     onmouseover="this.style.backgroundColor='#e8f4fd'; this.style.transform='translateX(4px)'" 
                                     onmouseout="this.style.backgroundColor='<?php echo $bg_color; ?>'; this.style.transform='translateX(0)'"
                                     onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                    <div style="flex: 1; display: flex; align-items: center;">
                                        <div style="width: 4px; height: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 2px; margin-right: 16px;"></div>
                                        <span style="color: #212529; font-weight: 500; font-size: 14px;"><?php echo htmlspecialchars($tache['titre']); ?></span>
                                    </div>
                                    <div style="width: 30%; text-align: center;">
                                        <span class="badge <?php echo $urgence_class; ?>" style="font-size: 11px; padding: 8px 16px; border-radius: 20px; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"><?php echo htmlspecialchars($tache['urgence']); ?></span>
                                    </div>
                                </div>
                            <?php
                                endforeach;
                            else :
                            ?>
                                <div style="padding: 40px 20px; text-align: center;">
                                    <div style="color: #6c757d; font-size: 16px; margin-bottom: 8px;">
                                        <i class="fas fa-tasks" style="font-size: 24px; opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                                        Aucune tâche en cours
                                    </div>
                                    <p style="color: #9ca3af; font-size: 13px; margin: 0;">Toutes les tâches ont été complétées</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="tab-content" id="mes-taches">
                    <!-- 🎨 DESIGN MODERNE SANS BORDURES -->
                    <div style="padding: 0;">
                        <div style="background: #f8f9fa; padding: 12px 20px; border-radius: 8px 8px 0 0; border-bottom: 1px solid #e9ecef;">
                            <div style="display: flex; align-items: center;">
                                <span style="flex: 1; font-weight: 600; color: #495057; font-size: 13px;">TITRE</span>
                                <span style="width: 30%; text-align: center; font-weight: 600; color: #495057; font-size: 13px;">PRIORITÉ</span>
                            </div>
                        </div>
                        <div style="background: white; border-radius: 0 0 8px 8px; overflow: hidden;">
                            <?php
                            $mes_taches = get_taches_en_cours(10);
                            if (!empty($mes_taches)) :
                                foreach ($mes_taches as $index => $tache) :
                                    $urgence_class = get_urgence_class($tache['urgence']);
                                    $bg_color = $index % 2 === 0 ? '#ffffff' : '#fafbfc';
                            ?>
                                <div data-task-id="<?php echo $tache['id']; ?>" 
                                     style="display: flex; align-items: center; padding: 16px 20px; background: <?php echo $bg_color; ?>; transition: all 0.2s ease; cursor: pointer; border-bottom: 1px solid #f1f3f4;" 
                                     onmouseover="this.style.backgroundColor='#e8f4fd'; this.style.transform='translateX(4px)'" 
                                     onmouseout="this.style.backgroundColor='<?php echo $bg_color; ?>'; this.style.transform='translateX(0)'"
                                     onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                    <div style="flex: 1; display: flex; align-items: center;">
                                        <div style="width: 4px; height: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 2px; margin-right: 16px;"></div>
                                        <span style="color: #212529; font-weight: 500; font-size: 14px;"><?php echo htmlspecialchars($tache['titre']); ?></span>
                                    </div>
                                    <div style="width: 30%; text-align: center;">
                                        <span class="badge <?php echo $urgence_class; ?>" style="font-size: 11px; padding: 8px 16px; border-radius: 20px; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"><?php echo htmlspecialchars($tache['urgence']); ?></span>
                                    </div>
                                </div>
                            <?php
                                endforeach;
                            else :
                            ?>
                                <div style="padding: 40px 20px; text-align: center;">
                                    <div style="color: #6c757d; font-size: 16px; margin-bottom: 8px;">
                                        <i class="fas fa-tasks" style="font-size: 24px; opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                                        Aucune tâche en cours
                                    </div>
                                    <p style="color: #9ca3af; font-size: 13px; margin: 0;">Toutes les tâches ont été complétées</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Réparations récentes -->
        <div class="table-section">
            <div class="table-section-header">
                <h4 class="table-section-title">
                    <i class="fas fa-wrench"></i>
                    <a href="index.php?page=reparations" style="text-decoration: none; color: inherit;">
                        Réparations récentes
                        <span class="badge bg-primary ms-2"><?php echo $reparations_recentes_count; ?></span>
                    </a>
                </h4>
            </div>
            <!-- 🎨 TABLEAU RÉPARATIONS DESIGN MODERNE -->
            <div class="table-container">
                <div style="padding: 0;">
                    <div style="background: #f8f9fa; padding: 12px 20px; border-radius: 8px 8px 0 0; border-bottom: 1px solid #e9ecef;">
                        <div style="display: flex; align-items: center;">
                            <span style="flex: 1; font-weight: 600; color: #495057; font-size: 13px;">CLIENT</span>
                            <span style="width: 35%; font-weight: 600; color: #495057; font-size: 13px;">MODÈLE</span>
                            <span style="width: 25%; text-align: center; font-weight: 600; color: #495057; font-size: 13px;">DATE</span>
                        </div>
                    </div>
                    <div style="background: white; border-radius: 0 0 8px 8px; overflow: hidden;">
                        <?php if (count($reparations_recentes) > 0): ?>
                            <?php foreach ($reparations_recentes as $index => $reparation): ?>
                                <?php $bg_color = $index % 2 === 0 ? '#ffffff' : '#fafbfc'; ?>
                                <div style="display: flex; align-items: center; padding: 16px 20px; background: <?php echo $bg_color; ?>; transition: all 0.2s ease; cursor: pointer; border-bottom: 1px solid #f1f3f4;" 
                                     onmouseover="this.style.backgroundColor='#e8f4fd'; this.style.transform='translateX(4px)'" 
                                     onmouseout="this.style.backgroundColor='<?php echo $bg_color; ?>'; this.style.transform='translateX(0)'"
                                     onclick="window.location.href='index.php?page=reparations&open_modal=<?php echo $reparation['id']; ?>'">
                                    <div style="flex: 1; display: flex; align-items: center;">
                                        <div style="width: 4px; height: 32px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 2px; margin-right: 16px;"></div>
                                        <div style="display: flex; align-items: center;">
                                            <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                                                <i class="fas fa-user" style="color: white; font-size: 12px;"></i>
                                            </div>
                                            <span style="color: #212529; font-weight: 500; font-size: 14px;"><?php echo htmlspecialchars($reparation['client_nom'] ?? 'N/A'); ?></span>
                                        </div>
                                    </div>
                                    <div style="width: 35%; padding-right: 16px;">
                                        <span style="color: #495057; font-weight: 400; font-size: 14px;"><?php echo htmlspecialchars($reparation['modele'] ?? ''); ?></span>
                                    </div>
                                    <div style="width: 25%; text-align: center;">
                                        <div style="background: #f1f3f4; padding: 6px 12px; border-radius: 12px; display: inline-block;">
                                            <span style="color: #6c757d; font-size: 12px; font-weight: 500;"><?php echo format_date($reparation['date_reception'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding: 40px 20px; text-align: center;">
                                <div style="color: #6c757d; font-size: 16px; margin-bottom: 8px;">
                                    <i class="fas fa-wrench" style="font-size: 24px; opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                                    Aucune réparation récente
                                </div>
                                <p style="color: #9ca3af; font-size: 13px; margin: 0;">Aucune réparation en cours actuellement</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commandes récentes -->
        <div class="table-section">
            <div class="table-section-header">
                <h4 class="table-section-title">
                    <i class="fas fa-shopping-cart"></i>
                    <a href="index.php?page=commandes_pieces" style="text-decoration: none; color: inherit;">
                        Commandes à traiter
                    </a>
                </h4>
            </div>
            <!-- 🎨 TABLEAU COMMANDES DESIGN MODERNE -->
            <div class="table-container">
                <div style="padding: 0;">
                    <div style="background: #f8f9fa; padding: 12px 20px; border-radius: 8px 8px 0 0; border-bottom: 1px solid #e9ecef;">
                        <div style="display: flex; align-items: center;">
                            <span style="flex: 1; font-weight: 600; color: #495057; font-size: 13px;">PIÈCE</span>
                            <span style="width: 30%; text-align: center; font-weight: 600; color: #495057; font-size: 13px;">STATUT</span>
                            <span style="width: 25%; text-align: center; font-weight: 600; color: #495057; font-size: 13px;">DATE</span>
                        </div>
                    </div>
                    <div style="background: white; border-radius: 0 0 8px 8px; overflow: hidden;">
                        <?php if (count($commandes_recentes) > 0): ?>
                            <?php foreach ($commandes_recentes as $index => $commande): ?>
                                <?php 
                                $bg_color = $index % 2 === 0 ? '#ffffff' : '#fafbfc';
                                $status_class = '';
                                $status_text = '';
                                $badge_color = '';
                                switch($commande['statut']) {
                                    case 'en_attente':
                                        $status_class = 'warning';
                                        $status_text = 'En attente';
                                        $badge_color = '#ffc107';
                                        break;
                                    case 'commande':
                                        $status_class = 'primary';
                                        $status_text = 'Commandé';
                                        $badge_color = '#0d6efd';
                                        break;
                                    case 'recue':
                                        $status_class = 'success';
                                        $status_text = 'Reçu';
                                        $badge_color = '#198754';
                                        break;
                                    case 'urgent':
                                        $status_class = 'danger';
                                        $status_text = 'URGENT';
                                        $badge_color = '#dc3545';
                                        break;
                                }
                                ?>
                                <div style="display: flex; align-items: center; padding: 16px 20px; background: <?php echo $bg_color; ?>; transition: all 0.2s ease; cursor: pointer; border-bottom: 1px solid #f1f3f4;" 
                                     onmouseover="this.style.backgroundColor='#e8f4fd'; this.style.transform='translateX(4px)'" 
                                     onmouseout="this.style.backgroundColor='<?php echo $bg_color; ?>'; this.style.transform='translateX(0)'">
                                    <div style="flex: 1; display: flex; align-items: center;" title="<?php echo htmlspecialchars($commande['nom_piece']); ?>">
                                        <div style="width: 4px; height: 32px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 2px; margin-right: 16px;"></div>
                                        <div style="display: flex; align-items: center;">
                                            <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                                                <i class="fas fa-cog" style="color: #666; font-size: 12px;"></i>
                                            </div>
                                            <span style="color: #212529; font-weight: 500; font-size: 14px;">
                                                <?php echo mb_strimwidth(htmlspecialchars($commande['nom_piece']), 0, 30, "..."); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div style="width: 30%; text-align: center; padding: 0 8px;">
                                        <span style="background: <?php echo $badge_color; ?>; color: white; padding: 8px 16px; border-radius: 20px; font-size: 11px; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.1); white-space: nowrap;">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                    <div style="width: 25%; text-align: center;">
                                        <div style="background: #f1f3f4; padding: 6px 12px; border-radius: 12px; display: inline-block;">
                                            <span style="color: #6c757d; font-size: 12px; font-weight: 500;"><?php echo format_date($commande['date_creation']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding: 40px 20px; text-align: center;">
                                <div style="color: #6c757d; font-size: 16px; margin-bottom: 8px;">
                                    <i class="fas fa-shopping-cart" style="font-size: 24px; opacity: 0.5; margin-bottom: 12px; display: block;"></i>
                                    Aucune commande récente
                                </div>
                                <p style="color: #9ca3af; font-size: 13px; margin: 0;">Aucune commande en attente de traitement</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles spécifiques pour le modal de recherche client -->
<style>
.avatar-lg {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.client-nom {
    font-size: 1.5rem;
    font-weight: 600;
}

.client-telephone {
    font-size: 1rem;
}

#clientHistoryTabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--gray);
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    background: transparent;
}

#clientHistoryTabs .nav-link.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: transparent;
}

#clientHistoryTabs .nav-link:hover:not(.active) {
    border-bottom-color: #e9ecef;
}
</style>

<!-- Modal de recherche client -->
<div class="modal fade" id="searchClientModal" tabindex="-1" aria-labelledby="searchClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchClientModalLabel">Rechercher un client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="search-container">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="clientSearchInput" placeholder="Nom, téléphone ou email">
                        </div>
                    <div id="searchResults" class="search-results">
                        <!-- Résultats de recherche apparaîtront ici -->
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Indicateurs principaux -->
</div>

<!-- Inclure les scripts pour le dashboard -->
<script src="assets/js/modal-commande.js"></script>
<script src="assets/js/dashboard-commands.js"></script>
<script src="assets/js/client-historique.js"></script>
<script src="assets/js/taches.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>

<!-- Modal pour afficher les détails d'une tâche -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="taskDetailsModalLabel">Détails de la tâche</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="task-detail-container">
                    <div class="mb-3">
                        <h5 id="task-title" class="fw-bold"></h5>
                        <div class="mt-2">
                            <span class="me-2">Priorité:</span>
                            <span id="task-priority" class="fw-medium"></span>
                        </div>
                        <div class="mt-3">
                            <span class="me-2 fw-medium">Description:</span>
                            <p id="task-description" class="mt-2 p-2 rounded">Chargement...</p>
                        </div>
                        <!-- Ajout d'une section pour afficher les erreurs de chargement -->
                        <div id="task-error-container" class="alert alert-danger mt-2" style="display:none;"></div>
                    </div>
                    
                    <div class="task-actions d-flex justify-content-between gap-2 mt-4">
                        <div class="d-flex gap-2">
                            <button id="start-task-btn" class="btn btn-primary" data-task-id="" data-status="en_cours">
                                <i class="fas fa-play me-2"></i>Démarrer
                            </button>
                            <button id="complete-task-btn" class="btn btn-success" data-task-id="" data-status="termine">
                                <i class="fas fa-check me-2"></i>Terminer
                            </button>
                        </div>
                        <a href="index.php?page=taches" id="voir-toutes-taches" class="btn btn-secondary">
                            <i class="fas fa-list-ul me-2"></i>Voir les détails
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


</div>
</div>
</div>