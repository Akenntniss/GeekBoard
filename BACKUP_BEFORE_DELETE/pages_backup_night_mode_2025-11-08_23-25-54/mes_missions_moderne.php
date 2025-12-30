<?php
// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    redirect('login');
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
                // Inscrire l'utilisateur à la mission avec valeurs par défaut
                $stmt = $shop_pdo->prepare("
                    INSERT INTO user_missions (user_id, mission_id, progres, statut, date_rejointe) 
                    VALUES (?, ?, 0, 'en_cours', NOW())
                ");
                $stmt->execute([$user_id, $mission_id]);
                set_message("Vous avez rejoint la mission avec succès !", "success");
            } else {
                set_message("Vous participez déjà à cette mission.", "warning");
            }
        } catch (PDOException $e) {
            set_message("Erreur lors de l'inscription à la mission: " . $e->getMessage(), "error");
        }
    }
    
    if ($_POST['action'] === 'valider_tache') {
        $user_mission_id = (int)$_POST['user_mission_id'];
        $mission_id = (int)$_POST['mission_id'];
        $description = cleanInput($_POST['description_tache']);
        $preuve_text = cleanInput($_POST['preuve_text']);
        
        if (empty($description)) {
            set_message("La description de la tâche est obligatoire.", "error");
        } elseif (!isset($_FILES['photo_tache']) || $_FILES['photo_tache']['error'] !== UPLOAD_ERR_OK) {
            set_message("La photo de la tâche est obligatoire.", "error");
        } else {
            try {
                // Vérifier que la user_mission existe
                $stmt = $shop_pdo->prepare("SELECT id, progres FROM user_missions WHERE id = ? AND user_id = ?");
                $stmt->execute([$user_mission_id, $user_id]);
                $user_mission = $stmt->fetch();
                
                if (!$user_mission) {
                    set_message("Mission non trouvée ou non autorisée.", "error");
                } else {
                    // Traitement de l'upload de photo
                    $photo_filename = null;
                    $file = $_FILES['photo_tache'];
                    
                    // Vérifier le type de fichier
                    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    if (!in_array($file['type'], $allowed_types)) {
                        set_message("Format de photo non autorisé. Utilisez JPG, PNG, WebP ou GIF.", "error");
                    } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB max
                        set_message("La photo est trop volumineuse (max 5MB).", "error");
                    } else {
                        // Créer le dossier s'il n'existe pas
                        $upload_dir = BASE_PATH . '/uploads/missions/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        // Calculer le numéro de tâche
                        $tache_numero = $user_mission['progres'] + 1;
                        
                        // Générer un nom de fichier unique
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $photo_filename = 'mission_' . $user_mission_id . '_' . $tache_numero . '_' . time() . '.' . $extension;
                        $upload_path = $upload_dir . $photo_filename;
                        
                        // Déplacer le fichier uploadé
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            
                            // Insérer la validation (structure corrigée)
                            $stmt = $shop_pdo->prepare("
                                INSERT INTO mission_validations (user_mission_id, tache_numero, description, preuve_fichier, statut) 
                                VALUES (?, ?, ?, ?, 'en_attente')
                            ");
                            $stmt->execute([$user_mission_id, $tache_numero, $description, $photo_filename]);
                            
                            // Mettre à jour la progression (nom de colonne corrigé)
                            $stmt = $shop_pdo->prepare("
                                UPDATE user_missions 
                                SET progres = progres + 1 
                                WHERE id = ? AND user_id = ?
                            ");
                            $stmt->execute([$user_mission_id, $user_id]);
                            
                            // Vérifier si la mission est complète (noms de colonnes corrigés)
                            $stmt = $shop_pdo->prepare("
                                SELECT um.progres, m.objectif_quantite 
                                FROM user_missions um 
                                JOIN missions m ON um.mission_id = m.id 
                                WHERE um.id = ?
                            ");
                            $stmt->execute([$user_mission_id]);
                            $progress = $stmt->fetch();
                            
                            if ($progress && $progress['progres'] >= $progress['objectif_quantite']) {
                                // Marquer la mission comme complète (statut corrigé)
                                $stmt = $shop_pdo->prepare("
                                    UPDATE user_missions 
                                    SET statut = 'terminee', date_completee = NOW() 
                                    WHERE id = ?
                                ");
                                $stmt->execute([$user_mission_id]);
                                set_message("🎉 Félicitations ! Vous avez complété cette mission !", "success");
                            } else {
                                set_message("Tâche soumise avec succès ! En attente de validation par l'admin.", "success");
                            }
                        } else {
                            set_message("Erreur lors de l'upload de la photo.", "error");
                        }
                    }
                }
                
            } catch (PDOException $e) {
                error_log("Erreur validation tâche: " . $e->getMessage());
                set_message("Erreur lors de la validation de la tâche: " . $e->getMessage(), "error");
            }
        }
    }
    
    redirect('mes_missions_moderne');
}

// Récupération des missions disponibles (non encore rejointes)
try {
    // Debug: Log des informations de session
    error_log("DEBUG MISSIONS - User ID: " . ($user_id ?? 'NULL'));
    error_log("DEBUG MISSIONS - Session user_id: " . ($_SESSION['user_id'] ?? 'NULL'));
    error_log("DEBUG MISSIONS - Session shop_id: " . ($_SESSION['shop_id'] ?? 'NULL'));
    
    $stmt = $shop_pdo->prepare("
        SELECT m.*, mt.nom as type_nom, mt.icone as icon, mt.couleur
        FROM missions m
        LEFT JOIN mission_types mt ON m.type_id = mt.id
        WHERE m.statut = 'active' 
        AND (m.date_fin IS NULL OR m.date_fin >= CURDATE())
        AND m.id NOT IN (
            SELECT COALESCE(mission_id, 0) FROM user_missions WHERE user_id = ?
        )
        ORDER BY m.date_fin ASC, m.recompense_euros DESC
    ");
    $stmt->execute([$user_id]);
    $missions_disponibles = $stmt->fetchAll();
    
    // Debug: Log du nombre de missions trouvées
    error_log("DEBUG MISSIONS - Missions disponibles trouvées: " . count($missions_disponibles));
    
} catch (PDOException $e) {
    $missions_disponibles = [];
    error_log("DEBUG MISSIONS - Erreur SQL: " . $e->getMessage());
    set_message("Erreur lors de la récupération des missions disponibles.", "error");
}

// Récupération des missions en cours
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.*, m.titre, m.description, m.objectif_quantite, m.recompense_euros, m.recompense_points,
               m.date_fin, mt.nom as type_nom, mt.icone as icon, mt.couleur,
               COUNT(mv.id) as validations_count,
               COALESCE(um.progres, 0) as progression_actuelle
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        LEFT JOIN mission_types mt ON m.type_id = mt.id
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

// Récupération des missions complétées et en attente de validation
try {
    $stmt = $shop_pdo->prepare("
        SELECT um.*, m.titre, m.objectif_quantite, m.recompense_euros, m.recompense_points,
               m.date_fin, mt.nom as type_nom, mt.icone as icon, mt.couleur,
               mr.montant_euros as gain_reel, mr.points_attribues as points_reels,
               COALESCE(validations_stats.total_validations, 0) as total_validations,
               COALESCE(validations_stats.validations_approuvees, 0) as validations_approuvees,
               COALESCE(validations_stats.validations_en_attente, 0) as validations_en_attente,
               CASE 
                   WHEN um.statut = 'terminee' AND COALESCE(validations_stats.validations_en_attente, 0) > 0 THEN 'en_attente_validation'
                   WHEN um.statut = 'terminee' THEN 'completee'
                   ELSE um.statut
               END as statut_affichage
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        LEFT JOIN mission_types mt ON m.type_id = mt.id
        LEFT JOIN mission_recompenses mr ON um.id = mr.user_mission_id
        LEFT JOIN (
            SELECT user_mission_id,
                   COUNT(*) as total_validations,
                   SUM(CASE WHEN statut = 'validee' THEN 1 ELSE 0 END) as validations_approuvees,
                   SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as validations_en_attente
            FROM mission_validations
            GROUP BY user_mission_id
        ) validations_stats ON um.id = validations_stats.user_mission_id
        WHERE um.user_id = ? AND (
            um.statut = 'terminee' OR 
            (um.progres >= m.objectif_quantite AND validations_stats.validations_en_attente > 0)
        )
        ORDER BY 
            CASE WHEN validations_stats.validations_en_attente > 0 THEN 0 ELSE 1 END,
            COALESCE(um.date_completee, um.date_rejointe) DESC
    ");
    $stmt->execute([$user_id]);
    $missions_completees = $stmt->fetchAll();
} catch (PDOException $e) {
    $missions_completees = [];
    error_log("Erreur missions complétées: " . $e->getMessage());
}

// Calcul des statistiques personnelles
$stats = [
    'missions_actives' => count($missions_en_cours),
    'missions_disponibles' => count($missions_disponibles),
    'missions_completees' => count($missions_completees),
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
    background: linear-gradient(90deg, #10b981, #059669);
}

.mission-card.mission-pending-validation::before {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.mission-card.mission-pending-validation {
    border-left: 4px solid #f59e0b;
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

.reward-item.pending {
    opacity: 0.7;
    font-style: italic;
}

.reward-item.pending i {
    color: #f59e0b;
}

.badge-pending {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-block;
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
   MODAL VALIDATION DE TÂCHE AMÉLIORÉ
======================================== */

/* Modal Container */
.validate-task-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.validate-task-modal.show {
    display: flex;
    opacity: 1;
    visibility: visible;
}

/* Backdrop */
.validate-task-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

/* Container principal */
.validate-task-modal-container {
    position: relative;
    width: 100%;
    max-width: 600px;
    margin: 2rem;
    z-index: 10001;
    transform: scale(0.9) translateY(20px);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.validate-task-modal.show .validate-task-modal-container {
    transform: scale(1) translateY(0);
}

/* Contenu du modal */
.validate-task-modal-content {
    background: var(--day-card-bg);
    border-radius: 20px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    border: 1px solid var(--day-border);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

/* En-tête */
.validate-task-modal-header {
    background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    position: relative;
    color: white;
}

.validate-task-modal-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.validate-task-modal-title-section {
    flex: 1;
}

.validate-task-modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
    color: white;
}

.validate-task-modal-subtitle {
    font-size: 0.95rem;
    margin: 0;
    opacity: 0.9;
    color: rgba(255, 255, 255, 0.9);
}

.validate-task-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 1.1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.validate-task-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

/* Corps du modal */
.validate-task-modal-body {
    padding: 2rem;
}

/* Formulaire */
.validate-task-form-group {
    margin-bottom: 2rem;
}

.validate-task-form-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
    color: var(--day-text);
    margin-bottom: 0.75rem;
    font-size: 1rem;
}

.validate-task-form-label i {
    color: var(--day-primary);
    font-size: 1.1rem;
}

.validate-task-form-input {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px solid var(--day-border);
    border-radius: 12px;
    background: var(--day-card-bg);
    color: var(--day-text);
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.3s ease;
    resize: vertical;
    min-height: 120px;
}

.validate-task-form-input:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    background: rgba(59, 130, 246, 0.02);
}

.validate-task-form-input::placeholder {
    color: var(--day-text-light);
    opacity: 0.7;
}

.validate-task-form-help {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    font-size: 0.875rem;
    color: var(--day-text-light);
    padding: 0.75rem 1rem;
    background: rgba(59, 130, 246, 0.05);
    border-radius: 8px;
    border-left: 3px solid var(--day-primary);
}

.validate-task-form-help i {
    color: var(--day-primary);
    opacity: 0.8;
}

/* Upload de photo */
.validate-task-photo-upload {
    position: relative;
}

.validate-task-form-input-file {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px dashed var(--day-border);
    border-radius: 12px;
    background: var(--day-card-bg);
    color: var(--day-text);
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.3s ease;
    cursor: pointer;
}

.validate-task-form-input-file:hover {
    border-color: var(--day-primary);
    background: rgba(59, 130, 246, 0.02);
}

.validate-task-form-input-file:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.validate-task-photo-preview {
    margin-top: 1rem;
    position: relative;
    display: inline-block;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.validate-task-photo-preview img {
    max-width: 200px;
    max-height: 200px;
    width: auto;
    height: auto;
    display: block;
    border-radius: 12px;
}

.validate-task-photo-remove {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 30px;
    height: 30px;
    background: rgba(239, 68, 68, 0.9);
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 0.875rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.validate-task-photo-remove:hover {
    background: rgba(239, 68, 68, 1);
    transform: scale(1.1);
}

/* Actions */
.validate-task-form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--day-border);
}

.validate-task-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    min-width: 140px;
    justify-content: center;
}

.validate-task-btn--cancel {
    background: var(--day-card-bg);
    color: var(--day-text);
    border: 2px solid var(--day-border);
}

.validate-task-btn--cancel:hover {
    background: rgba(107, 114, 128, 0.1);
    border-color: rgba(107, 114, 128, 0.3);
    transform: translateY(-2px);
}

.validate-task-btn--submit {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.validate-task-btn--submit:hover {
    background: linear-gradient(135deg, #059669, #047857);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    transform: translateY(-2px);
}

/* ========================================
   MODE NUIT - MODAL VALIDATION
======================================== */

body.night-mode .validate-task-modal-content {
    background: var(--night-card-bg);
    border-color: var(--night-border);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
}

body.night-mode .validate-task-modal-header {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
}

body.night-mode .validate-task-modal-icon {
    background: rgba(0, 212, 255, 0.2);
}

body.night-mode .validate-task-modal-close {
    background: rgba(0, 212, 255, 0.2);
}

body.night-mode .validate-task-modal-close:hover {
    background: rgba(0, 212, 255, 0.3);
    box-shadow: var(--night-glow);
}

body.night-mode .validate-task-form-label {
    color: var(--night-text);
}

body.night-mode .validate-task-form-label i {
    color: var(--night-primary);
}

body.night-mode .validate-task-form-input {
    background: rgba(15, 15, 25, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .validate-task-form-input:focus {
    border-color: var(--night-primary);
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
    background: rgba(0, 212, 255, 0.05);
}

body.night-mode .validate-task-form-input::placeholder {
    color: var(--night-text-light);
}

body.night-mode .validate-task-form-help {
    background: rgba(0, 212, 255, 0.1);
    border-left-color: var(--night-primary);
    color: var(--night-text-light);
}

body.night-mode .validate-task-form-help i {
    color: var(--night-primary);
}

body.night-mode .validate-task-form-actions {
    border-top-color: var(--night-border);
}

body.night-mode .validate-task-btn--cancel {
    background: rgba(15, 15, 25, 0.8);
    color: var(--night-text);
    border-color: var(--night-border);
}

body.night-mode .validate-task-btn--cancel:hover {
    background: rgba(0, 212, 255, 0.1);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

body.night-mode .validate-task-btn--submit {
    background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
    box-shadow: var(--night-glow);
}

body.night-mode .validate-task-btn--submit:hover {
    background: linear-gradient(135deg, var(--night-accent), var(--night-primary));
    box-shadow: 0 8px 25px rgba(0, 212, 255, 0.4);
}

/* Mode nuit - Upload de photo */
body.night-mode .validate-task-form-input-file {
    background: rgba(15, 15, 25, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .validate-task-form-input-file:hover {
    border-color: var(--night-primary);
    background: rgba(0, 212, 255, 0.05);
}

body.night-mode .validate-task-form-input-file:focus {
    border-color: var(--night-primary);
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.2);
}

body.night-mode .validate-task-photo-preview {
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

body.night-mode .validate-task-photo-remove {
    background: rgba(255, 0, 170, 0.9);
}

body.night-mode .validate-task-photo-remove:hover {
    background: rgba(255, 0, 170, 1);
    box-shadow: 0 0 10px rgba(255, 0, 170, 0.5);
}

/* Mode nuit - Missions en attente de validation */
body.night-mode .mission-card.mission-pending-validation::before {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

body.night-mode .mission-card.mission-pending-validation {
    border-left: 4px solid #f59e0b;
}

body.night-mode .reward-item.pending i {
    color: #fbbf24;
}

body.night-mode .badge-pending {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .validate-task-modal-container {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
    
    .validate-task-modal-header {
        padding: 1.5rem;
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .validate-task-modal-close {
        position: static;
        margin-top: 1rem;
    }
    
    .validate-task-modal-body {
        padding: 1.5rem;
    }
    
    .validate-task-form-actions {
        flex-direction: column;
    }
    
    .validate-task-btn {
        width: 100%;
    }
}
</style>

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
                            <div class="mission-type-badge" style="background: linear-gradient(135deg, <?= htmlspecialchars($mission['couleur'] ?? '#007bff') ?>, <?= htmlspecialchars($mission['couleur'] ?? '#007bff') ?>aa);">
                                <i class="<?= htmlspecialchars($mission['icon'] ?? 'fas fa-star') ?>"></i>
                                <?= htmlspecialchars($mission['type_nom'] ?? 'Mission') ?>
                            </div>
                        </div>
                        
                        <h3 class="mission-title"><?= htmlspecialchars($mission['titre'] ?? 'Mission sans titre') ?></h3>
                        <p class="mission-description"><?= htmlspecialchars($mission['description'] ?? 'Aucune description disponible') ?></p>
                        
                        <div class="mission-progress">
                            <?php 
                            $progression = $mission['progression_actuelle'] ?? 0;
                            $objectif = $mission['objectif_quantite'] ?? 1;
                            $progress_percent = $objectif > 0 ? 
                                min(100, ($progression / $objectif) * 100) : 0;
                            ?>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $progress_percent ?>%"></div>
                            </div>
                            <div class="progress-text">
                                <span><?= $progression ?> / <?= $objectif ?> tâches</span>
                                <span><?= number_format($progress_percent, 1) ?>%</span>
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
                            <button class="modern-btn modern-btn--success" onclick="validateTask(<?= $mission['id'] ?>, <?= $mission['mission_id'] ?>)">
                                <i class="fas fa-check"></i>
                                Valider une tâche
                            </button>
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
                            <div class="mission-type-badge" style="background: linear-gradient(135deg, <?= htmlspecialchars($mission['couleur'] ?? '#007bff') ?>, <?= htmlspecialchars($mission['couleur'] ?? '#007bff') ?>aa);">
                                <i class="<?= htmlspecialchars($mission['icon'] ?? 'fas fa-star') ?>"></i>
                                <?= htmlspecialchars($mission['type_nom'] ?? 'Mission') ?>
                            </div>
                        </div>
                        
                        <h3 class="mission-title"><?= htmlspecialchars($mission['titre'] ?? 'Mission sans titre') ?></h3>
                        <p class="mission-description"><?= htmlspecialchars($mission['description'] ?? 'Aucune description disponible') ?></p>
                        
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
                                <?= $mission['objectif_quantite'] ?? 0 ?> tâches
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
                    <div class="mission-card <?= $mission['statut_affichage'] === 'en_attente_validation' ? 'mission-pending-validation' : 'mission-completed' ?>">
                        <div class="mission-header">
                            <div class="mission-type-badge" style="background: linear-gradient(135deg, <?= htmlspecialchars($mission['couleur'] ?? '#007bff') ?>, <?= htmlspecialchars($mission['couleur'] ?? '#007bff') ?>aa);">
                                <i class="<?= htmlspecialchars($mission['icon'] ?? 'fas fa-star') ?>"></i>
                                <?= htmlspecialchars($mission['type_nom'] ?? 'Mission') ?>
                            </div>
                            <div style="text-align: right; color: var(--day-text-light); font-size: 0.875rem;">
                                <?php if ($mission['statut_affichage'] === 'en_attente_validation'): ?>
                                    <i class="fas fa-clock" style="color: #f59e0b;"></i>
                                    En attente de validation
                                    <?php if ($mission['validations_en_attente'] > 0): ?>
                                        <div style="margin-top: 0.25rem; font-size: 0.75rem;">
                                            <span class="badge-pending"><?= $mission['validations_en_attente'] ?> tâche(s) à valider</span>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                    Complétée et validée
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <h3 class="mission-title"><?= htmlspecialchars($mission['titre']) ?></h3>
                        
                        <div class="mission-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 100%"></div>
                            </div>
                            <div class="progress-text">
                                <span><?= $mission['objectif_quantite'] ?> / <?= $mission['objectif_quantite'] ?> tâches</span>
                                <span>100%</span>
                            </div>
                        </div>
                        
                        <div class="mission-rewards">
                            <?php if ($mission['statut_affichage'] === 'en_attente_validation'): ?>
                                <!-- Mission en attente de validation -->
                                <div class="reward-item pending">
                                    <i class="fas fa-euro-sign"></i>
                                    <?= number_format($mission['recompense_euros'] ?? 0, 2) ?>€ en attente
                                </div>
                                <div class="reward-item pending">
                                    <i class="fas fa-star"></i>
                                    <?= $mission['recompense_points'] ?? 0 ?> pts en attente
                                </div>
                                <div class="reward-item">
                                    <i class="fas fa-tasks"></i>
                                    <?= $mission['validations_approuvees'] ?>/<?= $mission['objectif_quantite'] ?> tâches validées
                                </div>
                                <?php if ($mission['date_completee']): ?>
                                    <div class="reward-item">
                                        <i class="fas fa-calendar-check"></i>
                                        Terminée le <?= date('d/m/Y', strtotime($mission['date_completee'])) ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Mission complètement validée -->
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
                                    Validée le <?= date('d/m/Y', strtotime($mission['date_completee'] ?? $mission['date_rejointe'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de validation de tâche amélioré -->
<div class="validate-task-modal" id="validateTaskModal">
    <div class="validate-task-modal-backdrop"></div>
    <div class="validate-task-modal-container">
        <div class="validate-task-modal-content">
            <!-- En-tête moderne avec icône et fermeture -->
            <div class="validate-task-modal-header">
                <div class="validate-task-modal-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="validate-task-modal-title-section">
                    <h3 class="validate-task-modal-title">Valider une tâche</h3>
                    <p class="validate-task-modal-subtitle">Décrivez votre accomplissement</p>
                </div>
                <button type="button" class="validate-task-modal-close" onclick="closeModal('validateTaskModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Corps du modal -->
            <div class="validate-task-modal-body">
                <form method="POST" class="validate-task-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="valider_tache">
                    <input type="hidden" name="user_mission_id" id="modalUserMissionId">
                    <input type="hidden" name="mission_id" id="modalMissionId">
                    
                    <!-- Description de la tâche -->
                    <div class="validate-task-form-group">
                        <label for="description_tache" class="validate-task-form-label">
                            <i class="fas fa-edit"></i>
                            Description de la tâche accomplie *
                        </label>
                        <textarea class="validate-task-form-input" 
                                  id="description_tache" 
                                  name="description_tache" 
                                  rows="4" 
                                  placeholder="Décrivez précisément ce que vous avez fait..." 
                                  required></textarea>
                        <div class="validate-task-form-help">
                            <i class="fas fa-info-circle"></i>
                            Soyez précis : modèle de l'appareil, panne réparée, lien de l'annonce, etc.
                        </div>
                    </div>
                    
                    <!-- Photo obligatoire -->
                    <div class="validate-task-form-group">
                        <label for="photo_tache" class="validate-task-form-label">
                            <i class="fas fa-camera"></i>
                            Photo de la tâche accomplie *
                        </label>
                        <div class="validate-task-photo-upload">
                            <input type="file" 
                                   class="validate-task-form-input-file" 
                                   id="photo_tache" 
                                   name="photo_tache" 
                                   accept="image/*"
                                   required>
                            <div class="validate-task-photo-preview" id="photoPreview" style="display: none;">
                                <img id="previewImage" src="" alt="Aperçu">
                                <button type="button" class="validate-task-photo-remove" onclick="removePhoto()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="validate-task-form-help">
                            <i class="fas fa-info-circle"></i>
                            Prenez une photo qui prouve l'accomplissement de la tâche (formats: JPG, PNG, WebP)
                        </div>
                    </div>
                    
                    <!-- Preuves supplémentaires -->
                    <div class="validate-task-form-group">
                        <label for="preuve_text" class="validate-task-form-label">
                            <i class="fas fa-paperclip"></i>
                            Détails supplémentaires
                        </label>
                        <textarea class="validate-task-form-input" 
                                  id="preuve_text" 
                                  name="preuve_text" 
                                  rows="3"
                                  placeholder="Numéro de série, lien vers l'annonce, référence client..."></textarea>
                        <div class="validate-task-form-help">
                            <i class="fas fa-lightbulb"></i>
                            Ajoutez des détails complémentaires (optionnel)
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="validate-task-form-actions">
                        <button type="button" class="validate-task-btn validate-task-btn--cancel" onclick="closeModal('validateTaskModal')">
                            <i class="fas fa-times"></i>
                            <span>Annuler</span>
                        </button>
                        <button type="submit" class="validate-task-btn validate-task-btn--submit">
                            <i class="fas fa-check"></i>
                            <span>Valider la tâche</span>
                        </button>
                    </div>
                </form>
            </div>
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
    console.log('🔄 Basculement vers onglet:', tabName);
    
    // Désactiver tous les onglets et contenus
    document.querySelectorAll('.modern-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.modern-tab-content').forEach(content => content.classList.remove('active'));
    
    // Activer l'onglet cliqué (chercher le bon bouton)
    const clickedTab = document.querySelector(`.modern-tab[onclick*="${tabName}"]`);
    if (clickedTab) {
        clickedTab.classList.add('active');
    }
    
    // Activer le contenu correspondant
    const content = document.getElementById('tab-' + tabName);
    if (content) {
        content.classList.add('active');
        console.log('✅ Contenu activé pour:', tabName);
    } else {
        console.error('❌ Contenu non trouvé pour:', 'tab-' + tabName);
    }
}

// Gestion des modals
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Animation d'entrée
        setTimeout(() => {
            modal.style.opacity = '1';
            modal.style.visibility = 'visible';
        }, 10);
        
        console.log('✅ Modal ouvert:', modalId);
    } else {
        console.error('❌ Modal non trouvé:', modalId);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
        
        // Animation de sortie
        setTimeout(() => {
            modal.style.opacity = '0';
            modal.style.visibility = 'hidden';
        }, 10);
        
        console.log('✅ Modal fermé:', modalId);
    }
}

// Validation de tâche
function validateTask(userMissionId, missionId) {
    document.getElementById('modalUserMissionId').value = userMissionId;
    document.getElementById('modalMissionId').value = missionId;
    document.getElementById('description_tache').value = '';
    document.getElementById('preuve_text').value = '';
    
    // Réinitialiser l'upload de photo
    document.getElementById('photo_tache').value = '';
    document.getElementById('photoPreview').style.display = 'none';
    
    openModal('validateTaskModal');
}

// Gestion de l'aperçu photo
document.addEventListener('DOMContentLoaded', function() {
    const photoInput = document.getElementById('photo_tache');
    const photoPreview = document.getElementById('photoPreview');
    const previewImage = document.getElementById('previewImage');
    
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Vérifier le type de fichier
                if (!file.type.startsWith('image/')) {
                    alert('Veuillez sélectionner un fichier image valide.');
                    this.value = '';
                    return;
                }
                
                // Vérifier la taille (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('La taille de l\'image ne doit pas dépasser 5MB.');
                    this.value = '';
                    return;
                }
                
                // Créer l'aperçu
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    photoPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
                
                console.log('✅ Photo sélectionnée:', file.name, 'Taille:', (file.size / 1024).toFixed(1) + 'KB');
            }
        });
    }
});

// Supprimer la photo
function removePhoto() {
    document.getElementById('photo_tache').value = '';
    document.getElementById('photoPreview').style.display = 'none';
    document.getElementById('previewImage').src = '';
    console.log('🗑️ Photo supprimée');
}

// Fermeture des modals en cliquant en dehors
document.addEventListener('click', function(e) {
    // Pour les anciens modals
    if (e.target.classList.contains('modern-modal')) {
        const modal = e.target;
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
    
    // Pour le nouveau modal de validation
    if (e.target.classList.contains('validate-task-modal-backdrop')) {
        const modal = e.target.closest('.validate-task-modal');
        if (modal) {
            closeModal(modal.id);
        }
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
    
    // Test automatique de l'onglet disponibles après 3 secondes
    setTimeout(function() {
        const missionsDisponibles = <?= count($missions_disponibles) ?>;
        console.log('🧪 Test automatique: missions disponibles =', missionsDisponibles);
        if (missionsDisponibles > 0) {
            console.log('🔄 Basculement automatique vers l\'onglet disponibles pour test');
            switchTab('disponibles');
        }
    }, 3000);
});
</script>
