<?php
// Page d'inscription publique pour créer un magasin
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
        
        // Fallback: reconstruction complète - Méthode robuste : trouver manuellement la section
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
        $new_domain = $subdomain . '.servo.tools';
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
        
        // Synchroniser les mappings statiques après création complète
        require_once(__DIR__ . '/config/subdomain_database_detector.php');
        $static_sync_result = syncSubdomainMappings();
        error_log("INSCRIPTION: Synchronisation finale mappings statiques: " . ($static_sync_result ? 'SUCCÈS' : 'ÉCHEC'));
        
        // Mettre à jour le certificat SSL pour inclure le nouveau sous-domaine
        $ssl_updated = updateSSLCertificate($subdomain);
        
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
            'ssl_updated' => $ssl_updated
        ];
        
    } catch (Exception $e) {
        throw new Exception('Erreur lors de la création du magasin: ' . $e->getMessage());
    }
}

// Traitement du formulaire
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
    
    // Si pas d'erreurs, créer l'inscription et le magasin
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
?>
<?php include_once(__DIR__ . '/marketing/shared/header.php'); ?>

<!-- Hero Section alignée à la charte -->
<section class="section bg-gradient-primary text-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <div class="badge bg-dark text-white mb-4 px-4 py-2">
                        <i class="fa-solid fa-rocket me-2"></i>
                        Commencez votre transformation digitale
    </div>
                    <h1 class="display-3 fw-black mb-4">L'intelligence qui fait<br>tourner votre boîte <span class="text-primary">SERVO</span></h1>
                    <p class="fs-5 mb-0 opacity-90">30 jours d'essai gratuit complet – Sans carte bancaire</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Formulaire -->
<section class="section bg-white">
        <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
            <?php if ($success_data): ?>
                <div class="card-modern bg-gradient-primary text-white p-5">
                    <h2><i class="fas fa-check-circle"></i>Félicitations !</h2>
                    <p style="font-size: 1.2rem; margin-bottom: 25px;">
                        Votre boutique <strong><?php echo htmlspecialchars($success_data['shop_name']); ?></strong> a été créée avec succès !
                    </p>
                    
                    <?php if ($success_data['mapping_updated']): ?>
                        <div style="background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.3); border-radius: 10px; padding: 15px; margin-bottom: 15px;">
                            <i class="fas fa-check-circle" style="color: #90EE90; margin-right: 10px;"></i>
                            <strong>Mapping automatique mis à jour !</strong><br>
                            <small>Le sous-domaine <code><?php echo htmlspecialchars($success_data['subdomain']); ?></code> a été ajouté au système de connexion automatique.</small>
                        </div>
                    <?php else: ?>
                        <div style="background: rgba(255,255,255,0.15); border: 2px solid rgba(255,165,0,0.5); border-radius: 10px; padding: 15px; margin-bottom: 15px;">
                            <i class="fas fa-exclamation-triangle" style="color: #FFD700; margin-right: 10px;"></i>
                            <strong>Attention :</strong> Le mapping automatique n'a pas pu être mis à jour.<br>
                            <small>Vous devrez ajouter manuellement le sous-domaine au fichier login_auto.php</small>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_data['ssl_updated']): ?>
                        <div style="background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.3); border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                            <i class="fas fa-lock" style="color: #90EE90; margin-right: 10px;"></i>
                            <strong>Certificat SSL mis à jour !</strong><br>
                            <small>Le sous-domaine <code><?php echo htmlspecialchars($success_data['subdomain']); ?>.mdgeek.top</code> a été ajouté au certificat SSL. Connexion HTTPS sécurisée disponible immédiatement.</small>
                        </div>
                    <?php else: ?>
                        <div style="background: rgba(255,255,255,0.15); border: 2px solid rgba(255,165,0,0.5); border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                            <i class="fas fa-exclamation-triangle" style="color: #FFD700; margin-right: 10px;"></i>
                            <strong>Attention :</strong> Le certificat SSL n'a pas pu être mis à jour automatiquement.<br>
                            <small>Vous devrez peut-être ajouter manuellement le sous-domaine au certificat SSL pour éviter les erreurs HTTPS.</small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-modern bg-white bg-opacity-15 border-0 p-4 info-card">
                        <h4 style="margin-bottom: 20px;"><i class="fas fa-store me-2"></i>Informations de votre boutique</h4>
                        <div class="info-item">
                            <span class="info-label">Propriétaire :</span>
                            <span class="info-value"><?php echo htmlspecialchars($success_data['owner_prenom'] . ' ' . $success_data['owner_nom']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nom de la boutique :</span>
                            <span class="info-value"><?php echo htmlspecialchars($success_data['shop_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">URL de votre boutique :</span>
                            <span class="info-value"><?php echo htmlspecialchars($success_data['url']); ?></span>
                            </div>
                        <div class="info-item">
                            <span class="info-label">Nom d'utilisateur :</span>
                            <span class="info-value"><?php echo htmlspecialchars($success_data['admin_username']); ?></span>
                                    </div>
                        <div class="info-item">
                            <span class="info-label">Mot de passe temporaire :</span>
                            <span class="info-value"><?php echo htmlspecialchars($success_data['admin_password']); ?></span>
                                    </div>
                                </div>
                                
                    <div style="background: rgba(255,255,255,0.2); border-radius: 10px; padding: 20px; margin: 25px 0;">
                        <i class="fas fa-info-circle" style="margin-right: 10px;"></i>
                        <strong>Important :</strong> Notez bien vos identifiants de connexion et changez votre mot de passe lors de votre première connexion.
                                </div>
                                
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center action-buttons">
                        <a href="<?php echo $success_data['url']; ?>" target="_blank" class="btn-action">
                            <i class="fas fa-external-link-alt"></i>Accéder à ma boutique
                        </a>
                        <a href="/" class="btn-action">
                            <i class="fas fa-home"></i>Retour à l'accueil
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card-modern p-5">
                    <h3 class="fw-bold text-primary mb-4"><i class="fas fa-store me-2"></i>L'intelligence qui fait<br>tourner votre boîte</h3>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Erreurs détectées</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <!-- Informations personnelles -->
                        <h5 class="fw-bold text-primary mb-4 pb-3 border-bottom">
                            <i class="fas fa-user"></i> Informations personnelles
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="prenom" class="form-label">
                                        <i class="fas fa-user"></i>Prénom <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" 
                                           value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" 
                                           placeholder="Votre prénom" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nom" class="form-label">
                                        <i class="fas fa-user"></i>Nom <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nom" name="nom" 
                                           value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" 
                                           placeholder="Votre nom de famille" required>
                                </div>
                                    </div>
                                </div>
                                
                        <div class="form-group">
                            <label for="nom_commercial" class="form-label">
                                <i class="fas fa-store"></i>Nom commercial (facultatif)
                            </label>
                            <input type="text" class="form-control" id="nom_commercial" name="nom_commercial" 
                                   value="<?php echo htmlspecialchars($_POST['nom_commercial'] ?? ''); ?>" 
                                   placeholder="Nom de votre entreprise/boutique">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="subdomain" class="form-label">
                                <i class="fas fa-link"></i>Sous-domaine <span class="required">*</span>
                                        </label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="subdomain" name="subdomain" 
                                       value="<?php echo htmlspecialchars($_POST['subdomain'] ?? ''); ?>" 
                                       placeholder="monmagasin" 
                                       pattern="[a-z0-9\-]{2,30}" required>
                                <span class="input-group-text">.servo.tools</span>
                                    </div>
                            <small class="form-text text-muted">
                                Votre sous-domaine (2-30 caractères, lettres, chiffres et tirets uniquement)
                            </small>
                                </div>
                                
                        <!-- Informations de connexion -->
                        <h5 class="fw-bold text-primary mb-4 mt-5 pb-3 border-bottom">
                            <i class="fas fa-key"></i> Informations de connexion
                        </h5>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i>Adresse email <span class="required">*</span>
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="votre@email.com" required>
                        </div>
                        
                        <div class="row">
                                <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock"></i>Mot de passe <span class="required">*</span>
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Minimum 6 caractères" minlength="6" required>
                                </div>
                                </div>
                                <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password_confirm" class="form-label">
                                        <i class="fas fa-lock"></i>Confirmer le mot de passe <span class="required">*</span>
                                    </label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                           placeholder="Confirmez votre mot de passe" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informations de contact -->
                        <h5 class="fw-bold text-primary mb-4 mt-5 pb-3 border-bottom">
                            <i class="fas fa-address-book"></i> Informations de contact
                        </h5>
                        
                        <div class="form-group">
                            <label for="telephone" class="form-label">
                                <i class="fas fa-phone"></i>Numéro de téléphone <span class="required">*</span>
                            </label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" 
                                   value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>" 
                                   placeholder="0123456789" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="adresse" class="form-label">
                                <i class="fas fa-map-marker-alt"></i>Adresse postale <span class="required">*</span>
                            </label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="2" 
                                      placeholder="Numéro et nom de rue" required><?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?></textarea>
                            </div>
                            
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="code_postal" class="form-label">
                                        <i class="fas fa-mail-bulk"></i>Code postal <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="code_postal" name="code_postal" 
                                           value="<?php echo htmlspecialchars($_POST['code_postal'] ?? ''); ?>" 
                                           placeholder="75000" pattern="[0-9]{5}" required>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="ville" class="form-label">
                                        <i class="fas fa-city"></i>Ville <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="ville" name="ville" 
                                           value="<?php echo htmlspecialchars($_POST['ville'] ?? ''); ?>" 
                                           placeholder="Paris" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Conditions générales -->
                        <h5 class="fw-bold text-primary mb-4 mt-5 pb-3 border-bottom">
                            <i class="fas fa-file-contract"></i> Conditions générales
                        </h5>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="cgu_acceptees" name="cgu_acceptees" required>
                            <label class="form-check-label" for="cgu_acceptees">
                                J'accepte les <a href="https://servo.tools/cgu" target="_blank">Conditions Générales d'Utilisation</a> <span class="required">*</span>
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="cgv_acceptees" name="cgv_acceptees" required>
                            <label class="form-check-label" for="cgv_acceptees">
                                J'accepte les <a href="https://servo.tools/mentions-legales" target="_blank">Conditions Générales de Vente</a> <span class="required">*</span>
                            </label>
                    </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-rocket me-2"></i>Créer ma boutique
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

    <script>
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
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#dc3545';
            }
        });
        
        // Validation de la confirmation du mot de passe
        document.getElementById('password_confirm').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            if (this.value === password && password.length >= 6) {
                this.style.borderColor = '#28a745';
            } else {
                this.style.borderColor = '#dc3545';
            }
        });
        
        // Animation d'entrée
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.card-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>

<?php include_once(__DIR__ . '/marketing/shared/footer.php'); ?>
