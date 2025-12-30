<?php
/**
 * Système de cache simple pour GeekBoard
 * Améliore les performances en stockant les résultats de requêtes coûteuses
 */

/**
 * Récupère une valeur du cache
 * 
 * @param PDO $pdo Connexion PDO
 * @param string $key Clé du cache
 * @param int $shop_id ID du magasin (optionnel)
 * @return mixed|null Données du cache ou null si expiré/inexistant
 */
function get_cache($pdo, $key, $shop_id = 0) {
    try {
        // Vérifier si la table existe
        $tables = $pdo->query("SHOW TABLES LIKE 'log_statistics_cache'")->fetchAll();
        if (empty($tables)) {
            return null;
        }
        
        $stmt = $pdo->prepare("
            SELECT cache_data 
            FROM log_statistics_cache 
            WHERE shop_id = ? 
            AND cache_key = ? 
            AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$shop_id, $key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return json_decode($result['cache_data'], true);
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Erreur get_cache: " . $e->getMessage());
        return null;
    }
}

/**
 * Enregistre une valeur dans le cache
 * 
 * @param PDO $pdo Connexion PDO
 * @param string $key Clé du cache
 * @param mixed $data Données à mettre en cache
 * @param int $ttl Durée de vie en secondes (défaut: 300 = 5 minutes)
 * @param int $shop_id ID du magasin (optionnel)
 * @return bool Succès ou échec
 */
function set_cache($pdo, $key, $data, $ttl = 300, $shop_id = 0) {
    try {
        // Vérifier si la table existe
        $tables = $pdo->query("SHOW TABLES LIKE 'log_statistics_cache'")->fetchAll();
        if (empty($tables)) {
            return false;
        }
        
        $expires_at = date('Y-m-d H:i:s', time() + $ttl);
        $cache_data = json_encode($data);
        
        // Supprimer l'ancien cache si existe
        $stmt = $pdo->prepare("
            DELETE FROM log_statistics_cache 
            WHERE shop_id = ? AND cache_key = ?
        ");
        $stmt->execute([$shop_id, $key]);
        
        // Insérer le nouveau cache
        $stmt = $pdo->prepare("
            INSERT INTO log_statistics_cache 
            (shop_id, cache_key, cache_data, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$shop_id, $key, $cache_data, $expires_at]);
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur set_cache: " . $e->getMessage());
        return false;
    }
}

/**
 * Invalide un cache spécifique
 * 
 * @param PDO $pdo Connexion PDO
 * @param string $key Clé du cache (peut utiliser % pour wildcard)
 * @param int $shop_id ID du magasin (optionnel)
 * @return bool Succès ou échec
 */
function invalidate_cache($pdo, $key, $shop_id = 0) {
    try {
        $tables = $pdo->query("SHOW TABLES LIKE 'log_statistics_cache'")->fetchAll();
        if (empty($tables)) {
            return false;
        }
        
        if (strpos($key, '%') !== false) {
            $stmt = $pdo->prepare("
                DELETE FROM log_statistics_cache 
                WHERE shop_id = ? AND cache_key LIKE ?
            ");
        } else {
            $stmt = $pdo->prepare("
                DELETE FROM log_statistics_cache 
                WHERE shop_id = ? AND cache_key = ?
            ");
        }
        
        $stmt->execute([$shop_id, $key]);
        return true;
    } catch (Exception $e) {
        error_log("Erreur invalidate_cache: " . $e->getMessage());
        return false;
    }
}

/**
 * Nettoie les caches expirés
 * 
 * @param PDO $pdo Connexion PDO
 * @return int Nombre de caches supprimés
 */
function clean_expired_cache($pdo) {
    try {
        $tables = $pdo->query("SHOW TABLES LIKE 'log_statistics_cache'")->fetchAll();
        if (empty($tables)) {
            return 0;
        }
        
        $stmt = $pdo->prepare("
            DELETE FROM log_statistics_cache 
            WHERE expires_at < NOW()
        ");
        $stmt->execute();
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Erreur clean_expired_cache: " . $e->getMessage());
        return 0;
    }
}

/**
 * Wrapper pour exécuter une fonction avec cache
 * 
 * @param PDO $pdo Connexion PDO
 * @param string $key Clé du cache
 * @param callable $callback Fonction à exécuter si cache manquant
 * @param int $ttl Durée de vie en secondes
 * @param int $shop_id ID du magasin
 * @return mixed Résultat (du cache ou de la fonction)
 */
function cached($pdo, $key, $callback, $ttl = 300, $shop_id = 0) {
    $cached = get_cache($pdo, $key, $shop_id);
    
    if ($cached !== null) {
        return $cached;
    }
    
    $result = $callback();
    set_cache($pdo, $key, $result, $ttl, $shop_id);
    
    return $result;
}

