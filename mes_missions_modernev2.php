<?php
// Inclure la configuration de session et de base de données
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser la session du magasin si ce n'est pas déjà fait
initializeShopSession();

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    redirect('login');
}

$user_id = $_SESSION['user_id'];

// Obtenir la connexion PDO pour le magasin actuel
$shop_pdo = getShopDBConnection();

// Fallback si getShopDBConnection() échoue
if (!$shop_pdo && isset($_SESSION['shop_id'])) {
    $shop_pdo = getShopDBConnectionById($_SESSION['shop_id']);
}

if (!$shop_pdo) {
    error_log("Erreur critique: Impossible d'obtenir une connexion PDO pour le magasin dans mes_missions_modernev2.php");
    set_message("Erreur de connexion à la base de données du magasin.", "error");
    exit;
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'rejoindre_mission':
            $mission_id = (int)($_POST['mission_id'] ?? 0);
            
            if ($mission_id > 0) {
                try {
                    // Vérifier si l'utilisateur n'a pas déjà rejoint cette mission
                    $stmt = $shop_pdo->prepare("SELECT id FROM user_missions WHERE user_id = ? AND mission_id = ?");
                    $stmt->execute([$user_id, $mission_id]);
                    
                    if (!$stmt->fetch()) {
                        // Vérifier les limites de participants
                        $stmt = $shop_pdo->prepare("
                            SELECT m.max_participants, COUNT(um.user_id) as current_participants
                            FROM missions m
                            LEFT JOIN user_missions um ON m.id = um.mission_id
                            WHERE m.id = ? AND m.statut = 'active'
                            GROUP BY m.id
                        ");
                        $stmt->execute([$mission_id]);
                        $mission_info = $stmt->fetch();
                        
                        if ($mission_info && ($mission_info['max_participants'] === null || $mission_info['current_participants'] < $mission_info['max_participants'])) {
                            // Inscrire l'utilisateur à la mission
                            $stmt = $shop_pdo->prepare("
                                INSERT INTO user_missions (user_id, mission_id, progression, statut) 
                                VALUES (?, ?, 0, 'en_cours')
                            ");
                            $stmt->execute([$user_id, $mission_id]);
                            set_message("Vous avez rejoint la mission avec succès !", "success");
                        } else {
                            set_message("Cette mission a atteint le nombre maximum de participants.", "error");
                        }
                    } else {
                        set_message("Vous participez déjà à cette mission.", "error");
                    }
                } catch (PDOException $e) {
                    set_message("Erreur lors de l'inscription: " . $e->getMessage(), "error");
                }
            }
            break;
            
        case 'valider_tache':
            $user_mission_id = (int)($_POST['user_mission_id'] ?? 0);
            $description = trim($_POST['description_tache'] ?? '');
            $preuve_text = trim($_POST['preuve_text'] ?? '');
            
            if ($user_mission_id > 0 && !empty($description)) {
                try {
                    // Vérifier que la user_mission appartient à l'utilisateur
                    $stmt = $shop_pdo->prepare("
                        SELECT um.progression, m.objectif_nombre 
                        FROM user_missions um 
                        JOIN missions m ON um.mission_id = m.id 
                        WHERE um.id = ? AND um.user_id = ? AND um.statut = 'en_cours'
                    ");
                    $stmt->execute([$user_mission_id, $user_id]);
                    $user_mission = $stmt->fetch();
                    
                    if ($user_mission) {
                        $tache_numero = $user_mission['progression'] + 1;
                        
                        // Traitement de l'upload de photo si présente
                        $photo_filename = null;
                        if (isset($_FILES['photo_tache']) && $_FILES['photo_tache']['error'] === UPLOAD_ERR_OK) {
                            $file = $_FILES['photo_tache'];
                            
                            // Vérifier le type de fichier
                            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                            if (in_array($file['type'], $allowed_types) && $file['size'] <= 5 * 1024 * 1024) {
                                // Créer le dossier s'il n'existe pas
                                $upload_dir = __DIR__ . '/../uploads/missions/';
                                if (!is_dir($upload_dir)) {
                                    mkdir($upload_dir, 0755, true);
                                }
                                
                                // Générer un nom de fichier unique
                                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                                $photo_filename = 'mission_' . $user_mission_id . '_' . $tache_numero . '_' . time() . '.' . $extension;
                                $upload_path = $upload_dir . $photo_filename;
                                
                                if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                                    $photo_filename = null;
                                }
                            }
                        }
                        
                        // Insérer la validation
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO mission_validations (user_mission_id, tache_numero, description, preuve_fichier, preuve_text, statut) 
                            VALUES (?, ?, ?, ?, ?, 'en_attente')
                        ");
                        $stmt->execute([$user_mission_id, $tache_numero, $description, $photo_filename, $preuve_text]);
                        
                        set_message("Tâche soumise avec succès ! En attente de validation par l'admin.", "success");
                    } else {
                        set_message("Mission non trouvée ou non autorisée.", "error");
                    }
                } catch (PDOException $e) {
                    set_message("Erreur lors de la validation: " . $e->getMessage(), "error");
                }
            } else {
                set_message("Veuillez remplir tous les champs obligatoires.", "error");
            }
            break;
            
        case 'demander_retrait':
            $montant = (float)($_POST['montant'] ?? 0);
            $methode_paiement = $_POST['methode_paiement'] ?? '';
            $details_paiement = trim($_POST['details_paiement'] ?? '');
            
            if ($montant > 0 && !empty($methode_paiement) && !empty($details_paiement)) {
                try {
                    // Vérifier le solde disponible
                    $stmt = $shop_pdo->prepare("SELECT solde_euros FROM user_cagnotte WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $cagnotte = $stmt->fetch();
                    
                    if ($cagnotte && $cagnotte['solde_euros'] >= $montant) {
                        $shop_pdo->beginTransaction();
                        
                        // Déduire le montant du solde
                        $stmt = $shop_pdo->prepare("
                            UPDATE user_cagnotte 
                            SET solde_euros = solde_euros - ? 
                            WHERE user_id = ?
                        ");
                        $stmt->execute([$montant, $user_id]);
                        
                        // Créer la demande de retrait
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO demandes_retrait (user_id, montant, methode_paiement, details_paiement, statut) 
                            VALUES (?, ?, ?, ?, 'en_attente')
                        ");
                        $stmt->execute([$user_id, $montant, $methode_paiement, $details_paiement]);
                        
                        $shop_pdo->commit();
                        set_message("Demande de retrait envoyée avec succès !", "success");
                    } else {
                        set_message("Solde insuffisant pour ce retrait.", "error");
                    }
                } catch (PDOException $e) {
                    $shop_pdo->rollBack();
                    set_message("Erreur lors de la demande: " . $e->getMessage(), "error");
                }
            } else {
                set_message("Veuillez remplir tous les champs.", "error");
            }
            break;
    }
}

// Récupération des données
try {
    // Cagnotte utilisateur
    $stmt = $shop_pdo->prepare("
        SELECT * FROM user_cagnotte WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cagnotte = $stmt->fetch();
    
    if (!$cagnotte) {
        // Créer la cagnotte si elle n'existe pas
        $stmt = $shop_pdo->prepare("
            INSERT INTO user_cagnotte (user_id, solde_euros, solde_points, total_gagne_euros, total_gagne_points) 
            VALUES (?, 0, 0, 0, 0)
        ");
        $stmt->execute([$user_id]);
        $cagnotte = [
            'solde_euros' => 0,
            'solde_points' => 0,
            'total_gagne_euros' => 0,
            'total_gagne_points' => 0
        ];
    }
    
    // Missions disponibles (non encore rejointes)
    $stmt = $shop_pdo->prepare("
        SELECT m.*, mt.nom as type_nom, mt.icon, mt.couleur,
               COUNT(DISTINCT um.user_id) as nb_participants
        FROM missions m
        JOIN mission_types mt ON m.mission_type_id = mt.id
        LEFT JOIN user_missions um ON m.id = um.mission_id
        WHERE m.statut = 'active' 
        AND (m.date_fin IS NULL OR m.date_fin >= CURDATE())
        AND m.id NOT IN (SELECT mission_id FROM user_missions WHERE user_id = ?)
        GROUP BY m.id
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $missions_disponibles = $stmt->fetchAll();
    
    // Missions en cours
    $stmt = $shop_pdo->prepare("
        SELECT um.*, m.titre, m.description, m.objectif_nombre, m.recompense_euros, m.recompense_points,
               m.date_fin, mt.nom as type_nom, mt.icon, mt.couleur,
               COUNT(mv.id) as validations_soumises,
               COUNT(CASE WHEN mv.statut = 'validee' THEN 1 END) as validations_approuvees
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.mission_type_id = mt.id
        LEFT JOIN mission_validations mv ON um.id = mv.user_mission_id
        WHERE um.user_id = ? AND um.statut = 'en_cours'
        GROUP BY um.id
        ORDER BY um.date_inscription DESC
    ");
    $stmt->execute([$user_id]);
    $missions_en_cours = $stmt->fetchAll();
    
    // Missions terminées
    $stmt = $shop_pdo->prepare("
        SELECT um.*, m.titre, m.description, m.objectif_nombre, m.recompense_euros, m.recompense_points,
               m.date_fin, mt.nom as type_nom, mt.icon, mt.couleur
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.id
        JOIN mission_types mt ON m.mission_type_id = mt.id
        WHERE um.user_id = ? AND um.statut IN ('terminee', 'validee', 'payee')
        ORDER BY um.date_completion DESC
    ");
    $stmt->execute([$user_id]);
    $missions_terminees = $stmt->fetchAll();
    
    // Historique des gains
    $stmt = $shop_pdo->prepare("
        SELECT hg.*, m.titre as mission_titre
        FROM historique_gains hg
        JOIN missions m ON hg.mission_id = m.id
        WHERE hg.user_id = ?
        ORDER BY hg.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $historique_gains = $stmt->fetchAll();
    
    // Demandes de retrait
    $stmt = $shop_pdo->prepare("
        SELECT * FROM demandes_retrait 
        WHERE user_id = ? 
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $demandes_retrait = $stmt->fetchAll();
    
    // Statistiques
    $stats = [
        'missions_disponibles' => count($missions_disponibles),
        'missions_en_cours' => count($missions_en_cours),
        'missions_terminees' => count($missions_terminees),
        'solde_euros' => $cagnotte['solde_euros'],
        'solde_points' => $cagnotte['solde_points'],
        'total_gagne_euros' => $cagnotte['total_gagne_euros'],
        'total_gagne_points' => $cagnotte['total_gagne_points']
    ];
    
} catch (PDOException $e) {
    $missions_disponibles = [];
    $missions_en_cours = [];
    $missions_terminees = [];
    $historique_gains = [];
    $demandes_retrait = [];
    $cagnotte = ['solde_euros' => 0, 'solde_points' => 0, 'total_gagne_euros' => 0, 'total_gagne_points' => 0];
    $stats = ['missions_disponibles' => 0, 'missions_en_cours' => 0, 'missions_terminees' => 0, 'solde_euros' => 0, 'solde_points' => 0, 'total_gagne_euros' => 0, 'total_gagne_points' => 0];
    set_message("Erreur lors de la récupération des données.", "error");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Missions - GeekBoard</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Variables CSS pour les thèmes */
        :root {
            /* Mode jour professionnel */
            --day-bg: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            --day-card-bg: rgba(255, 255, 255, 0.95);
            --day-text: #1e293b;
            --day-text-light: #64748b;
            --day-primary: #3b82f6;
            --day-secondary: #1e40af;
            --day-accent: #06b6d4;
            --day-border: rgba(148, 163, 184, 0.2);
            --day-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Mode nuit futuriste */
        body.night-mode {
            --night-bg: radial-gradient(ellipse at top, #0f0f23 0%, #000000 100%);
            --night-card-bg: rgba(15, 15, 35, 0.9);
            --night-text: #e2e8f0;
            --night-text-light: #94a3b8;
            --night-primary: #00d4ff;
            --night-secondary: #0ea5e9;
            --night-accent: #ff00aa;
            --night-border: rgba(0, 212, 255, 0.2);
            --night-glow: 0 0 20px rgba(0, 212, 255, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--day-bg);
            color: var(--day-text);
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        body.night-mode {
            font-family: 'Orbitron', monospace;
            background: var(--night-bg);
            color: var(--night-text);
        }

        /* Container principal */
        .missions-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* En-tête */
        .missions-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .missions-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        body.night-mode .missions-title {
            background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: var(--night-glow);
        }

        .missions-subtitle {
            color: var(--day-text-light);
            font-size: 1.1rem;
        }

        body.night-mode .missions-subtitle {
            color: var(--night-text-light);
        }

        /* Cagnotte */
        .cagnotte-section {
            background: var(--day-card-bg);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: var(--day-shadow);
            border: 1px solid var(--day-border);
        }

        body.night-mode .cagnotte-section {
            background: var(--night-card-bg);
            border-color: var(--night-border);
            box-shadow: var(--night-glow);
        }

        .cagnotte-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .cagnotte-item {
            text-align: center;
            padding: 1.5rem;
            background: rgba(59, 130, 246, 0.05);
            border-radius: 12px;
            border: 1px solid var(--day-border);
        }

        body.night-mode .cagnotte-item {
            background: rgba(0, 212, 255, 0.1);
            border-color: var(--night-border);
        }

        .cagnotte-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--day-primary);
            margin-bottom: 0.5rem;
        }

        body.night-mode .cagnotte-value {
            color: var(--night-primary);
        }

        .cagnotte-label {
            color: var(--day-text-light);
            font-size: 0.9rem;
        }

        body.night-mode .cagnotte-label {
            color: var(--night-text-light);
        }

        /* Statistiques */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--day-card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--day-shadow);
            border: 1px solid var(--day-border);
            text-align: center;
            transition: all 0.3s ease;
        }

        body.night-mode .stat-card {
            background: var(--night-card-bg);
            border-color: var(--night-border);
            box-shadow: var(--night-glow);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        body.night-mode .stat-card:hover {
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.4);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--day-primary);
            margin-bottom: 0.5rem;
        }

        body.night-mode .stat-value {
            color: var(--night-primary);
        }

        .stat-label {
            color: var(--day-text-light);
            font-size: 0.9rem;
        }

        body.night-mode .stat-label {
            color: var(--night-text-light);
        }

        /* Onglets */
        .missions-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--day-border);
            overflow-x: auto;
        }

        body.night-mode .missions-tabs {
            border-bottom-color: var(--night-border);
        }

        .missions-tab {
            padding: 1rem 2rem;
            background: none;
            border: none;
            color: var(--day-text-light);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border-radius: 8px 8px 0 0;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .missions-tab.active {
            color: var(--day-primary);
            background: var(--day-card-bg);
            border-bottom: 2px solid var(--day-primary);
        }

        body.night-mode .missions-tab {
            color: var(--night-text-light);
        }

        body.night-mode .missions-tab.active {
            color: var(--night-primary);
            background: var(--night-card-bg);
            border-bottom-color: var(--night-primary);
            box-shadow: var(--night-glow);
        }

        .tab-badge {
            background: var(--day-primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        body.night-mode .tab-badge {
            background: var(--night-primary);
            color: var(--night-bg);
        }

        /* Contenu des onglets */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Grille de missions */
        .missions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .mission-card {
            background: var(--day-card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--day-shadow);
            border: 1px solid var(--day-border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        body.night-mode .mission-card {
            background: var(--night-card-bg);
            border-color: var(--night-border);
            box-shadow: var(--night-glow);
        }

        .mission-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        body.night-mode .mission-card:hover {
            box-shadow: 0 0 40px rgba(0, 212, 255, 0.4);
        }

        .mission-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--day-primary), var(--day-secondary));
        }

        body.night-mode .mission-card::before {
            background: linear-gradient(90deg, var(--night-primary), var(--night-accent));
        }

        .mission-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .mission-type-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: white;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .mission-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--day-text);
            margin-bottom: 0.75rem;
        }

        body.night-mode .mission-title {
            color: var(--night-text);
        }

        .mission-description {
            color: var(--day-text-light);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        body.night-mode .mission-description {
            color: var(--night-text-light);
        }

        .mission-progress {
            margin-bottom: 1.5rem;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(148, 163, 184, 0.2);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        body.night-mode .progress-bar {
            background: rgba(0, 212, 255, 0.2);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--day-primary), var(--day-secondary));
            transition: width 0.3s ease;
        }

        body.night-mode .progress-fill {
            background: linear-gradient(90deg, var(--night-primary), var(--night-accent));
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: var(--day-text-light);
        }

        body.night-mode .progress-text {
            color: var(--night-text-light);
        }

        .mission-rewards {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .reward-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--day-primary);
        }

        body.night-mode .reward-item {
            background: rgba(0, 212, 255, 0.1);
            color: var(--night-primary);
        }

        .mission-actions {
            display: flex;
            gap: 1rem;
        }

        /* Boutons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1rem;
            flex: 1;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        body.night-mode .btn-primary {
            background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
        }

        body.night-mode .btn-success {
            background: linear-gradient(135deg, var(--night-primary), #00ff88);
        }

        body.night-mode .btn-warning {
            background: linear-gradient(135deg, #ffaa00, var(--night-accent));
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        body.night-mode .btn:hover {
            box-shadow: var(--night-glow);
        }

        /* État vide */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--day-text-light);
        }

        body.night-mode .empty-state {
            color: var(--night-text-light);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--day-text);
        }

        body.night-mode .empty-state h3 {
            color: var(--night-text);
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--day-card-bg);
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        body.night-mode .modal-content {
            background: var(--night-card-bg);
            box-shadow: var(--night-glow);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--day-primary);
        }

        body.night-mode .modal-title {
            color: var(--night-primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--day-text-light);
        }

        body.night-mode .modal-close {
            color: var(--night-text-light);
        }

        /* Formulaires */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--day-text);
        }

        body.night-mode .form-label {
            color: var(--night-text);
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--day-border);
            border-radius: 8px;
            background: var(--day-card-bg);
            color: var(--day-text);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        body.night-mode .form-input,
        body.night-mode .form-select,
        body.night-mode .form-textarea {
            background: rgba(15, 15, 35, 0.8);
            border-color: var(--night-border);
            color: var(--night-text);
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--day-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        body.night-mode .form-input:focus,
        body.night-mode .form-select:focus,
        body.night-mode .form-textarea:focus {
            border-color: var(--night-primary);
            box-shadow: var(--night-glow);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .missions-container {
                padding: 1rem;
            }
            
            .missions-title {
                font-size: 2rem;
            }
            
            .missions-grid {
                grid-template-columns: 1fr;
            }
            
            .missions-tabs {
                flex-direction: column;
            }
            
            .missions-tab {
                text-align: center;
            }
            
            .cagnotte-grid,
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="missions-container">
        <!-- En-tête -->
        <div class="missions-header">
            <h1 class="missions-title">
                <i class="fas fa-tasks"></i>
                Mes Missions v2
            </h1>
            <p class="missions-subtitle">Gagnez de l'argent en accomplissant des missions</p>
        </div>

        <!-- Cagnotte -->
        <div class="cagnotte-section">
            <h2 style="text-align: center; margin-bottom: 2rem; color: var(--day-primary);">
                <i class="fas fa-wallet"></i>
                Ma Cagnotte
            </h2>
            <div class="cagnotte-grid">
                <div class="cagnotte-item">
                    <div class="cagnotte-value"><?= number_format($cagnotte['solde_euros'], 2) ?>€</div>
                    <div class="cagnotte-label">Solde Disponible</div>
                </div>
                <div class="cagnotte-item">
                    <div class="cagnotte-value"><?= $cagnotte['solde_points'] ?></div>
                    <div class="cagnotte-label">Points</div>
                </div>
                <div class="cagnotte-item">
                    <div class="cagnotte-value"><?= number_format($cagnotte['total_gagne_euros'], 2) ?>€</div>
                    <div class="cagnotte-label">Total Gagné</div>
                </div>
                <div class="cagnotte-item">
                    <button onclick="openModal('retraitModal')" class="btn btn-warning" style="width: 100%;">
                        <i class="fas fa-money-bill-wave"></i>
                        Demander un retrait
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-list"></i>
                </div>
                <div class="stat-value"><?= $stats['missions_disponibles'] ?></div>
                <div class="stat-label">Disponibles</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1e40af);">
                    <i class="fas fa-play"></i>
                </div>
                <div class="stat-value"><?= $stats['missions_en_cours'] ?></div>
                <div class="stat-label">En Cours</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?= $stats['missions_terminees'] ?></div>
                <div class="stat-label">Terminées</div>
            </div>
        </div>

        <!-- Onglets -->
        <div class="missions-tabs">
            <button class="missions-tab active" onclick="switchTab('disponibles')">
                <i class="fas fa-list"></i>
                Disponibles
                <span class="tab-badge"><?= count($missions_disponibles) ?></span>
            </button>
            <button class="missions-tab" onclick="switchTab('en-cours')">
                <i class="fas fa-play"></i>
                En Cours
                <span class="tab-badge"><?= count($missions_en_cours) ?></span>
            </button>
            <button class="missions-tab" onclick="switchTab('terminees')">
                <i class="fas fa-check-circle"></i>
                Terminées
                <span class="tab-badge"><?= count($missions_terminees) ?></span>
            </button>
            <button class="missions-tab" onclick="switchTab('historique')">
                <i class="fas fa-history"></i>
                Historique
            </button>
        </div>

        <!-- Onglet Missions Disponibles -->
        <div class="tab-content active" id="tab-disponibles">
            <?php if (empty($missions_disponibles)): ?>
                <div class="empty-state">
                    <i class="fas fa-tasks"></i>
                    <h3>Aucune mission disponible</h3>
                    <p>Revenez plus tard pour découvrir de nouvelles missions !</p>
                </div>
            <?php else: ?>
                <div class="missions-grid">
                    <?php foreach ($missions_disponibles as $mission): ?>
                        <div class="mission-card">
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
                                <div class="reward-item">
                                    <i class="fas fa-users"></i>
                                    <?= $mission['nb_participants'] ?>/<?= $mission['max_participants'] ?: '∞' ?>
                                </div>
                                <?php if ($mission['date_fin']): ?>
                                    <div class="reward-item">
                                        <i class="fas fa-calendar"></i>
                                        Jusqu'au <?= date('d/m/Y', strtotime($mission['date_fin'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mission-actions">
                                <form method="POST" style="width: 100%;">
                                    <input type="hidden" name="action" value="rejoindre_mission">
                                    <input type="hidden" name="mission_id" value="<?= $mission['id'] ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-play"></i>
                                        Rejoindre
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Onglet Missions En Cours -->
        <div class="tab-content" id="tab-en-cours">
            <?php if (empty($missions_en_cours)): ?>
                <div class="empty-state">
                    <i class="fas fa-play"></i>
                    <h3>Aucune mission en cours</h3>
                    <p>Rejoignez une mission pour commencer à gagner de l'argent !</p>
                </div>
            <?php else: ?>
                <div class="missions-grid">
                    <?php foreach ($missions_en_cours as $mission): ?>
                        <div class="mission-card">
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
                                    min(100, ($mission['progression'] / $mission['objectif_nombre']) * 100) : 0;
                                ?>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $progress_percent ?>%"></div>
                                </div>
                                <div class="progress-text">
                                    <span><?= $mission['progression'] ?> / <?= $mission['objectif_nombre'] ?> tâches</span>
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
                                <div class="reward-item">
                                    <i class="fas fa-check-circle"></i>
                                    <?= $mission['validations_approuvees'] ?> validées
                                </div>
                                <div class="reward-item">
                                    <i class="fas fa-clock"></i>
                                    <?= $mission['validations_soumises'] - $mission['validations_approuvees'] ?> en attente
                                </div>
                            </div>
                            
                            <div class="mission-actions">
                                <button onclick="openValidateModal(<?= $mission['id'] ?>, '<?= htmlspecialchars($mission['titre']) ?>')" class="btn btn-success">
                                    <i class="fas fa-check"></i>
                                    Valider une tâche
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Onglet Missions Terminées -->
        <div class="tab-content" id="tab-terminees">
            <?php if (empty($missions_terminees)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>Aucune mission terminée</h3>
                    <p>Terminez vos missions en cours pour les voir ici !</p>
                </div>
            <?php else: ?>
                <div class="missions-grid">
                    <?php foreach ($missions_terminees as $mission): ?>
                        <div class="mission-card">
                            <div class="mission-header">
                                <div class="mission-type-badge" style="background: linear-gradient(135deg, <?= htmlspecialchars($mission['couleur']) ?>, <?= htmlspecialchars($mission['couleur']) ?>aa);">
                                    <i class="<?= htmlspecialchars($mission['icon']) ?>"></i>
                                    <?= htmlspecialchars($mission['type_nom']) ?>
                                </div>
                                <div style="text-align: right; color: var(--day-text-light); font-size: 0.875rem;">
                                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                    Terminée
                                </div>
                            </div>
                            
                            <h3 class="mission-title"><?= htmlspecialchars($mission['titre']) ?></h3>
                            <p class="mission-description"><?= htmlspecialchars($mission['description']) ?></p>
                            
                            <div class="mission-rewards">
                                <?php if ($mission['recompense_euros'] > 0): ?>
                                    <div class="reward-item">
                                        <i class="fas fa-euro-sign"></i>
                                        <?= number_format($mission['recompense_euros'], 2) ?>€ gagné
                                    </div>
                                <?php endif; ?>
                                <?php if ($mission['recompense_points'] > 0): ?>
                                    <div class="reward-item">
                                        <i class="fas fa-star"></i>
                                        <?= $mission['recompense_points'] ?> pts gagné
                                    </div>
                                <?php endif; ?>
                                <div class="reward-item">
                                    <i class="fas fa-tasks"></i>
                                    <?= $mission['objectif_nombre'] ?> tâches
                                </div>
                                <?php if ($mission['date_completion']): ?>
                                    <div class="reward-item">
                                        <i class="fas fa-calendar-check"></i>
                                        <?= date('d/m/Y', strtotime($mission['date_completion'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Onglet Historique -->
        <div class="tab-content" id="tab-historique">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Historique des gains -->
                <div style="background: var(--day-card-bg); border-radius: 16px; padding: 2rem; box-shadow: var(--day-shadow); border: 1px solid var(--day-border);">
                    <h3 style="color: var(--day-primary); margin-bottom: 1.5rem;">
                        <i class="fas fa-coins"></i>
                        Derniers Gains
                    </h3>
                    <?php if (empty($historique_gains)): ?>
                        <p style="color: var(--day-text-light); text-align: center; padding: 2rem;">Aucun gain pour le moment</p>
                    <?php else: ?>
                        <?php foreach ($historique_gains as $gain): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--day-border); margin-bottom: 1rem;">
                                <div>
                                    <div style="font-weight: 600; color: var(--day-text);"><?= htmlspecialchars($gain['mission_titre']) ?></div>
                                    <div style="font-size: 0.875rem; color: var(--day-text-light);"><?= date('d/m/Y H:i', strtotime($gain['created_at'])) ?></div>
                                </div>
                                <div style="font-weight: 700; color: <?= $gain['type'] === 'euros' ? '#10b981' : '#f59e0b' ?>;">
                                    <?= $gain['type'] === 'euros' ? '+' . number_format($gain['montant'], 2) . '€' : '+' . $gain['montant'] . ' pts' ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Demandes de retrait -->
                <div style="background: var(--day-card-bg); border-radius: 16px; padding: 2rem; box-shadow: var(--day-shadow); border: 1px solid var(--day-border);">
                    <h3 style="color: var(--day-primary); margin-bottom: 1.5rem;">
                        <i class="fas fa-money-bill-wave"></i>
                        Mes Retraits
                    </h3>
                    <?php if (empty($demandes_retrait)): ?>
                        <p style="color: var(--day-text-light); text-align: center; padding: 2rem;">Aucune demande de retrait</p>
                    <?php else: ?>
                        <?php foreach ($demandes_retrait as $retrait): ?>
                            <div style="padding: 1rem; border-bottom: 1px solid var(--day-border); margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <div style="font-weight: 700; color: var(--day-text);"><?= number_format($retrait['montant'], 2) ?>€</div>
                                    <span style="padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; 
                                        background: <?= $retrait['statut'] === 'en_attente' ? 'rgba(245, 158, 11, 0.1)' : ($retrait['statut'] === 'approuvee' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)') ?>;
                                        color: <?= $retrait['statut'] === 'en_attente' ? '#d97706' : ($retrait['statut'] === 'approuvee' ? '#059669' : '#dc2626') ?>;">
                                        <?= ucfirst(str_replace('_', ' ', $retrait['statut'])) ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.875rem; color: var(--day-text-light);">
                                    <?= ucfirst($retrait['methode_paiement']) ?> • <?= date('d/m/Y', strtotime($retrait['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de validation de tâche -->
    <div class="modal" id="validateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-check-circle"></i>
                    Valider une tâche
                </h3>
                <button class="modal-close" onclick="closeModal('validateModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="valider_tache">
                <input type="hidden" name="user_mission_id" id="modalUserMissionId">
                
                <div class="form-group">
                    <label for="description_tache" class="form-label">Description de la tâche accomplie *</label>
                    <textarea id="description_tache" name="description_tache" class="form-textarea" rows="4" 
                              placeholder="Décrivez précisément ce que vous avez fait..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="photo_tache" class="form-label">Photo de preuve (optionnel)</label>
                    <input type="file" id="photo_tache" name="photo_tache" class="form-input" accept="image/*">
                    <div style="font-size: 0.875rem; color: var(--day-text-light); margin-top: 0.5rem;">
                        Formats acceptés: JPG, PNG, WebP, GIF (max 5MB)
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="preuve_text" class="form-label">Détails supplémentaires</label>
                    <textarea id="preuve_text" name="preuve_text" class="form-textarea" rows="3"
                              placeholder="Numéro de série, lien vers l'annonce, référence client..."></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('validateModal')" class="btn" style="background: #6b7280; color: white;">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i>
                        Valider la tâche
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de demande de retrait -->
    <div class="modal" id="retraitModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Demander un retrait
                </h3>
                <button class="modal-close" onclick="closeModal('retraitModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="demander_retrait">
                
                <div class="form-group">
                    <label for="montant" class="form-label">Montant à retirer *</label>
                    <input type="number" id="montant" name="montant" class="form-input" 
                           min="1" max="<?= $cagnotte['solde_euros'] ?>" step="0.01" required>
                    <div style="font-size: 0.875rem; color: var(--day-text-light); margin-top: 0.5rem;">
                        Solde disponible: <?= number_format($cagnotte['solde_euros'], 2) ?>€
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="methode_paiement" class="form-label">Méthode de paiement *</label>
                    <select id="methode_paiement" name="methode_paiement" class="form-select" required>
                        <option value="">Sélectionner une méthode</option>
                        <option value="virement">Virement bancaire</option>
                        <option value="paypal">PayPal</option>
                        <option value="especes">Espèces</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="details_paiement" class="form-label">Détails de paiement *</label>
                    <textarea id="details_paiement" name="details_paiement" class="form-textarea" rows="3"
                              placeholder="IBAN, email PayPal, ou autres informations nécessaires..." required></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('retraitModal')" class="btn" style="background: #6b7280; color: white;">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-paper-plane"></i>
                        Envoyer la demande
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Détection automatique du mode nuit
        function detectNightMode() {
            const hour = new Date().getHours();
            if (hour >= 20 || hour < 7) {
                document.body.classList.add('night-mode');
            }
        }

        // Gestion des onglets
        function switchTab(tabName) {
            // Désactiver tous les onglets
            document.querySelectorAll('.missions-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Activer l'onglet cliqué
            event.target.classList.add('active');
            document.getElementById('tab-' + tabName).classList.add('active');
        }

        // Gestion des modals
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function openValidateModal(userMissionId, missionTitle) {
            document.getElementById('modalUserMissionId').value = userMissionId;
            document.querySelector('#validateModal .modal-title').innerHTML = 
                '<i class="fas fa-check-circle"></i> Valider une tâche - ' + missionTitle;
            openModal('validateModal');
        }

        // Fermeture des modals en cliquant en dehors
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            detectNightMode();
            console.log('Mes Missions v2 initialisé');
        });
    </script>
</body>
</html>
