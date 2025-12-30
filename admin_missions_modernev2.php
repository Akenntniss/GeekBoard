<?php
// Inclure la configuration de session et de base de données
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser la session du magasin si ce n'est pas déjà fait
initializeShopSession();

// Vérification des droits d'accès admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    set_message("Accès refusé. Vous devez être administrateur pour accéder à cette page.", "error");
    redirect('accueil');
}

// Obtenir la connexion PDO pour le magasin actuel
$shop_pdo = getShopDBConnection();

// Fallback si getShopDBConnection() échoue
if (!$shop_pdo && isset($_SESSION['shop_id'])) {
    $shop_pdo = getShopDBConnectionById($_SESSION['shop_id']);
}

if (!$shop_pdo) {
    error_log("Erreur critique: Impossible d'obtenir une connexion PDO pour le magasin dans admin_missions_modernev2.php");
    set_message("Erreur de connexion à la base de données du magasin.", "error");
    exit;
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_mission':
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $mission_type_id = (int)($_POST['mission_type_id'] ?? 0);
            $objectif_nombre = (int)($_POST['objectif_nombre'] ?? 1);
            $recompense_euros = (float)($_POST['recompense_euros'] ?? 0);
            $recompense_points = (int)($_POST['recompense_points'] ?? 0);
            $date_debut = $_POST['date_debut'] ?? date('Y-m-d');
            $date_fin = $_POST['date_fin'] ?? null;
            $max_participants = $_POST['max_participants'] ? (int)$_POST['max_participants'] : null;
            
            if (empty($titre) || empty($description) || $mission_type_id <= 0) {
                set_message("Veuillez remplir tous les champs obligatoires.", "error");
            } else {
                try {
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO missions (titre, description, mission_type_id, objectif_nombre, 
                                            recompense_euros, recompense_points, date_debut, date_fin, max_participants) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $titre, $description, $mission_type_id, $objectif_nombre,
                        $recompense_euros, $recompense_points, $date_debut, 
                        $date_fin ?: null, $max_participants
                    ]);
                    set_message("Mission créée avec succès !", "success");
                } catch (PDOException $e) {
                    set_message("Erreur lors de la création de la mission: " . $e->getMessage(), "error");
                }
            }
            break;
            
        case 'update_mission_status':
            $mission_id = (int)($_POST['mission_id'] ?? 0);
            $statut = $_POST['statut'] ?? '';
            
            if ($mission_id > 0 && in_array($statut, ['active', 'inactive', 'terminee'])) {
                try {
                    $stmt = $shop_pdo->prepare("UPDATE missions SET statut = ? WHERE id = ?");
                    $stmt->execute([$statut, $mission_id]);
                    set_message("Statut de la mission mis à jour !", "success");
                } catch (PDOException $e) {
                    set_message("Erreur lors de la mise à jour: " . $e->getMessage(), "error");
                }
            }
            break;
            
        case 'validate_task':
            $validation_id = (int)($_POST['validation_id'] ?? 0);
            $action_type = $_POST['validation_action'] ?? '';
            $commentaire = trim($_POST['commentaire_admin'] ?? '');
            
            if ($validation_id > 0 && in_array($action_type, ['validee', 'refusee'])) {
                try {
                    $shop_pdo->beginTransaction();
                    
                    // Mettre à jour la validation
                    $stmt = $shop_pdo->prepare("
                        UPDATE mission_validations 
                        SET statut = ?, commentaire_admin = ?, validated_at = NOW(), validated_by = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$action_type, $commentaire, $_SESSION['user_id'], $validation_id]);
                    
                    if ($action_type === 'validee') {
                        // Récupérer les infos de la validation
                        $stmt = $shop_pdo->prepare("
                            SELECT mv.user_mission_id, um.user_id, um.mission_id, um.progression, m.objectif_nombre, m.recompense_euros, m.recompense_points
                            FROM mission_validations mv
                            JOIN user_missions um ON mv.user_mission_id = um.id
                            JOIN missions m ON um.mission_id = m.id
                            WHERE mv.id = ?
                        ");
                        $stmt->execute([$validation_id]);
                        $info = $stmt->fetch();
                        
                        if ($info) {
                            // Incrémenter la progression
                            $nouvelle_progression = $info['progression'] + 1;
                            $stmt = $shop_pdo->prepare("UPDATE user_missions SET progression = ? WHERE id = ?");
                            $stmt->execute([$nouvelle_progression, $info['user_mission_id']]);
                            
                            // Vérifier si la mission est terminée
                            if ($nouvelle_progression >= $info['objectif_nombre']) {
                                $stmt = $shop_pdo->prepare("
                                    UPDATE user_missions 
                                    SET statut = 'terminee', date_completion = NOW() 
                                    WHERE id = ?
                                ");
                                $stmt->execute([$info['user_mission_id']]);
                                
                                // Ajouter les gains à la cagnotte
                                if ($info['recompense_euros'] > 0 || $info['recompense_points'] > 0) {
                                    // Créer/mettre à jour la cagnotte utilisateur
                                    $stmt = $shop_pdo->prepare("
                                        INSERT INTO user_cagnotte (user_id, solde_euros, solde_points, total_gagne_euros, total_gagne_points) 
                                        VALUES (?, ?, ?, ?, ?)
                                        ON DUPLICATE KEY UPDATE 
                                        solde_euros = solde_euros + VALUES(solde_euros),
                                        solde_points = solde_points + VALUES(solde_points),
                                        total_gagne_euros = total_gagne_euros + VALUES(total_gagne_euros),
                                        total_gagne_points = total_gagne_points + VALUES(total_gagne_points)
                                    ");
                                    $stmt->execute([
                                        $info['user_id'], $info['recompense_euros'], $info['recompense_points'],
                                        $info['recompense_euros'], $info['recompense_points']
                                    ]);
                                    
                                    // Historique des gains
                                    if ($info['recompense_euros'] > 0) {
                                        $stmt = $shop_pdo->prepare("
                                            INSERT INTO historique_gains (user_id, mission_id, type, montant, description) 
                                            VALUES (?, ?, 'euros', ?, ?)
                                        ");
                                        $stmt->execute([
                                            $info['user_id'], $info['mission_id'], $info['recompense_euros'],
                                            "Mission terminée: " . $info['recompense_euros'] . "€"
                                        ]);
                                    }
                                    
                                    if ($info['recompense_points'] > 0) {
                                        $stmt = $shop_pdo->prepare("
                                            INSERT INTO historique_gains (user_id, mission_id, type, montant, description) 
                                            VALUES (?, ?, 'points', ?, ?)
                                        ");
                                        $stmt->execute([
                                            $info['user_id'], $info['mission_id'], $info['recompense_points'],
                                            "Mission terminée: " . $info['recompense_points'] . " points"
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                    
                    $shop_pdo->commit();
                    set_message("Validation traitée avec succès !", "success");
                } catch (PDOException $e) {
                    $shop_pdo->rollBack();
                    set_message("Erreur lors du traitement: " . $e->getMessage(), "error");
                }
            }
            break;
            
        case 'process_retrait':
            $retrait_id = (int)($_POST['retrait_id'] ?? 0);
            $action_type = $_POST['retrait_action'] ?? '';
            $commentaire = trim($_POST['commentaire_admin'] ?? '');
            
            if ($retrait_id > 0 && in_array($action_type, ['approuvee', 'refusee', 'payee'])) {
                try {
                    $shop_pdo->beginTransaction();
                    
                    if ($action_type === 'refusee') {
                        // Récupérer le montant pour le rembourser
                        $stmt = $shop_pdo->prepare("SELECT user_id, montant FROM demandes_retrait WHERE id = ?");
                        $stmt->execute([$retrait_id]);
                        $retrait = $stmt->fetch();
                        
                        if ($retrait) {
                            // Rembourser le solde
                            $stmt = $shop_pdo->prepare("
                                UPDATE user_cagnotte 
                                SET solde_euros = solde_euros + ? 
                                WHERE user_id = ?
                            ");
                            $stmt->execute([$retrait['montant'], $retrait['user_id']]);
                        }
                    }
                    
                    // Mettre à jour la demande
                    $stmt = $shop_pdo->prepare("
                        UPDATE demandes_retrait 
                        SET statut = ?, commentaire_admin = ?, processed_at = NOW(), processed_by = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$action_type, $commentaire, $_SESSION['user_id'], $retrait_id]);
                    
                    $shop_pdo->commit();
                    set_message("Demande de retrait traitée !", "success");
                } catch (PDOException $e) {
                    $shop_pdo->rollBack();
                    set_message("Erreur lors du traitement: " . $e->getMessage(), "error");
                }
            }
            break;
    }
}

// Récupération des données
try {
    // Types de missions
    $stmt = $shop_pdo->query("SELECT * FROM mission_types ORDER BY nom");
    $mission_types = $stmt->fetchAll();
    
    // Missions avec statistiques
    $stmt = $shop_pdo->query("
        SELECT m.*, mt.nom as type_nom, mt.icon, mt.couleur,
               COUNT(DISTINCT um.user_id) as nb_participants,
               COUNT(DISTINCT CASE WHEN um.statut = 'terminee' THEN um.user_id END) as nb_terminees
        FROM missions m
        LEFT JOIN mission_types mt ON m.mission_type_id = mt.id
        LEFT JOIN user_missions um ON m.id = um.mission_id
        GROUP BY m.id
        ORDER BY m.created_at DESC
    ");
    $missions = $stmt->fetchAll();
    
    // Validations en attente
    $stmt = $shop_pdo->query("
        SELECT mv.*, um.user_id, m.titre as mission_titre, u.full_name
        FROM mission_validations mv
        JOIN user_missions um ON mv.user_mission_id = um.id
        JOIN missions m ON um.mission_id = m.id
        LEFT JOIN users u ON um.user_id = u.id
        WHERE mv.statut = 'en_attente'
        ORDER BY mv.created_at ASC
    ");
    $validations_attente = $stmt->fetchAll();
    
    // Demandes de retrait
    $stmt = $shop_pdo->query("
        SELECT dr.*, u.full_name
        FROM demandes_retrait dr
        LEFT JOIN users u ON dr.user_id = u.id
        WHERE dr.statut IN ('en_attente', 'approuvee')
        ORDER BY dr.created_at ASC
    ");
    $demandes_retrait = $stmt->fetchAll();
    
    // Statistiques générales
    $stmt = $shop_pdo->query("
        SELECT 
            COUNT(DISTINCT m.id) as total_missions,
            COUNT(DISTINCT um.user_id) as total_participants,
            SUM(CASE WHEN um.statut = 'terminee' THEN m.recompense_euros ELSE 0 END) as total_euros_distribues,
            SUM(CASE WHEN um.statut = 'terminee' THEN m.recompense_points ELSE 0 END) as total_points_distribues
        FROM missions m
        LEFT JOIN user_missions um ON m.id = um.mission_id
    ");
    $stats = $stmt->fetch();
    
} catch (PDOException $e) {
    $mission_types = [];
    $missions = [];
    $validations_attente = [];
    $demandes_retrait = [];
    $stats = ['total_missions' => 0, 'total_participants' => 0, 'total_euros_distribues' => 0, 'total_points_distribues' => 0];
    set_message("Erreur lors de la récupération des données.", "error");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration des Missions - GeekBoard</title>
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
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* En-tête */
        .admin-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .admin-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        body.night-mode .admin-title {
            background: linear-gradient(135deg, var(--night-primary), var(--night-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: var(--night-glow);
        }

        .admin-subtitle {
            color: var(--day-text-light);
            font-size: 1.1rem;
        }

        body.night-mode .admin-subtitle {
            color: var(--night-text-light);
        }

        /* Statistiques */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        .admin-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--day-border);
            overflow-x: auto;
        }

        body.night-mode .admin-tabs {
            border-bottom-color: var(--night-border);
        }

        .admin-tab {
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
        }

        .admin-tab.active {
            color: var(--day-primary);
            background: var(--day-card-bg);
            border-bottom: 2px solid var(--day-primary);
        }

        body.night-mode .admin-tab {
            color: var(--night-text-light);
        }

        body.night-mode .admin-tab.active {
            color: var(--night-primary);
            background: var(--night-card-bg);
            border-bottom-color: var(--night-primary);
            box-shadow: var(--night-glow);
        }

        /* Contenu des onglets */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Cartes */
        .card {
            background: var(--day-card-bg);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--day-shadow);
            border: 1px solid var(--day-border);
            margin-bottom: 2rem;
        }

        body.night-mode .card {
            background: var(--night-card-bg);
            border-color: var(--night-border);
            box-shadow: var(--night-glow);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--day-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        body.night-mode .card-title {
            color: var(--night-primary);
        }

        /* Formulaires */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

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
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--day-primary), var(--day-secondary));
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
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

        body.night-mode .btn-danger {
            background: linear-gradient(135deg, var(--night-accent), #ff0066);
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

        /* Tables */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--day-border);
        }

        body.night-mode .table-container {
            border-color: var(--night-border);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: var(--day-card-bg);
        }

        body.night-mode .table {
            background: var(--night-card-bg);
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--day-border);
        }

        body.night-mode .table th,
        body.night-mode .table td {
            border-bottom-color: var(--night-border);
        }

        .table th {
            background: rgba(59, 130, 246, 0.1);
            font-weight: 600;
            color: var(--day-primary);
        }

        body.night-mode .table th {
            background: rgba(0, 212, 255, 0.1);
            color: var(--night-primary);
        }

        .table tr:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        body.night-mode .table tr:hover {
            background: rgba(0, 212, 255, 0.05);
        }

        /* Badges de statut */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .status-inactive {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
        }

        .status-terminee {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .status-en-attente {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        .status-validee {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .status-refusee {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
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

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }
            
            .admin-title {
                font-size: 2rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-tabs {
                flex-direction: column;
            }
            
            .admin-tab {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- En-tête -->
        <div class="admin-header">
            <h1 class="admin-title">
                <i class="fas fa-tasks"></i>
                Administration des Missions v2
            </h1>
            <p class="admin-subtitle">Gérez les missions, validations et cagnottes</p>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1e40af);">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-value"><?= $stats['total_missions'] ?></div>
                <div class="stat-label">Missions Totales</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?= $stats['total_participants'] ?></div>
                <div class="stat-label">Participants</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-value"><?= number_format($stats['total_euros_distribues'], 2) ?>€</div>
                <div class="stat-label">Euros Distribués</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-value"><?= number_format($stats['total_points_distribues']) ?></div>
                <div class="stat-label">Points Distribués</div>
            </div>
        </div>

        <!-- Onglets -->
        <div class="admin-tabs">
            <button class="admin-tab active" onclick="switchTab('missions')">
                <i class="fas fa-tasks"></i>
                Missions
            </button>
            <button class="admin-tab" onclick="switchTab('validations')">
                <i class="fas fa-check-circle"></i>
                Validations (<?= count($validations_attente) ?>)
            </button>
            <button class="admin-tab" onclick="switchTab('retraits')">
                <i class="fas fa-money-bill-wave"></i>
                Retraits (<?= count($demandes_retrait) ?>)
            </button>
        </div>

        <!-- Onglet Missions -->
        <div class="tab-content active" id="tab-missions">
            <!-- Création de mission -->
            <div class="card">
                <h2 class="card-title">
                    <i class="fas fa-plus-circle"></i>
                    Créer une nouvelle mission
                </h2>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create_mission">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="titre" class="form-label">Titre de la mission *</label>
                            <input type="text" id="titre" name="titre" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mission_type_id" class="form-label">Type de mission *</label>
                            <select id="mission_type_id" name="mission_type_id" class="form-select" required>
                                <option value="">Sélectionner un type</option>
                                <?php foreach ($mission_types as $type): ?>
                                    <option value="<?= $type['id'] ?>">
                                        <?= htmlspecialchars($type['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="objectif_nombre" class="form-label">Nombre de tâches *</label>
                            <input type="number" id="objectif_nombre" name="objectif_nombre" class="form-input" min="1" value="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="recompense_euros" class="form-label">Récompense (€)</label>
                            <input type="number" id="recompense_euros" name="recompense_euros" class="form-input" min="0" step="0.01" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="recompense_points" class="form-label">Points</label>
                            <input type="number" id="recompense_points" name="recompense_points" class="form-input" min="0" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="max_participants" class="form-label">Max participants</label>
                            <input type="number" id="max_participants" name="max_participants" class="form-input" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" id="date_debut" name="date_debut" class="form-input" value="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="date" id="date_fin" name="date_fin" class="form-input">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description *</label>
                        <textarea id="description" name="description" class="form-textarea" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Créer la mission
                    </button>
                </form>
            </div>

            <!-- Liste des missions -->
            <div class="card">
                <h2 class="card-title">
                    <i class="fas fa-list"></i>
                    Missions existantes
                </h2>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mission</th>
                                <th>Type</th>
                                <th>Objectif</th>
                                <th>Récompense</th>
                                <th>Participants</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($missions as $mission): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($mission['titre']) ?></strong>
                                            <div style="font-size: 0.875rem; color: var(--day-text-light);">
                                                <?= substr(htmlspecialchars($mission['description']), 0, 100) ?>...
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <i class="<?= htmlspecialchars($mission['icon']) ?>" style="color: <?= htmlspecialchars($mission['couleur']) ?>;"></i>
                                            <?= htmlspecialchars($mission['type_nom']) ?>
                                        </div>
                                    </td>
                                    <td><?= $mission['objectif_nombre'] ?> tâches</td>
                                    <td>
                                        <?php if ($mission['recompense_euros'] > 0): ?>
                                            <div><?= number_format($mission['recompense_euros'], 2) ?>€</div>
                                        <?php endif; ?>
                                        <?php if ($mission['recompense_points'] > 0): ?>
                                            <div><?= $mission['recompense_points'] ?> pts</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $mission['nb_participants'] ?> / <?= $mission['max_participants'] ?: '∞' ?>
                                        <?php if ($mission['nb_terminees'] > 0): ?>
                                            <div style="font-size: 0.875rem; color: var(--day-text-light);">
                                                <?= $mission['nb_terminees'] ?> terminées
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $mission['statut'] ?>">
                                            <?= ucfirst($mission['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_mission_status">
                                            <input type="hidden" name="mission_id" value="<?= $mission['id'] ?>">
                                            <select name="statut" onchange="this.form.submit()" class="form-select" style="width: auto; padding: 0.25rem;">
                                                <option value="active" <?= $mission['statut'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="inactive" <?= $mission['statut'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                <option value="terminee" <?= $mission['statut'] === 'terminee' ? 'selected' : '' ?>>Terminée</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Onglet Validations -->
        <div class="tab-content" id="tab-validations">
            <div class="card">
                <h2 class="card-title">
                    <i class="fas fa-check-circle"></i>
                    Validations en attente
                </h2>
                
                <?php if (empty($validations_attente)): ?>
                    <p style="text-align: center; color: var(--day-text-light); padding: 2rem;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                        Aucune validation en attente
                    </p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Mission</th>
                                    <th>Tâche</th>
                                    <th>Description</th>
                                    <th>Preuve</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($validations_attente as $validation): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($validation['full_name'] ?: 'Utilisateur #' . $validation['user_id']) ?></td>
                                        <td><?= htmlspecialchars($validation['mission_titre']) ?></td>
                                        <td>Tâche #<?= $validation['tache_numero'] ?></td>
                                        <td><?= htmlspecialchars($validation['description']) ?></td>
                                        <td>
                                            <?php if ($validation['preuve_fichier']): ?>
                                                <a href="uploads/missions/<?= htmlspecialchars($validation['preuve_fichier']) ?>" target="_blank" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                    <i class="fas fa-image"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($validation['preuve_text']): ?>
                                                <div style="font-size: 0.875rem; margin-top: 0.25rem;">
                                                    <?= htmlspecialchars($validation['preuve_text']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($validation['created_at'])) ?></td>
                                        <td>
                                            <button onclick="openValidationModal(<?= $validation['id'] ?>, 'validee')" class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.875rem; margin-right: 0.5rem;">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button onclick="openValidationModal(<?= $validation['id'] ?>, 'refusee')" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Onglet Retraits -->
        <div class="tab-content" id="tab-retraits">
            <div class="card">
                <h2 class="card-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Demandes de retrait
                </h2>
                
                <?php if (empty($demandes_retrait)): ?>
                    <p style="text-align: center; color: var(--day-text-light); padding: 2rem;">
                        <i class="fas fa-money-bill-wave" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                        Aucune demande de retrait
                    </p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Montant</th>
                                    <th>Méthode</th>
                                    <th>Détails</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($demandes_retrait as $retrait): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($retrait['full_name'] ?: 'Utilisateur #' . $retrait['user_id']) ?></td>
                                        <td><?= number_format($retrait['montant'], 2) ?>€</td>
                                        <td><?= ucfirst($retrait['methode_paiement']) ?></td>
                                        <td><?= htmlspecialchars($retrait['details_paiement']) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= str_replace('_', '-', $retrait['statut']) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $retrait['statut'])) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($retrait['created_at'])) ?></td>
                                        <td>
                                            <?php if ($retrait['statut'] === 'en_attente'): ?>
                                                <button onclick="openRetraitModal(<?= $retrait['id'] ?>, 'approuvee')" class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.875rem; margin-right: 0.25rem;">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button onclick="openRetraitModal(<?= $retrait['id'] ?>, 'refusee')" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php elseif ($retrait['statut'] === 'approuvee'): ?>
                                                <button onclick="openRetraitModal(<?= $retrait['id'] ?>, 'payee')" class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                    <i class="fas fa-money-bill"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de validation -->
    <div class="modal" id="validationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="validationModalTitle">Validation</h3>
                <button class="modal-close" onclick="closeModal('validationModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="validate_task">
                <input type="hidden" name="validation_id" id="validationId">
                <input type="hidden" name="validation_action" id="validationAction">
                
                <div class="form-group">
                    <label for="commentaire_admin" class="form-label">Commentaire (optionnel)</label>
                    <textarea id="commentaire_admin" name="commentaire_admin" class="form-textarea" rows="3"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('validationModal')" class="btn" style="background: #6b7280; color: white;">
                        Annuler
                    </button>
                    <button type="submit" class="btn" id="validationSubmitBtn">
                        Confirmer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de retrait -->
    <div class="modal" id="retraitModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="retraitModalTitle">Retrait</h3>
                <button class="modal-close" onclick="closeModal('retraitModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="process_retrait">
                <input type="hidden" name="retrait_id" id="retraitId">
                <input type="hidden" name="retrait_action" id="retraitAction">
                
                <div class="form-group">
                    <label for="commentaire_admin_retrait" class="form-label">Commentaire (optionnel)</label>
                    <textarea id="commentaire_admin_retrait" name="commentaire_admin" class="form-textarea" rows="3"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('retraitModal')" class="btn" style="background: #6b7280; color: white;">
                        Annuler
                    </button>
                    <button type="submit" class="btn" id="retraitSubmitBtn">
                        Confirmer
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
            document.querySelectorAll('.admin-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Activer l'onglet cliqué
            event.target.classList.add('active');
            document.getElementById('tab-' + tabName).classList.add('active');
        }

        // Gestion des modals
        function openValidationModal(validationId, action) {
            document.getElementById('validationId').value = validationId;
            document.getElementById('validationAction').value = action;
            
            const title = action === 'validee' ? 'Valider la tâche' : 'Refuser la tâche';
            const btnClass = action === 'validee' ? 'btn-success' : 'btn-danger';
            const btnText = action === 'validee' ? 'Valider' : 'Refuser';
            
            document.getElementById('validationModalTitle').textContent = title;
            document.getElementById('validationSubmitBtn').className = 'btn ' + btnClass;
            document.getElementById('validationSubmitBtn').textContent = btnText;
            
            document.getElementById('validationModal').classList.add('show');
        }

        function openRetraitModal(retraitId, action) {
            document.getElementById('retraitId').value = retraitId;
            document.getElementById('retraitAction').value = action;
            
            let title, btnClass, btnText;
            switch(action) {
                case 'approuvee':
                    title = 'Approuver le retrait';
                    btnClass = 'btn-success';
                    btnText = 'Approuver';
                    break;
                case 'refusee':
                    title = 'Refuser le retrait';
                    btnClass = 'btn-danger';
                    btnText = 'Refuser';
                    break;
                case 'payee':
                    title = 'Marquer comme payé';
                    btnClass = 'btn-warning';
                    btnText = 'Marquer payé';
                    break;
            }
            
            document.getElementById('retraitModalTitle').textContent = title;
            document.getElementById('retraitSubmitBtn').className = 'btn ' + btnClass;
            document.getElementById('retraitSubmitBtn').textContent = btnText;
            
            document.getElementById('retraitModal').classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
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
            console.log('Admin Missions v2 initialisé');
        });
    </script>
</body>
</html>
