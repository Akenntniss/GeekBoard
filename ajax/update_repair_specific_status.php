<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Désactiver l'affichage des erreurs pour les réponses JSON propres
ini_set('display_errors', 0);
error_reporting(E_ALL);
 
// Définir le chemin de base pour les inclusions
$root_path = realpath(__DIR__ . '/..');
define('BASE_PATH', $root_path);

// Créer un fichier de log pour le débogage B
$logFile = __DIR__ . '/status_update.log';
file_put_contents($logFile, "--- Nouvelle tentative de mise à jour du statut ---\n", FILE_APPEND);
file_put_contents($logFile, "Date: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents($logFile, "BASE_PATH: " . BASE_PATH . "\n", FILE_APPEND);

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    $functions_path = realpath(__DIR__ . '/../includes/functions.php');
    
    file_put_contents($logFile, "Config path: " . $config_path . "\n", FILE_APPEND);
    file_put_contents($logFile, "Functions path: " . $functions_path . "\n", FILE_APPEND);

    // Inclure les fichiers requis
    if (!$config_path || !$functions_path) {
        throw new Exception('Impossible de localiser les fichiers requis');
    }

    // Inclure le fichier de configuration
    require_once $config_path;
    file_put_contents($logFile, "Paramètres de connexion: host=" . MAIN_DB_HOST . ", user=" . MAIN_DB_USER . "\n", FILE_APPEND);
    
    // Inclure les fonctions
    require_once $functions_path;

    // Initialiser la connexion à la base de données boutique
    $shop_pdo = getShopDBConnection();

    // Vérifier que la connexion PDO existe
    if (!isset($shop_pdo) || $shop_pdo === null) {
        file_put_contents($logFile, "ERREUR: Connexion PDO non disponible après inclusion de database.php\n", FILE_APPEND);
        throw new Exception('Erreur de connexion à la base de données: connexion PDO non disponible');
    }

    // Inclure la fonction d'envoi de SMS unifiée
    $sms_functions_path = realpath(__DIR__ . '/../includes/sms_functions.php');
    if ($sms_functions_path) {
        require_once $sms_functions_path;
        file_put_contents($logFile, "Fonction d'envoi de SMS unifiée incluse depuis: " . $sms_functions_path . "\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "AVERTISSEMENT: Impossible de localiser le fichier des fonctions SMS\n", FILE_APPEND);
    }

    file_put_contents($logFile, "Connexion PDO établie avec succès\n", FILE_APPEND);
    
    // Déterminer comment les données sont envoyées
    $input = file_get_contents('php://input');
    file_put_contents($logFile, "PHP INPUT: " . $input . "\n", FILE_APPEND);
    
    // Récupérer les données en analysant diverses sources
    if (!empty($input)) {
        // Tenter de décoder le JSON directement
        $data = json_decode($input, true);
        file_put_contents($logFile, "Données JSON reçues directement\n", FILE_APPEND);
    } else if (isset($_POST['json_data'])) {
        // Récupérer les données JSON depuis FormData
        $input = $_POST['json_data'];
        $data = json_decode($input, true);
        file_put_contents($logFile, "Données JSON reçues via FormData\n", FILE_APPEND);
    } else if (!empty($_POST)) {
        // Récupérer les données depuis les paramètres POST standards
        $data = [
            'repair_id' => isset($_POST['repair_id']) ? $_POST['repair_id'] : null,
            'status_id' => isset($_POST['status_id']) ? $_POST['status_id'] : null,
            'send_sms' => isset($_POST['send_sms']) ? $_POST['send_sms'] : false,
            'user_id' => isset($_POST['user_id']) ? $_POST['user_id'] : 1 // Utiliser admin par défaut
        ];
        file_put_contents($logFile, "Données reçues via POST standard\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "ERREUR: Aucune donnée n'a été reçue\n", FILE_APPEND);
        throw new Exception('Aucune donnée reçue');
    }

    // Vérifier si nous avons des données valides
    if (!isset($data) || !is_array($data)) {
        file_put_contents($logFile, "ERREUR: Données reçues invalides\n", FILE_APPEND);
        throw new Exception('Données reçues invalides');
    }
    
    file_put_contents($logFile, "Données décodées: " . print_r($data, true) . "\n", FILE_APPEND);

    // Valider les données requises
    if (!isset($data['repair_id']) || !isset($data['status_id'])) {
        file_put_contents($logFile, "ERREUR: Données requises manquantes\n", FILE_APPEND);
        throw new Exception('Données requises manquantes');
    }
    
    $repair_id = $data['repair_id'];
    $status_id = $data['status_id'];
    $send_sms = isset($data['send_sms']) ? (bool)$data['send_sms'] : false;
    $user_id = isset($data['user_id']) ? $data['user_id'] : 1; // Utiliser l'ID de l'admin par défaut
    
    file_put_contents($logFile, "Paramètres pour la mise à jour: 
        repair_id: $repair_id
        status_id: $status_id
        send_sms: " . ($send_sms ? 'true' : 'false') . "
        user_id: $user_id
    \n", FILE_APPEND);
    
    // Mise à jour du statut de la réparation
    file_put_contents($logFile, "Tentative de mise à jour...\n", FILE_APPEND);
    
    // Récupérer le code du statut
    $stmt = $shop_pdo->prepare("SELECT code FROM statuts WHERE id = ?");
    $stmt->execute([$status_id]);
    $status_code = $stmt->fetchColumn();
    
    if (!$status_code) {
        file_put_contents($logFile, "ERREUR: Code de statut non trouvé pour l'ID $status_id\n", FILE_APPEND);
        throw new Exception("Code de statut non trouvé pour l'ID $status_id");
    }
    
    file_put_contents($logFile, "Code de statut récupéré: $status_code\n", FILE_APPEND);
    
    // Traitement spécial pour le statut "Retard de livraison"
    if ($status_code === 'retard_livraison') {
        file_put_contents($logFile, "STATUT SPÉCIAL: Retard de livraison détecté - Envoi SMS uniquement sans changement de statut\n", FILE_APPEND);
        
        // Pour "Retard de livraison", on envoie seulement le SMS sans changer le statut
        $sms_sent = false;
        $sms_message = '';
        
        // Récupérer les informations de la réparation actuelle (sans la modifier)
        $stmt = $shop_pdo->prepare("SELECT r.*, c.telephone, c.nom as client_nom, c.prenom as client_prenom FROM reparations r JOIN clients c ON r.client_id = c.id WHERE r.id = ?");
        $stmt->execute([$repair_id]);
        $repair_data = $stmt->fetch();
        
        if ($repair_data) {
            file_put_contents($logFile, "Données de réparation récupérées pour SMS Retard de livraison\n", FILE_APPEND);
            
            // Récupérer le template SMS pour "Retard de livraison"
            $templateSQL = "SELECT contenu FROM sms_templates WHERE code = 'retard_livraison' AND est_actif = 1";
            $templateStmt = $shop_pdo->prepare($templateSQL);
            $templateStmt->execute();
            $template = $templateStmt->fetchColumn();
            
            if ($template && !empty($repair_data['telephone'])) {
                file_put_contents($logFile, "Template SMS Retard de livraison trouvé\n", FILE_APPEND);
                
                // Générer l'URL de suivi dynamique
                $suivi_url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'servo.tools') . '/suivi.php?id=' . ($repair_data['id'] ?? '');
                
                // Remplacer les variables dans le template
                $message = str_replace([
                    '[CLIENT_NOM]',
                    '[CLIENT_PRENOM]',
                    '[REPARATION_ID]',
                    '[APPAREIL_TYPE]',
                    '[APPAREIL_MARQUE]',
                    '[APPAREIL_MODELE]',
                    '[LIEN]',
                    '[URL_SUIVI]',
                    '[DATE_RECEPTION]',
                    '[DATE_FIN_PREVUE]'
                ], [
                    $repair_data['client_nom'] ?? '',
                    $repair_data['client_prenom'] ?? '',
                    $repair_data['id'] ?? '',
                    $repair_data['type_appareil'] ?? '',
                    $repair_data['marque'] ?? '',
                    $repair_data['modele'] ?? '',
                    $suivi_url, // [LIEN] pour compatibilité
                    $suivi_url, // [URL_SUIVI] nouvelle variable
                    $repair_data['date_reception'] ? date('d/m/Y', strtotime($repair_data['date_reception'])) : '',
                    $repair_data['date_fin_prevue'] ? date('d/m/Y', strtotime($repair_data['date_fin_prevue'])) : ''
                ], $template);
                
                // Stocker les données SMS pour envoi async
                $sms_data_retard = [
                    'telephone' => $repair_data['telephone'],
                    'message' => $message,
                    'client_id' => $repair_data['client_id'],
                    'user_id' => $user_id
                ];
                $sms_sent = true;
                $sms_message = 'SMS en cours d\'envoi...';
                file_put_contents($logFile, "SMS Retard de livraison préparé pour envoi async\n", FILE_APPEND);
            } else {
                $sms_message = 'Template SMS ou numéro de téléphone manquant';
                file_put_contents($logFile, $sms_message . "\n", FILE_APPEND);
            }
        } else {
            $sms_message = 'Données de réparation non trouvées';
            file_put_contents($logFile, $sms_message . "\n", FILE_APPEND);
        }
        
        // Préparer la réponse
        $response = [
            'success' => true,
            'message' => 'Notification "Retard de livraison" envoyée - Statut de la réparation inchangé',
            'data' => [
                'badge' => [
                    'text' => $repair_data['statut'] ?? 'Statut actuel',
                    'color' => '#ffc107'
                ],
                'sms_sent' => $sms_sent,
                'sms_message' => $sms_message,
                'status_changed' => false,
                'notification_type' => 'retard_livraison'
            ]
        ];
        
        file_put_contents($logFile, "Réponse pour Retard de livraison: " . json_encode($response) . "\n", FILE_APPEND);
        
        // Nettoyer les buffers et envoyer la réponse
        $json_response = json_encode($response);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        header('Connection: close');
        header('Content-Length: ' . strlen($json_response));
        echo $json_response;
        
        // Flush et continuer en arrière-plan
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
        }
        
        // Envoi SMS en arrière-plan
        if (isset($sms_data_retard) && function_exists('send_sms')) {
            ignore_user_abort(true);
            set_time_limit(30);
            
            file_put_contents($logFile, "Envoi SMS async retard_livraison vers " . $sms_data_retard['telephone'] . "\n", FILE_APPEND);
            try {
                $sms_result = send_sms(
                    $sms_data_retard['telephone'], 
                    $sms_data_retard['message'], 
                    'retard_livraison', 
                    $sms_data_retard['client_id'], 
                    $sms_data_retard['user_id']
                );
                file_put_contents($logFile, "Résultat SMS retard: " . json_encode($sms_result) . "\n", FILE_APPEND);
            } catch (Exception $e) {
                file_put_contents($logFile, "Erreur SMS retard: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        }
        
        file_put_contents($logFile, "--- Fin requête retard_livraison ---\n\n", FILE_APPEND);
        exit;
    }
    
    // Traitement normal pour tous les autres statuts
    file_put_contents($logFile, "Traitement normal du statut: $status_code\n", FILE_APPEND);
    
    // Mise à jour des deux colonnes: statut_id et statut
    $stmt = $shop_pdo->prepare("UPDATE reparations SET statut_id = ?, statut = ?, date_modification = NOW() WHERE id = ?");
    $result = $stmt->execute([$status_id, $status_code, $repair_id]);
    
    if (!$result) {
        file_put_contents($logFile, "ERREUR lors de la mise à jour: " . implode(", ", $stmt->errorInfo()) . "\n", FILE_APPEND);
        throw new Exception('Erreur lors de la mise à jour du statut: ' . implode(", ", $stmt->errorInfo()));
        }
    
    // Vérifier si des lignes ont été affectées
    if ($stmt->rowCount() === 0) {
        file_put_contents($logFile, "AVERTISSEMENT: Aucune ligne affectée - la réparation n'existe peut-être pas ou le statut est inchangé\n", FILE_APPEND);
        // Ne pas lancer d'exception, juste un avertissement dans le log
            } else {
        file_put_contents($logFile, "Mise à jour réussie: " . $stmt->rowCount() . " ligne(s) affectée(s)\n", FILE_APPEND);
        
        // Récupérer le statut précédent pour le journal
        $stmt_prev = $shop_pdo->prepare("SELECT statut_apres FROM reparation_logs WHERE reparation_id = ? AND action_type = 'changement_statut' ORDER BY date_action DESC LIMIT 1");
        $stmt_prev->execute([$repair_id]);
        $previous_status = $stmt_prev->fetchColumn();
        
        if (!$previous_status) {
            // Si aucun statut précédent dans les logs, essayer de trouver une valeur par défaut
            $previous_status = 'inconnu';
        }
        
        // Insérer un enregistrement dans reparation_logs
        try {
            $stmt_log = $shop_pdo->prepare("
                INSERT INTO reparation_logs (
                    reparation_id, employe_id, action_type, statut_avant, statut_apres, details
                ) VALUES (?, ?, 'changement_statut', ?, ?, ?)
            ");
            
            $details = "Mise à jour du statut avec SMS " . ($send_sms ? "activé" : "désactivé");
            $stmt_log->execute([
                $repair_id, 
                $user_id, 
                $previous_status, 
                $status_code,
            $details
        ]);
        
            file_put_contents($logFile, "Log enregistré dans la table reparation_logs\n", FILE_APPEND);
        } catch (Exception $e) {
            file_put_contents($logFile, "ERREUR lors de l'enregistrement du log: " . $e->getMessage() . "\n", FILE_APPEND);
            // Ne pas bloquer le processus en cas d'erreur d'enregistrement du log
        }
        
        // === ENVOI NOTIFICATION PUSH ===
        try {
            require_once __DIR__ . '/../includes/NotificationService.php';
            $employeId = $_SESSION['user_id'] ?? $user_id;
            NotificationService::notifyRepairStatusChange($repair_id, $previous_status, $status_code, $employeId);
            file_put_contents($logFile, "NOTIFICATION: Push notification sent for repair $repair_id status change\n", FILE_APPEND);
        } catch (Exception $e) {
            file_put_contents($logFile, "NOTIFICATION ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
    
    // Récupérer les informations sur le statut pour l'affichage du badge
    $stmt = $shop_pdo->prepare("SELECT nom, code FROM statuts WHERE id = ?");
    $stmt->execute([$status_id]);
    $status = $stmt->fetch();
    
    // Générer une réponse
    $response = [
        'success' => true,
        'message' => 'Statut mis à jour avec succès',
        'data' => [
            'badge' => [
                'text' => $status['nom'] ?? 'Statut inconnu',
                'color' => $status['code'] ?? '#999999'
            ],
            'sms_sent' => false,
            'sms_message' => 'SMS non traité'
        ]
    ];
    
    // Si l'envoi de SMS est demandé, préparer les données mais répondre d'abord
    $sms_data = null;
    if ($send_sms) {
        try {
            file_put_contents($logFile, "Préparation de l'envoi de SMS (mode async)\n", FILE_APPEND);
            
            // Récupérer les informations du client et de la réparation
            $stmt = $shop_pdo->prepare("
                SELECT r.*, c.telephone, c.nom as client_nom, c.prenom as client_prenom
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                WHERE r.id = ?
            ");
            $stmt->execute([$repair_id]);
            $repair_data = $stmt->fetch();
            
            if ($repair_data && !empty($repair_data['telephone'])) {
                $telephone = $repair_data['telephone'];
                $client_nom = $repair_data['client_nom'];
                
                // Récupérer le template correspondant au statut_id
                $stmt = $shop_pdo->prepare("
                    SELECT id, contenu 
                    FROM sms_templates 
                    WHERE statut_id = ? AND est_actif = 1
                    LIMIT 1
                ");
                $stmt->execute([$status_id]);
                $template = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($template && !empty($template['contenu'])) {
                    $message = $template['contenu'];
                    
                    // Récupérer les paramètres d'entreprise depuis la table parametres
                    $company_name = 'Maison du Geek';
                    $company_phone = '08 95 79 59 33';
                    $company_address = '';
                    $company_number = '';
                    $company_hours = '';
                    
                    try {
                        $stmt_company = $shop_pdo->query("SELECT cle, valeur FROM parametres");
                        $rows_company = $stmt_company->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($rows_company as $row) {
                            if (!empty($row['valeur'])) {
                                switch ($row['cle']) {
                                    case 'company_name': $company_name = $row['valeur']; break;
                                    case 'company_phone': $company_phone = $row['valeur']; break;
                                    case 'company_address': $company_address = $row['valeur']; break;
                                    case 'company_number': $company_number = $row['valeur']; break;
                                    case 'company_hours': $company_hours = $row['valeur']; break;
                                }
                            }
                        }
                    } catch (Exception $e) {
                        file_put_contents($logFile, "Erreur récupération parametres: " . $e->getMessage() . "\n", FILE_APPEND);
                    }
                    
                    // Générer les URLs dynamiques
                    $host = $_SERVER['HTTP_HOST'] ?? 'servo.tools';
                    $url_suivi = 'https://' . $host . '/suivi.php?id=' . $repair_id;
                    $url_devis = 'https://' . $host . '/devis_client.php?id=' . $repair_id;
                    
                    // Tableau des remplacements - TOUTES les 17 variables
                    $replacements = [
                        // Variables client
                        '[CLIENT_NOM]' => $repair_data['client_nom'] ?? '',
                        '[CLIENT_PRENOM]' => $repair_data['client_prenom'] ?? '',
                        '[CLIENT_TELEPHONE]' => $repair_data['telephone'] ?? '',
                        // Variables réparation
                        '[REPARATION_ID]' => $repair_id,
                        '[APPAREIL_TYPE]' => $repair_data['type_appareil'] ?? '',
                        '[APPAREIL_MARQUE]' => $repair_data['marque'] ?? '',
                        '[APPAREIL_MODELE]' => $repair_data['modele'] ?? '',
                        '[DATE_RECEPTION]' => !empty($repair_data['date_reception']) ? date('d/m/Y', strtotime($repair_data['date_reception'])) : '',
                        '[DATE_FIN_PREVUE]' => !empty($repair_data['date_fin_prevue']) ? date('d/m/Y', strtotime($repair_data['date_fin_prevue'])) : '',
                        '[PRIX]' => !empty($repair_data['prix_reparation']) ? number_format($repair_data['prix_reparation'], 2, ',', ' ') . '€' : '',
                        '[NOTES_TECHNIQUES]' => $repair_data['notes_techniques'] ?? '',
                        // Variables magasin
                        '[COMPANY_NAME]' => $company_name,
                        '[COMPANY_PHONE]' => $company_phone,
                        '[COMPANY_ADDRESS]' => $company_address,
                        '[COMPANY_NUMBER]' => $company_number,
                        '[COMPANY_HOURS]' => $company_hours,
                        // URLs
                        '[URL_SUIVI]' => $url_suivi,
                        '[URL_DEVIS]' => $url_devis,
                        '[LIEN]' => $url_suivi
                    ];
                    foreach ($replacements as $placeholder => $value) {
                        $message = str_replace($placeholder, $value, $message);
                    }
                    
                    // Stocker les données pour envoi après réponse
                    $sms_data = [
                        'telephone' => $telephone,
                        'message' => $message,
                        'repair_id' => $repair_id,
                        'user_id' => $user_id,
                        'client_nom' => $client_nom
                    ];
                    
                    $response['data']['sms_sent'] = true;
                    $response['data']['sms_message'] = "SMS en cours d'envoi...";
                } else {
                    $response['data']['sms_message'] = "Pas de template actif pour ce statut - Aucun SMS envoyé";
                    // $sms_data reste null, donc pas d'envoi
                }
            } else {
                $response['data']['sms_message'] = "Numéro de téléphone manquant";
            }
        } catch (Exception $e) {
            file_put_contents($logFile, "Erreur préparation SMS: " . $e->getMessage() . "\n", FILE_APPEND);
            $response['data']['sms_message'] = "Erreur: " . $e->getMessage();
        }
    }
    
    // Logger l'action
    try {
        $stmt = $shop_pdo->prepare("
            INSERT INTO logs (user_id, action, details) 
            VALUES (?, 'update_repair_status', ?)
        ");
        $details = json_encode([
            'repair_id' => $repair_id,
            'new_status_id' => $status_id,
            'sms_queued' => ($sms_data !== null)
        ]);
        $stmt->execute([$user_id, $details]);
    } catch (Exception $e) {
        // Ignorer les erreurs de logging
    }
    
    // === RÉPONDRE IMMÉDIATEMENT AU CLIENT ===
    file_put_contents($logFile, "Réponse finale: " . json_encode($response) . "\n", FILE_APPEND);
    
    $json_response = json_encode($response);
    
    // Nettoyer TOUS les buffers de sortie pour éviter les espaces parasites
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Fermer la connexion avec le client
    header('Content-Type: application/json');
    header('Connection: close');
    header('Content-Length: ' . strlen($json_response));
    echo $json_response;
    
    // Flush et continuer en arrière-plan
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        flush();
    }
    
    // === ENVOI SMS EN ARRIÈRE-PLAN ===
    if ($sms_data !== null && function_exists('send_sms')) {
        ignore_user_abort(true);
        set_time_limit(30);
        
        file_put_contents($logFile, "Envoi SMS async vers " . $sms_data['telephone'] . "\n", FILE_APPEND);
        
        try {
            $sms_result = send_sms(
                $sms_data['telephone'], 
                $sms_data['message'], 
                'status_change', 
                $sms_data['repair_id'], 
                $sms_data['user_id']
            );
            file_put_contents($logFile, "Résultat SMS: " . json_encode($sms_result) . "\n", FILE_APPEND);
        } catch (Exception $e) {
            file_put_contents($logFile, "Erreur envoi SMS: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
    
    file_put_contents($logFile, "--- Fin de la requête ---\n\n", FILE_APPEND);
    exit;
    
} catch (Exception $e) {
    // Logger l'erreur
    file_put_contents($logFile, "ERREUR FATALE: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Envoyer une réponse d'erreur
    $error_response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    file_put_contents($logFile, "Réponse d'erreur: " . json_encode($error_response) . "\n", FILE_APPEND);
    echo json_encode($error_response);
}

// Ajouter un séparateur de fin dans le log
file_put_contents($logFile, "--- Fin de la requête ---\n\n", FILE_APPEND);

/**
 * Convertit un nom de catégorie en valeur ENUM pour la colonne statut
 * 
 * @param string $categorie_nom Le nom de la catégorie
 * @return string La valeur ENUM correspondante
 */
function map_status_to_enum($categorie_nom) {
    $map = [
        'Nouvelle' => 'En attente',
        'En cours' => 'En cours',
        'En attente' => 'En attente',
        'Terminé' => 'Terminé',
        'Annulé' => 'Terminé' // Il n'y a pas d'équivalent direct, nous utilisons 'Terminé'
    ];
    
    return isset($map[$categorie_nom]) ? $map[$categorie_nom] : 'En attente';
} 