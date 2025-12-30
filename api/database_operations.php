<?php
/**
 * API de Gestion des Opérations de Base de Données
 * Fichier: api/database_operations.php
 * 
 * Gère les opérations de backup, restauration, destruction et suppression
 * des bases de données des magasins GeekBoard.
 * 
 * SÉCURITÉ: Accès réservé aux administrateurs uniquement
 */

// Démarrer la session
session_start();

// Vérification de sécurité: Admin uniquement
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Accès refusé. Administration requise.'
    ]);
    exit;
}

// Vérification du shop_id
if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Aucun magasin sélectionné.'
    ]);
    exit;
}

// Récupération de l'action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Configuration
require_once __DIR__ . '/../config/database.php';

// Configuration des chemins
$BACKUP_BASE_DIR = '/backups';
$MYSQL_USER = 'root';
$MYSQL_PASSWORD = 'Mamanmaman01#';
$MYSQL_HOST = 'localhost';

// Log file
$LOG_FILE = __DIR__ . '/../logs/database_operations.log';

/**
 * Fonction de logging
 */
function logOperation($message) {
    global $LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    $shop_id = $_SESSION['shop_id'] ?? 'unknown';
    $user = $_SESSION['username'] ?? 'unknown';
    $logMessage = "[{$timestamp}] [Shop:{$shop_id}] [User:{$user}] {$message}\n";
    
    // Créer le répertoire logs si inexistant
    $logDir = dirname($LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($LOG_FILE, $logMessage, FILE_APPEND);
    error_log($logMessage);
}

/**
 * Récupère les informations du magasin depuis la base principale
 */
function getShopInfo($shop_id) {
    try {
        $pdo_general = getMainDBConnection();
        $stmt = $pdo_general->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        logOperation("ERREUR getShopInfo: " . $e->getMessage());
        return null;
    }
}

/**
 * Crée un backup de la base de données
 */
function backupDatabase($shop_id) {
    global $BACKUP_BASE_DIR, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_HOST;
    
    $shop = getShopInfo($shop_id);
    if (!$shop) {
        return [
            'success' => false,
            'error' => 'Magasin non trouvé'
        ];
    }
    
    $shop_name = $shop['subdomain'];
    $db_name = 'geekboard_' . $shop_name;
    
    // Créer le répertoire de backup
    $backup_dir = $BACKUP_BASE_DIR . '/' . $shop_name;
    if (!is_dir($backup_dir)) {
        if (!mkdir($backup_dir, 0700, true)) {
            logOperation("ERREUR: Impossible de créer le répertoire $backup_dir");
            return [
                'success' => false,
                'error' => 'Impossible de créer le répertoire de backup'
            ];
        }
    }
    
    // Nom du fichier de backup
    $timestamp = date('Ymd_His');
    $backup_file = $backup_dir . '/backup_' . $timestamp . '.sql';
    
    // Commande mysqldump
    $command = sprintf(
        "mysqldump -h %s -u %s -p'%s' --single-transaction --routines --triggers --events %s > %s 2>&1",
        escapeshellarg($MYSQL_HOST),
        escapeshellarg($MYSQL_USER),
        $MYSQL_PASSWORD,
        escapeshellarg($db_name),
        escapeshellarg($backup_file)
    );
    
    logOperation("BACKUP START: Database $db_name vers $backup_file");
    
    // Exécution
    exec($command, $output, $return_code);
    
    if ($return_code === 0 && file_exists($backup_file)) {
        chmod($backup_file, 0600); // Sécurité: lecture/écriture propriétaire uniquement
        
        $file_size = filesize($backup_file);
        $file_size_mb = round($file_size / 1024 / 1024, 2);
        
        logOperation("BACKUP SUCCESS: $backup_file créé ($file_size_mb MB)");
        
        return [
            'success' => true,
            'backup_file' => basename($backup_file),
            'backup_path' => $backup_file,
            'size' => $file_size,
            'size_mb' => $file_size_mb,
            'timestamp' => $timestamp
        ];
    } else {
        logOperation("BACKUP FAILED: Code $return_code - " . implode("\n", $output));
        return [
            'success' => false,
            'error' => 'Échec de la création du backup',
            'details' => implode("\n", $output)
        ];
    }
}

/**
 * Liste tous les backups disponibles pour un magasin
 */
function listBackups($shop_id) {
    global $BACKUP_BASE_DIR;
    
    $shop = getShopInfo($shop_id);
    if (!$shop) {
        return [
            'success' => false,
            'error' => 'Magasin non trouvé'
        ];
    }
    
    $shop_name = $shop['subdomain'];
    $backup_dir = $BACKUP_BASE_DIR . '/' . $shop_name;
    
    if (!is_dir($backup_dir)) {
        return [
            'success' => true,
            'backups' => []
        ];
    }
    
    $backups = [];
    $files = scandir($backup_dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filepath = $backup_dir . '/' . $file;
        if (is_file($filepath) && preg_match('/^backup_(\d{8}_\d{6})\.sql$/', $file, $matches)) {
            $timestamp = $matches[1];
            $date_str = substr($timestamp, 0, 8);
            $time_str = substr($timestamp, 9);
            
            $date = DateTime::createFromFormat('Ymd_His', $timestamp);
            
            $backups[] = [
                'filename' => $file,
                'path' => $filepath,
                'timestamp' => $timestamp,
                'date' => $date ? $date->format('Y-m-d H:i:s') : $timestamp,
                'date_formatted' => $date ? $date->format('d/m/Y à H:i:s') : $timestamp,
                'size' => filesize($filepath),
                'size_mb' => round(filesize($filepath) / 1024 / 1024, 2)
            ];
        }
    }
    
    // Trier par date décroissante (plus récent en premier)
    usort($backups, function($a, $b) {
        return strcmp($b['timestamp'], $a['timestamp']);
    });
    
    return [
        'success' => true,
        'backups' => $backups
    ];
}

/**
 * Détruit la base de données (sauf la table users)
 * Crée un backup automatique avant destruction
 */
function destroyDatabase($shop_id) {
    global $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_HOST;
    
    $shop = getShopInfo($shop_id);
    if (!$shop) {
        return [
            'success' => false,
            'error' => 'Magasin non trouvé'
        ];
    }
    
    $db_name = 'geekboard_' . $shop['subdomain'];
    
    // 1. BACKUP AUTOMATIQUE AVANT DESTRUCTION
    logOperation("DESTROY: Création backup automatique avant destruction de $db_name");
    $backup_result = backupDatabase($shop_id);
    
    if (!$backup_result['success']) {
        logOperation("DESTROY ABORTED: Échec du backup automatique");
        return [
            'success' => false,
            'error' => 'Impossible de créer le backup de sécurité. Destruction annulée.'
        ];
    }
    
    logOperation("DESTROY: Backup créé: " . $backup_result['backup_file']);
    
    // 2. CONNEXION À LA BASE
    try {
        $pdo = new PDO(
            "mysql:host=$MYSQL_HOST;dbname=$db_name;charset=utf8mb4",
            $MYSQL_USER,
            $MYSQL_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        logOperation("DESTROY ERROR: Connexion échouée - " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Impossible de se connecter à la base de données'
        ];
    }
    
    // 3. LISTER TOUTES LES TABLES
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        logOperation("DESTROY: " . count($tables) . " tables trouvées dans $db_name");
        
        // 4. SUPPRIMER TOUTES LES TABLES SAUF 'users'
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0"); // Désactiver les contraintes temporairement
        
        $deleted_count = 0;
        $errors = [];
        
        foreach ($tables as $table) {
            if (strtolower($table) === 'users') {
                logOperation("DESTROY: Table 'users' PRÉSERVÉE (requis pour login)");
                continue;
            }
            
            try {
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
                $deleted_count++;
                logOperation("DESTROY: Table '$table' supprimée");
            } catch (PDOException $e) {
                $errors[] = "Erreur suppression '$table': " . $e->getMessage();
                logOperation("DESTROY ERROR: " . $errors[count($errors) - 1]);
            }
        }
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); // Réactiver les contraintes
        
        logOperation("DESTROY SUCCESS: $deleted_count tables supprimées, table 'users' préservée");
        
        return [
            'success' => true,
            'deleted_tables' => $deleted_count,
            'preserved_tables' => ['users'],
            'backup_file' => $backup_result['backup_file'],
            'errors' => $errors
        ];
        
    } catch (PDOException $e) {
        logOperation("DESTROY FATAL ERROR: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Erreur lors de la destruction: ' . $e->getMessage()
        ];
    }
}

/**
 * Restaure la base de données depuis un backup
 * Préserve la table users actuelle
 */
function restoreDatabase($shop_id, $backup_filename) {
    global $BACKUP_BASE_DIR, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_HOST;
    
    $shop = getShopInfo($shop_id);
    if (!$shop) {
        return [
            'success' => false,
            'error' => 'Magasin non trouvé'
        ];
    }
    
    $shop_name = $shop['subdomain'];
    $db_name = 'geekboard_' . $shop_name;
    $backup_file = $BACKUP_BASE_DIR . '/' . $shop_name . '/' . $backup_filename;
    
    // Vérifier que le fichier existe
    if (!file_exists($backup_file)) {
        return [
            'success' => false,
            'error' => 'Fichier de backup introuvable'
        ];
    }
    
    logOperation("RESTORE START: Restauration de $db_name depuis $backup_filename");
    
    // 1. SAUVEGARDER LA TABLE USERS ACTUELLE
    $users_temp_file = '/tmp/users_backup_' . time() . '.sql';
    $command_backup_users = sprintf(
        "mysqldump -h %s -u %s -p'%s' %s users > %s 2>&1",
        escapeshellarg($MYSQL_HOST),
        escapeshellarg($MYSQL_USER),
        $MYSQL_PASSWORD,
        escapeshellarg($db_name),
        escapeshellarg($users_temp_file)
    );
    
    exec($command_backup_users, $output1, $return1);
    
    if ($return1 !== 0 || !file_exists($users_temp_file)) {
        logOperation("RESTORE ERROR: Impossible de sauvegarder la table users");
        return [
            'success' => false,
            'error' => 'Impossible de sauvegarder la table users actuelle'
        ];
    }
    
    logOperation("RESTORE: Table users sauvegardée dans $users_temp_file");
    
    // 2. RESTAURER LE BACKUP COMPLET
    $command_restore = sprintf(
        "mysql -h %s -u %s -p'%s' %s < %s 2>&1",
        escapeshellarg($MYSQL_HOST),
        escapeshellarg($MYSQL_USER),
        $MYSQL_PASSWORD,
        escapeshellarg($db_name),
        escapeshellarg($backup_file)
    );
    
    exec($command_restore, $output2, $return2);
    
    if ($return2 !== 0) {
        logOperation("RESTORE ERROR: Échec restauration - Code $return2");
        @unlink($users_temp_file);
        return [
            'success' => false,
            'error' => 'Échec de la restauration du backup',
            'details' => implode("\n", $output2)
        ];
    }
    
    logOperation("RESTORE: Backup restauré avec succès");
    
    // 3. RESTAURER LA TABLE USERS ACTUELLE
    $command_restore_users = sprintf(
        "mysql -h %s -u %s -p'%s' %s < %s 2>&1",
        escapeshellarg($MYSQL_HOST),
        escapeshellarg($MYSQL_USER),
        $MYSQL_PASSWORD,
        escapeshellarg($db_name),
        escapeshellarg($users_temp_file)
    );
    
    exec($command_restore_users, $output3, $return3);
    
    // Nettoyer le fichier temporaire
    @unlink($users_temp_file);
    
    if ($return3 !== 0) {
        logOperation("RESTORE WARNING: Échec restauration table users - Code $return3");
        return [
            'success' => true, // La restauration principale a réussi
            'warning' => 'Base restaurée mais impossible de préserver la table users actuelle',
            'backup_file' => $backup_filename
        ];
    }
    
    logOperation("RESTORE SUCCESS: Restauration complète avec préservation de la table users");
    
    return [
        'success' => true,
        'backup_file' => $backup_filename,
        'users_preserved' => true
    ];
}

/**
 * Supprime un fichier de backup
 */
function deleteBackup($shop_id, $backup_filename) {
    global $BACKUP_BASE_DIR;
    
    $shop = getShopInfo($shop_id);
    if (!$shop) {
        return [
            'success' => false,
            'error' => 'Magasin non trouvé'
        ];
    }
    
    $shop_name = $shop['subdomain'];
    $backup_file = $BACKUP_BASE_DIR . '/' . $shop_name . '/' . $backup_filename;
    
    // Vérifier que le fichier existe
    if (!file_exists($backup_file)) {
        return [
            'success' => false,
            'error' => 'Fichier de backup introuvable'
        ];
    }
    
    // Sécurité: vérifier que c'est bien un fichier SQL dans le bon répertoire
    if (!preg_match('/^backup_\d{8}_\d{6}\.sql$/', $backup_filename)) {
        return [
            'success' => false,
            'error' => 'Nom de fichier invalide'
        ];
    }
    
    logOperation("DELETE BACKUP: Suppression de $backup_filename");
    
    if (unlink($backup_file)) {
        logOperation("DELETE BACKUP SUCCESS: $backup_filename supprimé");
        return [
            'success' => true,
            'message' => 'Backup supprimé avec succès'
        ];
    } else {
        logOperation("DELETE BACKUP ERROR: Impossible de supprimer $backup_filename");
        return [
            'success' => false,
            'error' => 'Impossible de supprimer le fichier'
        ];
    }
}

// ==================== ROUTAGE DES ACTIONS ====================

header('Content-Type: application/json');

try {
    $shop_id = $_SESSION['shop_id'];
    
    switch ($action) {
        case 'backup':
            $result = backupDatabase($shop_id);
            break;
            
        case 'list_backups':
            $result = listBackups($shop_id);
            break;
            
        case 'destroy':
            // Double vérification de sécurité
            if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'DESTROY') {
                $result = [
                    'success' => false,
                    'error' => 'Confirmation requise'
                ];
            } else {
                $result = destroyDatabase($shop_id);
            }
            break;
            
        case 'restore':
            $backup_filename = $_POST['backup_file'] ?? '';
            if (empty($backup_filename)) {
                $result = [
                    'success' => false,
                    'error' => 'Nom du fichier de backup requis'
                ];
            } else {
                $result = restoreDatabase($shop_id, $backup_filename);
            }
            break;
            
        case 'delete_backup':
            $backup_filename = $_POST['backup_file'] ?? '';
            if (empty($backup_filename)) {
                $result = [
                    'success' => false,
                    'error' => 'Nom du fichier de backup requis'
                ];
            } else {
                $result = deleteBackup($shop_id, $backup_filename);
            }
            break;
            
        default:
            $result = [
                'success' => false,
                'error' => 'Action invalide'
            ];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    logOperation("EXCEPTION: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
