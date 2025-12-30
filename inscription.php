<?php
// Start buffering immediately to catch any premature output (like DB errors)
ob_start();

// Early AJAX detection to handle fatal errors during initialization
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Disable error display in output, we want JSON
    ini_set('display_errors', 0);
    
    // Catch fatal errors that might occur before the main logic
    register_shutdown_function(function() {
        $error = error_get_last();
        // Check for fatal errors
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            // Clear any buffered output (HTML error pages etc)
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(200); // Return 200 so JS can process the JSON error
            }
            
            echo json_encode([
                'success' => false, 
                'errors' => ['Erreur critique système: ' . $error['message']]
            ]);
        }
    });
}
// Page d'inscription publique pour créer un magasin - Version avec modal de chargement
session_start();

// Inclure la configuration de la base de données
require_once('config/database.php');

// Utiliser la base de données principale (geekboard_general)
$pdo = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$errors = [];
$success_data = null;

/**
 * Fonction pour mettre à jour le mapping des sous-domaines dans login_auto.php et subdomain_database_detector.php
 */
function updateSubdomainMapping($subdomain, $shop_id, $shop_name, $db_name) {
    $login_auto_path = __DIR__ . '/pages/login_auto.php';
    
    // Debug: log du chemin utilisé
    error_log("INSCRIPTION: Chemin login_auto utilisé: " . $login_auto_path);
    error_log("INSCRIPTION: __DIR__ = " . __DIR__);
    error_log("INSCRIPTION: Fichier existe? " . (file_exists($login_auto_path) ? 'OUI' : 'NON'));
    
    // Note: La synchronisation des mappings statiques se fait maintenant après la création complète du magasin
    
    try {
        // Connexion à la base de données principale pour récupérer tous les shops
        $pdo_general = new PDO("mysql:host=localhost;dbname=geekboard_general", 'root', 'Mamanmaman01#');
        $pdo_general->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Récupérer tous les shops actifs
        $stmt = $pdo_general->prepare("SELECT id, name, subdomain, db_name FROM shops WHERE active = 1 AND subdomain IS NOT NULL AND subdomain != '' AND subdomain != 'general' ORDER BY id");
        $stmt->execute();
        $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lire le fichier actuel
        $content = file_get_contents($login_auto_path);
        if ($content === false) {
            error_log("Erreur : Impossible de lire le fichier login_auto.php");
            return false;
        }

        // Tentative rapide: append d'une seule entrée juste avant la fermeture du tableau si déjà présent
        $quick_array_start = strpos($content, '$shop_mapping = [');
        $quick_array_end = $quick_array_start !== false ? strpos($content, '];', $quick_array_start) : false;
        if ($quick_array_start !== false && $quick_array_end !== false) {
            // Vérifier si l'entrée existe déjà
            if (strpos($content, "'" . $subdomain . "' => [") === false) {
                $before_close = substr($content, 0, $quick_array_end);
                $after_close = substr($content, $quick_array_end);
                // Ajouter une virgule si le tableau n'est pas vide
                $insertion = "\n    '" . $subdomain . "' => ['id' => " . (int)$shop_id . ", 'name' => '" . addslashes($shop_name) . "', 'db' => '" . $db_name . "']";
                // Si juste avant il n'y a pas une virgule et pas la ligne d'en-tête, on ajoute une virgule
                $trimmed_before = rtrim($before_close);
                if (substr($trimmed_before, -1) !== '[' && substr($trimmed_before, -1) !== ',') {
                    $insertion = "," . $insertion;
                }
                $new_quick_content = $before_close . $insertion . $after_close;
                if (file_put_contents($login_auto_path, $new_quick_content) !== false) {
                    error_log("INSCRIPTION: Ajout rapide mapping pour $subdomain effectué");
                    return true;
                } else {
                    error_log("INSCRIPTION: Échec ajout rapide, fallback reconstruction complète");
                }
            } else {
                error_log("INSCRIPTION: Entrée $subdomain déjà présente (append ignoré)");
                return true;
            }
        }
        
        // Fallback: reconstruction complète - Méthode robuste : trouver manuellement la section
        // Créer le nouveau tableau shop_mapping complet
        $new_mapping_lines = [];
        
        // Ajouter les entrées fixes originales
        $new_mapping_lines[] = "    'pscannes' => ['id' => 2, 'name' => 'PScannes', 'db' => 'geekboard_pscannes'],";
        $new_mapping_lines[] = "    'psphonac' => ['id' => 6, 'name' => 'PSPHONAC', 'db' => 'geekboard_psphonac'],";
        
        // Ajouter toutes les entrées de la base de données
        foreach ($shops as $shop) {
            $escaped_name = addslashes($shop['name']);
            $new_mapping_lines[] = "    '" . $shop['subdomain'] . "' => ['id' => " . $shop['id'] . ", 'name' => '" . $escaped_name . "', 'db' => '" . $shop['db_name'] . "'],";
        }
        
        // Enlever la virgule de la dernière ligne
        $last_index = count($new_mapping_lines) - 1;
        $new_mapping_lines[$last_index] = rtrim($new_mapping_lines[$last_index], ',');
        
        // Construire le nouveau tableau
        $new_mapping_section = "// Mapping des sous-domaines vers les infos de magasin\n\$shop_mapping = [\n" . implode("\n", $new_mapping_lines) . "\n];";
        
        $start_pos = strpos($content, '// Mapping des sous-domaines');
        if ($start_pos === false) {
            error_log("INSCRIPTION: Section mapping non trouvée");
            return false;
        }
        
        $array_start = strpos($content, '$shop_mapping = [', $start_pos);
        if ($array_start === false) {
            error_log("INSCRIPTION: Début du tableau non trouvé");
            return false;
        }
        
        $array_end = strpos($content, '];', $array_start);
        if ($array_end === false) {
            error_log("INSCRIPTION: Fin du tableau non trouvée");
            return false;
        }
        
        // Remplacer manuellement la section
        $before = substr($content, 0, $start_pos);
        $after = substr($content, $array_end + 2);
        $new_content = $before . $new_mapping_section . $after;
        
        error_log("INSCRIPTION: Remplacement manuel effectué - Diff: " . (strlen($new_content) - strlen($content)) . " octets");
        
        // Écrire le fichier modifié
        if (file_put_contents($login_auto_path, $new_content) !== false) {
            error_log("INSCRIPTION: Mapping synchronisé avec succès - " . count($shops) . " magasins actifs");
            return true;
        } else {
            error_log("INSCRIPTION: Erreur - Impossible d'écrire dans le fichier login_auto.php");
            error_log("INSCRIPTION: Chemin du fichier: " . $login_auto_path);
            error_log("INSCRIPTION: Permissions du fichier: " . (file_exists($login_auto_path) ? substr(sprintf('%o', fileperms($login_auto_path)), -4) : 'FICHIER N\'EXISTE PAS'));
            return false;
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la synchronisation du mapping : " . $e->getMessage());
        return false;
    }
}

/**
 * Fonction pour valider un sous-domaine
 */
function validateSubdomain($subdomain) {
    // Nettoyer le sous-domaine
    $subdomain = strtolower(trim($subdomain));
    
    // Vérifier le format : uniquement lettres, chiffres et tirets
    if (!preg_match('/^[a-z0-9\-]{2,30}$/', $subdomain)) {
        return false;
    }
    
    // Ne peut pas commencer ou finir par un tiret
    if (substr($subdomain, 0, 1) === '-' || substr($subdomain, -1) === '-') {
        return false;
    }
    
    // Ne peut pas contenir deux tirets consécutifs
    if (strpos($subdomain, '--') !== false) {
        return false;
    }
    
    // Vérifier que ce n'est pas un mot réservé
    $reserved = ['www', 'mail', 'ftp', 'admin', 'api', 'test', 'dev', 'staging', 'prod', 'production'];
    if (in_array($subdomain, $reserved)) {
        return false;
    }
    
    return true;
}

/**
 * Fonction pour mettre à jour le certificat SSL
 */
function updateSSLCertificate($subdomain) {
    try {
        $new_domain = $subdomain . '.servo.tools';
        error_log("SERVO SSL: Début correction automatique pour $new_domain");
        
        // Exécuter le script de correction automatique avec privilèges root
        // Utiliser le script FINAL qui force le certificat principal servo.tools
        
        // Passer le sous-domaine en paramètre au script amélioré
        $cmd = "sudo /usr/local/bin/ssl_wrapper.sh " . escapeshellarg($subdomain) . " > /dev/null 2>&1 &";
        error_log("SERVO SSL: Exécution commande : $cmd");
        $output = shell_exec($cmd);
        
        error_log("SERVO SSL: Sortie script : " . substr($output, 0, 500));
        
        // Vérifier le succès
        if (strpos($output, '✅ SSL_SUCCESS: Configuration complète pour servo.tools - Certificat automatique détecté') !== false ||
            strpos($output, '✅ SSL_SUCCESS: Configuration complète') !== false || 
            strpos($output, '✅ Certificat SSL étendu avec succès') !== false || 
            strpos($output, 'Successfully received certificate') !== false || 
            strpos($output, 'Certificate not yet due for renewal') !== false ||
            strpos($output, 'SSL_SUCCESS:') !== false) {
            error_log("SERVO SSL: Correction automatique réussie pour $new_domain");
            return true;
        } else {
            error_log("SERVO SSL: Échec correction automatique pour $new_domain : " . $output);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("SERVO SSL: Exception lors de la correction : " . $e->getMessage());
        return false;
    }
}

/**
 * Ajouter automatiquement un sous-domaine à la configuration Nginx (méthode simplifiée)
 */
function addSubdomainToNginx($subdomain) {
    try {
        $new_domain = $subdomain . '.servo.tools';
        $nginx_config_file = '/etc/nginx/sites-available/servo.tools.conf';
        
        // Vérifier si le sous-domaine existe déjà dans la configuration
        $existing_config = file_get_contents($nginx_config_file);
        if (strpos($existing_config, "server_name {$new_domain};") !== false) {
            error_log("Configuration nginx pour $new_domain existe déjà");
            return true;
        }
        
        // Créer la configuration nginx pour le nouveau sous-domaine (méthode simple et fiable)
        $nginx_block = "\n# {$new_domain}\n";
        $nginx_block .= "server {\n";
        $nginx_block .= "    listen 80;\n";
        $nginx_block .= "    server_name {$new_domain};\n";
        $nginx_block .= "    root /var/www/mdgeek.top;\n";
        $nginx_block .= "    index index.php index.html index.htm;\n";
        $nginx_block .= "    set \$shop_subdomain \"{$subdomain}\";\n";
        $nginx_block .= "    location / { try_files \$uri \$uri/ @rewrite; }\n";
        $nginx_block .= "    location @rewrite {\n";
        $nginx_block .= "        rewrite ^/([^/]+)/?\$ /\$1.php last;\n";
        $nginx_block .= "        rewrite ^(.+)\$ /index.php?\$query_string last;\n";
        $nginx_block .= "    }\n";
        $nginx_block .= "    location ~ \\.php\$ {\n";
        $nginx_block .= "        include snippets/fastcgi-php.conf;\n";
        $nginx_block .= "        fastcgi_pass unix:/run/php/php8.3-fpm.sock;\n";
        $nginx_block .= "        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;\n";
        $nginx_block .= "        fastcgi_param SHOP_SUBDOMAIN \$shop_subdomain;\n";
        $nginx_block .= "        include fastcgi_params;\n";
        $nginx_block .= "    }\n";
        $nginx_block .= "    location ~ /\\.ht { deny all; }\n";
        $nginx_block .= "    location ~ /\\.env { deny all; }\n";
        $nginx_block .= "}\n";
        
        // Ajouter la configuration à la fin du fichier nginx
        if (file_put_contents($nginx_config_file, $nginx_block, FILE_APPEND | LOCK_EX) === false) {
            error_log("Erreur : Impossible d'ajouter la configuration nginx pour $new_domain");
            return false;
        }
        
        // Tester et recharger nginx
        $nginx_test = shell_exec('nginx -t 2>&1');
        if (strpos($nginx_test, 'syntax is ok') === false || strpos($nginx_test, 'test is successful') === false) {
            error_log("Erreur de syntaxe nginx après ajout de $new_domain : " . $nginx_test);
            return false;
        }
        
        // Recharger nginx
        $nginx_reload = shell_exec('systemctl reload nginx 2>&1');
        error_log("Configuration nginx ajoutée et rechargée pour : " . $new_domain);
        return true;
        
    } catch (Exception $e) {
        error_log("Erreur lors de l'ajout de la configuration nginx : " . $e->getMessage());
        return false;
    }
}

/**
 * Ajouter automatiquement un sous-domaine à la configuration Nginx (fonction originale - deprecated)
 */
function addSubdomainToNginx_OLD($subdomain) {
    try {
        $domain = $subdomain . '.servo.tools';
        
        // Nouveau bloc serveur à ajouter (sans échappements excessifs)
        $new_server_block = <<<EOD

# {$domain}
server {
    listen 80;
    server_name {$domain};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name {$domain};
    root /var/www/mdgeek.top;
    index index.php index.html index.htm;

    ssl_certificate /etc/letsencrypt/live/{$domain}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/{$domain}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    access_log /var/log/nginx/{$subdomain}_servo_access.log;
    error_log /var/log/nginx/{$subdomain}_servo_error.log;

    set \$shop_subdomain "{$subdomain}";

    location / {
        try_files \$uri \$uri/ @rewrite;
    }

    location @rewrite {
        rewrite ^/([^/]+)/?$ /\$1.php last;
        rewrite ^(.+)$ /index.php?\$query_string last;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param SHOP_SUBDOMAIN \$shop_subdomain;
        include fastcgi_params;
    }

    location ~ /\.ht { deny all; }
    location ~ /\.env { deny all; }
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOD;

        // Créer le script pour ajouter le bloc serveur
        $temp_script = "/tmp/add_nginx_" . uniqid() . ".sh";
        
        $script_content = "#!/bin/bash\n";
        $script_content .= "set -e\n";
        $script_content .= "# Script pour ajouter automatiquement {$domain} à la configuration Nginx\n\n";
        
        // Éviter les doublons puis append
        $script_content .= "if grep -q \"server_name {$domain};\" /etc/nginx/sites-available/servo.tools.conf; then\n";
        $script_content .= "  echo \"NGINX_INFO: {$domain} déjà présent\"\n";
        $script_content .= "else\n";
        $script_content .= "  cat >> /etc/nginx/sites-available/servo.tools.conf << 'EOF'\n";
        $script_content .= $new_server_block . "\n";
        $script_content .= "EOF\n";
        $script_content .= "fi\n\n";
        
        // Tester et recharger Nginx
        $script_content .= "nginx -t 2>&1\n";
        $script_content .= "systemctl reload nginx 2>&1\n";
        $script_content .= "echo \"NGINX_SUCCESS: {$domain} ajouté/vérifié\"\n";
        
        // Créer et exécuter le script
        file_put_contents($temp_script, $script_content);
        chmod($temp_script, 0755);
        
        $output = shell_exec("sudo bash " . escapeshellarg($temp_script) . " 2>&1");
        
        error_log("Sortie script Nginx pour {$domain}: " . $output);
        
        // Nettoyer le script temporaire
        unlink($temp_script);
        
        if (strpos($output, 'NGINX_SUCCESS') !== false) {
            error_log("Configuration Nginx mise à jour avec succès pour {$domain}");
            return true;
        } else {
            error_log("Erreur lors de la mise à jour de la configuration Nginx pour {$domain}: " . $output);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Exception lors de l'ajout Nginx pour {$subdomain}: " . $e->getMessage());
        return false;
    }
}

/**
 * Fonction pour inclure et utiliser la logique de create_shop.php
 */
function createShopForOwner($shop_owner_data) {
    // Utiliser le sous-domaine fourni par l'utilisateur
    $subdomain = $shop_owner_data['subdomain'];
    
    // Vérifier l'unicité du sous-domaine dans la table shops
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM shops WHERE subdomain = ?");
    $stmt->execute([$subdomain]);
    if ($stmt->fetch()) {
        throw new Exception("Ce sous-domaine est déjà utilisé");
    }
    
    $shop_name = !empty($shop_owner_data['nom_commercial']) ? 
                 $shop_owner_data['nom_commercial'] : 
                 "Boutique " . $shop_owner_data['prenom'] . " " . $shop_owner_data['nom'];
    
    // Reprendre la logique de create_shop.php
    try {
        // Informations de base de données
        $db_name = 'geekboard_' . strtolower($subdomain);
        $db_user = 'gb_' . strtolower($subdomain);
        $db_pass = 'Admin123!';
        $db_host = 'localhost';
        
        // Connexion à MySQL pour créer la base de données
        $pdo_mysql = new PDO("mysql:host=$db_host", 'root', 'Mamanmaman01#');
        $pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer la base de données
        $pdo_mysql->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        
        // Supprimer l'utilisateur MySQL s'il existe déjà
        try {
            $pdo_mysql->exec("DROP USER IF EXISTS '$db_user'@'localhost'");
        } catch (PDOException $e) {
            // Ignorer l'erreur si l'utilisateur n'existe pas
        }
        
        // Créer l'utilisateur MySQL pour ce magasin
        $pdo_mysql->exec("CREATE USER '$db_user'@'localhost' IDENTIFIED BY '$db_pass'");
        $pdo_mysql->exec("GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'localhost'");
        $pdo_mysql->exec("GRANT ALL PRIVILEGES ON `$db_name`.* TO 'geekboard_user'@'localhost'");
        $pdo_mysql->exec("FLUSH PRIVILEGES");
        
        // Connexion à la nouvelle base
        $shop_pdo = new PDO("mysql:host=$db_host;dbname=$db_name", 'root', 'Mamanmaman01#');
        $shop_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Insertion dans la base principale des shops
        $stmt = $pdo->prepare("INSERT INTO shops (name, subdomain, db_host, db_port, db_name, db_user, db_pass, active) VALUES (?, ?, ?, '3306', ?, ?, ?, 1)");
        $stmt->execute([$shop_name, $subdomain, $db_host, $db_name, $db_user, $db_pass]);
        $shop_id = $pdo->lastInsertId();
        
        // Initialiser la période d'essai immédiatement après création du shop (avant SSL qui peut timeout)
        error_log("INSCRIPTION: Début initializeTrialPeriod pour shop_id=$shop_id");
        require_once(__DIR__ . '/classes/SubscriptionManager.php');
        try {
            $subscriptionManager = new SubscriptionManager($shop_id);
            $trial_initialized = $subscriptionManager->initializeTrialPeriod($shop_id);
            error_log("INSCRIPTION: initializeTrialPeriod résultat: " . ($trial_initialized ? 'SUCCESS' : 'FAILED'));
        } catch (Exception $trialException) {
            error_log("INSCRIPTION: ERREUR initializeTrialPeriod: " . $trialException->getMessage());
            $trial_initialized = false;
        }
        
        // Charger et exécuter le script SQL complet via mysql CLI
        // Cette méthode est plus fiable que le parsing PHP car elle préserve
        // les commentaires conditionnels MySQL et évite les problèmes de parsing
        $sql_file = __DIR__ . '/superadmin/geekboard_complete_structure.sql';
        if (!file_exists($sql_file)) {
            throw new Exception("Fichier de structure SQL introuvable: " . $sql_file);
        }
        
        error_log("INSCRIPTION: Import SQL via mysql CLI pour $db_name depuis $sql_file");
        
        // Exécuter le fichier SQL directement via mysql CLI
        // Utiliser le chemin absolu et éviter escapeshellarg qui peut causer des problèmes
        $mysql_cmd = "mysql -u root -p'Mamanmaman01#' -h {$db_host} {$db_name} < '{$sql_file}' 2>&1";
        
        error_log("INSCRIPTION: Commande MySQL: " . preg_replace("/p'[^']+'/", "p'***'", $mysql_cmd));
        
        $output = [];
        $return_var = 0;
        exec($mysql_cmd, $output, $return_var);
        
        if ($return_var !== 0) {
            $error_output = implode("\n", $output);
            error_log("INSCRIPTION: Erreur import SQL (code $return_var): " . $error_output);
            // Throw une exception si l'import échoue
            throw new Exception("Erreur lors de l'import SQL: " . $error_output);
        } else {
            error_log("INSCRIPTION: Import SQL réussi pour $db_name");
        }
        
        // Vérifier le nombre de tables créées
        $table_count_result = $shop_pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$db_name'");
        $table_count = $table_count_result->fetchColumn();
        error_log("INSCRIPTION: $table_count tables créées dans $db_name");
        
        // Connexion PDO pour les opérations suivantes (user admin, etc.)
        $shop_pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Supprimer les données de bug_reports (données de test)
        try {
            $shop_pdo->exec("TRUNCATE TABLE bug_reports");
        } catch (PDOException $e) {
            // Ignorer si la table n'existe pas
        }
        
        // Supprimer les utilisateurs existants (sauf structure)
        try {
            $shop_pdo->exec("DELETE FROM users WHERE 1");
        } catch (PDOException $e) {
            // Ignorer si la table n'existe pas
        }
        
        $shop_pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Créer l'utilisateur admin avec l'email du propriétaire
        $admin_username = $shop_owner_data['email'];
        $admin_password = 'Admin123!';
        $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
        $admin_full_name = $shop_owner_data['prenom'] . ' ' . $shop_owner_data['nom'];
        
        // Utiliser une requête préparée pour éviter les problèmes d'échappement
        $stmt = $shop_pdo->prepare("INSERT INTO users (username, password, full_name, role, created_at, techbusy, is_online, cagnotte, points_experience, score_total, isActiveTask) VALUES (?, ?, ?, 'admin', NOW(), 0, 0, 0.00, 0, 0, 0)");
        $stmt->execute([$admin_username, $password_hash, $admin_full_name]);
        
        // Mise à jour du mapping des sous-domaines avec logging détaillé
        error_log("INSCRIPTION: Début mise à jour mapping pour $subdomain (ID: $shop_id)");
        $mapping_updated = updateSubdomainMapping($subdomain, $shop_id, $shop_name, $db_name);
        error_log("INSCRIPTION: Résultat mapping pour $subdomain: " . ($mapping_updated ? 'SUCCÈS' : 'ÉCHEC'));
        
        // Synchroniser les mappings statiques après création complète
        require_once(__DIR__ . '/config/subdomain_database_detector.php');
        $static_sync_result = syncSubdomainMappings();
        error_log("INSCRIPTION: Synchronisation finale mappings statiques: " . ($static_sync_result ? 'SUCCÈS' : 'ÉCHEC'));
        
        // ÉTAPE 1 : Ajouter la configuration nginx de base pour le nouveau sous-domaine
        // Configuration nginx gérée automatiquement par le script amélioré
        // $nginx_added = addSubdomainToNginx($subdomain); // DÉSACTIVÉ
        
        // ÉTAPE 2 : Étendre le certificat SSL principal avec le nouveau sous-domaine (méthode mdgeek.top)
        $ssl_updated = updateSSLCertificate($subdomain);
        
        // Note: initializeTrialPeriod a été déplacé plus tôt (juste après création du shop_id)
        // pour éviter les problèmes de timeout SSL
        
        return [
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
            'subdomain' => $subdomain,
            'url' => 'https://' . $subdomain . '.servo.tools',
            'db_name' => $db_name,
            'admin_username' => $admin_username,
            'admin_password' => $admin_password,
            'mapping_updated' => $mapping_updated,
            'static_mappings_synced' => $static_sync_result,
            'nginx_added' => $nginx_added,
            'ssl_updated' => $ssl_updated,
            'trial_initialized' => $trial_initialized
        ];
        
    } catch (Exception $e) {
        throw new Exception('Erreur lors de la création du magasin: ' . $e->getMessage());
    }
}

// Traitement du formulaire (AJAX ou normal)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $nom_commercial = trim($_POST['nom_commercial'] ?? '');
    $subdomain = trim($_POST['subdomain'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $cgu_acceptees = isset($_POST['cgu_acceptees']) ? 1 : 0;
    $cgv_acceptees = isset($_POST['cgv_acceptees']) ? 1 : 0;
    
    // Si c'est une requête AJAX, nous devons nous assurer que rien d'autre n'est affiché avant le JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Nettoyer tout buffer de sortie précédent
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Démarrer la capture d'erreurs fatales
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && ($error['type'] === E_ERROR || $error['type'] === E_USER_ERROR || $error['type'] === E_PARSE)) {
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                }
                echo json_encode(['success' => false, 'errors' => ['Erreur fatale serveur: ' . $error['message']]]);
            }
        });
    }
    
    // Validation
    if (empty($nom)) {
        $errors[] = 'Le nom est obligatoire.';
    }
    
    if (empty($prenom)) {
        $errors[] = 'Le prénom est obligatoire.';
    }
    
    if (empty($subdomain)) {
        $errors[] = 'Le sous-domaine est obligatoire.';
    } elseif (!validateSubdomain($subdomain)) {
        $errors[] = 'Le sous-domaine n\'est pas valide. Utilisez uniquement des lettres, chiffres et tirets (2-30 caractères).';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Une adresse email valide est obligatoire.';
    }
    
    if (empty($telephone)) {
        $errors[] = 'Le numéro de téléphone est obligatoire.';
    }
    
    if (empty($adresse)) {
        $errors[] = 'L\'adresse postale est obligatoire.';
    }
    
    if (empty($code_postal)) {
        $errors[] = 'Le code postal est obligatoire.';
    }
    
    if (empty($ville)) {
        $errors[] = 'La ville est obligatoire.';
    }
    
    if (!$cgu_acceptees) {
        $errors[] = 'Vous devez accepter les Conditions Générales d\'Utilisation.';
    }
    
    if (!$cgv_acceptees) {
        $errors[] = 'Vous devez accepter les Conditions Générales de Vente.';
    }
    
    // Vérifier l'unicité de l'email et du sous-domaine
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM shop_owners WHERE email = ? OR subdomain = ?");
        $stmt->execute([$email, $subdomain]);
        if ($stmt->fetch()) {
            $errors[] = 'Cet email ou ce sous-domaine est déjà utilisé.';
        }
        
        // Vérifier aussi l'unicité dans la table shops
        $stmt = $pdo->prepare("SELECT id FROM shops WHERE subdomain = ?");
        $stmt->execute([$subdomain]);
        if ($stmt->fetch()) {
            $errors[] = 'Ce sous-domaine est déjà utilisé par un magasin existant.';
        }
    }
    
    // Si c'est une requête AJAX, retourner JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        
        if (!empty($errors)) {
            // S'assurer que le header est bien envoyé
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        // Si pas d'erreurs, créer l'inscription et le magasin
        try {
            $pdo->beginTransaction();
            
            // Insérer les données du propriétaire avec mot de passe par défaut
            $default_password = 'Admin123!';
            $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO shop_owners 
                (nom, prenom, nom_commercial, subdomain, email, password, telephone, adresse, code_postal, ville, cgu_acceptees, cgv_acceptees, statut) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'actif')
            ");
            
            $stmt->execute([
                $nom, $prenom, $nom_commercial, $subdomain, $email, $password_hash,
                $telephone, $adresse, $code_postal, $ville, $cgu_acceptees, $cgv_acceptees
            ]);
            
            $owner_id = $pdo->lastInsertId();
            
            // Préparer les données pour la création du magasin
            $shop_owner_data = [
                'nom' => $nom,
                'prenom' => $prenom,
                'nom_commercial' => $nom_commercial,
                'subdomain' => $subdomain,
                'email' => $email
            ];
            
            // Créer le magasin
            $shop_data = createShopForOwner($shop_owner_data);
            
            // Mettre à jour le shop_owner avec l'ID du magasin créé
            $stmt = $pdo->prepare("UPDATE shop_owners SET shop_id = ?, date_creation_shop = NOW() WHERE id = ?");
            $stmt->execute([$shop_data['shop_id'], $owner_id]);
            
            $pdo->commit();
            
            $success_data = array_merge($shop_data, [
                'owner_nom' => $nom,
                'owner_prenom' => $prenom,
                'owner_email' => $email
            ]);
            
            echo json_encode(['success' => true, 'data' => $success_data]);
            exit;
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'errors' => ['Erreur lors de la création: ' . $e->getMessage()]]);
            exit;
        } catch (Throwable $e) {
            // Capture toutes les autres erreurs (PHP 7+)
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode(['success' => false, 'errors' => ['Erreur critique: ' . $e->getMessage()]]);
            exit;
        }
    }
    
    // Si pas d'erreurs et pas AJAX, créer l'inscription et le magasin (mode normal)
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insérer les données du propriétaire avec mot de passe par défaut
            $default_password = 'Admin123!';
            $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO shop_owners 
                (nom, prenom, nom_commercial, subdomain, email, password, telephone, adresse, code_postal, ville, cgu_acceptees, cgv_acceptees, statut) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'actif')
            ");
            
            $stmt->execute([
                $nom, $prenom, $nom_commercial, $subdomain, $email, $password_hash,
                $telephone, $adresse, $code_postal, $ville, $cgu_acceptees, $cgv_acceptees
            ]);
            
            $owner_id = $pdo->lastInsertId();
            
            // Préparer les données pour la création du magasin
            $shop_owner_data = [
                'nom' => $nom,
                'prenom' => $prenom,
                'nom_commercial' => $nom_commercial,
                'subdomain' => $subdomain,
                'email' => $email
            ];
            
            // Créer le magasin
            $shop_data = createShopForOwner($shop_owner_data);
            
            // Mettre à jour le shop_owner avec l'ID du magasin créé
            $stmt = $pdo->prepare("UPDATE shop_owners SET shop_id = ?, date_creation_shop = NOW() WHERE id = ?");
            $stmt->execute([$shop_data['shop_id'], $owner_id]);
            
            $pdo->commit();
            
            $success_data = array_merge($shop_data, [
                'owner_nom' => $nom,
                'owner_prenom' => $prenom,
                'owner_email' => $email
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Erreur lors de la création: ' . $e->getMessage();
        }
    }
}

// Initialiser le système i18n comme fait le routeur marketing
require_once __DIR__ . '/marketing/includes/i18n.php';
loadPageTranslations('home'); // Charger les traductions de la page d'accueil

// Inclure le header marketing - exactement le même que toutes les autres pages
$header_path = __DIR__ . '/marketing/shared/header.php';
if (file_exists($header_path)) {
    include_once($header_path);
} else {
    // Fallback minimaliste si le header marketing n'existe pas
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inscription - SERVO</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </head>
    <body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="/">SERVO</a>
        </div>
    </nav>
    <?php
}
?>

<!-- Styles CSS personnalisés pour la page d'inscription (Cyber-Tech Glass Theme) -->
<style>
/* --- CORE THEME & BACKGROUND --- */
:root {
    --bg-deep: #030712;      /* Ultra dark blue/black */
    --primary: #06b6d4;      /* Electric Cyan */
    --accent: #d946ef;       /* Hot Pink */
    --glass-bg: rgba(15, 23, 42, 0.6);
    --glass-border: rgba(255, 255, 255, 0.08);
    --neon-glow: 0 0 10px rgba(6, 182, 212, 0.5);
    --text-main: #f8fafc;
    --text-muted: #94a3b8;
}

body {
    background-color: var(--bg-deep) !important;
    background-image: 
        radial-gradient(circle at 15% 50%, rgba(6, 182, 212, 0.08), transparent 25%),
        radial-gradient(circle at 85% 30%, rgba(217, 70, 239, 0.08), transparent 25%) !important;
    min-height: 100vh !important;
    font-family: 'Outfit', sans-serif !important;
    color: var(--text-main) !important;
    overflow-x: hidden;
}

/* --- GLASSMORPHISM COMPONENTS --- */
.card-modern {
    background: var(--glass-bg);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid var(--glass-border);
    border-radius: 24px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
}

.form-control, .form-select {
    background: rgba(2, 6, 23, 0.5) !important;
    border: 1px solid var(--glass-border) !important;
    color: var(--text-main) !important;
    border-radius: 12px !important;
    padding: 12px 16px !important;
    font-size: 0.95rem !important;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1) !important;
    background: rgba(2, 6, 23, 0.8) !important;
}

.form-label {
    color: var(--text-muted);
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 8px;
}

/* --- BUTTONS --- */
.btn-submit-modern {
    background: linear-gradient(135deg, var(--primary) 0%, #2563eb 100%);
    border: none;
    border-radius: 12px;
    padding: 16px;
    font-weight: 600;
    letter-spacing: 0.5px;
    color: white;
    width: 100%;
    margin-top: 20px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
}

.btn-submit-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(6, 182, 212, 0.4);
}

.btn-submit-modern:active {
    transform: translateY(0);
}

/* --- TYPOGRAPHY --- */
h1, h2, h3, h4, h5 {
    font-family: 'Space Grotesk', sans-serif !important;
    color: white;
}

.text-gradient {
    background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.text-neon {
    color: var(--primary);
    text-shadow: 0 0 10px rgba(6, 182, 212, 0.3);
}

/* --- DECORATIONS --- */
.feature-check {
    width: 20px;
    height: 20px;
    background: rgba(6, 182, 212, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 0.7rem;
    margin-right: 10px;
}

/* --- ANIMATIONS --- */
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.hero-visual {
    animation: float 6s ease-in-out infinite;
}

/* Loader (Keep existing if complex, or simplify) */
.loader-overlay {
    background: rgba(3, 7, 18, 0.9);
    backdrop-filter: blur(10px);
}
</style>

<!-- Main Container -->
<div class="position-relative overflow-hidden w-100" style="min-height: 100vh;">
    <!-- Background Elements -->
    <div class="position-absolute top-0 start-0 w-100 h-100 overflow-hidden" style="z-index: -1;">
        <div class="position-absolute top-0 end-0 bg-primary opacity-20 rounded-circle blur-3xl" style="width: 600px; height: 600px; filter: blur(100px); transform: translate(30%, -30%);"></div>
        <div class="position-absolute bottom-0 start-0 bg-secondary opacity-20 rounded-circle blur-3xl" style="width: 500px; height: 500px; filter: blur(100px); transform: translate(-30%, 30%);"></div>
    </div>

<?php if ($success_data): ?>
    <!-- SUCCESS STATE (Futuristic) -->
    <div class="container d-flex flex-column justify-content-center align-items-center min-vh-100 py-5">
        <div class="card-modern p-5 text-center" style="max-width: 700px; width: 100%;">
            <div class="mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-20 text-success p-4 mb-3" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-check fs-1"></i>
                </div>
            </div>
            
            <h1 class="display-5 fw-bold mb-3">Félicitations !</h1>
            <p class="fs-5 text-muted mb-5">
                Votre atelier <strong><?php echo htmlspecialchars($success_data['shop_name']); ?></strong> est prêt à décoller.
            </p>

            <div class="row g-4 text-start mb-5">
                <div class="col-md-6">
                    <div class="p-3 rounded-3 bg-white bg-opacity-5 border border-white border-opacity-10 h-100">
                        <small class="text-muted d-block mb-1">URL d'accès</small>
                        <a href="<?php echo $success_data['url']; ?>" class="fw-bold text-primary text-decoration-none fs-5 break-all">
                            <?php echo htmlspecialchars($success_data['url']); ?>
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded-3 bg-white bg-opacity-5 border border-white border-opacity-10 h-100">
                        <small class="text-muted d-block mb-1">Identifiant</small>
                        <span class="fw-bold text-white fs-5"><?php echo htmlspecialchars($success_data['admin_username']); ?></span>
                    </div>
                </div>
                <div class="col-12">
                    <div class="p-3 rounded-3 bg-white bg-opacity-5 border border-white border-opacity-10">
                        <small class="text-muted d-block mb-1">Mot de passe temporaire</small>
                        <span class="font-monospace text-warning fs-5"><?php echo htmlspecialchars($success_data['admin_password']); ?></span>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-3">
                <a href="<?php echo $success_data['url']; ?>" target="_blank" class="btn btn-primary btn-lg rounded-pill fw-bold py-3">
                    <i class="fa-solid fa-rocket me-2"></i> Accéder à mon Dashboard
                </a>
                <a href="/" class="btn btn-outline-light btn-lg rounded-pill fw-bold py-3">
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- REGISTRATION FORM (Futuristic) -->
    <div class="container py-5">
        <div class="row align-items-center justify-content-center min-vh-100">
            
            <!-- Left Column: Value Prop -->
            <div class="col-lg-5 mb-5 mb-lg-0 pe-lg-5 d-none d-lg-block">
                <div class="hero-visual">
                    <div class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-20 rounded-pill px-3 py-2 mb-4">
                        <i class="fa-solid fa-bolt me-2"></i> Installation en 120 secondes
                    </div>
                    <h1 class="display-3 fw-bold mb-4 lh-sm">
                        Le futur de votre <span class="text-neon">atelier</span> commence ici.
                    </h1>
                    <p class="lead text-muted mb-5">
                        Rejoignez 150+ réparateurs qui utilisent SERVO pour automatiser leur gestion. Essai gratuit, sans CB.
                    </p>
                    
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-center text-white">
                            <div class="feature-check"><i class="fa-solid fa-check"></i></div>
                            <span>Accès complet fonctionnalités Pro</span>
                        </div>
                        <div class="d-flex align-items-center text-white">
                            <div class="feature-check"><i class="fa-solid fa-check"></i></div>
                            <span>SMS illimités inclus (test)</span>
                        </div>
                        <div class="d-flex align-items-center text-white">
                            <div class="feature-check"><i class="fa-solid fa-check"></i></div>
                            <span>Pas de carte bancaire requise</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Form -->
            <div class="col-lg-6 col-xl-5">
                <div class="card-modern p-4 p-md-5">
                    
                    <div class="text-center mb-4 d-lg-none">
                        <img src="/assets/images/logo/logoservo.png" alt="Logo" height="40" class="mb-3">
                        <h2 class="fw-bold">Créer un compte</h2>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-20 text-danger rounded-3 mb-4">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form id="shopForm" method="post" class="row g-3">
                        
                        <!-- Section 1: Vous -->
                        <div class="col-12 mb-2">
                            <h5 class="text-white border-bottom border-white border-opacity-10 pb-2 mb-3">
                                <i class="fa-regular fa-user me-2 text-primary"></i>Vos informations
                            </h5>
                        </div>

                        <div class="col-md-6">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>

                                <input type="text" class="form-control" id="prenom" name="prenom" 
                                       value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" 
                                       placeholder="Votre prénom" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="nom" class="form-label fw-semibold">
                                    Nom <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" 
                                       placeholder="Votre nom de famille" required>
                            </div>
                            
                            <div class="col-12">
                                <label for="nom_commercial" class="form-label fw-semibold">
                                    Nom commercial <small class="text-muted">(facultatif)</small>
                                </label>
                                <input type="text" class="form-control" id="nom_commercial" name="nom_commercial" 
                                       value="<?php echo htmlspecialchars($_POST['nom_commercial'] ?? ''); ?>" 
                                       placeholder="Nom de votre entreprise/boutique">
                            </div>
                            
                            <div class="col-12">
                                <label for="subdomain" class="form-label fw-semibold">
                                    Sous-domaine <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="subdomain" name="subdomain" 
                                           value="<?php echo htmlspecialchars($_POST['subdomain'] ?? ''); ?>" 
                                           placeholder="monmagasin" 
                                           pattern="[a-z0-9\-]{2,30}" required 
                                           style="border-radius: 12px 0 0 12px; border: 2px solid var(--border-color, #e2e8f0); padding: 1rem;">
                                    <span class="input-group-text fw-semibold" style="border-radius: 0 12px 12px 0; background: var(--gradient-primary); color: white; border: 2px solid var(--primary); border-left: none; padding: 1rem 1.5rem;">.servo.tools</span>
                                </div>
                                <small class="text-muted">
                                    Votre sous-domaine (2-30 caractères, lettres, chiffres et tirets uniquement)
                                </small>
                            </div>

                            <!-- Informations -->
                            <div class="col-12 mt-5">
                                <h5 class="form-section-header fw-bold mb-4 pb-3">
                                    <i class="fa-solid fa-address-book me-2"></i>Informations
                                </h5>
                            </div>
                            
                            <div class="col-12">
                                <label for="email" class="form-label fw-semibold">
                                    Adresse email <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       placeholder="votre@email.com" required>
                                <small class="text-muted">
                                    Votre mot de passe par défaut sera : <strong>Admin123!</strong> (modifiable après connexion)
                                </small>
                            </div>
                            
                            <div class="col-12">
                                <label for="telephone" class="form-label fw-semibold">
                                    Numéro de téléphone <span class="text-danger">*</span>
                                </label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" 
                                       value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>" 
                                       placeholder="0123456789" required>
                            </div>
                            
                            <div class="col-12">
                                <label for="adresse" class="form-label fw-semibold">
                                    Adresse postale <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="adresse" name="adresse" rows="2" 
                                          placeholder="Numéro et nom de rue" required><?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="code_postal" class="form-label fw-semibold">
                                    Code postal <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="code_postal" name="code_postal" 
                                       value="<?php echo htmlspecialchars($_POST['code_postal'] ?? ''); ?>" 
                                       placeholder="75000" pattern="[0-9]{5}" required>
                            </div>
                            
                            <div class="col-md-8">
                                <label for="ville" class="form-label fw-semibold">
                                    Ville <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="ville" name="ville" 
                                       value="<?php echo htmlspecialchars($_POST['ville'] ?? ''); ?>" 
                                       placeholder="Paris" required>
                            </div>

                            <!-- Conditions générales -->
                            <div class="col-12 mt-5">
                                <h5 class="form-section-header fw-bold mb-4 pb-3">
                                    <i class="fa-solid fa-file-contract me-2"></i>Conditions générales
                                </h5>
                            </div>
                            
                            <div class="col-12">
                                <div class="checkbox-modern mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="cgu_acceptees" name="cgu_acceptees" required>
                                        <label class="form-check-label fw-semibold" for="cgu_acceptees">
                                            J'accepte les <a href="https://servo.tools/cgu" target="_blank" class="text-primary">Conditions Générales d'Utilisation</a> <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="checkbox-modern mb-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="cgv_acceptees" name="cgv_acceptees" required>
                                        <label class="form-check-label fw-semibold" for="cgv_acceptees">
                                            J'accepte les <a href="https://servo.tools/mentions-legales" target="_blank" class="text-primary">Conditions Générales de Vente</a> <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12 d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="fa-solid fa-rocket me-2"></i>Créer ma boutique SERVO
                                </button>
                                        </div>
                            <div class="col-12 text-center text-muted small">
                                <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-3">
                                    <span><i class="fa-solid fa-gift text-success me-1"></i>30 jours gratuits</span>
                                    <span><i class="fa-solid fa-credit-card text-success me-1"></i>Aucune CB requise</span>
                                    <span><i class="fa-solid fa-message text-success me-1"></i>SMS illimités inclus</span>
                                        </div>
                                        </div>
                        </form>
                                    </div>
                                </div>
                            </div>
                    </div>
    </section>

    <!-- Stats Section -->
    <section class="section-sm" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
        <div class="container">
            <div class="row g-4 text-center">
                <div class="col-lg-3 col-md-6 fade-in-up"><div class="h2 fw-black text-primary mb-1">2 min</div><div class="text-muted">Temps de prise en charge client</div></div>
                <div class="col-lg-3 col-md-6 fade-in-up"><div class="h2 fw-black text-success mb-1">-45%</div><div class="text-muted">Temps administratif économisé</div></div>
                <div class="col-lg-3 col-md-6 fade-in-up"><div class="h2 fw-black text-warning mb-1">+28%</div><div class="text-muted">Productivité atelier</div></div>
                <div class="col-lg-3 col-md-6 fade-in-up"><div class="h2 fw-black text-primary mb-1">99.9%</div><div class="text-muted">Disponibilité garantie</div></div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section text-white" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px);">
        <div class="container text-center">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="fw-black mb-4 fade-in-up">Prêt à transformer votre atelier ?</h2>
                    <p class="fs-5 mb-4 opacity-90 fade-in-up">Rejoignez des centaines d'ateliers qui ont déjà adopté SERVO.</p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mb-4 fade-in-up">
                        <a href="#" class="btn btn-light btn-lg" onclick="document.getElementById('prenom').focus(); return false;"><i class="fa-solid fa-rocket me-2"></i>Commencer l'essai gratuit</a>
                        <a href="/features" class="btn btn-outline-light btn-lg"><i class="fa-solid fa-play me-2"></i>Voir une démo</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Modal de chargement et succès -->
<div class="modal fade" id="creationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 bg-white">
            <!-- Phase de chargement -->
                                    <div id="loadingPhase" class="modal-body text-center p-5" style="display: block;">
                <div class="mb-4">
                    <h4 class="fw-bold text-primary mb-4">Création de votre boutique</h4>
                    
                    <!-- Animation SERVO SVG -->
                    <div class="loader-servo mx-auto mb-4">
                        <svg height="0" width="0" viewBox="0 0 100 100" class="absolute-servo">
                            <defs class="s-xJBuHA073rTt" xmlns="http://www.w3.org/2000/svg">
                                <linearGradient
                                    class="s-xJBuHA073rTt"
                                    gradientUnits="userSpaceOnUse"
                                    y2="2"
                                    x2="0"
                                    y1="62"
                                    x1="0"
                                    id="b"
                                >
                                    <stop class="s-xJBuHA073rTt" stop-color="#0369a1"></stop>
                                    <stop class="s-xJBuHA073rTt" stop-color="#67e8f9" offset="1.5"></stop>
                                </linearGradient>
                                <linearGradient
                                    class="s-xJBuHA073rTt"
                                    gradientUnits="userSpaceOnUse"
                                    y2="0"
                                    x2="0"
                                    y1="64"
                                    x1="0"
                                    id="c"
                                >
                                    <stop class="s-xJBuHA073rTt" stop-color="#0369a1"></stop>
                                    <stop class="s-xJBuHA073rTt" stop-color="#22d3ee" offset="1"></stop>
                                    <animateTransform
                                        repeatCount="indefinite"
                                        keySplines=".42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1;.42,0,.58,1"
                                        keyTimes="0; 0.125; 0.25; 0.375; 0.5; 0.625; 0.75; 0.875; 1"
                                        dur="8s"
                                        values="0 32 32;-270 32 32;-270 32 32;-540 32 32;-540 32 32;-810 32 32;-810 32 32;-1080 32 32;-1080 32 32"
                                        type="rotate"
                                        attributeName="gradientTransform"
                                    ></animateTransform>
                                </linearGradient>
                                <linearGradient
                                    class="s-xJBuHA073rTt"
                                    gradientUnits="userSpaceOnUse"
                                    y2="2"
                                    x2="0"
                                    y1="62"
                                    x1="0"
                                    id="d"
                                >
                                    <stop class="s-xJBuHA073rTt" stop-color="#38bdf8"></stop>
                                    <stop class="s-xJBuHA073rTt" stop-color="#075985" offset="1.5"></stop>
                                </linearGradient>
                            </defs>
                        </svg>
                        
                        <!-- Lettre S -->
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 100 100"
                            width="80"
                            height="80"
                            class="inline-block-servo"
                        >
                            <path
                                stroke-linejoin="round"
                                stroke-linecap="round"
                                stroke-width="8"
                                stroke="url(#b)"
                                d="M 80,20 L 30,20 C 20,20 20,30 20,35 C 20,45 30,50 40,50 L 70,50 C 80,50 80,60 80,65 C 80,75 70,80 60,80 L 20,80"
                                class="dash-servo"
                                id="S"
                                pathLength="360"
                            ></path>
                        </svg>
                        
                        <!-- Lettre E -->
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 100 100"
                            width="80"
                            height="80"
                            class="inline-block-servo"
                        >
                            <path
                                stroke-linejoin="round"
                                stroke-linecap="round"
                                stroke-width="8"
                                stroke="url(#b)"
                                d="M 20,20 L 80,20 L 80,27 L 27,27 L 27,50 L 70,50 L 70,57 L 25,57 L 25,80 L 80,80 L 80,87 L 20,87 Z"
                                class="dash-servo"
                                id="E"
                                pathLength="360"
                            ></path>
                        </svg>
                        
                        <!-- Lettre R -->
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 100 100"
                            width="80"
                            height="80"
                            class="inline-block-servo"
                        >
                            <path
                                stroke-linejoin="round"
                                stroke-linecap="round"
                                stroke-width="8"
                                stroke="url(#d)"
                                d="M 25,20 L 25,80 M 25,20 L 65,20 A 15,15 0 0 1 65,50 L 25,50 M 50,50 L 75,80"
                                class="dash-servo"
                                id="R"
                                pathLength="360"
                            ></path>
                        </svg>
                        
                        <!-- Lettre V -->
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 100 100"
                            width="80"
                            height="80"
                            class="inline-block-servo"
                        >
                            <path
                                stroke-linejoin="round"
                                stroke-linecap="round"
                                stroke-width="12"
                                stroke="url(#d)"
                                d="M 20,20 L 50,80 L 80,20"
                                class="dash-servo"
                                id="V"
                                pathLength="360"
                            ></path>
                        </svg>
                        
                        <!-- Lettre O -->
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 100 100"
                            width="80"
                            height="80"
                            class="inline-block-servo"
                        >
                            <path
                                stroke-linejoin="round"
                                stroke-linecap="round"
                                stroke-width="11"
                                stroke="url(#c)"
                                d="M 50,15 A 35,35 0 0 1 85,50 A 35,35 0 0 1 50,85 A 35,35 0 0 1 15,50 A 35,35 0 0 1 50,15 Z"
                                class="spin-servo"
                                id="O"
                                pathLength="360"
                            ></path>
                        </svg>
                </div>
                
                <p class="text-muted mb-4">Veuillez patienter pendant que nous configurons votre boutique...</p>
                
                <!-- Progress Bar Modern -->
                <div class="progress mb-2" style="height: 10px; border-radius: 10px; overflow: hidden; background-color: rgba(0,0,0,0.05);">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div id="progressText" class="text-muted small fw-semibold">Initialisation...</div>
                </div>
                
                <!-- Styles CSS pour l'animation SERVO -->
                <style>
                .absolute-servo {
                    position: absolute;
                }
                
                .inline-block-servo {
                    display: inline-block;
                }
                
                .loader-servo {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    margin: 0.25em 0;
                }
                
                .dash-servo {
                    animation: dashArray-servo 2s ease-in-out infinite, dashOffset-servo 2s linear infinite;
                }
                
                .spin-servo {
                    animation: spinDashArray-servo 2s ease-in-out infinite, spin-servo 8s ease-in-out infinite, dashOffset-servo 2s linear infinite;
                    transform-origin: center;
                }
                
                @keyframes dashArray-servo {
                    0% {
                        stroke-dasharray: 0 1 359 0;
                    }
                    50% {
                        stroke-dasharray: 0 359 1 0;
                    }
                    100% {
                        stroke-dasharray: 359 1 0 0;
                    }
                }
                
                @keyframes spinDashArray-servo {
                    0% {
                        stroke-dasharray: 270 90;
                    }
                    50% {
                        stroke-dasharray: 0 360;
                    }
                    100% {
                        stroke-dasharray: 250 90;
                    }
                }
                
                @keyframes dashOffset-servo {
                    0% {
                        stroke-dashoffset: 385;
                    }
                    100% {
                        stroke-dashoffset: 5;
                    }
                }
                
                @keyframes spin-servo {
                    0% {
                        rotate: 0deg;
                    }
                    12.5%, 25% {
                        rotate: 270deg;
                    }
                    37.5%, 50% {
                        rotate: 540deg;
                    }
                    62.5%, 75% {
                        rotate: 810deg;
                    }
                    87.5%, 100% {
                        rotate: 1080deg;
                    }
                }
                </style>
            </div>
            
            <!-- Phase de succès -->
            <div id="successPhase" class="modal-body text-center p-5" style="display: none;">
                <div class="mb-4">
                    <i class="fa-solid fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                
                <h4 class="fw-bold text-success mb-3">Félicitations !</h4>
                <p class="text-muted mb-4">Votre boutique SERVO a été créée avec succès !</p>
                
                <div class="card border-success border-2 mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Informations de connexion</h6>
                        
                        <div class="row g-3 text-start">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="fw-semibold">URL :</span>
                                    <span id="shopUrl" class="text-primary fw-semibold"></span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span class="fw-semibold">Nom d'utilisateur :</span>
                                    <span id="shopUsername" class="text-dark fw-semibold"></span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center py-2">
                                    <span class="fw-semibold">Mot de passe (Temporaire) :</span>
                                    <span class="text-danger fw-bold font-monospace bg-light px-2 py-1 rounded">Admin123!</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                    <button id="accessShopBtn" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-external-link-alt me-2"></i>Accéder à la boutique
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal">
                        <i class="fa-solid fa-home me-2"></i>Fermer
                    </button>
                </div>
            </div>
            
            <!-- Phase d'erreur -->
            <div id="errorPhase" class="modal-body text-center p-5" style="display: none;">
                <div class="mb-4">
                    <i class="fa-solid fa-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
                </div>
                
                <h4 class="fw-bold text-danger mb-3">Erreur</h4>
                <p class="text-muted mb-4">Une erreur s'est produite lors de la création de votre boutique.</p>
                
                <div id="errorMessages" class="alert alert-danger text-start mb-4">
                    <!-- Messages d'erreur dynamiques -->
                </div>
                
                <div class="d-flex justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-arrow-left me-2"></i>Retour
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Variables pour la progression sur 90 secondes
let progressSteps = [
    { percent: 10, text: "Initialisation du système..." },
    { percent: 20, text: "Validation des données..." },
    { percent: 35, text: "Création de la base de données..." },
    { percent: 50, text: "Configuration de l'infrastructure..." },
    { percent: 65, text: "Installation des composants..." },
    { percent: 80, text: "Configuration des permissions..." },
    { percent: 90, text: "Mise à jour du certificat SSL..." },
    { percent: 95, text: "Tests de connectivité..." },
    { percent: 100, text: "Finalisation..." }
];

let currentStep = 0;
let startTime = null;
const TOTAL_DURATION = 90000; // 90 secondes

// Fonction pour animer la progression de manière fluide sur 90 secondes
function animateProgress() {
    if (startTime === null) {
        startTime = Date.now();
    }
    
    const elapsed = Date.now() - startTime;
    const progress = Math.min((elapsed / TOTAL_DURATION) * 100, 100);
    
    // Mettre à jour la barre de progression
    document.getElementById('progressBar').style.width = progress + '%';
    document.getElementById('progressBar').setAttribute('aria-valuenow', Math.floor(progress));
    
    // Déterminer le texte basé sur le pourcentage
    let currentText = "Initialisation...";
    for (let i = 0; i < progressSteps.length; i++) {
        if (progress >= progressSteps[i].percent) {
            currentText = progressSteps[i].text;
        }
    }
    document.getElementById('progressText').textContent = currentText;
    
    // Continuer l'animation si pas terminé
    if (progress < 100) {
        requestAnimationFrame(animateProgress);
    }
}


// Validation du sous-domaine en temps réel
document.getElementById('subdomain').addEventListener('input', function() {
    let value = this.value.toLowerCase().trim();
    // Nettoyer automatiquement la saisie
    value = value.replace(/[^a-z0-9\-]/g, '');
    this.value = value;
    
    // Validation
    if (value.length >= 2 && value.length <= 30 && 
        /^[a-z0-9\-]*$/.test(value) && 
        !value.startsWith('-') && 
        !value.endsWith('-') &&
        !value.includes('--')) {
        this.style.borderColor = 'var(--success)';
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    } else {
        this.style.borderColor = 'var(--danger)';
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
    }
});

// Plus de validation de mot de passe nécessaire - mot de passe par défaut utilisé

// Gestion de la soumission du formulaire
document.getElementById('shopForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Réinitialiser les variables de progression
    currentStep = 0;
    startTime = null;
    
    // Afficher la modal de chargement
    const modal = new bootstrap.Modal(document.getElementById('creationModal'));
    modal.show();
    
    // Cacher les phases et afficher le chargement
    document.getElementById('loadingPhase').style.display = 'block';
    document.getElementById('successPhase').style.display = 'none';
    document.getElementById('errorPhase').style.display = 'none';
    
    // Préparer les données du formulaire
    const formData = new FormData(this);
    
    // Stocker les données pour utilisation après 30 secondes
    let formSubmissionData = null;
    
    // Faire la requête AJAX immédiatement mais ne pas traiter le résultat avant 30 secondes
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        formSubmissionData = data;
    })
    .catch(error => {
        console.error('Erreur:', error);
        formSubmissionData = { 
            success: false, 
            errors: ['Une erreur technique s\'est produite: ' + error.message + '. Veuillez réessayer.'] 
        };
    });
    
    // Commencer l'animation de progression
    setTimeout(() => {
        animateProgress();
    }, 500);
    
    // Attendre exactement 30 secondes puis afficher le résultat
    setTimeout(() => {
        if (formSubmissionData) {
            if (formSubmissionData.success) {
                // Succès - afficher les informations
                document.getElementById('loadingPhase').style.display = 'none';
                document.getElementById('successPhase').style.display = 'block';
                
                // Remplir les informations
                document.getElementById('shopUrl').textContent = formSubmissionData.data.url;
                document.getElementById('shopUsername').textContent = formSubmissionData.data.admin_username;
                
                // Configurer le bouton d'accès
                document.getElementById('accessShopBtn').onclick = function() {
                    window.open(formSubmissionData.data.url, '_blank');
                };
                
            } else {
                // Erreur - afficher les messages
                document.getElementById('loadingPhase').style.display = 'none';
                document.getElementById('errorPhase').style.display = 'block';
                
                // Afficher les erreurs
                const errorDiv = document.getElementById('errorMessages');
                errorDiv.innerHTML = '<ul class="mb-0">' + 
                    formSubmissionData.errors.map(error => '<li>' + error + '</li>').join('') + 
                    '</ul>';
            }
        } else {
            // Si aucune réponse après 30 secondes, afficher une erreur
            document.getElementById('loadingPhase').style.display = 'none';
            document.getElementById('errorPhase').style.display = 'block';
            
            document.getElementById('errorMessages').innerHTML = 
                '<p>Timeout - La création prend plus de temps que prévu. Veuillez réessayer.</p>';
        }
    }, TOTAL_DURATION);
});

// Animation d'entrée pour les sections et effets modernes
document.addEventListener('DOMContentLoaded', function() {
    // Animation d'entrée pour les cartes
    const cards = document.querySelectorAll('.card-modern, .form-container, .features-banner');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });

    // Animation séquentielle pour les éléments du formulaire
    const formElements = document.querySelectorAll('.stagger-animation > div');
    formElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            element.style.transition = 'all 0.6s ease-out';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, 200 + (100 * index));
    });

    // Effet de brillance sur les inputs au focus
    const inputs = document.querySelectorAll('.form-control, .form-check-input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.boxShadow = '0 0 20px rgba(99, 102, 241, 0.2)';
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.boxShadow = '';
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // Animation pour les checkboxes
    const checkboxes = document.querySelectorAll('.checkbox-modern');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.02)';
        });
        
        checkbox.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Effet de parallaxe léger sur le hero
    const hero = document.querySelector('.inscription-hero');
    if (hero) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallax = scrolled * 0.3;
            hero.style.transform = `translateY(${parallax}px)`;
        });
    }

    // Animation d'ondulation sur les boutons
    const buttons = document.querySelectorAll('.btn-submit-modern, .btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const ripple = document.createElement('div');
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255,255,255,0.4);
                transform: scale(0);
                animation: rippleEffect 0.6s linear;
                pointer-events: none;
                z-index: 1000;
            `;
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = e.clientX - rect.left - size / 2 + 'px';
            ripple.style.top = e.clientY - rect.top - size / 2 + 'px';
            
            this.style.position = 'relative';
            this.appendChild(ripple);
            
            setTimeout(() => {
                if (ripple.parentNode) {
                    ripple.parentNode.removeChild(ripple);
                }
            }, 600);
        });
    });

    // Intersection Observer pour les animations au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                
                // Animation spéciale pour la bannière des fonctionnalités
                if (entry.target.classList.contains('features-banner')) {
                    const benefits = entry.target.querySelectorAll('.benefit-item');
                    benefits.forEach((benefit, index) => {
                        setTimeout(() => {
                            benefit.style.opacity = '1';
                            benefit.style.transform = 'translateX(0)';
                        }, index * 100);
                    });
                }
            }
        });
    }, observerOptions);

    // Observer tous les éléments animés
    const animatedElements = document.querySelectorAll('.fade-in-up-slow, .features-banner, .benefit-item');
    animatedElements.forEach(el => {
        if (el.classList.contains('benefit-item')) {
            el.style.opacity = '0';
            el.style.transform = 'translateX(-20px)';
            el.style.transition = 'all 0.6s ease';
        }
        observer.observe(el);
    });
});

// Ajouter les keyframes CSS pour l'effet ripple
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
    @keyframes rippleEffect {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(rippleStyle);
</script>

<?php
// Inclure le footer marketing - vérifier l'existence du fichier
$footer_path = __DIR__ . '/public_html/marketing/shared/footer.php';
if (file_exists($footer_path)) {
    include_once($footer_path);
} else {
    // Fallback : créer un footer minimal
    ?>
    <!-- Footer -->
    <footer class="border-top bg-white">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="mb-4">
                        <a href="https://servo.tools" class="navbar-brand fs-4">
                            <strong>SERVO</strong>
                        </a>
                    </div>
                    <p class="text-muted mb-3">
                        Révolutionnez votre atelier avec SERVO. La solution tout-en-un qui digitalise votre activité : SMS automatiques, gestion intelligente du stock, suivi clients en temps réel et pointage employés simplifié.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="/inscription" class="btn btn-primary">
                            <i class="fa-solid fa-rocket me-2"></i>Essai gratuit 30 jours
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <h6 class="fw-bold mb-3">Contact</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2 d-flex align-items-center">
                            <i class="fa-solid fa-envelope text-primary me-3"></i>
                            <a href="mailto:servo@maisondugeek.fr" class="text-decoration-none text-muted">servo@maisondugeek.fr</a>
                        </li>
                        <li class="mb-2 d-flex align-items-center">
                            <i class="fa-solid fa-phone text-primary me-3"></i>
                            <span class="text-muted">08 95 79 59 33</span>
                        </li>
                        <li class="mb-2 d-flex align-items-center">
                            <i class="fa-solid fa-map-marker-alt text-primary me-3"></i>
                            <span class="text-muted">78 bd maison du geek, 06110 le cannet</span>
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-4">
                    <div class="text-center text-lg-end">
                        <p class="text-muted small mb-0">
                            &copy; <?php echo date('Y'); ?> SERVO. Tous droits réservés.
                        </p>
                        <div class="mt-3">
                            <a href="https://servo.tools/privacy" class="text-decoration-none text-muted small me-3">Confidentialité</a>
                            <a href="https://servo.tools/cgu" class="text-decoration-none text-muted small me-3">CGU</a>
                            <a href="https://servo.tools/mentions-legales" class="text-decoration-none text-muted small">Mentions légales</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
?>
