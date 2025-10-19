<?php
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
 * Fonction pour mettre à jour le mapping des sous-domaines dans login_auto.php
 */
function updateSubdomainMapping($subdomain, $shop_id, $shop_name, $db_name) {
    $login_auto_path = __DIR__ . '/pages/login_auto.php';
    
    // Debug: log du chemin utilisé
    error_log("INSCRIPTION: Chemin login_auto utilisé: " . $login_auto_path);
    error_log("INSCRIPTION: __DIR__ = " . __DIR__);
    error_log("INSCRIPTION: Fichier existe? " . (file_exists($login_auto_path) ? 'OUI' : 'NON'));
    
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
        // Déterminer le chemin du certificat actuel
        $cert_path = "/etc/letsencrypt/live/mdgeek.top-0001/fullchain.pem";
        if (!file_exists($cert_path)) {
            $cert_path = "/etc/letsencrypt/live/mdgeek.top/fullchain.pem";
        }
        
        // Récupérer les domaines actuels du certificat
        $cert_info = shell_exec("openssl x509 -in $cert_path -text -noout | grep DNS:");
        if (!$cert_info) {
            error_log("Erreur : Impossible de lire le certificat SSL existant dans $cert_path");
            return false;
        }
        
        // Extraire les domaines existants
        preg_match_all('/DNS:([^,\s]+)/', $cert_info, $matches);
        $existing_domains = $matches[1];
        
        // Ajouter le nouveau sous-domaine s'il n'existe pas déjà
        $new_domain = $subdomain . '.mdgeek.top';
        if (!in_array($new_domain, $existing_domains)) {
            $existing_domains[] = $new_domain;
        } else {
            error_log("Domaine $new_domain déjà présent dans le certificat SSL");
            return true;
        }
        
        // Créer un script temporaire pour exécuter certbot avec les bonnes permissions
        $temp_script = "/tmp/update_ssl_" . uniqid() . ".sh";
        
        // Construire la commande certbot avec tous les domaines
        $domain_args = '';
        foreach ($existing_domains as $domain) {
            $domain_args .= '-d ' . escapeshellarg($domain) . ' ';
        }
        
        // Créer le contenu du script
        $script_content = "#!/bin/bash\n";
        $script_content .= "# Script temporaire pour mise à jour SSL\n";
        $script_content .= "# Exécuter en tant que root pour éviter les problèmes de permissions\n";
        $script_content .= "/usr/bin/certbot certonly --nginx {$domain_args} --expand --non-interactive --agree-tos -m admin@mdgeek.top 2>&1\n";
        $script_content .= "CERTBOT_RESULT=\$?\n";
        $script_content .= "if [ \$CERTBOT_RESULT -eq 0 ]; then\n";
        $script_content .= "    /usr/bin/systemctl reload nginx 2>&1\n";
        $script_content .= "    echo \"SSL_SUCCESS: Certificat SSL mis à jour avec succès pour {$new_domain}\"\n";
        $script_content .= "else\n";
        $script_content .= "    echo \"SSL_ERROR: Erreur lors de la mise à jour du certificat SSL\"\n";
        $script_content .= "fi\n";
        $script_content .= "exit \$CERTBOT_RESULT\n";
        
        // Écrire le script temporaire
        if (file_put_contents($temp_script, $script_content) === false) {
            error_log("Erreur : Impossible de créer le script temporaire SSL");
            return false;
        }
        
        // Rendre le script exécutable
        chmod($temp_script, 0755);
        
        // Exécuter le script avec sudo
        $cmd = "sudo bash " . escapeshellarg($temp_script) . " 2>&1";
        error_log("Exécution de la commande SSL : " . $cmd);
        $output = shell_exec($cmd);
        
        // Nettoyer le script temporaire
        unlink($temp_script);
        
        // Vérifier le résultat
        if (strpos($output, 'SSL_SUCCESS:') !== false || 
            strpos($output, 'Successfully received certificate') !== false || 
            strpos($output, 'Certificate not yet due for renewal') !== false) {
            error_log("Certificat SSL mis à jour avec succès pour : " . $new_domain . " - Output: " . substr($output, 0, 200));
            return true;
        } else {
            error_log("Erreur lors de la mise à jour du certificat SSL : " . $output);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour du certificat SSL : " . $e->getMessage());
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
        
        // Charger et exécuter le script SQL complet
        $sql_file = __DIR__ . '/superadmin/geekboard_complete_structure.sql';
        if (!file_exists($sql_file)) {
            throw new Exception("Fichier de structure SQL introuvable");
        }
        
        $sql_content = file_get_contents($sql_file);
        if ($sql_content === false) {
            throw new Exception("Impossible de lire le fichier SQL");
        }
        
        // Nettoyer et diviser les requêtes SQL
        $sql_content = preg_replace('/^--.*$/m', '', $sql_content);
        $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
        $all_queries = array_filter(
            array_map('trim', explode(';', $sql_content)),
            function($query) { 
                return !empty($query) && (
                    strtoupper(substr($query, 0, 6)) === 'CREATE' || 
                    strtoupper(substr($query, 0, 5)) === 'ALTER' ||
                    strtoupper(substr($query, 0, 6)) === 'INSERT'
                );
            }
        );
        
        // Tables essentielles dont on veut copier les données
        $essential_tables = [
            'statuts', 'statut_categories', 'sms_templates', 'sms_template_variables',
            'notification_types', 'parametres', 'parametres_gardiennage', 'parrainage_config',
            'kb_categories', 'kb_tags', 'fournisseurs', 'marges_reference'
        ];
        
        // Séparer les requêtes CREATE, ALTER et INSERT
        $create_queries = [];
        $alter_queries = [];
        $insert_queries = [];
        
        foreach ($all_queries as $query) {
            if (strtoupper(substr($query, 0, 6)) === 'CREATE') {
                $create_queries[] = $query;
            } elseif (strtoupper(substr($query, 0, 5)) === 'ALTER') {
                $alter_queries[] = $query;
            } elseif (strtoupper(substr($query, 0, 6)) === 'INSERT') {
                foreach ($essential_tables as $table) {
                    if (preg_match('/INSERT INTO `?' . preg_quote($table, '/') . '`?\s/i', $query)) {
                        $insert_queries[] = $query;
                        break;
                    }
                }
            }
        }
        
        // Désactiver la vérification des clés étrangères temporairement
        $shop_pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Exécuter d'abord les requêtes CREATE TABLE
        foreach ($create_queries as $sql_query) {
            try {
                $shop_pdo->exec($sql_query);
            } catch (PDOException $e) {
                error_log("Erreur CREATE: " . $e->getMessage() . " - Requête: " . substr($sql_query, 0, 100));
            }
        }
        
        // Puis exécuter les requêtes ALTER TABLE
        foreach ($alter_queries as $sql_query) {
            try {
                $shop_pdo->exec($sql_query);
            } catch (PDOException $e) {
                error_log("Erreur ALTER: " . $e->getMessage() . " - Requête: " . substr($sql_query, 0, 100));
            }
        }
        
        // Enfin, insérer les données essentielles
        foreach ($insert_queries as $sql_query) {
            try {
                $shop_pdo->exec($sql_query);
            } catch (PDOException $e) {
                error_log("Erreur INSERT: " . $e->getMessage() . " - Requête: " . substr($sql_query, 0, 100));
            }
        }
        
        // Réactiver la vérification des clés étrangères
        $shop_pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Créer l'utilisateur admin avec l'email du propriétaire
        $admin_username = $shop_owner_data['email'];
        $admin_password = 'Admin123!';
        $password_md5 = md5($admin_password);
        $admin_full_name = $shop_owner_data['prenom'] . ' ' . $shop_owner_data['nom'];
        
        $shop_pdo->exec("INSERT INTO users (username, password, full_name, role, created_at) VALUES ('$admin_username', '$password_md5', '$admin_full_name', 'admin', NOW())");
        
        // Mise à jour du mapping des sous-domaines avec logging détaillé
        error_log("INSCRIPTION: Début mise à jour mapping pour $subdomain (ID: $shop_id)");
        $mapping_updated = updateSubdomainMapping($subdomain, $shop_id, $shop_name, $db_name);
        error_log("INSCRIPTION: Résultat mapping pour $subdomain: " . ($mapping_updated ? 'SUCCÈS' : 'ÉCHEC'));
        
        // Mettre à jour le certificat SSL pour inclure le nouveau sous-domaine
        $ssl_updated = updateSSLCertificate($subdomain);
        
        // Initialiser la période d'essai gratuit de 30 jours
        require_once(__DIR__ . '/classes/SubscriptionManager.php');
        $subscriptionManager = new SubscriptionManager($pdo);
        $trial_initialized = $subscriptionManager->initializeTrialPeriod($shop_id);
        
        return [
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
            'subdomain' => $subdomain,
            'url' => 'https://' . $subdomain . '.mdgeek.top',
            'db_name' => $db_name,
            'admin_username' => $admin_username,
            'admin_password' => $admin_password,
            'mapping_updated' => $mapping_updated,
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
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $cgu_acceptees = isset($_POST['cgu_acceptees']) ? 1 : 0;
    $cgv_acceptees = isset($_POST['cgv_acceptees']) ? 1 : 0;
    
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
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    }
    
    if ($password !== $password_confirm) {
        $errors[] = 'La confirmation du mot de passe ne correspond pas.';
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
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        // Si pas d'erreurs, créer l'inscription et le magasin
        try {
            $pdo->beginTransaction();
            
            // Insérer les données du propriétaire
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
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
            $pdo->rollBack();
            echo json_encode(['success' => false, 'errors' => ['Erreur lors de la création: ' . $e->getMessage()]]);
            exit;
        }
    }
    
    // Si pas d'erreurs et pas AJAX, créer l'inscription et le magasin (mode normal)
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insérer les données du propriétaire
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
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

// Inclure le header marketing
include_once('marketing/shared/header.php');
?>

<?php if ($success_data): ?>
    <!-- Success Section -->
    <section class="section bg-gradient-primary text-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-5">
                        <div class="display-1 mb-4">
                            <i class="fa-solid fa-check-circle"></i>
                        </div>
                        <h1 class="fw-black mb-4">Félicitations !</h1>
                        <p class="fs-5 opacity-90 mb-4">
                            Votre boutique <strong><?php echo htmlspecialchars($success_data['shop_name']); ?></strong> a été créée avec succès !
                        </p>
                    </div>

                    <?php if ($success_data['mapping_updated']): ?>
                        <div class="card-modern bg-white bg-opacity-15 border-0 p-4 mb-4">
                            <div class="d-flex align-items-center text-white">
                                <i class="fa-solid fa-check-circle text-success me-3 fs-4"></i>
                                <div>
                                    <strong>Mapping automatique mis à jour !</strong><br>
                                    <small class="opacity-75">Le sous-domaine <?php echo htmlspecialchars($success_data['subdomain']); ?> a été ajouté au système de connexion automatique.</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_data['ssl_updated']): ?>
                        <div class="card-modern bg-white bg-opacity-15 border-0 p-4 mb-5">
                            <div class="d-flex align-items-center text-white">
                                <i class="fa-solid fa-lock text-success me-3 fs-4"></i>
                                <div>
                                    <strong>Certificat SSL mis à jour !</strong><br>
                                    <small class="opacity-75">Le sous-domaine <?php echo htmlspecialchars($success_data['subdomain']); ?>.mdgeek.top a été ajouté au certificat SSL. Connexion HTTPS sécurisée disponible immédiatement.</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card-modern bg-white text-dark p-5 mb-5">
                        <h4 class="fw-bold mb-4"><i class="fa-solid fa-store text-primary me-2"></i>Informations de votre boutique</h4>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                    <span class="text-muted">Propriétaire</span>
                                    <span class="fw-semibold"><?php echo htmlspecialchars($success_data['owner_prenom'] . ' ' . $success_data['owner_nom']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                    <span class="text-muted">Nom de la boutique</span>
                                    <span class="fw-semibold"><?php echo htmlspecialchars($success_data['shop_name']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                    <span class="text-muted">URL de votre boutique</span>
                                    <span class="fw-semibold text-primary"><?php echo htmlspecialchars($success_data['url']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                    <span class="text-muted">Nom d'utilisateur</span>
                                    <span class="fw-semibold"><?php echo htmlspecialchars($success_data['admin_username']); ?></span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center py-2">
                                    <span class="text-muted">Mot de passe temporaire</span>
                                    <span class="fw-semibold font-monospace bg-light px-3 py-1 rounded"><?php echo htmlspecialchars($success_data['admin_password']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-modern bg-warning bg-opacity-15 border-warning border-2 p-4 mb-5">
                        <div class="d-flex align-items-start">
                            <i class="fa-solid fa-info-circle text-warning me-3 fs-4 mt-1"></i>
                            <div class="text-white">
                                <strong>Important :</strong> Notez bien vos identifiants de connexion et changez votre mot de passe lors de votre première connexion.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        <a href="<?php echo $success_data['url']; ?>" target="_blank" class="btn btn-light btn-lg">
                            <i class="fa-solid fa-external-link-alt me-2"></i>Accéder à ma boutique
                        </a>
                        <a href="/" class="btn btn-outline-light btn-lg">
                            <i class="fa-solid fa-home me-2"></i>Retour à l'accueil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php else: ?>
    <!-- Hero Section with form -->
    <section class="section bg-gradient-hero text-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-5">
                        <div class="badge bg-dark text-white mb-4 px-4 py-2">
                            <i class="fa-solid fa-rocket me-2"></i>
                            Commencez votre transformation digitale
                        </div>
                        
                        <h1 class="display-3 fw-black mb-4">
                            L'intelligence qui fait<br>tourner votre boîte 
                            <span class="position-relative">
                                SERVO
                                <svg class="position-absolute" style="bottom: -10px; left: 0; width: 100%; height: 20px;" viewBox="0 0 300 20" fill="none">
                                    <path d="M5 15 Q150 5 295 15" stroke="currentColor" stroke-width="3" fill="none" opacity="0.7"/>
                                </svg>
                            </span>
                        </h1>
                        
                        <p class="fs-5 mb-5 opacity-90">
                            <strong>30 jours d'essai gratuit complet</strong> - Toutes les fonctionnalités, SMS illimités, sans carte bancaire. 
                            Configuration automatique, données sécurisées, support français inclus.
                        </p>
                        
                        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3 gap-md-4 text-white-50 justify-content-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-check-circle"></i>
                                <small>Installation en 2 minutes</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-shield-halved"></i>
                                <small>Données sécurisées</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-headset"></i>
                                <small>Support français</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Form Section -->
    <section class="section bg-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <?php if (!empty($errors)): ?>
                        <div class="card-modern border-danger p-4 mb-5">
                            <div class="d-flex align-items-start">
                                <i class="fa-solid fa-exclamation-triangle text-danger me-3 fs-4"></i>
                                <div>
                                    <h6 class="fw-bold text-danger mb-3">Erreurs détectées</h6>
                                    <ul class="mb-0 text-danger">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card-modern p-5">
                        <form id="shopForm" method="post" class="row g-4">
                            <!-- Informations personnelles -->
                            <div class="col-12">
                                <h5 class="fw-bold text-primary mb-4 pb-3 border-bottom">
                                    <i class="fa-solid fa-user me-2"></i>Informations personnelles
                                </h5>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="prenom" class="form-label fw-semibold">
                                    Prénom <span class="text-danger">*</span>
                                </label>
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
                                           pattern="[a-z0-9\-]{2,30}" required>
                                    <span class="input-group-text fw-semibold">.mdgeek.top</span>
                                </div>
                                <small class="text-muted">
                                    Votre sous-domaine (2-30 caractères, lettres, chiffres et tirets uniquement)
                                </small>
                            </div>

                            <!-- Informations de connexion -->
                            <div class="col-12 mt-5">
                                <h5 class="fw-bold text-primary mb-4 pb-3 border-bottom">
                                    <i class="fa-solid fa-key me-2"></i>Informations de connexion
                                </h5>
                            </div>
                            
                            <div class="col-12">
                                <label for="email" class="form-label fw-semibold">
                                    Adresse email <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       placeholder="votre@email.com" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold">
                                    Mot de passe <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Minimum 6 caractères" minlength="6" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="password_confirm" class="form-label fw-semibold">
                                    Confirmer le mot de passe <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                       placeholder="Confirmez votre mot de passe" required>
                            </div>

                            <!-- Informations de contact -->
                            <div class="col-12 mt-5">
                                <h5 class="fw-bold text-primary mb-4 pb-3 border-bottom">
                                    <i class="fa-solid fa-address-book me-2"></i>Informations de contact
                                </h5>
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
                                <h5 class="fw-bold text-primary mb-4 pb-3 border-bottom">
                                    <i class="fa-solid fa-file-contract me-2"></i>Conditions générales
                                </h5>
                            </div>
                            
                            <div class="col-12">
                                <div class="card-modern bg-gray-50 border-0 p-4 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="cgu_acceptees" name="cgu_acceptees" required>
                                        <label class="form-check-label fw-semibold" for="cgu_acceptees">
                                            J'accepte les <a href="#" target="_blank" class="text-primary">Conditions Générales d'Utilisation</a> <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="card-modern bg-gray-50 border-0 p-4 mb-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="cgv_acceptees" name="cgv_acceptees" required>
                                        <label class="form-check-label fw-semibold" for="cgv_acceptees">
                                            J'accepte les <a href="#" target="_blank" class="text-primary">Conditions Générales de Vente</a> <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary btn-lg px-5" id="submitBtn">
                                    <i class="fa-solid fa-rocket me-2"></i>Créer ma boutique SERVO
                                </button>
                                
                                <div class="mt-4">
                                    <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-3 gap-md-4 text-muted">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-gift text-success"></i>
                                            <small>30 jours gratuits complets</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-credit-card text-success"></i>
                                            <small>Aucune CB requise</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-message text-success"></i>
                                            <small>SMS illimités inclus</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
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
            <div id="loadingPhase" class="modal-body text-center p-5">
                <div class="mb-4">
                    <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
                
                <h4 class="fw-bold text-primary mb-3">Création de votre boutique SERVO</h4>
                <p class="text-muted mb-4">Veuillez patienter pendant que nous configurons votre boutique...</p>
                
                <!-- Barre de progression -->
                <div class="progress mb-4" style="height: 12px;">
                    <div id="progressBar" class="progress-bar bg-gradient-primary progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
                
                <div id="progressText" class="small text-muted">
                    Initialisation...
                </div>
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
// Variables pour la progression
let progressSteps = [
    { percent: 20, text: "Validation des données..." },
    { percent: 40, text: "Création de la base de données..." },
    { percent: 60, text: "Configuration des permissions..." },
    { percent: 80, text: "Mise à jour du certificat SSL..." },
    { percent: 95, text: "Finalisation..." },
    { percent: 100, text: "Terminé !" }
];

let currentStep = 0;

// Fonction pour animer la progression
function animateProgress() {
    if (currentStep < progressSteps.length) {
        const step = progressSteps[currentStep];
        
        // Animer la barre de progression
        document.getElementById('progressBar').style.width = step.percent + '%';
        document.getElementById('progressBar').setAttribute('aria-valuenow', step.percent);
        document.getElementById('progressText').textContent = step.text;
        
        currentStep++;
        
        // Délai variable pour chaque étape
        const delay = currentStep === 1 ? 500 : (currentStep === progressSteps.length ? 1000 : 1500);
        
        setTimeout(animateProgress, delay);
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

// Validation de la confirmation du mot de passe
document.getElementById('password_confirm').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    if (this.value === password && password.length >= 6) {
        this.style.borderColor = 'var(--success)';
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    } else {
        this.style.borderColor = 'var(--danger)';
        this.classList.remove('is-valid');
        this.classList.add('is-invalid');
    }
});

// Gestion de la soumission du formulaire
document.getElementById('shopForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Réinitialiser les variables de progression
    currentStep = 0;
    
    // Afficher la modal de chargement
    const modal = new bootstrap.Modal(document.getElementById('creationModal'));
    modal.show();
    
    // Cacher les phases et afficher le chargement
    document.getElementById('loadingPhase').style.display = 'block';
    document.getElementById('successPhase').style.display = 'none';
    document.getElementById('errorPhase').style.display = 'none';
    
    // Commencer l'animation de progression
    setTimeout(() => {
        animateProgress();
    }, 500);
    
    // Préparer les données du formulaire
    const formData = new FormData(this);
    
    // Faire la requête AJAX
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Attendre que la progression soit terminée
        setTimeout(() => {
            if (data.success) {
                // Succès - afficher les informations
                document.getElementById('loadingPhase').style.display = 'none';
                document.getElementById('successPhase').style.display = 'block';
                
                // Remplir les informations
                document.getElementById('shopUrl').textContent = data.data.url;
                document.getElementById('shopUsername').textContent = data.data.admin_username;
                
                // Configurer le bouton d'accès
                document.getElementById('accessShopBtn').onclick = function() {
                    window.open(data.data.url, '_blank');
                };
                
            } else {
                // Erreur - afficher les messages
                document.getElementById('loadingPhase').style.display = 'none';
                document.getElementById('errorPhase').style.display = 'block';
                
                // Afficher les erreurs
                const errorDiv = document.getElementById('errorMessages');
                errorDiv.innerHTML = '<ul class="mb-0">' + 
                    data.errors.map(error => '<li>' + error + '</li>').join('') + 
                    '</ul>';
            }
        }, Math.max(0, 3000 - (Date.now() - startTime))); // S'assurer qu'on attend au moins 3 secondes
    })
    .catch(error => {
        console.error('Erreur:', error);
        
        // Afficher l'erreur
        setTimeout(() => {
            document.getElementById('loadingPhase').style.display = 'none';
            document.getElementById('errorPhase').style.display = 'block';
            
            document.getElementById('errorMessages').innerHTML = 
                '<p>Une erreur technique s\'est produite. Veuillez réessayer.</p>';
        }, Math.max(0, 3000 - (Date.now() - startTime)));
    });
    
    // Enregistrer le temps de début pour la progression minimale
    const startTime = Date.now();
});

// Animation d'entrée pour les sections
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card-modern');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });
});
</script>

<?php
// Inclure le footer marketing
include_once('marketing/shared/footer.php');
?>
