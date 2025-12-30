<?php
/**
 * ================================================================================
 * GESTIONNAIRE PROPRE POUR LA CRÉATION DE DEVIS
 * ================================================================================
 * Description: Script PHP simple pour sauvegarder les devis
 * Date: 2025-01-27
 * ================================================================================
 */

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le type de contenu comme JSON
header('Content-Type: application/json; charset=utf-8');

// Désactiver l'affichage des erreurs pour la production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Inclure les dépendances comme les autres fichiers AJAX
    require_once('../config/database.php');

    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode HTTP non autorisée');
    }

    // Vérifier l'authentification
    session_start();
    if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
        throw new Exception('Session expirée. Veuillez vous reconnecter.');
    }

    $shop_id = $_SESSION['shop_id'];

    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin');
    }

    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('Aucune donnée reçue');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Données JSON invalides: ' . json_last_error_msg());
    }

    // Valider les données obligatoires
    if (empty($data['reparation_id'])) {
        throw new Exception('ID de réparation manquant');
    }
    if (empty($data['titre'])) {
        throw new Exception('Titre du devis obligatoire');
    }
    if (empty($data['solutions']) || !is_array($data['solutions'])) {
        throw new Exception('Au moins une solution est requise');
    }

    $reparation_id = intval($data['reparation_id']);
    $titre = trim($data['titre']);
    $description = trim($data['description'] ?? '');
    $duree = trim($data['duree'] ?? '');
    $garantie = trim($data['garantie'] ?? '');
    $pannes = $data['pannes'] ?? [];
    $solutions = $data['solutions'];

    // Vérifier que la réparation existe (pas de shop_id dans la table reparations)
    $query = "SELECT id FROM reparations WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$reparation_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Réparation non trouvée');
    }

    // Commencer une transaction
    $pdo->beginTransaction();

    try {
        // Récupérer les informations de la réparation et du client
        $query = "SELECT r.*, c.id as client_id, c.nom, c.prenom, c.telephone, c.email FROM reparations r 
                  LEFT JOIN clients c ON r.client_id = c.id 
                  WHERE r.id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$reparation_id]);
        $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reparation) {
            throw new Exception('Réparation non trouvée');
        }

        // Log pour débugger les informations client
        error_log("DEBUG DEVIS CLEAN - Réparation ID: " . $reparation_id);
        error_log("DEBUG DEVIS CLEAN - Client téléphone: " . ($reparation['telephone'] ?? 'MANQUANT'));
        error_log("DEBUG DEVIS CLEAN - Client nom: " . ($reparation['nom'] ?? 'MANQUANT'));
        error_log("DEBUG DEVIS CLEAN - Client prénom: " . ($reparation['prenom'] ?? 'MANQUANT'));

        // Génération du numéro de devis avec approche sécurisée
        $year = date('Y');
        $max_attempts = 100; // Éviter les boucles infinies
        $attempt = 0;
        $numero_devis = '';
        
        do {
            $attempt++;
            // Générer un numéro séquentiel basé sur le timestamp et un nombre aléatoire
            $random_suffix = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $numero_devis = "DV-$year-$random_suffix";
            
            // Vérifier si ce numéro existe déjà
            $check_query = "SELECT COUNT(*) FROM devis WHERE numero_devis = ?";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute([$numero_devis]);
            $exists = $check_stmt->fetchColumn();
            
            if (!$exists) {
                break; // Numéro unique trouvé
            }
            
            error_log("DEBUG DEVIS CLEAN - Numéro $numero_devis existe déjà, tentative $attempt");
            
        } while ($attempt < $max_attempts);
        
        if ($attempt >= $max_attempts) {
            throw new Exception("Impossible de générer un numéro de devis unique après $max_attempts tentatives");
        }
        
        error_log("DEBUG DEVIS CLEAN - Numéro de devis généré: $numero_devis (tentative $attempt)");
        
        // Génération du lien sécurisé
        $lien_securise = md5($reparation_id . '-' . $reparation['client_id'] . '-' . time() . '-' . rand());
        
        // Calculer le total à partir des solutions
        $total_ht = 0;
        foreach ($solutions as $solution) {
            $total_ht += floatval($solution['prix'] ?? 0);
        }
        
        $taux_tva = 20.00;
        $total_ttc = $total_ht * (1 + $taux_tva / 100);

        // Insérer le devis principal avec la structure existante
        $query = "INSERT INTO devis (
            reparation_id, 
            client_id,
            employe_id,
            numero_devis,
            titre, 
            description_generale,
            date_expiration,
            taux_tva,
            total_ht,
            total_ttc,
            lien_securise,
            statut,
            date_envoi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'envoye', NOW())";
        
        $date_expiration = date('Y-m-d', strtotime('+14 days'));
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $reparation_id,
            $reparation['client_id'],
            $shop_id, // employe_id = shop_id
            $numero_devis,
            $titre,
            $description,
            $date_expiration,
            $taux_tva,
            $total_ht,
            $total_ttc,
            $lien_securise
        ]);

        $devis_id = $pdo->lastInsertId();

        // Insérer les pannes avec la structure existante
        if (!empty($pannes)) {
            $query = "INSERT INTO devis_pannes (devis_id, titre, description, gravite, ordre) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            
            foreach ($pannes as $index => $panne) {
                if (!empty($panne['nom'])) {
                    $stmt->execute([
                        $devis_id,
                        trim($panne['nom']),
                        trim($panne['description'] ?? ''),
                        trim($panne['gravite'] ?? 'moyenne'),
                        $index + 1
                    ]);
                }
            }
        }

        // Insérer les solutions avec la structure existante
        error_log("DEBUG DEVIS CLEAN - Nombre total de solutions reçues: " . count($solutions));
        $query = "INSERT INTO devis_solutions (
            devis_id, 
            nom, 
            description, 
            prix_total,
            duree_reparation, 
            garantie,
            ordre
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        
        $solutions_inserees = 0;
        foreach ($solutions as $index => $solution) {
            error_log("DEBUG DEVIS CLEAN - Solution $index: " . json_encode($solution));
            if (!empty($solution['nom']) && isset($solution['prix']) && $solution['prix'] > 0) {
                error_log("DEBUG DEVIS CLEAN - Insertion solution: nom=" . $solution['nom'] . ", prix=" . $solution['prix']);
                $stmt->execute([
                    $devis_id,
                    trim($solution['nom']),
                    trim($solution['description'] ?? ''),
                    floatval($solution['prix']),
                    trim($solution['duree'] ?? ''),
                    trim($solution['garantie'] ?? ''),
                    $index + 1
                ]);
                $solutions_inserees++;
                error_log("DEBUG DEVIS CLEAN - Solution $index insérée avec succès");
            } else {
                error_log("DEBUG DEVIS CLEAN - Solution $index ignorée - nom vide ou prix vide/zéro");
                error_log("DEBUG DEVIS CLEAN - Détails: nom='" . ($solution['nom'] ?? 'MANQUANT') . "', prix='" . ($solution['prix'] ?? 'MANQUANT') . "'");
            }
        }
        
        error_log("DEBUG DEVIS CLEAN - Total solutions insérées: $solutions_inserees sur " . count($solutions));

        // Mettre à jour le statut de la réparation vers "En attente de l'accord client"
        $stmt = $pdo->prepare("
            UPDATE reparations 
            SET devis_envoye = 1, 
                date_envoi_devis = NOW(), 
                statut = 'en_attente_accord_client',
                statut_id = 6
            WHERE id = ?
        ");
        $stmt->execute([$reparation_id]);
        
        // Log pour vérifier la mise à jour
        error_log("DEBUG DEVIS CLEAN - Statut réparation mis à jour vers 'en_attente_accord_client' pour ID: " . $reparation_id);

        // Valider la transaction
        $pdo->commit();
        
        // === ENVOI NOTIFICATION PUSH ===
        try {
            require_once __DIR__ . '/../includes/NotificationService.php';
            $title = "Devis envoyé au client";
            $body = "Devis $numero_devis - " . number_format($total_ttc, 2) . "€ TTC";
            NotificationService::sendToAdmins('quote_sent', $title, $body, [
                'url' => "/index.php?page=reparations&id=$reparation_id",
                'related_id' => $devis_id,
                'related_type' => 'devis'
            ]);
            error_log("NOTIFICATION: Quote sent notification for devis #$devis_id");
        } catch (Exception $e) {
            error_log("NOTIFICATION ERROR (devis): " . $e->getMessage());
        }

        // Envoyer le SMS avec le lien du devis (ASYNCHRONE)
        $sms_sent = false;
        $sms_message = '';
        $sms_queued = false;
        
        if (!empty($reparation['telephone'])) {
            try {
                // Récupérer le template SMS "En attente de validation"
                $stmt = $pdo->prepare("
                    SELECT contenu FROM sms_templates 
                    WHERE nom = 'En attente de validation' AND est_actif = 1 
                    LIMIT 1
                ");
                $stmt->execute();
                $template = $stmt->fetchColumn();
                
                if ($template) {
                    // Générer l'URL de suivi dynamique (pour [URL_SUIVI])
                    $current_host = $_SERVER['HTTP_HOST'] ?? 'servo.tools';
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'https://';
                    $suivi_url = $protocol . $current_host . '/suivi.php?id=' . $reparation_id;
                    
                    // Générer l'URL du devis dynamique (pour [URL_DEVIS])
                    $devis_url = $protocol . $current_host . '/pages/devis_client.php?lien=' . $lien_securise;
                    
                    // Récupérer les paramètres d'entreprise
                    $company_name = 'Maison du Geek';
                    $company_phone = '08 95 79 59 33';
                    
                    try {
                        $stmt_company = $pdo->prepare("SELECT cle, valeur FROM parametres WHERE cle IN ('company_name', 'company_phone')");
                        $stmt_company->execute();
                        $company_params = $stmt_company->fetchAll(PDO::FETCH_KEY_PAIR);
                        
                        if (!empty($company_params['company_name'])) {
                            $company_name = $company_params['company_name'];
                        }
                        if (!empty($company_params['company_phone'])) {
                            $company_phone = $company_params['company_phone'];
                        }
                    } catch (Exception $e) {
                        error_log("Erreur récupération paramètres entreprise: " . $e->getMessage());
                    }
                    
                    // Variables pour le template SMS
                    $variables = [
                        '[CLIENT_PRENOM]' => $reparation['prenom'],
                        '[CLIENT_NOM]' => $reparation['nom'],
                        '[APPAREIL_TYPE]' => $reparation['type_appareil'],
                        '[APPAREIL_MODELE]' => $reparation['modele'],
                        '[REPARATION_ID]' => $reparation_id,
                        '[PRIX]' => number_format($total_ttc, 2, ',', ' ') . ' €',
                        '[URL_SUIVI]' => $suivi_url,
                        '[URL_DEVIS]' => $devis_url,
                        '[DOMAINE]' => $current_host,
                        '[COMPANY_NAME]' => $company_name,
                        '[COMPANY_PHONE]' => $company_phone
                    ];
                    
                    $message_sms = $template;
                    foreach ($variables as $variable => $valeur) {
                        $message_sms = str_replace($variable, $valeur, $message_sms);
                    }
                    
                    // Enregistrer la notification SMS en base avec statut 'en_attente'
                    $stmt = $pdo->prepare("
                        INSERT INTO devis_notifications (devis_id, type, telephone, message, statut_envoi, date_programmee)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $devis_id,
                        'envoi_devis',
                        $reparation['telephone'],
                        $message_sms,
                        'en_attente'
                    ]);
                    
                    $sms_queued = true;
                    $sms_message = 'SMS mis en file d\'attente';
                    
                } else {
                    $sms_message = 'Template SMS non trouvé';
                }
            } catch (Exception $e) {
                error_log("Erreur préparation SMS devis: " . $e->getMessage());
                $sms_message = 'Erreur lors de la préparation du SMS: ' . $e->getMessage();
            }
        } else {
            $sms_message = 'Numéro de téléphone manquant';
        }

        // Réponse de succès - ENVOYER IMMÉDIATEMENT
        echo json_encode([
            'success' => true,
            'message' => 'Devis créé avec succès',
            'devis_id' => $devis_id,
            'numero_devis' => $numero_devis,
            'sms_sent' => $sms_sent,
            'sms_message' => $sms_message,
            'sms_queued' => $sms_queued,
            'data' => [
                'titre' => $titre,
                'nb_pannes' => count($pannes),
                'nb_solutions' => count($solutions),
                'total_ht' => $total_ht,
                'total_ttc' => $total_ttc,
                'lien_securise' => $lien_securise,
                'lien_complet' => "https://" . $_SERVER['HTTP_HOST'] . "/pages/devis_client.php?lien=" . $lien_securise
            ]
        ]);
        
        // ====================================================================
        // ENVOI SMS ASYNCHRONE APRÈS LA RÉPONSE CLIENT
        // ====================================================================
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request(); // Libère la connexion client
        }
        
        // Le code ci-dessous s'exécute APRÈS que le client ait reçu la réponse
        if ($sms_queued && !empty($reparation['telephone'])) {
            try {
                // Inclure les fonctions SMS
                if (!function_exists('send_sms')) {
                    require_once '../includes/sms_functions.php';
                }
                
                // Récupérer le message depuis la base
                $stmt = $pdo->prepare("
                    SELECT id, message FROM devis_notifications 
                    WHERE devis_id = ? AND type = 'envoi_devis' AND statut_envoi = 'en_attente'
                    LIMIT 1
                ");
                $stmt->execute([$devis_id]);
                $notification = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($notification && function_exists('send_sms')) {
                    // Envoyer le SMS
                    $sms_result = send_sms(
                        $reparation['telephone'], 
                        $notification['message'],
                        'envoi_devis',
                        $devis_id,
                        $shop_id
                    );
                    
                    // Mettre à jour le statut
                    if ($sms_result['success'] ?? false) {
                        $stmt = $pdo->prepare("
                            UPDATE devis_notifications 
                            SET statut_envoi = 'envoye', date_envoi = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$notification['id']]);
                        error_log("✅ SMS devis envoyé en arrière-plan pour devis #$devis_id");
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE devis_notifications 
                            SET statut_envoi = 'erreur', date_envoi = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$notification['id']]);
                        error_log("❌ Erreur envoi SMS devis #$devis_id: " . ($sms_result['message'] ?? 'Erreur inconnue'));
                    }
                }
            } catch (Exception $e) {
                error_log("❌ Erreur envoi SMS async devis #$devis_id: " . $e->getMessage());
            }
        }

    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Erreur création devis: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename(__FILE__),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>

