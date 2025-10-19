<?php
// Page de gestion des absences et retards - Utilise la table users
// Session déjà démarrée par config/session_config.php

// Auto-initialisation du système de présence
if (defined('BASE_PATH')) {
    require_once BASE_PATH . '/includes/presence_auto_init.php';
    
    // Auto-initialiser le système de présence si nécessaire
    if (function_exists('isPresenceSystemInitialized') && !isPresenceSystemInitialized()) {
        initializePresenceSystem();
    }
}

// Variables globales pour l'authentification (nécessaires pour l'export)
$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

/**
 * Fonction pour gérer l'upload des documents justificatifs
 */
function handleDocumentUpload($file, $event_type) {
    // Vérifier que le fichier est valide
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erreur lors de l'upload du fichier.");
    }
    
    // Vérifier la taille du fichier (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        throw new Exception("Le fichier est trop volumineux. Taille maximale : 5MB.");
    }
    
    // Types de fichiers autorisés
    $allowed_types = [
        'application/pdf',
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    // Extensions autorisées
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    
    // Obtenir l'extension du fichier
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension'] ?? '');
    
    // Vérifier l'extension
    if (!in_array($extension, $allowed_extensions)) {
        throw new Exception("Type de fichier non autorisé. Formats acceptés : PDF, JPG, PNG, DOC, DOCX.");
    }
    
    // Vérifier le type MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception("Type de fichier non autorisé.");
    }
    
    // Créer le dossier de destination si nécessaire
    $upload_dir = 'uploads/justificatifs/' . date('Y/m');
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception("Impossible de créer le dossier de destination.");
        }
    }
    
    // Générer un nom de fichier unique
    $filename = date('Y-m-d_H-i-s') . '_' . $event_type . '_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . '/' . $filename;
    
    // Déplacer le fichier uploadé
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Erreur lors de la sauvegarde du fichier.");
    }
    
    return $filepath;
}

// Export géré par presence_export_handler.php pour éviter les conflits de headers

// Obtenir la connexion à la base de données
if (function_exists('getShopDBConnection')) {
    $shop_pdo = getShopDBConnection();
}

// Traitement des actions (supprimer, modifier statut)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($shop_pdo)) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                if (isset($_POST['event_id'])) {
                    $stmt = $shop_pdo->prepare("DELETE FROM presence_events WHERE id = ?");
                    $stmt->execute([$_POST['event_id']]);
                    $success_message = "Événement supprimé avec succès.";
                }
                break;
                
            case 'update_status':
                if (isset($_POST['event_id']) && isset($_POST['new_status'])) {
                    $event_id = intval($_POST['event_id']);
                    $new_status = $_POST['new_status'];
                    // Variables déjà définies en début de fichier
                    
                    // Vérifier que l'utilisateur est admin et que le statut est valide
                    if ($is_admin && in_array($new_status, ['approved', 'rejected'])) {
                        $stmt = $shop_pdo->prepare("UPDATE presence_events SET status = ?, approved_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$new_status, $current_user_id, $event_id]);
                        
                        $status_message = $new_status === 'approved' ? 'accepté' : 'rejeté';
                        
                        // Si c'est une requête AJAX, renvoyer du JSON
                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => true,
                                'message' => "Événement $status_message avec succès.",
                                'new_status' => $new_status
                            ]);
                            exit;
                        }
                        
                        $success_message = "Événement $status_message avec succès.";
                    } else {
                        // Si c'est une requête AJAX, renvoyer l'erreur en JSON
                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => false,
                                'message' => "Vous n'avez pas les droits pour effectuer cette action."
                            ]);
                            exit;
                        }
                        
                        $error_message = "Vous n'avez pas les droits pour effectuer cette action.";
                    }
                }
                break;
                
            // L'export est maintenant traité en début de fichier avant le HTML
                
            case 'add_event':
                // Traitement de l'ajout d'événement depuis les modals
                try {
                    $event_type = $_POST['event_type'] ?? '';
                    $user_id = $_POST['user_id'] ?? null;
                    $comment = trim($_POST['comment'] ?? '');
                    // Variable déjà définie en début de fichier
                    
                    // Validation de base
                    if (!$user_id || !$comment || !$event_type) {
                        throw new Exception("Tous les champs sont obligatoires.");
                    }
                    
                    // Gestion de l'upload du document justificatif (facultatif)
                    $document_path = null;
                    if (isset($_FILES['document_justificatif']) && $_FILES['document_justificatif']['error'] == UPLOAD_ERR_OK) {
                        $document_path = handleDocumentUpload($_FILES['document_justificatif'], $event_type);
                    }
                    
                    // Obtenir l'ID du type de présence
                    $stmt = $shop_pdo->prepare("SELECT id FROM presence_types WHERE name = ?");
                    $stmt->execute([$event_type]);
                    $type_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$type_data) {
                        throw new Exception("Type d'événement non valide.");
                    }
                    
                    $type_id = $type_data['id'];
                    
                    // Préparer les données selon le type
                    if ($event_type === 'retard') {
                        $duration_minutes = intval($_POST['duration_minutes'] ?? 0);
                        if ($duration_minutes <= 0 || $duration_minutes > 480) {
                            throw new Exception("Durée invalide (entre 1 et 480 minutes).");
                        }
                        
                        $date_retard = $_POST['date_retard'] ?? '';
                        if (empty($date_retard)) {
                            $date_start = new DateTime(); // Aujourd'hui par défaut
                        } else {
                            $date_start = new DateTime($date_retard);
                        }
                        $date_end = null;
                        
                    } else {
                        // Pour absence, congé payé, congé sans solde
                        $date_debut = $_POST['date_debut'] ?? '';
                        $date_fin = $_POST['date_fin'] ?? '';
                        
                        if (!$date_debut || !$date_fin) {
                            throw new Exception("Les dates de début et fin sont obligatoires.");
                        }
                        
                        $date_start = new DateTime($date_debut);
                        $date_end = new DateTime($date_fin);
                        $duration_minutes = null;
                        
                        if ($date_start > $date_end) {
                            throw new Exception("La date de fin doit être après la date de début.");
                        }
                    }
                    
                    // Insérer l'événement
                    $sql = "INSERT INTO presence_events (employee_id, type_id, date_start, date_end, duration_minutes, comment, document_path, created_by, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                    
                    $stmt = $shop_pdo->prepare($sql);
                    $stmt->execute([
                        $user_id,
                        $type_id,
                        $date_start->format('Y-m-d H:i:s'),
                        $date_end ? $date_end->format('Y-m-d H:i:s') : null,
                        $duration_minutes,
                        $comment,
                        $document_path,
                        $current_user_id
                    ]);
                    
                    // Message de succès sans redirection
                    $success_message = "Événement créé avec succès ! L'événement a été ajouté et est en attente d'approbation.";
                    
                } catch (Exception $e) {
                    $error_message = 'Erreur : ' . $e->getMessage();
                }
                
                break;
        }
    }
}

// Les fonctions d'export ont été remplacées par le traitement en début de fichier

// Vérification du rôle admin et utilisateur connecté (variables déjà définies en début de fichier)

// Récupération des filtres
// Par défaut, afficher l'utilisateur connecté (sauf si admin modifie explicitement)
$filter_user = $_GET['user'] ?? ($current_user_id ?? '');
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_date_start = $_GET['date_start'] ?? '';
$filter_date_end = $_GET['date_end'] ?? '';

// Si l'utilisateur n'est pas admin, forcer le filtre sur son propre ID
if (!$is_admin && $current_user_id) {
    $filter_user = $current_user_id;
}

// Données par défaut si la base n'est pas initialisée
$events = [];
$users = [];
$presence_types = [];

// Variables pour les messages
$success_message = '';
$error_message = '';

if (isset($shop_pdo)) {
    try {
        // Vérifier que la table presence_types existe, sinon l'initialiser
        $stmt = $shop_pdo->prepare("SHOW TABLES LIKE 'presence_types'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            // La table n'existe pas, initialiser le système
            require_once BASE_PATH . '/includes/presence_auto_init.php';
            initializePresenceSystem();
        }
        
        // Construction de la requête avec filtres (utilise users au lieu d'employes)
        $query = "
            SELECT pe.*, u.full_name, u.username, pt.name as type_nom, pt.color_code as couleur
            FROM presence_events pe
            JOIN users u ON pe.employee_id = u.id
            LEFT JOIN presence_types pt ON pe.type_id = pt.id
            WHERE 1=1
        ";

        $params = [];

        if ($filter_user) {
            // Si c'est un nombre (ID utilisateur), filtrer par ID
            if (is_numeric($filter_user)) {
                $query .= " AND pe.employee_id = ?";
                $params[] = $filter_user;
            } else {
                // Sinon, filtrer par nom
                $query .= " AND (u.full_name LIKE ? OR u.username LIKE ?)";
                $params[] = "%$filter_user%";
                $params[] = "%$filter_user%";
            }
        }

        if ($filter_type) {
            $query .= " AND pe.type_id = ?";
            $params[] = $filter_type;
        }

        if ($filter_status) {
            $query .= " AND pe.status = ?";
            $params[] = $filter_status;
        }

        if ($filter_date_start) {
            $query .= " AND pe.date_start >= ?";
            $params[] = $filter_date_start;
        }

        if ($filter_date_end) {
            $query .= " AND pe.date_end <= ?";
            $params[] = $filter_date_end;
        }

        $query .= " ORDER BY pe.date_start DESC, pe.created_at DESC";

        $stmt = $shop_pdo->prepare($query);
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupération des utilisateurs pour le filtre et les modals
        $stmt = $shop_pdo->query("SELECT id, username, full_name FROM users ORDER BY full_name, username");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $all_users = $users; // Pour les modals
        
        // Récupération de l'utilisateur actuel pour les modals (variable déjà définie en début de fichier)
        $current_user_name = '';
        if ($current_user_id) {
            foreach ($users as $user) {
                if ($user['id'] == $current_user_id) {
                    $current_user_name = $user['full_name'] ?: $user['username'];
                    break;
                }
            }
        }
        
        // Détection admin pour les modals (améliorer la détection existante)
        if (!isset($is_admin)) {
            $is_admin = false;
        }
        $is_admin = $is_admin || (
            (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'administrateur', 'superadmin'])) ||
            (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'administrateur', 'superadmin'])) ||
            (isset($_SESSION['superadmin_id']) && $_SESSION['superadmin_id']) ||
            (strpos(strtolower($current_user_name), 'administrateur') !== false)
        );

        // Récupération des types de présence pour le filtre
        try {
            $stmt = $shop_pdo->query("SELECT id, name, color_code as color FROM presence_types ORDER BY name");
            $presence_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // En cas d'erreur, essayer d'initialiser le système et retry
            require_once BASE_PATH . '/includes/presence_auto_init.php';
            initializePresenceSystem();
            $stmt = $shop_pdo->query("SELECT id, name, color_code as color FROM presence_types ORDER BY name");
            $presence_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la récupération des données : " . $e->getMessage();
    }
}
?>

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

<div class="container-fluid" id="mainContent" style="display: none;">
    <div class="row">
        <div class="col-12">
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-user-clock me-2"></i>Gestion des Absences & Retards</h1>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                        <i class="fas fa-plus"></i> Ajouter un événement
                    </button>
                    <a href="index.php?page=presence_calendrier" class="btn btn-info">
                        <i class="fas fa-calendar"></i> Vue calendrier
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="fas fa-print"></i> Imprimer un rapport
                    </button>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-filter me-2"></i>Filtres</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="index.php">
                        <input type="hidden" name="page" value="presence_gestion">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Utilisateur</label>
                                <?php if ($is_admin): ?>
                                    <!-- Admin peut voir tous les utilisateurs -->
                                    <select name="user" class="form-select">
                                        <option value="">Tous les utilisateurs</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>" 
                                                    <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <!-- Utilisateur normal voit seulement son nom -->
                                    <?php
                                    $current_user_name = '';
                                    foreach ($users as $user) {
                                        if ($user['id'] == $current_user_id) {
                                            $current_user_name = $user['full_name'] ?: $user['username'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <input type="hidden" name="user" value="<?php echo $current_user_id; ?>">
                                    <div class="form-control bg-light" style="border: 2px solid #e9ecef;">
                                        <i class="fas fa-user me-2 text-primary"></i>
                                        <strong><?php echo htmlspecialchars($current_user_name); ?></strong>
                                        <small class="text-muted ms-2">(Vos événements)</small>
                                    </div>
                                    <small class="form-text text-muted mt-1">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Vous ne pouvez voir que vos propres événements
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select">
                                    <option value="">Tous les types</option>
                                    <?php foreach ($presence_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" 
                                                <?php echo $filter_type == $type['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Statut</label>
                                <select name="status" class="form-select">
                                    <option value="">Tous les statuts</option>
                                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Approuvé</option>
                                    <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Rejeté</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date début</label>
                                <input type="date" name="date_start" class="form-control" value="<?php echo htmlspecialchars($filter_date_start); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date fin</label>
                                <input type="date" name="date_end" class="form-control" value="<?php echo htmlspecialchars($filter_date_end); ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary stats-card" data-filter="" style="cursor: pointer; transition: transform 0.2s;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo count($events); ?></h4>
                                    <p class="mb-0">Total événements</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-list fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning stats-card" data-filter="pending" style="cursor: pointer; transition: transform 0.2s;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo count(array_filter($events, function($e) { return $e['status'] == 'pending'; })); ?></h4>
                                    <p class="mb-0">En attente</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo count(array_filter($events, function($e) { return $e['status'] == 'approved'; })); ?></h4>
                                    <p class="mb-0">Approuvés</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4><?php echo count(array_filter($events, function($e) { return $e['status'] == 'rejected'; })); ?></h4>
                                    <p class="mb-0">Rejetés</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-times fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des événements -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list me-2"></i>Liste des événements</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($events)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucun événement trouvé</h5>
                            <p class="text-muted">Aucun événement ne correspond aux critères de recherche.</p>
                            <a href="index.php?page=presence_ajouter" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Ajouter le premier événement
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Tableau simple et propre -->
                        <div class="presence-table-wrapper">
                            <table class="presence-events-table">
                                <thead>
                                    <tr>
                                        <th>Utilisateur</th>
                                        <th>Type</th>
                                        <th>Date début</th>
                                        <th>Date fin</th>
                                        <th>Durée</th>
                                        <th class="document-column">Document</th>
                                        <th>Statut</th>
                                        <th>Commentaire</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events as $event): ?>
                                        <tr data-event-id="<?php echo $event['id']; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($event['full_name'] ?: $event['username']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if (isset($event['type_nom']) && isset($event['couleur'])): ?>
                                                    <span class="event-badge" style="background-color: <?php echo htmlspecialchars($event['couleur']); ?>;">
                                                        <?php echo htmlspecialchars($event['type_nom']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="event-badge event-badge-default">Type <?php echo $event['type_id']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $date_start = new DateTime($event['date_start']);
                                                echo $date_start->format('d/m/Y H:i');
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($event['date_end']) {
                                                    $date_end = new DateTime($event['date_end']);
                                                    echo $date_end->format('d/m/Y H:i');
                                                } else {
                                                    echo '<em>Non définie</em>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                if ($event['duration_minutes']) {
                                                    $hours = floor($event['duration_minutes'] / 60);
                                                    $minutes = $event['duration_minutes'] % 60;
                                                    echo $hours > 0 ? "{$hours}h " : "";
                                                    echo $minutes > 0 ? "{$minutes}min" : "";
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td class="document-column">
                                                <?php if (!empty($event['document_path']) && file_exists($event['document_path'])): ?>
                                                    <a href="<?php echo htmlspecialchars($event['document_path']); ?>" 
                                                       target="_blank" 
                                                       class="document-link btn btn-sm btn-outline-primary" 
                                                       title="Voir le document justificatif">
                                                        <i class="fas fa-paperclip"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">
                                                        <i class="fas fa-minus" title="Aucun document"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_classes = [
                                                    'pending' => 'status-pending',
                                                    'approved' => 'status-approved',
                                                    'rejected' => 'status-rejected'
                                                ];
                                                $status_labels = [
                                                    'pending' => 'En attente',
                                                    'approved' => 'Approuvé',
                                                    'rejected' => 'Rejeté'
                                                ];
                                                ?>
                                                <span class="status-badge <?php echo $status_classes[$event['status']] ?? 'status-default'; ?>">
                                                    <?php echo $status_labels[$event['status']] ?? htmlspecialchars($event['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                echo $event['comment'] ? htmlspecialchars(substr($event['comment'], 0, 50)) . (strlen($event['comment']) > 50 ? '...' : '') : '<em>Aucun</em>';
                                                ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php 
                                                    // Boutons admin (Accepter/Rejeter) uniquement si l'événement est en attente
                                                    if ($is_admin && $event['status'] === 'pending'): ?>
                                                        <button type="button" class="action-btn action-btn-approve" 
                                                                onclick="updateEventStatus(<?php echo $event['id']; ?>, 'approved')" title="Accepter">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="action-btn action-btn-reject" 
                                                                onclick="updateEventStatus(<?php echo $event['id']; ?>, 'rejected')" title="Rejeter">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Boutons standard -->
                                                    <a href="index.php?page=presence_modifier&id=<?php echo $event['id']; ?>" 
                                                       class="action-btn action-btn-edit" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($is_admin): ?>
                                                        <button type="button" class="action-btn action-btn-delete" 
                                                                onclick="confirmDelete(<?php echo $event['id']; ?>)" title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
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
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="event_id" id="deleteEventId">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'export PDF -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-print me-2"></i>Générer un rapport à imprimer
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" action="index.php?page=presence_export_print" method="POST">
                    <input type="hidden" name="action" value="export_pdf">
                    
                    <div class="row g-3">
                        <!-- Filtre par employé -->
                        <div class="col-md-6">
                            <label for="export_user" class="form-label">
                                <i class="fas fa-user me-1"></i>Employé
                            </label>
                            <select name="export_user" id="export_user" class="form-select">
                                <option value="">Tous les employés</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filtre par type -->
                        <div class="col-md-6">
                            <label for="export_type" class="form-label">
                                <i class="fas fa-tag me-1"></i>Type d'événement
                            </label>
                            <select name="export_type" id="export_type" class="form-select">
                                <option value="">Tous les types</option>
                                <?php foreach ($presence_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" <?php echo $filter_type == $type['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filtre par statut -->
                        <div class="col-md-4">
                            <label for="export_status" class="form-label">
                                <i class="fas fa-check-circle me-1"></i>Statut
                            </label>
                            <select name="export_status" id="export_status" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>En attente</option>
                                <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Approuvé</option>
                                <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Rejeté</option>
                                <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Annulé</option>
                            </select>
                        </div>

                        <!-- Filtre par dates -->
                        <div class="col-md-4">
                            <label for="export_date_start" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Date début
                            </label>
                            <input type="date" name="export_date_start" id="export_date_start" class="form-control" value="<?php echo htmlspecialchars($filter_date_start); ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="export_date_end" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Date fin
                            </label>
                            <input type="date" name="export_date_end" id="export_date_end" class="form-control" value="<?php echo htmlspecialchars($filter_date_end); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Format d'export</label>
                            <select name="export_format" class="form-select">
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel (XLSX)</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Orientation</label>
                            <select name="orientation" class="form-select">
                                <option value="portrait">Portrait</option>
                                <option value="landscape">Paysage</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Colonnes à inclure</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="user" checked id="col_user">
                                        <label class="form-check-label" for="col_user">Utilisateur</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="type" checked id="col_type">
                                        <label class="form-check-label" for="col_type">Type</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="date_start" checked id="col_date_start">
                                        <label class="form-check-label" for="col_date_start">Date début</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="date_end" id="col_date_end">
                                        <label class="form-check-label" for="col_date_end">Date fin</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="duration" id="col_duration">
                                        <label class="form-check-label" for="col_duration">Durée</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="status" checked id="col_status">
                                        <label class="form-check-label" for="col_status">Statut</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="comment" id="col_comment">
                                        <label class="form-check-label" for="col_comment">Commentaire</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="created_at" id="col_created_at">
                                        <label class="form-check-label" for="col_created_at">Date de création</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Titre du rapport (optionnel)</label>
                            <input type="text" name="report_title" class="form-control" 
                                   placeholder="Rapport des événements de présence" 
                                   value="Rapport des événements de présence">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                                        <button type="button" class="btn btn-success" onclick="submitExport()">
                            <i class="fas fa-print me-1"></i>Générer le rapport
                        </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'ajout d'événement -->
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>Ajouter un Événement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Dashboard de sélection identique -->
                <div class="text-center mb-4">
                    <h4 class="mb-2"><i class="fas fa-calendar-plus me-2"></i>Que souhaitez-vous déclarer ?</h4>
                    <p class="text-muted">Sélectionnez le type d'événement à enregistrer</p>
                </div>
                
                <div class="row g-4">
                    <!-- RETARD -->
                    <div class="col-md-6 col-lg-3">
                        <div class="event-card-modal card h-100 border-0 shadow-sm hover-lift" data-type="retard" style="cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div class="event-icon mb-3" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h5 class="card-title text-dark mb-2">Retard</h5>
                                <p class="card-text text-muted small mb-3">
                                    Déclarer une arrivée tardive ou un départ anticipé
                                </p>
                                <div class="badge bg-warning text-dark">
                                    <i class="fas fa-stopwatch me-1"></i>Ponctuel
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ABSENCE -->
                    <div class="col-md-6 col-lg-3">
                        <div class="event-card-modal card h-100 border-0 shadow-sm hover-lift" data-type="absence" style="cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div class="event-icon mb-3" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <h5 class="card-title text-dark mb-2">Absence</h5>
                                <p class="card-text text-muted small mb-3">
                                    Absence non planifiée ou urgence
                                </p>
                                <div class="badge bg-danger">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Période
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CONGÉ PAYÉ -->
                    <div class="col-md-6 col-lg-3">
                        <div class="event-card-modal card h-100 border-0 shadow-sm hover-lift" data-type="conge_paye" style="cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div class="event-icon mb-3" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                    <i class="fas fa-umbrella-beach"></i>
                                </div>
                                <h5 class="card-title text-dark mb-2">Congé Payé</h5>
                                <p class="card-text text-muted small mb-3">
                                    Vacances, RTT, congé avec rémunération
                                </p>
                                <div class="badge bg-success">
                                    <i class="fas fa-money-bill-wave me-1"></i>Rémunéré
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CONGÉ SANS SOLDE -->
                    <div class="col-md-6 col-lg-3">
                        <div class="event-card-modal card h-100 border-0 shadow-sm hover-lift" data-type="conge_sans_solde" style="cursor: pointer;">
                            <div class="card-body text-center p-4">
                                <div class="event-icon mb-3" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                                    <i class="fas fa-hand-paper"></i>
                                </div>
                                <h5 class="card-title text-dark mb-2">Congé Sans Solde</h5>
                                <p class="card-text text-muted small mb-3">
                                    Congé personnel non rémunéré
                                </p>
                                <div class="badge bg-secondary">
                                    <i class="fas fa-ban me-1"></i>Non rémunéré
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aide rapide -->
                <div class="mt-4">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center py-3">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-question-circle me-2"></i>Besoin d'aide ?
                            </h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <small class="text-muted">
                                        <strong>Retard :</strong> Quelques minutes à quelques heures
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">
                                        <strong>Absence :</strong> Journée complète ou plus
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">
                                        <strong>Congé payé :</strong> Décompté du solde
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">
                                        <strong>Sans solde :</strong> Non rémunéré
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sous-modals pour chaque type d'événement -->

<!-- Modal Retard -->
<div class="modal fade" id="retardModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                <h5 class="modal-title">
                    <i class="fas fa-clock me-2"></i>Déclarer un Retard
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">
                    <i class="fas fa-info-circle me-2"></i>Enregistrer une arrivée tardive ou un départ anticipé
                </p>
                
                <form id="retardForm" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=presence_gestion" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_event">
                    <input type="hidden" name="event_type" value="retard">
                    
                    <!-- Sélection utilisateur -->
                    <div class="mb-3">
                        <label for="retard_user_id" class="form-label">
                            <i class="fas fa-user me-2"></i>Utilisateur concerné
                        </label>
                        <?php if ($is_admin): ?>
                            <select class="form-select" id="retard_user_id" name="user_id" required>
                                <?php foreach ($all_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo ($user['id'] == $current_user_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="user_id" value="<?php echo $current_user_id; ?>">
                            <div class="form-control bg-light">
                                <i class="fas fa-user-check me-2 text-success"></i>
                                <strong><?php echo htmlspecialchars($current_user_name); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Date du retard -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-day me-2"></i>Date du retard
                        </label>
                        <input type="date" class="form-control" name="date_retard" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <!-- Durée -->
                    <div class="mb-3">
                        <label for="retard_duration" class="form-label">
                            <i class="fas fa-stopwatch me-2"></i>Durée du retard (en minutes)
                        </label>
                        <div class="row g-2">
                            <div class="col-8">
                                <input type="number" class="form-control" id="retard_duration" name="duration_minutes" 
                                       min="1" max="480" value="30" required>
                            </div>
                            <div class="col-4">
                                <div class="form-control bg-light text-center">
                                    <strong id="retard_duration_display">30min</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Commentaire -->
                    <div class="mb-3">
                        <label for="retard_comment" class="form-label">
                            <i class="fas fa-comment me-2"></i>Commentaire <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="retard_comment" name="comment" rows="3" 
                                  placeholder="Précisions, justification..." required></textarea>
                    </div>

                    <!-- Document justificatif -->
                    <div class="mb-3">
                        <label for="retard_document" class="form-label">
                            <i class="fas fa-paperclip me-2"></i>Document justificatif <span class="text-muted">(facultatif)</span>
                        </label>
                        <input type="file" class="form-control" id="retard_document" name="document_justificatif" 
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Formats acceptés : PDF, JPG, PNG, DOC, DOCX - Taille max : 5MB
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Enregistrer le retard
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Absence -->
<div class="modal fade" id="absenceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);">
                <h5 class="modal-title">
                    <i class="fas fa-user-times me-2"></i>Déclarer une Absence
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">
                    <i class="fas fa-info-circle me-2"></i>Enregistrer une absence non planifiée
                </p>
                
                <form id="absenceForm" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=presence_gestion" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_event">
                    <input type="hidden" name="event_type" value="absence">
                    
                    <!-- Sélection utilisateur -->
                    <div class="mb-3">
                        <label for="absence_user_id" class="form-label">
                            <i class="fas fa-user me-2"></i>Utilisateur concerné
                        </label>
                        <?php if ($is_admin): ?>
                            <select class="form-select" id="absence_user_id" name="user_id" required>
                                <?php foreach ($all_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo ($user['id'] == $current_user_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="user_id" value="<?php echo $current_user_id; ?>">
                            <div class="form-control bg-light">
                                <i class="fas fa-user-check me-2 text-success"></i>
                                <strong><?php echo htmlspecialchars($current_user_name); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Période d'absence -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>Période d'absence
                        </label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="absence_date_start" class="form-label small">Date de début</label>
                                <input type="date" class="form-control" id="absence_date_start" name="date_debut" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="absence_date_end" class="form-label small">Date de fin</label>
                                <input type="date" class="form-control" id="absence_date_end" name="date_fin" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Durée calculée : <span id="absence_duration" class="fw-bold">1 jour</span>
                        </small>
                    </div>

                    <!-- Commentaire -->
                    <div class="mb-3">
                        <label for="absence_comment" class="form-label">
                            <i class="fas fa-comment me-2"></i>Commentaire <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="absence_comment" name="comment" rows="3" 
                                  placeholder="Précisions, justification..." required></textarea>
                    </div>

                    <!-- Document justificatif -->
                    <div class="mb-3">
                        <label for="absence_document" class="form-label">
                            <i class="fas fa-paperclip me-2"></i>Document justificatif <span class="text-muted">(facultatif)</span>
                        </label>
                        <input type="file" class="form-control" id="absence_document" name="document_justificatif" 
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Formats acceptés : PDF, JPG, PNG, DOC, DOCX - Taille max : 5MB
                            <br><small class="text-success">
                                <i class="fas fa-lightbulb me-1"></i>
                                Ex: arrêt maladie, convocation, certificat médical...
                            </small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-save me-1"></i>Enregistrer l'absence
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Congé Payé -->
<div class="modal fade" id="conge_payeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h5 class="modal-title">
                    <i class="fas fa-umbrella-beach me-2"></i>Déclarer un Congé Payé
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">
                    <i class="fas fa-info-circle me-2"></i>Enregistrer des vacances ou RTT
                </p>
                
                <form id="congePayeForm" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=presence_gestion" method="POST">
                    <input type="hidden" name="action" value="add_event">
                    <input type="hidden" name="event_type" value="conge_paye">
                    
                    <!-- Sélection utilisateur -->
                    <div class="mb-3">
                        <label for="conge_paye_user_id" class="form-label">
                            <i class="fas fa-user me-2"></i>Utilisateur concerné
                        </label>
                        <?php if ($is_admin): ?>
                            <select class="form-select" id="conge_paye_user_id" name="user_id" required>
                                <?php foreach ($all_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo ($user['id'] == $current_user_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="user_id" value="<?php echo $current_user_id; ?>">
                            <div class="form-control bg-light">
                                <i class="fas fa-user-check me-2 text-success"></i>
                                <strong><?php echo htmlspecialchars($current_user_name); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Période de congé -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>Période de congé payé
                        </label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="conge_paye_date_start" class="form-label small">Date de début</label>
                                <input type="date" class="form-control" id="conge_paye_date_start" name="date_debut" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="conge_paye_date_end" class="form-label small">Date de fin</label>
                                <input type="date" class="form-control" id="conge_paye_date_end" name="date_fin" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Durée calculée : <span id="conge_paye_duration" class="fw-bold">1 jour</span>
                        </small>
                    </div>

                    <!-- Commentaire -->
                    <div class="mb-3">
                        <label for="conge_paye_comment" class="form-label">
                            <i class="fas fa-comment me-2"></i>Commentaire <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="conge_paye_comment" name="comment" rows="3" 
                                  placeholder="Précisions, justification..." required></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Enregistrer le congé payé
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Congé Sans Solde -->
<div class="modal fade" id="conge_sans_soldeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                <h5 class="modal-title">
                    <i class="fas fa-hand-paper me-2"></i>Déclarer un Congé Sans Solde
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">
                    <i class="fas fa-info-circle me-2"></i>Enregistrer un congé non rémunéré
                </p>
                
                <form id="congeSansSoldeForm" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=presence_gestion" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_event">
                    <input type="hidden" name="event_type" value="conge_sans_solde">
                    
                    <!-- Sélection utilisateur -->
                    <div class="mb-3">
                        <label for="conge_sans_solde_user_id" class="form-label">
                            <i class="fas fa-user me-2"></i>Utilisateur concerné
                        </label>
                        <?php if ($is_admin): ?>
                            <select class="form-select" id="conge_sans_solde_user_id" name="user_id" required>
                                <?php foreach ($all_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo ($user['id'] == $current_user_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="user_id" value="<?php echo $current_user_id; ?>">
                            <div class="form-control bg-light">
                                <i class="fas fa-user-check me-2 text-success"></i>
                                <strong><?php echo htmlspecialchars($current_user_name); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Période de congé -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>Période de congé sans solde
                        </label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label for="conge_sans_solde_date_start" class="form-label small">Date de début</label>
                                <input type="date" class="form-control" id="conge_sans_solde_date_start" name="date_debut" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="conge_sans_solde_date_end" class="form-label small">Date de fin</label>
                                <input type="date" class="form-control" id="conge_sans_solde_date_end" name="date_fin" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Durée calculée : <span id="conge_sans_solde_duration" class="fw-bold">1 jour</span>
                        </small>
                    </div>

                    <!-- Commentaire -->
                    <div class="mb-3">
                        <label for="conge_sans_solde_comment" class="form-label">
                            <i class="fas fa-comment me-2"></i>Commentaire <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="conge_sans_solde_comment" name="comment" rows="3" 
                                  placeholder="Précisions, justification..." required></textarea>
                    </div>

                    <!-- Document justificatif -->
                    <div class="mb-3">
                        <label for="conge_sans_solde_document" class="form-label">
                            <i class="fas fa-paperclip me-2"></i>Document justificatif <span class="text-muted">(facultatif)</span>
                        </label>
                        <input type="file" class="form-control" id="conge_sans_solde_document" name="document_justificatif" 
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Formats acceptés : PDF, JPG, PNG, DOC, DOCX - Taille max : 5MB
                            <br><small class="text-success">
                                <i class="fas fa-lightbulb me-1"></i>
                                Ex: demande écrite, justificatif personnel...
                            </small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </button>
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-save me-1"></i>Enregistrer le congé sans solde
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* CSS simple et propre pour le tableau de présence */
.presence-table-wrapper {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.presence-events-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
    font-size: 14px;
    background: white;
}

.presence-events-table th {
    background: #343a40;
    color: white;
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
}

.presence-events-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.presence-events-table tbody tr:hover {
    background-color: #f8f9fa;
}

.presence-events-table tbody tr:last-child td {
    border-bottom: none;
}

/* Badges pour les types d'événements */
.event-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.event-badge-default {
    background-color: #6c757d;
}

/* Badges pour les statuts */
.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background-color: #ffc107;
    color: #000;
}

.status-approved {
    background-color: #28a745;
    color: white;
}

.status-rejected {
    background-color: #dc3545;
    color: white;
}

.status-default {
    background-color: #6c757d;
    color: white;
}

/* Boutons d'action */
.action-buttons {
    display: flex;
    gap: 5px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: white;
    color: #6c757d;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    font-size: 12px;
}

.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
}

.action-btn-edit:hover {
    background: #007bff;
    border-color: #007bff;
    color: white;
}

.action-btn-delete:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: white;
}

.action-btn-approve:hover {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

.action-btn-reject:hover {
    background: #dc3545;
    border-color: #dc3545;
    color: white;
}

/* Indicateur de chargement */
.action-loader {
    margin-left: 5px;
    color: #007bff;
}

.action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Notifications toast */
.toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    padding: 15px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 1000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    border-left: 4px solid #007bff;
    max-width: 300px;
}

.toast-notification.show {
    transform: translateX(0);
}

.toast-success {
    border-left-color: #28a745;
    background: #f8fff9;
}

.toast-success i {
    color: #28a745;
}

.toast-error {
    border-left-color: #dc3545;
    background: #fff8f8;
}

.toast-error i {
    color: #dc3545;
}

.toast-notification span {
    font-weight: 500;
    color: #333;
}

/* Styles pour les documents */
.presence-events-table .document-column {
    width: 80px;
    text-align: center;
}

.document-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.document-link:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(0,123,255,0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .presence-events-table {
        font-size: 12px;
    }
    
    .presence-events-table th,
    .presence-events-table td {
        padding: 10px 8px;
    }
    
    .document-column {
        width: 60px;
    }
    
    .toast-notification {
        right: 10px;
        left: 10px;
        max-width: none;
        transform: translateY(-100%);
    }
    
    .toast-notification.show {
        transform: translateY(0);
    }
}

/* Styles pour le modal d'ajout d'événement */
.event-card-modal {
    transition: all 0.3s ease;
    cursor: pointer;
    border-radius: 15px !important;
    position: relative;
    z-index: 1;
    pointer-events: auto;
}

.event-card-modal:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175) !important;
}

.event-card-modal * {
    pointer-events: none;
}

.event-card-modal .event-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
    font-size: 2rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
}

.modal-xl .modal-body {
    padding: 2rem;
}

/* Animation d'apparition pour les cartes du modal */
.event-card-modal {
    animation: fadeInUp 0.6s ease;
}

.event-card-modal:nth-child(1) { animation-delay: 0.1s; }
.event-card-modal:nth-child(2) { animation-delay: 0.2s; }
.event-card-modal:nth-child(3) { animation-delay: 0.3s; }
.event-card-modal:nth-child(4) { animation-delay: 0.4s; }

/* Responsive pour le modal */
@media (max-width: 768px) {
    .event-card-modal .event-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .modal-xl .modal-body {
        padding: 1rem;
    }
}
</style>

<script>
function confirmDelete(eventId) {
    document.getElementById('deleteEventId').value = eventId;
    var modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    modal.show();
}

function updateEventStatus(eventId, newStatus) {
    const actionText = newStatus === 'approved' ? 'accepter' : 'rejeter';
    
    if (confirm(`Êtes-vous sûr de vouloir ${actionText} cet événement ?`)) {
        // Trouver la ligne du tableau pour cette événement
        const row = document.querySelector(`tr[data-event-id="${eventId}"]`);
        const actionButtons = row ? row.querySelector('.action-buttons') : null;
        
        // Désactiver temporairement les boutons et afficher un loader
        if (actionButtons) {
            const approveBtn = actionButtons.querySelector('.action-btn-approve');
            const rejectBtn = actionButtons.querySelector('.action-btn-reject');
            
            if (approveBtn) approveBtn.disabled = true;
            if (rejectBtn) rejectBtn.disabled = true;
            
            // Ajouter un indicateur de chargement
            const loader = document.createElement('span');
            loader.className = 'action-loader';
            loader.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            actionButtons.appendChild(loader);
        }
        
        // Envoyer la requête AJAX
        fetch('index.php?page=presence_gestion', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=update_status&event_id=${eventId}&new_status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour la cellule statut
                const statusCell = row.querySelector('td:nth-child(6) .status-badge');
                if (statusCell) {
                    statusCell.className = `status-badge status-${newStatus}`;
                    statusCell.textContent = newStatus === 'approved' ? 'Approuvé' : 'Rejeté';
                }
                
                // Supprimer les boutons d'action admin (plus besoin)
                const approveBtn = actionButtons.querySelector('.action-btn-approve');
                const rejectBtn = actionButtons.querySelector('.action-btn-reject');
                if (approveBtn) approveBtn.remove();
                if (rejectBtn) rejectBtn.remove();
                
                // Afficher un message de succès temporaire
                showToast(`Événement ${actionText} avec succès !`, 'success');
            } else {
                // Réactiver les boutons en cas d'erreur
                if (actionButtons) {
                    const approveBtn = actionButtons.querySelector('.action-btn-approve');
                    const rejectBtn = actionButtons.querySelector('.action-btn-reject');
                    if (approveBtn) approveBtn.disabled = false;
                    if (rejectBtn) rejectBtn.disabled = false;
                }
                
                showToast(data.message || 'Erreur lors de la mise à jour', 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            
            // Réactiver les boutons en cas d'erreur
            if (actionButtons) {
                const approveBtn = actionButtons.querySelector('.action-btn-approve');
                const rejectBtn = actionButtons.querySelector('.action-btn-reject');
                if (approveBtn) approveBtn.disabled = false;
                if (rejectBtn) rejectBtn.disabled = false;
            }
            
            showToast('Erreur de connexion', 'error');
        })
        .finally(() => {
            // Supprimer l'indicateur de chargement
            if (actionButtons) {
                const loader = actionButtons.querySelector('.action-loader');
                if (loader) loader.remove();
            }
        });
    }
}

// Fonction pour afficher des notifications toast
function showToast(message, type = 'info') {
    // Créer l'élément toast
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        <span>${message}</span>
    `;
    
    // Ajouter au body
    document.body.appendChild(toast);
    
    // Animation d'apparition
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Fonction pour soumettre l'export
function submitExport() {
    const form = document.getElementById('exportForm');
    
    // Construire l'URL avec les paramètres
    const formData = new FormData(form);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        params.append(key, value);
    }
    
    // Ouvrir le rapport dans un popup
    const url = 'index.php?page=presence_export_print&' + params.toString();
    const popup = window.open(url, 'rapport_presence', 'width=1200,height=800,scrollbars=yes,resizable=yes');
    
    if (popup) {
        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
        modal.hide();
        showToast('Rapport généré avec succès !', 'success');
        
        // Focus sur le popup
        popup.focus();
    } else {
        showToast('Popup bloqué ! Veuillez autoriser les popups pour ce site.', 'error');
    }
}

// Gestion des clics sur les cartes d'événement dans le modal
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier que Bootstrap est chargé
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap n\'est pas chargé !');
        return;
    }
    
    const eventCards = document.querySelectorAll('.event-card-modal');
    console.log('Nombre de cartes trouvées:', eventCards.length);
    
    eventCards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const eventType = this.getAttribute('data-type');
            console.log('Clic détecté sur:', eventType);
            
            // Fermer le modal principal proprement
            const addModalElement = document.getElementById('addEventModal');
            let addModal = bootstrap.Modal.getInstance(addModalElement);
            
            // Si l'instance n'existe pas, la créer
            if (!addModal) {
                addModal = new bootstrap.Modal(addModalElement);
            }
            
            // Fermer le modal principal
            addModal.hide();
            
            // Attendre que le modal soit fermé avant d'ouvrir le suivant
            addModalElement.addEventListener('hidden.bs.modal', function openSubModal() {
                // Ouvrir le sous-modal correspondant
                const subModalId = `${eventType}Modal`;
                const subModalElement = document.getElementById(subModalId);
                
                if (subModalElement) {
                    const subModal = new bootstrap.Modal(subModalElement);
                    subModal.show();
                } else {
                    console.error('Modal non trouvé:', subModalId);
                }
                
                // Retirer l'écouteur pour éviter les doublons
                addModalElement.removeEventListener('hidden.bs.modal', openSubModal);
            });
        });
        
        // Ajouter un effet de sélection au survol
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Gérer le retour des sous-modals vers le modal principal
    const subModalIds = ['retardModal', 'absenceModal', 'conge_payeModal', 'conge_sans_soldeModal'];
    
    subModalIds.forEach(modalId => {
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            modalElement.addEventListener('hidden.bs.modal', function() {
                // Vérifier si c'est un retour au modal principal (pas une fermeture complète)
                setTimeout(() => {
                    // Si aucun autre modal n'est ouvert, remonter le modal principal
                    const openModals = document.querySelectorAll('.modal.show');
                    if (openModals.length === 0) {
                        const addModalElement = document.getElementById('addEventModal');
                        const addModal = new bootstrap.Modal(addModalElement);
                        addModal.show();
                    }
                }, 100);
            });
        }
    });
    
    // Calculs de durée pour les modals
    // Modal Retard
    const retardDuration = document.getElementById('retard_duration');
    const retardDurationDisplay = document.getElementById('retard_duration_display');
    
    if (retardDuration && retardDurationDisplay) {
        function updateRetardDuration() {
            const minutes = parseInt(retardDuration.value) || 0;
            
            if (minutes === 0) {
                retardDurationDisplay.innerHTML = '<span class="text-muted">-</span>';
            } else if (minutes < 60) {
                retardDurationDisplay.innerHTML = minutes + 'min';
            } else {
                const hours = Math.floor(minutes / 60);
                const remainingMinutes = minutes % 60;
                
                if (remainingMinutes === 0) {
                    retardDurationDisplay.innerHTML = hours + 'h';
                } else {
                    retardDurationDisplay.innerHTML = hours + 'h ' + remainingMinutes + 'min';
                }
            }
        }
        
        retardDuration.addEventListener('input', updateRetardDuration);
        retardDuration.addEventListener('change', updateRetardDuration);
        updateRetardDuration(); // Initial call
    }
    
    // Modal Absence
    const absenceDateStart = document.getElementById('absence_date_start');
    const absenceDateEnd = document.getElementById('absence_date_end');
    const absenceDuration = document.getElementById('absence_duration');
    
    if (absenceDateStart && absenceDateEnd && absenceDuration) {
        function updateAbsenceDuration() {
            if (absenceDateStart.value && absenceDateEnd.value) {
                const start = new Date(absenceDateStart.value);
                const end = new Date(absenceDateEnd.value);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                if (diffDays === 1) {
                    absenceDuration.textContent = '1 jour';
                } else {
                    absenceDuration.textContent = diffDays + ' jours';
                }
            }
        }
        
        absenceDateStart.addEventListener('change', updateAbsenceDuration);
        absenceDateEnd.addEventListener('change', updateAbsenceDuration);
        updateAbsenceDuration(); // Initial call
    }
    
    // Modal Congé Payé
    const congePayeDateStart = document.getElementById('conge_paye_date_start');
    const congePayeDateEnd = document.getElementById('conge_paye_date_end');
    const congePayeDuration = document.getElementById('conge_paye_duration');
    
    if (congePayeDateStart && congePayeDateEnd && congePayeDuration) {
        function updateCongePayeDuration() {
            if (congePayeDateStart.value && congePayeDateEnd.value) {
                const start = new Date(congePayeDateStart.value);
                const end = new Date(congePayeDateEnd.value);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                if (diffDays === 1) {
                    congePayeDuration.textContent = '1 jour';
                } else {
                    congePayeDuration.textContent = diffDays + ' jours';
                }
            }
        }
        
        congePayeDateStart.addEventListener('change', updateCongePayeDuration);
        congePayeDateEnd.addEventListener('change', updateCongePayeDuration);
        updateCongePayeDuration(); // Initial call
    }
    
    // Modal Congé Sans Solde
    const congeSansSoldeDateStart = document.getElementById('conge_sans_solde_date_start');
    const congeSansSoldeDateEnd = document.getElementById('conge_sans_solde_date_end');
    const congeSansSoldeDuration = document.getElementById('conge_sans_solde_duration');
    
    if (congeSansSoldeDateStart && congeSansSoldeDateEnd && congeSansSoldeDuration) {
        function updateCongeSansSoldeDuration() {
            if (congeSansSoldeDateStart.value && congeSansSoldeDateEnd.value) {
                const start = new Date(congeSansSoldeDateStart.value);
                const end = new Date(congeSansSoldeDateEnd.value);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                if (diffDays === 1) {
                    congeSansSoldeDuration.textContent = '1 jour';
                } else {
                    congeSansSoldeDuration.textContent = diffDays + ' jours';
                }
            }
        }
        
        congeSansSoldeDateStart.addEventListener('change', updateCongeSansSoldeDuration);
        congeSansSoldeDateEnd.addEventListener('change', updateCongeSansSoldeDuration);
        updateCongeSansSoldeDuration(); // Initial call
    }
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

/* Masquer le loader quand la page est chargée */
.loader.fade-out {
  opacity: 0;
  transition: opacity 0.5s ease-out;
}

.loader.hidden {
  display: none;
}

/* Afficher le contenu principal quand chargé */
#mainContent.fade-in {
  opacity: 1;
  transition: opacity 0.5s ease-in;
}

/* Gestion des deux types de loaders */
.dark-loader {
  display: flex;
}

.light-loader {
  display: none;
  background: #ffffff !important;
}

/* En mode clair, inverser l'affichage */
body:not(.dark-mode) #pageLoader {
  background: #ffffff !important;
}

body:not(.dark-mode) .dark-loader {
  display: none;
}

body:not(.dark-mode) .light-loader {
  display: flex;
}

/* Loader Mode Clair - Cercle avec couleurs sombres */
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

/* Texte du loader mode clair */
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

/* Appliquer le fond du loader à la page - MODE JOUR ET NUIT */
body,
body.dark-mode,
body.light-mode,
html {
  background: linear-gradient(0deg, #0f1419, #0a0f1a, #000) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

.container-fluid,
.container-fluid * {
  background: transparent !important;
}

/* Forcer le fond pour tous les éléments principaux */
.main-content,
.content-wrapper {
  background: transparent !important;
}

/* S'assurer que les cartes et éléments restent visibles */
.card,
.modal-content,
.alert {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}

.dark-mode .card,
.dark-mode .modal-content,
.dark-mode .alert {
  background: rgba(30, 41, 59, 0.95) !important;
  backdrop-filter: blur(10px) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('pageLoader');
    const mainContent = document.getElementById('mainContent');
    
    // Attendre 0,3 seconde puis masquer le loader et afficher le contenu
    setTimeout(function() {
        // Commencer l'animation de disparition du loader
        loader.classList.add('fade-out');
        
        // Après l'animation de disparition, masquer complètement le loader et afficher le contenu
        setTimeout(function() {
            loader.classList.add('hidden');
            mainContent.style.display = 'block';
            mainContent.classList.add('fade-in');
        }, 500); // Durée de l'animation de disparition
        
    }, 300); // 0,3 seconde comme demandé
});
</script>