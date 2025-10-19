<?php
// Initialiser la session du magasin pour les appels directs
if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
    // Fonction pour détecter et initialiser la session du magasin
    function initializeShopSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Détecter le sous-domaine
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $subdomain = '';
        
        if (strpos($host, 'localhost') !== false) {
            $subdomain = 'mkmkmk'; // Par défaut en local
        } else {
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                $subdomain = $parts[0];
            }
        }
        
        // Se connecter à la base principale pour récupérer les infos du magasin
        $main_pdo = getMainDBConnection();
        if ($main_pdo) {
            try {
                $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1 LIMIT 1");
                $stmt->execute([$subdomain]);
                $shop = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($shop) {
                    $_SESSION['shop_id'] = $shop['id'];
                    $_SESSION['shop_name'] = $shop['name'];
                    $_SESSION['current_database'] = $shop['db_name'];
                    error_log("Session magasin initialisée pour: " . $shop['name'] . " (ID: " . $shop['id'] . ")");
                } else {
                    error_log("Aucun magasin trouvé pour le sous-domaine: " . $subdomain);
                }
            } catch (PDOException $e) {
                error_log("Erreur lors de l'initialisation de la session magasin: " . $e->getMessage());
            }
        }
    }
    
    initializeShopSession();
}

// Vérifier si l'ID de la réparation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_message("ID réparation non spécifié.", "danger");
    redirect("reparations");
}

$reparation_id = (int)$_GET['id'];

// Récupérer les informations de la réparation
try {
    $shop_pdo = getShopDBConnection();
    
    // Vérifier que la connexion est établie
    if (!$shop_pdo) {
        throw new Exception("Impossible de se connecter à la base de données du magasin");
    }
$stmt = $shop_pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        set_message("Réparation non trouvée.", "danger");
        redirect("reparations");
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des informations de la réparation: " . $e->getMessage(), "danger");
    redirect("reparations");
}

// Vérifier si l'utilisateur est déjà attribué à cette réparation
$user_id = $_SESSION['user_id'];
$est_attribue = false;

try {
    // Vérifier si cette réparation est assignée à l'utilisateur connecté
    // et si l'utilisateur a cette réparation comme réparation active
    $stmt = $shop_pdo->prepare("
        SELECT u.active_repair_id, r.employe_id 
        FROM users u, reparations r
        WHERE u.id = ? AND r.id = ?
        ");
        $stmt->execute([$user_id, $reparation_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
    if ($result) {
        // La réparation est attribuée à cet utilisateur ET c'est sa réparation active
        if ($result['employe_id'] == $user_id && $result['active_repair_id'] == $reparation_id) {
            $est_attribue = true;
        }
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la vérification de l'attribution: " . $e->getMessage());
}

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_notes':
            $notes_techniques = clean_input($_POST['notes_techniques'] ?? '');
            try {
        $stmt = $shop_pdo->prepare("UPDATE reparations SET notes_techniques = ? WHERE id = ?");
        $stmt->execute([$notes_techniques, $reparation_id]);
        set_message("Notes techniques mises à jour avec succès!", "success");
        redirect("index.php?page=statut_rapide&id=" . $reparation_id);
    } catch (PDOException $e) {
        set_message("Erreur lors de la mise à jour des notes techniques: " . $e->getMessage(), "danger");
    }
            break;
            
        case 'restitue':
            try {
        $stmt = $shop_pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
        $stmt->execute([$reparation_id]);
        $ancien_statut = $stmt->fetchColumn();
        
                $stmt = $shop_pdo->prepare("UPDATE reparations SET statut = 'restitue', statut_categorie = 5, date_modification = NOW() WHERE id = ?");
                $stmt->execute([$reparation_id]);
                
                set_message("Réparation marquée comme restituée avec succès!", "success");
                redirect("reparations");
        } catch (PDOException $e) {
                set_message("Erreur lors du changement de statut: " . $e->getMessage(), "danger");
            }
            break;
            
        case 'gardiennage':
            try {
            $stmt = $shop_pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
            $stmt->execute([$reparation_id]);
            $ancien_statut = $stmt->fetchColumn();
            
                $stmt = $shop_pdo->prepare("UPDATE reparations SET statut = 'en_gardiennage', statut_categorie = 3, date_modification = NOW() WHERE id = ?");
            $stmt->execute([$reparation_id]);
            
                set_message("Appareil mis en gardiennage avec succès!", "success");
                redirect("index.php?page=statut_rapide&id=" . $reparation_id);
        } catch (PDOException $e) {
                set_message("Erreur lors du changement de statut: " . $e->getMessage(), "danger");
            }
            break;
    }
}

// Récupérer les photos de la réparation
$photos = [];
try {
    $stmt = $shop_pdo->prepare("SELECT * FROM photos_reparation WHERE reparation_id = ? ORDER BY date_upload DESC");
    $stmt->execute([$reparation_id]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Inclure la photo initiale (prise en charge) si elle existe et n'est pas déjà listée
    if (!empty($reparation['photo_appareil'])) {
        $initialUrl = $reparation['photo_appareil'];
        $alreadyPresent = false;
        foreach ($photos as $p) {
            if (($p['url'] ?? '') === $initialUrl) {
                $alreadyPresent = true;
                break;
            }
        }
        if (!$alreadyPresent) {
            $photos[] = [
                'url' => $initialUrl,
                'date_upload' => $reparation['date_reception'] ?? date('Y-m-d H:i:s')
            ];
        }
    }
    // Normaliser les URLs pour l'affichage web et les rendre absolues
    $docRootPrefix = '/var/www/mdgeek.top/';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'mdgeek.top';
    foreach ($photos as &$p) {
        $u = isset($p['url']) ? trim($p['url']) : '';
        if ($u === '') { continue; }
        // Si chemin système absolu, convertir vers URL relative au docroot
        if (strpos($u, $docRootPrefix) === 0) {
            $u = substr($u, strlen($docRootPrefix));
        }
        // Nettoyer les préfixes relatifs
        if (strpos($u, './') === 0) {
            $u = substr($u, 2);
        }
        // Ajouter un slash initial si ce n'est ni http(s) ni déjà absolu web
        if (stripos($u, 'http://') !== 0 && stripos($u, 'https://') !== 0 && strpos($u, '/') !== 0) {
            $u = '/' . $u;
        }
        // Construire une URL absolue avec schéma+hôte si nécessaire
        if (stripos($u, 'http://') !== 0 && stripos($u, 'https://') !== 0) {
            $u = $scheme . '://' . $host . $u;
        }
        $p['url'] = $u;
    }
    unset($p);

    // Préparer des informations de debug sur les fichiers réels
    $photosDebug = [];
    $docRoot = '/var/www/mdgeek.top';
    foreach ($photos as $pp) {
        $u = $pp['url'] ?? '';
        $path = '';
        if ($u !== '') {
            if (stripos($u, 'http://') === 0 || stripos($u, 'https://') === 0) {
                $parsed = parse_url($u);
                $path = $parsed['path'] ?? '';
            } else {
                $path = $u;
            }
            if ($path !== '' && strpos($path, '/') !== 0) {
                $path = '/' . $path;
            }
        }
        $serverPath = rtrim($docRoot, '/') . $path;
        $exists = ($path !== '') ? file_exists($serverPath) : false;
        $readable = ($path !== '') ? is_readable($serverPath) : false;
        $size = ($exists) ? @filesize($serverPath) : 0;
        $photosDebug[] = [
            'url' => $u,
            'path' => $path,
            'server_path' => $serverPath,
            'exists' => $exists,
            'readable' => $readable,
            'size' => $size
        ];
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des photos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statut Rapide - Réparation #<?php echo $reparation_id; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Thème sombre -->
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <link rel="stylesheet" href="assets/css/modern-theme.css">

<style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);     /* Violet */
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);     /* Vert */
            --warning-gradient: linear-gradient(135deg, #ff9a56 0%, #ff6b35 100%);     /* Orange */
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);        /* Bleu */
            --danger-gradient: linear-gradient(135deg, #ff4757 0%, #ff3742 100%);      /* Rouge */
            --teal-gradient: linear-gradient(135deg, #06beb6 0%, #48cae4 100%);        /* Bleu-vert */
            --coral-gradient: linear-gradient(135deg, #ff6b6b 0%, #ffa726 100%);       /* Corail */
            --indigo-gradient: linear-gradient(135deg, #5c6bc0 0%, #7986cb 100%);      /* Indigo */
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .status-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    overflow: hidden;
            margin-bottom: 20px;
        }

        .header-section {
            background: var(--primary-gradient);
            color: white;
            padding: 30px;
    position: relative;
    overflow: hidden;
        }

        .header-section::before {
    content: '';
    position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></svg>');
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .header-content {
    position: relative;
    z-index: 2;
    display: flex;
            justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
            gap: 20px;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
    color: white;
            border: 2px solid rgba(255,255,255,0.3);
            width: 50px;
            height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
    color: white;
            transform: translateY(-2px);
        }

        .repair-title {
            font-size: 2rem;
            font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
            gap: 15px;
        }

        .status-badge {
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 1rem;
    font-weight: 600;
}

        .info-section {
            padding: 30px;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f4ff 100%);
        }

        .info-grid {
    display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .info-card {
            background: white;
            padding: 25px;
    border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .info-card.client { border-left-color: #667eea; }
        .info-card.device { border-left-color: #11998e; }
        .info-card.price { border-left-color: #f093fb; cursor: pointer; }

        .info-card h3 {
            font-size: 1.2rem;
    font-weight: 600;
            margin-bottom: 15px;
            color: #2d3748;
    display: flex;
            align-items: center;
            gap: 10px;
}

.info-item {
    display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
}

.info-item i {
            width: 20px;
    text-align: center;
            opacity: 0.7;
        }

        .price-display {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
    text-align: center;
            margin: 15px 0;
        }

        .actions-section {
            padding: 30px;
        }

        .actions-title {
    text-align: center;
            margin-bottom: 30px;
}

        .actions-title h2 {
            font-size: 2rem;
    font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 20px;
        }

        .action-btn {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 25px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-height: 160px;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
            z-index: 1;
        }
        
        .action-btn-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
        }

        .action-btn:hover::before {
            transform: scaleX(1);
        }

        .action-btn:hover {
            border-color: #667eea;
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.15);
            text-decoration: none;
            color: inherit;
        }

        .action-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }

        /* Palette harmonieuse - Nouvelle disposition rangée 2 */
        .action-icon.start { background: var(--success-gradient); animation: pulse 2s infinite; }      /* Vert - Action positive */
        .action-icon.stop { background: var(--danger-gradient); animation: pulse 2s infinite; }       /* Rouge - Action finale */
        .action-icon.quote { background: var(--warning-gradient); }                                    /* Orange - Commercial */
        .action-icon.sms { background: var(--info-gradient); }                                         /* Bleu - Communication */
        .action-icon.notes { background: var(--indigo-gradient); }                                     /* Indigo - Documentation */
        .action-icon.order { background: var(--teal-gradient); }                                       /* Bleu-vert - Commande */
        .action-icon.return { background: var(--success-gradient); }                                   /* Vert - Restitution */
        .action-icon.storage { background: var(--info-gradient); }                                     /* Bleu - Stockage */
        .action-icon.photos { background: var(--indigo-gradient); }                                    /* Indigo - Média */
        .action-icon.price { background: var(--warning-gradient); }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .action-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
            text-align: center;
            width: 100%;
            display: block;
        }

        .action-description {
            color: #718096;
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
            text-align: center;
            width: 100%;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 10px;
            }
            
            .header-content {
                flex-direction: column;
    text-align: center;
            }
            
            .repair-title {
                font-size: 1.5rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(4, 1fr);
            }
            
            .action-icon {
    width: 70px;
    height: 70px;
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .actions-grid {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
            }
        }

        /* Correction mode sombre pour les images */
        .dark-mode .photo-thumbnail,
        body.dark-mode .photo-thumbnail {
            filter: none !important;
            -webkit-filter: none !important;
        }
        
        /* Optimisations performance */
        .photo-thumbnail {
            will-change: transform;
            backface-visibility: hidden;
        }
        
        .action-btn {
            contain: layout style paint;
        }
        
        /* ===== STYLES POUR LE MODE NUIT ===== */
        body.dark-mode {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%) !important;
            color: #f1f5f9 !important;
            min-height: 100vh;
        }
        
        body.dark-mode .status-card {
            background: rgba(30, 41, 59, 0.95) !important;
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        body.dark-mode .header-section {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
            color: #f1f5f9 !important;
        }
        
        body.dark-mode .info-section {
            background: rgba(51, 65, 85, 0.8) !important;
            color: #e2e8f0 !important;
        }
        
        body.dark-mode .actions-section {
            background: rgba(51, 65, 85, 0.6) !important;
        }
        
        body.dark-mode .action-btn {
            background: rgba(59, 130, 246, 0.1) !important;
            border: 2px solid rgba(59, 130, 246, 0.3) !important;
            color: #93c5fd !important;
        }
        
        body.dark-mode .action-btn:hover {
            background: rgba(59, 130, 246, 0.2) !important;
            border-color: rgba(59, 130, 246, 0.5) !important;
            color: #dbeafe !important;
            transform: translateY(-2px);
        }
        
        body.dark-mode .action-title {
            color: #dbeafe !important;
            text-align: center;
        }
        
        body.dark-mode .action-description {
            color: #93c5fd !important;
            text-align: center;
        }
        
        body.dark-mode .modal-content {
            background: rgba(30, 41, 59, 0.95) !important;
            border: 1px solid rgba(148, 163, 184, 0.2) !important;
            color: #e2e8f0 !important;
        }
        
        body.dark-mode .modal-header {
            background: rgba(51, 65, 85, 0.8) !important;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2) !important;
        }
        
        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background: rgba(51, 65, 85, 0.8) !important;
            border: 1px solid rgba(148, 163, 184, 0.3) !important;
            color: #e2e8f0 !important;
        }
        
        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            background: rgba(51, 65, 85, 0.9) !important;
            border-color: rgba(59, 130, 246, 0.5) !important;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
            color: #f1f5f9 !important;
        }
        
        body.dark-mode .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
            border: none !important;
        }
        
        body.dark-mode .btn-success {
            background: linear-gradient(135deg, #10b981, #059669) !important;
            border: none !important;
        }
        
        body.dark-mode .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            border: none !important;
        }
        
        body.dark-mode .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
            border: none !important;
        }
        
        body.dark-mode .btn-outline-primary {
            border-color: rgba(59, 130, 246, 0.5) !important;
            color: #93c5fd !important;
        }
        
        body.dark-mode .btn-outline-primary:hover {
            background: rgba(59, 130, 246, 0.2) !important;
            border-color: rgba(59, 130, 246, 0.7) !important;
            color: #dbeafe !important;
        }
        
        body.dark-mode .text-primary {
            color: #93c5fd !important;
        }
        
        body.dark-mode .text-muted {
            color: #94a3b8 !important;
        }
        
        /* Cartes d'information (Client, Appareil, Prix) en mode nuit */
        body.dark-mode .info-card {
            background: rgba(30, 41, 59, 0.95) !important;
            border: 1px solid rgba(148, 163, 184, 0.2) !important;
            color: #e2e8f0 !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
        }
        
        body.dark-mode .info-card h3 {
            color: #f1f5f9 !important;
        }
        
        body.dark-mode .info-card .info-item {
            color: #e2e8f0 !important;
        }
        
        body.dark-mode .info-card .info-item i {
            color: #93c5fd !important;
        }
        
        body.dark-mode .info-card.price {
            background: rgba(30, 41, 59, 0.95) !important;
            border: 2px solid rgba(59, 130, 246, 0.3) !important;
        }
        
        body.dark-mode .info-card.price:hover {
            background: rgba(30, 41, 59, 1) !important;
            border-color: rgba(59, 130, 246, 0.5) !important;
            transform: translateY(-2px);
        }
        
        body.dark-mode .price-display {
            color: #f1f5f9 !important;
        }
        
        body.dark-mode .info-card small {
            color: #94a3b8 !important;
        }
        
        /* Bordures colorées des cartes en mode nuit */
        body.dark-mode .info-card.client {
            border-left: 4px solid #667eea !important;
        }
        
        body.dark-mode .info-card.device {
            border-left: 4px solid #11998e !important;
        }
        
        body.dark-mode .info-card.price {
            border-left: 4px solid #f093fb !important;
        }
        
        /* Corrections spécifiques pour les modals nouvelles_actions_modal et ajouterCommandeModal */
        /* Force l'affichage et la visibilité des modals */
        #nouvelles_actions_modal.show,
        #ajouterCommandeModal.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 1060 !important;
        }
        
        #nouvelles_actions_modal .modal-dialog,
        #ajouterCommandeModal .modal-dialog {
            transform: none !important;
            pointer-events: auto !important;
        }
        
        /* Permettre aux styles modernes de s'appliquer en priorité */
        #nouvelles_actions_modal .modal-content:not(.modern-modal),
        #ajouterCommandeModal .modal-content:not(.modern-modal) {
            background: #ffffff !important;
            border: none !important;
            border-radius: 20px !important;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25) !important;
            color: #1f2937 !important;
            opacity: 1 !important;
            transform: none !important;
            pointer-events: auto !important;
        }
        
        /* Les modals avec la classe modern-modal gardent leurs styles modernes */
        #nouvelles_actions_modal .modern-modal,
        #ajouterCommandeModal .modern-modal {
            opacity: 1 !important;
            transform: none !important;
            pointer-events: auto !important;
        }
        
        /* Styles de base pour les modals qui n'ont pas encore les classes modernes */
        #nouvelles_actions_modal .modal-content:not(.modern-modal) .modal-header,
        #ajouterCommandeModal .modal-content:not(.modern-modal) .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border: none !important;
            border-radius: 20px 20px 0 0 !important;
            color: #ffffff !important;
        }
        
        #nouvelles_actions_modal .modal-content:not(.modern-modal) .modal-body,
        #ajouterCommandeModal .modal-content:not(.modern-modal) .modal-body {
            background: #ffffff !important;
            color: #1f2937 !important;
        }
        
        #nouvelles_actions_modal .modal-content:not(.modern-modal) .modal-footer,
        #ajouterCommandeModal .modal-content:not(.modern-modal) .modal-footer {
            background: #f8f9fa !important;
            border: none !important;
            border-radius: 0 0 20px 20px !important;
            color: #1f2937 !important;
        }
        
        /* Assurer que les modals modernes gardent leurs styles natifs */
        #nouvelles_actions_modal .modern-modal,
        #ajouterCommandeModal .modern-modal {
            background: unset !important;
            border: unset !important;
            border-radius: unset !important;
            box-shadow: unset !important;
        }
        
        #nouvelles_actions_modal .form-control,
        #nouvelles_actions_modal .form-select,
        #ajouterCommandeModal .form-control,
        #ajouterCommandeModal .form-select {
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
            color: #1f2937 !important;
        }
        
        #nouvelles_actions_modal .form-control:focus,
        #nouvelles_actions_modal .form-select:focus,
        #ajouterCommandeModal .form-control:focus,
        #ajouterCommandeModal .form-select:focus {
            background: #ffffff !important;
            border-color: #667eea !important;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
            color: #1f2937 !important;
        }
        
        #nouvelles_actions_modal .text-muted,
        #ajouterCommandeModal .text-muted {
            color: #6b7280 !important;
        }
        
        #nouvelles_actions_modal .btn-close,
        #ajouterCommandeModal .btn-close {
            filter: invert(1) !important;
        }
        
        /* Mode sombre spécifique pour ces modals */
        body.dark-mode #nouvelles_actions_modal .modal-content,
        body.dark-mode #ajouterCommandeModal .modal-content {
            background: #1f2937 !important;
            color: #f9fafb !important;
        }
        
        body.dark-mode #nouvelles_actions_modal .modal-body,
        body.dark-mode #ajouterCommandeModal .modal-body {
            background: #1f2937 !important;
            color: #f9fafb !important;
        }
        
        body.dark-mode #nouvelles_actions_modal .modal-footer,
        body.dark-mode #ajouterCommandeModal .modal-footer {
            background: #374151 !important;
            color: #f9fafb !important;
        }
        
        body.dark-mode #nouvelles_actions_modal .form-control,
        body.dark-mode #nouvelles_actions_modal .form-select,
        body.dark-mode #ajouterCommandeModal .form-control,
        body.dark-mode #ajouterCommandeModal .form-select {
            background: #374151 !important;
            border: 1px solid #4b5563 !important;
            color: #f9fafb !important;
        }
        
        body.dark-mode #nouvelles_actions_modal .form-control:focus,
        body.dark-mode #nouvelles_actions_modal .form-select:focus,
        body.dark-mode #ajouterCommandeModal .form-control:focus,
        body.dark-mode #ajouterCommandeModal .form-select:focus {
            background: #374151 !important;
            border-color: #667eea !important;
            color: #f9fafb !important;
        }
        
        body.dark-mode #nouvelles_actions_modal .text-muted,
        body.dark-mode #ajouterCommandeModal .text-muted {
            color: #9ca3af !important;
        }
        
        body.dark-mode #nouvelles_actions_modal .btn-close,
        body.dark-mode #ajouterCommandeModal .btn-close {
            filter: invert(0) !important;
        }
        
        /* Correction des backdrops pour ces modals */
        .modal-backdrop.nouvelles_actions_modal-backdrop,
        .modal-backdrop.ajouterCommandeModal-backdrop {
            z-index: 1055 !important;
            opacity: 0.5 !important;
        }
        
        /* Empêcher la fermeture automatique */
        #nouvelles_actions_modal,
        #ajouterCommandeModal {
            pointer-events: auto !important;
        }
        
        #nouvelles_actions_modal .modal-dialog,
        #ajouterCommandeModal .modal-dialog {
            margin: 1.75rem auto !important;
            max-width: 900px !important;
        }
        
        /* Assurer que le modal de commande a une largeur appropriée */
        #ajouterCommandeModal .modal-dialog {
            max-width: 1000px !important;
            width: 95% !important;
        }
        
        /* Styles spécifiques pour les boutons d'action dans nouvelles_actions_modal */
        #nouvelles_actions_modal .modern-action-card {
            background: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 12px !important;
            transition: all 0.2s ease !important;
        }
        
        #nouvelles_actions_modal .modern-action-card:hover {
            background: #f3f4f6 !important;
            border-color: #667eea !important;
            transform: translateY(-2px) !important;
        }
        
        body.dark-mode #nouvelles_actions_modal .modern-action-card {
            background: #374151 !important;
            border: 1px solid #4b5563 !important;
            color: #f9fafb !important;
        }
        
        body.dark-mode #nouvelles_actions_modal .modern-action-card:hover {
            background: #4b5563 !important;
            border-color: #667eea !important;
        }
        
    </style>
</head>

<body>
    <div class="main-container">
        <!-- Carte principale -->
        <div class="status-card">
            <!-- Header Section -->
            <div class="header-section">
                <div class="header-content">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <a href="index.php?page=reparations" class="back-btn">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h1 class="repair-title">
                            <i class="fas fa-tools"></i>
                            Réparation #<?php echo $reparation_id; ?>
                        </h1>
                    </div>
                    <div class="status-badge">
                        <?php 
                            $statusClass = 'primary';
                            if (strpos(strtolower($reparation['statut']), 'termin') !== false) {
                                $statusClass = 'success';
                            } elseif (strpos(strtolower($reparation['statut']), 'attente') !== false) {
                                $statusClass = 'warning';
                            } elseif (strpos(strtolower($reparation['statut']), 'cours') !== false) {
                                $statusClass = 'info';
                            } elseif (strpos(strtolower($reparation['statut']), 'annul') !== false) {
                                $statusClass = 'danger';
                            }
                        ?>
                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($reparation['statut']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Info Section -->
            <div class="info-section">
                <div class="info-grid">
                    <!-- Carte Client -->
                    <div class="info-card client">
                        <h3><i class="fas fa-user"></i> Client</h3>
                        <div class="info-item">
                            <i class="fas fa-signature"></i>
                            <span><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($reparation['client_telephone']); ?></span>
                        </div>
                    </div>

                    <!-- Carte Appareil -->
                    <div class="info-card device">
                        <h3><i class="fas fa-laptop"></i> <?php echo htmlspecialchars($reparation['type_appareil']); ?></h3>
                        <div class="info-item">
                            <i class="fas fa-tag"></i>
                            <span><strong>Modèle:</strong> <?php echo htmlspecialchars($reparation['modele']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-sticky-note"></i>
                            <span><strong>Note interne:</strong> 
                                <?php echo (!empty($reparation['notes_techniques']) && trim($reparation['notes_techniques']) !== '') ? 
                                    '<span class="text-success"><i class="fas fa-check-circle"></i> Oui</span>' : 
                                    '<span class="text-danger"><i class="fas fa-times-circle"></i> Non</span>'; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><strong>Problème:</strong> <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 50)) . (strlen($reparation['description_probleme']) > 50 ? '...' : ''); ?></span>
                        </div>
                        <?php if (!empty($reparation['mot_de_passe'])): ?>
                        <div class="info-item">
                            <i class="fas fa-key"></i>
                            <span><strong>Mot de passe:</strong> <?php echo htmlspecialchars($reparation['mot_de_passe']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Carte Prix -->
                    <div class="info-card price" onclick="openPriceModal()" id="priceCard">
                        <h3><i class="fas fa-euro-sign"></i> Prix</h3>
                        <div class="price-display" id="priceDisplay">
                            <?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 0, '', ' ') . ' €' : 'Non défini'; ?>
                        </div>
                        <small style="color: #718096; text-align: center; display: block;">Cliquer pour modifier</small>
                    </div>
                </div>
            </div>

            <!-- Actions Section -->
            <div class="actions-section">
                <div class="actions-title">
                    <h2>Actions Rapides</h2>
                    <p class="text-muted">Choisissez l'action à effectuer</p>
                </div>

                <div class="actions-grid">
                    <!-- Rangée 1 -->
                    <!-- 1. Démarrer/Terminer -->
                    <?php if (!$est_attribue): ?>
                    <div class="action-btn" onclick="startRepair()">
                        <div class="action-btn-content">
                            <div class="action-icon start">
                                <i class="fas fa-play-circle"></i>
                            </div>
                            <h3 class="action-title">Démarrer</h3>
                            <p class="action-description">Commencer la réparation</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="action-btn" onclick="stopRepair()">
                        <div class="action-btn-content">
                            <div class="action-icon stop">
                                <i class="fas fa-stop-circle"></i>
                            </div>
                            <h3 class="action-title">Terminer</h3>
                            <p class="action-description">Terminer la réparation</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 2. Envoyer devis -->
                    <div class="action-btn" onclick="openQuoteModal()">
                        <div class="action-btn-content">
                            <div class="action-icon quote">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <h3 class="action-title">Envoyer devis</h3>
                            <p class="action-description">Créer et envoyer un devis au client</p>
                        </div>
                    </div>

                    <!-- 3. Envoyer SMS -->
                    <div class="action-btn" onclick="openSmsModal()">
                        <div class="action-btn-content">
                            <div class="action-icon sms">
                                <i class="fas fa-sms"></i>
                            </div>
                            <h3 class="action-title">Envoyer SMS</h3>
                            <p class="action-description">Envoyer un message au client</p>
                        </div>
                    </div>

                    <!-- 4. Notes techniques -->
                    <div class="action-btn" onclick="openNotesModal()">
                        <div class="action-btn-content">
                            <div class="action-icon notes">
                                <i class="fas fa-sticky-note"></i>
                            </div>
                            <h3 class="action-title">Notes techniques</h3>
                            <p class="action-description">Afficher et modifier les notes techniques internes</p>
                        </div>
                    </div>

                    <!-- Rangée 2 -->
                    <!-- 5. Commander pièce -->
                    <div class="action-btn" onclick="openOrderModal()">
                        <div class="action-btn-content">
                            <div class="action-icon order">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h3 class="action-title">Commander pièce</h3>
                            <p class="action-description">Commander une pièce détachée</p>
                        </div>
                    </div>

                    <!-- 6. Restitué -->
                    <div class="action-btn" onclick="markAsReturned()">
                        <div class="action-btn-content">
                            <div class="action-icon return">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="action-title">Restitué</h3>
                            <p class="action-description">Marquer la réparation comme restituée</p>
                        </div>
                    </div>

                    <!-- 7. Gardiennage -->
                    <div class="action-btn" onclick="markAsStorage()">
                        <div class="action-btn-content">
                            <div class="action-icon storage">
                                <i class="fas fa-archive"></i>
                            </div>
                            <h3 class="action-title">Gardiennage</h3>
                            <p class="action-description">Placer l'appareil en gardiennage</p>
                        </div>
                    </div>

                    <!-- 8. Photos -->
                    <div class="action-btn" onclick="openPhotosModal()">
                        <div class="action-btn-content">
                            <div class="action-icon photos">
                                <i class="fas fa-images"></i>
                            </div>
                            <h3 class="action-title">Photos</h3>
                            <p class="action-description">Ajouter et afficher les photos de la réparation</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    
    <!-- Modal Notes Techniques -->
    <div class="modal fade" id="notesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sticky-note me-2"></i>Notes Techniques
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="index.php?page=statut_rapide&id=<?php echo $reparation_id; ?>">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_notes">
                        <div class="mb-3">
                            <label for="notes_techniques" class="form-label">Notes internes (visibles uniquement par les techniciens) :</label>
                            <textarea class="form-control" id="notes_techniques" name="notes_techniques" rows="8" placeholder="Saisissez vos notes techniques ici..."><?php echo html_entity_decode($reparation['notes_techniques'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Prix -->
    <div class="modal fade" id="priceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-euro-sign me-2"></i>Modifier le prix
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div id="currentPrice" class="display-4 text-primary"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2) : '0.00'; ?> €</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-8">
                            <div class="row g-2">
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="1">1</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="2">2</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="3">3</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="4">4</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="5">5</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="6">6</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="7">7</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="8">8</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-primary w-100 numpad-btn" data-value="9">9</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-secondary w-100 numpad-btn" data-value="0">0</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-secondary w-100 numpad-btn" data-value=".">.</button></div>
                                <div class="col-4"><button type="button" class="btn btn-outline-danger w-100" onclick="clearPrice()">C</button></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-warning w-100 mb-2" onclick="backspacePrice()">
                                <i class="fas fa-backspace"></i>
                            </button>
                            <button type="button" class="btn btn-success w-100" onclick="savePrice()" style="height: 150px;">
                                <i class="fas fa-check fa-2x"></i><br>Valider
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Photos -->
    <div class="modal fade" id="photosModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-images me-2"></i>Photos de la réparation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Ajouter une photo</h6>
                            <div class="mb-3">
                                <input type="file" class="form-control" id="photoInput" accept="image/*" multiple>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="uploadPhotos()">
                                <i class="fas fa-upload me-1"></i>Télécharger
                            </button>
                        </div>
                        <div class="col-md-8">
                            <h6>Photos existantes</h6>
                            <div class="row" id="photosGrid">
                                <?php foreach ($photos as $photo): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card">
                                        <?php $imgUrl = ($photo['url'] ?? '') ? ($photo['url'] . (strpos($photo['url'], '?') !== false ? '&' : '?') . 'cb=' . time() . '&nocache=1') : ''; ?>
                                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" class="card-img-top photo-thumbnail" style="height: 150px; object-fit: cover; cursor: pointer;" 
                                             onclick="showPhotoFullscreen('<?php echo htmlspecialchars($imgUrl); ?>')"
                                             onerror="console.error('Erreur image:', this.src); this.style.background='#ff6b6b'; this.style.color='white'; this.style.display='flex'; this.style.alignItems='center'; this.style.justifyContent='center'; this.innerHTML='<div style=\'text-align:center;padding:10px;\'><i class=\'fas fa-exclamation-triangle\'></i><br>Erreur<br><small>' + this.src.split('/').pop() + '</small></div>';"
                                             onload="console.log('Image chargée:', this.src);"
                                             crossorigin="anonymous">
                                        <div class="card-body p-2">
                                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($photo['date_upload'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($photos)): ?>
                                <div class="col-12">
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-images fa-3x mb-3"></i>
                                        <p>Aucune photo disponible</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal SMS -->
    <div class="modal fade" id="smsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sms me-2"></i>Envoyer SMS
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Destinataire</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom'] . ' - ' . $reparation['client_telephone']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="smsMessage" class="form-label">Message</label>
                        <textarea class="form-control" id="smsMessage" rows="4" placeholder="Tapez votre message ici..."></textarea>
                        <div class="form-text">
                            <span id="charCount">0</span>/160 caractères
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="sendSms()">
                        <i class="fas fa-paper-plane me-1"></i>Envoyer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Commande Pièce -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shopping-cart me-2"></i>Commander une pièce
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="orderForm">
                        <div class="mb-3">
                            <label for="partName" class="form-label">Nom de la pièce *</label>
                            <input type="text" class="form-control" id="partName" required>
                        </div>
                        <div class="mb-3">
                            <label for="partQuantity" class="form-label">Quantité *</label>
                            <input type="number" class="form-control" id="partQuantity" min="1" value="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="partPrice" class="form-label">Prix estimé (€)</label>
                            <input type="number" class="form-control" id="partPrice" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label for="partNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="partNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="submitOrder()">
                        <i class="fas fa-save me-1"></i>Commander
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Inclure le modal de devis depuis reparations.php -->
    <?php include BASE_PATH . '/components/modals/devis_modal_clean.php'; ?>
    
    <!-- Inclure les modals principaux -->
    <?php include BASE_PATH . '/includes/modals.php'; ?>
    
    <!-- Modal Lightbox pour affichage plein écran des photos -->
    <div class="modal fade" id="photoLightboxModal" tabindex="-1" aria-labelledby="photoLightboxLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header border-0 p-2">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 text-center">
                    <img id="lightboxImage" src="" class="img-fluid" style="max-height: 90vh; max-width: 100%;">
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS - critique pour les modals -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script pour le modal de devis - différé -->
    <script src="assets/js/devis-clean.js" defer></script>
    
    <!-- Script de débogage pour les modals -->
    <script src="assets/js/modal-debug-statut-rapide.js" defer></script>
    
    <!-- Script pour la gestion des modals de commande -->
    <script src="assets/js/modal-commande.js" defer></script>
    
    <!-- Script pour la gestion de session -->
    <script src="assets/js/session-helper.js" defer></script>

    <script>
        // Configuration critique
        let currentPrice = <?php echo !empty($reparation['prix_reparation']) ? $reparation['prix_reparation'] : 0; ?>;
        const reparationId = <?php echo $reparation_id; ?>;
        
        // Données de la réparation pour le modal de commande
        const reparationData = {
            id: <?php echo $reparation_id; ?>,
            type_appareil: <?php echo json_encode($reparation['type_appareil']); ?>,
            modele: <?php echo json_encode($reparation['modele']); ?>,
            description_probleme: <?php echo json_encode($reparation['description_probleme']); ?>,
            notes_techniques: <?php echo json_encode($reparation['notes_techniques'] ?? ''); ?>,
            client_id: <?php echo $reparation['client_id']; ?>,
            client_nom: <?php echo json_encode($reparation['client_nom']); ?>,
            client_prenom: <?php echo json_encode($reparation['client_prenom']); ?>,
            client_telephone: <?php echo json_encode($reparation['client_telephone']); ?>
        };

        // Cache des modals pour optimiser les performances
        const modalCache = new Map();
        
        function getModal(id) {
            if (!modalCache.has(id)) {
                modalCache.set(id, new bootstrap.Modal(document.getElementById(id)));
            }
            return modalCache.get(id);
        }

        // Fonctions pour les boutons d'action - optimisées
        function openNotesModal() {
            getModal('notesModal').show();
        }

        function openPriceModal() {
            getModal('priceModal').show();
        }

        function openPhotosModal() {
            getModal('photosModal').show();
        }

        function openSmsModal() {
            getModal('smsModal').show();
        }

        function openOrderModal() {
            getModal('ajouterCommandeModal').show();
        }

        function openQuoteModal() {
            // Utiliser la fonction du modal devis clean
            if (typeof window.ouvrirDevisClean === 'function') {
                window.ouvrirDevisClean(reparationId);
                } else {
                // Fallback - ouvrir directement le modal
                const modal = new bootstrap.Modal(document.getElementById('devisModalClean'));
                const reparationIdField = document.getElementById('devis_reparation_id');
                if (reparationIdField) {
                    reparationIdField.value = reparationId;
                }
                modal.show();
            }
        }

        function startRepair() {
            if (confirm('Êtes-vous sûr de vouloir démarrer cette réparation ?')) {
                // Vérifier d'abord si l'utilisateur a déjà une réparation active
                fetch('ajax/repair_assignment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                        action: 'check_active_repair',
                        reparation_id: reparationId
                            }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                        if (data.has_active_repair && data.active_repair.id != reparationId) {
                            // L'utilisateur a déjà une réparation active différente
                            if (confirm('Vous avez déjà une réparation active (#' + data.active_repair.id + '). Voulez-vous la terminer et démarrer cette nouvelle réparation ?')) {
                                // Terminer d'abord la réparation active
                                completeActiveRepairAndStart(data.active_repair.id);
                            }
                            } else {
                            // Aucune réparation active ou c'est la même, procéder au démarrage
                            assignRepair();
                        }
                    } else {
                        alert('Erreur lors de la vérification : ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                    alert('Erreur de connexion lors de la vérification');
                });
            }
        }

        function assignRepair() {
            fetch('ajax/repair_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'assign_repair',
                    reparation_id: reparationId
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Réparation démarrée avec succès !');
                    location.reload();
                    } else {
                    alert('Erreur lors du démarrage : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de connexion lors du démarrage');
            });
        }

        function completeActiveRepairAndStart(activeRepairId) {
            fetch('ajax/repair_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'complete_active_repair',
                    reparation_id: activeRepairId
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Maintenant démarrer la nouvelle réparation
                    assignRepair();
                } else {
                    alert('Erreur lors de la finalisation de la réparation active : ' + data.message);
                }
            })
            .catch(error => {
                        console.error('Erreur:', error);
                alert('Erreur de connexion lors de la finalisation');
            });
        }

        function stopRepair() {
            if (confirm('Êtes-vous sûr de vouloir arrêter cette réparation ?')) {
                fetch('ajax/repair_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                        action: 'complete_active_repair',
                        reparation_id: reparationId
                    }),
            })
            .then(response => response.json())
            .then(data => {
                    if (data.success) {
                        alert('Réparation terminée avec succès !');
                        location.reload();
                } else {
                        alert('Erreur lors de l\'arrêt : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                    alert('Erreur de connexion lors de l\'arrêt');
                });
            }
        }

        function markAsReturned() {
            if (confirm('Êtes-vous sûr de vouloir marquer cette réparation comme restituée ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'restitue';
                
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function markAsStorage() {
            if (confirm('Êtes-vous sûr de vouloir placer cet appareil en gardiennage ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'gardiennage';
                
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Gestion du clavier numérique pour le prix
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.numpad-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                    const currentPriceElement = document.getElementById('currentPrice');
                    let priceText = currentPriceElement.textContent.replace(' €', '').replace(',', '.');
                    
                    if (priceText === '0.00') {
                        priceText = '';
                    }
                    
                    priceText += value;
                    
                    // Limiter à 2 décimales
                    if (priceText.includes('.')) {
                        const parts = priceText.split('.');
                        if (parts[1] && parts[1].length > 2) {
            return;
                        }
                    }
                    
                    currentPrice = parseFloat(priceText) || 0;
                    currentPriceElement.textContent = priceText + ' €';
                });
            });

            // Gestion du compteur de caractères pour SMS
            const smsMessage = document.getElementById('smsMessage');
            if (smsMessage) {
                smsMessage.addEventListener('input', function() {
                    const count = this.value.length;
                    document.getElementById('charCount').textContent = count;
                    
                    if (count > 160) {
                        document.getElementById('charCount').style.color = 'red';
            } else {
                        document.getElementById('charCount').style.color = '';
                    }
                });
            }
        });

        function clearPrice() {
            currentPrice = 0;
            document.getElementById('currentPrice').textContent = '0.00 €';
        }

        function backspacePrice() {
            const currentPriceElement = document.getElementById('currentPrice');
            let priceText = currentPriceElement.textContent.replace(' €', '');
            priceText = priceText.slice(0, -1);
            
            if (priceText === '' || priceText === '0') {
                priceText = '0.00';
                currentPrice = 0;
                } else {
                currentPrice = parseFloat(priceText) || 0;
            }
            
            currentPriceElement.textContent = priceText + ' €';
        }

        function savePrice() {
            // Envoyer le nouveau prix au serveur
            fetch('ajax/update_price.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reparation_id: reparationId,
                    price: currentPrice
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'affichage
                    document.getElementById('priceDisplay').textContent = currentPrice.toLocaleString('fr-FR') + ' €';
                    // Fermer le modal
                    bootstrap.Modal.getInstance(document.getElementById('priceModal')).hide();
                    // Afficher un message de succès
                    alert('Prix mis à jour avec succès !');
                    // Recharger la page pour voir les changements
                    location.reload();
                } else {
                    alert('Erreur lors de la mise à jour du prix : ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la mise à jour du prix');
            });
        }

        function sendSms() {
            const message = document.getElementById('smsMessage').value;
            if (!message.trim()) {
                alert('Veuillez saisir un message');
        return;
    }
    
            // Envoyer le SMS
            fetch('ajax/send_sms.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reparation_id: reparationId,
                    message: message
                })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
                    alert('SMS envoyé avec succès !');
                    bootstrap.Modal.getInstance(document.getElementById('smsModal')).hide();
                    document.getElementById('smsMessage').value = '';
        } else {
                    alert('Erreur lors de l\'envoi du SMS : ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
                alert('Erreur lors de l\'envoi du SMS');
            });
        }

        function submitOrder() {
            const partName = document.getElementById('partName').value;
            const partQuantity = document.getElementById('partQuantity').value;
            const partPrice = document.getElementById('partPrice').value;
            const partNotes = document.getElementById('partNotes').value;
            
            if (!partName || !partQuantity) {
                alert('Veuillez remplir les champs obligatoires');
            return;
        }
        
            // Envoyer la commande
            fetch('ajax/create_order.php', {
            method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reparation_id: reparationId,
                    part_name: partName,
                    quantity: partQuantity,
                    price: partPrice,
                    notes: partNotes
                })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                    alert('Commande créée avec succès !');
                    bootstrap.Modal.getInstance(document.getElementById('orderModal')).hide();
                    document.getElementById('orderForm').reset();
            } else {
                    alert('Erreur lors de la création de la commande : ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
                alert('Erreur lors de la création de la commande');
            });
        }

        function uploadPhotos() {
            const fileInput = document.getElementById('photoInput');
            const files = fileInput.files;
            
            if (files.length === 0) {
                alert('Veuillez sélectionner au moins une photo');
                    return;
                }
                
            const formData = new FormData();
            formData.append('reparation_id', reparationId);
            
            for (let i = 0; i < files.length; i++) {
                formData.append('photos[]', files[i]);
            }
            
            // Envoyer les photos
            fetch('ajax/upload_photos.php?id=' + reparationId, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Photos téléchargées avec succès !');
                    location.reload(); // Recharger pour voir les nouvelles photos
                } else {
                    console.error('Erreur upload:', data);
                    alert('Erreur lors du téléchargement : ' + (data.error || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors du téléchargement des photos');
            });
        }

        // Fonction pour afficher une photo en plein écran
        function showPhotoFullscreen(photoUrl) {
            const lightboxImage = document.getElementById('lightboxImage');
            const lightboxModal = new bootstrap.Modal(document.getElementById('photoLightboxModal'));
            
            lightboxImage.src = photoUrl;
            lightboxModal.show();
            
        // Fermer le modal avec Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    lightboxModal.hide();
                }
            }, { once: true });
}
</script>

<?php if (!empty($_GET['debug_photos'])): ?>
<div class="container my-4">
    <div class="alert alert-warning">
        <strong>Debug Photos</strong>
        <pre style="white-space: pre-wrap; font-size: 12px; max-height: 300px; overflow: auto;">
<?php echo htmlspecialchars(json_encode($photosDebug ?? [], JSON_PRETTY_PRINT)); ?>
        </pre>
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script pour la détection automatique du mode nuit -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le mode sombre automatiquement
    initAutoDarkMode();
});

/**
 * Initialise la détection automatique du mode sombre
 */
function initAutoDarkMode() {
    // Vérifier les préférences système
    const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");
    
    // Vérifier si le mode sombre est déjà activé dans le localStorage
    const savedDarkMode = localStorage.getItem('darkMode');
    
    // Appliquer le mode sombre selon les préférences
    if (savedDarkMode === 'true' || (savedDarkMode === null && prefersDarkScheme.matches)) {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }
    
    // Écouter les changements de préférences système
    prefersDarkScheme.addEventListener('change', function(e) {
        // Ne changer que si l'utilisateur n'a pas explicitement choisi un mode
        if (localStorage.getItem('darkMode') === null) {
            if (e.matches) {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        }
    });
}
</script>

</body>
</html>
