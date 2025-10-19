<?php
/**
 * Script de synchronisation automatique des mappings de sous-domaines
 * Exécuté toutes les 2 minutes via cron
 * 
 * Ce script :
 * 1. Vérifie s'il y a de nouveaux sous-domaines dans la base de données
 * 2. Met à jour automatiquement les mappings statiques
 * 3. Log les opérations pour traçabilité
 */

// Configuration
define('LOG_FILE', '/var/log/geekboard_sync_mappings.log');
define('LOCK_FILE', '/tmp/geekboard_sync_mappings.lock');

/**
 * Fonction de logging avec timestamp
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Vérifier si le script est déjà en cours d'exécution
 */
function checkLock() {
    if (file_exists(LOCK_FILE)) {
        $pid = file_get_contents(LOCK_FILE);
        // Vérifier si le processus existe encore
        if (posix_kill($pid, 0)) {
            logMessage("SKIP: Script déjà en cours d'exécution (PID: $pid)");
            exit(0);
        } else {
            // Le processus n'existe plus, supprimer le fichier de verrouillage
            unlink(LOCK_FILE);
        }
    }
    
    // Créer le fichier de verrouillage
    file_put_contents(LOCK_FILE, getmypid());
}

/**
 * Nettoyer le fichier de verrouillage
 */
function cleanup() {
    if (file_exists(LOCK_FILE)) {
        unlink(LOCK_FILE);
    }
}

// Gérer les signaux pour nettoyer le lock en cas d'arrêt
register_shutdown_function('cleanup');

try {
    // Vérifier le verrouillage
    checkLock();
    
    logMessage("DEBUT: Synchronisation automatique des mappings");
    
    // Changer vers le répertoire de l'application
    chdir('/var/www/mdgeek.top');
    
    // Inclure la configuration
    require_once 'config/subdomain_database_detector.php';
    
    // Obtenir la liste actuelle des mappings depuis le fichier
    $detector = new SubdomainDatabaseDetector();
    $config_file = '/var/www/mdgeek.top/config/subdomain_database_detector.php';
    $current_content = file_get_contents($config_file);
    
    // Extraire les mappings actuels
    preg_match('/private\s+\$subdomain_mappings\s*=\s*\[(.*?)\];/s', $current_content, $matches);
    $current_mappings = [];
    
    if (isset($matches[1])) {
        $mappings_content = $matches[1];
        preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $mappings_content, $mapping_matches);
        
        for ($i = 0; $i < count($mapping_matches[1]); $i++) {
            $subdomain = $mapping_matches[1][$i];
            $db_name = $mapping_matches[2][$i];
            $current_mappings[$subdomain] = $db_name;
        }
    }
    
    // Obtenir les sous-domaines de la base de données
    $dsn = "mysql:host=localhost;port=3306;dbname=geekboard_general;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', 'Mamanmaman01#', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $pdo->exec("SET time_zone = 'Europe/Paris'");
    
    $stmt = $pdo->prepare("SELECT subdomain, db_name FROM shops WHERE active = 1 AND subdomain IS NOT NULL AND subdomain != '' ORDER BY subdomain");
    $stmt->execute();
    $db_shops = $stmt->fetchAll();
    
    // Créer le mapping attendu
    $expected_mappings = [];
    foreach ($db_shops as $shop) {
        $expected_mappings[$shop['subdomain']] = $shop['db_name'];
    }
    
    // Ajouter les mappings par défaut
    $expected_mappings['mdgeek'] = 'geekboard_general';
    $expected_mappings['www'] = 'geekboard_general';
    $expected_mappings[''] = 'geekboard_general';
    
    // Comparer les mappings
    $missing_mappings = array_diff_assoc($expected_mappings, $current_mappings);
    $extra_mappings = array_diff_assoc($current_mappings, $expected_mappings);
    
    if (empty($missing_mappings) && empty($extra_mappings)) {
        logMessage("OK: Aucune synchronisation nécessaire - mappings à jour");
    } else {
        // Il y a des différences, synchroniser
        $changes = [];
        
        if (!empty($missing_mappings)) {
            $changes[] = count($missing_mappings) . " nouveau(x) mapping(s)";
            foreach ($missing_mappings as $subdomain => $db_name) {
                logMessage("NOUVEAU: '$subdomain' => '$db_name'");
            }
        }
        
        if (!empty($extra_mappings)) {
            $changes[] = count($extra_mappings) . " mapping(s) obsolète(s)";
            foreach ($extra_mappings as $subdomain => $db_name) {
                logMessage("OBSOLETE: '$subdomain' => '$db_name'");
            }
        }
        
        logMessage("SYNC: Synchronisation nécessaire - " . implode(', ', $changes));
        
        // Effectuer la synchronisation
        $sync_result = $detector->syncStaticMappings();
        
        if ($sync_result) {
            logMessage("SUCCESS: Mappings synchronisés avec succès");
        } else {
            logMessage("ERROR: Échec de la synchronisation des mappings");
        }
    }
    
    logMessage("FIN: Synchronisation automatique terminée");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    logMessage("TRACE: " . $e->getTraceAsString());
} finally {
    cleanup();
}
?>
