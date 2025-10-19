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
                    
                    redirect("campagne_sms_no_bootstrap", ["preview" => 1]);
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
                    
                    // Envoi à chaque client
                    foreach ($clients as $client) {
                        // Préparation du message personnalisé pour ce client
                        $personalized_message = str_replace(
                            ['[CLIENT_NOM]', '[CLIENT_PRENOM]'],
                            [$client['nom'], $client['prenom']],
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
                    
                    redirect("campagne_sms_no_bootstrap");
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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campagnes SMS - GeekBoard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

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

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        .badge-success {
            background: #4CAF50;
            color: white;
        }

        .badge-warning {
            background: #ff9800;
            color: white;
        }

        .badge-danger {
            background: #f44336;
            color: white;
        }

        .badge-light {
            background: #f8f9fa;
            color: #495057;
        }

        .progress {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }

        .progress-bar.warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }

        .progress-bar.danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
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

        @media (max-width: 768px) {
            .button-group {
                flex-direction: column;
            }
            
            .table {
                font-size: 14px;
            }
            
            .table th,
            .table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📱 Campagnes SMS</h1>
            <p>Créez et envoyez des campagnes SMS à vos clients</p>
        </div>

        <?php if (isset($_GET['preview']) && $_GET['preview'] == 1 && !empty($preview_clients)): ?>
        <!-- Mode aperçu -->
        <div class="card">
            <div class="card-header">
                👁️ Aperçu de la campagne - <?php echo count($preview_clients); ?> destinataire(s)
            </div>
            <div class="card-body">
                <div class="button-group" style="justify-content: space-between; margin-bottom: 20px;">
                    <a href="index.php?page=campagne_sms_no_bootstrap" class="btn btn-back">← Retour</a>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="send_campaign">
                        <input type="hidden" name="template_id" value="<?php echo $selected_template_id; ?>">
                        <input type="hidden" name="custom_message" value="<?php echo htmlspecialchars($custom_message); ?>">
                        <input type="hidden" name="client_filter" value="<?php echo isset($_POST['client_filter']) ? $_POST['client_filter'] : 'all'; ?>">
                        <button type="submit" class="btn btn-success">🚀 Envoyer la campagne</button>
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
                
                <h3>👥 Liste des destinataires</h3>
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
        <?php else: ?>
        <!-- Formulaire de création de campagne -->
        <div class="card">
            <div class="card-header">
                ➕ Nouvelle campagne SMS
            </div>
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
                    
                    <div class="form-group">
                        <label for="template_id" class="form-label">📄 Modèle de SMS</label>
                        <select class="form-control" id="template_id" name="template_id">
                            <option value="0">-- Message personnalisé --</option>
                            <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template['id']; ?>">
                                <?php echo htmlspecialchars($template['nom']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="client_filter" class="form-label">🔍 Filtrer les clients</label>
                        <select class="form-control" id="client_filter" name="client_filter">
                            <option value="all">Tous les clients</option>
                            <option value="with_repair">Clients avec réparations</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="date_filters" style="display: none;">
                        <label class="form-label">📅 Filtres par date</label>
                        <div style="display: flex; gap: 15px;">
                            <div style="flex: 1;">
                                <input type="date" class="form-control" name="date_debut" placeholder="Date de début">
                            </div>
                            <div style="flex: 1;">
                                <input type="date" class="form-control" name="date_fin" placeholder="Date de fin">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group" id="custom_message_container">
                        <label for="custom_message" class="form-label">✏️ Message personnalisé</label>
                        <textarea class="form-control" id="custom_message" name="custom_message" rows="4" 
                                 maxlength="320" placeholder="Saisissez votre message ici..."></textarea>
                        <div class="counter-info">
                            <span id="charCount" class="counter-badge counter-chars">0/320 caractères</span>
                            <span id="smsCount" class="counter-badge counter-sms">1 SMS</span>
                        </div>
                        <div class="variables-info">
                            ℹ️ Variables disponibles : 
                            <span class="variable">[CLIENT_NOM]</span>
                            <span class="variable">[CLIENT_PRENOM]</span>
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" name="preview" value="1" class="btn btn-outline">👁️ Aperçu</button>
                        <button type="submit" class="btn btn-primary">🚀 Envoyer</button>
                    </div>
                </form>
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
                <table class="table">
                    <thead>
                        <tr>
                            <th>📅 Date</th>
                            <th>📝 Nom</th>
                            <th>👤 Envoyé par</th>
                            <th>📊 Destinataires</th>
                            <th>✅ Taux de succès</th>
                            <th>🔍 Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                        <tr>
                            <td><span class="badge badge-light"><?php echo date('d/m/Y H:i', strtotime($campaign['date_envoi'])); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($campaign['nom']); ?></strong></td>
                            <td>
                                <?php 
                                if ($campaign['user_full_name']) {
                                    echo htmlspecialchars($campaign['user_full_name']);
                                } else {
                                    echo 'Système';
                                }
                                ?>
                            </td>
                            <td><?php echo $campaign['nb_destinataires']; ?></td>
                            <td>
                                <?php 
                                $success_rate = $campaign['nb_destinataires'] > 0 
                                    ? round(($campaign['nb_envoyes'] / $campaign['nb_destinataires']) * 100) 
                                    : 0;
                                
                                $progress_class = '';
                                if ($success_rate < 50) {
                                    $progress_class = 'danger';
                                } elseif ($success_rate < 90) {
                                    $progress_class = 'warning';
                                }
                                ?>
                                <div class="progress">
                                    <div class="progress-bar <?php echo $progress_class; ?>" style="width: <?php echo $success_rate; ?>%">
                                        <?php echo $success_rate; ?>%
                                    </div>
                                </div>
                                <small style="text-align: center; display: block;">
                                    (<?php echo $campaign['nb_envoyes']; ?>/<?php echo $campaign['nb_destinataires']; ?>)
                                </small>
                            </td>
                            <td>
                                <a href="index.php?page=campagne_details&id=<?php echo $campaign['id']; ?>" class="btn btn-outline" style="padding: 8px 12px; font-size: 14px;">
                                    🔍 Détails
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
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
</body>
</html>
