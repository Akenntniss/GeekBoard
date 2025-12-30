<?php
// Script de prolongation fonctionnel basé sur le debug qui marche
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Démarrer la session si nécessaire
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Définir le type de contenu comme JSON
    header('Content-Type: application/json');

    // Inclure les fichiers nécessaires
    require_once('../config/database.php');
    require_once('../includes/functions.php');

    // Vérifier l'authentification (même logique que le debug qui marche)
    $is_authenticated = false;
    $user_id = null;

    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $is_authenticated = true;
        $user_id = $_SESSION['user_id'];
    } elseif (isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
        $is_authenticated = true;
        $user_id = $_SESSION['shop_id'];
    } elseif (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
        $is_authenticated = true;
        $user_id = $_SESSION['admin_id'];
    }

    if (!$is_authenticated) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Non authentifié']);
        exit;
    }

    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
        exit;
    }

    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Données invalides']);
        exit;
    }

    $data = json_decode($input, true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Données invalides']);
        exit;
    }

    $devis_id = isset($data['devis_id']) ? (int)$data['devis_id'] : 0;
    $duree_jours = isset($data['duree_jours']) ? (int)$data['duree_jours'] : 0;

    // Validation des données
    if ($devis_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de devis invalide']);
        exit;
    }

    if ($duree_jours <= 0 || $duree_jours > 365) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Durée invalide (entre 1 et 365 jours)']);
        exit;
    }

    // Obtenir la connexion à la base de données du shop
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du shop');
    }

    // Vérifier que le devis existe et récupérer les informations complètes
    $check_sql = "SELECT d.*, r.client_id, c.nom, c.prenom, c.telephone, r.type_appareil, r.modele 
                  FROM devis d 
                  LEFT JOIN reparations r ON d.reparation_id = r.id 
                  LEFT JOIN clients c ON r.client_id = c.id 
                  WHERE d.id = ?";
    $check_stmt = $shop_pdo->prepare($check_sql);
    $check_stmt->execute([$devis_id]);
    $devis = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$devis) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Devis non trouvé']);
        exit;
    }

    // Vérifier que le devis peut être prolongé
    $expire_date = new DateTime($devis['date_expiration']);
    $today = new DateTime();

    if (!($expire_date < $today || $devis['statut'] === 'envoye')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ce devis ne peut pas être prolongé']);
        exit;
    }

    // Calculer la nouvelle date d'expiration
    $nouvelle_expiration = new DateTime();
    $nouvelle_expiration->add(new DateInterval('P' . $duree_jours . 'D'));

    // Mettre à jour le devis
    $update_sql = "UPDATE devis SET 
                   date_expiration = ?, 
                   statut = 'envoye',
                   date_modification = NOW() 
                   WHERE id = ?";

    $update_stmt = $shop_pdo->prepare($update_sql);
    $success = $update_stmt->execute([
        $nouvelle_expiration->format('Y-m-d H:i:s'),
        $devis_id
    ]);

    if (!$success) {
        throw new Exception('Erreur lors de la mise à jour du devis');
    }

    // Log de l'action
    error_log("Devis {$devis['numero_devis']} prolongé de {$duree_jours} jours par l'utilisateur {$user_id}");

    // Envoyer un SMS de notification de prolongation
    $sms_success = false;
    $sms_error = null;

    if (!empty($devis['telephone'])) {
        try {
            // Template SMS par défaut
            $template = "Bonjour {CLIENT_PRENOM}, votre devis {NUMERO_DEVIS} pour {APPAREIL_TYPE} {APPAREIL_MODELE} a été prolongé de {DUREE_JOURS} jour(s). Nouvelle date limite: {NOUVELLE_DATE}. Merci - {NOM_MAGASIN}";

            // Créer le lien vers le devis si disponible
            $lien_devis = "";
            if (!empty($devis['lien_securise'])) {
                $lien_devis = "https://" . $_SERVER['HTTP_HOST'] . "/pages/devis_client.php?lien=" . $devis['lien_securise'];
            }

            // Remplacer les variables dans le template
            $variables = [
                '{CLIENT_PRENOM}' => $devis['prenom'] ?: 'Client',
                '{CLIENT_NOM}' => $devis['nom'] ?: '',
                '{NUMERO_DEVIS}' => $devis['numero_devis'],
                '{APPAREIL_TYPE}' => $devis['type_appareil'] ?: 'appareil',
                '{APPAREIL_MODELE}' => $devis['modele'] ?: '',
                '{DUREE_JOURS}' => $duree_jours,
                '{NOUVELLE_DATE}' => $nouvelle_expiration->format('d/m/Y'),
                '{LIEN_DEVIS}' => $lien_devis,
                '{NOM_MAGASIN}' => 'GeekBoard'
            ];

            $message_sms = str_replace(array_keys($variables), array_values($variables), $template);

            // Inclure les fonctions SMS
            if (!function_exists('send_sms')) {
                if (file_exists('../includes/sms_functions.php')) {
                    require_once '../includes/sms_functions.php';
                }
            }

            // Envoyer le SMS
            if (function_exists('send_sms')) {
                $sms_result = send_sms(
                    $devis['telephone'], 
                    $message_sms,
                    'prolongation_devis',
                    $devis_id,
                    $user_id
                );

                if (isset($sms_result['success']) && $sms_result['success'] === true) {
                    $sms_success = true;
                    error_log("SMS de prolongation envoyé avec succès pour le devis {$devis['numero_devis']}");
                } else {
                    $sms_error = $sms_result['message'] ?? 'Erreur inconnue lors de l\'envoi SMS';
                    error_log("Erreur envoi SMS prolongation: " . $sms_error);
                }
            } else {
                $sms_error = "Fonction SMS non disponible";
                error_log("Fonction send_sms non trouvée");
            }

            // Enregistrer la notification dans l'historique si la table existe
            try {
                $notification_sql = "INSERT INTO devis_notifications (devis_id, type, telephone, message, statut_envoi, date_programmee)
                                   VALUES (?, ?, ?, ?, ?, NOW())";
                $notification_stmt = $shop_pdo->prepare($notification_sql);
                $notification_stmt->execute([
                    $devis_id,
                    'prolongation_devis',
                    $devis['telephone'],
                    $message_sms,
                    $sms_success ? 'envoye' : 'echec'
                ]);
            } catch (Exception $e) {
                // Si la table n'existe pas, on continue sans erreur
                error_log("Table devis_notifications non trouvée ou erreur: " . $e->getMessage());
            }

        } catch (Exception $e) {
            $sms_error = $e->getMessage();
            error_log("Exception lors de l'envoi SMS de prolongation: " . $sms_error);
        }
    }

    // Réponse de succès
    $response = [
        'success' => true,
        'message' => "Devis {$devis['numero_devis']} prolongé de {$duree_jours} jour" . ($duree_jours > 1 ? 's' : ''),
        'nouvelle_expiration' => $nouvelle_expiration->format('d/m/Y'),
        'devis_id' => $devis_id,
        'sms_envoye' => $sms_success
    ];

    if (!$sms_success && $sms_error) {
        $response['sms_error'] = $sms_error;
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Erreur lors de la prolongation du devis: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur serveur lors de la prolongation du devis'
    ]);
}
?>
