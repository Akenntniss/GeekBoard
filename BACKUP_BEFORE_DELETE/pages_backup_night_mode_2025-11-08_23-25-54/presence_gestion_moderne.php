<?php
// Page de gestion des absences et retards - Version moderne
// Session déjà démarrée par config/session_config.php

// Initialisation de la connexion à la base de données
try {
    // Forcer la détection du shop_id si pas encore défini
    if (!isset($_SESSION['shop_id'])) {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Mapping direct pour les sous-domaines connus
        $subdomain_to_shop_id = [
            'mkmkmk.mdgeek.top' => 63,
            'cannesphones.mdgeek.top' => 4,
            'cannes.mdgeek.top' => 4,
            // Ajouter d'autres mappings si nécessaire
        ];
        
        if (isset($subdomain_to_shop_id[$host])) {
            $_SESSION['shop_id'] = $subdomain_to_shop_id[$host];
            error_log("Shop ID forcé pour $host: " . $_SESSION['shop_id']);
        } else {
            // Fallback: essayer la détection automatique
            if (function_exists('detectShopFromSubdomain')) {
                $detected_shop_id = detectShopFromSubdomain();
                if ($detected_shop_id) {
                    $_SESSION['shop_id'] = $detected_shop_id;
                }
            }
        }
    }
    
    $shop_pdo = getShopDBConnection();
} catch (Exception $e) {
    // En cas d'erreur de connexion, essayer une connexion directe
    error_log("Erreur de connexion DB dans presence_gestion_moderne.php: " . $e->getMessage());
    
    try {
        // Connexion directe à la base de données du magasin mkmkmk
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $db_name = 'geekboard_mkmkmk'; // Base par défaut pour mkmkmk
        
        // Ajuster le nom de la base selon le sous-domaine
        if (strpos($host, 'cannes') !== false) {
            $db_name = 'geekboard_cannesphones';
        }
        
        $shop_pdo = new PDO(
            "mysql:host=localhost;dbname=$db_name;charset=utf8mb4",
            'root',
            'Mamanmaman01#',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        
        error_log("Connexion directe réussie à $db_name");
        
        // Forcer la récupération des utilisateurs avec la connexion directe
        try {
            $stmt = $shop_pdo->query("SELECT id, username, full_name FROM users ORDER BY full_name, username");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupération de l'utilisateur actuel
            $current_user_name = 'Administrateur';
            if ($current_user_id && !empty($users)) {
                foreach ($users as $user) {
                    if ($user['id'] == $current_user_id) {
                        $current_user_name = $user['full_name'] ?: $user['username'];
                        break;
                    }
                }
            }
            
            error_log("Utilisateurs récupérés avec connexion directe: " . count($users));
        } catch (Exception $e3) {
            error_log("Erreur récupération utilisateurs connexion directe: " . $e3->getMessage());
            $users = [];
        }
        
    } catch (Exception $e2) {
        error_log("Échec de la connexion directe: " . $e2->getMessage());
        $shop_pdo = null;
    }
}

// Variables globales pour l'authentification (nécessaires pour l'export)
$current_user_id = $_SESSION['user_id'] ?? 6; // ID admin par défaut pour mkmkmk
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Initialisation des variables
$users = [];
$events = [];
$current_user_name = 'Administrateur';

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

// Variables pour les messages
$success_message = '';
$error_message = '';

// Traitement des actions (supprimer, modifier statut)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $shop_pdo !== null) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                if (isset($_POST['event_id'])) {
                    // SÉCURITÉ: Seuls les admins peuvent supprimer des événements
                    // Vérifier le rôle depuis la DB
                    $user_role_from_db = '';
                    if ($current_user_id) {
                        $stmt_role = $shop_pdo->prepare("SELECT role FROM users WHERE id = ?");
                        $stmt_role->execute([$current_user_id]);
                        $user_data = $stmt_role->fetch(PDO::FETCH_ASSOC);
                        if ($user_data) {
                            $user_role_from_db = $user_data['role'];
                        }
                    }
                    
                    $is_admin_verified = (
                        (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'administrateur', 'superadmin'])) ||
                        (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'administrateur', 'superadmin'])) ||
                        (isset($_SESSION['superadmin_id']) && $_SESSION['superadmin_id']) ||
                        ($user_role_from_db === 'admin') ||
                        (strpos(strtolower($current_user_name), 'administrateur') !== false)
                    );
                    
                    if ($is_admin_verified) {
                        $stmt = $shop_pdo->prepare("DELETE FROM presence_events WHERE id = ?");
                        $stmt->execute([$_POST['event_id']]);
                        $success_message = "Événement supprimé avec succès.";
                    } else {
                        $error_message = "Vous n'avez pas les droits pour supprimer des événements.";
                    }
                }
                break;
                
            case 'update_status':
                if (isset($_POST['event_id']) && isset($_POST['new_status'])) {
                    $event_id = intval($_POST['event_id']);
                    $new_status = $_POST['new_status'];
                    
                    // SÉCURITÉ: Vérifier que l'utilisateur est admin et que le statut est valide
                    // Vérifier le rôle depuis la DB
                    $user_role_from_db = '';
                    if ($current_user_id) {
                        $stmt_role = $shop_pdo->prepare("SELECT role FROM users WHERE id = ?");
                        $stmt_role->execute([$current_user_id]);
                        $user_data = $stmt_role->fetch(PDO::FETCH_ASSOC);
                        if ($user_data) {
                            $user_role_from_db = $user_data['role'];
                        }
                    }
                    
                    $is_admin_verified = (
                        (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'administrateur', 'superadmin'])) ||
                        (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'administrateur', 'superadmin'])) ||
                        (isset($_SESSION['superadmin_id']) && $_SESSION['superadmin_id']) ||
                        ($user_role_from_db === 'admin') ||
                        (strpos(strtolower($current_user_name), 'administrateur') !== false)
                    );
                    
                    if ($is_admin_verified && in_array($new_status, ['approved', 'rejected'])) {
                        $stmt = $shop_pdo->prepare("UPDATE presence_events SET status = ?, approved_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$new_status, $current_user_id, $event_id]);
                        
                        $status_message = $new_status === 'approved' ? 'accepté' : 'rejeté';
                        
                        // Si c'est une requête AJAX, renvoyer du JSON
                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                            // Nettoyer le buffer de sortie pour éviter le HTML parasite
                            while (ob_get_level()) {
                                ob_end_clean();
                            }
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
                            // Nettoyer le buffer de sortie pour éviter le HTML parasite
                            while (ob_get_level()) {
                                ob_end_clean();
                            }
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
                
            case 'add_event':
                // Traitement de l'ajout d'événement depuis les modals
                try {
                    $event_type = $_POST['event_type'] ?? '';
                    $user_id = $_POST['user_id'] ?? null;
                    $comment = trim($_POST['comment'] ?? '');
                    
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

// Les données réelles seront récupérées depuis la base de données plus bas

// Variables déjà initialisées plus haut

try { // Activer la récupération des données réelles
    // Vérifier que la connexion à la base de données est disponible
    if ($shop_pdo === null) {
        throw new Exception("Connexion à la base de données non disponible");
    }
    
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
        
        // Récupération de l'utilisateur actuel pour les modals
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
        
        // Récupérer le rôle depuis la base de données
        $user_role_from_db = '';
        if ($current_user_id && !empty($users)) {
            foreach ($users as $user) {
                if ($user['id'] == $current_user_id) {
                    // Récupérer le rôle depuis la DB
                    $stmt_role = $shop_pdo->prepare("SELECT role FROM users WHERE id = ?");
                    $stmt_role->execute([$current_user_id]);
                    $user_data = $stmt_role->fetch(PDO::FETCH_ASSOC);
                    if ($user_data) {
                        $user_role_from_db = $user_data['role'];
                    }
                    break;
                }
            }
        }
        
        $is_admin = $is_admin || (
            (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'administrateur', 'superadmin'])) ||
            (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'administrateur', 'superadmin'])) ||
            (isset($_SESSION['superadmin_id']) && $_SESSION['superadmin_id']) ||
            ($user_role_from_db === 'admin') || // Vérification depuis la DB
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
} catch (Exception $e) {
    // En cas d'erreur générale, utiliser des données de fallback
    $error_message = "Erreur lors de l'initialisation : " . $e->getMessage();
}

// Fallback : Si pas d'utilisateurs récupérés, essayer de récupérer au moins l'utilisateur actuel
if (empty($users) && $shop_pdo !== null) {
    try {
        // Essayer de récupérer les utilisateurs de la table users
        $stmt = $shop_pdo->query("SELECT id, username, full_name FROM users ORDER BY full_name, username");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupération de l'utilisateur actuel
        if ($current_user_id && !empty($users)) {
            foreach ($users as $user) {
                if ($user['id'] == $current_user_id) {
                    $current_user_name = $user['full_name'] ?: $user['username'];
                    break;
                }
            }
        }
    } catch (Exception $e) {
        // Si même la table users n'existe pas, créer un utilisateur par défaut
        $users = [
            ['id' => $current_user_id, 'username' => 'admin', 'full_name' => 'Administrateur']
        ];
        $current_user_name = 'Administrateur';
    }
}

// Si toujours pas d'utilisateurs (pas de connexion DB), créer un utilisateur par défaut
if (empty($users)) {
    $users = [
        ['id' => $current_user_id, 'username' => 'admin', 'full_name' => 'Administrateur']
    ];
    $current_user_name = 'Administrateur';
    $error_message = "Mode hors ligne : Connexion à la base de données non disponible";
}

// Calculer les statistiques
$total_events = count($events);
$pending_events = count(array_filter($events, function($e) { return $e['status'] == 'pending'; }));
$approved_events = count(array_filter($events, function($e) { return $e['status'] == 'approved'; }));
$rejected_events = count(array_filter($events, function($e) { return $e['status'] == 'rejected'; }));
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

body.night-mode .navbar-nav .dropdown-menu {
    background: var(--night-card-bg) !important;
    border: 1px solid var(--night-border) !important;
    box-shadow: 0 4px 20px var(--night-shadow) !important;
}

body.night-mode .navbar-nav .dropdown-item {
    color: var(--night-text) !important;
}

body.night-mode .navbar-nav .dropdown-item:hover,
body.night-mode .navbar-nav .dropdown-item:focus {
    background: rgba(0, 212, 255, 0.1) !important;
    color: var(--night-primary) !important;
}

/* Corrections pour les boutons de la navbar en mode nuit */
body.night-mode #desktop-navbar .btn {
    color: var(--night-text) !important;
    border-color: var(--night-border) !important;
}

body.night-mode #desktop-navbar .btn:hover {
    color: var(--night-primary) !important;
    border-color: var(--night-primary) !important;
    background: rgba(0, 212, 255, 0.1) !important;
}

/* ========================================
   MODE NUIT - STYLES GÉNÉRAUX
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
body.night-mode .modern-controls,
body.night-mode .modern-stats-grid .modern-stat-card,
body.night-mode .modern-table-container,
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

body.night-mode .modern-form-input,
body.night-mode .modern-select {
    background: rgba(15, 23, 42, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .modern-form-input:focus,
body.night-mode .modern-select:focus {
    background: rgba(15, 23, 42, 0.9);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

body.night-mode .modern-table {
    background: #0f172a;
}

body.night-mode .modern-table th {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: var(--night-text);
}

body.night-mode .modern-table tr:hover {
    background-color: rgba(0, 212, 255, 0.1);
}

/* ========================================
   MODALS EN MODE NUIT
======================================== */
body.night-mode .modern-modal {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(12px);
}

body.night-mode .modern-modal-dialog {
    background: var(--night-card-bg) !important;
    color: var(--night-text) !important;
    border: 1px solid var(--night-border) !important;
    box-shadow: 0 25px 50px rgba(0, 212, 255, 0.3) !important;
}

body.night-mode .modern-modal-header {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary)) !important;
    color: var(--night-text) !important;
    border-bottom: 1px solid var(--night-border) !important;
}

body.night-mode .modern-modal-title {
    color: var(--night-text) !important;
}

body.night-mode .modern-modal-body {
    background: var(--night-card-bg) !important;
    color: var(--night-text) !important;
}

body.night-mode .modern-form-label {
    color: var(--night-text) !important;
}

body.night-mode .modern-form-input,
body.night-mode .modern-form-select {
    background: rgba(15, 23, 42, 0.8) !important;
    border-color: var(--night-border) !important;
    color: var(--night-text) !important;
}

body.night-mode .modern-form-input:focus,
body.night-mode .modern-form-select:focus {
    background: rgba(15, 23, 42, 0.9) !important;
    border-color: var(--night-primary) !important;
    box-shadow: var(--night-glow) !important;
}

/* Styles spécifiques pour les cartes d'événements dans le modal */
body.night-mode .event-card-modal {
    background: var(--night-card-bg) !important;
    border: 1px solid var(--night-border) !important;
    color: var(--night-text) !important;
    box-shadow: 0 4px 12px var(--night-shadow) !important;
}

body.night-mode .event-card-modal:hover {
    border-color: var(--night-primary) !important;
    box-shadow: 0 8px 25px var(--night-shadow), var(--night-glow) !important;
}

body.night-mode .event-card-modal .event-icon {
    background: rgba(0, 212, 255, 0.1) !important;
    border: 1px solid var(--night-border) !important;
}

body.night-mode .event-card-modal .event-title {
    color: var(--night-text) !important;
}

body.night-mode .event-card-modal .event-description {
    color: var(--night-text-light) !important;
}

body.night-mode .event-card-modal .event-badge {
    background: rgba(0, 212, 255, 0.2) !important;
    color: var(--night-primary) !important;
    border: 1px solid var(--night-border) !important;
}

/* Styles pour le modal d'export */
body.night-mode #exportModal .modal-content,
body.night-mode #exportModal .modal-dialog {
    background: var(--night-card-bg) !important;
    color: var(--night-text) !important;
    border: 1px solid var(--night-border) !important;
}

body.night-mode #exportModal .modal-header {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary)) !important;
    color: var(--night-text) !important;
    border-bottom: 1px solid var(--night-border) !important;
}

body.night-mode #exportModal .modal-title {
    color: var(--night-text) !important;
}

body.night-mode #exportModal .modal-body {
    background: var(--night-card-bg) !important;
    color: var(--night-text) !important;
}

body.night-mode #exportModal .form-label {
    color: var(--night-text) !important;
}

body.night-mode #exportModal .form-control,
body.night-mode #exportModal .form-select {
    background: rgba(15, 23, 42, 0.8) !important;
    border-color: var(--night-border) !important;
    color: var(--night-text) !important;
}

body.night-mode #exportModal .form-control:focus,
body.night-mode #exportModal .form-select:focus {
    background: rgba(15, 23, 42, 0.9) !important;
    border-color: var(--night-primary) !important;
    box-shadow: var(--night-glow) !important;
}

/* Styles pour les boutons dans les modals en mode nuit */
body.night-mode .modern-btn,
body.night-mode .btn {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary)) !important;
    color: var(--night-text) !important;
    border: 1px solid var(--night-border) !important;
}

body.night-mode .modern-btn:hover,
body.night-mode .btn:hover {
    background: linear-gradient(135deg, var(--night-secondary), var(--night-primary)) !important;
    border-color: var(--night-primary) !important;
    box-shadow: var(--night-glow) !important;
}

body.night-mode .modern-btn--secondary,
body.night-mode .btn-secondary {
    background: rgba(15, 23, 42, 0.8) !important;
    color: var(--night-text) !important;
    border: 1px solid var(--night-border) !important;
}

body.night-mode .modern-btn--secondary:hover,
body.night-mode .btn-secondary:hover {
    background: rgba(15, 23, 42, 0.9) !important;
    border-color: var(--night-primary) !important;
}

/* Styles pour les boutons de fermeture des modals */
body.night-mode .btn-close,
body.night-mode .modern-modal-close {
    background: rgba(255, 255, 255, 0.1) !important;
    color: var(--night-text) !important;
    border: 1px solid var(--night-border) !important;
    filter: invert(1) !important;
}

body.night-mode .btn-close:hover,
body.night-mode .modern-modal-close:hover {
    background: rgba(255, 255, 255, 0.2) !important;
    border-color: var(--night-primary) !important;
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
   BOUTONS D'ACTION MODERNES
======================================== */
.modern-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, var(--day-primary) 0%, var(--day-secondary) 100%);
    color: white;
    text-decoration: none;
    border-radius: 15px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
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
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
}

.modern-btn--success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.modern-btn--success:hover {
    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
}

.modern-btn--info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.modern-btn--info:hover {
    box-shadow: 0 10px 30px rgba(6, 182, 212, 0.4);
}

.modern-btn--warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.modern-btn--warning:hover {
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
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
    cursor: pointer;
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
   CONTRÔLES MODERNES
======================================== */
.modern-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    padding: 1.5rem;
    background: var(--day-card-bg);
    border-radius: 20px;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
}

.modern-search {
    position: relative;
    flex: 1;
    min-width: 300px;
}

.modern-search input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid var(--day-border);
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.8);
    color: var(--day-text);
    font-size: 1rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.modern-search input:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    background: rgba(255, 255, 255, 1);
}

.modern-search i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--day-text-light);
    font-size: 1.1rem;
}

.modern-select {
    padding: 1rem;
    border: 2px solid var(--day-border);
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.8);
    color: var(--day-text);
    font-size: 1rem;
    min-width: 150px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.modern-select:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* ========================================
   TABLEAU MODERNE
======================================== */
.modern-table-container {
    background: var(--day-card-bg);
    border-radius: 20px;
    padding: 1.5rem;
    backdrop-filter: blur(20px);
    border: 1px solid var(--day-border);
    box-shadow: 0 8px 32px var(--day-shadow);
    overflow: hidden;
    animation: slideInUp 0.6s ease-out;
}

.modern-table-wrapper {
    overflow-x: auto;
    border-radius: 15px;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.modern-table th {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    color: var(--day-text);
    font-weight: 700;
    padding: 1.25rem;
    text-align: left;
    border-bottom: 2px solid var(--day-border);
    position: sticky;
    top: 0;
    z-index: 10;
}

.modern-table td {
    padding: 1.25rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    color: var(--day-text);
    vertical-align: middle;
}

.modern-table tr {
    transition: all 0.2s ease;
}

.modern-table tr:hover {
    background: rgba(59, 130, 246, 0.05);
    transform: scale(1.002);
}

/* ========================================
   BADGES MODERNES
======================================== */
.modern-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.875rem;
    font-weight: 600;
    letter-spacing: 0.025em;
}

.modern-badge--success {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.modern-badge--warning {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    color: #92400e;
    border: 1px solid #fcd34d;
}

.modern-badge--danger {
    background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.modern-badge--info {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    color: #1e40af;
    border: 1px solid #93c5fd;
}

/* ========================================
   BOUTONS D'ACTION TABLE
======================================== */
.modern-actions-cell {
    display: flex;
    gap: 0.5rem;
}

.modern-action-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid var(--day-border);
    background: white;
    color: var(--day-text-light);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
    text-decoration: none;
}

.modern-action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    color: var(--day-primary);
    border-color: var(--day-primary);
}

.modern-action-btn--approve {
    color: #10b981;
    border-color: #10b981;
}

.modern-action-btn--approve:hover {
    background: #10b981;
    color: white;
}

.modern-action-btn--reject {
    color: #ef4444;
    border-color: #ef4444;
}

.modern-action-btn--reject:hover {
    background: #ef4444;
    color: white;
}

.modern-action-btn--delete {
    color: #ef4444;
    border-color: #ef4444;
}

.modern-action-btn--delete:hover {
    background: #ef4444;
    color: white;
}

/* ========================================
   MODALS MODERNES
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
    background: white;
    border-radius: 20px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    animation: slideInUp 0.3s ease;
    position: relative;
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

.modern-form-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.modern-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.modern-form-label {
    font-weight: 600;
    color: var(--day-text);
    font-size: 0.95rem;
}

.modern-form-input {
    padding: 0.875rem;
    border: 2px solid var(--day-border);
    border-radius: 10px;
    background: white;
    color: var(--day-text);
    font-size: 1rem;
    transition: all 0.2s ease;
}

.modern-form-input:focus {
    outline: none;
    border-color: var(--day-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
    
    .modern-actions {
        width: 100%;
        justify-content: center;
    }
    
    .modern-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .modern-search {
        min-width: unset;
    }
    
    .modern-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .modern-title {
        font-size: 2rem;
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
body.night-mode .modern-controls,
body.night-mode .modern-table-container {
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

body.night-mode .modern-table {
    background: #0f172a;
}

body.night-mode .modern-table th {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: var(--night-text);
}

body.night-mode .modern-table tr:hover {
    background-color: rgba(0, 212, 255, 0.1);
}

body.night-mode .modern-search input,
body.night-mode .modern-select,
body.night-mode .modern-form-input {
    background: rgba(15, 23, 42, 0.8);
    border-color: var(--night-border);
    color: var(--night-text);
}

body.night-mode .modern-search input:focus,
body.night-mode .modern-select:focus,
body.night-mode .modern-form-input:focus {
    background: rgba(15, 23, 42, 0.9);
    border-color: var(--night-primary);
    box-shadow: var(--night-glow);
}

body.night-mode .modern-modal-dialog {
    background: #0f172a;
    border-color: var(--night-border);
}

body.night-mode .modern-btn {
    background: linear-gradient(135deg, var(--night-primary), var(--night-secondary));
    color: var(--night-text);
}

/* Règle spécifique pour mode jour */
body:not(.night-mode) .stat-value {
    color: #1e293b !important; /* Noir en mode jour */
}

body.night-mode .stat-value {
    color: var(--night-text) !important; /* Blanc en mode nuit - Priorité forte */
}

/* ========================================
   TOAST NOTIFICATIONS
======================================== */
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

/* ========================================
   STYLES SPÉCIFIQUES PRÉSENCE
======================================== */
.event-type-card {
    background: var(--day-card-bg);
    border: 2px solid var(--day-border);
    border-radius: 15px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    height: 100%;
}

.event-type-card:hover {
    transform: translateY(-5px);
    border-color: var(--day-primary);
    box-shadow: 0 10px 30px var(--day-shadow);
}

.event-type-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    margin: 0 auto 1rem;
}

.event-type-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--day-text);
    margin-bottom: 0.5rem;
}

.event-type-description {
    color: var(--day-text-light);
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
</style>

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
</script>

<!-- Particules d'arrière-plan -->
<div class="particles-container" id="particles"></div>

<div class="modern-dashboard bg-animated" id="dashboard">
    
    <!-- Messages d'alerte -->
    <?php if ($success_message): ?>
        <div class="modern-toast modern-toast--success" id="successToast">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-check-circle"></i>
                <span style="font-weight: 500;"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="modern-toast modern-toast--error" id="errorToast">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-times-circle"></i>
                <span style="font-weight: 500;"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- En-tête moderne -->
    <div class="modern-header fade-in">
        <h1 class="modern-title">
            <i class="fas fa-user-clock"></i>
            Gestion des Présences
        </h1>
        <div class="modern-actions">
            <button class="modern-btn modern-btn--success" onclick="openAddEventModal()">
                <i class="fas fa-plus"></i>
                Ajouter un événement
            </button>
            <button class="modern-btn modern-btn--warning" onclick="openExportModal()">
                <i class="fas fa-print"></i>
                Exporter
            </button>
        </div>
    </div>

    <!-- Statistiques modernes -->
    <div class="modern-stats-grid fade-in">
        <div class="modern-stat-card" onclick="filterByStatus('')">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-list"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $total_events; ?></div>
            <div class="stat-label">Total Événements</div>
        </div>
        
        <div class="modern-stat-card" onclick="filterByStatus('pending')">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $pending_events; ?></div>
            <div class="stat-label">En Attente</div>
        </div>
        
        <div class="modern-stat-card" onclick="filterByStatus('approved')">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $approved_events; ?></div>
            <div class="stat-label">Approuvés</div>
        </div>
        
        <div class="modern-stat-card" onclick="filterByStatus('rejected')">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo $rejected_events; ?></div>
            <div class="stat-label">Rejetés</div>
        </div>
    </div>

    <!-- Contrôles modernes -->
    <div class="modern-controls fade-in">
        <form method="GET" action="index.php" style="display: contents;">
            <input type="hidden" name="page" value="presence_gestion_moderne">
            
            <?php if ($is_admin): ?>
                <select name="user" class="modern-select">
                    <option value="">Tous les utilisateurs</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" 
                                <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="hidden" name="user" value="<?php echo $current_user_id; ?>">
                <div class="modern-select" style="background: var(--day-card-bg); border-color: var(--day-primary);">
                    <i class="fas fa-user" style="margin-right: 0.5rem; color: var(--day-primary);"></i>
                    <strong><?php echo htmlspecialchars($current_user_name); ?></strong>
                    <small style="color: var(--day-text-light); margin-left: 0.5rem;">(Vos événements)</small>
                </div>
            <?php endif; ?>
            
            <select name="type" class="modern-select">
                <option value="">Tous les types</option>
                <?php foreach ($presence_types as $type): ?>
                    <option value="<?php echo $type['id']; ?>" 
                            <?php echo $filter_type == $type['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="status" class="modern-select">
                <option value="">Tous les statuts</option>
                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>En attente</option>
                <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Approuvé</option>
                <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Rejeté</option>
            </select>
            
            <input type="date" name="date_start" class="modern-form-input" value="<?php echo htmlspecialchars($filter_date_start); ?>" placeholder="Date début">
            
            <input type="date" name="date_end" class="modern-form-input" value="<?php echo htmlspecialchars($filter_date_end); ?>" placeholder="Date fin">
            
            <button type="submit" class="modern-btn">
                <i class="fas fa-search"></i>
                Filtrer
            </button>
        </form>
    </div>

    <!-- Tableau moderne -->
    <div class="modern-table-container fade-in">
        <div class="modern-table-wrapper">
            <?php if (empty($events)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--day-text-light);">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <div style="font-size: 1.1rem; font-weight: 600;">Aucun événement trouvé</div>
                    <div style="margin-top: 0.5rem;">Aucun événement ne correspond aux critères de recherche</div>
                    <button class="modern-btn" style="margin-top: 1rem;" onclick="openAddEventModal()">
                        <i class="fas fa-plus"></i>
                        Ajouter le premier événement
                    </button>
                </div>
            <?php else: ?>
                <table class="modern-table" id="eventsTable">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Type</th>
                            <th>Date début</th>
                            <th>Date fin</th>
                            <th>Durée</th>
                            <th>Document</th>
                            <th>Statut</th>
                            <th>Commentaire</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr data-event-id="<?php echo $event['id']; ?>" data-status="<?php echo $event['status']; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($event['full_name'] ?: $event['username']); ?></strong>
                                </td>
                                <td>
                                    <?php if (isset($event['type_nom']) && isset($event['couleur'])): ?>
                                        <span class="modern-badge" style="background-color: <?php echo htmlspecialchars($event['couleur']); ?>; color: white;">
                                            <?php echo htmlspecialchars($event['type_nom']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="modern-badge modern-badge--info">Type <?php echo $event['type_id']; ?></span>
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
                                        echo '<em style="color: var(--day-text-light);">Non définie</em>';
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
                                <td>
                                    <?php if (!empty($event['document_path']) && file_exists($event['document_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($event['document_path']); ?>" 
                                           target="_blank" 
                                           class="modern-action-btn" 
                                           title="Voir le document justificatif">
                                            <i class="fas fa-paperclip"></i>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--day-text-light);">
                                            <i class="fas fa-minus" title="Aucun document"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_classes = [
                                        'pending' => 'modern-badge--warning',
                                        'approved' => 'modern-badge--success',
                                        'rejected' => 'modern-badge--danger'
                                    ];
                                    $status_labels = [
                                        'pending' => 'En attente',
                                        'approved' => 'Approuvé',
                                        'rejected' => 'Rejeté'
                                    ];
                                    $status_icons = [
                                        'pending' => 'fas fa-clock',
                                        'approved' => 'fas fa-check-circle',
                                        'rejected' => 'fas fa-times-circle'
                                    ];
                                    ?>
                                    <span class="modern-badge <?php echo $status_classes[$event['status']] ?? 'modern-badge--info'; ?>">
                                        <i class="<?php echo $status_icons[$event['status']] ?? 'fas fa-question-circle'; ?>"></i>
                                        <?php echo $status_labels[$event['status']] ?? htmlspecialchars($event['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    echo $event['comment'] ? htmlspecialchars(substr($event['comment'], 0, 50)) . (strlen($event['comment']) > 50 ? '...' : '') : '<em style="color: var(--day-text-light);">Aucun</em>';
                                    ?>
                                </td>
                                <td class="modern-actions-cell">
                                    <?php 
                                    // Boutons admin (Accepter/Rejeter) uniquement si l'événement est en attente
                                    if ($is_admin && $event['status'] === 'pending'): ?>
                                        <button type="button" class="modern-action-btn modern-action-btn--approve" 
                                                onclick="updateEventStatus(<?php echo $event['id']; ?>, 'approved')" title="Accepter">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="modern-action-btn modern-action-btn--reject" 
                                                onclick="updateEventStatus(<?php echo $event['id']; ?>, 'rejected')" title="Rejeter">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- Boutons standard - Modifier seulement ses propres événements ou si admin -->
                                    <?php if ($is_admin || $event['employee_id'] == $current_user_id): ?>
                                        <a href="index.php?page=presence_modifier&id=<?php echo $event['id']; ?>" 
                                           class="modern-action-btn" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($is_admin): ?>
                                        <button type="button" class="modern-action-btn modern-action-btn--delete" 
                                                onclick="confirmDelete(<?php echo $event['id']; ?>)" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal d'ajout d'événement -->
<div class="modern-modal" id="addEventModal">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, var(--day-primary), var(--day-secondary)); color: white; border-radius: 20px 20px 0 0;">
            <h3 class="modern-modal-title" style="color: white; margin-bottom: 0;">
                <i class="fas fa-plus-circle"></i>
                Ajouter un Événement
            </h3>
        </div>
        <div class="modern-modal-body">
            <div style="text-center; margin-bottom: 2rem;">
                <h4 style="margin-bottom: 0.5rem; color: var(--day-text);">Que souhaitez-vous déclarer ?</h4>
                <p style="color: var(--day-text-light); margin: 0;">Sélectionnez le type d'événement à enregistrer</p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <!-- RETARD -->
                <div class="event-type-card" onclick="selectEventType('retard')">
                    <div class="event-type-icon" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="event-type-title">Retard</div>
                    <div class="event-type-description">Déclarer une arrivée tardive ou un départ anticipé</div>
                    <div class="modern-badge modern-badge--warning">
                        <i class="fas fa-stopwatch"></i>Ponctuel
                    </div>
                </div>

                <!-- ABSENCE -->
                <div class="event-type-card" onclick="selectEventType('absence')">
                    <div class="event-type-icon" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="event-type-title">Absence</div>
                    <div class="event-type-description">Absence non planifiée ou urgence</div>
                    <div class="modern-badge modern-badge--danger">
                        <i class="fas fa-exclamation-triangle"></i>Période
                    </div>
                </div>

                <!-- CONGÉ PAYÉ -->
                <div class="event-type-card" onclick="selectEventType('conge_paye')">
                    <div class="event-type-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-umbrella-beach"></i>
                    </div>
                    <div class="event-type-title">Congé Payé</div>
                    <div class="event-type-description">Vacances, RTT, congé avec rémunération</div>
                    <div class="modern-badge modern-badge--success">
                        <i class="fas fa-money-bill-wave"></i>Rémunéré
                    </div>
                </div>

                <!-- CONGÉ SANS SOLDE -->
                <div class="event-type-card" onclick="selectEventType('conge_sans_solde')">
                    <div class="event-type-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div class="event-type-title">Congé Sans Solde</div>
                    <div class="event-type-description">Congé personnel non rémunéré</div>
                    <div class="modern-badge modern-badge--info">
                        <i class="fas fa-hand-holding"></i>Non rémunéré
                    </div>
                </div>
            </div>
            
            <!-- Formulaire dynamique -->
            <div id="eventForm" style="display: none;">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_event">
                    <input type="hidden" name="event_type" id="selectedEventType">
                    
                    <div class="modern-form-grid">
                        <div class="modern-form-group">
                            <label class="modern-form-label">Utilisateur</label>
                            <?php if ($is_admin): ?>
                                <select name="user_id" class="modern-form-input" required>
                                    <option value="">Sélectionner un utilisateur</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" <?php echo $user['id'] == $current_user_id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="hidden" name="user_id" value="<?php echo $current_user_id; ?>">
                                <div class="modern-form-input" style="background: var(--day-card-bg); border-color: var(--day-primary);">
                                    <i class="fas fa-user" style="margin-right: 0.5rem; color: var(--day-primary);"></i>
                                    <strong><?php echo htmlspecialchars($current_user_name); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Champs spécifiques au retard -->
                    <div id="retardFields" style="display: none;">
                        <div class="modern-form-grid">
                            <div class="modern-form-group">
                                <label class="modern-form-label">Date du retard</label>
                                <input type="date" name="date_retard" class="modern-form-input" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="modern-form-group">
                                <label class="modern-form-label">Durée (minutes)</label>
                                <input type="number" name="duration_minutes" class="modern-form-input" min="1" max="480" placeholder="Ex: 30">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Champs pour absence/congés -->
                    <div id="periodFields" style="display: none;">
                        <div class="modern-form-grid">
                            <div class="modern-form-group">
                                <label class="modern-form-label">Date de début</label>
                                <input type="date" name="date_debut" class="modern-form-input">
                            </div>
                            <div class="modern-form-group">
                                <label class="modern-form-label">Date de fin</label>
                                <input type="date" name="date_fin" class="modern-form-input">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modern-form-group">
                        <label class="modern-form-label">Commentaire / Raison</label>
                        <textarea name="comment" class="modern-form-input" rows="3" placeholder="Expliquez la raison de cet événement..." required></textarea>
                    </div>
                    
                    <div class="modern-form-group">
                        <label class="modern-form-label">Document justificatif (optionnel)</label>
                        <input type="file" name="document_justificatif" class="modern-form-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small style="color: var(--day-text-light); margin-top: 0.25rem; display: block;">
                            Formats acceptés : PDF, JPG, PNG, DOC, DOCX (max 5MB)
                        </small>
                    </div>
                    
                    <div class="modern-form-actions">
                        <button type="button" class="modern-btn" style="background: #6b7280; color: white;" onclick="closeAddEventModal()">
                            <i class="fas fa-times"></i>
                            Annuler
                        </button>
                        <button type="submit" class="modern-btn modern-btn--success">
                            <i class="fas fa-plus"></i>
                            Créer l'événement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'export -->
<div class="modern-modal" id="exportModal">
    <div class="modern-modal-dialog">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #10b981, #059669); color: white; border-radius: 20px 20px 0 0;">
            <h3 class="modern-modal-title" style="color: white; margin-bottom: 0;">
                <i class="fas fa-print"></i>
                Exporter un Rapport
            </h3>
        </div>
        <div class="modern-modal-body">
            <form action="index.php?page=presence_export_print" method="POST">
                <input type="hidden" name="action" value="export_pdf">
                
                <div class="modern-form-grid">
                    <div class="modern-form-group">
                        <label class="modern-form-label">Employé</label>
                        <select name="export_user" class="modern-form-input">
                            <option value="">Tous les employés</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $filter_user == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="modern-form-group">
                        <label class="modern-form-label">Type d'événement</label>
                        <select name="export_type" class="modern-form-input">
                            <option value="">Tous les types</option>
                            <?php foreach ($presence_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo $filter_type == $type['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="modern-form-group">
                        <label class="modern-form-label">Statut</label>
                        <select name="export_status" class="modern-form-input">
                            <option value="">Tous les statuts</option>
                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>En attente</option>
                            <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Approuvé</option>
                            <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Rejeté</option>
                        </select>
                    </div>
                    
                    <div class="modern-form-group">
                        <label class="modern-form-label">Date début</label>
                        <input type="date" name="export_date_start" class="modern-form-input" value="<?php echo htmlspecialchars($filter_date_start); ?>">
                    </div>
                    
                    <div class="modern-form-group">
                        <label class="modern-form-label">Date fin</label>
                        <input type="date" name="export_date_end" class="modern-form-input" value="<?php echo htmlspecialchars($filter_date_end); ?>">
                    </div>
                    
                    <div class="modern-form-group">
                        <label class="modern-form-label">Format d'export</label>
                        <select name="export_format" class="modern-form-input">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel (XLSX)</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </div>
                
                <div class="modern-form-group">
                    <label class="modern-form-label">Titre du rapport</label>
                    <input type="text" name="report_title" class="modern-form-input" 
                           placeholder="Rapport des événements de présence" 
                           value="Rapport des événements de présence">
                </div>
                
                <div class="modern-form-actions">
                    <button type="button" class="modern-btn" style="background: #6b7280; color: white;" onclick="closeExportModal()">
                        <i class="fas fa-times"></i>
                        Annuler
                    </button>
                    <button type="submit" class="modern-btn modern-btn--success">
                        <i class="fas fa-print"></i>
                        Générer le rapport
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modern-modal" id="confirmDeleteModal">
    <div class="modern-modal-dialog" style="max-width: 400px;">
        <div class="modern-modal-header" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border-radius: 20px 20px 0 0;">
            <h3 class="modern-modal-title" style="color: white; margin-bottom: 0;">
                <i class="fas fa-exclamation-triangle"></i>
                Confirmer la suppression
            </h3>
        </div>
        <div class="modern-modal-body">
            <p style="color: var(--day-text); margin-bottom: 2rem;">
                Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible.
            </p>
            
            <div class="modern-form-actions">
                <button type="button" class="modern-btn" style="background: #6b7280; color: white;" onclick="closeConfirmDeleteModal()">
                    <i class="fas fa-times"></i>
                    Annuler
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="event_id" id="deleteEventId">
                    <button type="submit" class="modern-btn" style="background: #ef4444; color: white;">
                        <i class="fas fa-trash"></i>
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
let selectedEventType = null;

// Utils Modals
function openModal(id) { 
    document.getElementById(id).classList.add('show'); 
    document.body.style.overflow = 'hidden';
}

function closeModal(id) { 
    document.getElementById(id).classList.remove('show'); 
    document.body.style.overflow = 'auto';
}

// Modal d'ajout d'événement
function openAddEventModal() {
    openModal('addEventModal');
    resetEventForm();
}

function closeAddEventModal() {
    closeModal('addEventModal');
    resetEventForm();
}

function resetEventForm() {
    selectedEventType = null;
    document.getElementById('eventForm').style.display = 'none';
    document.getElementById('retardFields').style.display = 'none';
    document.getElementById('periodFields').style.display = 'none';
    
    // Réinitialiser les cartes
    document.querySelectorAll('.event-type-card').forEach(card => {
        card.style.borderColor = 'var(--day-border)';
        card.style.transform = 'none';
    });
}

function selectEventType(type) {
    selectedEventType = type;
    document.getElementById('selectedEventType').value = type;
    
    // Réinitialiser toutes les cartes
    document.querySelectorAll('.event-type-card').forEach(card => {
        card.style.borderColor = 'var(--day-border)';
        card.style.transform = 'none';
    });
    
    // Mettre en évidence la carte sélectionnée
    event.currentTarget.style.borderColor = 'var(--day-primary)';
    event.currentTarget.style.transform = 'translateY(-5px)';
    
    // Afficher le formulaire
    document.getElementById('eventForm').style.display = 'block';
    
    // Afficher les champs appropriés
    if (type === 'retard') {
        document.getElementById('retardFields').style.display = 'block';
        document.getElementById('periodFields').style.display = 'none';
    } else {
        document.getElementById('retardFields').style.display = 'none';
        document.getElementById('periodFields').style.display = 'block';
    }
    
    // Scroll vers le formulaire
    setTimeout(() => {
        document.getElementById('eventForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);
}

// Modal d'export
function openExportModal() {
    openModal('exportModal');
}

function closeExportModal() {
    closeModal('exportModal');
}

// Modal de confirmation de suppression
function confirmDelete(eventId) {
    document.getElementById('deleteEventId').value = eventId;
    openModal('confirmDeleteModal');
}

function closeConfirmDeleteModal() {
    closeModal('confirmDeleteModal');
}

// Mise à jour du statut d'un événement (AJAX)
function updateEventStatus(eventId, newStatus) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('event_id', eventId);
    formData.append('new_status', newStatus);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        // Vérifier si la réponse est OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Vérifier le type de contenu
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('La réponse n\'est pas du JSON valide');
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showToast('✅ ' + data.message, 'success');
            
            // Mettre à jour l'affichage de la ligne
            const row = document.querySelector(`tr[data-event-id="${eventId}"]`);
            if (row) {
                const statusCell = row.querySelector('td:nth-child(7)');
                const actionsCell = row.querySelector('td:nth-child(9)');
                
                // Mettre à jour le badge de statut
                const statusBadge = statusCell.querySelector('.modern-badge');
                if (newStatus === 'approved') {
                    statusBadge.className = 'modern-badge modern-badge--success';
                    statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Approuvé';
                } else if (newStatus === 'rejected') {
                    statusBadge.className = 'modern-badge modern-badge--danger';
                    statusBadge.innerHTML = '<i class="fas fa-times-circle"></i> Rejeté';
                }
                
                // Supprimer les boutons d'approbation/rejet
                const approveBtn = actionsCell.querySelector('.modern-action-btn--approve');
                const rejectBtn = actionsCell.querySelector('.modern-action-btn--reject');
                if (approveBtn) approveBtn.remove();
                if (rejectBtn) rejectBtn.remove();
                
                // Mettre à jour l'attribut data-status
                row.setAttribute('data-status', newStatus);
            }
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur AJAX:', error);
        // Ne pas afficher de message d'erreur visuel si l'action a fonctionné
        // (l'utilisateur verra le résultat au rechargement de la page)
        console.log('L\'action a peut-être fonctionné malgré l\'erreur JSON. Vérifiez en rechargeant la page.');
    });
}

// Filtrage par statut (clic sur les statistiques)
function filterByStatus(status) {
    const url = new URL(window.location.href);
    url.searchParams.set('status', status);
    url.searchParams.set('page', 'presence_gestion_moderne');
    window.location.href = url.toString();
}

// Toast notifications
function showToast(message, type = 'info') {
    // Supprimer les anciens toasts
    const existingToasts = document.querySelectorAll('.modern-toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `modern-toast modern-toast--${type}`;
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
            <span style="font-weight: 500;">${message}</span>
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

// Fermeture des modals en cliquant en dehors
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modern-modal')) {
        const modal = e.target;
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
});

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
`;
document.head.appendChild(style);

// Fonction de détection automatique du mode nuit
function detectAndApplyDarkMode() {
    // Détecter si l'utilisateur préfère le mode sombre
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Clé localStorage spécifique à l'utilisateur pour éviter les conflits
    const userId = <?php echo $current_user_id ?? 'null'; ?>;
    const themeKey = userId ? `theme_user_${userId}` : 'theme';
    
    // Vérifier s'il y a une préférence stockée en localStorage
    let storedTheme = localStorage.getItem(themeKey);
    
    // Migration des anciennes préférences si nécessaire
    if (!storedTheme && userId) {
        const oldTheme = localStorage.getItem('theme');
        if (oldTheme) {
            localStorage.setItem(themeKey, oldTheme);
            storedTheme = oldTheme;
        }
    }
    
    // Appliquer le thème
    if (storedTheme === 'dark' || (storedTheme === null && prefersDarkMode)) {
        document.body.classList.add('night-mode');
        console.log('🌙 Mode nuit activé pour utilisateur', userId);
    } else {
        document.body.classList.remove('night-mode');
        console.log('☀️ Mode jour activé pour utilisateur', userId);
    }
    
    // Debug des informations
    console.log('Diagnostic thème:', {
        userId: userId,
        themeKey: themeKey,
        storedTheme: storedTheme,
        prefersDarkMode: prefersDarkMode,
        hasNightModeClass: document.body.classList.contains('night-mode')
    });
}

// Écouter les changements de préférence système
if (window.matchMedia) {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addListener(function(e) {
        const userId = <?php echo $current_user_id ?? 'null'; ?>;
        const themeKey = userId ? `theme_user_${userId}` : 'theme';
        
        // Si aucune préférence n'est stockée, suivre les préférences système
        if (localStorage.getItem(themeKey) === null) {
            if (e.matches) {
                document.body.classList.add('night-mode');
                console.log('🌙 Passage automatique en mode nuit pour utilisateur', userId);
            } else {
                document.body.classList.remove('night-mode');
                console.log('☀️ Passage automatique en mode jour pour utilisateur', userId);
            }
        }
    });
}

// Fonction pour basculer manuellement le mode (si vous voulez ajouter un bouton plus tard)
function toggleDarkMode() {
    const userId = <?php echo $current_user_id ?? 'null'; ?>;
    const themeKey = userId ? `theme_user_${userId}` : 'theme';
    
    document.body.classList.toggle('night-mode');
    const isDark = document.body.classList.contains('night-mode');
    localStorage.setItem(themeKey, isDark ? 'dark' : 'light');
    console.log('Mode basculé vers:', isDark ? 'nuit' : 'jour', 'pour utilisateur', userId);
}

// Fonction pour forcer l'affichage de la navbar et appliquer le thème
function forceNavbarDisplay() {
    const navbar = document.getElementById('desktop-navbar');
    if (navbar) {
        // Forcer les styles navbar
        navbar.style.display = 'block';
        navbar.style.visibility = 'visible';
        navbar.style.opacity = '1';
        navbar.style.position = 'fixed';
        navbar.style.top = '0';
        navbar.style.zIndex = '10000';
        navbar.style.width = '100%';
        navbar.style.height = '60px';
        
        // Forcer l'application du thème sur la navbar
        const isNightMode = document.body.classList.contains('night-mode');
        if (isNightMode) {
            // Appliquer les styles de mode nuit directement
            navbar.style.background = 'rgba(15, 15, 25, 0.95)';
            navbar.style.borderBottom = '1px solid rgba(0, 212, 255, 0.3)';
            navbar.style.boxShadow = '0 2px 10px rgba(0, 212, 255, 0.25)';
            
            // Forcer les couleurs des liens
            const navLinks = navbar.querySelectorAll('.nav-link, .navbar-text, .navbar-brand');
            navLinks.forEach(link => {
                link.style.color = '#ffffff';
            });
            
            console.log('🌙 Navbar mode nuit forcé');
        } else {
            // Réinitialiser les styles pour le mode jour
            navbar.style.background = '';
            navbar.style.borderBottom = '';
            navbar.style.boxShadow = '';
            
            const navLinks = navbar.querySelectorAll('.nav-link, .navbar-text, .navbar-brand');
            navLinks.forEach(link => {
                link.style.color = '';
            });
            
            console.log('☀️ Navbar mode jour appliqué');
        }
        
        console.log('✅ Navbar forcée visible avec thème');
    } else {
        console.warn('⚠️ Navbar non trouvée');
    }
}

// Fonction pour forcer le thème sur les modals
function forceModalTheme() {
    const isNightMode = document.body.classList.contains('night-mode');
    
    // Forcer le thème sur tous les modals existants
    const modals = document.querySelectorAll('.modern-modal, .modal, #exportModal, #addEventModal');
    
    modals.forEach(modal => {
        if (isNightMode) {
            // Appliquer les styles de mode nuit
            modal.style.background = 'rgba(0, 0, 0, 0.8)';
            modal.style.backdropFilter = 'blur(12px)';
            
            // Forcer les styles sur le contenu du modal
            const modalContent = modal.querySelector('.modern-modal-dialog, .modal-content');
            if (modalContent) {
                modalContent.style.background = 'rgba(15, 15, 25, 0.95)';
                modalContent.style.color = '#ffffff';
                modalContent.style.border = '1px solid rgba(0, 212, 255, 0.3)';
                modalContent.style.boxShadow = '0 25px 50px rgba(0, 212, 255, 0.3)';
            }
            
            // Forcer les styles sur les éléments de formulaire
            const formElements = modal.querySelectorAll('input, select, textarea, .form-control, .form-select');
            formElements.forEach(element => {
                element.style.background = 'rgba(15, 23, 42, 0.8)';
                element.style.color = '#ffffff';
                element.style.borderColor = 'rgba(0, 212, 255, 0.3)';
            });
            
            // Forcer les styles sur les labels
            const labels = modal.querySelectorAll('label, .form-label, .modern-form-label');
            labels.forEach(label => {
                label.style.color = '#ffffff';
            });
            
            console.log('🌙 Thème modal mode nuit forcé');
        } else {
            // Réinitialiser pour le mode jour
            modal.style.background = '';
            modal.style.backdropFilter = '';
            
            const modalContent = modal.querySelector('.modern-modal-dialog, .modal-content');
            if (modalContent) {
                modalContent.style.background = '';
                modalContent.style.color = '';
                modalContent.style.border = '';
                modalContent.style.boxShadow = '';
            }
            
            const formElements = modal.querySelectorAll('input, select, textarea, .form-control, .form-select');
            formElements.forEach(element => {
                element.style.background = '';
                element.style.color = '';
                element.style.borderColor = '';
            });
            
            const labels = modal.querySelectorAll('label, .form-label, .modern-form-label');
            labels.forEach(label => {
                label.style.color = '';
            });
            
            console.log('☀️ Thème modal mode jour appliqué');
        }
    });
}

// Observer pour détecter l'ouverture de nouveaux modals
const modalObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            const target = mutation.target;
            if (target.classList.contains('show') || target.classList.contains('active')) {
                // Un modal vient de s'ouvrir, appliquer le thème
                setTimeout(() => forceModalTheme(), 100);
            }
        }
    });
});

// Observer tous les modals
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.modern-modal, .modal');
    modals.forEach(modal => {
        modalObserver.observe(modal, { attributes: true, attributeFilter: ['class'] });
    });
});

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Détecter et appliquer le mode nuit dès le chargement
    detectAndApplyDarkMode();
    
    // Forcer l'affichage de la navbar
    forceNavbarDisplay();
    
    // Re-forcer après un délai pour s'assurer que tout est chargé
    setTimeout(() => {
        detectAndApplyDarkMode();
        forceNavbarDisplay();
        forceModalTheme();
    }, 500);
    
    createParticles();
    
    // Auto-masquer les toasts existants après 4 secondes
    const existingToasts = document.querySelectorAll('.modern-toast');
    existingToasts.forEach(toast => {
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    });
    
    console.log('Gestion des présences moderne initialisée avec détection automatique du mode nuit');
});
</script>
