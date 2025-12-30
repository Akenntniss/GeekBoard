<?php
/**
 * MISE À JOUR PAR LOTS DES STATUTS DE RÉPARATIONS
 * Permet de mettre à jour le statut de plusieurs réparations en une seule opération
 * avec option d'envoi de SMS automatique
 */

// Charger les dépendances
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Obtenir la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

if (!$shop_pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

// Récupérer les données JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Valider les données
if (!isset($data['repair_ids']) || !is_array($data['repair_ids']) || empty($data['repair_ids'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'IDs de réparations manquants ou invalides'
    ]);
    exit;
}

if (!isset($data['new_status']) || empty($data['new_status'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Nouveau statut manquant'
    ]);
    exit;
}

$repair_ids = array_map('intval', $data['repair_ids']);
$new_status_code = $data['new_status']; // C'est le CODE du statut (ex: 'restituee')
$send_sms = isset($data['send_sms']) && $data['send_sms'] === true;

// Log de débogage
error_log("🔄 Mise à jour par lots - IDs: " . implode(',', $repair_ids) . " - Code statut: $new_status_code - SMS: " . ($send_sms ? 'oui' : 'non'));

try {
    // ÉTAPE 1: Convertir le CODE du statut en ID
    // Le frontend envoie le code (ex: 'restituee') mais on a besoin de l'ID numérique
    $stmt = $shop_pdo->prepare("SELECT id, code FROM statuts WHERE code = ? LIMIT 1");
    $stmt->execute([$new_status_code]);
    $status_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$status_info) {
        // Essayer plusieurs variantes
        $alternatives = [];
        
        // 1. Sans le dernier 'e' si ça finit par 'ee' (restituee -> restitue)
        if (substr($new_status_code, -2) === 'ee') {
            $alternatives[] = substr($new_status_code, 0, -1);
        }
        
        // 2. Chercher par LIKE %code%
        $stmt = $shop_pdo->prepare("SELECT id, code FROM statuts WHERE code LIKE ? LIMIT 1");
        $stmt->execute(['%' . substr($new_status_code, 0, -2) . '%']);
        $status_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$status_info && !empty($alternatives)) {
            // Essayer les alternatives
            foreach ($alternatives as $alt_code) {
                error_log("⚠️ Code statut '$new_status_code' introuvable, essai avec '$alt_code'");
                $stmt = $shop_pdo->prepare("SELECT id, code FROM statuts WHERE code = ? LIMIT 1");
                $stmt->execute([$alt_code]);
                $status_info = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($status_info) break;
            }
        }
        
        if (!$status_info) {
            // Renvoyer un message d'erreur avec les statuts disponibles
            $stmt = $shop_pdo->query("SELECT code FROM statuts ORDER BY code");
            $available = $stmt->fetchAll(PDO::FETCH_COLUMN);
            throw new Exception("Code de statut '$new_status_code' introuvable. Codes disponibles: " . implode(', ', $available));
        }
    }
    
    $new_status_id = $status_info['id'];
    $actual_status_code = $status_info['code'];
    
    error_log("✅ Code '$new_status_code' converti en ID: $new_status_id (code réel: '$actual_status_code')");
    
    // ÉTAPE 2: Préparer la requête de mise à jour
    $placeholders = implode(',', array_fill(0, count($repair_ids), '?'));
    
    // Mettre à jour BOTH statut_id ET statut pour être sûr
    $sql = "UPDATE reparations 
            SET statut_id = ?,
                statut = ?,
                date_modification = NOW() 
            WHERE id IN ($placeholders)";
    
    $params = array_merge([$new_status_id, $actual_status_code], $repair_ids);
    
    error_log("🔍 SQL: $sql");
    error_log("🔍 Params: " . json_encode($params));
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($params);
    
    $updated_count = $stmt->rowCount();
    
    // Préparer la liste des réparations pour envoi SMS asynchrone
    $sms_to_send = [];
    
    if ($send_sms) {
        error_log("📱 Préparation SMS pour $updated_count réparation(s)");
        
        $sql_get = "SELECT r.id, r.statut, r.type_appareil, r.marque, r.modele, 
                           r.prix_reparation, r.description_probleme, r.notes_techniques,
                           r.date_fin_prevue, r.date_reception,
                           c.telephone, c.nom, c.prenom, c.email
                    FROM reparations r
                    LEFT JOIN clients c ON r.client_id = c.id
                    WHERE r.id IN ($placeholders)";
        
        $stmt_get = $shop_pdo->prepare($sql_get);
        $stmt_get->execute($repair_ids);
        $repairs = $stmt_get->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("📱 Réparations récupérées: " . count($repairs));
        
        // Préparer les SMS pour envoi asynchrone
        foreach ($repairs as $repair) {
            error_log("📱 Réparation #{$repair['id']}: tel=" . ($repair['telephone'] ?? 'VIDE') . ", client=" . ($repair['nom'] ?? 'N/A'));
            
            if (!empty($repair['telephone'])) {
                $sms_to_send[] = [
                    'repair_id' => $repair['id'],
                    'telephone' => $repair['telephone'],
                    'status_code' => $actual_status_code,
                    'repair_data' => $repair
                ];
            } else {
                error_log("⚠️ Réparation #{$repair['id']}: Pas de numéro de téléphone client - SMS ignoré");
            }
        }
        
        error_log("📱 " . count($sms_to_send) . " SMS à envoyer en arrière-plan");
    }
    
    // Préparer la réponse - ENVOYER IMMÉDIATEMENT
    $message = "$updated_count réparation(s) mise(s) à jour avec succès";
    
    if ($send_sms && count($sms_to_send) > 0) {
        $message .= " - " . count($sms_to_send) . " SMS en file d'attente";
    }
    
    error_log("✅ Mise à jour par lots réussie - $updated_count réparation(s)");
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'updated_count' => $updated_count,
        'sms_queued' => count($sms_to_send)
    ]);
    
    // ====================================================================
    // ENVOI SMS ASYNCHRONE APRÈS LA RÉPONSE CLIENT
    // Envoi par tranches de 10 secondes pour ne pas surcharger le serveur SMS
    // ====================================================================
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request(); // Libère la connexion client
    }
    
    // Le code ci-dessous s'exécute APRÈS que le client ait reçu la réponse
    if (!empty($sms_to_send)) {
        $total_sms = count($sms_to_send);
        error_log("📱 Début de l'envoi asynchrone de $total_sms SMS (1 toutes les 10 secondes)");
        
        $sms_sent = 0;
        $sms_errors = [];
        
        // Charger la fonction d'envoi de SMS
        if (file_exists(__DIR__ . '/../includes/sms_functions.php')) {
            require_once __DIR__ . '/../includes/sms_functions.php';
            
            foreach ($sms_to_send as $index => $sms_data) {
                // Attendre 10 secondes entre chaque envoi (sauf pour le premier)
                if ($index > 0) {
                    error_log("⏳ Pause de 10 secondes avant le prochain SMS ({$index}/{$total_sms})");
                    sleep(10);
                }
                
                try {
                    error_log("📱 Envoi SMS " . ($index + 1) . "/$total_sms à {$sms_data['telephone']}");
                    
                    // Récupérer le message de template SMS
                    $message = getStatusSMSTemplate($sms_data['status_code'], $sms_data['repair_data']);
                    
                    if ($message && function_exists('send_sms')) {
                        $sms_result = send_sms($sms_data['telephone'], $message);
                        
                        if ($sms_result['success']) {
                            $sms_sent++;
                            error_log("✅ SMS " . ($index + 1) . "/$total_sms envoyé à {$sms_data['telephone']} pour réparation #{$sms_data['repair_id']}");
                        } else {
                            $sms_errors[] = "Réparation #{$sms_data['repair_id']}: " . $sms_result['message'];
                            error_log("❌ Erreur SMS " . ($index + 1) . "/$total_sms pour réparation #{$sms_data['repair_id']}: " . $sms_result['message']);
                        }
                    } else {
                        if (!$message) {
                            error_log("⚠️ Pas de template SMS pour le statut '{$sms_data['status_code']}'");
                        }
                        if (!function_exists('send_sms')) {
                            error_log("⚠️ Fonction send_sms non disponible");
                        }
                    }
                } catch (Exception $e) {
                    $sms_errors[] = "Réparation #{$sms_data['repair_id']}: " . $e->getMessage();
                    error_log("❌ Exception SMS " . ($index + 1) . "/$total_sms pour réparation #{$sms_data['repair_id']}: " . $e->getMessage());
                }
            }
            
            error_log("📱 Envoi asynchrone terminé - $sms_sent/$total_sms SMS envoyés, " . count($sms_errors) . " erreurs");
        } else {
            error_log("❌ Fichier sms_functions.php non trouvé");
        }
    }
    
} catch (PDOException $e) {
    error_log("❌ Erreur PDO lors de la mise à jour par lots: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("❌ Erreur lors de la mise à jour par lots: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}

/**
 * Récupère le template SMS pour un statut donné
 * Supporte toutes les variables définies dans sms_template_variables
 */
function getStatusSMSTemplate($status_code, $repair) {
    global $shop_pdo;
    
    try {
        // D'abord, récupérer l'ID du statut depuis son code
        $stmt = $shop_pdo->prepare("SELECT id FROM statuts WHERE code = ?");
        $stmt->execute([$status_code]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$status) {
            error_log("⚠️ Statut avec code '$status_code' introuvable");
            return null;
        }
        
        $status_id = $status['id'];
        
        // Récupérer le template SMS depuis la table sms_templates
        $stmt = $shop_pdo->prepare("
            SELECT contenu 
            FROM sms_templates 
            WHERE statut_id = ? AND est_actif = 1 
            LIMIT 1
        ");
        $stmt->execute([$status_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['contenu'])) {
            $template = $result['contenu'];
            
            // Récupérer les informations du magasin
            $shop_info = getShopInfo();
            
            // Générer les URLs
            $base_url = $shop_info['base_url'] ?? 'https://mdg.servo.tools';
            $repair_id = $repair['id'] ?? '';
            $url_suivi = $base_url . '/suivi.php?id=' . $repair_id;
            $url_devis = $base_url . '/devis_client.php?id=' . $repair_id;
            
            // Formater les dates
            $date_reception = '';
            if (!empty($repair['date_creation'])) {
                $date_reception = date('d/m/Y', strtotime($repair['date_creation']));
            }
            
            $date_fin_prevue = '';
            if (!empty($repair['date_fin_prevue'])) {
                $date_fin_prevue = date('d/m/Y', strtotime($repair['date_fin_prevue']));
            }
            
            // Formater le prix
            $prix = '';
            if (!empty($repair['prix'])) {
                $prix = number_format((float)$repair['prix'], 2, ',', ' ') . '€';
            }
            
            // Remplacer TOUTES les variables du template
            $replacements = [
                // Variables client
                '[CLIENT_NOM]' => $repair['nom'] ?? '',
                '[CLIENT_PRENOM]' => $repair['prenom'] ?? '',
                '[CLIENT_TELEPHONE]' => $repair['telephone'] ?? '',
                
                // Variables réparation
                '[REPARATION_ID]' => $repair_id,
                '[APPAREIL_TYPE]' => $repair['type_appareil'] ?? '',
                '[APPAREIL_MARQUE]' => $repair['marque'] ?? '',
                '[APPAREIL_MODELE]' => $repair['modele'] ?? '',
                '[DATE_RECEPTION]' => $date_reception,
                '[DATE_FIN_PREVUE]' => $date_fin_prevue,
                '[PRIX]' => $prix,
                '[NOTES_TECHNIQUES]' => $repair['notes_techniques'] ?? '',
                
                // Variables magasin
                '[COMPANY_NAME]' => $shop_info['name'] ?? 'MDG',
                '[COMPANY_PHONE]' => $shop_info['phone'] ?? '',
                '[COMPANY_NUMBER]' => $shop_info['siret'] ?? '',
                '[COMPANY_HOURS]' => $shop_info['hours'] ?? '',
                
                // URLs
                '[URL_SUIVI]' => $url_suivi,
                '[URL_DEVIS]' => $url_devis,
                
                // Anciennes syntaxes pour compatibilité
                '{nom}' => $repair['nom'] ?? '',
                '{prenom}' => $repair['prenom'] ?? '',
                '{type_appareil}' => $repair['type_appareil'] ?? '',
                '{modele}' => $repair['modele'] ?? '',
                '{id}' => $repair_id
            ];
            
            $message = str_replace(array_keys($replacements), array_values($replacements), $template);
            error_log("✅ Template SMS trouvé pour statut_id=$status_id (code='$status_code')");
            return $message;
        }
        
        error_log("⚠️ Aucun template SMS actif pour statut_id=$status_id (code='$status_code')");
        return null;
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération du template SMS: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère les informations du magasin actuel
 * La table parametres utilise une structure clé/valeur
 */
function getShopInfo() {
    global $shop_pdo;
    
    $default_info = [
        'name' => 'MDG',
        'phone' => '',
        'siret' => '',
        'hours' => '',
        'base_url' => 'https://mdg.servo.tools'
    ];
    
    try {
        // Récupérer tous les paramètres depuis la table parametres (structure clé/valeur)
        $stmt = $shop_pdo->query("SELECT cle, valeur FROM parametres");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convertir en tableau associatif
        $params = [];
        foreach ($rows as $row) {
            $params[$row['cle']] = $row['valeur'];
        }
        
        if (!empty($params)) {
            return [
                'name' => $params['company_name'] ?? $params['nom_magasin'] ?? $default_info['name'],
                'phone' => $params['company_phone'] ?? $params['telephone'] ?? $default_info['phone'],
                'siret' => $params['company_number'] ?? $params['siret'] ?? $default_info['siret'],
                'hours' => $params['company_hours'] ?? $params['horaires'] ?? $default_info['hours'],
                'base_url' => $params['base_url'] ?? $params['url'] ?? $default_info['base_url']
            ];
        }
    } catch (Exception $e) {
        error_log("⚠️ Impossible de récupérer les infos magasin: " . $e->getMessage());
    }
    
    return $default_info;
}
?>
