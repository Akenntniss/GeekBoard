<?php
// Copie du contenu PHP complet de mes_missions_harmonieux.php mais avec un design de cartes moderne

// Vérifier si on accède directement à cette page
if (basename($_SERVER['PHP_SELF']) === 'mes_missions_modern_cards.php') {
    header('Location: ../index.php?page=mes_missions');
    exit();
}

// Inclure la configuration de session avant de démarrer la session
require_once __DIR__ . '/../config/session_config.php';

// Inclure la configuration pour la gestion des sous-domaines
require_once __DIR__ . '/../config/subdomain_config.php';

// Inclure les fichiers de configuration et de connexion à la base de données
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification de l'authentification GeekBoard
if (!isset($_SESSION['shop_id'])) {
    header('Location: /pages/login_auto.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login_auto.php');
    exit();
}

// Récupération des informations utilisateur
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? $_SESSION['name'] ?? 'Utilisateur';
$shop_id = $_SESSION['shop_id'];

// Connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Les actions AJAX sont maintenant traitées dans ajax/missions_actions.php

// Récupération des données utilisateur (cagnotte et XP)
$cagnotte_utilisateur = 0.00;
$xp_utilisateur = 0;
try {
    $stmt = $shop_pdo->prepare("SELECT cagnotte, COALESCE(xp_total, 0) as xp FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $cagnotte_utilisateur = $result['cagnotte'];
        $xp_utilisateur = $result['xp'];
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération de la cagnotte: " . $e->getMessage());
}

// Récupération des missions par catégorie BASÉ SUR LA VRAIE STRUCTURE BDD
try {
    // 1. MISSIONS EN COURS - Structure réelle
        $stmt = $shop_pdo->prepare("
            SELECT
                um.id,
                m.titre,
                m.description,
            COALESCE(mt.nom, 'Générale') AS type_nom,
            COALESCE(mt.couleur, '#4361ee') AS couleur,
                m.recompense_euros,
                m.recompense_points,
                m.nombre_taches,
            um.progres as progression,
            um.date_rejointe,
            COALESCE(validations_count.validees, 0) as validations_validees,
            COALESCE(validations_count.en_attente, 0) as validations_en_attente
            FROM user_missions um
            JOIN missions m ON um.mission_id = m.id
            LEFT JOIN mission_types mt ON m.type_id = mt.id
            LEFT JOIN (
                SELECT
                    user_mission_id,
                SUM(CASE WHEN statut = 'validee' THEN 1 ELSE 0 END) as validees,
                SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente
                FROM mission_validations
                GROUP BY user_mission_id
        ) validations_count ON um.id = validations_count.user_mission_id
            WHERE um.user_id = ? AND um.statut = 'en_cours'
            ORDER BY um.date_rejointe DESC
        ");
        $stmt->execute([$user_id]);
        $missions_en_cours = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    // 2. MISSIONS DISPONIBLES - Structure réelle
    $stmt = $shop_pdo->prepare("
        SELECT
            m.id,
            m.titre,
            m.description,
            COALESCE(mt.nom, 'Générale') AS type_nom,
            COALESCE(mt.couleur, '#4361ee') AS couleur,
            m.recompense_euros,
            m.recompense_points,
            m.nombre_taches,
            m.date_fin
        FROM missions m
        LEFT JOIN mission_types mt ON m.type_id = mt.id
        WHERE m.statut = 'active'
          AND m.actif = 1
          AND (m.date_fin IS NULL OR m.date_fin >= CURDATE())
          AND m.id NOT IN (
            SELECT mission_id FROM user_missions WHERE user_id = ?
          )
        ORDER BY m.priorite DESC, m.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $missions_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. MISSIONS COMPLÉTÉES - Structure réelle
    $stmt = $shop_pdo->prepare("
        SELECT
            um.id,
            m.titre,
            m.description,
            COALESCE(mt.nom, 'Générale') AS type_nom,
            COALESCE(mt.couleur, '#4361ee') AS couleur,
            m.recompense_euros,
            m.recompense_points,
            m.nombre_taches,
            um.date_completee
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        LEFT JOIN mission_types mt ON m.type_id = mt.id
        WHERE um.user_id = ? AND um.statut = 'terminee'
        ORDER BY um.date_completee DESC
    ");
    $stmt->execute([$user_id]);
    $missions_completees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. VALIDATIONS EN ATTENTE
    $stmt = $shop_pdo->prepare("
        SELECT COUNT(*) as total
        FROM mission_validations mv
        JOIN user_missions um ON mv.user_mission_id = um.id
        WHERE um.user_id = ? AND mv.statut = 'en_attente'
    ");
    $stmt->execute([$user_id]);
    $validations_en_attente = $stmt->fetchColumn();

} catch (Exception $e) {
    error_log("Erreur lors de la récupération des missions: " . $e->getMessage());
    $missions_en_cours = [];
    $missions_disponibles = [];
    $missions_completees = [];
    $validations_en_attente = 0;
}

// Calcul des statistiques
$total_missions_actives = count($missions_en_cours);
$total_missions_disponibles = count($missions_disponibles);
$total_missions_completees = count($missions_completees);

// Historique des gains pour la modal cagnotte
    $gains_historiques = [];
    $total_gains_euros = 0.0;
    $total_gains_points = 0;
    try {
        $stmt = $shop_pdo->prepare("
            SELECT 
                m.titre as mission_titre,
            m.recompense_euros as euros,
            m.recompense_points as points,
            um.date_completee as date_gain
            FROM user_missions um
            JOIN missions m ON um.mission_id = m.id
        WHERE um.user_id = ? AND um.statut = 'terminee'
        ORDER BY um.date_completee DESC
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
        $gains_historiques = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        foreach ($gains_historiques as $row) {
            $total_gains_euros += (float)($row['euros'] ?? 0);
            $total_gains_points += (int)($row['points'] ?? 0);
        }
    } catch (Exception $e) {
        error_log('Erreur récupération historique cagnotte: ' . $e->getMessage());
}

// Fonction pour obtenir le badge de statut des missions
function getMissionStatusBadge($status) {
    switch ($status) {
        case 'en_cours':
            return '<span class="badge badge-warning"><i class="fas fa-clock"></i> En cours</span>';
        case 'terminee':
            return '<span class="badge badge-success"><i class="fas fa-check"></i> Terminée</span>';
        case 'disponible':
            return '<span class="badge badge-primary"><i class="fas fa-star"></i> Disponible</span>';
        default:
            return '<span class="badge badge-secondary">' . htmlspecialchars($status) . '</span>';
    }
}

function getMissionTypeBadge($type_nom, $couleur) {
    return '<span class="mission-type-badge" style="background-color: ' . htmlspecialchars($couleur) . ';">' . htmlspecialchars($type_nom) . '</span>';
}
?>

<!-- Design basé sur dashboard-new.css + styles des cartes de réparation -->
<link href="assets/css/dashboard-new.css" rel="stylesheet">

<style>
/* Styles inspirés des cartes de réparation */
:root {
    --mission-card-border-radius: 12px;
    --mission-card-shadow: 0 4px 12px rgba(0,0,0,0.08);
    --mission-card-hover-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
    --mission-transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

/* Conteneur principal des cartes */
.mission-cards-container {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem;
    width: 100%;
    margin: 0 auto;
    padding: 1rem 0;
}

/* Style de base pour les cartes de mission */
.dashboard-card.mission-row {
    height: auto;
    transition: var(--mission-transition);
    cursor: pointer;
    border: none;
    border-radius: var(--mission-card-border-radius);
    box-shadow: var(--mission-card-shadow);
    overflow: hidden;
    flex: 1 0 280px;
    max-width: calc(33.333% - 1.25rem);
    min-width: 280px;
    margin-bottom: 1.25rem;
    background: white;
    position: relative;
    display: flex;
    flex-direction: column;
}

.dashboard-card.mission-row:hover {
    transform: translateY(-8px);
    box-shadow: var(--mission-card-hover-shadow);
}

/* Responsive */
@media (max-width: 991px) {
    .dashboard-card.mission-row {
        max-width: calc(50% - 0.75rem);
        min-width: 250px;
        flex: 1 0 250px;
    }
}

@media (max-width: 768px) {
    .dashboard-card.mission-row {
        max-width: 100%;
        min-width: 100%;
        flex: 1 0 100%;
    }
    
    .mission-cards-container {
        gap: 1rem;
    }
}

/* En-tête de la carte */
.dashboard-card .card-header {
    background: linear-gradient(to right, #f8f9fa, #ffffff);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Contenu de la carte */
.dashboard-card .card-content {
    flex: 1;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    background: white;
    position: relative;
}

/* Pied de la carte */
.dashboard-card .card-footer {
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    background-color: #f8f9fa;
    padding: 1rem;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.75rem;
    margin-top: auto;
}

/* Badges et indicateurs */
.mission-type-badge {
    color: white;
    padding: 0.4em 0.8em;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.badge {
    padding: 0.5em 0.75em;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.badge-success {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
}

.badge-warning {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.badge-primary {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
}

.badge-secondary {
    background: rgba(107, 114, 128, 0.1);
    color: #4b5563;
}

/* Barre de progression moderne */
.mission-progress {
    width: 100%;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
    margin: 0.75rem 0;
}

.mission-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 3px;
    transition: width 0.3s ease;
}

/* Info sections dans les cartes */
.mission-info {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
}

.mission-info i {
    width: 20px;
    text-align: center;
    margin-right: 0.5rem;
    opacity: 0.7;
}

.mission-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.mission-description {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 1rem;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.mission-rewards {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.mission-reward-euros {
    font-size: 1.1rem;
    font-weight: 700;
    color: #059669;
}

.mission-reward-points {
    font-size: 0.9rem;
    color: #7c3aed;
    font-weight: 500;
}

/* Boutons d'action dans les cartes */
.mission-action-btn {
    background: linear-gradient(135deg, #4361ee, #3730a3);
    color: white;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    text-decoration: none;
}

.mission-action-btn:hover {
    background: linear-gradient(135deg, #3730a3, #312e81);
    transform: translateY(-1px);
    color: white;
    text-decoration: none;
}

.mission-action-btn.success {
    background: linear-gradient(135deg, #10b981, #059669);
}

.mission-action-btn.success:hover {
    background: linear-gradient(135deg, #059669, #047857);
}

/* Animation d'apparition */
@keyframes fadeInUp {
    from { 
        opacity: 0; 
        transform: translate3d(0, 20px, 0); 
    }
    to { 
        opacity: 1; 
        transform: translate3d(0, 0, 0); 
    }
}

.dashboard-card.mission-row {
    animation: fadeInUp 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.dashboard-card.mission-row:nth-child(1) { animation-delay: 0.05s; }
.dashboard-card.mission-row:nth-child(2) { animation-delay: 0.1s; }
.dashboard-card.mission-row:nth-child(3) { animation-delay: 0.15s; }
.dashboard-card.mission-row:nth-child(4) { animation-delay: 0.2s; }
.dashboard-card.mission-row:nth-child(5) { animation-delay: 0.25s; }
.dashboard-card.mission-row:nth-child(6) { animation-delay: 0.3s; }

/* Ajustements pour onglets */
.tab-content {
    padding: 1rem 0;
}

/* Indicateur visuel quand aucune mission */
.no-missions-container {
    text-align: center;
    padding: 3rem 1rem;
    color: #6b7280;
}

.no-missions-container i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-missions-container h4 {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.no-missions-container p {
    font-size: 0.9rem;
    opacity: 0.8;
}

/* Cartes cliquables */
.mission-row {
    cursor: pointer;
    transition: all 0.3s ease;
}

.mission-row:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.mission-row:active {
    transform: translateY(0);
}

/* Styles pour le modal détails */
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0;
    border-bottom: none;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 9999;
    min-width: 300px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.notification.success {
    background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
}

.notification.error {
    background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
}
</style>

<div class="statistics-container">
    <h2 class="section-title"><i class="fas fa-bullseye"></i> Mes Missions</h2>
    <div class="statistics-grid">
        <a href="#" class="stat-card js-stat-card" data-tab="en-cours" style="text-decoration: none; color: inherit;">
            <div class="stat-icon"><i class="fas fa-play-circle"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_missions_actives; ?></div>
                <div class="stat-label">Missions en cours</div>
            </div>
            <div class="stat-link"><i class="fas fa-arrow-right"></i></div>
        </a>
        <a href="#" class="stat-card progress-card js-stat-card" data-tab="disponibles" style="text-decoration: none; color: inherit;">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_missions_disponibles; ?></div>
                <div class="stat-label">Disponibles</div>
            </div>
            <div class="stat-link"><i class="fas fa-arrow-right"></i></div>
        </a>
        <a href="#" class="stat-card waiting-card js-stat-card" data-tab="en-cours" style="text-decoration: none; color: inherit;">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $validations_en_attente; ?></div>
                <div class="stat-label">Validations en attente</div>
            </div>
            <div class="stat-link"><i class="fas fa-arrow-right"></i></div>
        </a>
        <a href="#" class="stat-card clients-card js-open-cagnotte" style="text-decoration: none; color: inherit;">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($cagnotte_utilisateur, 2); ?> €</div>
                <div class="stat-label">Ma cagnotte</div>
            </div>
            <div class="stat-link"><i class="fas fa-arrow-right"></i></div>
        </a>
    </div>
</div>

<!-- Onglets -->
<div class="tabs-container">
    <div class="tabs-header">
        <button class="tab-button active" data-tab="en-cours">
            <i class="fas fa-play-circle"></i> En cours 
            <span class="badge bg-primary ms-2"><?php echo $total_missions_actives; ?></span>
        </button>
        <button class="tab-button" data-tab="disponibles">
            <i class="fas fa-star"></i> Disponibles 
            <span class="badge bg-primary ms-2"><?php echo $total_missions_disponibles; ?></span>
        </button>
        <button class="tab-button" data-tab="completees">
            <i class="fas fa-trophy"></i> Complétées 
            <span class="badge bg-primary ms-2"><?php echo $total_missions_completees; ?></span>
        </button>
    </div>
</div>

<!-- Contenu des onglets avec design cartes -->
<div class="tab-content-container">
    <!-- Missions en cours -->
    <div id="en-cours" class="tab-content active">
        <?php if (empty($missions_en_cours)): ?>
            <div class="no-missions-container">
                <i class="fas fa-tasks"></i>
                <h4>Aucune mission en cours</h4>
                <p>Consultez les missions disponibles pour commencer</p>
        </div>
        <?php else: ?>
            <div class="mission-cards-container">
            <?php foreach ($missions_en_cours as $mission): ?>
                    <div class="dashboard-card mission-row" onclick="ouvrirDetailsMission(<?php echo $mission['id']; ?>)">
                        <!-- En-tête de la carte -->
                        <div class="card-header">
                            <span class="mission-status">
                                <?php echo getMissionStatusBadge('en_cours'); ?>
                            </span>
                            <span class="mission-reward-euros">
                                <?php echo number_format($mission['recompense_euros'], 2); ?> €
                            </span>
                </div>
                        
                        <!-- Contenu principal -->
                        <div class="card-content">
                            <!-- Titre et type -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></h5>
                                <?php echo getMissionTypeBadge($mission['type_nom'], $mission['couleur']); ?>
                </div>
                            
                            <!-- Description -->
                            <p class="mission-description"><?php echo htmlspecialchars($mission['description']); ?></p>
                            
                            <!-- Progression -->
                            <div class="mission-info">
                                <i class="fas fa-tasks"></i>
                                <span>Progression: <?php echo $mission['validations_validees']; ?>/<?php echo $mission['nombre_taches']; ?> tâches</span>
                </div>
                            
                            <?php 
                            $progress_pct = $mission['nombre_taches'] > 0 ? ($mission['validations_validees'] / $mission['nombre_taches']) * 100 : 0;
                            ?>
                            <div class="mission-progress">
                                <div class="mission-progress-fill" style="width: <?php echo min(100, $progress_pct); ?>%"></div>
                            </div>
                            
                            <?php if ($mission['validations_en_attente'] > 0): ?>
                            <div class="mission-info text-warning">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $mission['validations_en_attente']; ?> validation(s) en attente</span>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Récompenses -->
                            <div class="mission-rewards">
                                <span class="mission-reward-points">
                                    <i class="fas fa-star"></i> <?php echo (int)$mission['recompense_points']; ?> XP
                                </span>
                                <span class="text-muted">
                                    <i class="fas fa-calendar"></i> Depuis le <?php echo date('d/m', strtotime($mission['date_rejointe'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Pied avec action -->
                        <div class="card-footer">
                            <?php if ($mission['validations_validees'] < $mission['nombre_taches']): ?>
                                <a href="javascript:void(0)" class="mission-action-btn" onclick="event.stopPropagation(); validerTache(<?php echo $mission['id']; ?>)">
                                    <i class="fas fa-check"></i> Valider une tâche
                                </a>
                    <?php else: ?>
                                <span class="mission-action-btn success" style="cursor: default;">
                                    <i class="fas fa-trophy"></i> Mission complète
                                </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Missions disponibles -->
    <div id="disponibles" class="tab-content">
        <?php if (empty($missions_disponibles)): ?>
            <div class="no-missions-container">
                <i class="fas fa-star"></i>
                <h4>Aucune mission disponible</h4>
                <p>Revenez plus tard pour de nouvelles missions</p>
        </div>
        <?php else: ?>
            <div class="mission-cards-container">
            <?php foreach ($missions_disponibles as $mission): ?>
                    <div class="dashboard-card mission-row" onclick="ouvrirDetailsMission(<?php echo $mission['id']; ?>)">
                        <!-- En-tête de la carte -->
                        <div class="card-header">
                            <span class="mission-status">
                                <?php echo getMissionStatusBadge('disponible'); ?>
                            </span>
                            <span class="mission-reward-euros">
                                <?php echo number_format($mission['recompense_euros'], 2); ?> €
                            </span>
                </div>
                        
                        <!-- Contenu principal -->
                        <div class="card-content">
                            <!-- Titre et type -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></h5>
                                <?php echo getMissionTypeBadge($mission['type_nom'], $mission['couleur']); ?>
                </div>
                            
                            <!-- Description -->
                            <p class="mission-description"><?php echo htmlspecialchars($mission['description']); ?></p>
                            
                            <!-- Informations -->
                            <div class="mission-info">
                                <i class="fas fa-tasks"></i>
                                <span><?php echo (int)$mission['nombre_taches']; ?> tâche(s) à accomplir</span>
                </div>
                            
                            <?php if ($mission['date_fin']): ?>
                            <div class="mission-info text-warning">
                                <i class="fas fa-clock"></i>
                                <span>Expire le <?php echo date('d/m/Y', strtotime($mission['date_fin'])); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Récompenses -->
                            <div class="mission-rewards">
                                <span class="mission-reward-points">
                                    <i class="fas fa-star"></i> <?php echo (int)$mission['recompense_points']; ?> XP
                                </span>
                            </div>
                        </div>
                        
                        <!-- Pied avec action -->
                        <div class="card-footer">
                            <a href="javascript:void(0)" class="mission-action-btn" onclick="event.stopPropagation(); accepterMission(<?php echo $mission['id']; ?>)">
                                <i class="fas fa-plus"></i> Accepter la mission
                            </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Missions complétées -->
    <div id="completees" class="tab-content">
        <?php if (empty($missions_completees)): ?>
            <div class="no-missions-container">
                <i class="fas fa-trophy"></i>
                <h4>Aucune mission complétée</h4>
                <p>Complétez vos premières missions pour les voir ici</p>
        </div>
        <?php else: ?>
            <div class="mission-cards-container">
            <?php foreach ($missions_completees as $mission): ?>
                    <div class="dashboard-card mission-row" onclick="ouvrirDetailsMission(<?php echo $mission['id']; ?>)">
                        <!-- En-tête de la carte -->
                        <div class="card-header">
                            <span class="mission-status">
                                <?php echo getMissionStatusBadge('terminee'); ?>
                            </span>
                            <span class="mission-reward-euros">
                                <?php echo number_format($mission['recompense_euros'], 2); ?> €
                            </span>
                </div>
                        
                        <!-- Contenu principal -->
                        <div class="card-content">
                            <!-- Titre et type -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mission-title"><?php echo htmlspecialchars($mission['titre']); ?></h5>
                                <?php echo getMissionTypeBadge($mission['type_nom'], $mission['couleur']); ?>
                </div>
                            
                            <!-- Description -->
                            <p class="mission-description"><?php echo htmlspecialchars($mission['description']); ?></p>
                            
                            <!-- Informations -->
                            <div class="mission-info">
                                <i class="fas fa-calendar-check"></i>
                                <span>Terminée le <?php echo date('d/m/Y', strtotime($mission['date_completee'])); ?></span>
                </div>
                            
                            <!-- Récompenses -->
                            <div class="mission-rewards">
                                <span class="mission-reward-points">
                                    <i class="fas fa-star"></i> <?php echo (int)$mission['recompense_points']; ?> XP
                                </span>
                            </div>
                        </div>
                        
                        <!-- Pied -->
                        <div class="card-footer">
                            <span class="mission-action-btn success" style="cursor: default;">
                                <i class="fas fa-trophy"></i> Mission terminée
                            </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de validation de tâche -->
<div class="modal fade" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="validationModalLabel">
                    <i class="fas fa-check-circle me-2"></i>
                    Valider une tâche
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="validationForm" enctype="multipart/form-data">
                    <input type="hidden" id="userMissionId" name="user_mission_id">
                    <div class="mb-3">
                        <label for="description" class="form-label">Description de la tâche accomplie *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  placeholder="Décrivez ce que vous avez fait pour accomplir cette tâche..."
                                  required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="photo" class="form-label">Photo (optionnelle)</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                        <small class="form-text text-muted">Formats acceptés: JPG, PNG, GIF</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" onclick="soumettreValidation()">
                    <i class="fas fa-check"></i>
                    Valider la tâche
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cagnotte (simplifiée) -->
<div class="modal fade" id="cagnotteModal" tabindex="-1" aria-labelledby="cagnotteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cagnotteModalLabel">
                    <i class="fas fa-coins me-2"></i>
                    Ma cagnotte
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <h2 class="display-4 text-success mb-2"><?php echo number_format($cagnotte_utilisateur, 2, ',', ' '); ?> €</h2>
                    <p class="lead">Solde actuel de votre cagnotte</p>
                    <p class="text-muted">Total gagné: <?php echo number_format($total_gains_euros, 2, ',', ' '); ?> € • <?php echo (int)$total_gains_points; ?> XP</p>
                            </div>

                    <?php if (!empty($gains_historiques)): ?>
                <h6 class="mb-3">Historique des gains</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Mission</th>
                                <th>Date</th>
                                <th>Euros</th>
                                <th>Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($gains_historiques, 0, 10) as $gain): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($gain['mission_titre'] ?? ''); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($gain['date_gain'] ?? 'now')); ?></td>
                                <td class="text-success"><?php echo number_format((float)($gain['euros'] ?? 0), 2); ?> €</td>
                                <td class="text-primary"><?php echo (int)($gain['points'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                                </div>
                    <?php endif; ?>
                                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>

<!-- Modal détails de mission -->
<div class="modal fade" id="missionDetailsModal" tabindex="-1" aria-labelledby="missionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="missionDetailsModalLabel">
                    <i class="fas fa-info-circle me-2"></i>
                    Détails de la mission
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
            <div class="modal-body" id="missionDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                                </div>
                    <p class="mt-2">Chargement des détails...</p>
                                </div>
                                </div>
            <div class="modal-footer" id="missionDetailsFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    const statCards = document.querySelectorAll('.js-stat-card');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            if (!tabId) return;

            // Retirer la classe active de tous les boutons et contenus
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // Activer le bouton et l'onglet correspondant
            this.classList.add('active');
            const target = document.getElementById(tabId);
            if (target) target.classList.add('active');
        });
    });

    // Click sur cartes de stats -> bascule l'onglet correspondant
    statCards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.dataset.tab;
            if (!tabId) return;
            
            const btn = document.querySelector(`.tab-button[data-tab="${tabId}"]`);
            if (btn) btn.click();
        });
    });

    // Ouvrir modal cagnotte
    const cagnotteCard = document.querySelector('.js-open-cagnotte');
    if (cagnotteCard) {
        cagnotteCard.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = new bootstrap.Modal(document.getElementById('cagnotteModal'));
            modal.show();
        });
    }
});

// Fonction pour ouvrir les détails d'une mission
function ouvrirDetailsMission(missionId) {
    const modal = new bootstrap.Modal(document.getElementById('missionDetailsModal'));
    
    // Réinitialiser le contenu du modal
    document.getElementById('missionDetailsContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-2">Chargement des détails...</p>
        </div>
    `;
    
    // Ouvrir le modal
    modal.show();
    
    // Charger les détails
    fetch(`ajax/mission_details.php?mission_id=${missionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                afficherDetailsMission(data.mission, data.validations, data.stats);
            } else {
                document.getElementById('missionDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Erreur: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('missionDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Erreur de chargement des détails
                </div>
            `;
        });
}

// Fonction pour afficher les détails de la mission dans le modal
function afficherDetailsMission(mission, validations, stats) {
    const isParticipating = mission.user_mission_id !== null;
    
    // Déterminer la couleur du badge de statut
    const getStatutBadge = (statut) => {
        switch(statut) {
            case 'disponible': return '<span class="badge bg-primary"><i class="fas fa-star"></i> Disponible</span>';
            case 'en_cours': return '<span class="badge bg-warning"><i class="fas fa-clock"></i> En cours</span>';
            case 'terminee': return '<span class="badge bg-success"><i class="fas fa-check"></i> Terminée</span>';
            default: return '<span class="badge bg-secondary">' + statut + '</span>';
        }
    };
    
    const getPriorityBadge = (priorite) => {
        switch(priorite) {
            case 1: return '<span class="badge bg-danger">Haute</span>';
            case 2: return '<span class="badge bg-warning">Moyenne</span>';
            case 3: return '<span class="badge bg-secondary">Basse</span>';
            default: return '<span class="badge bg-info">Normale</span>';
        }
    };
    
    let content = `
        <div class="row">
            <!-- Colonne principale -->
            <div class="col-md-8">
                <!-- En-tête de mission -->
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="mb-2">${mission.titre}</h4>
                        <div class="d-flex gap-2 flex-wrap">
                            ${getStatutBadge(stats.statut_utilisateur)}
                            ${getPriorityBadge(mission.priorite)}
                            <span class="badge" style="background-color: ${mission.type_couleur}; color: white;">
                                <i class="${mission.type_icone}"></i> ${mission.type_nom}
                            </span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="h5 text-success mb-0">${parseFloat(mission.recompense_euros).toLocaleString('fr-FR', {minimumFractionDigits: 2})} €</div>
                        <small class="text-muted">+ ${mission.recompense_points} XP</small>
                    </div>
                </div>
                
                <!-- Description -->
                <div class="mb-4">
                    <h6><i class="fas fa-info-circle text-primary"></i> Description</h6>
                    <p class="mb-0">${mission.description || 'Aucune description disponible'}</p>
                </div>
                
                <!-- Informations détaillées -->
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h6><i class="fas fa-tasks text-info"></i> Objectifs</h6>
                        <ul class="list-unstyled">
                            <li><strong>Tâches à accomplir:</strong> ${mission.nombre_taches}</li>
                            <li><strong>Objectif quantité:</strong> ${mission.objectif_quantite}</li>
                        </ul>
                    </div>
                    <div class="col-sm-6">
                        <h6><i class="fas fa-calendar text-warning"></i> Dates</h6>
                        <ul class="list-unstyled">
                            <li><strong>Début:</strong> ${mission.date_debut_fr || 'Non définie'}</li>
                            <li><strong>Fin:</strong> ${mission.date_fin_fr || 'Pas de limite'}</li>
                            ${stats.jours_restants !== null ? `<li><strong>Jours restants:</strong> <span class="${stats.jours_restants < 7 ? 'text-danger' : 'text-success'}">${stats.jours_restants}</span></li>` : ''}
                        </ul>
                    </div>
                </div>
    `;
    
    // Section participation utilisateur
    if (isParticipating) {
        content += `
            <div class="mb-4">
                <h6><i class="fas fa-user-check text-success"></i> Votre participation</h6>
                <div class="row">
                    <div class="col-sm-6">
                        <ul class="list-unstyled">
                            <li><strong>Rejoint le:</strong> ${mission.date_rejointe_fr}</li>
                            <li><strong>Progression:</strong> ${mission.validations_approuvees}/${mission.nombre_taches} tâches</li>
                        </ul>
                    </div>
                    <div class="col-sm-6">
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-success" style="width: ${stats.progression_pct}%">${stats.progression_pct}%</div>
                        </div>
                        <small class="text-muted">
                            ${mission.validations_soumises} validation(s) soumise(s) • 
                            ${mission.validations_en_attente} en attente
                        </small>
                    </div>
                </div>
            </div>
        `;
        
        // Historique des validations
        if (validations.length > 0) {
            content += `
                <div class="mb-4">
                    <h6><i class="fas fa-history text-info"></i> Historique des validations</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tâche #</th>
                                    <th>Description</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            validations.forEach(validation => {
                const statutBadge = validation.statut === 'validee' ? 
                    '<span class="badge bg-success">Validée</span>' :
                    validation.statut === 'refusee' ?
                    '<span class="badge bg-danger">Refusée</span>' :
                    '<span class="badge bg-warning">En attente</span>';
                    
                content += `
                    <tr>
                        <td>${validation.tache_numero}</td>
                        <td>${validation.description.substring(0, 50)}${validation.description.length > 50 ? '...' : ''}</td>
                        <td>${statutBadge}</td>
                        <td>${new Date(validation.date_soumission).toLocaleDateString('fr-FR')}</td>
                    </tr>
                `;
            });
            
            content += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
    }
    
    content += `
            </div>
            
            <!-- Colonne latérale -->
            <div class="col-md-4">
                <!-- Statistiques -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Statistiques</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Participants totaux</small>
                            <div class="fw-bold">${mission.total_participants}</div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Missions terminées</small>
                            <div class="fw-bold">${mission.participants_termines}</div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Taux de réussite</small>
                            <div class="progress" style="height: 15px;">
                                <div class="progress-bar" style="width: ${stats.popularite_pct}%">${stats.popularite_pct}%</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informations techniques -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-cog"></i> Informations</h6>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">
                            <div><strong>ID:</strong> #${mission.id}</div>
                            <div><strong>Créée le:</strong> ${mission.created_at_fr}</div>
                            <div><strong>Statut:</strong> ${mission.statut}</div>
                            <div><strong>Active:</strong> ${mission.actif == 1 ? 'Oui' : 'Non'}</div>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('missionDetailsContent').innerHTML = content;
    
    // Mettre à jour le footer avec les actions appropriées
    let footerContent = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>';
    
    if (!isParticipating && stats.statut_utilisateur === 'disponible') {
        footerContent = `
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            <button type="button" class="btn btn-primary" onclick="accepterMissionDepuisModal(${mission.id})">
                <i class="fas fa-plus"></i> Accepter la mission
            </button>
        `;
    } else if (isParticipating && mission.validations_approuvees < mission.nombre_taches) {
        footerContent = `
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            <button type="button" class="btn btn-success" onclick="validerTacheDepuisModal(${mission.user_mission_id})">
                <i class="fas fa-check"></i> Valider une tâche
            </button>
        `;
    }
    
    document.getElementById('missionDetailsFooter').innerHTML = footerContent;
}

// Fonctions pour les actions depuis le modal
function accepterMissionDepuisModal(missionId) {
    bootstrap.Modal.getInstance(document.getElementById('missionDetailsModal')).hide();
    accepterMission(missionId);
}

function validerTacheDepuisModal(userMissionId) {
    bootstrap.Modal.getInstance(document.getElementById('missionDetailsModal')).hide();
    validerTache(userMissionId);
}

// Fonction pour accepter une mission
function accepterMission(missionId) {
    fetch('ajax/missions_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=accepter_mission&mission_id=${missionId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de connexion', 'error');
    });
}

// Fonction pour valider une tâche
function validerTache(userMissionId) {
    document.getElementById('userMissionId').value = userMissionId;
    new bootstrap.Modal(document.getElementById('validationModal')).show();
}

// Fonction pour soumettre la validation
function soumettreValidation() {
    const form = document.getElementById('validationForm');
    const formData = new FormData(form);
    formData.append('action', 'valider_tache');
    
    fetch('ajax/missions_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('validationModal')).hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de connexion', 'error');
    });
}

// Fonction pour afficher les notifications
function showNotification(message, type) {
    // Supprimer les notifications existantes
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        z-index: 10000;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        font-weight: 500;
        min-width: 300px;
    `;
    notification.innerHTML = `
        ${message}
        <button type="button" onclick="this.parentElement.remove()" 
                style="background: none; border: none; color: white; margin-left: 1rem; cursor: pointer; font-size: 1.2rem;">×</button>
    `;
    
    document.body.appendChild(notification);
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}
</script>
