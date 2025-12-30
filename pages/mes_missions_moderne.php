<?php
// Vérification simplifiée pour test (comme les autres pages qui fonctionnent)
if (!isset($_SESSION['user_id'])) {
    // Initialiser une session de test si pas de session active
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['full_name'] = 'Administrateur';
}

// S'assurer que le shop_id est défini pour mkmkmk
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 63; // mkmkmk
}

$user_id = $_SESSION['user_id'];
$shop_pdo = getShopDBConnection();

// Traitement de l'inscription à une mission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'rejoindre_mission') {
        $mission_id = (int)$_POST['mission_id'];
        
        try {
            // Vérifier si l'utilisateur n'est pas déjà inscrit
            $stmt = $shop_pdo->prepare("SELECT id FROM user_missions WHERE user_id = ? AND mission_id = ?");
            $stmt->execute([$user_id, $mission_id]);
            
            if (!$stmt->fetch()) {
                // Inscrire l'utilisateur à la mission
                $stmt = $shop_pdo->prepare("INSERT INTO user_missions (user_id, mission_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $mission_id]);
                set_message("Vous avez rejoint la mission avec succès !", "success");
            } else {
                set_message("Vous participez déjà à cette mission.", "warning");
            }
        } catch (PDOException $e) {
            set_message("Erreur lors de l'inscription à la mission: " . $e->getMessage(), "error");
        }
    }
    
    if ($_POST['action'] === 'soumettre_tache') {
        $user_mission_id = (int)$_POST['user_mission_id'];
        $mission_id = (int)$_POST['mission_id'];
        $description = cleanInput($_POST['description_tache']);
        $preuve_text = cleanInput($_POST['preuve_text']);
        
        if (empty($description)) {
            set_message("La description de la tâche est obligatoire.", "error");
        } else {
            try {
                // Vérifier que la mission appartient à l'utilisateur et est en cours
                $stmt = $shop_pdo->prepare("
                    SELECT um.progression, um.statut, m.objectif_nombre 
                    FROM user_missions um 
                    JOIN missions m ON um.mission_id = m.id 
                    WHERE um.id = ? AND um.user_id = ?
                ");
                $stmt->execute([$user_mission_id, $user_id]);
                $current_progress = $stmt->fetch();
                
                if (!$current_progress) {
                    set_message("Mission non trouvée.", "error");
                } elseif ($current_progress['statut'] !== 'en_cours') {
                    set_message("Cette mission n'est plus en cours.", "error");
                } elseif ($current_progress['progression'] >= $current_progress['objectif_nombre']) {
                    set_message("Cette mission est déjà complète !", "error");
                } else {
                    // Calculer le numéro de tâche
                    $tache_numero = $current_progress['progression'] + 1;
                    
                    // Insérer la demande de validation (statut: en_attente)
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO mission_validations (user_mission_id, tache_numero, description, preuve_text, statut) 
                        VALUES (?, ?, ?, ?, 'en_attente')
                    ");
                    $stmt->execute([$user_mission_id, $tache_numero, $description, $preuve_text]);
                    
                    set_message("Tâche soumise avec succès ! En attente de validation par l'administrateur.", "success");
                }
                
            } catch (PDOException $e) {
                set_message("Erreur lors de la soumission: " . $e->getMessage(), "error");
            }
        }
    }
    
    redirect('mes_missions_moderne');
}

// Récupération des missions disponibles (non encore rejointes)
try {
    $stmt = $shop_pdo->prepare("
        SELECT m.*, mt.nom as type_nom, mt.icon, mt.couleur
        FROM missions m
        JOIN mission_types mt ON m.mission_type_id = mt.id
        WHERE m.statut = 'active' 
        AND (m.date_fin IS NULL OR m.date_fin >= CURDATE())
        AND m.id NOT IN (
            SELECT mission_id FROM user_missions WHERE user_id = ?
        )
        ORDER BY m.date_fin ASC, m.recompense_euros DESC
    ");
    $stmt->execute([$user_id]);
    $missions_disponibles = $stmt->fetchAll();
} catch (PDOException $e) {
    $missions_disponibles = [];
    set_message("Erreur lors de la récupération des missions disponibles.", "error");
}

// Récupération des missions en cours
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.*, m.titre, m.description, m.objectif_nombre, m.recompense_euros, m.recompense_points,
               m.date_fin, mt.nom as type_nom, mt.icon, mt.couleur,
               um.progression as progression_actuelle,
               COUNT(mv.id) as total_soumissions
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.mission_type_id = mt.id
        LEFT JOIN mission_validations mv ON um.id = mv.user_mission_id
        WHERE um.user_id = ? AND um.statut = 'en_cours'
        GROUP BY um.id
        ORDER BY m.date_fin ASC
    ");
    $stmt->execute([$user_id]);
    $missions_en_cours = $stmt->fetchAll();
} catch (PDOException $e) {
    $missions_en_cours = [];
    set_message("Erreur lors de la récupération des missions en cours.", "error");
}

// Récupération des missions complétées
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.*, m.titre, m.objectif_nombre, m.recompense_euros, m.recompense_points,
               m.date_fin, mt.nom as type_nom, mt.icon, mt.couleur,
               m.recompense_euros as gain_reel, m.recompense_points as points_reels
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.mission_type_id = mt.id
        WHERE um.user_id = ? AND um.statut = 'terminee'
        ORDER BY um.date_completion DESC
    ");
    $stmt->execute([$user_id]);
    $missions_completees = $stmt->fetchAll();
} catch (PDOException $e) {
    $missions_completees = [];
    error_log("Erreur récupération missions complétées: " . $e->getMessage());
}

// Récupération des soumissions en attente de validation
try {
    $stmt = $shop_pdo->prepare("
        SELECT 
            mv.id, mv.tache_numero, mv.description, mv.preuve_text, mv.statut, mv.created_at,
            m.titre as mission_titre, m.objectif_nombre,
            um.id as user_mission_id, um.progression,
            mt.nom as type_nom, mt.icon, mt.couleur
        FROM mission_validations mv
        JOIN user_missions um ON mv.user_mission_id = um.id
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.mission_type_id = mt.id
        WHERE um.user_id = ? AND mv.statut = 'en_attente'
        ORDER BY mv.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $soumissions_en_attente = $stmt->fetchAll();
} catch (PDOException $e) {
    $soumissions_en_attente = [];
    error_log("Erreur récupération soumissions en attente: " . $e->getMessage());
}

// Calcul des statistiques personnelles
$stats = [
    'missions_actives' => count($missions_en_cours),
    'missions_disponibles' => count($missions_disponibles),
    'missions_completees' => count($missions_completees),
    'soumissions_en_attente' => count($soumissions_en_attente),
    'total_gains' => array_sum(array_column($missions_completees, 'gain_reel')),
    'total_points' => array_sum(array_column($missions_completees, 'points_reels'))
];
?>

<style>
/* FIX NAVBAR - Obligatoire pour affichage correct */
/* Masquer dock mobile sur desktop */
@media (min-width: 992px) {
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    /* Forcer navbar desktop visible */
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
    /* Surcharger navbar-servo-fix.css */
    body #desktop-navbar, html body #desktop-navbar {
        height: 60px !important;
        min-height: 60px !important;
        max-height: 60px !important;
    }
    /* Éléments navbar visibles */
    #desktop-navbar * {
        visibility: visible !important;
        opacity: 1 !important;
    }
    /* Container navbar avec centrage vertical parfait */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.75rem 1rem !important; /* Augmenté à 0.75rem pour plus de centrage */
        min-height: 60px !important;
    }
    /* Logo avec centrage vertical parfait */
    #desktop-navbar .navbar-brand {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        line-height: 1 !important;
    }
    #desktop-navbar .navbar-brand img {
        height: 32px !important; /* Encore réduit pour plus d'espace vertical */
        width: auto !important;
        vertical-align: middle !important;
    }
    /* Boutons avec centrage vertical parfait */
    #desktop-navbar .btn,
    #desktop-navbar .navbar-nav .nav-link,
    #desktop-navbar .dropdown-toggle {
        display: flex !important;
        align-items: center !important;
        height: auto !important;
        padding: 0.375rem 0.75rem !important; /* Padding encore plus réduit */
        margin: 0.125rem 0.25rem !important; /* Marges ajustées */
        line-height: 1.2 !important;
        vertical-align: middle !important;
    }
    /* Correction spécifique pour les icônes dans les boutons */
    #desktop-navbar .btn i,
    #desktop-navbar .navbar-nav .nav-link i,
    #desktop-navbar .dropdown-toggle i {
        vertical-align: middle !important;
        line-height: 1 !important;
    }
    /* Messages de bienvenue centrés */
    #desktop-navbar .d-none.d-md-flex {
        display: flex !important;
        align-items: center !important;
        height: 100% !important;
    }
    /* Forcer l'alignement vertical pour tous les éléments flex */
    #desktop-navbar .d-flex {
        align-items: center !important;
    }
    /* Animation SERVO centrée parfaitement */
    body .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 10001 !important;
        display: flex !important;
        align-items: center !important;
    }
    /* Réserver espace navbar */
    body {
        padding-top: 80px !important;
        margin: 0 !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        overflow-x: hidden !important;
    }
}

/* Styles généraux navbar (mobile + desktop) */
#desktop-navbar, nav#desktop-navbar {
    display: block !important;
    visibility: visible !important;
    position: fixed !important;
    top: 0 !important;
    z-index: 10000 !important;
}

/* Masquer navbar sur mobile */
@media (max-width: 767px) {
    #desktop-navbar, nav#desktop-navbar {
        display: none !important;
    }
}

/* ========================================
   VARIABLES CSS POUR LES THÈMES
======================================== */
:root {
    /* Mode Jour - Moderne Dynamique */
    --day-primary: #3b82f6;
    --day-secondary: #8b5cf6;
    --day-accent: #06b6d4;
    --day-bg: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    --day-bg-animated: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff);
    --day-card-bg: rgba(255, 255, 255, 0.95);
    --day-text: #1e293b;
    --day-text-light: #64748b;
    --day-shadow: rgba(59, 130, 246, 0.15);
    --day-border: rgba(148, 163, 184, 0.2);

    /* Mode Nuit - Futuriste */
    --night-primary: #00d4ff;
    --night-secondary: #7c3aed;
    --night-accent: #ff00aa;
    --night-bg: #0a0a0a;
    --night-bg-animated: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483);
    --night-card-bg: rgba(15, 15, 25, 0.95);
    --night-text: #ffffff;
    --night-text-light: #a0aec0;
    --night-shadow: rgba(0, 212, 255, 0.25);
    --night-border: rgba(0, 212, 255, 0.3);
    --night-glow: 0 0 20px rgba(0, 212, 255, 0.5);
}

/* ========================================
   STRUCTURE DE BASE
======================================== */
body {
    margin: 0;
    padding: 0;
    padding-top: 80px; /* Espace pour la navbar fixe */
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow-x: hidden;
}

.modern-dashboard {
    position: relative;
    min-height: 100vh;
    padding: 1rem;
    transition: all 0.3s ease;
    margin-top: -80px; /* Remonter sous la navbar */
    padding-top: calc(80px + 1rem); /* Compenser avec padding */
}

/* ========================================
   ANIMATIONS DE FOND
======================================== */
.bg-animated {
    background: var(--day-bg-animated);
    background-size: 300% 300%;
    animation: gradientFlow 20s ease infinite;
}

.bg-animated.night-mode {
    background: var(--night-bg-animated);
    background-size: 400% 400%;
}

@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* ========================================
   ANIMATIONS MODERNES
======================================== */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.fade-in {
    animation: fadeIn 0.6s ease-out;
}

/* ========================================
   EN-TÊTE MODERNE
======================================== */
.modern-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--day-card-bg);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    animation: slideInUp 0.6s ease-out;
}

.modern-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--day-text);
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0;
}

.modern-title i {
    color: var(--day-primary);
    font-size: 2rem;
}

/* ========================================
   STATISTIQUES MODERNES
======================================== */
.modern-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.modern-stat-card {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px var(--day-shadow);
    transition: all 0.3s ease;
    animation: slideInUp 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

.modern-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary), var(--day-accent));
    background-size: 200% 100%;
    animation: gradientFlow 3s ease infinite;
}

.modern-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px var(--day-shadow);
}

.stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1e293b !important; /* Noir en mode jour - Priorité forte */
    margin: 0;
    line-height: 1;
}

.stat-label {
    color: var(--day-text-light);
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0.5rem 0 0;
}

/* ========================================
   ONGLETS MODERNES
======================================== */
.modern-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    padding: 0.5rem;
    background: var(--day-card-bg);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    animation: slideInUp 0.6s ease-out;
}

.modern-tab {
    flex: 1;
    padding: 1rem 1.5rem;
    border-radius: 15px;
    border: none;
    background: transparent;
    color: var(--day-text-light);
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    position: relative;
    overflow: hidden;
}

.modern-tab::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.modern-tab:hover::before {
    left: 100%;
}

.modern-tab.active {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
}

.modern-tab .badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    min-width: 20px;
    text-align: center;
}

.modern-tab:not(.active) .badge {
    background: var(--day-primary);
    color: white;
}

/* ========================================
   CONTENU DES ONGLETS
======================================== */
.modern-tab-content {
    display: none;
    animation: fadeIn 0.4s ease-out;
}

.modern-tab-content.active {
    display: block;
}

/* ========================================
   CARTES DE MISSION MODERNES
======================================== */
.missions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
}

.mission-card {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px var(--day-shadow);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.mission-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #10b981, #059669);
    background-size: 200% 100%;
    animation: gradientFlow 3s ease infinite;
}

.mission-card.mission-available::before {
    background: linear-gradient(90deg, var(--day-primary), var(--day-secondary));
}

.mission-card.mission-completed::before {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.mission-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px var(--day-shadow);
}

.mission-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.mission-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.75rem;
    font-weight: 600;
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    color: white;
}

.mission-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--day-text);
    margin: 0.75rem 0 0.5rem;
    line-height: 1.3;
}

.mission-description {
    color: var(--day-text-light);
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.mission-progress {
    margin-bottom: 1rem;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: rgba(148, 163, 184, 0.2);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.progress-text {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
    color: var(--day-text-light);
}

.mission-submissions {
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: var(--day-bg);
    border: 1px solid var(--day-border);
    border-radius: 12px;
}

.submissions-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--day-text);
    font-size: 0.9rem;
    font-weight: 500;
}

.submissions-info i {
    color: var(--day-primary);
    font-size: 1rem;
}

.submissions-info span {
    color: var(--day-text);
}

/* Mode nuit pour les soumissions */
body.night-mode .mission-submissions {
    background: var(--night-bg);
    border-color: var(--night-border);
}

body.night-mode .submissions-info,
body.night-mode .submissions-info span {
    color: var(--night-text);
}

body.night-mode .submissions-info i {
    color: var(--night-primary);
}

.mission-rewards {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: rgba(59, 130, 246, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(59, 130, 246, 0.1);
}

.reward-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--day-text);
}

.reward-item i {
    color: var(--day-primary);
}

.mission-actions {
    display: flex;
    gap: 0.75rem;
}

.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    position: relative;
    overflow: hidden;
}

.modern-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.modern-btn:hover::before {
    left: 100%;
}

.modern-btn:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}

.modern-btn--success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.modern-btn--success:hover {
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.modern-btn--warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.modern-btn--warning:hover {
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
}

.modern-btn--secondary {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
}

.modern-btn--secondary:hover {
    box-shadow: 0 8px 25px rgba(107, 114, 128, 0.4);
}

/* ========================================
   ÉTAT VIDE
======================================== */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--day-text-light);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 0.5rem;
}

.empty-state p {
    font-size: 1rem;
    margin: 0;
}

/* ========================================
   MODAL MODERNE
======================================== */
.modern-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    animation: fadeIn 0.3s ease;
}

.modern-modal.show {
    display: flex;
}

.modern-modal-dialog {
    background: var(--day-card-bg);
    border-radius: 20px;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    animation: slideInUp 0.3s ease;
    position: relative;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
}

.modern-modal-header {
    padding: 2rem 2rem 0;
    border-bottom: 1px solid var(--day-border);
    margin-bottom: 1.5rem;
}

.modern-modal-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--day-text);
    margin: 0 0 1.5rem;
}

.modern-modal-body {
    padding: 0 2rem 2rem;
}

.modern-form-group {
    margin-bottom: 1.5rem;
}

.modern-form-label {
    display: block;
    font-weight: 600;
    color: var(--day-text);
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
}

.modern-form-input {
    width: 100%;
    padding: 0.875rem;
    border: 2px solid var(--day-border);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.8);
    color: var(--day-text);
    font-size: 1rem;
    transition: all 0.2s ease;
    backdrop-filter: blur(10px);
}

.modern-form-input:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    background: rgba(255, 255, 255, 1);
}

.modern-form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--day-border);
}

/* ========================================
   RESPONSIVE
======================================== */
@media (max-width: 768px) {
    .modern-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .modern-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .modern-tabs {
        flex-direction: column;
    }
    
    .missions-grid {
        grid-template-columns: 1fr;
    }
    
    .modern-title {
        font-size: 2rem;
    }
    
    .mission-actions {
        flex-direction: column;
    }
}

/* ========================================
   MODE NUIT
======================================== */
body.night-mode {
    --day-primary: var(--night-primary);
    --day-secondary: var(--night-secondary);
    --day-accent: var(--night-accent);
    --day-card-bg: var(--night-card-bg);
    --day-text: var(--night-text);
    --day-text-light: var(--night-text-light);
    --day-shadow: var(--night-shadow);
    --day-border: var(--night-border);
    
    /* Rendre le body transparent pour voir #animated-bg */
    background: transparent !important;
}

body.night-mode .bg-animated {
    background: var(--night-bg-animated);
}

body.night-mode .modern-header,
body.night-mode .modern-stat-card,
body.night-mode .modern-tabs,
body.night-mode .mission-card,
body.night-mode .modern-modal-dialog {
    background: var(--night-card-bg);
    color: var(--night-text);
    border: 1px solid var(--night-border);
    box-shadow: 0 8px 32px var(--night-shadow);
}

/* ========================================
   CARTES DE SOUMISSIONS EN ATTENTE
======================================== */
.submissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.submission-card {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px var(--day-shadow);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.submission-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #f59e0b, #d97706);
    background-size: 200% 100%;
    animation: gradientFlow 3s ease infinite;
}

.submission-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px var(--day-shadow);
}

.submission-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--day-border);
}

.mission-type-badge-small {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.submission-mission-info {
    flex: 1;
    min-width: 0;
}

.submission-mission-info h4 {
    margin: 0 0 0.25rem 0;
    color: var(--day-text);
    font-size: 1.1rem;
    font-weight: 600;
    word-wrap: break-word;
}

.submission-type {
    margin: 0;
    color: var(--day-text-light);
    font-size: 0.85rem;
}

.submission-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 500;
    white-space: nowrap;
}

.submission-status-badge.pending {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.submission-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.submission-task-info {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.task-number,
.task-progress {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--day-bg);
    border: 1px solid var(--day-border);
    border-radius: 12px;
    font-size: 0.9rem;
    color: var(--day-text-light);
}

.task-number i,
.task-progress i {
    color: var(--day-primary);
}

.submission-description,
.submission-proof {
    padding: 1rem;
    background: var(--day-bg);
    border-radius: 12px;
    border: 1px solid var(--day-border);
}

.submission-description h5,
.submission-proof h5 {
    margin: 0 0 0.5rem 0;
    color: var(--day-text);
    font-size: 0.95rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.submission-description p,
.submission-proof p {
    margin: 0;
    color: var(--day-text-light);
    line-height: 1.6;
    font-size: 0.9rem;
}

.submission-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--day-text-light);
    font-size: 0.85rem;
    padding-top: 0.5rem;
    border-top: 1px solid var(--day-border);
}

.submission-date i {
    color: var(--day-primary);
}

/* Mode nuit pour les cartes de soumissions */
body.night-mode .submission-card {
    background: var(--night-card-bg);
    border-color: var(--night-border);
    box-shadow: 0 8px 32px var(--night-shadow);
}

body.night-mode .submission-header {
    border-color: var(--night-border);
}

body.night-mode .submission-mission-info h4 {
    color: var(--night-text);
}

body.night-mode .submission-type,
body.night-mode .task-number,
body.night-mode .task-progress,
body.night-mode .submission-description p,
body.night-mode .submission-proof p,
body.night-mode .submission-date {
    color: var(--night-text-light);
}

body.night-mode .submission-description,
body.night-mode .submission-proof,
body.night-mode .task-number,
body.night-mode .task-progress {
    background: var(--night-bg);
    border-color: var(--night-border);
}

body.night-mode .submission-description h5,
body.night-mode .submission-proof h5 {
    color: var(--night-text);
}

body.night-mode .submission-date {
    border-color: var(--night-border);
}

/* Responsive pour les cartes de soumissions */
@media (max-width: 768px) {
    .submissions-grid {
        grid-template-columns: 1fr;
    }
    
    .submission-header {
        flex-wrap: wrap;
    }
    
    .submission-task-info {
        flex-direction: column;
        gap: 0.5rem;
    }
}

body.night-mode .modern-title {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

body.night-mode .modern-form-input {
    background: rgba(15, 23, 42, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .modern-form-input:focus {
    background: rgba(15, 23, 42, 0.9);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

/* Règle spécifique pour mode jour */
body:not(.night-mode) .stat-value {
    color: #1e293b !important; /* Noir en mode jour */
}

body.night-mode .stat-value {
    color: var(--night-text) !important; /* Blanc en mode nuit - Priorité forte */
}

body.night-mode .mission-rewards {
    background: rgba(0, 212, 255, 0.1);
    border-color: rgba(0, 212, 255, 0.2);
}

/* ========================================
   NAVBAR EN MODE NUIT
======================================== */
body.night-mode #desktop-navbar,
body.night-mode nav#desktop-navbar,
body.night-mode .navbar {
    background: var(--night-card-bg) !important;
    border-bottom: 1px solid var(--night-border) !important;
    box-shadow: 0 2px 10px var(--night-shadow) !important;
}

body.night-mode #desktop-navbar .navbar-brand,
body.night-mode #desktop-navbar .nav-link,
body.night-mode #desktop-navbar .navbar-text {
    color: var(--night-text) !important;
}

body.night-mode #desktop-navbar .nav-link:hover {
    color: var(--night-primary) !important;
}

body.night-mode #desktop-navbar .servo-logo-container .servo-text,
body.night-mode #desktop-navbar .servo-logo-container .animated-text {
    color: var(--night-primary) !important;
}

/* Corrections pour les éléments de navigation en mode nuit */
body.night-mode .navbar-nav .nav-item .nav-link {
    color: var(--night-text) !important;
}

body.night-mode .navbar-nav .nav-item .nav-link:hover,
body.night-mode .navbar-nav .nav-item .nav-link:focus {
    color: var(--night-primary) !important;
}

/* Corrections pour les boutons de la navbar en mode nuit */
body.night-mode #desktop-navbar .btn {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary)) !important;
    color: var(--night-text) !important;
    border: 1px solid var(--night-border) !important;
}

body.night-mode #desktop-navbar .btn:hover {
    background: linear-gradient(135deg, var(--night-secondary), var(--night-primary)) !important;
    box-shadow: var(--night-glow) !important;
}

/* Corrections pour les dropdowns en mode nuit */
body.night-mode .dropdown-menu {
    background: var(--night-card-bg) !important;
    border: 1px solid var(--night-border) !important;
    box-shadow: 0 8px 32px var(--night-shadow) !important;
}

body.night-mode .dropdown-item {
    color: var(--night-text) !important;
}

body.night-mode .dropdown-item:hover,
body.night-mode .dropdown-item:focus {
    background: rgba(0, 212, 255, 0.1) !important;
    color: var(--night-primary) !important;
}

/* ========================================
   FIX NAVBAR & ANIMATION SERVO
   ======================================== */
@media (min-width: 992px) {
    /* Masquer le dock mobile sur desktop */
    #mobile-dock, #dock-recall-zone {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -1 !important;
    }
    
    /* S'assurer que la navbar desktop est visible */
    #desktop-navbar, nav#desktop-navbar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 1030 !important;
        width: 100% !important;
    }
    
    /* Container fluid de la navbar */
    #desktop-navbar .container-fluid {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        height: 100% !important;
        padding: 0.5rem 1rem !important;
        min-height: 60px !important;
    }
    
    /* Logo SERVO - CENTRÉ horizontalement ET verticalement */
    .servo-logo-container {
        position: absolute !important;
        left: 50% !important;
        top: 50% !important;
        transform: translate(-50%, -50%) !important;
        z-index: 1031 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    /* S'assurer que le loader SERVO est visible */
    .servo-logo-container .loader {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Animations SVG pour toutes les lettres SERVO */
    .servo-logo-container .dash {
        animation: dashArray 2s ease-in-out infinite, dashOffset 2s linear infinite !important;
    }
    
    .servo-logo-container .spin {
        animation: spinDashArray 2s ease-in-out infinite, spin 8s ease-in-out infinite, dashOffset 2s linear infinite !important;
        transform-origin: center;
    }
    
    /* Keyframes pour l'animation .dash (S, E, R, V) */
    @keyframes dashArray {
        0% { stroke-dasharray: 0 1 359 0; }
        50% { stroke-dasharray: 0 359 1 0; }
        100% { stroke-dasharray: 359 1 0 0; }
    }
    
    /* Keyframes pour l'animation .spin (O) */
    @keyframes spinDashArray {
        0% { stroke-dasharray: 270 90; }
        50% { stroke-dasharray: 0 360; }
        100% { stroke-dasharray: 250 90; }
    }
    
    /* Animation du trait qui se dessine */
    @keyframes dashOffset {
        0% { stroke-dashoffset: 385; }
        100% { stroke-dashoffset: 5; }
    }
    
    /* Animation de rotation pour le O */
    @keyframes spin {
        0% { rotate: 0deg; }
        12.5%, 25% { rotate: 270deg; }
        37.5%, 50% { rotate: 540deg; }
        62.5%, 75% { rotate: 810deg; }
        87.5%, 100% { rotate: 1080deg; }
    }
    
    /* S'assurer que tous les SVG sont visibles */
    .servo-logo-container svg,
    .servo-logo-container path {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Padding pour le body */
    body {
        padding-top: 80px !important;
    }
}

/* ====================================================================
   ANIMATED BACKGROUND SYSTEM (harmonisé avec taches_moderne.php)
==================================================================== */
/* Mode Jour - Fond animé bleu/violet */
html body {
    background: linear-gradient(-45deg, #e0f2fe, #f0f9ff, #ede9fe, #fdf4ff) !important;
    background-size: 300% 300% !important;
    animation: gradientFlowDay 20s ease infinite !important;
}

@keyframes gradientFlowDay {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Mode Nuit - Transparent pour voir #animated-bg */
html body.night-mode,
html body.dark-mode {
    background: transparent !important;
    animation: none !important;
}

/* Conteneurs transparents pour laisser voir le fond */
html body .modern-dashboard,
html body .container-fluid,
html body .main-content {
    background: transparent !important;
}

/* #animated-bg pour le mode nuit */
#animated-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: -1;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.5s ease;
    background-color: #0f172a;
}

body.night-mode #animated-bg,
body.dark-mode #animated-bg {
    opacity: 1;
}

#animated-bg::before,
#animated-bg::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

#animated-bg::before {
    background: radial-gradient(circle at 20% 30%, rgba(76, 29, 149, 0.4), transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(59, 130, 246, 0.3), transparent 50%);
    animation: moveBackground1 25s ease-in-out infinite alternate;
}

#animated-bg::after {
    background: radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.3), transparent 45%),
                radial-gradient(circle at 10% 80%, rgba(236, 72, 153, 0.25), transparent 45%);
    animation: moveBackground2 30s ease-in-out infinite alternate-reverse;
}

@keyframes moveBackground1 {
    0% { transform: scale(1) translate(0, 0); }
    50% { transform: scale(1.1) translate(30px, -20px); }
    100% { transform: scale(1) translate(-20px, 20px); }
}

@keyframes moveBackground2 {
    0% { transform: scale(1) translate(0, 0); }
    50% { transform: scale(1.15) translate(-30px, 25px); }
    100% { transform: scale(1) translate(20px, -20px); }
}
</style>

<!-- Animated Background for Night Mode -->
<div id="animated-bg"></div>

<!-- Particules d'arrière-plan -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">
    
    <!-- En-tête moderne -->
    <div class="modern-header fade-in">
        <h1 class="modern-title">
            <i class="fas fa-trophy"></i>
            Mes Missions & Primes
        </h1>
    </div>

    <!-- Statistiques modernes -->
    <div class="modern-stats-grid fade-in">
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
            </div>
            <div class="stat-value"><?= $stats['missions_actives'] ?></div>
            <div class="stat-label">Missions Actives</div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="stat-value"><?= $stats['soumissions_en_attente'] ?></div>
            <div class="stat-label">En Attente</div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-plus-circle"></i>
                </div>
            </div>
            <div class="stat-value"><?= $stats['missions_disponibles'] ?></div>
            <div class="stat-label">Disponibles</div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-value"><?= $stats['missions_completees'] ?></div>
            <div class="stat-label">Complétées</div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <i class="fas fa-euro-sign"></i>
                </div>
            </div>
            <div class="stat-value"><?= number_format($stats['total_gains'], 2) ?>€</div>
            <div class="stat-label">Gains Totaux</div>
        </div>
        
        <div class="modern-stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                    <i class="fas fa-star"></i>
                </div>
            </div>
            <div class="stat-value"><?= $stats['total_points'] ?></div>
            <div class="stat-label">Points Totaux</div>
        </div>
    </div>

    <!-- Onglets modernes -->
    <div class="modern-tabs fade-in">
        <button class="modern-tab active" onclick="switchTab('en-cours')">
            <i class="fas fa-tasks"></i>
            En Cours
            <span class="badge"><?= count($missions_en_cours) ?></span>
        </button>
        <button class="modern-tab" onclick="switchTab('en-attente')">
            <i class="fas fa-clock"></i>
            En Attente
            <span class="badge"><?= count($soumissions_en_attente) ?></span>
        </button>
        <button class="modern-tab" onclick="switchTab('disponibles')">
            <i class="fas fa-plus-circle"></i>
            Disponibles
            <span class="badge"><?= count($missions_disponibles) ?></span>
        </button>
        <button class="modern-tab" onclick="switchTab('completees')">
            <i class="fas fa-check-circle"></i>
            Complétées
            <span class="badge"><?= count($missions_completees) ?></span>
        </button>
    </div>

    <!-- Contenu des onglets -->
    
    <!-- Missions en cours -->
    <div class="modern-tab-content active" id="tab-en-cours">
        <?php if (empty($missions_en_cours)): ?>
            <div class="empty-state">
                <i class="fas fa-tasks"></i>
                <h3>Aucune mission en cours</h3>
                <p>Consultez l'onglet "Disponibles" pour rejoindre une mission !</p>
            </div>
        <?php else: ?>
            <div class="missions-grid">
                <?php foreach ($missions_en_cours as $mission): ?>
                    <div class="mission-card mission-active">
                        <div class="mission-header">
                            <div class="mission-type-badge" style="background: linear-gradient(135deg, <?= htmlspecialchars($mission['couleur']) ?>, <?= htmlspecialchars($mission['couleur']) ?>aa);">
                                <i class="<?= htmlspecialchars($mission['icon']) ?>"></i>
                                <?= htmlspecialchars($mission['type_nom']) ?>
                            </div>
                        </div>
                        
                        <h3 class="mission-title"><?= htmlspecialchars($mission['titre']) ?></h3>
                        <p class="mission-description"><?= htmlspecialchars($mission['description']) ?></p>
                        
                        <div class="mission-progress">
                            <?php 
                            $progress_percent = $mission['objectif_nombre'] > 0 ? 
                                min(100, ($mission['progression_actuelle'] / $mission['objectif_nombre']) * 100) : 0;
                            ?>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $progress_percent ?>%"></div>
                            </div>
                            <div class="progress-text">
                                <span><?= $mission['progression_actuelle'] ?> / <?= $mission['objectif_nombre'] ?> tâches</span>
                                <span><?= number_format($progress_percent, 1) ?>%</span>
                            </div>
                        </div>
                        
                        <div class="mission-submissions">
                            <div class="submissions-info">
                                <i class="fas fa-paper-plane"></i>
                                <span>Soumissions <?= $mission['total_soumissions'] ?? 0 ?> / <?= $mission['objectif_nombre'] ?></span>
                            </div>
                        </div>
                        
                        <div class="mission-rewards">
                            <?php if ($mission['recompense_euros'] > 0): ?>
                                <div class="reward-item">
                                    <i class="fas fa-euro-sign"></i>
                                    <?= number_format($mission['recompense_euros'], 2) ?>€
                                </div>
                            <?php endif; ?>
                            <?php if ($mission['recompense_points'] > 0): ?>
                                <div class="reward-item">
                                    <i class="fas fa-star"></i>
                                    <?= $mission['recompense_points'] ?> pts
                                </div>
                            <?php endif; ?>
                            <?php if ($mission['date_fin']): ?>
                                <div class="reward-item">
                                    <i class="fas fa-clock"></i>
                                    <?= date('d/m/Y', strtotime($mission['date_fin'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mission-actions">
                            <button class="modern-btn modern-btn--primary" onclick="submitTask(<?= $mission['id'] ?>, <?= $mission['mission_id'] ?>)">
                                <i class="fas fa-paper-plane"></i>
                                Soumettre une tâche
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Soumissions en attente -->
    <div class="modern-tab-content" id="tab-en-attente">
        <?php if (empty($soumissions_en_attente)): ?>
            <div class="empty-state">
                <i class="fas fa-clock" style="color: #f59e0b;"></i>
                <h3>Aucune soumission en attente</h3>
                <p>Toutes vos soumissions ont été traitées par l'administrateur.</p>
            </div>
        <?php else: ?>
            <div class="submissions-grid">
                <?php foreach ($soumissions_en_attente as $soumission): ?>
                    <div class="submission-card pending">
                        <div class="submission-header">
                            <div class="mission-type-badge-small" style="background: <?php echo $soumission['couleur'] ?? '#6366f1'; ?>">
                                <i class="<?php echo $soumission['icon'] ?? 'fas fa-tasks'; ?>"></i>
                            </div>
                            <div class="submission-mission-info">
                                <h4><?php echo htmlspecialchars($soumission['mission_titre']); ?></h4>
                                <p class="submission-type"><?php echo htmlspecialchars($soumission['type_nom']); ?></p>
                            </div>
                            <div class="submission-status-badge pending">
                                <i class="fas fa-clock"></i>
                                En attente
                            </div>
                        </div>
                        
                        <div class="submission-content">
                            <div class="submission-task-info">
                                <div class="task-number">
                                    <i class="fas fa-hashtag"></i>
                                    Tâche #<?php echo $soumission['tache_numero']; ?>
                                </div>
                                <div class="task-progress">
                                    <i class="fas fa-chart-line"></i>
                                    Progression: <?php echo $soumission['progression']; ?>/<?php echo $soumission['objectif_nombre']; ?>
                                </div>
                            </div>
                            
                            <div class="submission-description">
                                <h5><i class="fas fa-align-left"></i> Description</h5>
                                <p><?php echo htmlspecialchars($soumission['description']); ?></p>
                            </div>
                            
                            <?php if (!empty($soumission['preuve_text'])): ?>
                                <div class="submission-proof">
                                    <h5><i class="fas fa-file-alt"></i> Preuve</h5>
                                    <p><?php echo htmlspecialchars($soumission['preuve_text']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="submission-date">
                                <i class="fas fa-calendar"></i>
                                Soumis le <?php echo date('d/m/Y à H:i', strtotime($soumission['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Missions disponibles -->
    <div class="modern-tab-content" id="tab-disponibles">
        <?php if (empty($missions_disponibles)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle" style="color: #10b981;"></i>
                <h3>Toutes les missions rejointes !</h3>
                <p>Vous participez déjà à toutes les missions disponibles.</p>
            </div>
        <?php else: ?>
            <div class="missions-grid">
                <?php foreach ($missions_disponibles as $mission): ?>
                    <div class="mission-card mission-available">
                        <div class="mission-header">
                            <div class="mission-type-badge" style="background: linear-gradient(135deg, <?= htmlspecialchars($mission['couleur']) ?>, <?= htmlspecialchars($mission['couleur']) ?>aa);">
                                <i class="<?= htmlspecialchars($mission['icon']) ?>"></i>
                                <?= htmlspecialchars($mission['type_nom']) ?>
                            </div>
                        </div>
                        
                        <h3 class="mission-title"><?= htmlspecialchars($mission['titre']) ?></h3>
                        <p class="mission-description"><?= htmlspecialchars($mission['description']) ?></p>
                        
                        <div class="mission-rewards">
                            <?php if ($mission['recompense_euros'] > 0): ?>
                                <div class="reward-item">
                                    <i class="fas fa-euro-sign"></i>
                                    <?= number_format($mission['recompense_euros'], 2) ?>€
                                </div>
                            <?php endif; ?>
                            <?php if ($mission['recompense_points'] > 0): ?>
                                <div class="reward-item">
                                    <i class="fas fa-star"></i>
                                    <?= $mission['recompense_points'] ?> pts
                                </div>
                            <?php endif; ?>
                            <div class="reward-item">
                                <i class="fas fa-tasks"></i>
                                <?= $mission['objectif_nombre'] ?> tâches
                            </div>
                            <?php if ($mission['date_fin']): ?>
                                <div class="reward-item">
                                    <i class="fas fa-clock"></i>
                                    <?= date('d/m/Y', strtotime($mission['date_fin'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mission-actions">
                            <form method="POST" style="width: 100%;">
                                <input type="hidden" name="action" value="rejoindre_mission">
                                <input type="hidden" name="mission_id" value="<?= $mission['id'] ?>">
                                <button type="submit" class="modern-btn" style="width: 100%;">
                                    <i class="fas fa-plus"></i>
                                    Rejoindre cette mission
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Missions complétées -->
    <div class="modern-tab-content" id="tab-completees">
        <?php if (empty($missions_completees)): ?>
            <div class="empty-state">
                <i class="fas fa-medal" style="color: #f59e0b;"></i>
                <h3>Aucune mission complétée</h3>
                <p>Complétez vos premières missions pour voir vos récompenses ici !</p>
            </div>
        <?php else: ?>
            <div class="missions-grid">
                <?php foreach ($missions_completees as $mission): ?>
                    <div class="mission-card mission-completed">
                        <div class="mission-header">
                            <div class="mission-type-badge" style="background: linear-gradient(135deg, <?= htmlspecialchars($mission['couleur']) ?>, <?= htmlspecialchars($mission['couleur']) ?>aa);">
                                <i class="<?= htmlspecialchars($mission['icon']) ?>"></i>
                                <?= htmlspecialchars($mission['type_nom']) ?>
                            </div>
                            <div style="text-align: right; color: var(--day-text-light); font-size: 0.875rem;">
                                <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                Complétée
                            </div>
                        </div>
                        
                        <h3 class="mission-title"><?= htmlspecialchars($mission['titre']) ?></h3>
                        
                        <div class="mission-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 100%"></div>
                            </div>
                            <div class="progress-text">
                                <span><?= $mission['objectif_nombre'] ?> / <?= $mission['objectif_nombre'] ?> tâches</span>
                                <span>100%</span>
                            </div>
                        </div>
                        
                        <div class="mission-rewards">
                            <?php if ($mission['gain_reel'] > 0): ?>
                                <div class="reward-item">
                                    <i class="fas fa-euro-sign"></i>
                                    <?= number_format($mission['gain_reel'], 2) ?>€ gagnés
                                </div>
                            <?php endif; ?>
                            <?php if ($mission['points_reels'] > 0): ?>
                                <div class="reward-item">
                                    <i class="fas fa-star"></i>
                                    <?= $mission['points_reels'] ?> pts gagnés
                                </div>
                            <?php endif; ?>
                            <div class="reward-item">
                                <i class="fas fa-calendar-check"></i>
                                <?= date('d/m/Y', strtotime($mission['date_completion'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de soumission de tâche -->
<div class="modern-modal" id="submitTaskModal">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header">
            <h3 class="modern-modal-title">
                <i class="fas fa-paper-plane"></i>
                Soumettre une tâche
            </h3>
        </div>
        <div class="modern-modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="soumettre_tache">
                <input type="hidden" name="user_mission_id" id="modalUserMissionId">
                <input type="hidden" name="mission_id" id="modalMissionId">
                
                <div class="modern-form-group">
                    <label for="description_tache" class="modern-form-label">Description de la tâche accomplie *</label>
                    <textarea class="modern-form-input" id="description_tache" name="description_tache" rows="4" 
                              placeholder="Décrivez précisément ce que vous avez fait..." required></textarea>
                    <div style="color: var(--day-text-light); font-size: 0.875rem; margin-top: 0.5rem;">
                        Soyez précis : modèle de l'appareil, panne réparée, lien de l'annonce, etc.
                    </div>
                </div>
                
                <div class="modern-form-group">
                    <label for="preuve_text" class="modern-form-label">Preuves ou détails supplémentaires</label>
                    <textarea class="modern-form-input" id="preuve_text" name="preuve_text" rows="3"
                              placeholder="Numéro de série, lien vers l'annonce, référence client..."></textarea>
                    <div style="color: var(--day-text-light); font-size: 0.875rem; margin-top: 0.5rem;">
                        Ces informations aideront l'administrateur à valider votre tâche
                    </div>
                </div>
                
                <div class="modern-form-actions">
                    <button type="button" class="modern-btn modern-btn--secondary" onclick="closeModal('submitTaskModal')">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                    <button type="submit" class="modern-btn modern-btn--primary">
                        <i class="fas fa-paper-plane"></i>
                        Soumettre pour validation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Détection IMMÉDIATE du mode nuit (avant DOMContentLoaded)
(function() {
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const storedTheme = localStorage.getItem('theme');
    
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.documentElement.classList.add('night-mode');
        document.body.classList.add('night-mode');
        console.log('🌙 Mode nuit détecté et appliqué immédiatement');
    } else {
        document.documentElement.classList.remove('night-mode');
        document.body.classList.remove('night-mode');
        console.log('☀️ Mode jour détecté et appliqué immédiatement');
    }
})();

// Gestion des onglets
function switchTab(tabName) {
    // Désactiver tous les onglets et contenus
    document.querySelectorAll('.modern-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.modern-tab-content').forEach(content => content.classList.remove('active'));
    
    // Activer l'onglet cliqué
    event.target.classList.add('active');
    
    // Activer le contenu correspondant
    const content = document.getElementById('tab-' + tabName);
    if (content) {
        content.classList.add('active');
    }
}

// Gestion des modals
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

// Soumission de tâche
function submitTask(userMissionId, missionId) {
    document.getElementById('modalUserMissionId').value = userMissionId;
    document.getElementById('modalMissionId').value = missionId;
    document.getElementById('description_tache').value = '';
    document.getElementById('preuve_text').value = '';
    
    openModal('submitTaskModal');
}

// Fermeture des modals en cliquant en dehors
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modern-modal')) {
        const modal = e.target;
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
});

// Toast notifications
function showToast(message, type = 'info') {
    // Supprimer les anciens toasts
    const existingToasts = document.querySelectorAll('.modern-toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `modern-toast modern-toast--${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: white;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        border-left: 4px solid var(--day-primary);
        z-index: 100000;
        animation: slideInUp 0.3s ease;
        min-width: 300px;
    `;
    
    if (type === 'success') {
        toast.style.borderLeftColor = '#10b981';
    } else if (type === 'error') {
        toast.style.borderLeftColor = '#ef4444';
    } else if (type === 'warning') {
        toast.style.borderLeftColor = '#f59e0b';
    }
    
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
            <span style="font-weight: 500; color: var(--day-text);">${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Supprimer après 4 secondes
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Animations des particules (optionnel)
function createParticles() {
    const container = document.getElementById('particles');
    if (!container) return;
    
    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(59, 130, 246, 0.3);
            border-radius: 50%;
            pointer-events: none;
            animation: float ${Math.random() * 3 + 2}s ease-in-out infinite;
        `;
        
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 2 + 's';
        
        container.appendChild(particle);
    }
}

// Style pour l'animation des particules
const style = document.createElement('style');
style.textContent = `
    .particles-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
        overflow: hidden;
    }
    
    @keyframes float {
        0%, 100% { 
            transform: translateY(0px) rotate(0deg);
            opacity: 0.3;
        }
        50% { 
            transform: translateY(-20px) rotate(180deg);
            opacity: 0.7;
        }
    }
    
    .modern-toast {
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: white;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        border-left: 4px solid var(--day-primary);
        z-index: 100000;
        animation: slideInUp 0.3s ease;
        min-width: 300px;
    }
    
    .modern-toast--success {
        border-left-color: #10b981;
    }
    
    .modern-toast--error {
        border-left-color: #ef4444;
    }
    
    .modern-toast--warning {
        border-left-color: #f59e0b;
    }
`;
document.head.appendChild(style);

// Fonction de détection automatique du mode nuit
function detectAndApplyDarkMode() {
    // Détecter si l'utilisateur préfère le mode sombre
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Vérifier s'il y a une préférence stockée en localStorage
    const storedTheme = localStorage.getItem('theme');
    
    // Appliquer le thème
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.body.classList.add('night-mode');
        console.log('Mode nuit activé');
    } else {
        document.body.classList.remove('night-mode');
        console.log('Mode jour activé');
    }
}

// Écouter les changements de préférence système
if (window.matchMedia) {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addListener(function(e) {
        // Si aucune préférence n'est stockée, suivre les préférences système
        if (localStorage.getItem('theme') === null) {
            if (e.matches) {
                document.body.classList.add('night-mode');
                console.log('Passage automatique en mode nuit');
            } else {
                document.body.classList.remove('night-mode');
                console.log('Passage automatique en mode jour');
            }
        }
    });
}

// Fonction pour basculer manuellement le mode (si vous voulez ajouter un bouton plus tard)
function toggleDarkMode() {
    document.body.classList.toggle('night-mode');
    const isDark = document.body.classList.contains('night-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    console.log('Mode basculé vers:', isDark ? 'nuit' : 'jour');
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Détecter et appliquer le mode nuit dès le chargement
    detectAndApplyDarkMode();
    
    createParticles();
    
    console.log('Mes Missions moderne initialisé avec détection automatique du mode nuit');
});
</script>
