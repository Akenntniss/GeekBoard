<?php
/**
 * ================================================================================
 * TRAITEMENT DES RÉPONSES CLIENTS AUX DEVIS
 * ================================================================================
 * Description: Traite l'acceptation ou le refus de devis par les clients
 * Date: 2025-01-27
 * ================================================================================
 */

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Inclure le détecteur de base de données
    require_once '../config/subdomain_database_detector.php';

    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode HTTP non autorisée');
    }

    // Récupérer et décoder les données JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Données JSON invalides: ' . json_last_error_msg());
    }

    // Logger les données reçues pour debug
    error_log("=== TRAITEMENT DEVIS CLIENT ===");
    error_log("Action: " . ($data['action'] ?? 'non définie'));
    error_log("Devis ID: " . ($data['devis_id'] ?? 'non défini'));

    // Validation des données obligatoires
    if (!isset($data['action']) || !isset($data['devis_id'])) {
        throw new Exception('Action et ID de devis requis');
    }

    $action = $data['action'];
    $devis_id = intval($data['devis_id']);

    if (!in_array($action, ['accepter', 'refuser'])) {
        throw new Exception('Action non valide');
    }

    // Récupérer la connexion à la base de données
    $detector = new SubdomainDatabaseDetector();
    $shop_pdo = $detector->getConnection();
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }

    // Commencer la transaction
    $shop_pdo->beginTransaction();

    try {
        // 1. Vérifier que le devis existe et est dans le bon état
        $stmt = $shop_pdo->prepare("
            SELECT d.*, c.nom as client_nom, c.prenom as client_prenom,
                   c.telephone as client_telephone, c.email as client_email,
                   r.type_appareil, r.modele as appareil_modele
            FROM devis d
            LEFT JOIN clients c ON d.client_id = c.id
            LEFT JOIN reparations r ON d.reparation_id = r.id
            WHERE d.id = ? AND d.statut = 'envoye'
        ");
        $stmt->execute([$devis_id]);
        $devis = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$devis) {
            throw new Exception('Devis non trouvé ou non accessible');
        }

        // 2. Vérifier que le devis n'est pas expiré
        $date_expiration = new DateTime($devis['date_expiration']);
        $maintenant = new DateTime();
        
        if ($maintenant > $date_expiration) {
            // Marquer comme expiré
            $stmt = $shop_pdo->prepare("UPDATE devis SET statut = 'expire' WHERE id = ?");
            $stmt->execute([$devis_id]);
            throw new Exception('Ce devis a expiré et ne peut plus être modifié');
        }

        // 3. Traiter selon l'action
        if ($action === 'accepter') {
            // Validation des données d'acceptation
            if (!isset($data['solution_choisie_id']) || !isset($data['signature'])) {
                throw new Exception('Données d\'acceptation incomplètes');
            }

            $solution_choisie_id = intval($data['solution_choisie_id']);
            $signature = $data['signature'];
            $nom_complet = trim($devis['client_nom'] . ' ' . $devis['client_prenom']);

            if (empty($signature) || $signature === 'data:image/png;base64,') {
                throw new Exception('La signature est obligatoire');
            }

            // Vérifier que la solution existe pour ce devis
            $stmt = $shop_pdo->prepare("
                SELECT * FROM devis_solutions 
                WHERE id = ? AND devis_id = ?
            ");
            $stmt->execute([$solution_choisie_id, $devis_id]);
            $solution = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$solution) {
                throw new Exception('Solution non trouvée');
            }

            // 4. Mettre à jour le devis
            $stmt = $shop_pdo->prepare("
                UPDATE devis 
                SET statut = 'accepte', 
                    solution_choisie_id = ?,
                    date_reponse = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$solution_choisie_id, $devis_id]);

            // 5. Enregistrer l'acceptation avec signature
            $hash_verification = hash('sha256', $signature . $nom_complet . $devis_id . time());

            $stmt = $shop_pdo->prepare("
                INSERT INTO devis_acceptations (
                    devis_id, solution_choisie_id, signature_client, nom_complet,
                    email, telephone, ip_client, user_agent, hash_verification
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $devis_id,
                $solution_choisie_id,
                $signature,
                $nom_complet,
                $devis['client_email'],
                $devis['client_telephone'],
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $hash_verification
            ]);

            // Logger l'acceptation
            $stmt = $shop_pdo->prepare("
                INSERT INTO devis_logs (
                    devis_id, action, description, utilisateur_type,
                    ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");

            $description = "Devis accepté par le client. Solution choisie: " . $solution['nom'];

            $stmt->execute([
                $devis_id,
                'ACCEPTATION_CLIENT',
                $description,
                'client',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            // 7. Mettre à jour la réparation
            $stmt = $shop_pdo->prepare("
                UPDATE reparations 
                SET devis_accepte = 'oui', 
                    date_reponse_devis = NOW(),
                    prix_reparation = ?,
                    statut = 'devis_accepte'
                WHERE id = ?
            ");
            $stmt->execute([$solution['prix_total'], $devis['reparation_id']]);

            // SMS à implémenter plus tard si nécessaire

            $message_reponse = 'Devis accepté avec succès';
            
        } else { // Action = refuser
            // 4. Mettre à jour le devis
            $stmt = $shop_pdo->prepare("
                UPDATE devis 
                SET statut = 'refuse', 
                    date_reponse = NOW(),
                    notes_acceptation = ?
                WHERE id = ?
            ");
            $stmt->execute([$data['raison_refus'] ?? '', $devis_id]);

            // Logger le refus
            $stmt = $shop_pdo->prepare("
                INSERT INTO devis_logs (
                    devis_id, action, description, utilisateur_type,
                    ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");

            $description = "Devis refusé par le client";
            if (!empty($data['raison_refus'])) {
                $description .= ". Raison: " . $data['raison_refus'];
            }

            $stmt->execute([
                $devis_id,
                'REFUS_CLIENT', 
                $description,
                'client',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            // 6. Mettre à jour la réparation
            $stmt = $shop_pdo->prepare("
                UPDATE reparations 
                SET devis_accepte = 'non', 
                    date_reponse_devis = NOW(),
                    statut = 'devis_refuse'
                WHERE id = ?
            ");
            $stmt->execute([$devis['reparation_id']]);

            // SMS à implémenter plus tard si nécessaire

            $message_reponse = 'Devis refusé';
        }

        // Valider la transaction
        $shop_pdo->commit();

        // Réponse de succès
        $response = [
            'success' => true,
            'message' => $message_reponse,
            'action' => $action,
            'devis_id' => $devis_id
        ];

        error_log("Devis " . $devis['numero_devis'] . " " . $action . " avec succès");
        echo json_encode($response);

    } catch (Exception $e) {
        // Annuler la transaction
        $shop_pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Log de l'erreur
    error_log("ERREUR TRAITEMENT DEVIS CLIENT: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Réponse d'erreur
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
?> 