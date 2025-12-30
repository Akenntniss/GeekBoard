<?php
// Vérification des droits de base
if (!isset($_SESSION['user_id'])) {
    set_message("Vous devez être connecté pour accéder à cette page.", "danger");
    redirect("");
    exit;
}

// Inclure les fonctions SMS nécessaires
require_once __DIR__ . '/../includes/sms_functions.php';

// Variable pour déterminer le niveau d'accès
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Traitement de l'envoi d'une campagne SMS
$campaign_sent = false;
$campaign_error = null;
$preview_mode = isset($_POST['preview']) && $_POST['preview'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_campaign') {
    // Récupération des données du formulaire
    $template_id = isset($_POST['template_id']) ? (int)$_POST['template_id'] : 0;
    $client_filter = isset($_POST['client_filter']) ? clean_input($_POST['client_filter']) : 'all';
    $date_debut = isset($_POST['date_debut']) ? clean_input($_POST['date_debut']) : '';
    $date_fin = isset($_POST['date_fin']) ? clean_input($_POST['date_fin']) : '';
    $custom_message = isset($_POST['custom_message']) ? $_POST['custom_message'] : '';
    
    // Validation des données
    $errors = [];
    if ($template_id == 0 && empty($custom_message)) {
        $errors[] = "Veuillez sélectionner un modèle ou saisir un message personnalisé.";
    }
    
    if (empty($errors)) {
        try {
            // Construction de la requête pour obtenir les clients selon le filtre
            $sql = "SELECT id, nom, prenom, telephone FROM clients WHERE 1=1";
            $params = [];
            
            if ($client_filter === 'with_repair') {
                $sql = "SELECT DISTINCT c.id, c.nom, c.prenom, c.telephone 
                        FROM clients c 
                        JOIN reparations r ON c.id = r.client_id";
                
                if (!empty($date_debut)) {
                    $sql .= " WHERE r.date_creation >= ?";
                    $params[] = $date_debut;
                    
                    if (!empty($date_fin)) {
                        $sql .= " AND r.date_creation <= ?";
                        $params[] = $date_fin . ' 23:59:59';
                    }
                } elseif (!empty($date_fin)) {
                    $sql .= " WHERE r.date_creation <= ?";
                    $params[] = $date_fin . ' 23:59:59';
                }
            } else {
                // Filtre par date pour tous les clients
                if (!empty($date_debut)) {
                    $sql .= " AND date_creation >= ?";
                    $params[] = $date_debut;
                    
                    if (!empty($date_fin)) {
                        $sql .= " AND date_creation <= ?";
                        $params[] = $date_fin . ' 23:59:59';
                    }
                } elseif (!empty($date_fin)) {
                    $sql .= " AND date_creation <= ?";
                    $params[] = $date_fin . ' 23:59:59';
                }
            }
            
            // Exécution de la requête
            $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare($sql);
            $stmt->execute($params);
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($clients)) {
                $campaign_error = "Aucun client ne correspond aux critères sélectionnés.";
            } else {
                // Si nous sommes en mode aperçu, afficher seulement les clients qui recevraient le SMS
                if ($preview_mode) {
                    $_SESSION['campaign_preview'] = [
                        'clients' => $clients,
                        'template_id' => $template_id,
                        'custom_message' => $custom_message
                    ];
                    
                    redirect("campagne_sms", ["preview" => 1]);
                    exit;
                }
                
                // Sinon, procéder à l'envoi de la campagne
                $message = '';
                if ($template_id > 0) {
                    // Récupération du modèle SMS
                    $template_stmt = $shop_pdo->prepare("SELECT contenu FROM sms_templates WHERE id = ?");
                    $template_stmt->execute([$template_id]);
                    $template = $template_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($template) {
                        $message = $template['contenu'];
                    } else {
                        $campaign_error = "Le modèle sélectionné n'existe pas.";
                    }
                } else {
                    // Utilisation du message personnalisé
                    $message = $custom_message;
                }
                
                if (!empty($message)) {
                    $success_count = 0;
                    $error_count = 0;
                    
                    // Enregistrement de la campagne
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO sms_campaigns (
                            nom, message, date_envoi, nb_destinataires, user_id
                        ) VALUES (?, ?, NOW(), ?, ?)
                    ");
                    $campaign_name = "Campagne du " . date('d/m/Y H:i');
                    $stmt->execute([$campaign_name, $message, count($clients), $_SESSION['user_id']]);
                    $campaign_id = $shop_pdo->lastInsertId();
                    
                    // Récupérer les paramètres d'entreprise une seule fois pour toute la campagne
                    $company_name = 'Maison du Geek';  // Valeur par défaut
                    $company_phone = '08 95 79 59 33';  // Valeur par défaut
                    
                    try {
                        $stmt_company = $shop_pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone')");
                        $stmt_company->execute();
                        $company_params = $stmt_company->fetchAll(PDO::FETCH_KEY_PAIR);
                        
                        if (!empty($company_params['company_name'])) {
                            $company_name = $company_params['company_name'];
                        }
                        if (!empty($company_params['company_phone'])) {
                            $company_phone = $company_params['company_phone'];
                        }
                    } catch (Exception $e) {
                        error_log("Erreur lors de la récupération des paramètres d'entreprise: " . $e->getMessage());
                    }
                    
                    // Envoi à chaque client
                    foreach ($clients as $client) {
                        // Préparation du message personnalisé pour ce client
                        $personalized_message = str_replace(
                            ['[CLIENT_NOM]', '[CLIENT_PRENOM]', '[COMPANY_NAME]', '[COMPANY_PHONE]'],
                            [$client['nom'], $client['prenom'], $company_name, $company_phone],
                            $message
                        );
                        
                        // Envoi du SMS
                        $result = send_sms($client['telephone'], $personalized_message);
                        
                        // Enregistrement du résultat
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO sms_campaign_details (
                                campaign_id, client_id, telephone, message, statut, date_envoi
                            ) VALUES (?, ?, ?, ?, ?, NOW())
                        ");
                        $status = $result['success'] ? 'envoyé' : 'échec';
                        $stmt->execute([
                            $campaign_id,
                            $client['id'],
                            $client['telephone'],
                            $personalized_message,
                            $status
                        ]);
                        
                        // Comptage des succès/échecs
                        if ($result['success']) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                    }
                    
                    // Mise à jour des statistiques de la campagne
                    $stmt = $shop_pdo->prepare("UPDATE sms_campaigns SET nb_envoyes = ?, nb_echecs = ? WHERE id = ?");
                    $stmt->execute([$success_count, $error_count, $campaign_id]);
                    
                    // Message de succès
                    $campaign_sent = true;
                    set_message("Campagne SMS envoyée : $success_count SMS envoyés avec succès, $error_count échecs.", 
                                $error_count > 0 ? "warning" : "success");
                    
                    redirect("campagne_sms");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $campaign_error = "Erreur lors de l'envoi de la campagne : " . $e->getMessage();
            error_log("Erreur campagne SMS: " . $e->getMessage());
        }
    } else {
        $campaign_error = implode("<br>", $errors);
    }
}

// Récupération des modèles de SMS
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->query("
        SELECT id, nom, contenu 
        FROM sms_templates 
        WHERE est_actif = 1
        ORDER BY nom
    ");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $templates = [];
    set_message("Erreur lors de la récupération des modèles : " . $e->getMessage(), "danger");
    error_log("Erreur récupération templates: " . $e->getMessage());
}

// Récupération des campagnes précédentes avec debug
try {
    $shop_pdo = getShopDBConnection();
    
    // Debug: Vérifier la connexion à la bonne base
    $db_name = $shop_pdo->query("SELECT DATABASE()")->fetchColumn();
    error_log("Base de données actuelle pour campagnes: " . $db_name);
    
    // Requête avec debug
    $sql = "
        SELECT c.*, u.full_name as user_full_name
        FROM sms_campaigns c
        LEFT JOIN users u ON c.user_id = u.id
        ORDER BY c.date_envoi DESC
        LIMIT 20
    ";
    
    error_log("Requête campagnes: " . $sql);
    
    $stmt = $shop_pdo->query($sql);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Nombre de campagnes trouvées: " . count($campaigns));
    
} catch (PDOException $e) {
    $campaigns = [];
    set_message("Erreur lors de la récupération des campagnes : " . $e->getMessage(), "danger");
    error_log("Erreur récupération campagnes: " . $e->getMessage());
}

// Mode aperçu
$preview_clients = [];
if (isset($_GET['preview']) && $_GET['preview'] == 1 && isset($_SESSION['campaign_preview'])) {
    $preview_clients = $_SESSION['campaign_preview']['clients'];
    $selected_template_id = $_SESSION['campaign_preview']['template_id'];
    $custom_message = $_SESSION['campaign_preview']['custom_message'];
}
?>

<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Hérite des styles globaux de l'application (header/footer inclus par index.php) */

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 20px;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .card-header.success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }

        .card-header.warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }

        .card-header.danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #4facfe;
            color: #4facfe;
        }

        .btn-outline:hover {
            background: #4facfe;
            color: white;
        }

        .btn-back {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #cce7ff;
            color: #004085;
            border: 1px solid #b8daff;
        }

        .campaigns-list {
            margin-top: 20px;
        }

        .campaign-item {
            background: white;
            border-radius: 10px;
            margin-bottom: 15px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .campaign-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .campaign-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .campaign-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .campaign-date {
            font-size: 14px;
            color: #666;
        }

        .campaign-stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }

        .badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .badge-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: white;
        }

        .badge-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
        }

        .badge-light {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #495057;
            border: 1px solid #e1e5e9;
        }

        .campaign-user {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .campaign-message {
            font-size: 14px;
            color: #777;
            font-style: italic;
            margin-bottom: 10px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border-left: 3px solid #667eea;
        }

        .success-rate {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            color: white;
        }

        .success-rate.high {
            background: #4CAF50;
        }

        .success-rate.medium {
            background: #ff9800;
        }

        .success-rate.low {
            background: #f44336;
        }

        .view-details-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .view-details-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
            min-height: 100vh;
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 0;
            max-width: 800px;
            width: 100%;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease;
            position: relative;
            margin: auto;
            flex-shrink: 0;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 15px 15px 0 0;
            position: relative;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .modal-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 25px;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
        }

        .detail-section {
            margin-bottom: 25px;
        }

        .detail-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-box {
            background: white;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-box:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }

        .stat-box-number {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-box-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { 
                transform: translateY(-50px);
                opacity: 0;
            }
            to { 
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .modal {
                padding: 10px;
            }
            
            .modal-content {
                width: 100%;
                max-width: none;
                max-height: calc(100vh - 20px);
            }
            
            .modal-header {
                padding: 20px 25px;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
        }


        .button-group {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .counter-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .counter-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .counter-chars {
            background: #e3f2fd;
            color: #1976d2;
        }

        .counter-chars.warning {
            background: #fff3e0;
            color: #f57c00;
        }

        .counter-chars.danger {
            background: #ffebee;
            color: #d32f2f;
        }

        .counter-sms {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .variables-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 14px;
            color: #6c757d;
        }

        .variables-info .variable {
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            margin: 0 5px;
            font-family: monospace;
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            border-left: 4px solid;
        }

        .stats-card.success {
            border-left-color: #4CAF50;
        }

        .stats-card.warning {
            border-left-color: #ff9800;
        }

        .stats-card.info {
            border-left-color: #2196F3;
        }

        .responsive-table {
            overflow-x: auto;
            margin: -20px;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .button-group {
                flex-direction: column;
            }
            
            .campaign-stats {
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .campaign-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .stat-item {
                flex: 1;
                min-width: 80px;
            }
        }
        /* Harmonisation avec l'accueil */
        .header { display: none; }
        .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 15px; padding: 32px 24px; margin: 0 12px 20px; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
        /* Remonter le contenu de 50px (padding global est 85px) */
        .page-container { padding-top: 15px !important; }
        .page-header h1 { font-weight: 700; margin: 0 0 6px; }
        .page-header .subtitle { opacity: .9; }
        .kpi-card { height: 100%; border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); }
        .kpi-card .card-body { display: flex; align-items: center; gap: 14px; }
        .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display:flex; align-items:center; justify-content:center; color:#fff; }
        .kpi-number { font-size: 1.6rem; font-weight: 800; margin: 0; }
        .kpi-label { color: #6c757d; margin: 0; font-weight: 600; letter-spacing: .2px; }
        .campaign-item { background:#fff; border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); padding: 16px; margin-bottom: 12px; }
        .campaign-item .title { font-weight: 600; }
        .success-rate.badge { border-radius: 999px; padding: 6px 10px; font-weight: 700; }
        .view-details-btn { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; border-radius:999px; padding:8px 14px; font-weight:700; text-decoration:none; }
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
    
    <div class="page-container" id="mainContent" style="display: none;">
        <div class="page-header">
            <h1><i class="fas fa-sms me-2"></i>Campagnes SMS</h1>
            <div class="subtitle">Créez et suivez vos campagnes, comme sur l'accueil.</div>
            <div class="mt-3">
                <a href="index.php?page=sms_historique" class="btn btn-light me-2"><i class="fas fa-history me-2"></i>Historique</a>
                <?php if ($is_admin): ?><a href="index.php?page=sms_templates" class="btn btn-outline-light"><i class="fas fa-cog me-2"></i>Modèles</a><?php endif; ?>
            </div>
        </div>
    
    <?php if (isset($_GET['preview']) && $_GET['preview'] == 1 && !empty($preview_clients)): ?>
    <!-- Mode aperçu (aligné dashboard) -->
            <div class="container-fluid px-3">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white"><i class="fas fa-eye me-2"></i>Aperçu - <?php echo count($preview_clients); ?> destinataire(s)</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <a href="index.php?page=campagne_sms" class="btn btn-secondary">← Retour</a>
                        <form method="post" class="m-0">
                <input type="hidden" name="action" value="send_campaign">
                <input type="hidden" name="template_id" value="<?php echo $selected_template_id; ?>">
                <input type="hidden" name="custom_message" value="<?php echo htmlspecialchars($custom_message); ?>">
                <input type="hidden" name="client_filter" value="<?php echo isset($_POST['client_filter']) ? $_POST['client_filter'] : 'all'; ?>">
                                <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-2"></i>Envoyer la campagne</button>
                            </form>
                        </div>

                        <div class="alert alert-info">
                    <strong>💬 Message qui sera envoyé :</strong><br><br>
                    <?php
                    $preview_message = '';
                    if ($selected_template_id > 0) {
                        foreach ($templates as $template) {
                            if ($template['id'] == $selected_template_id) {
                                $preview_message = $template['contenu'];
                                break;
                            }
                        }
                    } else {
                        $preview_message = $custom_message;
                    }
                    
                    // Afficher avec le premier client comme exemple
                    if (!empty($preview_clients)) {
                        $example_client = $preview_clients[0];
                        $preview_message = str_replace(
                            ['[CLIENT_NOM]', '[CLIENT_PRENOM]'],
                            [$example_client['nom'], $example_client['prenom']],
                            $preview_message
                        );
                    }
                    ?>
                            <div style="background: white; padding: 20px; border-radius: 8px; margin-top: 10px; font-weight: normal;">
                        <?php echo nl2br(htmlspecialchars($preview_message)); ?>
                            </div>
                        </div>
                        <h3 class="mt-3">👥 Liste des destinataires</h3>
                        <div class="table-responsive">
                        <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Téléphone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview_clients as $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['nom']); ?></td>
                                <td><?php echo htmlspecialchars($client['prenom']); ?></td>
                            <td><span class="badge badge-light"><?php echo htmlspecialchars($client['telephone']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
    <?php else: ?>
    <!-- Formulaire de création de campagne (mise en page dashboard) -->
            <div class="container-fluid px-3">
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header bg-white fw-bold"><i class="fas fa-plus-circle me-2"></i>Nouvelle campagne</div>
                        <div class="card-body">
                <?php if ($campaign_error): ?>
                <div class="alert alert-danger">
                    ⚠️ <?php echo $campaign_error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($campaign_sent): ?>
                <div class="alert alert-success">
                    ✅ Campagne SMS envoyée avec succès !
                </div>
                <?php endif; ?>
                
                <form method="post" id="campaignForm">
                    <input type="hidden" name="action" value="send_campaign">
                    
                                <div class="mb-3">
                                    <label for="template_id" class="form-label">📄 Modèle de SMS</label>
                                    <select class="form-select" id="template_id" name="template_id">
                            <option value="0">-- Message personnalisé --</option>
                            <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template['id']; ?>">
                                <?php echo htmlspecialchars($template['nom']); ?>
                            </option>
                            <?php endforeach; ?>
                                    </select>
                                </div>
                    
                                <div class="mb-3">
                                    <label for="client_filter" class="form-label">🔍 Filtrer les clients</label>
                                    <select class="form-select" id="client_filter" name="client_filter">
                            <option value="all">Tous les clients</option>
                            <option value="with_repair">Clients avec réparations</option>
                                    </select>
                                </div>
                    
                                <div class="mb-3" id="date_filters" style="display: none;">
                                    <label class="form-label">📅 Filtres par date</label>
                                    <div class="row g-2">
                                        <div class="col">
                                            <input type="date" class="form-control" name="date_debut" placeholder="Date de début">
                                        </div>
                                        <div class="col">
                                            <input type="date" class="form-control" name="date_fin" placeholder="Date de fin">
                                        </div>
                                    </div>
                                </div>
                    
                                <div class="mb-2" id="custom_message_container">
                                    <label for="custom_message" class="form-label">✏️ Message personnalisé</label>
                                    <textarea class="form-control" id="custom_message" name="custom_message" rows="4" maxlength="320" placeholder="Saisissez votre message ici..."></textarea>
                                    <div class="counter-info mt-2">
                                        <span id="charCount" class="counter-badge counter-chars">0/320 caractères</span>
                                        <span id="smsCount" class="counter-badge counter-sms">1 SMS</span>
                                    </div>
                                    <div class="variables-info">
                                        ℹ️ Variables disponibles : 
                                        <span class="variable">[CLIENT_NOM]</span>
                                        <span class="variable">[CLIENT_PRENOM]</span>
                                    </div>
                                </div>
                    
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="submit" name="preview" value="1" class="btn btn-outline-primary"><i class="fas fa-eye me-2"></i>Aperçu</button>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Envoyer</button>
                                </div>
                </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header bg-white fw-bold"><i class="fas fa-chart-bar me-2"></i>Campagnes récentes</div>
                        <div class="card-body">
                            <?php if (empty($campaigns)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <div>Aucune campagne SMS trouvée</div>
                                <?php if ($is_admin): ?><div class="small">Debug: Base = <?php echo isset($db_name) ? $db_name : 'inconnue'; ?></div><?php endif; ?>
                            </div>
                            <?php else: ?>
                            <?php foreach ($campaigns as $campaign): ?>
                            <?php $success_rate = $campaign['nb_destinataires'] > 0 ? round(($campaign['nb_envoyes'] / $campaign['nb_destinataires']) * 100) : 0; ?>
                            <div class="campaign-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="title"><?php echo htmlspecialchars($campaign['nom']); ?></div>
                                        <div class="text-muted small">📅 <?php echo date('d/m/Y H:i', strtotime($campaign['date_envoi'])); ?> • 👤 <?php echo $campaign['user_full_name'] ? htmlspecialchars($campaign['user_full_name']) : 'Système'; ?></div>
                                    </div>
                                    <span class="badge success-rate bg-<?php echo $success_rate>=90?'success':($success_rate>=50?'warning':'danger'); ?>"><?php echo $success_rate; ?>%</span>
                                </div>
                                <div class="mt-2 text-muted" style="font-style:italic;">
                                    <?php echo strlen($campaign['message']) > 100 ? htmlspecialchars(substr($campaign['message'], 0, 100)) . '…' : htmlspecialchars($campaign['message']); ?>
                                </div>
                                <div class="d-flex align-items-center gap-3 mt-2">
                                    <span class="badge bg-light text-dark border">Dest: <?php echo (int)$campaign['nb_destinataires']; ?></span>
                                    <span class="badge bg-success">Envoyés: <?php echo (int)$campaign['nb_envoyes']; ?></span>
                                    <span class="badge bg-danger">Échecs: <?php echo (int)$campaign['nb_echecs']; ?></span>
                                    <a class="view-details-btn ms-auto" href="javascript:void(0)" onclick="showCampaignDetails(<?php echo $campaign['id']; ?>)">Détails</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            </div>
    
    <!-- Historique des campagnes -->
        <div class="card">
            <div class="card-header">
                📊 Historique des campagnes
            </div>
            <div class="card-body">
                <?php if (empty($campaigns)): ?>
                <div class="empty-state">
                    <div style="font-size: 4rem; margin-bottom: 20px;">📪</div>
                    <h3>Aucune campagne SMS trouvée</h3>
                    <p>Les campagnes que vous enverrez apparaîtront ici.</p>
                    <?php if ($is_admin): ?>
                    <p><small>Debug: Base actuelle = <?php echo isset($db_name) ? $db_name : 'inconnue'; ?></small></p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="campaigns-list">
                            <?php foreach ($campaigns as $campaign): ?>
                    <div class="campaign-item">
                        <div class="campaign-header">
                            <div>
                                <div class="campaign-title">
                                    <?php echo htmlspecialchars($campaign['nom']); ?>
                                </div>
                                <div class="campaign-date">
                                    📅 <?php echo date('d/m/Y à H:i', strtotime($campaign['date_envoi'])); ?>
                                </div>
                                <div class="campaign-user">
                                    👤 <?php echo $campaign['user_full_name'] ? htmlspecialchars($campaign['user_full_name']) : 'Système'; ?>
                                </div>
                            </div>
                            <div>
                                    <?php 
                                    $success_rate = $campaign['nb_destinataires'] > 0 
                                        ? round(($campaign['nb_envoyes'] / $campaign['nb_destinataires']) * 100) 
                                        : 0;
                                    
                                $rate_class = 'low';
                                if ($success_rate >= 90) {
                                    $rate_class = 'high';
                                } elseif ($success_rate >= 50) {
                                    $rate_class = 'medium';
                                }
                                ?>
                                <div class="success-rate <?php echo $rate_class; ?>">
                                    <?php echo $success_rate; ?>% de succès
                                        </div>
                                    </div>
                        </div>
                        
                        <div class="campaign-message">
                            💬 <?php echo strlen($campaign['message']) > 100 ? substr($campaign['message'], 0, 100) . '...' : $campaign['message']; ?>
                        </div>
                        
                        <div class="campaign-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $campaign['nb_destinataires']; ?></div>
                                <div class="stat-label">Destinataires</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $campaign['nb_envoyes']; ?></div>
                                <div class="stat-label">Envoyés</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $campaign['nb_echecs']; ?></div>
                                <div class="stat-label">Échecs</div>
                            </div>
                            <div class="stat-item">
                                <button class="view-details-btn" onclick="showCampaignDetails(<?php echo $campaign['id']; ?>)">
                                    🔍 Voir détails
                                </button>
                            </div>
                        </div>
                    </div>
                            <?php endforeach; ?>
                </div>
                <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

    <!-- Modal pour les détails de campagne -->
    <div id="campaignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="modalTitle">Détails de la campagne</h2>
                    <div class="modal-subtitle" id="modalDate"></div>
                </div>
                <button class="modal-close" onclick="closeCampaignModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-section">
                    <div class="detail-title">💬 Message envoyé</div>
                    <div class="detail-content" id="modalMessage"></div>
                </div>
                
                <div class="detail-section">
                    <div class="detail-title">👤 Créé par</div>
                    <div class="detail-content" id="modalUser"></div>
                </div>
                
                <div class="detail-section">
                    <div class="detail-title">📊 Statistiques détaillées</div>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-box-number" id="modalDestinataires">0</div>
                            <div class="stat-box-label">Destinataires</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-box-number" id="modalEnvoyes">0</div>
                            <div class="stat-box-label">Envoyés</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-box-number" id="modalEchecs">0</div>
                            <div class="stat-box-label">Échecs</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-box-number" id="modalTaux">0%</div>
                            <div class="stat-box-label">Taux de succès</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Données des campagnes pour le modal
        const campaignsData = <?php echo json_encode($campaigns); ?>;
        
        // Fonction pour afficher les détails d'une campagne
        function showCampaignDetails(campaignId) {
            const campaign = campaignsData.find(c => c.id == campaignId);
            if (!campaign) return;
            
            // Calcul du taux de succès
            const successRate = campaign.nb_destinataires > 0 
                ? Math.round((campaign.nb_envoyes / campaign.nb_destinataires) * 100) 
                : 0;
            
            // Remplir le modal avec les données
            document.getElementById('modalTitle').textContent = campaign.nom;
            document.getElementById('modalDate').textContent = 'Envoyée le ' + new Date(campaign.date_envoi).toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('modalMessage').textContent = campaign.message;
            document.getElementById('modalUser').textContent = campaign.user_full_name || 'Système';
            document.getElementById('modalDestinataires').textContent = campaign.nb_destinataires;
            document.getElementById('modalEnvoyes').textContent = campaign.nb_envoyes;
            document.getElementById('modalEchecs').textContent = campaign.nb_echecs;
            document.getElementById('modalTaux').textContent = successRate + '%';
            
            // Colorer le taux selon le succès
            const tauxElement = document.getElementById('modalTaux');
            tauxElement.style.color = successRate >= 90 ? '#4CAF50' : (successRate >= 50 ? '#ff9800' : '#f44336');
            
            // Afficher le modal
            const modal = document.getElementById('campaignModal');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        // Fonction pour fermer le modal
        function closeCampaignModal() {
            const modal = document.getElementById('campaignModal');
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
        
        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('campaignModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCampaignModal();
            }
        });
        
        // Fermer le modal avec la touche Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCampaignModal();
            }
        });

document.addEventListener('DOMContentLoaded', function() {
            // Gestion de l'affichage du message personnalisé
    const templateSelect = document.getElementById('template_id');
    const customMessageContainer = document.getElementById('custom_message_container');
    const customMessageTextarea = document.getElementById('custom_message');
            const clientFilter = document.getElementById('client_filter');
            const dateFilters = document.getElementById('date_filters');
    
    function toggleCustomMessage() {
        if (templateSelect.value === '0') {
            customMessageContainer.style.display = 'block';
            customMessageTextarea.setAttribute('required', 'required');
        } else {
            customMessageContainer.style.display = 'none';
            customMessageTextarea.removeAttribute('required');
        }
    }
            
            function toggleDateFilters() {
                if (clientFilter.value === 'with_repair' || clientFilter.value === 'all') {
                    dateFilters.style.display = 'block';
                } else {
                    dateFilters.style.display = 'none';
        }
    }
    
    templateSelect.addEventListener('change', toggleCustomMessage);
            clientFilter.addEventListener('change', toggleDateFilters);
            
            // Initialisation
            toggleCustomMessage();
            toggleDateFilters();
            
            // Compteur de caractères
    const charCount = document.getElementById('charCount');
    const smsCount = document.getElementById('smsCount');
    
    function updateCounter() {
        const length = customMessageTextarea.value.length;
        charCount.textContent = length + "/320 caractères";
        
        // Calcul du nombre de SMS
        let count = 1;
        if (length <= 160) {
            smsCount.textContent = "1 SMS";
        } else {
            count = Math.ceil(length / 153);
            smsCount.textContent = count + " SMS";
        }
        
                // Couleurs selon le nombre de caractères
                charCount.className = 'counter-badge counter-chars';
        if (length > 300) {
                    charCount.className += ' danger';
                } else if (length > 250) {
                    charCount.className += ' warning';
        }
    }
    
    customMessageTextarea.addEventListener('input', updateCounter);
    
            // Animation d'entrée pour les cartes
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
    });
});
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

.page-container,
.page-container * {
  background: transparent !important;
}

.campaign-item,
.modal-content {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .campaign-item,
.dark-mode .modal-content {
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