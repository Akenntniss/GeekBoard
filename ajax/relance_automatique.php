<?php
/**
 * Script de relance automatique des devis
 * Ce script doit être exécuté par un cron job toutes les minutes
 */

// Définir le chemin de base
define('BASE_PATH', dirname(__DIR__));

// Inclure les fichiers nécessaires
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/sms_functions.php';

// Fonction pour logger avec timestamp
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
    error_log("RELANCE AUTO [$timestamp] $message");
}

try {
    logMessage("🚀 Début du script de relance automatique");
    
    // Heure actuelle
    $heure_actuelle = date('H:i');
    $date_actuelle = date('Y-m-d');
    
    logMessage("Heure actuelle: $heure_actuelle");
    
    // Récupérer la liste des magasins avec relance automatique active
    $main_pdo = getMainDBConnection();
    if (!$main_pdo) {
        throw new Exception("Impossible de se connecter à la base principale");
    }
    
    // Récupérer tous les magasins actifs
    $stmt = $main_pdo->prepare("SELECT id, name, db_host, db_name, db_user, db_pass, db_port FROM shops WHERE active = 1");
    $stmt->execute();
    $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Magasins trouvés: " . count($shops));
    
    $total_relances = 0;
    $total_erreurs = 0;
    
    foreach ($shops as $shop) {
        try {
            logMessage("--- Traitement du magasin: {$shop['name']} (ID: {$shop['id']}) ---");
            
            // Connexion à la base du magasin
            $shop_dsn = "mysql:host={$shop['db_host']};port=" . ($shop['db_port'] ?? '3306') . ";dbname={$shop['db_name']};charset=utf8mb4";
            $shop_pdo = new PDO($shop_dsn, $shop['db_user'], $shop['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Vérifier si les tables existent
            $tables_check = $shop_pdo->query("SHOW TABLES LIKE 'relance_automatique_config'");
            if ($tables_check->rowCount() === 0) {
                logMessage("Table relance_automatique_config non trouvée pour {$shop['name']}, création...");
                
                // Créer les tables manquantes
                $shop_pdo->exec("
                    CREATE TABLE IF NOT EXISTS `relance_automatique_config` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `shop_id` int(11) NOT NULL,
                      `est_active` tinyint(1) DEFAULT 0,
                      `relances_horaires` JSON DEFAULT NULL,
                      `derniere_execution` datetime DEFAULT NULL,
                      `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
                      `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `unique_shop` (`shop_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $shop_pdo->exec("
                    CREATE TABLE IF NOT EXISTS `relance_automatique_logs` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `shop_id` int(11) NOT NULL,
                      `devis_id` int(11) NOT NULL,
                      `heure_programmee` time NOT NULL,
                      `date_execution` datetime NOT NULL,
                      `statut` enum('succes','echec') DEFAULT 'succes',
                      `message` text DEFAULT NULL,
                      `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      KEY `idx_shop_date` (`shop_id`, `date_execution`),
                      KEY `idx_devis` (`devis_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                continue; // Passer au magasin suivant
            }
            
            // Récupérer la configuration de relance automatique
            $stmt = $shop_pdo->prepare("
                SELECT est_active, relances_horaires, derniere_execution 
                FROM relance_automatique_config 
                WHERE shop_id = ?
            ");
            $stmt->execute([$shop['id']]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$config || !$config['est_active']) {
                logMessage("Relance automatique désactivée pour {$shop['name']}");
                continue;
            }
            
            $horaires = json_decode($config['relances_horaires'], true);
            if (!$horaires || !in_array($heure_actuelle, $horaires)) {
                logMessage("Pas de relance prévue à $heure_actuelle pour {$shop['name']}");
                continue;
            }
            
            logMessage("🎯 Relance prévue à $heure_actuelle pour {$shop['name']}");
            
            // Vérifier si on a déjà fait une relance aujourd'hui à cette heure
            $stmt = $shop_pdo->prepare("
                SELECT COUNT(*) as count 
                FROM relance_automatique_logs 
                WHERE shop_id = ? 
                AND DATE(date_execution) = ? 
                AND heure_programmee = ?
            ");
            $stmt->execute([$shop['id'], $date_actuelle, $heure_actuelle]);
            $deja_execute = $stmt->fetch()['count'] > 0;
            
            if ($deja_execute) {
                logMessage("Relance déjà exécutée aujourd'hui à $heure_actuelle pour {$shop['name']}");
                continue;
            }
            
            // Récupérer les devis en attente ET les devis expirés depuis moins de 15 jours
            $stmt = $shop_pdo->prepare("
                SELECT 
                    d.id,
                    d.numero_devis,
                    d.total_ttc,
                    d.date_expiration,
                    d.lien_securise,
                    c.nom as client_nom,
                    c.prenom as client_prenom,
                    c.telephone as client_telephone,
                    CASE 
                        WHEN d.date_expiration > NOW() THEN 'en_attente'
                        WHEN d.date_expiration <= NOW() AND d.date_expiration >= DATE_SUB(NOW(), INTERVAL 15 DAY) THEN 'expire_recent'
                        ELSE 'expire_ancien'
                    END as statut_relance
                FROM devis d
                LEFT JOIN reparations r ON d.reparation_id = r.id
                LEFT JOIN clients c ON r.client_id = c.id
                WHERE d.statut = 'envoye' 
                AND (
                    d.date_expiration > NOW() 
                    OR (d.date_expiration <= NOW() AND d.date_expiration >= DATE_SUB(NOW(), INTERVAL 15 DAY))
                )
                AND c.telephone IS NOT NULL 
                AND c.telephone != ''
                ORDER BY d.date_creation ASC
            ");
            $stmt->execute();
            $devis_a_relancer = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Compter les différents types
            $devis_en_attente = array_filter($devis_a_relancer, function($d) { return $d['statut_relance'] === 'en_attente'; });
            $devis_expires_recents = array_filter($devis_a_relancer, function($d) { return $d['statut_relance'] === 'expire_recent'; });
            
            logMessage("Devis trouvés: " . count($devis_a_relancer) . " total (" . count($devis_en_attente) . " en attente, " . count($devis_expires_recents) . " expirés récents)");
            
            if (empty($devis_a_relancer)) {
                logMessage("Aucun devis à relancer pour {$shop['name']}");
                continue;
            }
            
            // Récupérer les templates SMS
            $stmt = $shop_pdo->prepare("
                SELECT * FROM sms_templates 
                WHERE type = 'devis' AND est_actif = 1 AND code IN ('devis_relance_auto', 'devis_relance_expire')
                ORDER BY code
            ");
            $stmt->execute();
            $templates_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organiser les templates
            $templates = [];
            foreach ($templates_results as $tmpl) {
                $templates[$tmpl['code']] = $tmpl;
            }
            
            // Templates par défaut si non trouvés
            if (!isset($templates['devis_relance_auto'])) {
                $templates['devis_relance_auto'] = [
                    'contenu' => 'Bonjour {client_nom}, votre devis #{devis_numero} de {montant}€ expire bientôt. Consultez-le ici: {lien_devis}'
                ];
            }
            
            if (!isset($templates['devis_relance_expire'])) {
                $templates['devis_relance_expire'] = [
                    'contenu' => 'Bonjour {client_nom}, votre devis #{devis_numero} de {montant}€ a expiré mais reste valable. Vous pouvez encore l\'accepter ici: {lien_devis}'
                ];
            }
            
            logMessage("Templates configurés pour {$shop['name']}");
            
            $relances_shop = 0;
            $erreurs_shop = 0;
            
            foreach ($devis_a_relancer as $devis) {
                try {
                    // Calculer les jours restants ou écoulés depuis expiration
                    $expiration = new DateTime($devis['date_expiration']);
                    $now = new DateTime();
                    $diff = $expiration->diff($now);
                    
                    if ($devis['statut_relance'] === 'en_attente') {
                        $jours_restants = $diff->days;
                        $template_code = 'devis_relance_auto';
                        $type_message = "en attente (expire dans $jours_restants jours)";
                    } else {
                        $jours_expires = $diff->days;
                        $template_code = 'devis_relance_expire';
                        $type_message = "expiré depuis $jours_expires jours";
                    }
                    
                    // Préparer le lien du devis
                    $lien_devis = "https://" . ($_SERVER['HTTP_HOST'] ?? $shop['name'] . '.mdgeek.top') . "/pages/devis_client.php?lien=" . ($devis['lien_securise'] ?? '');
                    
                    // Préparer les variables pour le template
                    $variables = [
                        'client_nom' => $devis['client_nom'],
                        'devis_numero' => $devis['numero_devis'],
                        'montant' => number_format($devis['total_ttc'] ?? 0, 2, ',', ' '),
                        'lien_devis' => $lien_devis,
                        'jours_restants' => isset($jours_restants) ? $jours_restants : 0,
                        'jours_expires' => isset($jours_expires) ? $jours_expires : 0
                    ];
                    
                    // Remplacer les variables dans le template
                    $message = $templates[$template_code]['contenu'];
                    foreach ($variables as $key => $value) {
                        $message = str_replace('{' . $key . '}', $value, $message);
                    }
                    
                    // Envoyer le SMS
                    $sms_result = send_sms(
                        $devis['client_telephone'], 
                        $message, 
                        'relance_auto',
                        $devis['id'],
                        null // Pas d'utilisateur spécifique pour les relances auto
                    );
                    
                    if ($sms_result && ($sms_result['success'] ?? false)) {
                        $relances_shop++;
                        $total_relances++;
                        
                        // Logger dans la table des relances automatiques
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO relance_automatique_logs 
                            (shop_id, devis_id, heure_programmee, date_execution, statut, message)
                            VALUES (?, ?, ?, NOW(), 'succes', ?)
                        ");
                        $stmt->execute([
                            $shop['id'],
                            $devis['id'],
                            $heure_actuelle,
                            "SMS envoyé avec succès"
                        ]);
                        
                        // Logger dans la table des devis
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO devis_logs (devis_id, action, description, utilisateur_type, donnees_supplementaires, date_action)
                            VALUES (?, 'relance_automatique', ?, 'systeme', ?, NOW())
                        ");
                        $stmt->execute([
                            $devis['id'],
                            "Relance automatique envoyée à {$devis['client_telephone']}",
                            json_encode([
                                'heure_relance' => $heure_actuelle,
                                'message' => $message,
                                'telephone' => $devis['client_telephone'],
                                'type_devis' => $devis['statut_relance']
                            ])
                        ]);
                        
                        logMessage("✅ SMS envoyé pour devis #{$devis['numero_devis']} ($type_message) à {$devis['client_telephone']}");
                        
                    } else {
                        $erreurs_shop++;
                        $total_erreurs++;
                        
                        // Logger l'erreur
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO relance_automatique_logs 
                            (shop_id, devis_id, heure_programmee, date_execution, statut, message)
                            VALUES (?, ?, ?, NOW(), 'echec', ?)
                        ");
                        $stmt->execute([
                            $shop['id'],
                            $devis['id'],
                            $heure_actuelle,
                            "Échec d'envoi SMS: " . json_encode($sms_result)
                        ]);
                        
                        logMessage("❌ Échec SMS pour devis #{$devis['numero_devis']}: " . json_encode($sms_result));
                    }
                    
                    // Délai entre les SMS pour éviter la surcharge
                    usleep(500000); // 0.5 seconde
                    
                } catch (Exception $e) {
                    $erreurs_shop++;
                    $total_erreurs++;
                    logMessage("❌ Erreur pour devis #{$devis['numero_devis']}: " . $e->getMessage());
                }
            }
            
            // Mettre à jour la dernière exécution
            $stmt = $shop_pdo->prepare("
                UPDATE relance_automatique_config 
                SET derniere_execution = NOW() 
                WHERE shop_id = ?
            ");
            $stmt->execute([$shop['id']]);
            
            logMessage("Magasin {$shop['name']}: $relances_shop relances envoyées, $erreurs_shop erreurs");
            
        } catch (Exception $e) {
            $total_erreurs++;
            logMessage("❌ Erreur pour magasin {$shop['name']}: " . $e->getMessage());
        }
    }
    
    logMessage("🏁 Fin du script - Total: $total_relances relances envoyées, $total_erreurs erreurs");
    
} catch (Exception $e) {
    logMessage("❌ Erreur critique: " . $e->getMessage());
    exit(1);
}

exit(0);
?>

